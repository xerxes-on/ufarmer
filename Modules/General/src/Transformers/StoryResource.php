<?php

declare(strict_types=1);

namespace Modules\General\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'color' => $this->color,
            'attachment' => $this->attachment,
            'is_active' => $this->is_active,
            'pre_attachment' => $this->pre_attachment,
            'button' => $this->when($this->button, [
                'type' => $this->button,
                'text' => $this->button_text,
                'action' => $this->button_action,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
