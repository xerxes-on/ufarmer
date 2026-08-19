<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JobDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
                'phone' => $this->when(auth()->check() && $this->executor_id === auth()->id(), $this->user->phone),
            ],
            'executor' => $this->when($this->executor_id, [
                'id' => $this->executor?->id,
                'name' => $this->executor?->name,
                'avatar' => $this->executor?->avatar,
                'phone' => $this->when(auth()->id() === $this->user_id, $this->executor?->phone),
            ]),
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'icon' => $this->category->icon,
            ],
            'title' => $this->title,
            'body' => $this->body,
            'price_mode' => $this->price_mode,
            'price_fixed' => $this->price_fixed,
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'display_price' => $this->display_price,
            'currency' => $this->currency,
            'timing_type' => $this->timing_type,
            'deadline' => $this->deadline,
            'fixed_time' => $this->fixed_time,
            'property_size' => $this->property_size,
            'property_unit' => $this->property_unit,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address' => $this->address,
            'city' => $this->city,
            'region' => $this->region,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'views_count' => $this->views_count,
            'applications_count' => $this->applications_count,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'applications' => JobApplicationResource::collection($this->whenLoaded('applications')),
            'distance' => $this->when(isset($this->distance), $this->distance),
            'is_owner' => auth()->id() === $this->user_id,
            'has_applied' => $this->when(auth()->check(), function () {
                return $this->applications()->where('applicant_id', auth()->id())->exists();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
