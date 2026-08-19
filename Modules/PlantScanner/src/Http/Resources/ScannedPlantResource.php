<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonException;
use Modules\PlantScanner\Enums\ScanStatus;
use Modules\PlantScanner\Models\ScannedPlant;

/**
 * @property ScannedPlant $resource
 */
final class ScannedPlantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response = [
            'id' => $this->resource->id,
            'status' => $this->resource->status,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];

        if ($this->resource->processing_started_at) {
            $response['processing_started_at'] = $this->resource->processing_started_at;
        }

        if ($this->resource->processing_completed_at) {
            $response['processing_completed_at'] = $this->resource->processing_completed_at;
            $response['processing_time_seconds'] = $this->resource->processing_started_at
                ->diffInSeconds($this->resource->processing_completed_at);
        }

        if ($this->resource->status === ScanStatus::Completed) {
            $response = array_merge($response, [
                'analysis' => $this->decodeIfJson($this->resource->ai_analysis),
                'ai_provider' => $this->resource->ai_provider_used ?? $this->resource->ai_provider,
                'structured_data' => $this->resource->structured_data,
                'tags' => $this->resource->tags,
                'photos' => $this->resource->photos,
            ]);
        }

        if ($this->resource->status === ScanStatus::Failed && $this->resource->error_message) {
            $response['error'] = $this->resource->error_message;
        }

        $response['progress'] = $this->resource->status->getProgressPercentage();

        return $response;
    }

    private function decodeIfJson(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || (! str_starts_with($trimmed, '{') && ! str_starts_with($trimmed, '['))) {
            return $value;
        }

        try {
            return json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $value;
        }
    }
}
