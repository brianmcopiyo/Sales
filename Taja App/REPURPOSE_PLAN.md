# Taja App Repurpose Plan — FMCG Distribution & Outlet Mapping

This document contains: **(1)** deep research on FMCG distribution app best practices with findings per site (60+ sources), **(2)** what we are implementing based on those results, and **(3)** an updated repurpose plan that **keeps and refactors the dashboard** for distribution-based data and defines **two packages**: **com.taja.app** (distribution app) and **com.taja.outlet** (outlet mapping app).

---

# PRODUCT DIRECTIVE — BACKEND ALIGNMENT TO FINDINGS

**If our research findings describe a function or capability that our backend does not yet implement, we add it.**

- **Scope:** Every capability cited in **PART A (Findings per site)** and **PART B (What we are implementing)** that implies a backend API, service, or data model must exist on our backend. If it does not, it is added to the backlog and implemented so the app can deliver that behaviour.
- **Goal:** Build a **superior product in the market** by closing gaps between industry best practices (from 70+ sources) and our implementation. The backend is the single source of truth; the apps (com.taja.app, com.taja.outlet) consume it. Missing backend features cap the product; we do not leave them missing by default.
- **Process:** When implementing an “Implementing” item from the findings, check whether the backend already supports it (endpoint, response shape, validation, permissions). If not, add the backend work (new or extended endpoint, migration, service) before or in parallel with the app work. Document new backend requirements in this plan or in the backend repo so nothing is skipped.

**Examples:** OTP (verify-otp, resend-otp) is in findings and not yet in backend → add it. Dashboard summary (outlet count, check-in counts) is needed for distribution KPIs → add endpoint or extend existing. Geo-fence on outlets for check-in validation → ensure backend supports it (we have GeoFenceService). Secondary sales, beat/route, or TPM in a later phase → when we adopt those findings, backend gets the corresponding APIs first.

---

# STATUS OVERVIEW — WHAT WE HAVE, IN PROGRESS, AND TO DO

Use this table to track progress. Update the **Status** column as work moves from **To do** → **In progress** → **Done**.

- **Backend** = Laravel API (PHP) + **Blade** views (web admin). Paths: `routes/api.php`, `app/Http/Controllers/Api/`, `resources/views/`.
- **Frontend** = Android **XML** layouts + Kotlin (com.taja.app / com.taja.outlet). Paths: `app/src/main/res/layout/*.xml`, `res/values/strings.xml`, `app/src/main/java/`.

| # | Area | Item | Already have | In progress | To do |
|---|------|------|:------------:|:-----------:|:-----:|
| 1 | Backend API | Login (email/phone + password → token + user) | ✅ | | |
| 2 | Backend API | GET /api/user | ✅ | | |
| 3 | Backend API | Outlets CRUD (GET/POST/PUT /api/outlets) | ✅ | | |
| 4 | Backend API | POST /api/check-ins (outlet_id, lat, lng, photo, notes) | ✅ | | |
| 5 | Backend API | POST /api/sync/check-ins (offline queue) | ✅ | | |
| 6 | Backend API | GeoFenceService (validate check-in location) | ✅ | | |
| 7 | Backend API | OTP: login may return requires_otp + pending_token; POST verify-otp, resend-otp | ✅ | | |
| 8 | Backend API | Dashboard summary: GET endpoint (outlet count, check-in counts today/week) for app | ✅ | | |
| 9 | Backend API | GET /api/user response: include branch_id, phone for app profile/dashboard | ✅ | | |
| 10 | Backend API | Outlets CRUD: request/response support geo_fence_type, geo_fence_radius_metres, geo_fence_polygon | ✅ | | |
| 11 | Backend Blade | Distribution dashboard (Blade): web view of outlets count, check-ins summary, coverage | ✅ | | |
| 12 | Backend Blade | Check-ins list (Blade): web view of check-ins by user/outlet/date for managers | ✅ | | |
| 13 | Backend Blade | Outlets index/edit (Blade): ensure outlet forms support geo_fence fields for mapping | ✅ | | |
| 14 | Frontend XML | activity_login.xml, activity_otp.xml, activity_profile.xml, bottomsheet_confirm.xml | ✅ | | |
| 15 | Frontend XML | activity_dashboard.xml: refactor to distribution KPI cards + bottom nav (Dashboard, Outlets, Profile) | ✅ | | |
| 16 | Frontend XML | activity_outlets_list.xml (new): outlets list layout, toolbar, bottom nav include | ✅ | | |
| 17 | Frontend XML | item_outlet.xml (new): single outlet row/card for list | ✅ | | |
| 18 | Frontend XML | bottomsheet_checkin.xml (new): check-in form (outlet name, location, photo, notes, submit/cancel) | ✅ | | |
| 19 | Frontend XML | bottom_navigation_include.xml: only Outlets + Profile (update) | ✅ | | |
| 20 | Frontend XML | strings.xml: add distribution/outlet/check-in strings; remove restock/stock-take/IMEI | ✅ | | |
| 21 | Frontend Kotlin | LoginActivity, OtpActivity, SessionManager, Profile, Dashboard (current) | ✅ | | |
| 22 | Frontend Kotlin | ApiClient: login + verifyOtp + resendOtp; getUser, getOutlets, createCheckIn, syncCheckIns; remove restock/stock-take | ✅ | | |
| 23 | Frontend Kotlin | DashboardActivity: refactor to distribution KPIs (outlets, visits, check-ins, coverage) | ✅ | | |
| 24 | Frontend Kotlin | OutletsListActivity.kt (new): load outlets, list, tap → check-in bottom sheet | ✅ | | |
| 25 | Frontend Kotlin | Check-in flow in OutletsListActivity (BottomSheetDialog + bottomsheet_checkin.xml) | ✅ | | |
| 26 | Frontend Kotlin | Offline check-in queue + sync when online | ✅ | | |
| 27 | Frontend Kotlin | Remove PendingOrders, RestockWizard, StockTake×3, ScanImei, ReviewScannedImeis + their XML | ✅ | | |
| 28 | Frontend Kotlin | AndroidManifest: remove 7 activities, add OutletsListActivity; location permission | ✅ | | |
| 29 | Frontend Kotlin | build.gradle: location, camera if needed; remove Maps/ML Kit/CameraX if unused | ✅ | | |
| 30 | Frontend XML | activity_outlet_map.xml, activity_outlet_list.xml, activity_outlet_form.xml (outlet module) | ✅ | | |
| 31 | Frontend XML | activity_login.xml, activity_otp.xml, strings.xml (outlet module, copy/adapt from app) | ✅ | | |
| 32 | Frontend Kotlin | New module: Login, Otp, OutletMapActivity, OutletListActivity, OutletFormActivity, ApiClient, SessionManager | ✅ | | |
| 33 | Testing | Login (with and without OTP) → Dashboard → Outlets → Check-in → Profile | ✅ | | |
| 34 | Testing | Offline check-in → sync when online | ✅ | | |
| 35 | Testing | com.taja.outlet: login → map → CRUD outlets | ✅ | | |
| 36 | Testing | Backend Blade: distribution dashboard and check-ins list in browser | ✅ | | |

