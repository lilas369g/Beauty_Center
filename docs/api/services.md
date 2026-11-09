# Services API (v1)

All endpoints here require a valid Bearer token (`auth:sanctum`); without one they return `401`. See [auth.md](auth.md). Per-permission checks (Policies) are not applied yet.

All public identifiers are UUIDs. Responses use Laravel's standard `data` wrapper; paginated service lists also include `links` and `meta`.

## Categories

`GET /api/v1/service-categories/options?active=true`

The optional `active` boolean filters by status. Categories are returned by display order then name for form controls. `GET /api/v1/service-categories?active=true` remains available for backward compatibility; new consumers use `/options`.

```json
{
  "data": [{ "uuid": "e02e…", "name": "Laser", "is_active": true }]
}
```

## List services

`GET /api/v1/services?search=underarms&category_uuid=e02e…&is_active=true&per_page=20`

Optional parameters: `search` (name only), `category_uuid`, `is_active` (`true` or `false`), and `per_page` (1–100; default 20). Omitting `is_active` returns both active and inactive services for the management screen.

```json
{
  "data": [{
    "uuid": "9f80…",
    "name": "Laser Underarms",
    "description": null,
    "price": "0.00",
    "duration_minutes": 30,
    "is_active": true,
    "is_laser_enabled": true,
    "category": { "uuid": "e02e…", "name": "Laser" },
    "created_at": "2026-09-17T10:00:00.000000Z",
    "updated_at": "2026-09-17T10:00:00.000000Z"
  }],
  "links": {},
  "meta": {}
}
```

`is_laser_enabled` is derived only from whether a `laser_service_settings` record already exists. These endpoints neither create laser settings nor edit laser areas.

## Create a service

`POST /api/v1/services`

```json
{
  "category_uuid": "e02e…",
  "name": "Laser Underarms",
  "description": "Optional",
  "price": 0,
  "duration_minutes": 30,
  "is_active": true
}
```

`category_uuid`, `name`, `price`, and `duration_minutes` are required. The category must exist; price must be non-negative; duration must be positive. Invalid input returns `422` with Laravel validation errors.

## Read and update a service

`GET /api/v1/services/{service_uuid}`

`PATCH /api/v1/services/{service_uuid}` accepts any subset of the create fields. It cannot update UUIDs, internal IDs, laser settings, or laser areas. Both calls return the service response shown above; an unknown UUID returns `404`.

## Enable or disable a service

`PATCH /api/v1/services/{service_uuid}/status`

```json
{ "is_active": false }
```

This is the current alternative to deletion. There is deliberately no `DELETE /api/v1/services/{uuid}` endpoint. The first React interface will use this module for a service list with filters, an add/edit form, and an enable/disable control.
