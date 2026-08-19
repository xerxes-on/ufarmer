<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScoringFlag extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'agro_calculator_scoring_flags';

    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
    ];

    public function scoringRun(): BelongsTo
    {
        return $this->belongsTo(ScoringRun::class);
    }
}
