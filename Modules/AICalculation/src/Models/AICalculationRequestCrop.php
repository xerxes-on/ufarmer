<?php

declare(strict_types=1);

namespace Modules\AICalculation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Crops\Models\Crop;

class AICalculationRequestCrop extends Model
{
    protected $table = 'ai_calculation_request_crops';

    protected $guarded = [];

    protected $casts = [
        'planting_date' => 'date',
        'metadata' => 'array',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(AICalculationRequest::class, 'ai_calculation_request_id');
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }
}
