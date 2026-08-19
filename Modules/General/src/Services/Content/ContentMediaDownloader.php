<?php

declare(strict_types=1);

namespace Modules\General\Services\Content;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\General\Models\ContentDraft;
use Throwable;

final class ContentMediaDownloader
{
    public function download(ContentDraft $draft): ContentDraft
    {
        if (filled($draft->media_path) && filled($draft->media_disk)) {
            return $draft;
        }

        $sourceUrl = $draft->media_original_url ?: $draft->media_url;

        if (! filled($sourceUrl)) {
            throw ValidationException::withMessages([
                'media_url' => 'Media URL is required before downloading to S3.',
            ]);
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'ufarm-content-');

        if ($tmpPath === false) {
            throw ValidationException::withMessages([
                'media_url' => 'Could not create temporary media file.',
            ]);
        }

        try {
            $response = Http::timeout((int) config('general.content.media_download_timeout', 120))
                ->withOptions(['sink' => $tmpPath])
                ->get($sourceUrl);

            if ($response->failed()) {
                throw ValidationException::withMessages([
                    'media_url' => "Media download failed with HTTP {$response->status()}.",
                ]);
            }

            $mimeType = $this->resolveMimeType($tmpPath, $response->header('Content-Type'));
            $this->assertDownloadableMedia($mimeType);

            $extension = $this->extension($sourceUrl, $mimeType);
            $disk = (string) config('general.content.media_disk', 's3');
            $path = trim((string) config('general.content.media_path', 'content/daily-updates'), '/')
                .'/'.$draft->id.'/'.(string) Str::uuid().'.'.$extension;

            $stream = fopen($tmpPath, 'rb');

            if ($stream === false) {
                throw ValidationException::withMessages([
                    'media_url' => 'Could not read downloaded media file.',
                ]);
            }

            try {
                Storage::disk($disk)->put($path, $stream, ['visibility' => 'public']);
            } finally {
                fclose($stream);
            }

            $draft->forceFill([
                'media_original_url' => $sourceUrl,
                'media_url' => Storage::disk($disk)->url($path),
                'media_disk' => $disk,
                'media_path' => $path,
                'media_mime_type' => $mimeType,
                'media_size_bytes' => filesize($tmpPath) ?: null,
                'media_downloaded_at' => now(),
            ])->save();

            return $draft->refresh();
        } finally {
            try {
                @unlink($tmpPath);
            } catch (Throwable) {
                // Best-effort cleanup only.
            }
        }
    }

    public function storeLocalFile(ContentDraft $draft, string $localPath, string $originalUrl): ContentDraft
    {
        if (! is_file($localPath)) {
            throw ValidationException::withMessages([
                'media_url' => 'Downloaded media file was not found.',
            ]);
        }

        $mimeType = $this->resolveMimeType($localPath, null);
        $this->assertDownloadableMedia($mimeType);

        $extension = $this->extension($localPath, $mimeType);
        $disk = (string) config('general.content.media_disk', 's3');
        $path = trim((string) config('general.content.media_path', 'content/daily-updates'), '/')
            .'/'.$draft->id.'/'.(string) Str::uuid().'.'.$extension;

        $stream = fopen($localPath, 'rb');

        if ($stream === false) {
            throw ValidationException::withMessages([
                'media_url' => 'Could not read downloaded media file.',
            ]);
        }

        try {
            Storage::disk($disk)->put($path, $stream, ['visibility' => 'public']);
        } finally {
            fclose($stream);
        }

        $draft->forceFill([
            'media_original_url' => $originalUrl,
            'media_url' => Storage::disk($disk)->url($path),
            'media_disk' => $disk,
            'media_path' => $path,
            'media_mime_type' => $mimeType,
            'media_size_bytes' => filesize($localPath) ?: null,
            'media_downloaded_at' => now(),
        ])->save();

        return $draft->refresh();
    }

    private function resolveMimeType(string $path, ?string $header): string
    {
        $mimeType = mime_content_type($path);

        if (is_string($mimeType) && $mimeType !== '') {
            return $mimeType;
        }

        return trim(explode(';', (string) $header)[0]) ?: 'application/octet-stream';
    }

    private function extension(string $url, string $mimeType): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = is_string($path) ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';

        if ($extension !== '') {
            return $extension;
        }

        return match ($mimeType) {
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }

    private function assertDownloadableMedia(string $mimeType): void
    {
        if (
            str_starts_with($mimeType, 'video/')
            || str_starts_with($mimeType, 'image/')
            || $mimeType === 'application/octet-stream'
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'media_url' => 'Media URL must point directly to a downloadable image/video file. YouTube or Instagram page URLs need an extractor/API first.',
        ]);
    }
}
