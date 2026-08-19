<?php

declare(strict_types=1);

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentDraft extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PUBLISHED = 'published';

    public const TYPE_ARTICLE = 'article';

    public const TYPE_NEWS = 'news';

    public const TYPE_STORY = 'story';

    public const TYPE_VIDEO = 'video';

    protected $guarded = [];

    protected $casts = [
        'title' => 'array',
        'preview' => 'array',
        'body' => 'array',
        'tag_ids' => 'array',
        'crop_ids' => 'array',
        'source_payload' => 'array',
        'approved_at' => 'datetime',
        'media_downloaded_at' => 'datetime',
        'published_at' => 'datetime',
        'mq_published_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(ContentSource::class, 'content_source_id');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
