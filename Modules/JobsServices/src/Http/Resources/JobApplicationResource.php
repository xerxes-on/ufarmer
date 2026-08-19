<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JobApplicationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'job' => $this->when($this->relationLoaded('job'), new JobResource($this->job)),
            'job_id' => $this->when(! $this->relationLoaded('job'), $this->job_id),
            'applicant' => [
                'id' => $this->applicant->id,
                'name' => $this->applicant->name,
                'avatar' => $this->applicant->avatar,
                'phone' => $this->when(
                    auth()->id() === $this->job->user_id && $this->status === 'accepted',
                    $this->applicant->phone
                ),
            ],
            'message' => $this->message,
            'proposed_price' => $this->proposed_price,
            'currency' => $this->currency,
            'proposed_start_time' => $this->proposed_start_time,
            'estimated_duration' => $this->estimated_duration,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'responded_at' => $this->responded_at,
            'is_mine' => auth()->id() === $this->applicant_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
