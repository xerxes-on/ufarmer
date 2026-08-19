<?php

declare(strict_types=1);

namespace Modules\General\Transformers\Content;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentDraftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content_source_id' => $this->content_source_id,
            'content_type' => $this->content_type,
            'status' => $this->status,
            'source_url' => $this->source_url,
            'source_title' => $this->source_title,
            'title' => $this->title,
            'preview' => $this->preview,
            'body' => $this->body,
            'media_original_url' => $this->media_original_url,
            'media_url' => $this->media_url,
            'thumbnail_url' => $this->thumbnail_url,
            'media_disk' => $this->media_disk,
            'media_path' => $this->media_path,
            'media_mime_type' => $this->media_mime_type,
            'media_size_bytes' => $this->media_size_bytes,
            'media_downloaded_at' => $this->media_downloaded_at?->toISOString(),
            'tag_ids' => $this->tag_ids,
            'crop_ids' => $this->crop_ids,
            'approved_at' => $this->approved_at?->toISOString(),
            'published_type' => $this->published_type,
            'published_id' => $this->published_id,
            'published_at' => $this->published_at?->toISOString(),
            'mq_published_at' => $this->mq_published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
