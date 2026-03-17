# Plan: Android App UI & API Modernization

**Document version:** 1.0  
**Last updated:** March 2026  
**Status:** Complete  
**Applies to:** Distribution app, Outlet Map app (TajaCore Sales repo)

**Progress:** `[x]` = Completed · `[ ]` = Pending — see markers below and §6 for order.

This document summarizes research on **best Kotlin mobile UI design** and **recommended Android OS APIs**, then outlines a concrete plan: **what to add**, **what to remove**, and **what to change** so the apps align with production-grade, industry-standard Android development.

---

## 1. Research Summary

### 1.1 Best Kotlin Mobile App UI Design (2024–2026)

| Finding | Source / practice |
|--------|--------------------|
| **Jetpack Compose is the standard** for new Android UI | Google positions Compose as the future; new projects in XML create technical debt from day one. |
| **Material 3 (Material You)** is the recommended design system | Material 3 is built for Compose; M3 offers dynamic color, updated components, and better engagement (e.g. ~18% more user engagement than M2 in cited studies). |
| **Declarative, state-driven UI** | Compose uses `remember`, `mutableStateOf`, recomposition; reduces bugs and improves consistency vs imperative View updates. |
| **Responsive / adaptive layouts** | Use layout modifiers and breakpoints so UI adapts to screen size; improves engagement on tablets and foldables. |
| **Accessibility from the start** | Use `semantics` and content descriptions; ~15% of users benefit from accessible design. |
| **Lists:** use `LazyColumn` / `LazyRow` | Efficient recycling and recomposition; replaces RecyclerView + adapters with less boilerplate. |
| **Theming:** color scheme + typography + shapes | Centralize in `MaterialTheme`; support light/dark and (on Android 12+) dynamic color from wallpaper. |

### 1.2 Best Android OS APIs & Versions

| Topic | Recommendation |
|-------|-----------------|
| **Target SDK** | **API 35 (Android 15)** for new apps and updates by **August 31, 2025** (Google Play). API 34 minimum for existing apps to stay visible to new users on newer devices. |
| **Min SDK** | API 21+ for Compose; **API 24+ or 26+** is common for production to avoid legacy edge cases. Current apps use **minSdk 26** — keep or align across apps. |
| **Compile SDK** | Match or exceed targetSdk; use **34+** now, plan for **35** when targeting Android 15. |
| **UI toolkit** | **Jetpack Compose** for new screens and gradual migration; XML retained only where migration is deferred. |
| **Material library** | **Material 3** (`androidx.compose.material3`) in Compose; for XML, use **Theme.Material3** or **Theme.MaterialComponents** with correct `TextAppearance` so `TextInputLayout` does not crash. |
| **Theme base** | Apps using **TextInputLayout** (Material Components) must use a theme that inherits from **Theme.MaterialComponents** (or descendant) and provides valid **TextAppearance**; otherwise inflate crashes. |

### 1.3 Migration Strategy (Compose + XML)

- **Incremental migration:** Compose and Views can coexist. Use `ComposeView` in XML/Fragment or `AndroidView` in Compose.
- **Order of migration:** Start with small, stateless components or simple screens (e.g. login, settings); then lists (outlets, check-ins); then flows (check-in, sync).
- **New features:** Implement in Compose; avoid adding new XML-only screens where possible.

---

## 2. What to Add

| # | Status | Item | Rationale |
|---|--------|------|-----------|
| 1 | [x] | **Jetpack Compose** (UI, Material 3, compiler, BOM) | Added to Distribution: compose-bom, material3, activity-compose, buildFeatures.compose, composeOptions. |
| 2 | [x] | **Material 3 theming** | `TajaCoreDistributionTheme` with light/dark ColorScheme and dynamic color on API 31+; Typography in Type.kt. |
| 3 | [x] | **Compose Navigation** | Distribution: single Activity (MainActivity) as LAUNCHER; NavHost with routes `login`, `main`; type-safe navigation login ↔ main; check-in remains separate Activity. |
| 4 | [x] | **Target SDK 35** and **compileSdk 35** | Set in Distribution and Outlet Map app/build.gradle.kts. |
| 5 | [x] | **Dark theme** | Theme respects `isSystemInDarkTheme()`; DarkColorScheme and dynamic dark on API 31+. |
| 6 | [x] | **Accessibility** | Distribution Compose screens: `contentDescription`/`semantics` on login fields and button, main list (Logout, outlet cards), check-in (buttons, notes, submit). M3 buttons meet 48dp. |
| 7 | [x] | **Compose Previews** | `@Preview` on `LoginScreenPreview` in LoginScreen.kt. |

---

## 3. What to Remove

| # | Status | Item | Rationale |
|---|--------|------|-----------|
| 1a | [x] | **User-editable base URL in UI (Distribution)** | Base URL is a build property (`BuildConfig.API_BASE_URL`); login field removed. |
| 1b | [x] | **User-editable base URL in UI (Outlet Map)** | Base URL is build property (`BuildConfig.API_BASE_URL`); `baseUrlField` and `saveBaseUrl` removed. |
| 2 | [x] | **Redundant or duplicate theme files** | Single `themes.xml` in Distribution; no conflicting `values-v*` theme overrides. |
| 3 | [x] | **Legacy Widget.Material.* styles** | Apps use **Widget.MaterialComponents.*** (e.g. OutlinedBox); no legacy Material 2 widget styles in use. |
| 4 | [x] | **Unused dependencies** | Removed constraintlayout; viewBinding disabled (no XML layouts left). AppCompat/activity-ktx kept for compatibility. |
| 5 | [x] | **XML layouts and Activities** | Removed `activity_login.xml`, `activity_main.xml`, `activity_checkin.xml`, `item_outlet.xml` and `OutletAdapter.kt`; all screens now Compose. |

