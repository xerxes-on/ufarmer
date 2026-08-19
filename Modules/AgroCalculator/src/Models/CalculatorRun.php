<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\AreaCrop;

class CalculatorRun extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'agro_calculator_runs';

    protected $guarded = [];

    protected $casts = [
        'inputs' => 'array',
        'outputs' => 'array',
    ];

    public function areaCrop(): BelongsTo
    {
        return $this->belongsTo(AreaCrop::class);
    }

    public function scoringRuns(): HasMany
    {
        return $this->hasMany(ScoringRun::class);
    }
}
