<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\AgroCalendar\Models\LaborRateDefault;

class LaborRateDefaultPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_labor::rate::default');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LaborRateDefault $laborRateDefault): bool
    {
        return $user->can('view_labor::rate::default');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_labor::rate::default');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LaborRateDefault $laborRateDefault): bool
    {
        return $user->can('update_labor::rate::default');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LaborRateDefault $laborRateDefault): bool
    {
        return $user->can('delete_labor::rate::default');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_labor::rate::default');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, LaborRateDefault $laborRateDefault): bool
    {
        return $user->can('force_delete_labor::rate::default');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_labor::rate::default');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, LaborRateDefault $laborRateDefault): bool
    {
        return $user->can('restore_labor::rate::default');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_labor::rate::default');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, LaborRateDefault $laborRateDefault): bool
    {
        return $user->can('replicate_labor::rate::default');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_labor::rate::default');
    }
}
