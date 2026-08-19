<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\AreaCrop;

class ScoringRun extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'agro_calculator_scoring_runs';

    protected $guarded = [];

    protected $casts = [
        'inputs' => 'array',
        'outputs' => 'array',
    ];

    public function areaCrop(): BelongsTo
    {
        return $this->belongsTo(AreaCrop::class);
    }

    public function scoringModel(): BelongsTo
    {
        return $this->belongsTo(ScoringModel::class);
    }

    public function calculatorRun(): BelongsTo
    {
        return $this->belongsTo(CalculatorRun::class);
    }

    public function flags(): HasMany
    {
        return $this->hasMany(ScoringFlag::class);
    }
}
