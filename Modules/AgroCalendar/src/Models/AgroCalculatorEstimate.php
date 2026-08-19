<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Area;
use Modules\Core\Models\User;

class AgroCalculatorEstimate extends Model
{
    protected $table = 'agro_calculator_estimates';

    protected $guarded = [];

    protected $casts = [
        'effective_ha' => 'decimal:2',
        'start_date' => 'date',
        'input_data' => 'array',
        'result_data' => 'array',
        'total_cost' => 'decimal:2',
        'cost_per_ha' => 'decimal:2',
        'est_profit' => 'decimal:2',
        'roi_percent' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(\Modules\Crops\Models\Crop::class);
    }
}
