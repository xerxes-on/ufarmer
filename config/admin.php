<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Uzgidromet Panel Email Allowlist
    |--------------------------------------------------------------------------
    |
    | The `uzgidromet` Filament panel is NOT part of the admin_panel SSO
    | migration (the `admin` panel's own gate is now handled by ufarm-auth
    | application-alias membership, see App\Models\User). It keeps its
    | pre-existing, unrelated allowlist gate.
    | Set ADMIN_PANEL_EMAILS to a comma-separated list. Empty = fail-closed.
    |
    */
    'panel_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ADMIN_PANEL_EMAILS', '')),
    ))),
];
