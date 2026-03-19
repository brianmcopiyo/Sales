# FMCG Repurpose Gap Matrix

This matrix tracks actual implementation status against `Taja App/REPURPOSE_PLAN.md`.

## Legend
- `Done`: implemented and wired.
- `Partial`: exists but missing key behavior or parity.
- `Missing`: not implemented.

## Backend API

| Capability | Status | Evidence | Gap |
|---|---|---|---|
| Login + OTP | Done | `routes/api.php`, `AuthApiController.php` | None |
| User profile payload | Done | `GET /api/user` in `routes/api.php` | None |
| Outlets CRUD + geofence fields | Done | `OutletApiController.php` | None |
| Check-in create | Done | `CheckInApiController::store` | None |
| Check-out flow | Missing | `check_out_at` in `check_ins` schema but no API route/method | Add route + controller method |
| Offline sync for check-ins | Partial | `SyncApiController::syncCheckIns` | No idempotency/duplicate protection by `client_id` |
| Dashboard summary KPIs | Partial | `DashboardApiController::summary` | Missing richer execution KPIs |
| Planned visits mobile API | Missing | Present in web routes only | Add API controller/routes |
| DCR mobile API | Missing | Present in web routes only | Add API controller/routes |
| Audits mobile API | Missing | Present in web routes only | Add API controller/routes |

## Backend Data Model

| Capability | Status | Evidence | Gap |
|---|---|---|---|
| Outlets + geofence schema | Done | `create_outlets_table` + geofence fields | None |
| Check-in schema | Done | `2026_03_25_000002_create_check_ins_table.php` | None |
| Sync idempotency persistence | Missing | No unique/indexed `client_id` on `check_ins` | Add migration and use in sync logic |
| Planned visits schema | Done | `create_planned_visits_table` exists | API parity missing |
| Attendance domain | Missing | No attendance table/controller | Add migrations/models/controllers/routes |
| Field expense domain | Missing | No dedicated field expenses table/controller | Add migrations/models/controllers/routes |
| Route/territory primitives | Missing | No dedicated territory/route entities | Add core tables/models |
| Audit entities | Done | Web audits are operational | Add API surface and mobile usage |

## com.taja.app (distribution app)

| Capability | Status | Evidence | Gap |
|---|---|---|---|
| Login/OTP flow | Done | `LoginActivity.kt`, `OtpActivity.kt` | None |
| Dashboard distribution KPIs | Partial | `DashboardActivity.kt` + `ApiClient.getDashboardSummary` | Needs richer KPI mapping if backend extended |
| Outlets list + check-in | Done | `OutletsListActivity.kt` | None |
| Offline queue + sync | Partial | `CheckInQueue.kt`, sync route usage | Needs idempotent server support and stronger UX states |
| Optional photo on check-in | Partial | `ApiClient.createCheckIn` supports `photoFile` | Confirm UI wiring and camera/file capture flow |
| Distribution-only API surface | Partial | `ApiClient.kt` still includes restock/stock-take sections | Remove legacy sections fully |
| Profile branch/user correctness | Partial | session values shown but not always refreshed from `/api/user` | Normalize post-login hydration |

## com.taja.outlet (mapping app)

| Capability | Status | Evidence | Gap |
|---|---|---|---|
| Login/OTP | Done | `LoginActivity.kt`, `OtpActivity.kt` | None |
| Outlet list/form/detail | Done | `OutletListActivity.kt`, `OutletFormActivity.kt`, `OutletDetailActivity.kt` | None |
| Geo-fence editing (none/radius/polygon) | Done | `OutletGeoFenceActivity.kt` | None |
| Map-first launcher behavior | Partial | Has `OutletMapActivity.kt` but login currently lands on list | Route login/OTP to map-first |
| Live map markers + interactions | Missing | `OutletMapActivity.kt` has only back/list buttons | Implement map loading/markers/select actions |
| List filters | Missing | No strong filtering controls in list workflow | Add filter UI + query behavior |
| Bottom nav behavior parity | Partial | nav resources exist, wiring incomplete | Implement navigation handlers consistently |

## Web/Blade Operations

| Capability | Status | Evidence | Gap |
|---|---|---|---|
| Distribution dashboard | Done | `DistributionDashboardController.php`, `resources/views/distribution/dashboard.blade.php` | None |
| Check-ins list | Done | `CheckInController.php`, `resources/views/check-ins` | None |
| Planned visits web | Done | `PlannedVisitController.php`, `resources/views/planned-visits` | None |
| DCR web | Done | `DcrController.php`, `resources/views/dcr` | None |
| Audit web | Done | `Audit*Controller.php`, audit views | None |
| Attendance/expense web | Missing | No dedicated module routes/views | Add minimal manager surfaces |

## Execution Order (from this baseline)
1. Backend P0 API parity (checkout, planned visits, DCR, audits) + sync idempotency + dashboard KPI extension.
2. DB/model/permission alignment for new API domains.
3. `com.taja.app` finalize distribution-only and clean legacy API surface.
4. `com.taja.outlet` implement live map workflow + map-first landing + filters/nav.
5. Web parity and end-to-end verification checklist.
