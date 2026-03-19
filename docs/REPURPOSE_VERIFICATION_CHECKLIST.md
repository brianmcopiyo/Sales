# FMCG Repurpose Verification Checklist

Use this checklist to validate end-to-end readiness after migrations and builds.

## 1) Backend API Validation
- [ ] `POST /api/login` works for direct login and OTP-required login.
- [ ] `POST /api/verify-otp` and `POST /api/resend-otp` return expected payloads.
- [ ] `GET /api/dashboard-summary` returns: `outlets_count`, `check_ins_today`, `check_ins_this_week`, `visited_outlets_today`, `coverage_today_percent`, `open_check_ins`.
- [ ] `POST /api/check-ins` records check-in and returns `check_in.id`.
- [ ] `POST /api/check-ins/{id}/check-out` records checkout and returns `check_out_at`.
- [ ] `POST /api/sync/check-ins` handles retries idempotently by `client_id` after `client_ref` migration.
- [ ] `GET/POST/DELETE /api/planned-visits` are reachable with auth token.
- [ ] `GET /api/dcr` returns planned vs unplanned rows.
- [ ] `GET /api/audit-templates`, `POST /api/audit-runs`, `POST /api/audit-runs/{id}/submit`, `GET /api/audit-reports` are reachable and return JSON.

## 2) Database Migration Validation
- [ ] Run migrations successfully, including:
  - [ ] `add_client_ref_to_check_ins_table`
  - [ ] `create_attendances_table`
  - [ ] `create_field_expenses_table`
  - [ ] `create_territories_and_visit_routes_tables`
  - [ ] `add_fmcg_execution_permissions`
- [ ] Confirm `check_ins` has unique `(user_id, client_ref)` constraint.
- [ ] Confirm admin/super_admin received newly inserted permission slugs.

## 3) com.taja.app Validation
- [ ] Login/OTP lands on distribution dashboard.
- [ ] Dashboard shows KPI cards and refreshes after sync.
- [ ] User/branch info refreshes from `/api/user`.
- [ ] Outlets list loads and check-in sheet opens.
- [ ] Check-in supports optional notes + optional photo.
- [ ] Offline check-in saves to queue and later syncs online.

## 4) com.taja.outlet Validation
- [ ] Login/OTP lands on map-first screen.
- [ ] Map loads outlets with coordinates as markers.
- [ ] Marker click opens outlet detail.
- [ ] List screen supports filter input and navigation from bottom nav.
- [ ] Add/edit/geofence flows still work and return to list/map correctly.

## 5) Blade/Web Validation
- [ ] `distribution-dashboard` and check-ins pages remain accessible.
- [ ] Planned visits, DCR, and audit pages remain functional.
- [ ] New attendance pages:
  - [ ] `GET /attendance`
  - [ ] `GET /attendance/create`
  - [ ] `POST /attendance`
- [ ] New field expense pages:
  - [ ] `GET /field-expenses`
  - [ ] `GET /field-expenses/create`
  - [ ] `POST /field-expenses`

## 6) Regression Safety
- [ ] No PHP syntax errors across changed controllers/routes/migrations/models.
- [ ] Android lint/build passes for both modules.
- [ ] No breaking navigation loops on login/logout/OTP flows.
