<?php

declare(strict_types=1);

namespace Modules\AICalculation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AICalculationResult extends Model
{
    protected $table = 'ai_calculation_results';

    protected $guarded = [];

    protected $casts = [
        'raw_ai_response' => 'array',
        'parsed_soil_analysis' => 'array',
        'received_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(AICalculationRequest::class, 'ai_calculation_request_id');
    }

    public function alternativeCrops(): HasMany
    {
        return $this->hasMany(AICalculationAlternativeCrop::class, 'ai_calculation_result_id');
    }
}
