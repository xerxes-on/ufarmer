<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\AgroCalendar\Enums\AnalysisType;

class AreaAnalysis extends Model
{
    protected $table = 'agro_area_analyses';

    protected $guarded = [];

    protected $casts = [
        'type' => AnalysisType::class,
        'analysis_date' => 'date',
        'confirmed_at' => 'datetime',
        'details' => 'array',
    ];

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at');
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('analysis_date')->orderByDesc('id');
    }
}
