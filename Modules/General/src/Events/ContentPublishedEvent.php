<?php

declare(strict_types=1);

namespace Modules\General\Events;

use Illuminate\Database\Eloquent\Model;
use Modules\General\Models\ContentDraft;
use Xerxes\RabbitMQ\Support\ShouldPublish;

class ContentPublishedEvent implements ShouldPublish
{
    public string $exchange;

    public function __construct(
        public readonly int $draftId,
        public readonly string $contentType,
        public readonly string $publishedType,
        public readonly int $publishedId,
        public readonly array $payload,
    ) {
        $this->exchange = (string) config('general.content.mq_exchange', 'content.published');
    }

    public static function fromDraft(ContentDraft $draft, Model $published): self
    {
        return new self(
            draftId: (int) $draft->id,
            contentType: (string) $draft->content_type,
            publishedType: $published::class,
            publishedId: (int) $published->getKey(),
            payload: [
                'draft_id' => $draft->id,
                'content_type' => $draft->content_type,
                'published_type' => $published::class,
                'published_id' => $published->getKey(),
                'source_url' => $draft->source_url,
                'source_title' => $draft->source_title,
                'title' => $draft->title,
                'preview' => $draft->preview,
                'media_url' => $draft->media_url,
                'media_original_url' => $draft->media_original_url,
                'thumbnail_url' => $draft->thumbnail_url,
                'media_disk' => $draft->media_disk,
                'media_path' => $draft->media_path,
                'media_mime_type' => $draft->media_mime_type,
                'media_size_bytes' => $draft->media_size_bytes,
                'tag_ids' => $draft->tag_ids,
                'crop_ids' => $draft->crop_ids,
                'published_at' => $draft->published_at?->toISOString(),
            ],
        );
    }

    public function routingKey(): string
    {
        return "content.{$this->contentType}.published";
    }
}
