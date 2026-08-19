<?php

declare(strict_types=1);

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArticleTag extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'article_tags';

    protected $guarded = [];

    public function getLocalizedNameAttribute(): ?string
    {
        return $this->getAttribute('name_'.app()->getLocale())
            ?? $this->getAttribute('name_'.config('app.fallback_locale', 'en'))
            ?? $this->getAttribute('name_uz')
            ?? $this->getAttribute('slug');
    }

    /**
     * Articles associated with this tag.
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_article_tag');
    }
}
