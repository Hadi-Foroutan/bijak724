# 

# Bijak API Integration Guide

Oct 17, 2026 · @reza

This guide is the contract between the Bijak Laravel 12 API and the React frontend: every route, payload, response and rule the frontend needs, written so a person or an AI assistant can build against it directly.

## 1. At a glance

The React app calls one Laravel 12 API. Every company-panel request is checked for a valid token, the right company scope and a route-name permission, then reads only that company's data.

&#91;embedded content: request path · 3 middleware checks, 2 kinds of storage, 1 realtime channel\]

Each company's operational records (waybills, drivers, fleets, parties, number ranges) live in its own `company_{id}_*` tables; a branch shares its parent's tables. Identity, roles, companies, contracts and insurances are shared tables filtered by `company_id`. The frontend never sends a company id: the token decides it.

## 2. Conventions

Every endpoint is JSON under `/api`, authenticated with a Sanctum bearer token, and wrapped in one response envelope.

### 2.1 Base URL and headers

| Item | Value |
| --- | --- |
| Base URL | `APP_URL` + `/api` (the example env uses `https://api.bijak724.ir`) |
| Auth header | `Authorization: Bearer <token>` on every route except login |
| Accept | Forced to `application/json` server-side; send it anyway |
| Body | JSON; use `multipart/form-data` only for image uploads |
| Locale | `fa`: messages and validation errors come back in Persian |
| Timezone | `Asia/Tehran`; dates are `YYYY-MM-DD`, times `HH:mm` |
| Health check | `GET /up` (outside `/api`) |

### 2.2 Route groups

| Prefix | Route-name prefix | Who calls it |
| --- | --- | --- |
| `/api/auth` | `auth.` | Anyone: login, token check, logout |
| `/api/user` | `user.` and `profile.` | Company panel: company users and admin support tokens |
| `/api/admin` | `admin.` | System admin panel; company tokens are rejected with 403 |

Numeric route parameters (`{user}`, `{waybill}`, `{transportContract}` and the rest) only match digits; anything else returns 404.

### 2.3 Success envelope

```json
{
  "success": true,
  "message": "عملیات موفق بود",
  "data": { }
}
```

`data` is an object for single records, an array for unpaginated lists, and a paginator object for paginated lists. Create returns `201`; everything else returns `200`. Delete returns `data: []` and a Persian success message.

### 2.4 Error shapes

| Status | When | Body |
| --- | --- | --- |
| 422 | Validation failed or a business rule was broken | `{ success: false, message, errors: { field: ["..."] } }` |
| 401 | Missing, expired or revoked token | `{ message }` only (no `success` key) |
| 403 | No permission for the route name, or a company token on an admin route | `{ message }` from the permission check; `{ success: false, message, errors }` from the company-scope check |
| 404 | Unknown route or record outside the current company | `{ success: false, message, errors: { error: ["..."] } }` |
| 429 | Rate limit hit | `{ success: false, message, errors }` |
| 400 and others | Thrown service errors (`ErrorException`) | `{ success: false, message, errors: { error: ["..."] } }` |

Nested field errors use dot paths, for example `items.3.primary_value` or `cargos.0.cargo_id`. Treat `success === false` or `status >= 400` as failure, and always read `message` as the user-facing text.

### 2.5 Lists: pagination, search, filters, sort

Index endpoints share one query-string grammar (the `AdvancedSearch` trait):

| Param | Effect |
| --- | --- |
| `paginate=1` | Return a Laravel paginator; omit it to get a plain array of all rows |
| `itemsPerPage=N` | Page size, clamped to 1–100, default 10 |
| `page=N` | Page number |
| `search=text` | `LIKE %text%` across the model's global-search columns |
| `<field>=x` | `LIKE %x%` on a searchable field |
| `eq-<field>=x` | Exact match |
| `notEq-<field>=x` | Not equal |
| `min-<field>=x` / `max-<field>=x` | `>=` / `<=`, for dates and amounts |
| `<relation>__<field>=x` | Filter through a relation, for example `cargos__cargo_id` |
| `is_parent=1` | Only rows with no `parent_id` |
| `order_field=<field>` | Sort column; unknown fields fall back to `id` |
| `order_type=ASC\|DESC` | Default `DESC` |

Unknown filter keys are ignored, not rejected. Paginated `data` has this shape:

```json
{
  "current_page": 1,
  "data": [],
  "first_page_url": "...",
  "from": 1,
  "last_page": 5,
  "last_page_url": "...",
  "links": [],
  "next_page_url": "...",
  "path": "...",
  "per_page": 10,
  "prev_page_url": null,
  "to": 10,
  "total": 48
}
```

### 2.6 Booleans and nulls

Send real JSON booleans. Some requests also accept the strings `"true"`/`"false"` (for example the transport-contract default flags), but don't rely on it. Send `null` to clear a nullable field; leaving a key out of a PATCH keeps the stored value.

## 3. Authentication

Log in with username and password, store the returned Sanctum token, and send it as a bearer token. The token's abilities decide whether the session is a system admin, a company user, or an admin acting as a company.

### 3.1 Endpoints