**Legend**

- **Already have:** Implemented and in place.
- **In progress:** Currently being worked on (move items here when you start; move to **Already have** when done).
- **To do:** Not started; plan to do (☐ = checkbox to tick when scheduled or done).

**Quick counts (update as you progress)**

| Status | Count |
|--------|-------|
| **Already have** | 36 |
| **In progress** | 0 |
| **To do** | 0 |

**How to use**

1. When you start an item, add a note in **In progress** (e.g. “Refactoring”) or move the row to an “In progress” section if you prefer.
2. When an item is done, mark **Already have** and clear **To do** for that row.
3. Update the **Quick counts** above as items move from To do → In progress → Already have.
4. Optionally duplicate this table into a separate “sprint” or “weekly” view and only list items you’re targeting in that period.

---

# FILES: REMOVE / UPDATE / ADD

All paths below are relative to the **Taja App** project root (e.g. `Taja App/app/...`). **com.taja.app** = distribution app (current project). **com.taja.outlet** = new app (new module or repo).

---

## Files to REMOVE (com.taja.app)

These files are no longer needed and should be **deleted**.

### Kotlin (activities)

| # | Path |
|---|------|
| 1 | `app/src/main/java/com/taja/app/activity/PendingOrdersActivity.kt` |
| 2 | `app/src/main/java/com/taja/app/activity/RestockWizardActivity.kt` |
| 3 | `app/src/main/java/com/taja/app/activity/StockTakeListActivity.kt` |
| 4 | `app/src/main/java/com/taja/app/activity/StockTakeCreateActivity.kt` |
| 5 | `app/src/main/java/com/taja/app/activity/StockTakeEditActivity.kt` |
| 6 | `app/src/main/java/com/taja/app/activity/ScanImeiActivity.kt` |
| 7 | `app/src/main/java/com/taja/app/activity/ReviewScannedImeisActivity.kt` |

### Layouts (XML)

| # | Path |
|---|------|
| 8 | `app/src/main/res/layout/activity_pending_orders.xml` |
| 9 | `app/src/main/res/layout/item_pending_order.xml` |
| 10 | `app/src/main/res/layout/activity_restock_wizard.xml` |
| 11 | `app/src/main/res/layout/item_restock_product_row.xml` |
| 12 | `app/src/main/res/layout/activity_stocktake_list.xml` |
| 13 | `app/src/main/res/layout/activity_stocktake_create.xml` |
| 14 | `app/src/main/res/layout/activity_stocktake_edit.xml` |
| 15 | `app/src/main/res/layout/item_stocktake_row.xml` |
| 16 | `app/src/main/res/layout/item_stocktake_create_product.xml` |
| 17 | `app/src/main/res/layout/item_stocktake_edit_row.xml` |
| 18 | `app/src/main/res/layout/item_stocktake_sheet_row.xml` |
| 19 | `app/src/main/res/layout/bottomsheet_stocktake_detail.xml` |
| 20 | `app/src/main/res/layout/bottomsheet_stocktake_count_item.xml` |
| 21 | `app/src/main/res/layout/bottomsheet_stocktake_cancel.xml` |
| 22 | `app/src/main/res/layout/activity_scan_imei.xml` |
| 23 | `app/src/main/res/layout/activity_review_imei.xml` |
| 24 | `app/src/main/res/layout/bottomsheet_receive_stock.xml` |
| 25 | `app/src/main/res/layout/bottomsheet_edit_quantity.xml` |
| 26 | `app/src/main/res/layout/bottomsheet_reject_order.xml` |
| 27 | `app/src/main/res/layout/bottomsheet_partial_receive.xml` |

**Total to remove:** 7 Kotlin + 20 layouts = **27 files**.

### Bottom sheet to KEEP (do not remove)

We keep **at least one bottom sheet** for design consistency and reuse across the app.

| Path | Purpose |
|------|--------|
| `app/src/main/res/layout/bottomsheet_confirm.xml` | **KEEP.** Generic confirm dialog (title, message, cancel, ok). Used by LoginActivity (forgot password), ProfileActivity (logout). Reuse this layout and `BottomSheetDialog` + `R.style.AppBottomSheetDialogTheme` for any new confirmations or simple forms (e.g. check-in can use a bottom sheet that follows this design). |

Use this same bottom sheet pattern (and style) when adding new flows (e.g. `bottomsheet_checkin.xml`) so the app keeps a consistent bottom sheet design.

---

## Files to UPDATE (com.taja.app)

These files **stay** but must be **edited** (refactor, change behaviour, or trim).

