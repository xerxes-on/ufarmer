<?php

declare(strict_types=1);

namespace Ufarm\Premium\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Ufarm\Premium\Enums\SubscriptionStatus;
use Ufarm\Premium\Models\Subscription;
use Xerxes\AuthBridge\Models\Application;

class EnsurePremiumSubscription
{
    public function handle(Request $request, Closure $next, string ...$parameters): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED, 'User is not authenticated.');
        }

        [$planName, $applicationOverride] = $this->parseParameters($parameters);
        $applicationId = $this->resolveApplicationId($applicationOverride ?? $request->header('X-Application'));

        // Build cache key based on user, plan, and application
        $cacheKey = $this->buildCacheKey($user->getKey(), $planName, $applicationId);

        // Check cache first (cached until end of day)
        $hasAccess = Cache::remember($cacheKey, $this->getCacheTTL(), function () use ($user, $planName, $applicationId) {
            return Subscription::query()
                ->where('user_id', $user->getKey())
                ->where('status', SubscriptionStatus::ACTIVE->value)
                ->when($planName, fn ($query) => $query->whereHas('plan', fn ($planQuery) => $planQuery->where('name', $planName)))
                ->when($applicationId, fn ($query) => $query->where('application_id', $applicationId))
                ->where(function ($query) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>', Carbon::now());
                })
                ->exists();
        });

        if (! $hasAccess) {
            abort(Response::HTTP_FORBIDDEN, 'Premium subscription required.');
        }

        return $next($request);
    }

    private function parseParameters(array $parameters): array
    {
        $planName = null;
        $applicationAlias = null;

        foreach ($parameters as $parameter) {
            [$key, $value] = array_pad(explode(':', $parameter, 2), 2, null);

            if ($key === 'plan') {
                $planName = $value;
            }

            if ($key === 'application') {
                $applicationAlias = $value;
            }
        }

        return [$planName, $applicationAlias];
    }

    private function resolveApplicationId(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return Application::query()
            ->where('alias', $value)
            ->value('id');
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
