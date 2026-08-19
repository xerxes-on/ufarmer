<?php

declare(strict_types=1);

namespace Modules\Uzgidromet\Support;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Uzgidromet\Models\Role;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    public function hasRole(string $name): bool
    {
        return $this->roles()->where('name', $name)->exists();
    }

    /**
     * @param  array<int, string>  $names
     */
    public function hasAnyRole(array $names): bool
    {
        return $this->roles()->whereIn('name', $names)->exists();
    }

    /**
     * True when the user has the given role AND no other roles attached.
     */
    public function hasOnlyRole(string $name): bool
    {
        $roleNames = $this->roles()->pluck('name')->all();

        return count($roleNames) === 1 && $roleNames[0] === $name;
    }
}
