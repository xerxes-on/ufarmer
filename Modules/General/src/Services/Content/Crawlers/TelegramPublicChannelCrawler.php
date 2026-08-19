<?php

declare(strict_types=1);

namespace Modules\General\Services\Content\Crawlers;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\General\Models\ContentDraft;
use Modules\General\Models\ContentSource;
use Modules\General\Services\Content\ContentMediaDownloader;

final class TelegramPublicChannelCrawler
{
    public function __construct(
        private readonly ContentMediaDownloader $mediaDownloader,
    ) {}

    /**
     * @return array{created: int, skipped: int, downloaded: int}
     */
    public function crawl(ContentSource $source, int $limit, bool $download): array
    {
        $items = $this->items($source, $limit);
        $created = 0;
        $skipped = 0;
        $downloaded = 0;

        foreach ($items as $item) {
            $existingDraft = $this->existingDraft($source, $item['source_url']);

            if ($existingDraft instanceof ContentDraft) {
                $skipped++;
                $this->refreshMissingMediaUrl($existingDraft, $item);
                $downloaded += $this->downloadMissingMedia($existingDraft, $download);

                continue;
            }

            if ($this->wantsMediaOnly($source) && blank($item['media_url'])) {
                $skipped++;

                continue;
            }

            $draft = $this->createDraft($source, $item);
            $created++;
            $downloaded += $this->downloadMissingMedia($draft, $download);
        }

        $source->forceFill(['last_crawled_at' => now()])->save();

        return [
            'created' => $created,
            'skipped' => $skipped,
            'downloaded' => $downloaded,
        ];
    }

    /**
     * @return array<int, array{source_url: string, title: string, text: string, media_url: ?string, media_type: ?string}>
     */
    private function items(ContentSource $source, int $limit): array
    {
        $response = Http::timeout(30)->get($this->publicUrl($source));
        $response->throw();

        $document = new DOMDocument;

        libxml_use_internal_errors(true);
        $document->loadHTML($response->body());
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' tgme_widget_message_wrap ')]");

        if ($nodes === false) {
            return [];
        }

        $items = [];

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $postNode = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' js-widget_message ')]", $node)?->item(0);

            if (! $postNode instanceof DOMElement) {
                continue;
            }

            $post = $postNode->getAttribute('data-post');

            if ($post === '') {
                continue;
            }

            $text = trim($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' tgme_widget_message_text ')]", $node)?->item(0)?->textContent ?? '');
            [$mediaUrl, $mediaType] = $this->media($xpath, $node);
            $title = trim(Str::limit($text !== '' ? $text : $post, 120, ''));

            $items[] = [
                'source_url' => "https://t.me/{$post}",
                'title' => $title !== '' ? $title : $post,
                'text' => $text,
                'media_url' => $mediaUrl,
                'media_type' => $mediaType,
            ];
        }

        return collect($items)
            ->reverse()
            ->take($limit)
            ->values()
            ->all();
    }

    private function createDraft(ContentSource $source, array $item): ContentDraft
    {
        $body = $item['text'] !== '' ? $item['text'] : $item['title'];

        return ContentDraft::create([
            'content_source_id' => $source->id,
            'content_type' => $this->contentType($source, $item['media_type']),
            'status' => ContentDraft::STATUS_DRAFT,
            'source_url' => $item['source_url'],
            'source_title' => $item['title'],
            'title' => $this->localized($item['title']),
            'preview' => $this->localized(Str::limit($body, 220)),
            'body' => $this->localized($body),
            'media_original_url' => $item['media_url'],
            'media_url' => $item['media_url'],
            'thumbnail_url' => $item['media_type'] === 'image' ? $item['media_url'] : null,
            'source_payload' => [
                'crawler' => 'telegram-public',
                'source_type' => $source->source_type,
                'media_type' => $item['media_type'],
            ],
        ]);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function media(DOMXPath $xpath, DOMElement $node): array
    {
        $video = $xpath->query('.//video[@src]', $node)?->item(0);

        if ($video instanceof DOMElement) {
            return [$video->getAttribute('src'), 'video'];
        }

        $photo = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' tgme_widget_message_photo_wrap ')]", $node)?->item(0);

        if ($photo instanceof DOMElement) {
            $style = $photo->getAttribute('style');

            if (preg_match("/background-image:url\\('([^']+)'\\)/", $style, $matches) === 1) {
                return [$matches[1], 'image'];
            }
        }

        return [null, null];
    }

    private function publicUrl(ContentSource $source): string
    {
        $path = parse_url($source->url, PHP_URL_PATH);
        $handle = trim((string) $path, '/');
        $handle = Str::after($handle, 's/');

        return "https://t.me/s/{$handle}";
    }

    private function existingDraft(ContentSource $source, string $sourceUrl): ?ContentDraft
    {
        return ContentDraft::query()
            ->where('content_source_id', $source->id)
            ->where('source_url', $sourceUrl)
            ->first();
    }

    private function downloadMissingMedia(ContentDraft $draft, bool $download): int
    {
        if (! $download || blank($draft->media_url) || filled($draft->media_path)) {
            return 0;
        }

        $this->mediaDownloader->download($draft);

        return 1;
    }

    private function refreshMissingMediaUrl(ContentDraft $draft, array $item): void
    {
        if (filled($draft->media_path) || blank($item['media_url'])) {
            return;
        }

        $draft->forceFill([
            'media_original_url' => $item['media_url'],
            'media_url' => $item['media_url'],
            'thumbnail_url' => $item['media_type'] === 'image' ? $item['media_url'] : $draft->thumbnail_url,
            'source_payload' => array_merge($draft->source_payload ?? [], [
                'media_type' => $item['media_type'],
            ]),
        ])->save();
    }

    private function wantsMediaOnly(ContentSource $source): bool
    {
        $types = collect($source->content_types);

        return $types->contains(ContentDraft::TYPE_VIDEO)
            || $types->contains(ContentDraft::TYPE_STORY);
    }

    private function contentType(ContentSource $source, ?string $mediaType): string
    {
        if ($mediaType === 'video') {
            return ContentDraft::TYPE_VIDEO;
        }

        if ($mediaType === 'image') {
            return ContentDraft::TYPE_STORY;
        }

        return collect($source->content_types)->first() ?: ContentDraft::TYPE_ARTICLE;
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
}