| Method | Path | Body | Returns |
| --- | --- | --- | --- |
| POST | `/api/auth/login` | `{ username, password }` | Auth context + `token` |
| GET | `/api/auth/checkToken` | none | Auth context (no new token) |
| POST | `/api/auth/logout` | none | `data: []`; revokes only the current token |
| POST | `/api/admin/companies/{company}/login-as` | none | `201` + auth context + a support `token` for that company; permission \`admin.companies.loginAs\`, caller must be super admin or \`admin\` role |

### 3.2 Auth context (`data`)

```json
{
  "token": "12|plain-text-token",
  "user": { "id": 7, "company_id": 1000, "full_name": "...", "username": "...", "status": "active", "profile_image_url": null, "...": "UserResource" },
  "role": { "id": 3, "name": "companyManager", "display_name": "..." },
  "permissions": ["user.waybills.index", "user.waybills.store"],
  "auth_mode": "company",
  "company_access": {
    "access_type": "company_user",
    "company": { "id": 1000, "name": "...", "...": "CompanyResource" },
    "issued_by": { "id": 7, "full_name": "..." },
    "permissions": [],
    "expires_at": null
  }
}
```

`token` is only present on login and support-token responses. `company_access` is absent when `auth_mode` is `system`.

### 3.3 Session modes

| `auth_mode` | Who | Token abilities | Expiry | Can call |
| --- | --- | --- | --- | --- |
| `system` | User with no `company_id` (admins) | `*` | Never | `/api/admin/*`; `/api/user/*` returns 403 because there is no company context |
| `company` | User belonging to a company | `company-user`, `company:{id}` | Never | `/api/user/*` only; admin routes return 403 |
| `company_support` | An admin who issued a support token | `company-support`, `company:{id}` | 1 day | `/api/user/*` for that company, limited to the `active companyManager` role's permissions |

### 3.4 Frontend flow

1. `POST /auth/login`, save `token`, `auth_mode`, `permissions` and `company_access`.
2. Route to the admin panel when `auth_mode === "system"`, otherwise to the company panel.
3. On app start, call `GET /auth/checkToken` to restore the session; a 401 means log out locally.
4. For "enter company as support", call the support-token endpoint with the admin token. Keep the admin token: the support token is rejected on admin routes. Switch back by discarding the support token.
5. Show a countdown from `company_access.expires_at` for support sessions; after it passes every call returns 401.
6. `POST /auth/logout` revokes only the token it is called with.

Login errors return 400 with a Persian message: user not found, wrong password, inactive user (403), user without a role (403), or inactive company (403).

## 4. Permissions and roles

A permission is a route name. The server allows a request when the user's direct, active permissions contain the current route's name (for example `user.waybills.store`), so the frontend should gate every screen and button on the same string.

### 4.1 How it works

- `php artisan generate-permissions` creates one permission per named route, except `*.login`, `*.logout` and `*.checkToken`, which are always allowed.
- Permissions are grouped by prefix (for example `user.waybills.` is "مدیریت بارنامه‌ها") for the permission-picker UI.
- Roles get permissions by pattern, then each user receives their role's permissions directly. The check reads the user's own permission list, not the role.
- `superAdmin` passes every check. Support tokens are checked against the `companyManager` role's active permissions.
- `user.users.*` is non-default: plain `user` role accounts never receive it, so they cannot manage other company users.

| Role (`name`) | Gets | Panel |
| --- | --- | --- |
| `superAdmin` | Everything (`*`) | Admin |
| `admin` | `admin.*` | Admin |
| `companyManager` | `user.*`, `profile.*` | Company |
| `user` | `user.*`, `profile.*` except non-default ones | Company |

### 4.2 Frontend rules

1. Read `permissions` from login or `checkToken` and keep it in a `Set<string>`.
2. Gate each route and action on its route name, for example show "ثبت بارنامه" only when `can("user.waybills.store")`.
3. Still handle 403 on every call: permissions can change server-side at any time.
4. Edit a company user's permissions with `GET`/`PUT /api/user/{user}/permissions`; an admin edits any user's with `GET /api/admin/users/{user}/permissions` and `POST /api/admin/users/permissions/{user}`.

The permission for each route is listed in the route catalog below.

## 5. Route catalog

The API has 135 named routes: 3 auth, 32 admin and 100 company-panel routes. Paths below are relative to `/api`; the permission is the route name. `PUT|PATCH` means either verb works, and PATCH is a partial update. Inquiry routes accept GET or POST.

### 5.1 Admin panel (`/api/admin`, system tokens only)

| Method | Path | Permission | Purpose |
| --- | --- | --- | --- |
| GET | `/admin/companies` | `admin.companies.index` | List companies |
| POST | `/admin/companies` | `admin.companies.store` | Create a company and its manager account |
| GET | `/admin/companies/aggregate-report` | `admin.companies.aggregate-report` | Cross-company report |
| GET | `/admin/companies/{company}` | `admin.companies.show` | Company detail |
| PUT\|PATCH | `/admin/companies/{company}` | `admin.companies.update` | Update a company |
| DELETE | `/admin/companies/{company}` | `admin.companies.destroy` | Delete a company |
| POST | `/admin/companies/{company}/login-as` | `admin.companies.loginAs` | Issue a 1-day support token |
| GET | `/admin/cities` | `admin.cities.index` | List cities |
| POST | `/admin/cities` | `admin.cities.store` | Create a city |
| GET | `/admin/cities/{city}` | `admin.cities.show` | City detail |
| PUT\|PATCH | `/admin/cities/{city}` | `admin.cities.update` | Update a city |
| DELETE | `/admin/cities/{city}` | `admin.cities.destroy` | Delete a city |
| GET | `/admin/notifications` | `admin.notifications.index` | Sent notifications |
| POST | `/admin/notifications` | `admin.notifications.store` | Send a realtime notification |
| GET | `/admin/notifications/{notification}` | `admin.notifications.show` | Notification detail |
| GET | `/admin/permissions` | `admin.permissions.index` | List permissions (grouped) |
| POST | `/admin/permissions` | `admin.permissions.store` | Create a permission |
| PUT\|PATCH | `/admin/permissions/{permission}` | `admin.permissions.update` | Update a permission |
| DELETE | `/admin/permissions/{permission}` | `admin.permissions.destroy` | Delete a permission |
| GET | `/admin/roles` | `admin.roles.index` | List roles |
| POST | `/admin/roles` | `admin.roles.store` | Create a role |
| GET | `/admin/roles/{role}` | `admin.roles.show` | Role detail |
| PUT\|PATCH | `/admin/roles/{role}` | `admin.roles.update` | Update a role |
| DELETE | `/admin/roles/{role}` | `admin.roles.destroy` | Delete a role |
| GET | `/admin/users` | `admin.users.index` | List users |
| POST | `/admin/users` | `admin.users.store` | Create a user |
| GET | `/admin/users/{user}` | `admin.users.show` | User detail |
| PUT\|PATCH | `/admin/users/{user}` | `admin.users.update` | Update a user |
| DELETE | `/admin/users/{user}` | `admin.users.destroy` | Delete a user |
| GET | `/admin/users/{user}/permissions` | `admin.users.permissions` | A user's permissions |
| POST | `/admin/users/permissions/{user}` | `admin.users.syncPermissions` | Replace a user's permissions |
| GET | `/admin/waybills` | `admin.waybills.index` | Waybills across companies |

### 5.2 Company panel: setup and people (`/api/user`)

| Method | Path | Permission | Purpose |
| --- | --- | --- | --- |
| POST | `/user/profile` | none (`profile.update`) | Update my profile (multipart, image) |
| GET | `/user/dashboard` | `user.dashboard.index` | Summary counters |
| GET | `/user/dashboard/waybills/daily` | `user.dashboard.waybills.daily` | Waybills per day |
| GET | `/user/dashboard/waybills/monthly` | `user.dashboard.waybills.monthly` | Waybills per month |
| GET | `/user/dashboard/cargos/top` | `user.dashboard.cargos.top` | Most-shipped cargos |
| GET | `/user/dashboard/drivers/top` | `user.dashboard.drivers.top` | Busiest drivers |
| GET | `/user/settings` | `user.settings.show` | Company settings |
| PUT\|PATCH | `/user/settings` | `user.settings.update` | Update company settings |
| GET | `/user/users` | `user.users.index` | Company users |
| POST | `/user/users` | `user.users.store` | Create a user (multipart, images) |
| GET | `/user/users/{user}` | `user.users.show` | User detail |
| POST | `/user/users/{user}` | `user.users.update` | Update a user (POST so multipart works) |
| DELETE | `/user/users/{user}` | `user.users.destroy` | Delete a user |
| GET | `/user/{user}/permissions` | `user.permissions.index` | A company user's permissions |
| PUT | `/user/{user}/permissions` | `user.permissions.update` | Replace a company user's permissions |
| GET | `/user/notifications` | `user.notifications.index` | My notifications |
| GET | `/user/notifications/{notification}` | `user.notifications.show` | Notification detail |

### 5.3 Company panel: lookups (`/api/user/general`)

| Method | Path | Permission | Purpose |
| --- | --- | --- | --- |
| GET | `/user/general/states` | `user.general.states` | Provinces |
| GET | `/user/general/cities/{state}` | `user.general.cities` | Cities of a province |
| GET | `/user/general/cargos` | `user.general.cargos` | Cargo catalog |
| GET | `/user/general/packaging` | `user.general.packaging` | Packaging types |
| GET | `/user/general/fleet-types` | `user.general.fleet-types` | Fleet types |
| GET | `/user/general/fleet-systems` | `user.general.fleet-systems` | Fleet systems (truck makes) |
| GET | `/user/general/insurance-companies` | `user.general.insurance-companies` | Active insurance companies |

### 5.4 Company panel: drivers and fleets

| Method | Path | Permission | Purpose |
| --- | --- | --- | --- |
| GET | `/user/drivers` | `user.drivers.index` | List drivers |
| POST | `/user/drivers` | `user.drivers.store` | Create a driver (multipart) |
| GET\|POST | `/user/drivers/inquiry` | `user.drivers.inquiry` | Find a driver by national code |
| GET | `/user/drivers/{driver}` | `user.drivers.show` | Driver detail |
| POST | `/user/drivers/{driver}` | `user.drivers.update` | Update a driver (POST so multipart works) |
| DELETE | `/user/drivers/{driver}` | `user.drivers.destroy` | Delete a driver |
| GET | `/user/drivers/{driver}/accounts` | `user.drivers.accounts.index` | Driver bank accounts |
| POST | `/user/drivers/{driver}/accounts` | `user.drivers.accounts.store` | Add a bank account |
| GET | `/user/drivers/{driver}/accounts/{account}` | `user.drivers.accounts.show` | Account detail |
| PUT\|PATCH | `/user/drivers/{driver}/accounts/{account}` | `user.drivers.accounts.update` | Update an account |
| DELETE | `/user/drivers/{driver}/accounts/{account}` | `user.drivers.accounts.destroy` | Delete an account |
| GET | `/user/fleets` | `user.fleets.index` | List fleets (vehicles) |
| POST | `/user/fleets` | `user.fleets.store` | Create a fleet |
| GET\|POST | `/user/fleets/inquiry` | `user.fleets.inquiry` | Find a fleet by its full plate |
| GET | `/user/fleets/{fleet}` | `user.fleets.show` | Fleet detail |
| PUT\|PATCH | `/user/fleets/{fleet}` | `user.fleets.update` | Update a fleet |
| DELETE | `/user/fleets/{fleet}` | `user.fleets.destroy` | Delete a fleet |

### 5.5 Company panel: parties, cargo, insurance

| Method | Path | Permission | Purpose |
| --- | --- | --- | --- |
| GET | `/user/shipment-parties` | `user.shipment-parties.index` | Senders and receivers |
| POST | `/user/shipment-parties` | `user.shipment-parties.store` | Create a party |
| GET\|POST | `/user/shipment-parties/inquiry` | `user.shipment-parties.inquiry` | Find a party by national ID |
| GET\|POST | `/user/shipment-parties/addresses/inquiry` | `user.shipment-parties.addresses-inquiry` | Find an address |
| GET | `/user/shipment-parties/{shipmentParty}` | `user.shipment-parties.show` | Party detail |
| PUT\|PATCH | `/user/shipment-parties/{shipmentParty}` | `user.shipment-parties.update` | Update a party |
| DELETE | `/user/shipment-parties/{shipmentParty}` | `user.shipment-parties.destroy` | Delete a party |
| GET | `/user/shipment-parties/{shipmentParty}/addresses` | `user.addresses.index` | A party's addresses |
| POST | `/user/shipment-parties/{shipmentParty}/addresses` | `user.addresses.store` | Add an address |
| GET | `/user/shipment-parties/{shipmentParty}/addresses/{address}` | `user.addresses.show` | Address detail |
| PUT\|PATCH | `/user/shipment-parties/{shipmentParty}/addresses/{address}` | `user.addresses.update` | Update an address |
| DELETE | `/user/shipment-parties/{shipmentParty}/addresses/{address}` | `user.addresses.destroy` | Delete an address |
| GET | `/user/product-owners` | `user.product-owners.index` | Product owners |
| POST | `/user/product-owners` | `user.product-owners.store` | Create a product owner |
| GET | `/user/product-owners/{productOwner}` | `user.product-owners.show` | Detail |
| PUT\|PATCH | `/user/product-owners/{productOwner}` | `user.product-owners.update` | Update |
| DELETE | `/user/product-owners/{productOwner}` | `user.product-owners.destroy` | Delete |
| GET | `/user/cargo-groups` | `user.cargo-groups.index` | Cargo groups (insurance classes) |
| GET | `/user/cargo-groups/{cargoGroup}` | `user.cargo-groups.show` | Group detail |
| PUT | `/user/cargo-groups/{cargoGroup}/cargos` | `user.cargo-groups-cargos.update` | Replace a group's cargos |
| GET | `/user/insurances` | `user.insurances.index` | Insurance policies |
| POST | `/user/insurances` | `user.insurances.store` | Create a policy |
| GET\|POST | `/user/insurances/inquiry` | `user.insurances.inquiry` | Resolve a policy/tariff |
| GET | `/user/insurances/{insurance}` | `user.insurances.show` | Policy detail |
| PUT\|PATCH | `/user/insurances/{insurance}` | `user.insurances.update` | Update a policy |
| DELETE | `/user/insurances/{insurance}` | `user.insurances.destroy` | Delete a policy |
| GET | `/user/insurances/{insurance}/tariffs` | `user.insurances.tariffs.index` | Tariffs of a policy |
| POST | `/user/insurances/{insurance}/tariffs` | `user.insurances.tariffs.store` | Add a tariff |
| GET | `/user/insurances/{insurance}/tariffs/{tariff}` | `user.insurances.tariffs.show` | Tariff detail |
| PUT\|PATCH | `/user/insurances/{insurance}/tariffs/{tariff}` | `user.insurances.tariffs.update` | Update a tariff |
| DELETE | `/user/insurances/{insurance}/tariffs/{tariff}` | `user.insurances.tariffs.destroy` | Delete a tariff |

### 5.6 Company panel: contracts, numbering, waybills

| Method | Path | Permission | Purpose |
| --- | --- | --- | --- |
| GET | `/user/transport-contracts` | `user.transport-contracts.index` | Contracts, defaults first |
| POST | `/user/transport-contracts` | `user.transport-contracts.store` | Create a contract with its 11 items |
| GET | `/user/transport-contracts/options` | `user.transport-contracts.options` | Item names, types, default flags |
| GET | `/user/transport-contracts/{transportContract}` | `user.transport-contracts.show` | Contract detail |
| PUT\|PATCH | `/user/transport-contracts/{transportContract}` | `user.transport-contracts.update` | Update a contract |
| DELETE | `/user/transport-contracts/{transportContract}` | `user.transport-contracts.destroy` | Delete (blocked once used by an issued waybill) |
| GET | `/user/transport-contracts/{transportContract}/users` | `user.transport-contracts.users` | Users the contract is limited to |
| PUT | `/user/transport-contracts/{transportContract}/users` | `user.transport-contracts.syncUsers` | Limit to users; `[]` makes it public |
| GET | `/user/bijak-numbers` | `user.bijak-numbers.index` | Bijak number ranges |
| POST | `/user/bijak-numbers` | `user.bijak-numbers.store` | Add a range |
| GET\|POST | `/user/bijak-numbers/inquiry` | `user.bijak-numbers.inquiry` | Next available bijak number |
| GET | `/user/bijak-numbers/{bijakNumber}` | `user.bijak-numbers.show` | Range detail |
| PUT\|PATCH | `/user/bijak-numbers/{bijakNumber}` | `user.bijak-numbers.update` | Update a range |
| DELETE | `/user/bijak-numbers/{bijakNumber}` | `user.bijak-numbers.destroy` | Delete a range |
| GET | `/user/referral-numbers` | `user.referral-numbers.index` | Referral (حواله) ranges |
| POST | `/user/referral-numbers` | `user.referral-numbers.store` | Add a range |
| POST | `/user/referral-numbers/inquiry` | `user.referral-numbers.inquiry` | Next available referral number |
| GET | `/user/referral-numbers/{referralNumber}` | `user.referral-numbers.show` | Range detail |
| PUT\|PATCH | `/user/referral-numbers/{referralNumber}` | `user.referral-numbers.update` | Update a range |
| DELETE | `/user/referral-numbers/{referralNumber}` | `user.referral-numbers.destroy` | Delete a range |
| GET | `/user/canceled-referrals` | `user.canceled-referrals.index` | Canceled referral numbers |
| GET | `/user/waybills` | `user.waybills.index` | List waybills |
| POST | `/user/waybills` | `user.waybills.store` | Create a waybill (incomplete or complete) |
| GET | `/user/waybills/options` | `user.waybills.options` | Statuses and form options |
| GET | `/user/waybills/{waybill}` | `user.waybills.show` | Waybill detail |
| PUT\|PATCH | `/user/waybills/{waybill}` | `user.waybills.update` | Update a waybill |
| DELETE | `/user/waybills/{waybill}` | `user.waybills.destroy` | Delete a waybill (no status check server-side) |
| PATCH | `/user/waybills/{waybill}/cancel` | `user.waybills.cancel` | Cancel an issued waybill |
| PATCH | `/user/waybills/{waybill}/referral/cancel` | `user.waybills.referral.cancel` | Cancel its referral number |

## 6. Modules

Each module lists its request fields (create rules; update makes every field optional unless noted) and the fields its response returns. `!` marks required on create. Records are always scoped to the token's company, so IDs from another company return 404.

Company-panel records (drivers, fleets, parties, addresses, bijak and referral ranges, cargos, product owners, waybills) live in per-company tables. Their responses return `id`, every column listed, then `created_at` and `updated_at`.

### 6.1 My profile — `/user/profile`

Updates the signed-in user's own profile. No permission needed: `profile.update` is excluded from the permission check and only needs a valid token. Use `multipart/form-data` for the image. Every field is optional; only sent fields change.

| Field | Type | Rules |
| --- | --- | --- |
| `username` | string | Unique in the system (keeping your own is fine); max 255 |
| `first_name`, `last_name` | string | max 255 |
| `print_name` | string/null | Printed title |
| `national_code` | string | Unique |
| `postal_code`, `telephone` | string/null | max 20 |
| `phone` | string/null | Mobile number; unique |
| `min_commission_percentage`, `max_commission_percentage` | number | 0 to 100 |
| `address` | string/null |  |
| `password` | string/null | min 7; if omitted or blank the password is left unchanged |
| `profile_image` | file | jpg, jpeg, png, webp; max 5120 KB |
| `remove_profile_image` | bool | Remove the current image |

Response: the updated `UserResource` (same shape as company users).

### 6.2 Company users — `/user/users`

Send `multipart/form-data` when uploading images; update is `POST /user/users/{user}`.

| Field | Type | Rules |
| --- | --- | --- |
| `first_name`!, `last_name`! | string | max 255 |
| `national_code`! | string | unique across all users |
| `username`! | string | unique |
| `password`! (create only) | string | min 7; omit on update to keep it |
| `min_commission_percentage`!, `max_commission_percentage`! | number | 0–100 |
| `phone`, `email` | string | unique; email format |
| `parent_id` | int | an existing user (reporting line) |
| `print_name`, `address`, `description` | string |  |
| `profile_image`, `signature_image` | file | jpg, jpeg, png, webp; max 5120 KB |
| `remove_profile_image`, `remove_signature_image` | bool | delete the stored image |
| `status` | enum | `UserStatus` |

Response (`UserResource`): `id, company_id, parent_id, parent{id, full_name, print_name}, role, full_name, first_name, last_name, print_name, national_code, phone, email, username, min_commission_percentage, max_commission_percentage, address, profile_image_url, signature_image_url, description, status`.

Permissions: `GET /user/{user}/permissions` returns the user's permissions; `PUT` with `{ permissions: number[] }` (ids, `present`, may be `[]`) replaces them. Only ids the `companyManager` role holds are accepted.

### 6.3 Admin users — `/admin/users`

Same fields as company users plus `role_id`! (an existing role) and `company_id` (required for company roles). `status`! is required, and the remove flags are `remove_profile_image`/`is_profile_delete` and `remove_signature_image`/`is_signature_delete`. `POST /admin/users/permissions/{user}` takes `{ permissions: number[] }` (required, existing ids).

### 6.4 Companies — `/admin/companies`

| Field | Type | Rules |
| --- | --- | --- |
| `parent_type`! | enum | `CompanyParent` |
| `parent_id` | int | required when `parent_type` is `branch` and must be an original, top-level company; forbidden otherwise |
| `organization_code`! | string | unique |
| `name`! | string | max 255 |
| `national_code`! | string | unique, max 20 |
| `city_code`! | int | an existing `cities.code` |
| `contact_code1..3`, `technical_contact_first_name`, `technical_contact_last_name`, `technical_contact_phone`, `tel`, `fax`, `postal_code`, `email`, `logo`, `brand`, `address`, `description` | string | optional |
| `status` | enum | `Status` |

Response (`CompanyResource`): every field above plus `id, panel_code, created_at, updated_at, account` (the manager `UserResource` when loaded). Creating a company also creates its default transport contract, default bijak and referral ranges, default insurance and default settings. Branches share their parent's company tables.

`GET /admin/companies/aggregate-report` accepts the list grammar plus `min-/max-/eq-waybills_count`, `last_waybill_from`, `last_waybill_to`. It orders by `id, name, organization_code, panel_code, national_code, status, created_at, waybills_count, last_waybill_at` and returns `id, parent_id, parent_type, panel_code, organization_code, name, national_code, status, waybills_count, last_waybill_at`.

### 6.5 Roles and permissions — `/admin/roles`, `/admin/permissions`

Role: `name`! (unique), `display_name`!, `description`, `status`, `permissions`! (array of permission ids). Permission: `name`! (unique, route-name pattern), `display_name`!, `description`, `status`, `is_default`.

### 6.6 Cities — `/admin/cities` and lookups

City: `name`!, `code`! (unique int), `state_id`!, `tax_id`, `tax_ostan`, `anbar_code`. Lookups under `/user/general/*` are read-only lists for dropdowns: states, cities of a state, cargos, packaging, fleet types, fleet systems and active insurance companies.

### 6.7 Drivers — `/user/drivers`

Update is `POST /user/drivers/{driver}` so multipart works.

| Field | Type | Rules |
| --- | --- | --- |
| `national_code`! | string | unique within the company |
| `first_name`!, `last_name`! | string | max 255 |
| `license_type`! | int | an existing driver license type id |
| `father_name`, `license_number` | string |  |
| `license_expiry_date` | date | `YYYY-MM-DD` |
| `phone_number_1..3` | string | max 20 |
| `profile_image` | file | jpg, jpeg, png, webp; max 5120 KB |
| `remove_profile_image` / `is_profile_delete` | bool | update only |
| `description` | string |  |
| `status` | enum | `Status` |

Response: `id, national_code, first_name, last_name, full_name, father_name, license_number, license_type_id, license_type, default_account, license_expiry_date, phone_number_1..3, profile_image_url, description, status, created_at, updated_at`.

Inquiry: `national_code` (exactly 10 digits) returns the matching driver or 404.

Bank accounts — `/user/drivers/{driver}/accounts`: `sheba_number`! (max 34), `bank_name`!, `owner_name`!, `is_default`. The first account becomes the default; setting another as default unsets the old one, and deleting the default promotes another account.

### 6.8 Fleets — `/user/fleets`

| Field | Type | Rules |
| --- | --- | --- |
| `plate_first_number`! | string | 2 digits |
| `plate_second_letter`! | string | 1 character on create, up to 3 on update |
| `plate_third_number`! | string | 3 digits |
| `plate_fourth_number`! | string | 2 digits (region code) |
| `smart_card_number` | string | unique within the company, max 50 |
| `status`, `ownership_type` | enum | `Status`, `FleetOwnershipType` |
| `manufacture_year` | int | 1300 to next year |
| `driver_license_type_id`, `loading_type_id`, `system_id`, `tip_code` | int | existing ids for the first two |
| `owner_mobile` | string | `09` followed by 9 digits |
| `insurance_policy_number`, `chassis_number`, `engine_number`, `vin`, `document_number`, `description` | string |  |
| `document_date`, `insurance_date`, `technical_inspection_valid_until` | date | `YYYY-MM-DD` |
| `has_violation` | bool |  |

Response adds `plate{first_number, second_letter, third_number, fourth_number}`, `is_loading_type_fixed`, and nested `driver_license_type`, `loading_type`, `brand` and `type` objects. Inquiry takes the four plate parts (letter 1–3 chars) and returns the company's fleet with that exact plate.

### 6.9 Shipment parties and addresses — `/user/shipment-parties`

Party: `national_identifier`! (unique in the company, max 20), `is_sender`!, `is_receiver`! (at least one true), `first_name`!, `last_name`!, `title`, `mobile`, `landline`, `intermediary_code`, `transportation_code`, `email`, `description`, `status`. The response includes `full_name` and `addresses[]`.

Address — `/user/shipment-parties/{id}/addresses`: `postal_code`! (unique per party), `city_code`!, `address`!, `phone` (max 11), `description`. The response includes `city`.

Inquiries:

- `/inquiry`: `national_code` (10–11 digits) and `type` (`sender` or `receiver`).
- `/addresses/inquiry`: `postal_code` and `type`.

Both return the matching active record or a Persian error, which reports inactive ones.

### 6.10 Product owners — `/user/product-owners`

`name`!, `phone`! (max 20), `transportation_code`!.

### 6.11 Cargo groups — `/user/cargo-groups`

Read-only groups (`id, name, group_number, status, cargos[]`) used for insurance tariffs. `PUT /{cargoGroup}/cargos` with `{ cargo_ids: number[] }` replaces the group's cargos. The ids are cargo **codes** from `/user/general/cargos`, distinct.

### 6.12 Insurances and tariffs — `/user/insurances`

Insurance: `insurance_company_id`!, `title`!, `contract_number`! (unique in company), `is_default`!, `status`!, `start_date`!, `end_date`! (≥ start), `description`, and the `representative_first_name`, `_last_name`, `_mobile`, `_phone`, `_fax`, `_email` and `_address` fields. Only one insurance per company is default; setting another clears it. The response adds `insurance_company{id, name}`, `representative_full_name` and `tariffs[]`.

Tariff — `/user/insurances/{insurance}/tariffs`: `cargo_group_id` (null means all groups), `cargo_value_from`!, `cargo_value_to` (≥ from, null means unlimited), `fixed_premium` or `premium_percentage` (0–100; one is required), `excess_amount`, `description`. Tariffs are value ranges per cargo group.

Inquiry: `{ insurance_id, cargos: [{ id: <cargo code>, value }] }` returns the premium the matching tariffs produce for those cargos.

### 6.13 Bijak and referral number ranges — `/user/bijak-numbers`, `/user/referral-numbers`

Both take the same fields: `title`!, `serial_number`!, `from_number`! (≥ 1), `to_number`! (≥ from), `last_number` (create only; prohibited on update) and `status` (`active`, `inactive` or `completed`). New companies get an active default referral range of 900000 numbers. Inquiry returns the next number to use: bijak takes no body, referral takes `{ waybill_id }`. `GET /user/canceled-referrals` lists canceled referral numbers with `waybill_snapshot` and `cargos_snapshot` JSON.

### 6.14 Settings — `/user/settings`

One key so far, `general.assign_first_available_waybill_number` (boolean, default `false`). Send it as `{ "general": { "assign_first_available_waybill_number": true } }`.

### 6.15 Dashboard — `/user/dashboard`

Read-only statistics for the company: summary counters, daily and monthly waybill counts, top cargos and top drivers. Render them as charts; no request body.

## 7. Waybills and transport contracts

A waybill (بارنامه / بیجک) moves from `incomplete` to `referral` to `completed`. Each step requires more fields, and the server reserves the referral and bijak numbers. Only `incomplete` and `referral` waybills can be edited.

&#91;embedded content: waybill lifecycle · 4 statuses, 5 transitions\]

Create accepts any status except `canceled`. Canceling a referral archives its number and returns the waybill to `incomplete`.

### 7.1 Statuses

| Status | Label | Editable | How it's reached |
| --- | --- | --- | --- |
| `incomplete` | ناتمام | Yes | Create or update with this status; also the result of canceling a referral |
| `referral` | صدور حواله | Yes | Create or update with this status; the server assigns `referral_number` and `referral_serial` |
| `completed` | تمام‌شده | No | Create or update with this status; the server reserves the bijak number and tracking code |
| `canceled` | باطل‌شده | No | Only `PATCH /user/waybills/{id}/cancel` on a completed waybill |

Rules the UI must follow:

- Sending `status: "canceled"` to create or update returns 422; use the cancel endpoint.
- Updating a `completed` or `canceled` waybill returns 422 ("بارنامه صادرشده یا باطل‌شده قابل ویرایش نیست").
- A `referral` waybill updated with `status: "incomplete"` stays `referral`. To go back, call `PATCH /{id}/referral/cancel`: it archives the number in canceled referrals, clears it, and sets the waybill to `incomplete`.
- `referral_number`, `referral_serial` and `bijak_tracking_code` are read-only; the server ignores them on update.
- With the setting `general.assign_first_available_waybill_number` on, completing with a number other than the first free bijak number returns 422 naming the number to use.

### 7.2 Required fields by status

| Field group | `incomplete` | `referral` | `completed` |
| --- | --- | --- | --- |
| `status` | required | required | required |
| `sender_id`, `sender_address_id`, `receiver_id`, `receiver_address_id` | optional | required | required |
| `driver1_id`, `referral_driver_id`, `fleet_id` | optional | required | required |
| `loading_started_at`, `loading_ended_at` (≥ start) | optional | required | optional |
| `bijak_number`, `serial_number`, `issued_at`, `liability_insurance` | ignored | ignored | required |
| `transport_contract_id`, `base_freight_amount` (≥ 1), `insurance_amount` (≥ 1), `insurance_tax_amount`, `payable_amount` | optional | optional | required |
| `cargos` (1–10 rows) | optional, 0–10 rows | required | required |
| `cargos.*.cargo_id`, `cargos.*.packaging_id` | optional | required | required |
| `cargos.*.value`, `cargos.*.quantity` (≥ 1) | optional | optional | required |
| `cargos.*.origin_weight` | required on every row | required | required |

Other fields:

- Optional in every status: `driver2_id` (must differ from `driver1_id`), `referral_weight`, `quantity`, `description`, `advance_freight_amount`, `weighbridge_amount`, `loading_amount`, `warehousing_amount`, `unloading_amount`, `excess_tonnage_amount`, `commission_amount`, `detention_amount`, `driver_receivable_amount`, `freight_at_origin`, `is_fixed`.
- Cargo rows also take `product_owner_id`, `title`, `description` and `is_traffic`.
- All amounts are non-negative integers; the API does not state the currency unit.
- IDs must belong to the company:
  - `sender_id` must be a party with `is_sender`, and `receiver_id` one with `is_receiver`.
  - Each address id must belong to its party.
  - `cargo_id` and `packaging_id` are catalog **codes**, not ids.
  - `transport_contract_id` must be a contract the user can see (public, or assigned to them).

Known issue: `base_freight_amount` has no `nullable` rule, so sending `null` on an incomplete waybill fails validation. Omit the key instead.

### 7.3 Snapshots

On save the server copies the party, address and driver details into the waybill: `sender_full_name`, `sender_address_postal_code`, `driver1_national_code` and so on. A printed waybill therefore never changes when the source record is edited later. Read these snapshot columns for display and printing; the nested `sender`, `first_driver`, `fleet` and other objects show the live records.

### 7.4 Waybill response

- Every column of the waybills table: the snapshots, the numbers, all `*_amount` fields, `status`, `freight_at_origin` and `is_fixed`.
- `issued_at` as `YYYY-MM-DD HH:mm:ss`.
- Nested `sender`, `sender_address`, `receiver`, `receiver_address`, `first_driver`, `second_driver`, `referral_driver`, `fleet`, `insurance` and `transport_contract` objects.
- `cargos[]`: each row has `id, cargo_id, packaging_id, product_owner_id, title, description, origin_weight, value, quantity, is_traffic, is_returned, cottage_number, cottage_number_2, driver_account_number, container_number, container_number_2` and nested `cargo`, `packaging` and `product_owner`.
- `transport_contract.items` is only included while the waybill is `incomplete` or `referral`.

List filters use the shared grammar on any waybill column, plus:

- `fleet__plate_*` and `fleet__smart_card_number`
- `firstDriver__full_name`
- `cargos__cargo__code`
- `origin__city__code` and `destination__city__code`

The admin list `GET /admin/waybills` takes `company_id`, `company_name`, `status`, `created_at_from`, `created_at_to`, `serial_number`, `referral_number` and `bijak_tracking_code`.

### 7.5 Form options — `GET /user/waybills/options`

```json
{
  "statuses": [{ "value": "incomplete", "label": "ناتمام" }],
  "transport_contracts": [
    { "id": 1, "title": "...", "contract_number": "1", "items": [{ "name": "commission", "primary_value": 10 }] }
  ]
}
```

Contracts are the ones the user may use, default-flagged ones first.

### 7.6 Transport contracts

A contract holds exactly 11 items, one per `TransportContractItemName`. Each item says which fleet ownership types it applies to and how its amount is computed. `primary_value` is a percentage of the base freight (0–100). The exception is `base_freight`, where it is the per-ton rate and may exceed 100. `secondary_value` is a fixed amount (≥ 0).

| Field | Type | Rules |
| --- | --- | --- |
| `title`!, `contract_number`! | string | number unique in the company |
| `contract_date`! | date | `YYYY-MM-DD` |
| `customer_name`! | string |  |
| `status`! | enum | `Status` |
| `default_owned`!, `default_rental`!, `default_free`!, `default_unknown`! | bool | see default flags below |
| `description` | string |  |
| `items`! | array | exactly 11, one per item name, names distinct |
| `items.*.name` | enum | `TransportContractItemName` |
| `items.*.is_owned`, `is_rental`, `is_free`, `is_unknown` | bool | all `false` for `base_freight` |
| `items.*.charge_recipient` | bool |  |
| `items.*.primary_value` | number \| null | 0–100, except `base_freight` (≥ 0) |
| `items.*.secondary_value` | number \| null | ≥ 0 |

On update, every top-level field is optional. `items` is required on PUT and optional on PATCH, and when sent it replaces all 11 items.

**Default flags.** Each flag marks the company's default contract for one fleet ownership type (owned, rental, free, unknown). A flag can be held by only one contract per company: saving a contract with `default_rental: true` clears `default_rental` on every other contract in the same transaction. One contract may hold several flags; the contract every new company gets holds all four. Lists put flagged contracts first. There is no `is_default` field any more.

**Item response** (`TransportContractItemResource`): `id, name, name_label, field_name, is_owned, is_rental, is_free, is_unknown, charge_recipient, primary_value, primary_value_label, secondary_value, secondary_value_label, editable_fields{is_owned, is_rental, is_free, is_unknown, charge_recipient, primary_value, secondary_value}`. `field_name` is the waybill amount column the item feeds, for example `commission` → `commission_amount`. Use `editable_fields` to disable inputs; base freight has its type checkboxes and `charge_recipient` locked.

**Options** — `GET /user/transport-contracts/options` returns:

- `items[]`: `{ id (1–11), value, label, field_name, primary_value_label, secondary_value_label, editable_fields }`.
- `types[]`: `{ field: "is_owned"…, value, label }`.
- `defaults[]`: `{ field: "default_unknown"…, value, label }`, ordered unknown, free, rental, owned.

Build the contract form from this response instead of hard-coding the list.

**Visibility.** Contracts are public by default (`is_public: true`). `PUT /{id}/users` with `{ user_ids: number[] }` limits a contract to those users; `[]` makes it public again. Users who can view all contracts (managers) always see every contract. The response includes `is_public` and, on show, `user_ids`.

A contract referenced by an issued or canceled waybill cannot be deleted (422).

## 8. Realtime notifications

Admins broadcast notifications over Laravel Reverb (Pusher protocol) on one private channel, `notifications`, that every authenticated user may join. The event is `notification.sent`.

| Item | Value |
| --- | --- |
| Server | Reverb: `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME` |
| Channel auth | `POST /api/broadcasting/auth` with the bearer token |
| Channel | `private-notifications` (Echo: `Echo.private("notifications")`) |
| Event | `.notification.sent` (leading dot in Echo, since it uses `broadcastAs`) |
| Sent by | `POST /api/admin/notifications` with `{ type, message, should_remove_previous? }` |

Payload:

```json
{
  "notification": {
    "id": 42,
    "type": "warning",
    "should_remove_previous": true,
    "message": "...",
    "created_at": "2026-10-17T08:30:00.000000Z"
  }
}
```

Client setup:

```ts
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;
export const echo = new Echo({
  broadcaster: "reverb",
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: Number(import.meta.env.VITE_REVERB_PORT),
  wssPort: Number(import.meta.env.VITE_REVERB_PORT),
  forceTLS: import.meta.env.VITE_REVERB_SCHEME === "https",
  enabledTransports: ["ws", "wss"],
  authEndpoint: `${API_BASE}/broadcasting/auth`,
  auth: { headers: { Authorization: `Bearer ${token}` } },
});

echo.private("notifications").listen(".notification.sent", ({ notification }) => {
  if (notification.should_remove_previous) clearNotifications();
  pushNotification(notification);
});
```

When `should_remove_previous` is true, drop earlier notifications before showing the new one. Display style follows `type` (`success`, `error`, `warning`, `info`). The history endpoints return the same data with `type_label` and `sender{id, full_name}`: `GET /user/notifications` for users and `GET /admin/notifications` for admins, which also includes the sender's `username` and `email`. Load the history on start, then append live events.

## 9. Enums

Send the `value` column; show the label. Enums marked "from the API" also come with labels in responses (`status_label`, `name_label`, `type_label`, or the options endpoints), so prefer those over hard-coded text.

| Enum | Values (label) | Used by |
| --- | --- | --- |
| `Status` | `active` (فعال), `inactive` (غیرفعال) | companies, roles, permissions, drivers, fleets, parties, insurances, transport contracts |
| `UserStatus` | `active`, `inactive`, `blocked` | users |
| `CompanyParent` | `original`, `branch` | companies |
| `Role` | `superAdmin`, `admin`, `companyManager`, `user` | `role.name` |
| `WaybillStatus` | `incomplete` (ناتمام), `referral` (صدور حواله), `completed` (تمام‌شده), `canceled` (باطل‌شده) — from the API | waybills |
| `BijakNumberStatus` / `ReferralNumberStatus` | `active` (فعال), `inactive` (غیرفعال), `completed` (اتمام‌شده) | number ranges |
| `FleetOwnershipType` | `owned` (ملکی), `leased` (استیجاری), `free` (آزاد), `unknown` (نامشخص) | `fleets.ownership_type` |
| `TransportContractItemType` | `owned` (ملکی), `rental` (استیجاری), `free` (آزاد), `unknown` (نامشخص) — fields `is_<value>` and `default_<value>` | contract items and default flags |
| `NotificationType` | `success` (موفقیت‌آمیز), `error` (خطا), `warning` (هشدار), `info` (اطلاعات) — from the API | notifications |
| `SettingValueType` | `boolean`, `integer`, `decimal`, `string` | settings |

Note the mismatch: fleets use `leased`, while contract items and default flags use `rental`. Map them explicitly; don't reuse one constant for both.

### 9.1 Transport contract item names

Order matches the options endpoint (`id` 1–11).

| `id` | `value` | Label | Waybill field (`field_name`) |
| --- | --- | --- | --- |
| 1 | `base_freight` | کرایه پایه | `base_freight_amount` |
| 2 | `loading_cost` | هزینه بارگیری | `loading_amount` |
| 3 | `weighbridge_cost` | هزینه باسکول | `weighbridge_amount` |
| 4 | `warehousing` | انبارداری | `warehousing_amount` |
| 5 | `unloading_cost` | تخلیه بار | `unloading_amount` |
| 6 | `commission` | کمیسیون | `commission_amount` |
| 7 | `excess_tonnage` | اضافه تناژ | `excess_tonnage_amount` |
| 8 | `insurance_premium` | حق بیمه | `insurance_amount` |
| 9 | `insurance_vat` | ارزش افزوده بیمه | `insurance_tax_amount` |
| 10 | `advance_freight` | پیش‌کرایه | `advance_freight_amount` |
| 11 | `detention` | حق توقف | `detention_amount` |

`primary_value` label: base freight کرایه از هر تن, insurance premium درصد از تخفیف, others درصد از کرایه. `secondary_value` label: base freight کرایه ثابت, insurance premium مقدار ثابت از تخفیف, others مقدار ثابت.

## 10. Working with an AI assistant

Give the assistant two things: the generated OpenAPI file as the source of truth for shapes, and this guide for the rules the spec can't express. These rules are status-dependent requirements, number reservation, default flags and permissions. Regenerate the spec on every backend change.

### 10.1 1. Machine-readable contract

The backend ships Dedoc Scramble, which builds an OpenAPI 3 document from the routes, form requests and resources.

| How | Where |
| --- | --- |
| Browse (local env only) | `GET /docs/api` (UI), `GET /docs/api.json` (spec) |
| Export a file | `php artisan scramble:export --path=api.json` |
| Generate TS types | `npx openapi-typescript api.json -o src/api/schema.d.ts` |

Commit `api.json` and `schema.d.ts` to the frontend repo, and have CI regenerate them when the backend changes. Point the assistant at both files plus this guide.

### 10.2 2. Core TypeScript types

Use these hand-written types until the generated ones land, or as wrappers around them:

```ts
export type ApiSuccess<T> = { success: true; message: string; data: T };
export type ApiError = {
  success?: false;
  message: string;
  errors?: Record<string, string[]>; // dot paths, e.g. "items.3.primary_value"
};

export type Paginated<T> = {
  current_page: number; data: T[]; per_page: number; total: number;
  last_page: number; from: number | null; to: number | null;
  next_page_url: string | null; prev_page_url: string | null;
};

export type ListQuery = {
  paginate?: 0 | 1; page?: number; itemsPerPage?: number; search?: string;
  order_field?: string; order_type?: "ASC" | "DESC";
  [filter: `eq-${string}` | `notEq-${string}` | `min-${string}` | `max-${string}` | string]: unknown;
};

export type AuthMode = "system" | "company" | "company_support";
export type AuthContext = {
  token?: string;
  user: User;
  role: { id: number; name: RoleName; display_name: string } | null;
  permissions: string[];
  auth_mode: AuthMode;
  company_access?: {
    access_type: "support" | "company_user";
    company: Company;
    issued_by: { id: number; full_name: string };
    permissions: string[];
    expires_at: string | null;
  };
};

export type Status = "active" | "inactive";
export type UserStatus = Status | "blocked";
export type RoleName = "superAdmin" | "admin" | "companyManager" | "user";
export type WaybillStatus = "incomplete" | "referral" | "completed" | "canceled";
export type NumberRangeStatus = Status | "completed";
export type FleetOwnershipType = "owned" | "leased" | "free" | "unknown";
export type ContractItemType = "owned" | "rental" | "free" | "unknown";
export type NotificationType = "success" | "error" | "warning" | "info";
export type ContractItemName =
  | "base_freight" | "loading_cost" | "weighbridge_cost" | "warehousing"
  | "unloading_cost" | "commission" | "excess_tonnage" | "insurance_premium"
  | "insurance_vat" | "advance_freight" | "detention";

export type TransportContractItem = {
  id?: number; name: ContractItemName; name_label?: string; field_name?: string;
  is_owned: boolean; is_rental: boolean; is_free: boolean; is_unknown: boolean;
  charge_recipient: boolean; primary_value: number | null; secondary_value: number | null;
  editable_fields?: Record<"is_owned" | "is_rental" | "is_free" | "is_unknown" | "charge_recipient" | "primary_value" | "secondary_value", boolean>;
};

export type TransportContract = {
  id: number; company_id: number; title: string; contract_number: string;
  contract_date: string; customer_name: string; status: Status; is_public: boolean;
  default_owned: boolean; default_rental: boolean; default_free: boolean; default_unknown: boolean;
  description: string | null; user_ids?: number[]; items?: TransportContractItem[];
};
```

### 10.3 3. API client rules

1. One `fetch` wrapper: prefix `API_BASE`, add `Authorization` and `Accept: application/json`, parse JSON, throw `ApiError` on `!ok`.
2. On 401, clear the session and redirect to login. On 403, show `message` and keep the page.
3. On 422, map `errors` to form fields by dot path (`cargos.0.cargo_id` → `cargos[0].cargo_id`).
4. Use `multipart/form-data` only for user and driver create/update with images. Send booleans as `1`/`0` there.
5. Keep server state in TanStack Query, keyed by `[path, query]`. Invalidate the list and detail keys after any mutation.

### 10.4 4. Rules to give the assistant verbatim

- Never invent fields: every request field and enum value is listed in this guide or `api.json`.
- Gate UI on route-name permissions (`user.waybills.store`); 403 is still possible.
- Waybill form: required fields depend on `status` (see the table above). Never send `status: "canceled"`; use the cancel endpoints. Treat `completed` and `canceled` waybills as read-only.
- Never send `referral_number`, `referral_serial`, `bijak_tracking_code` or `last_number` on update; the server owns them.
- `cargo_id` and `packaging_id` are catalog codes, and `cargo_ids` in cargo groups are codes too.
- A transport contract always has exactly 11 items. Build them from `GET /user/transport-contracts/options` and honour `editable_fields`.
- Setting a `default_*` flag moves it off other contracts, so refetch the contract list after saving.
- Fleets use `leased`; contract items use `rental`.
- All user-facing strings from the API are Persian; render RTL.

### 10.5 5. Keeping the two sides in sync

| Backend change | Frontend action |
| --- | --- |
| New or renamed route | Regenerate `api.json`; run `generate-permissions`; add the permission gate |
| New request field or rule | Regenerate types; update the form schema (zod) to match |
| New enum case | Regenerate; add the label or read it from the options endpoint |
| New transport-contract item | Nothing hard-coded should change if the form is built from `options` |
| Breaking response change | Bump the client, fix type errors flagged by `tsc` |

Update this guide when a rule changes that the spec cannot show.
