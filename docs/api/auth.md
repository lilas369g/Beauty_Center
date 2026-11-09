# Authentication API (v1)

Staff sign in with email and password and receive a Sanctum Bearer token. Customer login is not implemented yet.

Every endpoint under `/api/v1` requires the header below, except `POST /api/v1/auth/login`:

```
Authorization: Bearer <token>
Accept: application/json
```

A token is rejected with `401`, the same as a missing token, when any of these is true:

- It is older than the expiration set in `config/sanctum.php` (`expiration`, read from `SANCTUM_EXPIRATION`, default `10080` minutes = 7 days).
- It was revoked by logout.
- Its owner's account is deactivated (`is_active = false`). This takes effect on the very next request, even if the token has not expired. Reactivating the account makes its unexpired tokens work again.

## Time values

All timestamps (for example `last_login_at`) are stored as `timestamptz` and returned in UTC, ISO 8601 (`...Z`). The app and the database session both run in UTC. Local business time (`Asia/Damascus`) is applied only when computing working hours and when displaying times.

## Unauthenticated response

Any protected endpoint called without a valid token returns `401`:

```json
{ "message": "Please sign in to continue." }
```

The message is translated (`auth.unauthenticated`).

## Login

`POST /api/v1/auth/login`

Permission: none (public).

| Field | Rules |
|---|---|
| `email` | required, string, email, max:255 |
| `password` | required, string, max:255 |
| `device_name` | required, string, max:150 (stored as the token name) |

```json
{ "email": "manager@example.com", "password": "secret", "device_name": "web" }
```

Success `200`:

```json
{
  "data": {
    "token": "1|Xk3…",
    "token_type": "Bearer",
    "user": {
      "name": "Manager",
      "email": "manager@example.com",
      "is_active": true,
      "last_login_at": "2026-11-08T10:00:00.000000Z"
    }
  }
}
```

On success `last_login_at` is set to the current time, and a new token is created. Earlier tokens stay valid.

Errors, all `422` in Laravel's validation format with the error on `email`:

| Case | Message key |
|---|---|
| Missing or invalid fields | standard validation messages |
| Unknown email, or wrong password (same message for both) | `auth.failed` |
| Correct credentials but `is_active = false` | `auth.inactive` |

```json
{
  "message": "The email or password is incorrect.",
  "errors": { "email": ["The email or password is incorrect."] }
}
```

## Logout

`POST /api/v1/auth/logout`

Permission: authenticated user.

No body. Deletes only the token used for this request. Other devices stay signed in. Returns `204` with no content. Without a valid token: `401`.

## Current user

`GET /api/v1/auth/me`

Permission: authenticated user.

Returns `200` with `{ "data": { ...user } }`, using the same user shape as the login response. Without a valid token, or if the account was deactivated: `401`.
