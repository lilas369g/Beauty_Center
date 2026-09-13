# Beauty Center ERD v2

V2 extends the V1 single-tenant operational schema with staff/manager identities, role-based access control, future opaque API sessions, and privacy-aware audit records. V1 tables and migrations remain unchanged. All V2 internal and foreign keys are `bigint`, matching V1; actual date-times are UTC `timestamptz` values.

## Optional laser-area extension

Laser capability is opt-in: a normal service such as nails needs no laser record, while the existence of one `laser_service_settings` row marks a `services` row as laser-enabled. Its available areas are rows in `laser_service_areas`, joined to the global `laser_areas` catalogue (for example, `full_body`). Areas are rows, never per-area boolean columns on a service or appointment.

The historical execution chain is strictly `appointments → appointment_services → appointment_service_laser_areas`. The client is derived through the existing `appointments.customer_id` relation; the laser history table intentionally has no `customer_id`, `user_id`, or direct `appointment_id`.

V1's `(appointment_id, service_id)` primary key remains intact. An additive, unique identity column, `appointment_services.id`, is introduced solely as the stable foreign-key target for per-area history; no V1 migration is edited or rewritten.

| Parent | Child | Relationship | Delete rule |
| --- | --- | --- | --- |
| `services` | `laser_service_settings` | Zero or one laser setting per service. | Restrict |
| `laser_service_settings` | `laser_service_areas` | Configures the areas offered by that laser service. | Restrict |
| `laser_areas` | `laser_service_areas` | A global area can be offered by many laser services. | Restrict |
| `appointment_services` | `appointment_service_laser_areas` | One booked service can retain many selected area snapshots. | Cascade when the booked service is removed with its appointment |
| `laser_service_areas` | `appointment_service_laser_areas` | A selected area must reference the service's configured area. | Restrict |
| `employees` | `appointment_service_laser_areas` | Performer is optional for planned/skipped work. | Restrict |

`area_name_snapshot`, and optionally price/duration snapshots, preserve the booked per-area meaning. A future service layer copies defaults from `laser_service_areas` and may apply an allowed booking override; this schema does not create controllers, APIs, CRUD, audit logic, or workflows.

### Activation and deletion policy

Add a global area and activate it for a laser service by creating a setting/area row. Disable an area globally or for one service with `is_active = false`; this never removes historical records. Physical deletion is only suitable for unused configuration: every historical reference uses `RESTRICT`, so a used laser-service area cannot be removed. The global catalogue relation is also restrictive, so remove an unused configuration association first before deleting its unused global area.

`laser_treatment_plans` is deliberately deferred. There is no direct appointment-area link and no treatment-plan table in this extension.

## Relationships and access design

| Parent | Child | Relationship | Delete rule |
| --- | --- | --- | --- |
| `roles` | `users` | Every user has exactly one role. | Restrict |
| `roles` / `permissions` | `role_permissions` | Many-to-many role grants with a composite primary key. | Restrict from either parent |
| `employees` | `users` | Optional one-to-zero/one administrative account for an employee. | Set null |
| `users` | `auth_sessions` | A user owns opaque future API sessions. | Restrict |
| `users` | `audit_logs` | An audit actor is optional and retained as a nullable reference. | Set null |

An employee may exist without a login account, while an owner or manager may be a user without an employee record. `users.employee_id` is unique when non-null, so a staff record can be attached to at most one account.

Each user has exactly one role. This keeps the authorization check deterministic and avoids role precedence/combination rules in the MVP. Roles receive atomic permission codes such as `appointments.view`; a missing code is a denial. Codes remain readable and independently grantable, unlike bitmasks whose meaning is coupled to bit positions.

## Authentication-session and audit privacy

`auth_sessions` is schema-only in V2; it does not implement endpoints, token issuance, or login. A future API returns an opaque bearer token exactly once and stores only its SHA-256 value in `token_hash`. The raw token, password, and `Authorization` header must never be persisted or audited.

`audit_logs` is an append-only application-level design; no database trigger is created. It stores the actor only when necessary, uses hash fields for IP and user-agent correlation, and allows a `system` actor or a null actor for events such as failed login. JSONB snapshots intentionally have no GIN index in V2, and sensitive or unnecessary personal data must not be placed in `old_values` or `new_values`.

## V2 indexes and constraints

| Name | Purpose |
| --- | --- |
| `roles_slug_unique`, `permissions_code_unique` | Stable, unique role and permission identifiers. |
| `role_permissions_pkey (role_id, permission_id)` | Prevents duplicate grants and loads one role's permissions. |
| `users_employee_id_unique`, `users_email_unique` | Enforces one account per linked employee and unique account email. |
| `auth_sessions_token_hash_unique` | Performs token lookup without storing its raw value. |
| `auth_sessions_user_revoked_expires_idx (user_id, revoked_at, expires_at)` | Lists/evaluates a user's active, revoked, or expired sessions. |
| `audit_logs_entity_created_idx (entity_type, entity_id, created_at DESC)` | Reads a chronological audit trail for one referenced entity. |
| `audit_logs_actor_created_idx (actor_user_id, created_at DESC)` | Reads an actor's chronological audit trail. |
| `audit_logs_actor_type_valid`, `audit_logs_result_valid` | Restricts audit actor/result vocabulary without PostgreSQL enums. |
| `appointment_services_id_unique` | Additive foreign-key target for per-area laser history while retaining V1's composite primary key. |
| `laser_service_settings_service_id_unique` | Makes the laser setting an opt-in one-to-zero/one marker per service. |
| `laser_service_areas_setting_area_unique` | Prevents adding the same global area twice to one laser service. |
| `appointment_service_laser_areas_appointment_service_area_unique` | Prevents selecting the same configured area twice for one booked service. |
| `appointment_service_laser_areas_performed_by_employee_idx` | Supports filtering/retention checks by performer. |

## Deferred beyond V2

Controllers, login and token issuance, middleware, authorization execution, password reset/MFA/challenges, direct user permissions, inherited or grouped roles, bitmask permissions, products, inventory, invoices, payments, reports, notifications, mobile-client authentication, audit triggers, JSONB GIN indexes, and `laser_treatment_plans` are intentionally outside V2.
