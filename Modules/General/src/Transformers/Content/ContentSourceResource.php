<?php

declare(strict_types=1);

namespace Modules\General\Transformers\Content;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentSourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'source_type' => $this->source_type,
            'url' => $this->url,
            'content_types' => $this->content_types,
            'languages' => $this->languages,
            'metadata' => $this->metadata,
            'is_active' => $this->is_active,
            'last_crawled_at' => $this->last_crawled_at?->toISOString(),
        ];
    }
}
