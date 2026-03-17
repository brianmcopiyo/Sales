# Plan: Distribution Module Addition

**Document version:** 1.0  
**Last updated:** March 2026  
**Status:** Implemented

**Progress markers:** `[ ]` = Pending · `[x]` = Completed — update the boxes below as tasks are done.

This document outlines a plan to add a **Distribution Module** to the existing Sales & Inventory platform (TajaCore). It is based on research into modern Distribution Management Systems (DMS), field force automation, outlet management, geo-fencing, and check-in/visit verification.

---

## 1. Executive Summary

The Distribution Module will extend the current system (branches, products, inventory, sales) with **outlet-centric** operations: registering and managing retail outlets, planning and recording field visits with **geo-fenced check-ins**, optional **route planning**, **daily call reports (DCR)**, and **outlet audits**. The goal is to give visibility and control over field distribution, reduce false visit reporting, and tie outlet activity to orders and compliance.

**Core pillars:**

| Pillar | Purpose |
|--------|--------|
| **Outlets** | Master data for retail points (shops, kiosks, dealers) with hierarchy and assignment |
| **Check-ins** | GPS-verified visit logging at outlets (with optional geo-fencing) |
| **Geo-fencing** | Restrict check-ins and/or order capture to defined areas (outlet radius or polygon) |
| **Visit planning** | Schedules, routes, and daily call reporting |
| **Outlet audits** | Optional checklists (merchandising, compliance, photos) per visit |

---

## 2. Research Summary

### 2.1 Industry Practice (DMS & Field Force)

- **Geo-fencing** is used to restrict transactions and check-ins to designated locations, preventing remote/fraudulent visit logging and order capture from wrong locations.
- **Check-in at outlet** is often mandatory with **photo + comment** and **GPS verification**; some systems use **QR codes** or **beacon** at outlet for extra verification.
- **Real-time dashboards** for managers show visit status, routes, and activity; **hierarchy** (e.g. Order Booker → ASM → RSM → Zonal) is common.
- **Offline-first mobile** is expected: capture visits and orders without connectivity, then **sync when online**.
- **Route planning** and **daily call reports** reduce drive time and give a clear record of who visited which outlet and when.

### 2.2 Outlet Management

- Outlets are **locations** (name, address, contact, type, channel) with optional **hierarchy** (e.g. region/area/outlet).
- **Assignment** of outlets to field reps (or to branches/territories) is required for visit planning and reporting.
- **Outlet registration** can include: address, GPS coordinates, trading hours, contact person, credit terms, and channel/segment.

### 2.3 Geo-Fencing (Technical)

- **Radius-based (circle):** center lat/lng + radius in metres; simple and supported natively on mobile.
- **Polygon:** array of lat/lng points forming a closed shape (4+ vertices); better for irregular territories.
- **Point-in-polygon** and **distance-from-point** logic can be implemented server-side or on-device; Google Maps Geometry Library or custom math (e.g. Haversine, ray-casting) can be used.
- **Client-side** geofence triggers (e.g. “entered outlet zone”) reduce server load and work offline.

### 2.4 Check-In & Visit Verification

- **Mandatory check-in** at outlet (with optional check-out) to log visit time and location.
- **Geo-verification:** check-in allowed only when device is within outlet’s geo-fence (radius or polygon).
- **Photo + notes** improve proof of visit and context (e.g. merchandising, issues).
- **Daily call report:** list of planned vs actual visits, times, and status (completed/skipped/partial).

### 2.5 Outlet Audits (Optional)

- **Checklists** per visit: merchandising, planogram, pricing, stock, cleanliness, promotions.
- **Photos** (before/after) and **scores** or pass/fail per section.
- Data used for **compliance reporting** and **outlet performance** over time.

---

## 3. Proposed Scope (Features)

### 3.1 Outlets

- **Outlet master:** Name, code, type (e.g. retail, kiosk, dealer), address, GPS (lat/lng), contact person, phone, email.
- **Optional:** Channel/segment, trading hours, credit limit, assigned branch/territory.
- **Assignment:** Outlets assigned to **field reps** (users) and/or to **branches/regions** for reporting.
- **Geo-fence per outlet:** Optional radius (metres) or polygon (list of coordinates) for check-in/order validation.
- **CRUD** and list/filter by branch, region, assignee, type.

### 3.2 Check-Ins (Visit Logging)

- **Check-in:** User selects outlet (or scans QR at outlet), app captures timestamp, GPS, optional photo and note.
- **Geo-validation:** If outlet has a geo-fence, check-in is allowed only when device location is inside the fence; otherwise show error.
- **Check-out (optional):** End visit with timestamp and GPS.
- **History:** List of check-ins per user, per outlet, per date; exportable for DCR.

### 3.3 Geo-Fencing

- **Define fence per outlet:** Either radius (metres) or polygon (GeoJSON or list of lat/lng).
- **Server-side validation:** On check-in (and optionally on order capture), validate submitted lat/lng against outlet’s fence.
- **Mobile:** Use device GPS; optionally use native geofence APIs for “entered/exited” events to trigger in-app check-in prompt.
- **Admin UI:** Map view to create/edit outlet and draw or set radius.

