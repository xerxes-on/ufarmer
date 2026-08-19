<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Enums\PaidServiceStatus;
use Spatie\Translatable\HasTranslations;

class PaidService extends Model
{
    use HasTranslations;
    use SoftDeletes;

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['name', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'price' => 'integer',
            'applicable_roles' => 'array',
            'config' => 'array',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'status' => PaidServiceStatus::class,
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PaidServiceStatus::Active);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('is_paid', true);
    }

    public function scopeFree(Builder $query): Builder
    {
        return $query->where('is_paid', false);
    }

    public function getLocalizedNameAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $this->getTranslation('name', $locale)
            ?: $this->getTranslation('name', config('app.fallback_locale', 'en'))
            ?: null;
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $this->getTranslation('description', $locale)
            ?: $this->getTranslation('description', config('app.fallback_locale', 'en'))
            ?: null;
    }
}
