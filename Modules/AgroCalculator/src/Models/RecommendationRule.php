<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class RecommendationRule extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected $table = 'agro_calculator_recommendation_rules';

    protected $guarded = [];

    protected $casts = [
        'conditions' => 'array',
        'recommendations' => 'array',
    ];

    public array $translatable = ['title'];
}
