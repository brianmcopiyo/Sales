# Outlet Map – Mobile app for mapping outlets

A **separate** Android app for **mapping outlets** on a map: place a pin, set name and optional geo-fence radius, and create or update outlets via the TajaCore API.

## Features

- **Login** with the same API as the distribution app (email/phone + password, optional base URL).
- **Map view** with all outlets as markers; radius geo-fences shown as circles.
- **Add outlet:** tap “Add outlet”, then tap on the map to set location; dialog for name, address, and geo-fence radius (metres). Saves via `POST /api/outlets`.
- **Edit outlet:** tap an existing marker to update name, address, location, or radius. Saves via `PUT /api/outlets/{id}`.

## Requirements

- Android Studio (Hedgehog or newer), JDK 17
- Min SDK 26, Target SDK 34
- **Google Maps API key** for the map
- Laravel backend running with API reachable from the device (e.g. `http://10.0.2.2:8000/api/` for emulator)

## Setup

1. **Google Maps API key**  
   Get an API key from [Google Cloud Console](https://console.cloud.google.com/) (enable “Maps SDK for Android”).  
   In the project root, create or edit `local.properties` and add:
   ```properties
   MAPS_API_KEY=your_google_maps_android_api_key
   ```
   Or in `gradle.properties`:
   ```properties
   MAPS_API_KEY=your_google_maps_android_api_key
   ```

2. **Open in Android Studio**  
   Open the `outlet-map-app` folder, sync Gradle.

3. **Run**  
   Run on a device or emulator. On the login screen, set the API base URL if needed (e.g. `http://10.0.2.2:8000/api/` for emulator).

## Backend API

The app uses the same auth as the distribution app and these endpoints:

- `POST /api/login` – login
- `GET /api/outlets` – list outlets (for markers)
- `GET /api/outlets/{id}` – single outlet (for edit)
- `POST /api/outlets` – create outlet (name, lat, lng, address, geo_fence_radius_metres, etc.)
- `PUT /api/outlets/{id}` – update outlet (including lat, lng, radius)

All outlet endpoints require `Authorization: Bearer <token>`.

## Project structure

- `app/src/main/java/com/tajacore/outletmap/`
  - `data/` – API (Retrofit), AuthRepository (DataStore)
  - `ui/login/` – LoginActivity
  - `ui/map/` – MapActivity (Google Map, markers, tap to add/edit, dialog form)

This app is **separate** from `distribution-app` (check-ins, DCR, offline sync) and is focused only on **mapping outlet locations** on the map.
