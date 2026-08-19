<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Enums\AppSettingType;

class AppSetting extends Model
{
    // Type constants (for backward compatibility)
    public const TYPE_STRING = 'string';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_FLOAT = 'float';

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_JSON = 'json';

    public const TYPE_ENUM = 'enum';

    // Group constants
    public const GROUP_GENERAL = 'general';

    public const GROUP_AREA = 'area';

    public const GROUP_CALENDAR = 'calendar';

    public const GROUP_USER = 'user';

    public const GROUP_APP = 'app';

    public const GROUP_AGRONOM = 'agronom';

    protected $fillable = [
        'key',
        'value_type',
        'value',
        'group',
        'description',
        'enum_options',
        'is_public',
    ];

    protected $casts = [
        'description' => 'array',
        'enum_options' => 'array',
        'is_public' => 'boolean',
    ];

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeInGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function getTypedValue(): mixed
    {
        return match ($this->value_type) {
            AppSettingType::Integer->value => (int) $this->value,
            AppSettingType::Float->value => (float) $this->value,
            AppSettingType::Boolean->value => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            AppSettingType::Json->value => json_decode($this->value, true),
            AppSettingType::Enum->value, AppSettingType::String->value => $this->value,
            default => $this->value,
        };
    }

    public function setTypedValue(mixed $value): void
    {
        $this->value = match ($this->value_type) {
            AppSettingType::Boolean->value => $value ? 'true' : 'false',
            AppSettingType::Json->value => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return $setting->getTypedValue();
    }

    public static function setValue(string $key, mixed $value): bool
    {
        $setting = static::query()->where('key', $key)->first();

        if (! $setting) {
            return false;
        }

        $setting->setTypedValue($value);

        return $setting->save();
    }

    public function getLocalizedDescription(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $descriptions = $this->description ?? [];

        return $descriptions[$locale] ?? $descriptions['en'] ?? $descriptions['uz'] ?? '';
    }
}
