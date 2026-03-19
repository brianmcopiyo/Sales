# Taja App — Manual testing guide

Use this guide to run the test scenarios from **REPURPOSE_PLAN.md** (#33–#36). When each scenario is executed and passes, mark it in the **Sign-off** table below and update the plan status table.

**Prerequisites**

- Backend Laravel API running (e.g. `php artisan serve`); base URL matches app `API_BASE_URL` (distribution app and outlet app `build.gradle` / BuildConfig).
- Device or emulator with network (and ability to turn off for #34); location permission for check-in.
- Built APKs: **com.taja.app** (distribution) and **com.taja.outlet** (outlet mapping), or run from Android Studio.

---

## #33 — Distribution app: Login → Dashboard → Outlets → Check-in → Profile

| Step | Action | Expected |
|------|--------|----------|
| 1 | Open **Taja** (com.taja.app). Enter email/phone + password, tap Login. | If backend returns OTP required: OTP screen appears. Else: Dashboard. |
| 2 | If on OTP screen: enter 6-digit code, tap Verify. | Dashboard with distribution KPIs (outlets, visits/check-ins, coverage). |
| 3 | Tap **Outlets** (bottom nav or tab). | Outlets list loads from API; items show name, code, address. |
| 4 | Tap one outlet. | Check-in bottom sheet opens (outlet name, location, optional photo, notes). |
| 5 | Allow location if prompted; optionally add notes. Tap Submit. | Toast/confirmation; check-in sent (or queued if offline). |
| 6 | Open **Profile** (bottom nav). | User name (and branch if API provides it). Logout works. |

**Sign-off:** ☐ Passed ___________ (date)

---

## #34 — Offline check-in → sync when online

| Step | Action | Expected |
|------|--------|----------|
| 1 | Turn off device network (airplane mode or Wi‑Fi/mobile off). | — |
| 2 | Open distribution app → Outlets → tap outlet → submit check-in. | Message that check-in is queued / saved for sync. |
| 3 | Turn network back on. | — |
| 4 | Open Dashboard or pull-to-refresh / trigger sync. | Toast like “X check-in(s) synced”; dashboard KPIs update. |

**Sign-off:** ☐ Passed ___________ (date)

---

## #35 — com.taja.outlet: Login → map → CRUD outlets

| Step | Action | Expected |
|------|--------|----------|
| 1 | Open **Taja Outlet** (com.taja.outlet). Login (email/phone + password). | If OTP: complete OTP → Map screen. Else: Map screen. |
| 2 | Tap **Outlets** (or “List”) on map screen. | Outlet list loads; FAB “Add outlet” visible. |
| 3 | Tap FAB “Add outlet”. Fill Name (required), optional Code and Address. Save. | “Outlet saved” (or similar); list shows new outlet. |
| 4 | Tap an outlet in the list. | Form opens in edit mode with name, code, address. |
| 5 | Change name or address, Save. | “Outlet saved”; list shows updated data. |

**Sign-off:** ☐ Passed ___________ (date)

---

## #36 — Backend Blade: distribution dashboard and check-ins list

**URLs (after login):** `/distribution-dashboard` and `/check-ins`. User needs permission `outlets.view`, `checkins.view`, or `distribution.reports` for the distribution dashboard.

| Step | Action | Expected |
|------|--------|----------|
| 1 | In browser, log in to Laravel web (e.g. `/login`). | — |
| 2 | Open **Distribution dashboard:** `/distribution-dashboard`. | Page shows outlets count, check-ins today, check-ins this week, outlets visited this week, coverage %. |
| 3 | Open **Check-ins list:** `/check-ins`. Use filters (user, outlet, date from/to) if present. | Paginated list of check-ins with user, outlet, date; filters work. |

**Sign-off:** ☐ Passed ___________ (date)

---

## Sign-off summary

| # | Scenario | Passed (date) |
|---|----------|---------------|
| 33 | Distribution app: Login → Dashboard → Outlets → Check-in → Profile | |
| 34 | Offline check-in → sync when online | |
| 35 | com.taja.outlet: login → map → CRUD outlets | |
| 36 | Backend Blade: distribution dashboard and check-ins list | |

When a row is passed, update **REPURPOSE_PLAN.md** status table: set that row’s **Already have** to ✅ and clear **To do**.