| # | Path | What to do |
|---|------|------------|
| 1 | `app/src/main/java/com/taja/app/ApiClient.kt` | Keep login, verifyOtp, resendOtp. Remove dashboard/restock/stock-take methods. Add getUser, getOutlets, getOutlet, createOutlet, updateOutlet, createCheckIn, syncCheckIns + data classes (User, Outlet, CheckIn, sync request/response). |
| 2 | `app/src/main/java/com/taja/app/activity/DashboardActivity.kt` | Refactor to load distribution KPIs (outlet count, check-ins, coverage). Replace old stats with new cards. Navigate to OutletsList instead of PendingOrders/StockTake. |
| 3 | `app/src/main/java/com/taja/app/activity/LoginActivity.kt` | After login success start DashboardActivity (already). Ensure no references to removed activities. |
| 4 | `app/src/main/java/com/taja/app/activity/OtpActivity.kt` | After verify success start DashboardActivity (already). Ensure no references to removed activities. |
| 5 | `app/src/main/java/com/taja/app/activity/ProfileActivity.kt` | Bottom nav: only Dashboard (or Outlets) + Profile. Remove intents to PendingOrders, StockTakeList. Optional: hide branch if not in API. |
| 6 | `app/src/main/res/layout/activity_dashboard.xml` | Replace old stat cards with distribution KPI cards (outlets, visits, check-ins, coverage). Bottom nav: Dashboard, Outlets, Profile only. |
| 7 | `app/src/main/res/layout/bottom_navigation_include.xml` | Keep only 2 items: Outlets (or Home), Profile. Remove nav_stock, nav_stock_takes, nav_dashboard if replaced by single Home. |
| 8 | `app/src/main/res/layout/activity_profile.xml` | Optional: hide branch block if not in API. Ensure bottom nav matches dashboard. |
| 9 | `app/src/main/AndroidManifest.xml` | Remove activity for PendingOrders, RestockWizard, StockTakes, ScanImei, ReviewScannedImeis. Add activity for OutletsListActivity and CheckInActivity (or CheckIn as dialog). Add location permission if needed. |
| 10 | `app/src/main/res/values/strings.xml` | Remove strings for restock, stock take, IMEI, receive, edit quantity, approve, reject. Add strings for distribution dashboard, outlets list, check-in (e.g. outlets_title, check_in_button, location_required, notes_hint). |
| 11 | `app/build.gradle` | Add ACCESS_FINE_LOCATION if needed for check-in. Add camera if check-in photo required. Remove Play Services Maps, ML Kit, CameraX if no longer used. |

**Total to update:** **11 files**.

---

## Files to ADD (com.taja.app)

New files to **create** for the distribution app.

| # | Path | Purpose |
|---|------|--------|
| 1 | `app/src/main/java/com/taja/app/activity/OutletsListActivity.kt` | Screen that loads outlets from GET /api/outlets, shows list; tap outlet to open check-in flow. Bottom nav: Dashboard, Outlets, Profile. |
| 2 | `app/src/main/res/layout/activity_outlets_list.xml` | Layout for outlets list (e.g. RecyclerView or ScrollView of cards), toolbar/title, bottom nav include. |
| 3 | `app/src/main/res/layout/item_outlet.xml` | Single row/card layout for one outlet (name, code, address snippet; tap to check-in). |
| 4 | `app/src/main/java/com/taja/app/activity/CheckInActivity.kt` | Optional: dedicated activity for check-in. **Preferred:** use a bottom sheet from OutletsListActivity to keep the bottom sheet design pattern. |
| 5 | `app/src/main/res/layout/bottomsheet_checkin.xml` | **Use bottom sheet layout** (same design pattern as `bottomsheet_confirm.xml`): title, outlet name, location, optional photo, notes, submit/cancel. Use `BottomSheetDialog` with `R.style.AppBottomSheetDialogTheme` for consistency. |

**Total to add (com.taja.app):** **5 files** (or 4 if check-in is only a bottom sheet with no new activity). **Recommendation:** implement check-in as a bottom sheet (`bottomsheet_checkin.xml`) so we keep and extend the existing bottom sheet design.

---

## Files to ADD (com.taja.outlet — new package)

New **Android application module** (or separate repo) with package **com.taja.outlet**. These are the main files to add for the outlet mapping app.

| # | Path (relative to outlet module) | Purpose |
|---|-----------------------------------|--------|
| 1 | `app/src/main/java/com/taja/outlet/MainActivity.kt` or `OutletMapActivity.kt` | Launcher: map + list of outlets (after login). |
| 2 | `app/src/main/java/com/taja/outlet/LoginActivity.kt` | Login (reuse flow from com.taja.app; same API). |
| 3 | `app/src/main/java/com/taja/outlet/OtpActivity.kt` | OTP verify/resend (reuse flow). |
| 4 | `app/src/main/java/com/taja/outlet/OutletListActivity.kt` | List outlets; filter; tap to edit or show on map. |
| 5 | `app/src/main/java/com/taja/outlet/OutletFormActivity.kt` or fragment | Create/Edit outlet: name, code, lat, lng, address, geo_fence (when backend supports). |
| 6 | `app/src/main/java/com/taja/outlet/ApiClient.kt` or shared module | Same backend: login, user, outlets CRUD. |
| 7 | `app/src/main/java/com/taja/outlet/SessionManager.kt` | Token and user (same as distribution app). |
| 8 | `app/src/main/res/layout/activity_outlet_map.xml` | Map view (e.g. MapFragment) with outlet markers. |
| 9 | `app/src/main/res/layout/activity_outlet_list.xml` | List of outlets for mapping app. |
| 10 | `app/src/main/res/layout/activity_outlet_form.xml` | Form for create/edit outlet. |
| 11 | `app/src/main/res/layout/activity_login.xml` | Login screen (copy/adapt from com.taja.app). |
| 12 | `app/src/main/res/layout/activity_otp.xml` | OTP screen (copy/adapt from com.taja.app). |
| 13 | `app/src/main/AndroidManifest.xml` | Application, activities, permissions (network, location). |
| 14 | `app/build.gradle` | Dependencies (maps if needed, networking, AndroidX). |
| 15 | `app/src/main/res/values/strings.xml` | Strings for outlet mapping app. |

**Total to add (com.taja.outlet):** New module with **15+ files** (exact count depends on whether you use fragments or activities and shared auth module).

---

## Summary

| Action | com.taja.app | com.taja.outlet |
|--------|--------------|-----------------|
| **Remove** | 27 files (7 Kotlin, 20 layouts) | — |
| **Update** | 11 files | — |
| **Add** | 5 files (outlets list + check-in) | New module, 15+ files |
| **Keep (design)** | **1 bottom sheet:** `bottomsheet_confirm.xml` — do not remove; reuse for logout, forgot password, and as the design reference for `bottomsheet_checkin.xml`. | — |

---

# PART A — DEEP RESEARCH: FINDINGS PER SITE

Research covered FMCG/CPG distribution, sales force automation (SFA), distributor management (DMS), dashboards, outlet mapping, geo-fencing, and security. Below are findings per company/site and the practices we are adopting.

---

## 1. Bizzfield (bizzfield.com)

- **Finding:** FMCG field sales app should include real-time order booking (much faster than manual), GPS tracking and beat planning, mobile-first design with offline, and live dashboards with real-time analytics.
- **Implementing:** Offline-capable distribution flows; live distribution-focused dashboard; prepare for beat/route concepts in outlet mapping.

---

## 2. FieldAssist (fieldassist.com) — multiple pages

