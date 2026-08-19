<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class AgroUnit extends Model
{
    use HasTranslations;

    protected $table = 'agro_units';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'precision' => 'integer',
    ];

    public array $translatable = ['name', 'description'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all units as options for Filament select.
     */
    public static function options(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        return static::query()
            ->active()
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (self $unit) => [
                $unit->symbol => sprintf(
                    '%s - %s',
                    $unit->symbol,
                    $unit->getTranslation('name', $locale) ?? $unit->code
                ),
            ])
            ->all();
    }

    /**
     * Get yield units (mass units suitable for yield measurement).
     */
    public static function yieldUnitOptions(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        return static::query()
            ->active()
            ->whereIn('code', ['kg', 'ton', 'centner', 'kg_per_ha', 'ton_per_ha'])
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (self $unit) => [
                $unit->id => sprintf(
                    '%s - %s',
                    $unit->symbol,
                    $unit->getTranslation('name', $locale) ?? $unit->code
                ),
            ])
            ->all();
    }
}
