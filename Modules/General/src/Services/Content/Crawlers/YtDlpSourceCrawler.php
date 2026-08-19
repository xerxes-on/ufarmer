<?php

declare(strict_types=1);

namespace Modules\General\Services\Content\Crawlers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\General\Models\ContentDraft;
use Modules\General\Models\ContentSource;
use Modules\General\Services\Content\ContentMediaDownloader;
use Symfony\Component\Process\Process;
use Throwable;

final class YtDlpSourceCrawler
{
    public function __construct(
        private readonly ContentMediaDownloader $mediaDownloader,
    ) {}

    public function supports(ContentSource $source): bool
    {
        return in_array($source->source_type, ['youtube', 'instagram', 'telegram'], true);
    }

    /**
     * @return array{created: int, skipped: int, downloaded: int}
     */
    public function crawl(ContentSource $source, int $limit, bool $download): array
    {
        $items = $this->metadata($source, $limit);
        $created = 0;
        $skipped = 0;
        $downloaded = 0;

        foreach ($items as $item) {
            $sourceUrl = $this->sourceUrl($item, $source);

            if ($sourceUrl === null) {
                $skipped++;

                continue;
            }

            $existingDraft = $this->existingDraft($source, $sourceUrl);

            if ($existingDraft instanceof ContentDraft) {
                $skipped++;
                $downloaded += $this->downloadMissingMedia($source, $existingDraft, $sourceUrl, $download);

                continue;
            }

            $draft = $this->createDraft($source, $item, $sourceUrl);
            $created++;
            $downloaded += $this->downloadMissingMedia($source, $draft, $sourceUrl, $download);
        }

        $source->forceFill(['last_crawled_at' => now()])->save();

        return [
            'created' => $created,
            'skipped' => $skipped,
            'downloaded' => $downloaded,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function metadata(ContentSource $source, int $limit): array
    {
        $cookiesFile = $this->cookiesFile($source);

        try {
            $command = [
                (string) config('general.content.yt_dlp_binary', 'yt-dlp'),
                '--dump-json',
                '--no-warnings',
                '--playlist-end',
                (string) $limit,
            ];

            if ($cookiesFile !== null) {
                $command[] = '--cookies';
                $command[] = $cookiesFile;
            }

            $command[] = $source->url;

            $process = new Process($command);
            $process->setTimeout((int) config('general.content.crawl_timeout', 600));
            $process->mustRun();

            return collect(explode("\n", trim($process->getOutput())))
                ->filter()
                ->map(static fn (string $line): ?array => json_decode($line, true))
                ->filter(static fn (mixed $item): bool => is_array($item))
                ->values()
                ->all();
        } finally {
            $this->cleanupTempCookies($source, $cookiesFile);
        }
    }

    private function createDraft(ContentSource $source, array $item, string $sourceUrl): ContentDraft
    {
        $title = trim((string) ($item['title'] ?? $item['fulltitle'] ?? 'Untitled'));
        $description = trim((string) ($item['description'] ?? $item['playlist_title'] ?? $title));
        $thumbnail = $this->thumbnail($item);

        return ContentDraft::create([
            'content_source_id' => $source->id,
            'content_type' => $this->contentType($source),
            'status' => ContentDraft::STATUS_DRAFT,
            'source_url' => $sourceUrl,
            'source_title' => $title,
            'title' => $this->localized($title),
            'preview' => $this->localized(Str::limit($description, 220)),
            'body' => $this->localized($description),
            'media_original_url' => $sourceUrl,
            'media_url' => $sourceUrl,
            'thumbnail_url' => $thumbnail,
            'source_payload' => [
                'crawler' => 'yt-dlp',
                'source_type' => $source->source_type,
                'external_id' => $item['id'] ?? null,
                'duration' => $item['duration'] ?? null,
                'uploader' => $item['uploader'] ?? $item['channel'] ?? null,
                'webpage_url' => $item['webpage_url'] ?? null,
            ],
        ]);
    }

    private function download(ContentSource $source, string $sourceUrl): ?string
    {
        $directory = storage_path('app/content-crawler/'.(string) Str::uuid());
        File::ensureDirectoryExists($directory);

        $cookiesFile = $this->cookiesFile($source);

        try {
            $command = [
                (string) config('general.content.yt_dlp_binary', 'yt-dlp'),
                '--no-warnings',
                '--no-playlist',
                '-f',
                'bv*+ba/best',
                '--merge-output-format',
                'mp4',
                '-o',
                $directory.'/%(id)s.%(ext)s',
            ];

            if ($cookiesFile !== null) {
                $command[] = '--cookies';
                $command[] = $cookiesFile;
            }

            $command[] = $sourceUrl;

            $process = new Process($command);
            $process->setTimeout((int) config('general.content.crawl_timeout', 600));
            $process->mustRun();

            return $this->largestFile($directory);
        } catch (Throwable) {
            return null;
        } finally {
            $this->cleanupTempCookies($source, $cookiesFile);
            $this->cleanupDirectoryExceptLargest($directory);
        }
    }

    private function existingDraft(ContentSource $source, string $sourceUrl): ?ContentDraft
    {
        return ContentDraft::query()
            ->where('content_source_id', $source->id)
            ->where('source_url', $sourceUrl)
            ->first();
    }

    private function downloadMissingMedia(ContentSource $source, ContentDraft $draft, string $sourceUrl, bool $download): int
    {
        if (! $download || filled($draft->media_path)) {
            return 0;
        }

        $localPath = $this->download($source, $sourceUrl);

        if ($localPath === null) {
            return 0;
        }

        try {
            $this->mediaDownloader->storeLocalFile($draft, $localPath, $sourceUrl);

            return 1;
        } finally {
            @unlink($localPath);
            @rmdir(dirname($localPath));
        }
    }

    private function contentType(ContentSource $source): string
    {
        $types = collect($source->content_types)->filter()->values();

        if ($types->contains(ContentDraft::TYPE_VIDEO)) {
            return ContentDraft::TYPE_VIDEO;
        }

        return (string) ($types->first() ?: ContentDraft::TYPE_VIDEO);
    }

    private function sourceUrl(array $item, ContentSource $source): ?string
    {
        $url = $item['webpage_url'] ?? $item['original_url'] ?? $item['url'] ?? null;

        if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $id = $item['id'] ?? null;

        if ($source->source_type === 'youtube' && is_string($id) && $id !== '') {
            return "https://www.youtube.com/watch?v={$id}";
        }

        return null;
    }

    private function thumbnail(array $item): ?string
    {
        $thumbnail = $item['thumbnail'] ?? null;

        if (is_string($thumbnail) && filter_var($thumbnail, FILTER_VALIDATE_URL)) {
            return $thumbnail;
        }

        $thumbnails = $item['thumbnails'] ?? [];

        if (! is_array($thumbnails)) {
            return null;
        }

        return collect($thumbnails)
            ->pluck('url')
            ->filter(static fn (mixed $url): bool => is_string($url) && filter_var($url, FILTER_VALIDATE_URL))
            ->last();
    }

    /**
     * @return array{uz: string, ru: string, en: string}
     */
    private function localized(string $value): array
    {
        return [
            'uz' => $value,
            'ru' => $value,
            'en' => $value,
        ];
    }

    private function cookiesFile(ContentSource $source): ?string
    {
        $credentials = $source->credentials ?? [];
        $cookiesPath = $credentials['cookies_path'] ?? null;

        if (is_string($cookiesPath) && $cookiesPath !== '') {
            return $cookiesPath;
        }

        $cookies = $credentials['cookies'] ?? null;

        if (! is_string($cookies) || trim($cookies) === '') {
            return null;
        }

        $path = storage_path('app/content-crawler/cookies-'.(string) Str::uuid().'.txt');
        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, $cookies);

        return $path;
    }

    private function cleanupTempCookies(ContentSource $source, ?string $cookiesFile): void
    {
        if ($cookiesFile === null) {
            return;
        }

        $credentials = $source->credentials ?? [];

        if (($credentials['cookies_path'] ?? null) === $cookiesFile) {
            return;
        }

        @unlink($cookiesFile);
    }

    private function largestFile(string $directory): ?string
    {
        $files = collect(File::files($directory));

        if ($files->isEmpty()) {
            return null;
        }

        return $files
            ->sortByDesc(static fn ($file): int => $file->getSize())
            ->first()
            ?->getPathname();
    }

    private function cleanupDirectoryExceptLargest(string $directory): void
    {
        $keep = $this->largestFile($directory);

        foreach (File::files($directory) as $file) {
            if ($keep !== $file->getPathname()) {
                @unlink($file->getPathname());
            }
        }
    }
}
