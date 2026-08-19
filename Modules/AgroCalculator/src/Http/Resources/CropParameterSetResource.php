<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\AgroCalculator\Models\CropParameterSet;

/**
 * @property CropParameterSet $resource
 */
final class CropParameterSetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'crop_id' => $this->resource->crop_id,
            'parameter_set_id' => $this->resource->id,
            'version' => $this->resource->version,
            'params' => $this->resource->params,
            'meta' => $this->resource->meta,
            'valid_from' => $this->resource->valid_from?->toDateString(),
            'valid_to' => $this->resource->valid_to?->toDateString(),
        ];
    }
}