---

## 4. What to Change

| # | Status | Item | Current → Target |
|---|--------|------|------------------|
| 1 | [x] | **Theme inheritance (XML)** | Distribution theme extends **Theme.MaterialComponents.Light.NoActionBar** and now sets **textAppearanceHeadline6**, **textAppearanceBody1**, **textAppearanceSubtitle1** so `TextInputLayout` does not crash. |
| 2 | [x] | **Target / compile SDK** | Set to **35** in Distribution and Outlet Map. |
| 3 | [x] | **Login screen** | Migrated to Compose + Material 3 (`LoginScreen.kt`); `LoginActivity` uses `setContent` and `TajaCoreDistributionTheme`. |
| 4 | [x] | **Main screens (outlet list, check-in)** | Main screen: `MainScreen.kt` + `MainActivity` setContent, `LazyColumn` for outlets. Check-in: `CheckInScreen.kt` + `CheckInActivity` setContent, Material 3 form. |
| 5 | [x] | **Build configuration** | Compose BOM, `buildFeatures.compose`, `composeOptions.kotlinCompilerExtensionVersion`, activity-compose and material3 in Distribution. |
| 6 | [x] | **Kotlin / AGP** | Kotlin 1.9.22, AGP 8.x in use; keep on supported versions for Compose compiler when adding Compose. |

---

## 5. Immediate Fix: LoginActivity Crash — [x] Done

The crash **"This component requires that you specify a valid TextAppearance attribute"** means `TextInputLayout` is not getting a valid Material theme/TextAppearance. The app theme already extends `Theme.MaterialComponents.Light.NoActionBar`; the issue is often:

- A **theme overlay** or **style** that strips Material attributes, or  
- **Missing or overridden** `textAppearance*` in the theme (e.g. in another `values` or `values-v*` folder).

**Actions:**

1. Ensure **only one** `themes.xml` (or that all variants inherit from `Theme.MaterialComponents.*` and do not override with a non-Material parent).
2. Explicitly set in the app theme, for example:
   - `android:textAppearanceHeadline6`
   - `android:textAppearanceBody1`
   - `android:textColorPrimary`, `android:textColorSecondary`
   if they are missing.
3. Alternatively, set **style** on each `TextInputLayout` to a style that explicitly inherits from `Widget.MaterialComponents.TextInputLayout.OutlinedBox` and, if needed, set a **theme** on the `TextInputLayout` to a Material theme overlay (e.g. `ThemeOverlay.MaterialComponents.TextInputLayout`).

Once the theme consistently provides Material TextAppearance, the crash should stop.

---

## 6. Suggested Implementation Order

| Step | Status | Action |
|------|--------|--------|
| 1 | [x] | **Fix login crash** (theme / TextAppearance) so the app runs reliably. |
| 2 | [x] | **Bump targetSdk to 35** and **compileSdk to 35** (Distribution + Outlet Map). |
| 3 | [x] | **Add Compose** (BOM, dependencies, `buildFeatures`, `composeOptions`) to Distribution. |
| 4 | [x] | **Implement login screen in Compose** (Material 3); `LoginActivity` uses `setContent` + `TajaCoreDistributionTheme` + `LoginScreen`. |
| 5 | [x] | **Migrate main list and check-in** to Compose (`MainScreen.kt`, `CheckInScreen.kt`; both Activities use `setContent` + theme). |
| 6 | [x] | **Remove obsolete XML** and unused dependencies (removed the four layouts, OutletAdapter, viewBinding, constraintlayout). |
| 7a | [x] | **Outlet Map: base URL as build property** (`BuildConfig.API_BASE_URL`, login layout and AuthRepository updated). |
| 7b | [x] | **Outlet Map: theme fix + Compose** (Theme.Material3.Light.NoActionBar; Compose BOM, theme, LoginScreen; `activity_login.xml` removed). |

---

## 7. Summary: Completed vs remaining

**Completed:** Theme/TextAppearance fix (Distribution); base URL as build property (both apps); single theme file, MaterialComponents styles; Kotlin/AGP ok; .gitignore for Android apps; **target/compile SDK 35** (both apps); **Compose + Material 3** in Distribution and Outlet Map (BOM, theme, dark + dynamic color); **Login in Compose** (both apps); **main list and check-in in Compose** (Distribution); **obsolete XML and unused deps removed**; **Outlet Map** base URL + theme + Compose login (7a, 7b); **Accessibility**: contentDescription/semantics on Distribution Compose screens; **Compose Navigation**: single Activity (MainActivity) as LAUNCHER, NavHost with `login` and `main` routes, LoginActivity removed.

**Remaining:** None.

---

## 8. References

- [Jetpack Compose – Android Developers](https://developer.android.com/develop/ui/compose)
- [Migrate XML Views to Jetpack Compose](https://developer.android.com/develop/ui/compose/migrate)
- [Material 3 in Compose](https://developer.android.com/develop/ui/compose/designsystems/material3)
- [Target API level requirements – Google Play](https://support.google.com/googleplay/android-developer/answer/11926878)
- Material Components Android (GitHub) – TextInputLayout theme/TextAppearance requirements (e.g. issue #2073, #2635)
