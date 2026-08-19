<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\AgroCalendar\Models\FieldHistory;

class FieldHistoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_field::history');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FieldHistory $fieldHistory): bool
    {
        return $user->can('view_field::history');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_field::history');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FieldHistory $fieldHistory): bool
    {
        return $user->can('update_field::history');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FieldHistory $fieldHistory): bool
    {
        return $user->can('delete_field::history');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_field::history');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, FieldHistory $fieldHistory): bool
    {
        return $user->can('force_delete_field::history');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_field::history');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, FieldHistory $fieldHistory): bool
    {
        return $user->can('restore_field::history');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_field::history');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, FieldHistory $fieldHistory): bool
    {
        return $user->can('replicate_field::history');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_field::history');
    }
}
