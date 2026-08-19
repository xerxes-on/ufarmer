<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ServiceRequestPermission;
use App\Models\User;
use Modules\JobsServices\Models\MarketplaceServiceRequest;

class MarketplaceServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(ServiceRequestPermission::ViewAnyMarketplace->value);
    }

    public function view(User $user, MarketplaceServiceRequest $request): bool
    {
        return $user->can(ServiceRequestPermission::ViewMarketplace->value)
            || $user->can(ServiceRequestPermission::ViewAnyMarketplace->value);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, MarketplaceServiceRequest $request): bool
    {
        return false;
    }

    public function delete(User $user, MarketplaceServiceRequest $request): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, MarketplaceServiceRequest $request): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, MarketplaceServiceRequest $request): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, MarketplaceServiceRequest $request): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
