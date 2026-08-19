<?php

declare(strict_types=1);

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentSource extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'content_types' => 'array',
        'languages' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'last_crawled_at' => 'datetime',
    ];

    public function drafts(): HasMany
    {
        return $this->hasMany(ContentDraft::class);
    }
}
