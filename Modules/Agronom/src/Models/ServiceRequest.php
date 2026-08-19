<?php

declare(strict_types=1);

namespace Modules\Agronom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Agronom\Enums\ServiceRequestStatus;
use Modules\Agronom\Enums\ServiceRequestType;
use Modules\Core\Models\User;
use Modules\Crops\Models\Currency;

class ServiceRequest extends Model
{
    protected $table = 'agronom_requests';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ServiceRequestType::class,
            'status' => ServiceRequestStatus::class,
            'price' => 'decimal:2',
            'crop_ids' => 'array',
            'scheduled_time' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function agronom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agronom_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @return HasMany<RequestCalendarRun, $this>
     */
    public function requestCalendarRuns(): HasMany
    {
        return $this->hasMany(RequestCalendarRun::class, 'service_request_id');
    }

    public function isMonitoring(): bool
    {
        return $this->type === ServiceRequestType::Monitoring;
    }
}
