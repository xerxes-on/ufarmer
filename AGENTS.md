# UFarm Admin API

> Inherits universal standards from root `/ufarm/AGENTS.md`

Filament-based admin panel for UFarm Core.

## Service-Specific

- **Shares database with ufarm-api**
- **Do NOT create migrations here** - All migrations in `ufarm-api`
- Uses Filament v4 for admin interface
- Uses `$this->successResponse()` / `$this->errorResponse()` for API endpoints

## Commands

```bash
php artisan serve
npm run dev
vendor/bin/pint --dirty
```

## Production

| | |
|---|---|
| **User** | `lv_api_admin_user` |
| **Path** | `/home/lv_api_admin_user/admin.prod.ufarmer.uz` |
| **Domain** | https://admin.prod.ufarmer.uz |
| **CI/CD** | Auto-deploy on push, workers auto-restart |
| **Alias** | `admin` |

### Testing
```bash
curl -X GET https://admin.prod.ufarmer.uz/api/v1/... \
  -H "Authorization: Bearer <token>" \
  -H "x-application-alias: admin"
```
