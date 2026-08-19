<?php

declare(strict_types=1);

namespace Modules\JobsServices\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\JobsServices\Support\WorkerMetadata;

class MarketplaceServiceRequest extends Model
{
    protected $table = 'service_requests';

    protected $guarded = [];

    protected $casts = [
        'proposed_price' => 'decimal:2',
        'worker_salary' => 'decimal:2',
        'responded_at' => 'datetime',
        'requester_rated_at' => 'datetime',
        'worker_rated_at' => 'datetime',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(ServiceOffer::class, 'service_offer_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requester_id');
    }

    public function scopeNeedsManualInspection(Builder $query): Builder
    {
        return $query
            ->where('status', 'pending')
            ->whereHas('offer.user.workerProfile', fn (Builder $profile) => $profile
                ->where('meta->origin->type', WorkerMetadata::AI_IMPORT))
            ->whereHas('offer.user', fn (Builder $user) => $user
                ->whereNull('auth_id')
                ->orWhereHas(
                    'workerProfile',
                    fn (Builder $profile): Builder => WorkerMetadata::whereAppActivityIsMissing($profile),
                ));
    }

    public function needsManualInspection(): bool
    {
        $worker = $this->offer?->user;
        $profile = $worker?->workerProfile;

        return $this->status === 'pending'
            && $profile !== null
            && WorkerMetadata::isAiAdded($profile->meta)
            && ($worker->auth_id === null || ! WorkerMetadata::hasAppActivity($profile->meta));
    }
}
