<?php

namespace App\Policies;

use App\Enums\ServiceRequestPermission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Agronom\Models\ServiceRequest;

class ServiceRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(ServiceRequestPermission::ViewAny->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->can(ServiceRequestPermission::View->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(ServiceRequestPermission::Create->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->can(ServiceRequestPermission::Update->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->can(ServiceRequestPermission::Delete->value);
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can(ServiceRequestPermission::DeleteAny->value);
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->can(ServiceRequestPermission::ForceDelete->value);
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can(ServiceRequestPermission::ForceDeleteAny->value);
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->can(ServiceRequestPermission::Restore->value);
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can(ServiceRequestPermission::RestoreAny->value);
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->can(ServiceRequestPermission::Replicate->value);
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can(ServiceRequestPermission::Reorder->value);
    }
}
