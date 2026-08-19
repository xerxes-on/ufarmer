<?php

declare(strict_types=1);

namespace Modules\AICalculation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\Area;
use Modules\Core\Models\User;

class AICalculationRequest extends Model
{
    use SoftDeletes;

    protected $table = 'ai_calculation_requests';

    protected $guarded = [];

    protected $casts = [
        'location_payload' => 'array',
        'area_hectares' => 'decimal:2',
        'irrigation_payload' => 'array',
        'planning_start_date' => 'date',
        'planning_end_date' => 'date',
        'farming_start_date' => 'date',
        'submitted_at' => 'datetime',
        'sent_to_n8n_at' => 'datetime',
        'n8n_received_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'error_payload' => 'array',
        'original_request' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function requestCrops(): HasMany
    {
        return $this->hasMany(AICalculationRequestCrop::class, 'ai_calculation_request_id');
    }

    public function soilDocuments(): HasMany
    {
        return $this->hasMany(AICalculationSoilDocument::class, 'ai_calculation_request_id');
    }

    public function waterDocuments(): HasMany
    {
        return $this->hasMany(AICalculationWaterDocument::class, 'ai_calculation_request_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(AICalculationResult::class, 'ai_calculation_request_id');
    }
}
