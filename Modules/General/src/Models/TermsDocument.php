<?php

declare(strict_types=1);

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TermsDocument extends Model
{
    use SoftDeletes;

    protected $table = 'terms_documents';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $appends = [
        'preview',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $document): void {
            $document->id = static::buildId($document->type, $document->locale);
        });

        static::saved(function (self $document): void {
            static::writeToDisk($document);
        });
    }

    public static function buildId(string $type, string $locale): string
    {
        return trim($type).'_'.trim($locale);
    }

    public function getPreviewAttribute(): string
    {
        return Str::limit(strip_tags($this->content ?? ''), 100, '');
    }

    public static function pathFor(string $type, string $locale): string
    {
        return public_path(sprintf('terms/%s_%s.md', trim($type), trim($locale)));
    }

    public static function writeToDisk(self $document): void
    {
        File::ensureDirectoryExists(public_path('terms'));
        File::put(static::pathFor($document->type, $document->locale), $document->content ?? '');
    }

    public static function syncFromDisk(): void
    {
        $directory = public_path('terms');

        if (! File::exists($directory)) {
            return;
        }

        foreach (File::files($directory) as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $name = $file->getBasename('.md');

            if (! str_contains($name, '_')) {
                continue;
            }

            [$type, $locale] = explode('_', $name, 2);

            static::query()->updateOrCreate(
                ['id' => static::buildId($type, $locale)],
                [
                    'type' => $type,
                    'locale' => $locale,
                    'content' => File::get($file->getPathname()),
                ],
            );
        }
    }
}
