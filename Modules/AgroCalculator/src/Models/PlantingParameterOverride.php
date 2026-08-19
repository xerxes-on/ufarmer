<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\AreaCrop;

class PlantingParameterOverride extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'agro_calculator_planting_parameter_overrides';

    protected $guarded = [];

    protected $casts = [
        'params' => 'array',
        'meta' => 'array',
    ];

    public function areaCrop(): BelongsTo
    {
        return $this->belongsTo(AreaCrop::class);
    }
}