### 3.4 Visit Planning (Optional Phase)

- **Planned visits:** Date, user, outlet(s), sequence or route order.
- **Route optimization:** Optional integration or simple “suggest order” by distance to reduce travel time.
- **Daily plan vs actual:** Compare planned vs checked-in visits for DCR.

### 3.5 Daily Call Report (DCR)

- **Report:** By rep and date: list of outlets planned, checked-in (time in/out), skipped, not planned.
- **Summary:** Total visits, duration, distance (if tracked).
- **Export:** PDF/Excel for management.

### 3.6 Order Capture at Outlet (Optional)

- Link **sales order** to **outlet** and optionally to **check-in** (visit).
- **Geo-rule:** Allow order creation only when device is within outlet’s geo-fence (or relax for back-office).
- Reuse existing sale/product/inventory logic; add outlet as a dimension.

### 3.7 Outlet Audits (Optional Phase)

- **Audit template:** Sections and questions (e.g. yes/no, score, photo).
- **Audit run:** Linked to a visit (check-in); rep fills checklist and attaches photos.
- **Reporting:** Compliance score per outlet, trend over time.

### 3.8 Mobile & Offline

- **Mobile app** (or PWA): Check-in, photo, notes, optional order capture and audit; **offline queue** with sync when online.
- **Permissions:** Only users with a “field” or “distribution” role (or similar) see outlet list, check-in, and DCR.

---

## 4. Phased Implementation Plan

**Phase progress:** Phase 1: 7/7 · Phase 2: 4/4 · Phase 3: 4/4 · Phase 4: 5/5 · Phase 5: 3/3

### Phase 1 – Foundation (Outlets + Check-In)

| Done | # | Task | Notes |
|------|---|------|--------|
| [x] | 1.1 | **DB: outlets table** | id, name, code, type, address, lat, lng, contact_name, contact_phone, contact_email, branch_id (optional), region/territory, assigned_to (user_id), geo_fence_type (null/radius/polygon), geo_fence_radius_metres, geo_fence_polygon (JSON), is_active, timestamps |
| [x] | 1.2 | **Model & API: Outlet** | CRUD, list with filters (branch, assignee, type), validation |
| [x] | 1.3 | **UI: Outlet list & form** | Create/edit outlet; optional map picker for lat/lng |
| [x] | 1.4 | **DB: check_ins table** | id, user_id, outlet_id, check_in_at, check_out_at, lat_in, lng_in, lat_out, lng_out, photo_path, notes, timestamps |
| [x] | 1.5 | **API: Check-in** | Submit check-in (outlet_id, lat, lng, photo, note); server-side geo-validation if outlet has fence |
| [x] | 1.6 | **Geo-helper** | Service/helper: point-in-radius and point-in-polygon; use when validating check-in |
| [x] | 1.7 | **UI: Check-in (web or PWA)** | Select outlet, get GPS, optional photo/note, submit; show success/error (e.g. “Outside geo-fence”) |

**Deliverable:** Outlets master data and GPS-verified check-ins (with optional geo-fencing).

---

### Phase 2 – Geo-Fencing & Admin Map

| Done | # | Task | Notes |
|------|---|------|--------|
| [x] | 2.1 | **Outlet geo-fence UI** | In outlet form: choose “None” / “Radius” / “Polygon”; radius input in metres; polygon: map drawing or coordinate list |
| [x] | 2.2 | **Store polygon** | Save polygon as JSON (array of {lat, lng}); validate closed shape (4+ points, first = last) |
| [x] | 2.3 | **Map on list/detail** | Show outlets on map; show fence (circle or polygon) for each |
| [x] | 2.4 | **Strict validation** | Reject check-in if outside fence; clear error message |

**Deliverable:** Full geo-fence definition per outlet and enforced at check-in.

---

### Phase 3 – Visit Planning & DCR

| Done | # | Task | Notes |
|------|---|------|--------|
| [x] | 3.1 | **DB: planned_visits (optional)** | id, user_id, outlet_id, planned_date, sequence, notes |
| [x] | 3.2 | **UI: Plan day** | Assign outlets to rep by date; optional sequence for route |
| [x] | 3.3 | **DCR report** | By user + date: planned vs actual (from check_ins); export PDF/Excel |
| [x] | 3.4 | **Dashboard widget** | Today’s visits (planned vs done) per rep or branch |

**Deliverable:** Visit planning and daily call report.

---

### Phase 4 – Order at Outlet & Optional Audit

| Done | # | Task | Notes |
|------|---|------|--------|
| [x] | 4.1 | **Link sale to outlet** | Add outlet_id to sales (or to a “distribution_sale” table); optional link to check_in_id |
| [x] | 4.2 | **Geo-rule for order** | When creating order from field, optionally require device within outlet fence |
| [x] | 4.3 | **Audit template (optional)** | Tables: audit_templates, audit_sections, audit_questions; types: yes/no, score, photo |
| [x] | 4.4 | **Audit run** | Linked to check_in_id; store answers and photo URLs; compliance score per run |
| [x] | 4.5 | **Audit reporting** | Per outlet or per rep: compliance over time |