- **Finding:** SFA for FMCG: real-time field and sales tracking, smart route optimization, geo-tagged check-ins (productivity gains ~21%), attendance and expense management, SKU-level dashboards, gamification. Top 7 secondary-sales KPIs; dashboards must link metrics to execution; 6 FMCG sales metrics; retail excellence metrics; attendance systems critical; expense management (32% faster reimbursement, 30% lower leakage). DMS 2025 guide: order/inventory, payment, returns, field mobility, GST, analytics, schemes.
- **Implementing:** Distribution dashboard with execution-oriented KPIs (coverage, call frequency, fill rate, productive calls); OTP and geo-tagged check-ins; keep attendance/expense in scope for future.

---

## 3. BeatRoute (beatroute.io) — multiple pages

- **Finding:** Goal-driven AI for FMCG: visit planning, beat optimization, order recommendations, GPS coverage, visual merchandising, stock audits, payment collection, real-time KPI dashboards. Beat planning guide: structured outlet visit schedule by store type and potential; AI improves beat adherence 15–30%, travel time reduction 25–35%. Secondary sales tracking; SFA for FMCG.
- **Implementing:** Dashboard shows distribution KPIs (outlet coverage, visits, performance); outlet list and check-ins feed into “visit” and “coverage” metrics; design for future beat/route in **com.taja.outlet**.

---

## 4. Delta Sales App (deltasalesapp.com) — multiple pages

- **Finding:** FMCG sales software: real-time dashboards, van sales, distributor management. Dashboards should drive execution (not just “what happened”). Top 10 DMS features: order processing, inventory, payment/credit, returns, mobile beat + GPS, billing/GST, analytics, schemes, ERP sync. Secondary sales automation; offline sync; HUL/Nestlé-style field sales (rural, van sales, secondary sales). Outlet–distributor–sales rep tracking.
- **Implementing:** Refactor dashboard to distribution metrics (outlets, check-ins, coverage); offline check-in queue and sync; outlet and visit tracking as foundation for secondary sales later.

---

## 5. Ivy Mobility (ivymobility.com)

- **Finding:** AI route-to-market for CPG: SFA, Cloud DMS, AI SKU recommendations, shelf audits (95–97% accuracy), AR for assets. Serves Nestlé, P&G, Mars, L’Oréal. Retail execution: image recognition, planogram, share-of-shelf; 3–5% same-store sales lift, 32% efficiency, 35% time savings vs manual.
- **Implementing:** Dashboard as single pane for “distribution execution” (outlets, visits, check-ins); keep design ready for future photo/audit features in distribution app.

---

## 6. Sellin / GoSellin (gosellin.com)

- **Finding:** Top 7 secondary sales KPIs (volume, retailer coverage/penetration, distributor KPIs, order vs execution, inventory). Primary vs secondary gap: secondary reveals real demand; 30% lost sales recoverable with secondary tracking. Real-time dashboards improve visibility and team performance.
- **Implementing:** Dashboard KPIs aligned to distribution: outlet count, visits/check-ins, coverage; structure so secondary sales can be added when backend supports it.

---

## 7. SalesCode (salescode.ai)

- **Finding:** Integrated SFA + DMS + eB2B for CPG; AI order prediction, outlet attrition, stock-out alerts. Used by Coca-Cola, ITC, Mondelez, P&G, PepsiCo (65+ brands, 2M+ users). NextGen DMS: no dedicated operator, modern UI; SFA app with AI targets and incentives.
- **Implementing:** Clean separation: **com.taja.app** = field distribution (login, dashboard, outlets, check-ins); **com.taja.outlet** = mapping/outlet management; dashboard shows “distribution health” metrics we can later enrich with AI/insights.

---

## 8. Coca-Cola / Spring Global (springglobal.com)

- **Finding:** Coca-Cola bottlers use Spring Global field tools: ~90 min saved per rep/day, 50% less training time, 30% better order accuracy, 3-month deployment.
- **Implementing:** Simple, fast flows in distribution app (login → dashboard → outlets → check-in); OTP and minimal training surface.

---

## 9. Unilever / Mobisoft (mobisoft.co)

- **Finding:** Unilever uses mobile sales and distribution (van sales, retail execution), integrated with SAP; real-time decisions for trade marketing and merchandising.
- **Implementing:** API-first design so distribution app can later plug into ERP/backend; dashboard reflects “today’s” distribution activity.

---

## 10. PepUpSales (pepupsales.com)

- **Finding:** AI-driven SFA and DMS; unified field operations, route optimization, order booking, real-time team monitoring, distributor stock visibility.
- **Implementing:** Dashboard as central place for “my outlets” and “my check-ins/visits”; real-time feel via pull-to-refresh and clear summaries.

---

## 11. SAP (sap.com)

- **Finding:** Wholesale distribution: unified warehouse, e-commerce, real-time supply chain planning, demand forecasting, inventory optimization; S/4HANA for retail/CPG/FMCG.
- **Implementing:** Dashboard metrics that could later map to SAP-friendly concepts (outlets, visits, coverage); no direct SAP dependency in v1.

---

## 12. Salesforce (salesforce.com)

- **Finding:** Consumer Goods Cloud: sales tools with pricing/inventory/margin; trade promotion management (TPM); retail execution consoles; omni-channel execution.
- **Implementing:** Keep user and outlet data model clear so TPM or CRM integration is possible later; dashboard stays distribution-focused.

---

## 13. Oracle (oracle.com)

- **Finding:** CPG cloud: supply chain, order and warehouse management, demand planning; Gartner leader in WMS.
- **Implementing:** Dashboard and APIs structured so order/fulfillment metrics can be added when backend extends.

---

## 14. Deloitte (deloitte.com)

- **Finding:** From aisles to algorithms: automation and vertical integration of supply chains; AI for cost and efficiency; digital client relationships.
- **Implementing:** Distribution app as “digital client relationship” layer (outlets, check-ins); dashboard as first analytics surface.

---

## 15. McKinsey (mckinsey.com)

- **Finding:** CPG lags in digital/AI; 6–10% revenue uplift and 3–5 pt EBITDA from digital/AI; “pilot purgatory” risk; rewiring full value chain from revenue management to logistics.
- **Implementing:** One clear scope: distribution (outlets + check-ins) and outlet mapping in two apps; avoid scope creep; dashboard shows value immediately.

---

## 16. BCG (bcg.com)

- **Finding:** Six actions: always-on portfolio, consumer-centric innovation, omnichannel execution, demand science. FMCG focus on winning in turbulent times.
- **Implementing:** Dashboard as “always-on” view of distribution (outlets, visits, coverage); extensible for omnichannel later.

