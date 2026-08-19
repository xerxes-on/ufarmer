<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScoringThreshold extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'agro_calculator_scoring_thresholds';

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
    ];

    public function scoringModel(): BelongsTo
    {
        return $this->belongsTo(ScoringModel::class);
    }
}
