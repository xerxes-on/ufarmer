<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AgroCalendar\Enums\SoilTextureType;
use Modules\Core\Models\Area;
use Modules\Core\Models\User;

class AreaSoilProfile extends Model
{
    protected $table = 'agro_area_soil_profiles';

    protected $guarded = [];

    protected $casts = [
        'test_date' => 'date',
        'ph' => 'decimal:2',
        'organic_matter_pct' => 'decimal:2',
        'texture' => SoilTextureType::class,
        'nitrogen_ppm' => 'decimal:2',
        'phosphorus_ppm' => 'decimal:2',
        'potassium_ppm' => 'decimal:2',
        'calcium_ppm' => 'decimal:2',
        'magnesium_ppm' => 'decimal:2',
        'sulfur_ppm' => 'decimal:2',
        'iron_ppm' => 'decimal:2',
        'zinc_ppm' => 'decimal:2',
        'manganese_ppm' => 'decimal:2',
        'boron_ppm' => 'decimal:2',
        'cec' => 'decimal:2',
        'salinity_ec' => 'decimal:2',
        'recommendations' => 'array',
        'raw_data' => 'array',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get pH classification
     */
    public function getPhClassification(): ?array
    {
        if ($this->ph === null) {
            return null;
        }

        $ph = (float) $this->ph;

        return match (true) {
            $ph < 4.5 => ['code' => 'extremely_acidic', 'uz' => 'Juda kislotali', 'ru' => 'Крайне кислая', 'en' => 'Extremely acidic'],
            $ph < 5.5 => ['code' => 'strongly_acidic', 'uz' => 'Kuchli kislotali', 'ru' => 'Сильнокислая', 'en' => 'Strongly acidic'],
            $ph < 6.0 => ['code' => 'moderately_acidic', 'uz' => 'O\'rtacha kislotali', 'ru' => 'Среднекислая', 'en' => 'Moderately acidic'],
            $ph < 6.5 => ['code' => 'slightly_acidic', 'uz' => 'Biroz kislotali', 'ru' => 'Слабокислая', 'en' => 'Slightly acidic'],
            $ph < 7.5 => ['code' => 'neutral', 'uz' => 'Neytral', 'ru' => 'Нейтральная', 'en' => 'Neutral'],
            $ph < 8.0 => ['code' => 'slightly_alkaline', 'uz' => 'Biroz ishqorli', 'ru' => 'Слабощелочная', 'en' => 'Slightly alkaline'],
            $ph < 8.5 => ['code' => 'moderately_alkaline', 'uz' => 'O\'rtacha ishqorli', 'ru' => 'Среднещелочная', 'en' => 'Moderately alkaline'],
            default => ['code' => 'strongly_alkaline', 'uz' => 'Kuchli ishqorli', 'ru' => 'Сильнощелочная', 'en' => 'Strongly alkaline'],
        };
    }

    /**
     * Get nitrogen level classification
     */
    public function getNitrogenLevel(): ?array
    {
        if ($this->nitrogen_ppm === null) {
            return null;
        }

        $n = (float) $this->nitrogen_ppm;

        return match (true) {
            $n < 10 => ['code' => 'very_low', 'uz' => 'Juda past', 'ru' => 'Очень низкий', 'en' => 'Very low'],
            $n < 20 => ['code' => 'low', 'uz' => 'Past', 'ru' => 'Низкий', 'en' => 'Low'],
            $n < 40 => ['code' => 'medium', 'uz' => 'O\'rtacha', 'ru' => 'Средний', 'en' => 'Medium'],
            $n < 60 => ['code' => 'high', 'uz' => 'Yuqori', 'ru' => 'Высокий', 'en' => 'High'],
            default => ['code' => 'very_high', 'uz' => 'Juda yuqori', 'ru' => 'Очень высокий', 'en' => 'Very high'],
        };
    }

    /**
     * Get phosphorus level classification
     */
    public function getPhosphorusLevel(): ?array
    {
        if ($this->phosphorus_ppm === null) {
            return null;
        }

        $p = (float) $this->phosphorus_ppm;

        return match (true) {
            $p < 5 => ['code' => 'very_low', 'uz' => 'Juda past', 'ru' => 'Очень низкий', 'en' => 'Very low'],
            $p < 15 => ['code' => 'low', 'uz' => 'Past', 'ru' => 'Низкий', 'en' => 'Low'],
            $p < 30 => ['code' => 'medium', 'uz' => 'O\'rtacha', 'ru' => 'Средний', 'en' => 'Medium'],
            $p < 50 => ['code' => 'high', 'uz' => 'Yuqori', 'ru' => 'Высокий', 'en' => 'High'],
            default => ['code' => 'very_high', 'uz' => 'Juda yuqori', 'ru' => 'Очень высокий', 'en' => 'Very high'],
        };
    }

    /**
     * Get potassium level classification
     */
    public function getPotassiumLevel(): ?array
    {
        if ($this->potassium_ppm === null) {
            return null;
        }

        $k = (float) $this->potassium_ppm;

        return match (true) {
            $k < 75 => ['code' => 'very_low', 'uz' => 'Juda past', 'ru' => 'Очень низкий', 'en' => 'Very low'],
            $k < 150 => ['code' => 'low', 'uz' => 'Past', 'ru' => 'Низкий', 'en' => 'Low'],
            $k < 250 => ['code' => 'medium', 'uz' => 'O\'rtacha', 'ru' => 'Средний', 'en' => 'Medium'],
            $k < 400 => ['code' => 'high', 'uz' => 'Yuqori', 'ru' => 'Высокий', 'en' => 'High'],
            default => ['code' => 'very_high', 'uz' => 'Juda yuqori', 'ru' => 'Очень высокий', 'en' => 'Very high'],
        };
    }

    /**
     * Get organic matter classification
     */
    public function getOrganicMatterLevel(): ?array
    {
        if ($this->organic_matter_pct === null) {
            return null;
        }

        $om = (float) $this->organic_matter_pct;

        return match (true) {
            $om < 1.0 => ['code' => 'very_low', 'uz' => 'Juda past', 'ru' => 'Очень низкий', 'en' => 'Very low'],
            $om < 2.0 => ['code' => 'low', 'uz' => 'Past', 'ru' => 'Низкий', 'en' => 'Low'],
            $om < 4.0 => ['code' => 'medium', 'uz' => 'O\'rtacha', 'ru' => 'Средний', 'en' => 'Medium'],
            $om < 6.0 => ['code' => 'high', 'uz' => 'Yuqori', 'ru' => 'Высокий', 'en' => 'High'],
            default => ['code' => 'very_high', 'uz' => 'Juda yuqori', 'ru' => 'Очень высокий', 'en' => 'Very high'],
        };
    }

    /**
     * Get salinity classification
     */
    public function getSalinityLevel(): ?array
    {
        if ($this->salinity_ec === null) {
            return null;
        }

        $ec = (float) $this->salinity_ec;

        return match (true) {
            $ec < 2.0 => ['code' => 'non_saline', 'uz' => 'Sho\'rlanmagan', 'ru' => 'Незасолённая', 'en' => 'Non-saline'],
            $ec < 4.0 => ['code' => 'slightly_saline', 'uz' => 'Biroz sho\'rlangan', 'ru' => 'Слабозасолённая', 'en' => 'Slightly saline'],
            $ec < 8.0 => ['code' => 'moderately_saline', 'uz' => 'O\'rtacha sho\'rlangan', 'ru' => 'Среднезасолённая', 'en' => 'Moderately saline'],
            $ec < 16.0 => ['code' => 'highly_saline', 'uz' => 'Kuchli sho\'rlangan', 'ru' => 'Сильнозасолённая', 'en' => 'Highly saline'],
            default => ['code' => 'extremely_saline', 'uz' => 'Juda sho\'rlangan', 'ru' => 'Крайне засолённая', 'en' => 'Extremely saline'],
        };
    }

    /**
     * Get overall soil health score (0-100)
     */
    public function getSoilHealthScore(): ?int
    {
        $scores = [];

        // pH score (optimal 6.0-7.0)
        if ($this->ph !== null) {
            $ph = (float) $this->ph;
            $scores[] = match (true) {
                $ph >= 6.0 && $ph <= 7.0 => 100,
                $ph >= 5.5 && $ph <= 7.5 => 80,
                $ph >= 5.0 && $ph <= 8.0 => 60,
                default => 40,
            };
        }

        // Organic matter score
        if ($this->organic_matter_pct !== null) {
            $om = (float) $this->organic_matter_pct;
            $scores[] = min(100, (int) ($om * 25));
        }

        // NPK availability
        if ($this->nitrogen_ppm !== null) {
            $scores[] = min(100, (int) ((float) $this->nitrogen_ppm * 2.5));
        }

        if (count($scores) === 0) {
            return null;
        }

        return (int) round(array_sum($scores) / count($scores));
    }

    /**
     * Check if soil test is recent (within 12 months)
     */
    public function isRecent(): bool
    {
        return $this->test_date->isAfter(now()->subMonths(12));
    }
}
