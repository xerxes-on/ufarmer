<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\PaidService;

class PaidServicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_paid::service');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PaidService $paidService): bool
    {
        return $user->can('view_paid::service');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_paid::service');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PaidService $paidService): bool
    {
        return $user->can('update_paid::service');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PaidService $paidService): bool
    {
        return $user->can('delete_paid::service');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_paid::service');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, PaidService $paidService): bool
    {
        return $user->can('force_delete_paid::service');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_paid::service');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, PaidService $paidService): bool
    {
        return $user->can('restore_paid::service');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_paid::service');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, PaidService $paidService): bool
    {
        return $user->can('replicate_paid::service');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_paid::service');
    }
}
