# Distribution Mobile App: UX, Animation & Information Design Research

**Document version:** 1.0  
**Last updated:** March 2026  
**Purpose:** In-depth research on official Android/Google UX and animation guidelines, third-party best practices, and a concrete plan for what the Distribution app should display, where, and for whom—aligned with data the system already collects.

**Implementation markers:** `[x]` = Implemented · `[ ]` = Wait list (not yet implemented). Update as the app is built out.

---

## 1. Executive Summary

The TajaCore Distribution mobile app currently presents a minimal UI (login, outlet list, check-in) with little contextual information, no loading/empty/error states, and no animations. This document summarizes research across **official Android and Material Design sources** and **third-party UX best practices** (50+ references) to define:

- **What features and information** the app should expose
- **Where each piece of data** should appear (screen, section, component)
- **Who sees what** (field rep vs. manager, role-based views)
- **How to present data** the system already collects (outlets, check-ins, pending sync, user)
- **UX and animation standards** following Google mobile app design and interactions

The plan is grounded in the existing Distribution Module: outlets, check-ins with GPS/photo/notes, geo-fencing, offline sync, and (on the backend) visit planning and DCR. The app should feel informative, responsive, and trustworthy—not empty.

---

## 2. Research Methodology & Sources

Research drew on:

- **Official Android:** developer.android.com (design, architecture, views, animations, accessibility, navigation, quality guidelines)
- **Material Design:** m2.material.io, m3.material.io (components, motion, top app bar, lists, cards)
- **Google Design & Research:** design.google (Expressive Material, research)
- **Third-party UX:** NN/G, Toptal, UX Collective, Mobbin, UXPin, LogRocket, CSS-Tricks, Pixelform, GetWorkForm, Orbit Forms, BE-DEV, Cursa, web.dev
- **Field sales / DCR:** Delta Sales App, Mapsly, Clappia, AllGeo, Salestrail, Insight7, CallProof, Claap
- **Technical:** Android Open Source Project (haptics), React Navigation, MoldStud, Medium, Dev.to

**Reference count:** 50+ distinct sources (URLs listed in **Section 11**).

---

## 3. Official Android & Google UX Guidelines

### 3.1 Design Foundations (developer.android.com)