---

## 17. Maersk (maersk.com)

- **Finding:** FMCG digital transformation and supply chain future-proofing; 75% see digital as priority but only 12% feel tech meets supply chain needs.
- **Implementing:** Prioritize reliability (offline sync, clear errors) so field trusts the app as core tool.

---

## 18. Nestlé / Plain Concepts (plainconcepts.com)

- **Finding:** Nestlé nShelf app: barcode/batch scanning, on-shelf availability, gamification. Rural distribution, van sales, secondary sales monitoring.
- **Implementing:** Check-in as “visit proof”; optional photo in check-in; future barcode/scan can be added in distribution app.

---

## 19. StayinFront (stayinfront.com)

- **Finding:** Consumer goods CRM and mobile field solutions for CPG.
- **Implementing:** Profile and user context in distribution app; outlet list as “accounts” for field user.

---

## 20. GoSpotCheck (gospotcheck.com)

- **Finding:** Retail execution for CPG/food/beverage; photo capture, audits, real-time data; ~97% product recognition; used by major CPG brands.
- **Implementing:** Optional photo on check-in (already in backend); dashboard can show “visits with photo” as quality metric later.

---

## 21. Channelplay (channelplay.in)

- **Finding:** Secondary sales tracking framework: DMS integration, SFA data entry, retailer self-report; four phases (assessment, technology, onboarding, adoption). Secondary = distributor-to-retailer movement.
- **Implementing:** Check-in and outlet data as foundation for secondary sales reporting once backend has DMS/secondary APIs.

---

## 22. OptCRM (optcrm.com)

- **Finding:** Secondary sales management: real-time distributor and retailer sales tracking.
- **Implementing:** Dashboard metrics that can later align to distributor/retailer tiers when backend supports.

---

## 23. Logiangle (logiangle.com)

- **Finding:** Real-time secondary sales tracking; delayed data is a key problem.
- **Implementing:** Near real-time dashboard (refresh after check-in, sync); offline queue to avoid data delay.

---

## 24. InetSoft (inetsoft.com)

- **Finding:** FMCG KPI dashboard: stock levels, delivery costs, fulfillment, shelf availability, brand performance.
- **Implementing:** Dashboard widgets: outlet count, visits (check-ins) today/this week, coverage (e.g. % outlets visited), simple “distribution health” view.

---

## 25. Bizinfograph (bizinfograph.com)

- **Finding:** FMCG financial dashboards: profitability, product portfolio, consolidated insights.
- **Implementing:** Keep dashboard extendable for revenue/profitability when backend exposes that data.

---

## 26. ZA Consulting (zaconsulting.in)

- **Finding:** 12 GTM metrics: outlet coverage efficacy, beat hygiene score, outlet conversion factor (OCF), weighted distribution, new outlet activation %, productive outlets, drop size, SKU repeat rate.
- **Implementing:** Dashboard: outlets covered, visit count, (later) OCF and productive outlets when we have order/primary data.

---

## 27. Theia (theia.eco)

- **Finding:** Geo-fencing in FMCG: visit compliance, automated check-in/out, retail audits at location, territory analytics, hyperlocal campaigns.
- **Implementing:** **com.taja.outlet**: outlet mapping with geo-fence; check-in in **com.taja.app** validates location against outlet geo-fence (backend already has GeoFenceService).

---

## 28. Geovision Group (geovisiongroup.com)

- **Finding:** Outlet classification: 10-20-30-40 rule (A/B/C by sales); data-driven models (location, size, demographics); AI to prioritize store visits.
- **Implementing:** Outlet model supports classification (e.g. type, size); **com.taja.outlet** for creating/editing outlets and optional classification; dashboard can show coverage by type later.

---

## 29. Proxima SFA (proximasfa.com)

- **Finding:** Retail execution software; AI trade promotion management; real-time ROI tracking.
- **Implementing:** Dashboard designed so “promo” or TPM metrics can be added when backend supports.

---

## 30. GS1 (gs1.org, gs1uk.org, gs1belu.org)

- **Finding:** FMCG data models (GDSN); product attributes, packaging, sustainability; GS1 UK–Nielsen collaboration for product data; sales uplift with standardized data.
- **Implementing:** Use stable IDs for outlets and users; keep data structures clear for future GS1/product alignment.

---

## 31. Nielsen Brandbank (via GS1)

- **Finding:** Collaboration with GS1 for product data in grocery; B2B/B2C data sharing.
- **Implementing:** No direct change in v1; outlet and product naming conventions can follow standards later.

---

## 32. Gartner (gartner.com) / RELEX / Oracle / Manhattan

- **Finding:** Supply chain planning, merchandise assortment, WMS Magic Quadrants; RELEX leader in planning; Oracle in assortment; Manhattan in WMS.
- **Implementing:** Dashboard and APIs stay modular so we can align to planning/assortment concepts when needed.

---

## 33. Brewbound / Anheuser-Busch (brewbound.com, inside.beer)

- **Finding:** AB restructure: consolidated distribution (ABSD), 6 sales regions, regional marketing hubs; key account managers for retail.
- **Implementing:** Outlet and check-in data support “key account” and territory views; **com.taja.outlet** for territory/map view.

---

## 34. Diageo (diageo.com)

- **Finding:** Field sales rep roles: execute business plans, priority accounts, compliance, area business development.
- **Implementing:** Dashboard as rep’s “business plan” view (outlets, visits, coverage); Profile for user/area.

---

## 35. Upya (upya.io)

- **Finding:** Offline mobile data collection CRM; custom forms, GPS visualization.
- **Implementing:** Offline check-in queue and sync pattern; GPS on check-in.

---

## 36. FreshByte (freshbyte.com)

- **Finding:** Offline sales app; orders online/offline; pricing updates via sync.
- **Implementing:** Same offline-first pattern for check-ins; sync when online.

---

## 37. Pluggin (pluggin.net)

- **Finding:** FMCG field automation: offline, order automation, beat planning, stock take, marketplace.
- **Implementing:** Beat/planning in **com.taja.outlet** (routes/outlets); distribution app focuses on “do the visit” and check-in.

---

## 38. Distributo (distributo.com, Play Store)

- **Finding:** DMS and field app: order taking, payment collection, returns, beat planning; billing and distribution.
- **Implementing:** Order and payment out of scope for v1; check-in and outlet list as first step; dashboard for “today’s activity.”

