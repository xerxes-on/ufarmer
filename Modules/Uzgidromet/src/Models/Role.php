<?php

declare(strict_types=1);

namespace Modules\Uzgidromet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Core\Models\User;

class Role extends Model
{
    public const string UZGIDROMET = 'uzgidromet';

    protected $guarded = [];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')->withTimestamps();
    }
}
