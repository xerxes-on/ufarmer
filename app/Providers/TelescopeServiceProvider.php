<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal): bool {
            if ($isLocal) {
                return true;
            }

            return $entry->isReportableException()
                || $entry->isFailedRequest()
                || $entry->isFailedJob()
                || $entry->isScheduledTask()
                || $entry->hasMonitoredTag();
        });

        Telescope::tag(function (IncomingEntry $entry): array {
            $tags = [];

            // Request ID tag
            if (app()->bound('request_id')) {
                $tags[] = 'request-id:'.app('request_id');
            }

            // IP address tag
            if ($ip = request()?->ip()) {
                $tags[] = 'ip:'.$ip;
            }

            // Auth ID tag
            return $tags;
        });
    }

    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Restrict Telescope dashboard access. In non-local environments the
     * dashboard (full request logs, PII, queries) is viewable by the
     * pre-existing email allowlist (config/admin.php, unchanged) or by the
     * `admin` panel's new super_admin role.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user): bool {
            if ($this->app->environment('local')) {
                return true;
            }

            if ($user === null) {
                return false;
            }

            $allowed = config('admin.panel_emails', []);

            if ($user->email !== null && in_array($user->email, $allowed, true)) {
                return true;
            }

            return method_exists($user, 'hasRole') && $user->hasRole('super_admin');
        });
    }
}