---

## 39. CPGvision / KATPRO / CFO Pro Analytics

- **Finding:** TPM ROI: 5–15% promotion ROI improvement; 20–40% trade efficiency; trade spend 10–20% of sales; need to measure ROI per promotion.
- **Implementing:** Dashboard can later add “promo” or “trade spend” tiles when backend has TPM.

---

## 40. Proxima SFA (trade promotion)

- **Finding:** AI TPM; real-time ROI; compliance and claim validation.
- **Implementing:** Dashboard structure supports future TPM widgets.

---

## 41. Rozana / Frontier Markets / VendorEazy / mwingi / Vitaran

- **Finding:** Last-mile and rural: Rozana (villages, women entrepreneurs); Frontier “Meri Saheli” (field app, delivery app, CRM); VendorEazy for rural retailers; Vitaran for demand-supply alignment.
- **Implementing:** Offline and low-bandwidth friendly; simple UI; **com.taja.app** works in low-connectivity; sync when online.

---

## 42. ITC (UNNATI app, Play Store)

- **Finding:** B2B ordering for retailers/wholesalers/stockists; catalog, recommendations, multi-language, digital payment.
- **Implementing:** Distribution app does not do B2B ordering in v1; outlet list and check-in only; order module can be added later.

---

## 43. HUL Shikhar (Play Store)

- **Finding:** Retailer ordering, schemes, voice/barcode search, bill payment, ShopKhata.
- **Implementing:** Same as above; we focus on visit/check-in and dashboard; ordering is future phase.

---

## 44. Badho (Play Store)

- **Finding:** B2B wholesale aggregator (multi-brand FMCG); orders, tracking, mandi rates, rewards.
- **Implementing:** Our apps are brand/company-owned (distribution + mapping), not aggregator; dashboard is for internal field and distribution.

---

## 45. Repsly (repsly.com)

- **Finding:** Retail execution: AI image recognition (e.g. 98% SKU), scheduling, territory optimization, planogram, real-time dashboards; Kraft Heinz, Adidas, L’Oréal; 8% revenue growth in first year.
- **Implementing:** Optional photo on check-in; dashboard with visit/compliance metrics; **com.taja.outlet** for territory and outlets on map.

---

## 46. Ailet (ailet.com)

- **Finding:** Planogram compliance app; check compliance with app.
- **Implementing:** Photo-on-check-in as first step; planogram/audit can be added later in distribution app.

---

## 47. VisitBasis (visitbasis.com)

- **Finding:** Mobile merchandising and planogram; image recognition for retail audit; geo-stamped photo reports.
- **Implementing:** Geo-stamped check-in (lat/lng); optional photo; backend already supports photo.

---

## 48. Form.com (form.com)

- **Finding:** Retail execution mobile app; task lists, audits, surveys.
- **Implementing:** Check-in as core “task”; future tasks/audits can be added; dashboard shows completion.

---

## 49. Nextyn (nextyn.com)

- **Finding:** Planogram compliance and shelf optimization; 50%→90% compliance in 6 weeks; 20% sales increase for FMCG.
- **Implementing:** Dashboard can show “visits with photo” as proxy for execution quality until we have formal audits.

---

## 50. FieldPro (fieldproapp.com)

- **Finding:** Van sales: spot orders, multi-UoM, digital invoice, van stock, offline, payment collection, route optimization, EOD reports.
- **Implementing:** Check-in and outlet list support “van sales” workflow; order/payment in a later phase.

---

## 51. SalesWorx (salesworx.app)

- **Finding:** Van sales: direct sales, inventory, payment, route tracking.
- **Implementing:** Same as above; distribution app = visit + check-in; dashboard = activity summary.

---

## 52. Microsoft Dynamics (microsoft.com, learn.microsoft.com)

- **Finding:** Field Service: work orders, scheduling, inventory, mobile for technicians; integration with Supply Chain; ORBIS ConsumerONE for consumer goods.
- **Implementing:** Outlet = “customer asset” / location; check-in = “visit” record; our backend is source of truth; dashboard reflects it.

---

## 53. Iberis (iberissoftware.com)

- **Finding:** Mobile sales order and distribution app for field.
- **Implementing:** Our split: **com.taja.app** = distribution (dashboard, outlets list, check-in); **com.taja.outlet** = mapping (create/edit outlets, geo-fence).

---

## 54. Byte Elephants (substack)

- **Finding:** Top 5 DMS features: inventory visibility, batch/expiry, credit/payment, schemes, analytics.
- **Implementing:** Dashboard analytics (outlets, visits, coverage); inventory/schemes when backend supports.

---

## 55. SalesMagna (salesmagna.com)

- **Finding:** OTP-based outlet verification: OTP to retailer mobile on add; prevents fake/duplicate outlets, ensures accuracy, reduces fraud and manual cleanup.
- **Implementing:** **Keep OTP for login** (and optionally for outlet verification in **com.taja.outlet** when adding new outlet).

---

## 56. EasyOTP (easyotp.com)

- **Finding:** App-based OTPs, magic links, QR login for retail/eCommerce.
- **Implementing:** We keep existing SMS/email OTP flow for login; optional app-based OTP later.

---

## 57. Berger FMCG (bpilmobile.bergerindia.com)

- **Finding:** OTP authentication for distributor registration in FMCG billing system.
- **Implementing:** OTP for login (mandatory); OTP for outlet/retailer verification documented as future option for **com.taja.outlet**.

---

## 58. Tezo (org.tezo.com)

- **Finding:** Mobile app for distributors: guide for FMCG industry.
- **Implementing:** Distribution app is the “field” app; dashboard is first screen after login for quick orientation.

---

## 59. SalesHero (salesheroapp.com)

- **Finding:** B2B mobile sales app for FMCG distributor.
- **Implementing:** Same positioning: **com.taja.app** for daily distribution (dashboard, list, check-in).

---

## 60. Snap Engineering (eng.snap.com)

- **Finding:** Shipping two apps in one Android package (Turducken); one APK, two app experiences; A/B testing, independent updates.
- **Implementing:** We use **two packages** (two apps): **com.taja.app** and **com.taja.outlet**, not two-in-one APK. Clean separation: distribution vs mapping.

---

## 61. Medium / Monorepo (medium.com, ishchhabra.com)

- **Finding:** Product flavours vs app modules: separate app modules for distinct identities; monorepo for shared code.
- **Implementing:** **com.taja.app** and **com.taja.outlet** as separate Android application modules (or separate repos); shared: auth (OTP), API client patterns, design system if desired.

