<?php

declare(strict_types=1);

namespace Modules\Agronom\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\AppSetting;
use Throwable;

final class DefaultAgronomProfileImage
{
    public const string SETTING_KEY = 'agronom_default_profile_image';

    public const string STORAGE_DISK = 'users';

    /** @return array{source: string, path?: string} */
    public function value(): array
    {
        try {
            $value = AppSetting::getValue(self::SETTING_KEY, ['source' => 'builtin']);
        } catch (QueryException) {
            return ['source' => 'builtin'];
        }

        return is_array($value) ? $value : ['source' => 'builtin'];
    }

    public function url(): string
    {
        $value = $this->value();
        $path = trim((string) ($value['path'] ?? ''));

        if (($value['source'] ?? null) === 'storage' && $path !== '') {
            return Storage::disk(self::STORAGE_DISK)->url($path);
        }

        return url('images/agronom/default-profile.png');
    }

    public function setStoragePath(string $path): bool
    {
        $path = ltrim(trim($path), '/');

        if ($path === '') {
            return false;
        }

        try {
            if (! Storage::disk(self::STORAGE_DISK)->exists($path)) {
                return false;
            }
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        return AppSetting::setValue(self::SETTING_KEY, [
            'source' => 'storage',
            'path' => $path,
        ]);
    }

    public function useBuiltIn(): bool
    {
        return AppSetting::setValue(self::SETTING_KEY, ['source' => 'builtin']);
    }
}
