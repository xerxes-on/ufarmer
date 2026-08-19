<?php

declare(strict_types=1);

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Models\PaidService;

/**
 * @mixin PaidService
 */
class PaidServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->getTranslations('name'),
            'localized_name' => $this->localized_name,
            'description' => $this->getTranslations('description'),
            'localized_description' => $this->localized_description,
            'is_paid' => $this->is_paid,
            'price' => $this->price,
            'currency' => $this->currency,
            'applicable_roles' => $this->applicable_roles,
            'config' => $this->config,
            'valid_from' => $this->valid_from?->toIso8601String(),
            'valid_until' => $this->valid_until?->toIso8601String(),
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