- **UI design home:** [Mobile | UI Design](https://developer.android.com/design/ui/mobile) — Foundations, Styles, Layout & Content, Behaviors & Patterns, Components, Home Screen, Accessibility.
- **Quality:** [Core app quality guidelines](https://developer.android.com/docs/quality-guidelines/core-app-quality) and [What a great user experience looks like](https://developer.android.com/quality/user-experience) stress usability, accessibility, visual appeal, and support for different form factors (phone, tablet, foldable).
- **Material Components:** Use Material 3 components (action, communication, containment, navigation, selection) for consistency; [Material overview](https://developer.android.com/design/ui/mobile/guides/components/material-overview).
- **Accessibility:** [Accessibility foundations](https://developer.android.com/design/ui/mobile/guides/foundations/accessibility) — support vision, hearing, motor; [Make apps more accessible](https://developer.android.com/guide/topics/ui/accessibility/apps) specifies **minimum 48dp × 48dp touch targets** and **contrast ratios** (e.g. 4.5:1 for small text).

### 3.2 Navigation (developer.android.com)

- **Principles:** [Principles of navigation](https://developer.android.com/guide/navigation/principles) — fixed start destination, back stack, Up vs Back, deep linking.
- **Structure:** [Design your navigation graph](https://developer.android.com/guide/navigation/design), [Encapsulate navigation code](https://developer.android.com/guide/navigation/design/encapsulate). For the Distribution app: **Login → Main (outlet list) → Check-in** with clear back/up behavior.
- **Transitions:** [Animate transitions between destinations](https://developer.android.com/guide/navigation/use-graph/animate-transitions) — use system or custom transitions for screen changes.

### 3.3 Animations & Motion

- **Android animations:** [Introduction to animations](https://developer.android.com/develop/ui/views/animations/overview) — drawable, property, physics-based; [MotionLayout](https://developer.android.com/develop/ui/views/animations/motionlayout) for complex transitions.
- **Material Motion:** [Motion – Material Design 3](https://m3.material.io/styles/motion/overview/how-it-works) — Standard vs Expressive schemes; [Building Beautiful Transitions with Material Motion](https://developer.android.com/codelabs/material-motion-android) defines **Fade**, **Fade Through**, **Shared Axis**, **Container Transform**.
- **Duration:** Transitions **200–400 ms** feel responsive; Material reports ~23% faster perceived speed under 400 ms. Stagger list items (e.g. 30–60 ms) for comprehension.
- **Compose:** [Customize animations (Compose)](https://developer.android.com/develop/ui/compose/animation/customize) — AnimationSpec, spring, tween.

### 3.4 Layout & Responsiveness

- **Responsive/adaptive:** [Responsive/adaptive design with views](https://developer.android.com/develop/ui/views/layout/responsive-adaptive-design-with-views) — ConstraintLayout, flexible dimensions; [Screen compatibility](https://developer.android.com/guide/practices/screens_support); [Window size classes](https://developer.android.com/develop/ui/views/layout/use-window-size-classes) (compact, medium, expanded).
- **Cards:** [Create a card-based layout](https://developer.android.com/develop/ui/views/layout/cardview) — elevation, containment for list items.

### 3.5 Top App Bar & System UI

- **Top app bar:** [Top app bar – Material Design 3](https://m3.material.io/components/top-app-bar/guidelines), [App bars (Compose)](https://developer.android.com/develop/ui/compose/components/app-bars) — Small, Center aligned, Medium, Large; title, navigation icon, actions; scroll behavior (pinned, enter always, exit until collapsed).
- **System bars:** [Android system bars](https://developer.android.com/design/ui/mobile/guides/foundations/system-bars).

### 3.6 Haptics & Feedback

- **Haptics:** [Implement haptics on Android](https://developer.android.com/develop/ui/views/haptics), [Haptic feedback constants](https://developer.android.com/develop/ui/views/haptics/haptics-apis), [UX foundation for haptic framework](https://source.android.com/docs/core/interaction/haptics/haptics-ux-foundation) — clear, sharp feedback for button press and confirmations; respect system settings.

### 3.7 Pull to Refresh

- **Swipe refresh:** [Pull to refresh (Compose)](https://developer.android.com/develop/ui/compose/components/pull-to-refresh), [Add swipe-to-refresh](https://developer.android.com/develop/ui/views/touch-and-input/swipe/add-swipe-interface) — standard pattern for refreshing the outlet list.

### 3.8 Error & UI State

- **UI layer:** [UI layer](https://developer.android.com/topic/architecture/ui-layer), [UI State production](https://developer.android.com/topic/architecture/ui-layer/state-production) — treat loading, success, error, and empty as explicit UI states and drive the UI from a single state source.

---

## 4. Material Design & Google App Patterns

- **Lists & cards:** [Lists](https://m2.material.io/components/lists), [Cards](https://developer.android.com/develop/ui/views/layout/cardview) — lists for comparison and scanning; cards for mixed content and actions.
- **Material 3 Expressive:** [Expressive Design: Google's UX Research](https://design.google/library/expressive-material-design-google-research) — bold color, shape, motion; Gmail and other Google apps moving to Expressive.
- **MotionScheme:** [MotionScheme API](https://developer.android.com/reference/kotlin/androidx/compose/material3/MotionScheme) — Standard vs Expressive animation specs.

---

## 5. Third-Party UX Best Practices (Summary)

### 5.1 Empty States

- **Purpose:** Explain why there’s no content and what to do next (e.g. “No outlets assigned”, “Pull to refresh” or “Contact your manager”).
- **Elements:** Headline, short explanation, illustration/icon, primary action, optional secondary action. Avoid generic “No data” only.
- **Sources:** UI Deploy, UX Design World, Primer, Toptal, Mobbin.

### 5.2 Loading States

- **Skeleton screens** improve perceived performance (~30% faster feel vs spinners); use for known layouts (e.g. outlet cards). Shimmer or pulse; match real layout; 800 ms typical.
- **Spinners** for unknown or short operations. Always pair with clear status (e.g. “Loading outlets…”).
- **Sources:** LogRocket, BE-DEV, Medium.

### 5.3 Forms (Login, Check-in)

- **Single-column layout**; **labels above fields**; **appropriate input types** (email, tel, number) for keyboards.
- **Touch targets ≥ 44–48 dp**; primary action in thumb zone; error messages below field, specific and actionable.
- **Sources:** NN/G, Pixelform, GetWorkForm, CSS-Tricks, Orbit Forms.

### 5.4 Navigation: Bottom vs Drawer

- **Bottom navigation:** 2–5 main sections, high visibility, one-handed use (e.g. Outlets, Check-ins, Profile).
- **Drawer:** 5+ destinations or complex hierarchy when not all need to be visible at once.
- **Sources:** CoderCrafter, AppMySite, UX Collective, SevenSquareTech, Google Design (Medium).

### 5.5 Success & Micro-Interactions

- **Success feedback:** Match intensity to action (toast for small, full-screen for critical). Immediate feedback reduces errors and increases completion.
- **Micro-interactions:** Trigger, rules, feedback, loops; subtle animation can make the app feel ~5% faster and more enjoyable.
- **Sources:** Pencil & Paper, UXPin, Sanjay Dey, Pixelfree Studio, Kitchn.

### 5.6 Offline & Sync

- **Visible sync state:** Connected / syncing / pending / error in a consistent place (e.g. app bar or banner).
- **“As of” timestamps** and “Pending upload” count; reassure that work continues offline and will sync.
- **Sources:** Google Open Health Stack, web.dev, Microsoft Power Apps, Cursa, Bluesky (GitHub).

### 5.7 Field Sales & Check-in UX

- **Check-in:** Location-based or manual; clear CTA (“Check in here”), optional photo/notes; check-out and duration where applicable.
- **Manager view:** Dashboards with visit logs, planned vs actual, time per outlet, route compliance.
- **Rep view:** Today’s outlets, quick check-in, offline support, simple navigation.
- **Sources:** Delta Sales App, Mapsly, Clappia, AllGeo.

### 5.8 Geofence Prompts

- **Contextual message** when entering zone (e.g. “You’re at [Outlet]. Check in?”) with clear actions (Check in, Later).
- **Frequency:** Avoid spamming; respect “Reduce motion” and system settings.
- **Sources:** Mapsly, OneSignal, Koder.ai, Radar.

### 5.9 Typography & Readability

- **Hierarchy:** Headlines 24–32 pt, section 18–24 pt, body 14–16 pt, captions 12–14 pt, fine print 10–12 pt.
- **Minimum body ~16 px**; line height and spacing for scanning; legibility in various lighting.
- **Sources:** Createbytes, Glance, Toptal.

### 5.10 Photo Capture

- **Clear controls** (visible against any background); option for camera or gallery when appropriate; immediate confirmation (thumbnail or checkmark).
- **Sources:** Theodo, Leewayhertz, web.dev, developer.android.com (CameraX).

---

## 6. Data the System Already Collects

From the existing backend, API, and mobile app:

| Data | Source | Currently used in app |
|------|--------|------------------------|
| **User** | Auth (Sanctum), `/user` | Login; token storage |
| **Outlets** | `GET /outlets` | List (id, name, code, address, lat, lng, geo_fence_*) |
| **Check-ins** | `POST /check-ins`, sync | Submit from Check-in screen; server stores user_id, outlet_id, lat, lng, photo, notes, timestamps |
| **Pending check-ins** | Room DB (offline queue) | Count shown as “X pending sync”; synced via `POST /sync/check-ins` |
| **Geo-fence** | OutletDto (radius/polygon) | GeofenceHelper registers fences; optional “Check in at [Outlet]?” |
| **Planned visits / DCR** | Backend (Phase 3) | Not yet exposed to mobile API; available for future “Today’s visits” and manager views |

**Possible future API (not yet in app):** Planned visits per day, DCR summary (e.g. planned vs completed count), outlet detail (last visit, visit count). The plan below uses only **current** API data but indicates where future data would slot in.

---

## 7. Features Needed & Information by Screen

### 7.1 Login Screen

| Done | Element | What to show | Who | Notes |
|------|--------|----------------|-----|--------|
| [x] | App name / logo | Branding | All | Clear hierarchy (e.g. Headline6). |
| [x] | Subtitle | e.g. “Sign in to continue” | All | Secondary text. |
| [x] | Email or phone | Input, top-aligned label | All | Appropriate keyboard type. |
| [x] | Password | Input, top-aligned label | All | Visibility toggle. |
| [x] | Error message | Inline, below fields or under button | All | Specific message (e.g. “Invalid credentials”), not generic. |
| [x] | Login button | Primary CTA | All | Full width, ≥ 48 dp height; disabled + loading state while requesting. |
| — | Empty state | N/A | — | Not applicable. |

**UX:** [x] Single-column form; [x] 48 dp touch targets; [x] success → navigate to Main; [x] screen transition animation; [x] optional brief success feedback (e.g. checkmark or toast).

---

### 7.2 Main Screen (Outlet List)

| Done | Element | What to show | Who | Notes |
|------|--------|----------------|-----|--------|
| [x] | Top app bar | Title “Outlets”, overflow menu (Logout) | Field rep | Per Material Top App Bar. |
| [x] | User / greeting | Optional: “Hello, [name]” or avatar | Field rep | From `/user` or token payload. |
| [x] | Pending sync banner | “X pending sync” + optional “Sync now” or icon | Field rep | Only when pending count > 0; [x] “Sync now” action. |
| [x] | Outlet list | Cards: outlet name, address or code, optional distance/last visit | Field rep | RecyclerView + cards; [x] pull-to-refresh. |
| [x] | Empty state | Illustration + “No outlets assigned” + “Pull to refresh” or “Contact your manager” | Field rep | When list is empty after load. |
| [x] | Loading state | Skeleton cards or progress indicator + “Loading outlets…” | Field rep | While `GET /outlets` in progress. |
| [x] | Error state | Message + “Retry” button | Field rep | On network/auth error. |
| [x] | List item action | Tap → open Check-in for that outlet | Field rep | Clear affordance (e.g. chevron or “Check in”). |

**Future (when API exists):** [ ] “Today’s visits” section (planned vs done), [ ] filters (e.g. by area), [ ] search.

**Who sees:** Field reps only (managers would see a different dashboard on web or a future “Manager” app).

---

### 7.3 Check-in Screen

| Done | Element | What to show | Who | Notes |
|------|--------|----------------|-----|--------|
| [x] | Outlet name | Headline at top | Field rep | From intent/navigation. |
| [x] | Location section | “Get my location” button, then lat/lng or “—”; optional “Location captured” with timestamp | Field rep | [x] lat/lng shown; [ ] “Location captured” / timestamp. |
| [x] | Photo section | “Take photo” button; “Photo captured” or thumbnail when done; “Optional” hint | Field rep | [x] button + status text; [x] thumbnail preview. |
| [x] | Notes section | Multiline input, label “Notes” | Field rep | Optional. |
| [x] | Submit button | “Check in” primary button | Field rep | Disabled while loading; [x] toast “Check-in recorded” + finish; [x] success animation. |
| [x] | Error | e.g. “Get location first”, “Outside geo-fence”, network error | Field rep | Toast. |
| [x] | Offline path | On submit failure: save to pending queue, toast “Saved offline. Will sync when online.” | Field rep | Implemented. |

**UX:** [x] Sections in cards (Location, Photo, Notes); [x] 48 dp buttons; [x] success micro-interaction; [x] optional haptic on capture and submit.

---

### 7.4 Geofence “Check in?” Prompt (Optional)

| Done | Element | What to show | Who | Notes |
|------|--------|----------------|-----|--------|
| [x] | Trigger | When device enters outlet geofence | Field rep | GeofenceHelper + GeofenceBroadcastReceiver. |
| [x] | Message | e.g. “You’re at [Outlet name]. Check in now?” | Field rep | In-app dialog implemented (GeofencePromptActivity). |
| [x] | Actions | “Check in” (opens Check-in), “Later” (dismiss) | Field rep | Avoid over-prompting. |

---

### 7.5 Sync & Offline (Global)

| Done | Element | What to show | Who | Notes |
|------|--------|----------------|-----|--------|
| [x] | Pending count | In Main screen banner/chip | Field rep | “X pending sync”. |
| [x] | Sync in progress | Small indicator (e.g. app bar or banner) | Field rep | “Syncing…” |
| [x] | Sync success | Brief confirmation (toast or checkmark) | Field rep | After WorkManager sync. |
| [x] | Sync error | “Sync failed. Will retry when online.” or “Retry” | Field rep | Non-blocking. |

---

## 8. Who Sees What (Roles)

| Role | Current app | Data they see | Possible future |
|------|-------------|----------------|------------------|
| **Field rep** | Login, Main, Check-in | Own outlets, own pending sync, own check-ins (via submit/sync) | Today’s planned visits, own DCR summary |
| **Manager** | Not in mobile app today | — | Separate manager app or web dashboard: team DCR, planned vs actual, outlet coverage, compliance |
| **Admin** | Not in mobile app | — | Web only: outlets, users, geo-fences, reports |

The mobile app is **field-rep-centric**. All “who” in Sections 7.1–7.5 is the **field rep**. Manager and admin views are out of scope for this document but should follow the same UX principles when implemented.

---

## 9. Presenting Existing Data: Mapping to UI

| Done | System data | Where to show | How to present |
|------|-------------|----------------|-----------------|
| [x] | **User name** | Main screen (optional) | “Hello, [name]” or app bar subtitle. |
| [x] | **Outlets** | Main list | Card per outlet: **name** (title), **address** or **code** (subtitle). [ ] Optional: icon for geo-fence type. |
| [x] | **Pending sync count** | Main screen | Banner: “X pending sync”. [ ] Optional sync icon / “Sync now”. |
| [x] | **Check-in result** | Check-in screen | Success: “Check-in recorded” + close. Offline: “Saved offline. Will sync when online.” + close. |
| [x] | **Location** | Check-in screen | After “Get my location”: show lat, lng. [ ] “Location captured” label. |
| [x] | **Photo** | Check-in screen | “Photo captured” text. [x] Thumbnail; [ ] optional remove. |
| [x] | **Notes** | Check-in screen | Multiline field; submit sends with check-in. |
| [x] | **Errors** | Per screen | Login: inline; Check-in: toast; Main: error state + Retry. |

**Future (when API exists):** [ ] Planned visits → “Today’s visits” block on Main; [ ] DCR → manager dashboard (web or app).

---

## 10. Animation & Motion Recommendations

| Done | Recommendation |
|------|----------------|
| [x] | **Screen transitions:** Use **Shared Axis** or **Fade** between Login ↔ Main ↔ Check-in (200–400 ms). |
| [x] | **List:** Stagger outlet card appearance (e.g. 30–50 ms delay per item) on load. |
| [x] | **Loading:** Skeleton cards for outlet list; subtle shimmer or pulse. |
| [x] | **Success:** Short checkmark or scale animation on successful check-in; optional haptic. |
| [x] | **Buttons:** Ripple or highlight on tap (Material default). [x] Optional light haptic (e.g. CLICK). |
| [x] | **Pull to refresh:** Standard Material indicator; optional brief success state. |
| [x] | **Respect:** “Reduce motion” / accessibility settings where available. |

---

## 11. Reference URLs (50+ Sources)

### Official Android (developer.android.com)

1. https://developer.android.com/design/ui/mobile  
2. https://developer.android.com/design/ui/mobile/guides/components/material-overview  
3. https://developer.android.com/quality/user-experience  
4. https://developer.android.com/design/ui/mobile/guides/foundations/accessibility  
5. https://developer.android.com/docs/quality-guidelines/core-app-quality  
6. https://developer.android.com/develop/ui/views/animations/overview  
7. https://developer.android.com/develop/ui/views/animations/motionlayout  
8. https://developer.android.com/develop/ui/views/animations/motionlayout/examples  
9. https://developer.android.com/develop/ui/compose/animation/customize  
10. https://developer.android.com/guide/navigation/principles  
11. https://developer.android.com/guide/navigation/design  
12. https://developer.android.com/guide/navigation/design/encapsulate  
13. https://developer.android.com/guide/navigation/use-graph/animate-transitions  
14. https://developer.android.com/develop/ui/views/layout/responsive-adaptive-design-with-views  
15. https://developer.android.com/guide/practices/screens_support  
16. https://developer.android.com/develop/ui/views/layout/use-window-size-classes  
17. https://developer.android.com/develop/ui/views/layout/cardview  
18. https://developer.android.com/develop/ui/compose/components/app-bars  
19. https://developer.android.com/design/ui/mobile/guides/foundations/system-bars  
20. https://developer.android.com/develop/ui/views/haptics  
21. https://developer.android.com/develop/ui/views/haptics/haptics-apis  
22. https://developer.android.com/develop/ui/compose/components/pull-to-refresh  
23. https://developer.android.com/develop/ui/views/touch-and-input/swipe/add-swipe-interface  
24. https://developer.android.com/topic/architecture/ui-layer  
25. https://developer.android.com/topic/architecture/ui-layer/state-production  
26. https://developer.android.com/guide/topics/ui/accessibility/apps  
27. https://developer.android.com/media/camera/camerax/take-photo  

### Material Design & Google

28. https://m3.material.io/styles/motion/overview/how-it-works  
29. https://m3.material.io/components/top-app-bar/guidelines  
30. https://m2.material.io/components/lists  
31. https://developer.android.com/reference/kotlin/androidx/compose/material3/MotionScheme  
32. https://developer.android.com/codelabs/material-motion-android  
33. https://design.google/library/expressive-material-design-google-research  

### Third-Party UX (Empty, Loading, Forms, Navigation)

34. https://ui-deploy.com/blog/complete-guide-to-empty-state-ux-design-turn-nothing-into-something-2025  
35. https://uxdworld.com/best-practices-for-designing-empty-state-in-applications/  
36. https://www.toptal.com/designers/ux/empty-state-ux-design  
37. https://mobbin.com/glossary/empty-state  
38. https://blog.logrocket.com/ux-design/past-present-skeleton-screen/  
39. https://www.nngroup.com/articles/mobile-input-checklist/  
40. https://usepixelform.com/blog/mobile-form-design/  
41. https://www.getworkform.com/blog/mobile-form-design-tips  
42. https://css-tricks.com/better-form-inputs-for-better-mobile-user-experiences/  
43. https://codercrafter.in/blogs/drawer-navigation-explained-best-practices-examples-when-to-use-it  
44. https://blog.appmysite.com/bottom-navigation-bar-in-mobile-apps-heres-all-you-need-to-know/  
45. https://uxdesign.cc/navigation-patterns-in-mobile-applications-how-to-make-the-right-choice-fa3c228e5097  

### Success, Micro-interactions, Offline, Field Sales

46. https://www.pencilandpaper.io/articles/success-ux  
47. https://www.uxpin.com/studio/blog/ultimate-guide-to-microinteractions-in-forms/  
48. https://developers.google.com/open-health-stack/design/offline-sync-guideline  
49. https://web.dev/articles/offline-ux-design-guidelines  
50. https://deltasalesapp.com/glossary/check-incheck-out  
51. https://deltasalesapp.com/blog/ultimate-field-sales-app-checklist  
52. https://mapsly.com/check-in-check-out-with-geofencing  
53. https://createbytes.com/insights/Typography-rules-for-mobile-application  
54. https://source.android.com/docs/core/interaction/haptics/haptics-ux-foundation  

### Additional (Codelabs, AOSP, Libraries, Articles)

55. https://primer.style/ui-patterns/empty-states  
56. https://be-dev.pl/blog/eng/mobile-app-skeleton-screens-better-than-spinners  
57. https://medium.com/@mohitphogat/this-one-ui-decision-makes-apps-feel-10x-faster-even-when-they-arent-be2b541054fe  
58. https://medium.com/google-design/a-primer-on-android-navigation-75e57d9d63fe  
59. https://www.sevensquaretech.com/which-mobile-navigation-patterns-works-what-fails/  
60. https://apps.theodo.com/article/the-essential-checklist-for-integrating-camera-functions-in-mobile-apps  
61. https://moldstud.com/articles/p-mastering-mobile-transitions-essential-animation-techniques-for-cross-platform-apps  

---

## 12. Implementation Priorities (Summary)

| Done | Priority | Item |
|------|----------|------|
| [x] | **High** | **Empty state** on Main (no outlets): illustration + “No outlets assigned” + action. |
| [x] | **High** | **Loading state** on Main: skeleton cards or spinner + “Loading outlets…”. |
| [x] | **High** | **Error state** on Main: message + “Retry” button. |
| [x] | **High** | **Pending sync** visibility (banner when count > 0). [x] Optional “Sync now”. |
| [x] | **High** | **User name** on Main (optional): “Hello, [name]” from `/user`. |
| [x] | **High** | Login **error inline**. |
| [x] | **High** | Check-in **success toast** + finish. [x] Location/photo **success feedback** (e.g. “Location captured”, thumbnail). |
| [x] | **High** | **Sync success** feedback (toast or indicator when sync completes). |
| [x] | **Medium** | **Screen transitions** (200–400 ms). |
| [x] | **Medium** | **List stagger** on outlet load. |
| [x] | **Medium** | **Pull-to-refresh** on Main. |
| [x] | **Medium** | Optional **button haptics**. |
| [x] | **Future** | **Geofence “Check in?”** in-app prompt (dialog/bottom sheet). |
| [ ] | **Future** | Today’s visits, DCR summary, manager dashboard (when API exists). |

This plan keeps the app aligned with **Google mobile UI design and interactions** and turns existing system data into **clear, purposeful information** for the field rep, with room to extend to managers and more data later.
