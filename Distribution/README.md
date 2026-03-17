# TajaCore Distribution – Android App

Kotlin Android app for the Distribution module: **check-in at outlets** with GPS, optional photo and notes, **offline queue** with sync when online, and **geofencing** to prompt “Check in at [Outlet]?” when entering an outlet’s radius.

## Requirements

- Android Studio Hedgehog (2023.1.1) or newer
- JDK 17
- Min SDK 26, Target SDK 34
- Laravel backend (Sales repo) running with API base URL reachable from the device (e.g. `http://10.0.2.2:8000/api/` for emulator)

## Setup

1. Open the **Distribution** folder in Android Studio (File → Open → select this folder).
2. Let Gradle sync (use default Gradle wrapper).
3. **API base URL** is set at build time (not on the login screen). For production, set it in `gradle.properties` in the project root (or pass via `-P`):
   - `API_BASE_URL=https://your-api.example.com/api/`
   - For local/dev: `API_BASE_URL=http://10.0.2.2:8000/api/` (emulator) or `http://<your-pc-ip>:8000/api/` (device on same network).
4. Build and run on a device or emulator.

## Features

- **Login**: Email or phone + password; receives a Bearer token (Laravel Sanctum).
- **Outlets list**: Fetched from `/api/outlets`; filtered by user’s branch/assignment.
- **Check-in**: Select outlet → Get GPS → optional photo and notes → Submit. If offline, the check-in is stored locally and synced later via `/api/sync/check-ins`.
- **Offline queue**: Pending check-ins stored in Room DB; WorkManager syncs when the device is online.
- **Geofencing**: Outlets with a radius geo-fence are registered with the system. When the device enters a fence, a notification “Check in at [Outlet]?” is shown; tapping opens the check-in screen for that outlet.

## Project structure

- `app/src/main/java/com/tajacore/distribution/`
  - `data/` – API (Retrofit), AuthRepository (DataStore), local DB (Room: `PendingCheckIn`).
  - `worker/` – `SyncWorker` (WorkManager) for syncing pending check-ins.
  - `geofence/` – `GeofenceHelper`, `GeofenceBroadcastReceiver`.
  - `ui/login/` – `LoginActivity`.
  - `ui/main/` – `MainActivity` (outlet list), `OutletAdapter`.
  - `ui/checkin/` – `CheckInActivity` (GPS, photo, notes, submit or save offline).

## Backend API (Laravel)

The app expects these endpoints (see `routes/api.php` in the Sales repo):

- `POST /api/login` – body: `{ "login", "password" }` → `{ "token", "user" }`
- `GET /api/outlets` – header: `Authorization: Bearer <token>` → `{ "outlets": [...] }`
- `POST /api/check-ins` – multipart: `outlet_id`, `lat`, `lng`, `notes`, optional `photo`
- `POST /api/sync/check-ins` – body: `{ "items": [ { "client_id", "outlet_id", "lat", "lng", "notes", "photo_base64?", "check_in_at" } ] }` → `{ "synced", "failed" }`

## Permissions

The app requests: Location (including background for geofencing), Camera, Notifications. On first run, allow location so check-in and geofencing work.
