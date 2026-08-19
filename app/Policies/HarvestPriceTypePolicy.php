<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Harvest\Models\HarvestPriceType;

class HarvestPriceTypePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_harvest::price::type');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, HarvestPriceType $harvestPriceType): bool
    {
        return $user->can('view_harvest::price::type');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_harvest::price::type');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, HarvestPriceType $harvestPriceType): bool
    {
        return $user->can('update_harvest::price::type');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, HarvestPriceType $harvestPriceType): bool
    {
        return $user->can('delete_harvest::price::type');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_harvest::price::type');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, HarvestPriceType $harvestPriceType): bool
    {
        return $user->can('force_delete_harvest::price::type');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_harvest::price::type');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, HarvestPriceType $harvestPriceType): bool
    {
        return $user->can('restore_harvest::price::type');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_harvest::price::type');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, HarvestPriceType $harvestPriceType): bool
    {
        return $user->can('replicate_harvest::price::type');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_harvest::price::type');
    }
}
