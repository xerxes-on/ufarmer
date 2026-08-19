<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
            ],
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'icon' => $this->category->icon,
            ],
            'title' => $this->title,
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
            'views_count' => $this->views_count,
            'applications_count' => $this->applications_count,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'distance' => $this->when(isset($this->distance), $this->distance),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
