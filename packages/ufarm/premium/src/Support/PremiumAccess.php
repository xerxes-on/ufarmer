<?php

declare(strict_types=1);

namespace Ufarm\Premium\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Core\app\Models\User;
use Ufarm\Premium\Models\Subscription;

class PremiumAccess
{
    public function userHasActive(User $user, ?string $planName = null, ?int $applicationId = null): bool
    {
        $cacheKey = $this->buildCacheKey($user->id, $planName, $applicationId);

        return Cache::remember($cacheKey, $this->getCacheTTL(), function () use ($user, $planName, $applicationId) {
            return Subscription::query()
                ->where('user_id', $user->id)
                ->active()
                ->when($planName, fn ($query) => $query->whereHas('plan', fn ($planQuery) => $planQuery->where('name', $planName)))
                ->when($applicationId, fn ($query) => $query->where('application_id', $applicationId))
                ->exists();
        });
    }

    public function clearUserCache(int|string $userId): void
    {
        $date = Carbon::now()->format('Y-m-d');
        $pattern = sprintf('premium_access:%s:*:%s', $userId, $date);

        // Clear all variations of premium access cache for this user today
        // Note: This is a simplified version. In production with Redis, use SCAN command
        // For now, clear specific common patterns
        $patterns = [
            sprintf('premium_access:%s:any:any:%s', $userId, $date),
            sprintf('premium_access:%s:*:*:%s', $userId, $date),
        ];

        foreach ($patterns as $cachePattern) {
            Cache::forget($cachePattern);
        }
    }

    private function buildCacheKey(int|string $userId, ?string $planName, ?int $applicationId): string
    {
        $date = Carbon::now()->format('Y-m-d');

        return sprintf(
            'premium_access:%s:%s:%s:%s',
            $userId,
            $planName ?? 'any',
            $applicationId ?? 'any',
            $date
        );
    }

    private function getCacheTTL(): int
    {
        // Cache until end of day
        return (int) Carbon::now()->endOfDay()->diffInSeconds(Carbon::now());
    }
}
