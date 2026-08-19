<?php

declare(strict_types=1);

namespace App\Services\AuthBridge;

use Illuminate\Support\Facades\DB;
use Xerxes\AuthBridge\Contracts\ApplicationRepositoryContract;
use Xerxes\AuthBridge\DTOs\ApplicationData;
use Xerxes\AuthBridge\Repositories\ApplicationRepository;

/**
 * xerxes/authbridge resolves application aliases against a fixed,
 * package-vendored enum (Sushi in-memory model) — it has no way to see
 * applications created after the package was published, like admin_panel.
 * Fall back to a real query against ufarm-auth's `applications` table for
 * any alias the vendored enum doesn't know about.
 *
 * ApplicationRepository is final, so this decorates it rather than
 * extending it.
 */
final readonly class DatabaseFallbackApplicationRepository implements ApplicationRepositoryContract
{
    public function __construct(private ApplicationRepository $inner) {}

    public function findByAlias(string $alias): ?ApplicationData
    {
        $fromEnum = $this->inner->findByAlias($alias);

        if ($fromEnum !== null) {
            return $fromEnum;
        }

        $row = DB::connection(config('authbridge.connections.authbridge', 'authbridge'))
            ->table(config('authbridge.application.table', 'applications'))
            ->where('alias', $alias)
            ->first();

        if ($row === null) {
            return null;
        }

        return ApplicationData::fromArray((array) $row);
    }

    public function userBelongsToOrganization(int $remoteUserId, int $organizationId): ?int
    {
        return $this->inner->userBelongsToOrganization($remoteUserId, $organizationId);
    }

    public function getOrganizationUserEntityType(int $remoteUserId, int $organizationId): ?string
    {
        return $this->inner->getOrganizationUserEntityType($remoteUserId, $organizationId);
    }

    public function forgetPivotCache(int $remoteUserId, int $organizationId): void
    {
        $this->inner->forgetPivotCache($remoteUserId, $organizationId);
    }

    public function getUserApplicationAliases(int $remoteUserId): array
    {
        return $this->inner->getUserApplicationAliases($remoteUserId);
    }

    public function forgetUserApplicationsCache(int $remoteUserId): void
    {
        $this->inner->forgetUserApplicationsCache($remoteUserId);
    }
}