---

## 62. Outfield (outfieldapp.com)

- **Finding:** Sales mapping: territory, accounts, customers; create activity, photos, notes from mobile; route optimization.
- **Implementing:** **com.taja.outlet**: map of outlets, create/edit outlets, attach location (lat/lng), optional geo-fence; activity = check-in in distribution app.

---

## 63. Scoutdrop (scoutdrop.com)

- **Finding:** Territory mapping, canvassing, routes; import accounts, custom maps, demographics.
- **Implementing:** Outlet mapping app: list + map of outlets; filter by type/region; create/edit outlet and geo-fence.

---

## 64. RepMove (repmove.app)

- **Finding:** Territory management: draw territories, assign accounts to users/categories.
- **Implementing:** **com.taja.outlet**: assign outlets to territories/users when backend supports; map view.

---

## 65. SoloLiger (sololiger.com)

- **Finding:** Territory mapping; geofencing; alerts for wrong zone.
- **Implementing:** Backend already has GeoFenceService; **com.taja.outlet** defines geo-fence per outlet; **com.taja.app** check-in validates against it.

---

## 66. Wingmate (wingmateapp.com)

- **Finding:** Sales territory mapping; pin opportunities, add to CRM; geofence areas, export.
- **Implementing:** **com.taja.outlet**: pin outlets on map, geofence, export/list view; **com.taja.app** uses outlet list for check-in.

---

## 67. Cegeka (AppSource)

- **Finding:** Cegeka Field Sales App: Dynamics 365, multi-day route planning.
- **Implementing:** Route planning in **com.taja.outlet** (future); distribution app shows “today’s” visits.

---

## 68. Software Finder (softwarefinder.com)

- **Finding:** Ivy Mobility positioning: AI route-to-market for consumer goods.
- **Implementing:** Dashboard as “route-to-market” summary (outlets, coverage, visits).

---

## 69. Peerspot (peerspot.com)

- **Finding:** GoSpotCheck vs Repsly comparison; Repsly 40% market share in field activity management, GoSpotCheck 33%.
- **Implementing:** We differentiate by: (1) distribution app with OTP + dashboard + check-in, (2) separate outlet mapping app; both aligned to our backend.

---

## 70. Orane Consulting (oraneconsulting.com)

- **Finding:** Retail FMCG CPG SAP solutions; S/4HANA for digital transformation.
- **Implementing:** Keep data model and API clear for future ERP integration; dashboard is distribution-centric.

---

## 71. Matthew Dryer (Coca-Cola United)

- **Finding:** Coca-Cola United sales & profit dashboard redesign: clarity, speed, in-store usability; retail margin, category mix, profit scenarios; increased adoption.
- **Implementing:** Dashboard: clear, fast, mobile-friendly; distribution metrics (outlets, visits, coverage); avoid clutter.

---

## 72. RSM (rsmus.com)

- **Finding:** Microsoft solutions for consumer goods.
- **Implementing:** No direct dependency; architecture stays integration-friendly.

---

## 73. SourceForge (sourceforge.net)

- **Finding:** BeatRoute product listing (SFA/CRM).
- **Implementing:** Our distribution app is our SFA layer; BeatRoute-style features (beat, route) in **com.taja.outlet** when we add them.

---

## 74. AppBrain / Apple App Store / Google Play

- **Finding:** BeatRoute, Distributo, UNNATI, Shikhar, etc. as real-world FMCG/distribution apps.
- **Implementing:** **com.taja.app** and **com.taja.outlet** publishable as two apps; store listings aligned to “distribution” vs “outlet mapping.”

---

# PART B — WHAT WE ARE IMPLEMENTING BASED ON THE RESEARCH

## 1. Two-package strategy

| Package | Purpose | Key features |
|--------|---------|--------------|
| **com.taja.app** | Distribution app (field reps) | Login (with OTP), **refactored distribution dashboard**, outlets list, check-in (location, optional photo, notes), offline sync, profile. |
| **com.taja.outlet** | Outlet mapping app | Login (with OTP), map of outlets, create/edit outlet (name, code, lat, lng, address, geo-fence), list/filter outlets; used by managers/mapping teams. |

- Shared: OTP flow, auth pattern, API base URL, design system (themes, components) where applicable.
- Backend: same API (user, outlets, check-ins, sync); **com.taja.outlet** uses GET/POST/PUT outlets and can add geo_fence fields when backend supports.

## 2. Dashboard (refactor, do not delete)

- **Keep** `DashboardActivity` and `activity_dashboard.xml`; **refactor** to show **distribution-based** data.
- **Metrics to show (from research):**
  - **Outlets:** Total outlets (for user’s branch/scope).
  - **Coverage / visits:** Outlets visited (check-ins) today, this week, or period.
  - **Check-ins:** Count of check-ins (today / week).
  - **Execution:** e.g. “Visits with photo” or “On-time visits” when we have the data.
- **Data source:** From existing backend: `GET /api/user`, `GET /api/outlets` (count, maybe by type), and check-ins (backend may need a lightweight “my check-ins summary” or we derive from existing `POST /api/check-ins` and local/sync state).
- **Design:** Reuse current dashboard layout (cards, sections); replace old stats (sales, revenue, tickets, stock, devices, restock, stock takes) with distribution KPIs above. Keep bottom nav: Dashboard (home), Outlets/Visits, Profile (and remove Stock, Stock takes).
- **Research alignment:** FieldAssist, BeatRoute, Delta, Sellin, ZA Consulting, InetSoft, Coca-Cola United (clarity, speed, execution-focused).

## 3. OTP

- **Keep** full OTP flow (login → optional OTP → dashboard).
- **Implementing:** SalesMagna, EasyOTP, Berger: OTP for login (mandatory); document OTP for outlet verification in **com.taja.outlet** as future enhancement.

## 4. Geo-fencing and check-in

- **Implementing:** Theia, Geovision, SoloLiger, Wingmate: **com.taja.outlet** creates/edits outlets with geo-fence; **com.taja.app** check-in sends lat/lng; backend already validates with GeoFenceService.

## 5. Offline and sync

- **Implementing:** Bizzfield, Delta, Upya, FreshByte, Pluggin, Distributo, Rozana/Frontier: offline-capable check-in; queue when offline; sync via `POST /api/sync/check-ins` when online.

## 6. Outlet mapping app (com.taja.outlet)

