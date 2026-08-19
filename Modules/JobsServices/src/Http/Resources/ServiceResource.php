<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'price' => $this->price,
            'price_unit' => $this->price_unit,
            'display_price' => $this->display_price,
            'currency' => $this->currency,
            'timing_description' => $this->timing_description,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address' => $this->address,
            'city' => $this->city,
            'region' => $this->region,
            'service_radius' => $this->service_radius,
            'status' => $this->status,
            'rating' => $this->rating,
            'reviews_count' => $this->reviews_count,
            'views_count' => $this->views_count,
            'requests_count' => $this->requests_count,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'distance' => $this->when(isset($this->distance), $this->distance),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
