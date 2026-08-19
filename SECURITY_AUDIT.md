# UFarm Admin API — Security Audit Report

**Date**: 2026-06-04 | **Branch**: `staging` | **Files**: 1,319 PHP, 70 migrations | **Findings**: 20

The main Filament admin panel managing ALL ufarm-core modules. This panel has administration access to the entire farm ecosystem.

---

## 🔴 CRITICAL (4)

| # | File | Finding |
|---|------|---------|
| 1 | 106 of 107 `*Resource.php` files | **Zero auth on 106 Filament resources**. Only `UzgidrometFileResource` has `canViewAny()`. Every authenticated admin can view/edit/delete everything across all modules (users, billing, crops, weather, exporter PII, etc.). **PARTIAL FIX**: `app/Models/User.php` `canAccessPanel()` no longer returns `true` for everyone — panel entry is now gated to the `config('admin.panel_emails')` allowlist (env `ADMIN_PANEL_EMAILS`, fail-closed when empty). Per-resource RBAC still a follow-up (#2). |
| 2 | `Modules/Uzgidromet/src/Support/HasRoles.php` + `Models/Role.php` | **No RBAC**. Custom ~40-line `HasRoles` trait with ONE role defined: `uzgidromet`. No `superadmin`, `admin`, `operator` roles. Spatie permissions referenced in CLAUDE.md but not installed. Flat permission model = everyone is superadmin. |
| 3 | (Entire app) | **No 2FA/MFA**. Zero multi-factor authentication. Any admin password compromise = full platform access. |
| 4 | `config/auth.php`, `config/livewire.php` | **No login brute force protection**. Zero rate limiting on admin login. Livewire file upload middleware explicitly set to `null`, bypassing default `throttle:60,1`. **FIXED**: restored `config/livewire.php:70` upload middleware to `throttle:60,1`; Filament default Login page already enforces `rateLimit(5)` per min on the login route. |

## 🟠 HIGH (5)

| # | File | Finding |
|---|------|---------|
| 5 | 139 models across all modules | **139 models with `$guarded = []`** — zero mass assignment protection. Including `User` model. Any admin sets any column via Filament form. Combined with no RBAC = full data integrity bypass. |
| 6 | `app/Providers/TelescopeServiceProvider.php` | **Telescope records everything, no auth gate**. `Telescope::filter(fn() => true)`. Empty `gate()`. Any admin views full request logs with PII, session cookies, DB queries. **FIXED**: `filter()` now records everything only in `local`; in other envs only exceptions/failed requests/failed jobs/scheduled/monitored. `gate()` defines `viewTelescope` allowing only `local` or admin-allowlist emails. |
| 7 | `Modules/Weather/config/config.php:10` | **Hardcoded OpenWeatherMap API key** as fallback: `'82de0b0c1a73ccdc536cfb46850b4d2f'`. Used if env var missing. **FIXED**: removed literal fallback → `env('OPENWEATHER_API_KEY')` only. NOTE: leaked key must be rotated by ops. |
| 8 | `Modules/General/src/Filament/Resources/ArticleResource.php:153` | **RichEditor stores raw HTML** — no sanitization. If articles reach farmer-facing API, stored XSS against all users. |
| 9 | `UzgidrometFileResource.php:169` | References `'admin'` role that doesn't exist. Dead authorization check. |

## Medium (selected)

- `SESSION_ENCRYPT=false` — plaintext sessions in DB
- `MediaProxyController.php:29` — unescaped filename in `Content-Disposition` header (HTTP response splitting)
- Yandex map Blade template — PHP variable in JS context without `@json()`
- No `cors.php` config file
- Exporter PII (INN, license, phone) exposed with no audit logging
- CSRF token echoed inline in Blade JS (exfiltratable if XSS present)

## Top 5 Actions

1. **Install Spatie Laravel Permission**, define roles (superadmin, admin, operator), add `canViewAny()`/`canEdit()` etc. to all 106 resources
2. **Implement 2FA** via TOTP for all admin accounts
3. **Add login rate limiting** — 5 attempts/min per IP
4. **Replace `$guarded = []` with `$fillable`** on all 139 models
5. **Gate Telescope** — restrict to specific admin roles, hide sensitive params
