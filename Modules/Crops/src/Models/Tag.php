<?php

declare(strict_types=1);

namespace Modules\Crops\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Chat\Models\Conversation;

class Tag extends Model
{
    protected $guarded = [];

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_tag');
    }
}