**Deliverable:** Orders tied to outlets (and optionally to visits) and optional outlet audit module.

---

### Phase 5 – Mobile App & Offline (Optional)

| Done | # | Task | Notes |
|------|---|------|--------|
| [x] | 5.1 | **Native app or PWA** | Check-in, photo upload, optional order and audit; use device GPS |
| [x] | 5.2 | **Offline queue** | Store check-ins (and orders) locally when offline; sync when online; conflict handling (e.g. last-write-wins or server-wins) |
| [x] | 5.3 | **Background location (if native)** | Use native geofence APIs to prompt “Check in at [Outlet]?” when entering fence |

**Deliverable:** Reliable field use with poor connectivity and optional entry/exit triggers.

**Implementation note:** The native app is a **Kotlin Android** app in the **`distribution-app/`** folder at the repository root. It uses Laravel Sanctum for API auth, Room for the offline queue, WorkManager for sync, and the Android Geofencing API for “Check in at [Outlet]?” prompts. The Laravel API is in `routes/api.php` (login, outlets, check-ins, sync/check-ins).

---

## 5. Technical Considerations

### 5.1 Geo-Fencing Implementation

- **Radius:** `Haversine` or equivalent to compute distance between (lat, lng) and outlet center; allow check-in if distance ≤ radius.
- **Polygon:** Ray-casting or point-in-polygon algorithm; store polygon in DB as JSON; validate once per check-in.
- **Library:** Laravel: custom helper or package (e.g. geo-related); frontend: Leaflet/Mapbox/Google Maps for drawing and display.

### 5.2 Data Model (Minimal for Phase 1)

- **outlets:** as in Phase 1 table above; ensure indexes on branch_id, assigned_to, is_active.
- **check_ins:** indexes on user_id, outlet_id, check_in_at for fast DCR and lists.

### 5.3 Permissions

- New permissions (examples): `outlets.view`, `outlets.manage`, `checkins.create`, `checkins.view`, `distribution.reports` (DCR).
- Restrict check-in to assigned users (or by branch/role) so reps only check in to their outlets.

### 5.4 Integration with Existing App

- **Branches:** Outlets can be linked to branch (e.g. outlet served by which branch); reports by branch.
- **Users:** Field reps = users with a role (e.g. “field_sales”) and optionally assigned outlets.
- **Sales:** If Phase 4 is done, sales can have outlet_id and optionally check_in_id; existing sale flow extended, not replaced.

---

## 6. Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| GPS inaccuracy | Use reasonable radius (e.g. 50–200 m); allow configurable radius per outlet; optional “soft” warning instead of hard block in v1 |
| Offline sync conflicts | Define rules (e.g. server timestamp wins); idempotent check-in by (user, outlet, date) to avoid duplicates |
| Polygon complexity | Start with radius-only; add polygon in Phase 2 with validation (closed, 4+ points) |
| Mobile effort | Start with responsive web/PWA for check-in; native app and offline in Phase 5 if needed |

---

## 7. Success Metrics (Examples)

- % of planned visits with a verified check-in (geo-validated).
- Average check-ins per rep per day.
- Reduction in “ghost” or unverified visits after geo-fencing.
- Time saved on DCR (automated vs manual).
- If audits: outlet compliance score trend.

---

## 8. References (Research Sources)

- Geo-fencing and field sales: Distributo, Fieldfy, Sokrio DMS, Sellin DMS.
- Outlet/retail management and geo-attendance: GeoAttendance, Caction, Taqtics, Shiftbase, BuddyPunch.
- Territory and geo-fencing tech: eSpatial, Maptive, SPOTIO, Google Maps Platform (Nav SDK, Geometry), AWS Location, Transistor Software (polygon geofencing).
- DMS and visit planning: Sokrio, BeatRoute, Delta Sales App.
- Field sales apps and DCR: RepMove, Outfield, BeatRoute.
- Outlet audit checklists: SafetyCulture, Effie, Delta Sales App, Lumiform.

---

## 9. Next Steps

| Done | Step |
|------|------|
| [x] | **Stakeholder alignment** on scope: confirm Phase 1 (outlets + check-in + basic geo-fence) as first release. |
| [x] | **Design:** Wireframes for outlet CRUD, check-in flow, and (if Phase 2) map for geo-fence. |
| [x] | **Schema:** Finalize `outlets` and `check_ins` migrations and relationships. |
| [x] | **Implement Phase 1** (outlets, check-in, radius-based geo-fencing), then iterate with Phase 2–5 as needed. |

**Module status:** All five phases are implemented. The Distribution Module is complete with outlets, check-ins, geo-fencing, visit planning, DCR, order-at-outlet, outlet audits, and the Kotlin Android app (`distribution-app/`) with offline sync and geofencing. Optional future work: order capture and audit flows inside the mobile app, PWA polish, or export formats (e.g. PDF for DCR).