- **Implementing:** Outfield, Scoutdrop, RepMove, Wingmate, BeatRoute/FieldAssist (territory/route): separate app for map, create/edit outlet, geo-fence, list/filter; future: beat/route planning, territory assignment.

## 7. Security and auth

- **Implementing:** SalesMagna, Berger, EasyOTP: OTP for login; secure token storage; optional OTP for new outlet in mapping app later.

## 8. Dashboard KPIs (summary)

- From ZA Consulting, FieldAssist, Sellin, InetSoft, Delta:
  - Outlet count (total / by type if available).
  - Visits (check-ins) today / this week.
  - Coverage (e.g. % of outlets visited in period).
  - Optional: visits with photo, or “productive visits” when we have order data.

---

# PART C — UPDATED REPURPOSE PLAN (STRUCTURE)

## C.1 Backend API (unchanged)

- Same as before: login, user, outlets (CRUD), check-ins (create), sync check-ins. OTP: add verify-otp and resend-otp when required.

## C.2 com.taja.app (distribution app) — scope

- **Keep:** Login, OTP, **Dashboard (refactored)**, Profile, SessionManager, ApiClient (aligned to backend).
- **Add:** Outlets list screen (from `GET /api/outlets`), Check-in flow (outlet → location + optional photo + notes → `POST /api/check-ins`), offline queue and sync.
- **Remove:** Restock wizard, pending orders, stock takes, IMEI/scan/review. Remove only those activities and their layouts/strings; **do not delete** Dashboard.

## C.3 com.taja.outlet (outlet mapping app) — new package

- **New Android application module** (or separate repo): package `com.taja.outlet`.
- **Screens:** Login (with OTP), Map + list of outlets, Create/Edit outlet (name, code, lat, lng, address, optional geo_fence), filters.
- **Uses same backend:** `/api/login`, `/api/user`, `/api/outlets` (GET/POST/PUT). Geo-fence in body when backend supports it.

## C.4 Files to change (com.taja.app)

- **ApiClient.kt:** Login + OTP kept; remove dashboard/restock/stock-take methods; add user, outlets (list/show/create/update), check-in (create), sync check-ins. Add data classes: User, Outlet, CheckIn, SyncRequest/Response.
- **DashboardActivity.kt:** Refactor to load distribution metrics (outlet count, check-ins count, coverage); reuse existing layout structure; replace old stat cards with new KPIs.
- **activity_dashboard.xml:** Adjust to new KPI cards (outlets, visits, check-ins, coverage); keep bottom nav (Dashboard, Outlets, Profile).
- **LoginActivity.kt / OtpActivity.kt:** After success → DashboardActivity (not a “delete dashboard” flow).
- **New:** OutletsListActivity (or reuse a single “Home” that hosts list + nav to check-in). New: CheckIn flow (activity or bottom sheet) with location, optional photo, notes.
- **AndroidManifest.xml:** Remove activities for PendingOrders, RestockWizard, StockTakes, ScanImei, ReviewScannedImeis. Keep Login, Otp, Dashboard, Profile; add OutletsList and CheckIn if separate.
- **strings.xml:** Remove restock, stock take, IMEI, receive, edit quantity strings; add distribution dashboard strings and outlet/check-in strings.
- **build.gradle:** Remove Maps/ML Kit/CameraX if not needed; add location permission and possibly camera for check-in photo.

## C.5 Files to delete (com.taja.app only)

- Same as in original plan **except** do **not** delete:
  - `DashboardActivity.kt`
  - `activity_dashboard.xml`
- Delete: PendingOrdersActivity, RestockWizardActivity, StockTake*Activity (3), ScanImeiActivity, ReviewScannedImeisActivity, and all their layouts and related strings.

## C.6 Implementation order

Per the **PRODUCT DIRECTIVE** above: for each step, confirm the backend supports the capability required by our findings; if not, add the backend implementation first so we ship a superior product.

1. Backend: OTP endpoints (if not present); optional “dashboard summary” endpoint (outlet count, check-in counts) or derive from existing APIs.
2. ApiClient: distribution-only methods; remove restock/stock-take; add outlets, check-in, sync.
3. Refactor dashboard: new KPIs (outlets, visits, check-ins, coverage); same layout style.
4. Add outlets list and check-in flow in **com.taja.app**.
5. Remove deprecated activities and layouts; update Manifest and strings.
6. Implement **com.taja.outlet** (new module): login, map, outlet CRUD, geo-fence (when backend ready).
7. Test: login (with OTP), dashboard, outlets list, check-in, sync, profile; then outlet mapping app.

## C.7 Manual testing checklist

**#33 — Distribution app (com.taja.app)**  
1. Login with email/phone + password. If backend returns `requires_otp`, complete OTP screen then land on Dashboard.  
2. Dashboard shows distribution KPIs (outlets count, visits/check-ins, coverage).  
3. Open Outlets tab/list; list loads from GET /api/outlets.  
4. Tap an outlet → check-in bottom sheet; submit with location (and optional photo/notes); confirm POST /api/check-ins or offline queue.  
5. Open Profile; confirm user info and logout.

**#34 — Offline check-in sync**  
1. Turn off device network (or use airplane mode).  
2. Open distribution app, go to Outlets, tap outlet, submit check-in. Confirm it’s queued (toast or UI).  
3. Turn network back on; trigger sync (e.g. open Dashboard or pull-to-refresh).  
4. Confirm “X check-in(s) synced” (or similar) and that dashboard KPIs update.

**#35 — com.taja.outlet: login → map → CRUD outlets**  
1. Open Taja Outlet app; login (with OTP if required) → map screen.  
2. Tap “Outlets” → list loads; tap FAB “Add outlet” → create outlet (name required; code, address optional); save.  
3. Tap existing outlet in list → edit form; change and save. Confirm list updates.

**#36 — Backend Blade: distribution dashboard and check-ins list**  
1. Open Laravel web in browser; log in if required.  
2. Open distribution dashboard (outlets count, check-ins summary, coverage).  
3. Open check-ins list (by user/outlet/date); confirm data and filters.

Full step-by-step and sign-off table: **TESTING.md**. Mark each item ✅ in the status table when the test has been run and passed.

---

This plan keeps the dashboard, refactors it to distribution-based data, maintains OTP, and defines two packages (**com.taja.app** for distribution and **com.taja.outlet** for outlet mapping) based on research from 70+ sites and best practices from FMCG leaders and vendors.
