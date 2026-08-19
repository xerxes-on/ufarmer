<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Crops\Models\Crop;

class CropParameterSet extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'agro_calculator_crop_parameter_sets';

    protected $guarded = [];

    protected $casts = [
        'params' => 'array',
        'meta' => 'array',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }
}
