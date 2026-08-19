<?php

declare(strict_types=1);

namespace Modules\General\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\General\Models\ProductStat;

/**
 * @property ProductStat $resource
 */
final class ProductStatResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->resource->date->format('Y-m-d'),
            'data' => $this->resource->data,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
