<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScoringModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'agro_calculator_scoring_models';

    protected $guarded = [];

    protected $casts = [
        'spec' => 'array',
        'meta' => 'array',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function thresholds(): HasMany
    {
        return $this->hasMany(ScoringThreshold::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ScoringRun::class);
    }
}
