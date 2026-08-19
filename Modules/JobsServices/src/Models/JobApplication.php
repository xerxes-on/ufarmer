<?php

declare(strict_types=1);

namespace Modules\JobsServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AuthFarmers\Models\User;

class JobApplication extends Model
{
    protected $guarded = [];

    protected $casts = [
        'proposed_price' => 'decimal:2',
        'estimated_duration' => 'integer',
        'proposed_start_time' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function accept(): void
    {
        $this->update([
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        // Update job with executor
        $this->job->update([
            'executor_id' => $this->applicant_id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        // Reject all other applications
        $this->job->applications()
            ->where('id', '!=', $this->id)
            ->pending()
            ->update([
                'status' => 'rejected',
                'rejection_reason' => 'Another applicant was selected',
                'responded_at' => now(),
            ]);
    }

    public function reject($reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'responded_at' => now(),
        ]);
    }

    public function withdraw(): void
    {
        $this->update([
            'status' => 'withdrawn',
        ]);
    }
}
