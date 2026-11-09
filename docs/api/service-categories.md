# Service categories API (v1)

All endpoints here require a valid Bearer token (`auth:sanctum`); without one they return `401`. See [auth.md](auth.md). Per-permission checks (Policies) are not applied yet. Public identifiers are UUIDs; bigint IDs are never returned.

## Admin list

`GET /api/v1/service-categories?search=laser&is_active=true&per_page=20`

All parameters are optional. `search` searches name and description; `is_active` filters status; `per_page` is 1–100. The response is Laravel pagination and each item includes `uuid`, `name`, `description`, `display_order`, `is_active`, `services_count`, `created_at`, and `updated_at`.

## Service form options

`GET /api/v1/service-categories/options?active=true`

Options default to active categories and are sorted by `display_order`, then name. The compact response has only `uuid`, `name`, `display_order`, and `is_active`.

The legacy `GET /api/v1/service-categories?active=true` is retained as a compatible alias for this compact list. New consumers should use `/options`.

## Create and update

`POST /api/v1/service-categories`

`PATCH /api/v1/service-categories/{category_uuid}`

```json
{
  "name": "Laser",
  "description": "Laser hair removal services",
  "display_order": 1,
  "is_active": true
}
```

Name and a positive display order are required on creation; name must be unique. Invalid input returns `422` validation errors.

## Change status

`PATCH /api/v1/service-categories/{category_uuid}/status`

```json
{ "is_active": false }
```

Disabling a category does not change its services. Disabled categories do not appear when creating a new service, but remain visible in management and on older service records.

## Safe deletion

`DELETE /api/v1/service-categories/{category_uuid}` returns `204` only when the category has no services. A category linked to any service cannot be deleted and returns `422` with: `لا يمكن حذف هذا التصنيف لأنه مرتبط بخدمات. عطّليه بدلاً من ذلك.` Disable it through the status endpoint instead.
