<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\AgroCalendar\Enums\AnalysisType;

class RegionAnalysisDefault extends Model
{
    protected $table = 'region_analysis_defaults';

    protected $guarded = [];

    protected $casts = [
        'type' => AnalysisType::class,
        'details' => 'array',
    ];
}
