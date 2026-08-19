<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'type' => $this->type,
            'sort_order' => $this->sort_order,
        ];
    }
}
