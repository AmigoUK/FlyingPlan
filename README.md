# FlyingPlan

**Drone flight management system** -- from customer request to mission delivery.

FlyingPlan handles the entire commercial drone workflow: customers submit flight requests, you plan missions with professional-grade tools, assign pilots, and they carry out flights with full UK CAA-compliant safety checks. Everything is tracked, documented, and exportable.

Built for UK drone operators who need to manage jobs, stay legally compliant, and deliver professional reports to clients.

### Feature Highlights

- **End-to-end workflow** -- customer request, route planning, pilot assignment, risk assessment, flight, delivery
- **Mission planning tools** -- grid planner, orbit/spiral/cable cam patterns, oblique grids, facade scanner
- **8 DJI drone profiles** -- Mini 4 Pro, Mini 5 Pro, Mavic 3/3 Pro/3 Classic, Mavic 4 Pro, Air 3, Air 3S
- **UK CAA category engine** -- automated Open A1/A2/A3 and Specific category determination
- **Live weather** -- 48-hour forecast with drone-specific wind warnings
- **Airspace awareness** -- UK FRZ and controlled airspace overlays on the map
- **Terrain & elevation** -- elevation profiles, terrain-following mode, 3D terrain mesh
- **Photogrammetry** -- GSD calculator, coverage heatmap, quality reports, photo positions
- **3D visualisation** -- Three.js preview with terrain mesh and camera frustum cones
- **Multi-format export** -- KMZ (DJI), KML, GeoJSON, CSV, GPX, Litchi CSV, enhanced GeoJSON
- **KMZ import** -- load existing DJI mission files with auto drone detection
- **Mission sharing** -- public links with configurable token expiry
- **PDF reports** -- branded flight reports with static maps, risk assessments, and activity logs
- **Dark mode** -- system-wide toggle
- **Guide mode** -- contextual tooltips for new users

---

## Table of Contents

- [How It Works -- The Complete Workflow](#how-it-works----the-complete-workflow)
  - [Step 1: Customer Submits a Flight Request](#step-1-customer-submits-a-flight-request)
  - [Step 2: Admin Reviews the Request](#step-2-admin-reviews-the-request)
  - [Step 3: Admin Plans the Flight Route](#step-3-admin-plans-the-flight-route)
  - [Step 4: Admin Creates an Order and Assigns a Pilot](#step-4-admin-creates-an-order-and-assigns-a-pilot)
  - [Step 5: Pilot Accepts the Job](#step-5-pilot-accepts-the-job)
  - [Step 6: Pilot Completes the Pre-Flight Risk Assessment](#step-6-pilot-completes-the-pre-flight-risk-assessment)
  - [Step 7: Pilot Flies the Mission](#step-7-pilot-flies-the-mission)
  - [Step 8: Review, Delivery, and Closure](#step-8-review-delivery-and-closure)
- [Mission Planning Tools](#mission-planning-tools)
- [Map and Visualisation Features](#map-and-visualisation-features)
- [Weather and Airspace](#weather-and-airspace)
- [Drone Support and Exports](#drone-support-and-exports)
- [UK CAA Compliance](#uk-caa-compliance)
- [Reports and Exports](#reports-and-exports)
- [Managing Your Pilots](#managing-your-pilots)
- [Settings and Branding](#settings-and-branding)
- [Order Status Reference](#order-status-reference)
- [Who Can Do What -- User Roles](#who-can-do-what----user-roles)
- [Installation](#installation)
  - [PHP Version (Shared Hosting)](#php-version-shared-hosting)
  - [Python Version (Original)](#python-version-original)
- [Running Tests](#running-tests)
- [Demo Data](#demo-data)
- [Default Login Credentials](#default-login-credentials)
- [Tech Stack](#tech-stack)
- [Licence](#licence)

---

## How It Works -- The Complete Workflow

Below is the full journey of a drone job, from the moment your customer fills in the request form to the final delivery. Each step includes screenshots so you can see exactly what happens on screen.

> **Tip:** Click any screenshot thumbnail to see the full-size image.

---

### Step 1: Customer Submits a Flight Request

Your customer visits your website and fills in a **5-step form** to describe what they need:

1. **Their Details** -- name, email, phone, company
2. **Job Brief** -- what type of drone work (e.g. aerial photography, roof inspection, land survey), description, preferred dates, urgency
3. **Location** -- they search for an address and drop a pin on the map. They can draw a polygon to mark the exact flight area and add points of interest (POIs)
4. **Flight Preferences** -- altitude, camera angle, video resolution, photo mode, any no-fly zone notes or privacy concerns
5. **Review and Submit** -- they check everything, tick the consent box, and optionally attach files (photos, PDFs, documents)

After submitting, the customer receives a unique reference number (e.g. `FP-20260316-1006`) and sees a confirmation page.

<p>
<a href="screenshots/customer-form-1.png"><img src="screenshots/customer-form-1.png" width="400" alt="Customer form - Step 1: Your Details"></a>
<a href="screenshots/customer-form-2.png"><img src="screenshots/customer-form-2.png" width="400" alt="Customer form - Step 2: Job Brief"></a>
</p>
<p>
<a href="screenshots/customer-form-3.png"><img src="screenshots/customer-form-3.png" width="400" alt="Customer form - Step 3: Location with map"></a>
<a href="screenshots/customer-form-4.png"><img src="screenshots/customer-form-4.png" width="400" alt="Customer form - Step 4: Flight Preferences"></a>
</p>
<p>
<a href="screenshots/customer-form-5.png"><img src="screenshots/customer-form-5.png" width="400" alt="Customer form - Step 5: Review and Submit"></a>
<a href="screenshots/customer-form-submited.png"><img src="screenshots/customer-form-submited.png" width="400" alt="Confirmation page with reference number"></a>
</p>

*The 5-step customer form wizard. Customers fill in their details, describe the job, pick a location on the map, set flight preferences, and submit. They get a unique reference number on the confirmation page.*

---

### Step 2: Admin Reviews the Request

When you log in as an Admin or Manager, you see the **Admin Dashboard** -- a list of all submitted flight requests. You can filter by status (New, In Review, Route Planned, etc.) and search by customer name, email, or reference number.

Click on any flight plan to see the full details of what the customer requested.

<p>
<a href="screenshots/dashboard-admin.png"><img src="screenshots/dashboard-admin.png" width="600" alt="Admin Dashboard showing flight plan list"></a>
</p>

*The admin dashboard lists all customer submissions. Each row shows the reference number, customer name, job type, status, and creation date.*

---

### Step 3: Admin Plans the Flight Route

From the flight plan detail page, you plan the drone's exact route using an **interactive map** with a full suite of planning tools:

- **Click on the map** to add waypoints (the points the drone will fly to)
- **Set parameters** for each waypoint: altitude, speed, heading (direction), gimbal (camera) pitch
- **Add Points of Interest (POIs)** -- the drone can be told to face these while flying
- **Use automated patterns** -- generate grid, orbit, spiral, cable cam, oblique grid, facade scan, or multi-orbit patterns in one click (see [Mission Planning Tools](#mission-planning-tools))
- **Import existing missions** -- upload a KMZ file from DJI Fly and the waypoints are loaded automatically, with drone model auto-detection
- **Check weather and airspace** -- live weather panel and UK airspace overlay are available directly on the planning page
- **View terrain** -- elevation profiles and 3D terrain preview help you plan around hills and obstacles
- **Export in any format** -- KMZ (DJI), KML, GeoJSON, CSV, GPX, or Litchi CSV

<p>
<a href="screenshots/task-waypoints.png"><img src="screenshots/task-waypoints.png" width="400" alt="Flight plan with waypoints on the map"></a>
<a href="screenshots/task-waypoints-edits.png"><img src="screenshots/task-waypoints-edits.png" width="400" alt="Editing waypoint parameters"></a>
</p>
<p>
<a href="screenshots/poi-label.png"><img src="screenshots/poi-label.png" width="400" alt="Points of Interest on the map"></a>
</p>

*Left: the interactive map showing the planned flight route with numbered waypoints. Right: editing individual waypoint parameters (altitude, speed, heading). Bottom: Points of Interest (POIs) marked on the map with labels.*

---

### Step 4: Admin Creates an Order and Assigns a Pilot

Once the route is planned, you create an **Order** from the flight plan. This is where you:

- **Choose a pilot** from your team
- **Set a scheduled date and time** for the flight
- **Add any notes** for the pilot (special instructions, access codes, customer contact, etc.)
- **Select flight context** for the UK CAA category engine -- time of day, proximity to people/buildings, airspace type, VLOS type, and speed mode. The system automatically determines the operational category (see [UK CAA Compliance](#uk-caa-compliance))

The pilot immediately sees the new job in their dashboard.

<p>
<a href="screenshots/assign-pilot.png"><img src="screenshots/assign-pilot.png" width="400" alt="Create order button on flight plan"></a>
<a href="screenshots/assign-pilot-form.png"><img src="screenshots/assign-pilot-form.png" width="400" alt="Pilot assignment form"></a>
</p>
<p>
<a href="screenshots/assigning-pilot-admin-view.png"><img src="screenshots/assigning-pilot-admin-view.png" width="600" alt="Admin view after assigning pilot"></a>
</p>

*Top left: the option to create an order from a flight plan. Top right: the pilot assignment form where you pick a pilot and set the schedule. Bottom: the admin view showing the order with the assigned pilot.*

---

### Step 5: Pilot Accepts the Job

The pilot logs in and sees their **Pilot Dashboard** with all assigned jobs. They can:

- **View the job details** -- customer info, job brief, flight preferences, location map
- **Accept** the order (they're happy to do it)
- **Decline** the order (with a reason -- the admin can then reassign to another pilot)

<p>
<a href="screenshots/pilot-dashboard.png"><img src="screenshots/pilot-dashboard.png" width="400" alt="Pilot dashboard showing assigned orders"></a>
<a href="screenshots/pilot-view-my-orders.png"><img src="screenshots/pilot-view-my-orders.png" width="400" alt="Pilot viewing their orders list"></a>
</p>
<p>
<a href="screenshots/accept-job-order-by-pilot.png"><img src="screenshots/accept-job-order-by-pilot.png" width="400" alt="Pilot accepting a job order"></a>
<a href="screenshots/order-accepted.png"><img src="screenshots/order-accepted.png" width="400" alt="Order accepted confirmation"></a>
</p>

*Top: the pilot's dashboard and orders list. Bottom left: the pilot reviewing and accepting a job. Bottom right: the order is now marked as accepted.*

---

### Step 6: Pilot Completes the Pre-Flight Risk Assessment

**This is a mandatory safety step required by UK CAA regulations.** Before the pilot can start flying, they must complete a **28-point pre-flight risk assessment** on-site. This is not optional -- the system will not let the pilot proceed without it.

The risk assessment is **category-aware** -- if the order has been assigned an operational category (Open A1/A2/A3 or Specific), the system shows additional category-specific sections:

| Section | What It Checks |
|---------|---------------|
| **Site Assessment** | Ground hazards, obstacles, safe distance from people and buildings |
| **Airspace Check** | Flight restriction zones, restricted airspace, NOTAMs, max altitude |
| **Weather Assessment** | Wind speed and direction, visibility, precipitation, temperature |
| **Equipment Check** | Drone condition, battery level, propellers, GPS lock, remote control, Remote ID |
| **Pilot Fitness (IMSAFE)** | Illness, medication, stress, alcohol, fatigue, nutrition |
| **Permissions & Compliance** | Flyer ID, Operator ID, insurance, authorisations |
| **Emergency Procedures** | Emergency landing site, contacts, contingency plan |
| **Night Flying** *(if applicable)* | Green flashing light fitted, switched on, VLOS maintainable, orientation visible |
| **A2 Assessment** *(if Open A2)* | Distance from uninvolved people confirmed, low-speed mode, segregation |
| **A3 Assessment** *(if Open A3)* | 150m from residential/commercial areas, 50m from people, 50m from buildings |
| **Specific Ops** *(if Specific)* | Operations manual reviewed, insurance confirmed, OA valid |

The pilot also sets **flight parameters** (altitude, speed) and selects a **risk level** (Low / Medium / High) and a **decision**:

- **Proceed** -- all clear, the flight can go ahead
- **Proceed with Mitigations** -- some concerns noted, but manageable (pilot writes mitigation notes)
- **Abort** -- conditions are unsafe, the flight cannot happen (the system blocks the pilot from starting)

The pilot's **GPS location is automatically captured** by the browser, proving the assessment was done on-site.

<p>
<a href="screenshots/flight-parameters.png"><img src="screenshots/flight-parameters.png" width="400" alt="Setting flight parameters"></a>
<a href="screenshots/risk-assesment-form.png"><img src="screenshots/risk-assesment-form.png" width="400" alt="Risk assessment form with checklist"></a>
</p>
<p>
<a href="screenshots/after-set-flight-paramiters-risk-assesment.png"><img src="screenshots/after-set-flight-paramiters-risk-assesment.png" width="600" alt="After completing flight parameters and risk assessment"></a>
</p>

*Top left: setting flight parameters before the mission. Top right: the 28-point risk assessment checklist. Bottom: the completed assessment with flight parameters set -- the pilot can now proceed to fly.*

---

### Step 7: Pilot Flies the Mission

Once the risk assessment is passed, the pilot:

1. **Starts the flight** -- the order status changes to "In Progress"
2. **Loads the mission file** into their DJI Fly app (KMZ), Litchi app (CSV), or any compatible flight controller
3. **Checks the live weather panel** -- current conditions and 48-hour forecast with wind warnings specific to their drone model
4. **Flies the mission** and captures footage/photos
5. **Uploads deliverables** -- videos, photos, PDFs, ZIP files (up to 32 MB each)
6. **Marks the flight as complete**

<p>
<a href="screenshots/order-details.png"><img src="screenshots/order-details.png" width="400" alt="Order details showing flight in progress"></a>
<a href="screenshots/pilot-order-dv.png"><img src="screenshots/pilot-order-dv.png" width="400" alt="Pilot order detail view"></a>
</p>

*Left: order details during an active flight. Right: the pilot's view of the order with upload and status controls.*

---

### Step 8: Review, Delivery, and Closure

After the flight:

1. **Pilot marks "Flight Complete"** and uploads all deliverables
2. **Pilot marks "Delivered"** when all files are uploaded
3. **Admin reviews** the deliverables and the risk assessment report
4. **Admin closes the order** -- the job is done

Every status change is automatically logged in the **Activity Log**, creating a full audit trail of who did what and when.

<p>
<a href="screenshots/pilot-dashboard-to-review.png"><img src="screenshots/pilot-dashboard-to-review.png" width="400" alt="Pilot dashboard showing orders ready for review"></a>
<a href="screenshots/order-done-for-review-admin-view.png"><img src="screenshots/order-done-for-review-admin-view.png" width="400" alt="Admin view of completed order ready for review"></a>
</p>

*Left: the pilot's dashboard showing completed flights waiting for admin review. Right: the admin's view of a completed order with deliverables and the full activity log.*

---

## Mission Planning Tools

FlyingPlan includes automated pattern generators that create optimised waypoint routes for common drone operations. Each tool generates waypoints with appropriate altitudes, speeds, headings, and camera angles -- ready to export as a DJI-compatible KMZ or any other format.

### Waypoint Editor

Click on the map to add waypoints manually. Each waypoint has configurable parameters: altitude, speed, heading, gimbal pitch, turn mode, hover time, and optional POI targeting.

**When to use:** Custom routes, cinematic flight paths, simple inspection runs.

### Grid Planner

<p>
<a href="screenshots/grid-planner.png"><img src="screenshots/grid-planner.png" width="400" alt="Grid planner generating survey pattern over a polygon area"></a>
<a href="screenshots/orbit-pattern.png"><img src="screenshots/orbit-pattern.png" width="400" alt="Orbit pattern generator circling a point of interest"></a>
</p>
<p>
<a href="screenshots/spiral-pattern.png"><img src="screenshots/spiral-pattern.png" width="400" alt="Spiral pattern ascending around a structure"></a>
<a href="screenshots/cable-cam.png"><img src="screenshots/cable-cam.png" width="400" alt="Cable cam linear path between two points"></a>
</p>
<p>
<a href="screenshots/oblique-grid.png"><img src="screenshots/oblique-grid.png" width="400" alt="Oblique grid planner with multi-angle passes"></a>
<a href="screenshots/facade-scanner.png"><img src="screenshots/facade-scanner.png" width="400" alt="Facade scanner generating inspection waypoints on a building face"></a>
</p>
<p>
<a href="screenshots/terrain-following.png"><img src="screenshots/terrain-following.png" width="400" alt="Terrain following mode adjusting altitudes to maintain constant AGL"></a>
<a href="screenshots/multi-orbit.png"><img src="screenshots/multi-orbit.png" width="400" alt="Multi-orbit pattern with stacked altitude layers"></a>
</p>

*Top row: grid planner and orbit pattern. Second row: spiral pattern and cable cam. Third row: oblique grid and facade scanner. Bottom row: terrain following and multi-orbit.*

Generates parallel flight lines across a polygon area. Supports configurable line spacing, altitude, speed, and flight direction. Crosshatch mode adds a second perpendicular pass.

**When to use:** Land surveys, orthomosaic captures, crop monitoring, site documentation.
**Why it matters:** Ensures consistent overlap coverage across the entire area, eliminating gaps in mapping data.

### Orbit Pattern

Creates a circular flight path around a point of interest. All waypoints face inward toward the centre. Configurable radius, altitude, number of points, and speed.

**When to use:** Building inspections, monument or structure documentation, 3D model captures.
**Why it matters:** Provides even coverage from all angles, producing complete 360-degree documentation.

### Spiral Pattern

An ascending or descending circular path around a centre point. Altitude changes progressively across revolutions, with configurable start/end altitude and number of revolutions.

**When to use:** Tall structures (towers, chimneys, wind turbines), multi-level inspections.
**Why it matters:** Captures data at multiple heights in a single continuous flight, covering vertical structures efficiently.

### Cable Cam

Creates a smooth linear path between two points. Evenly spaced waypoints at a constant altitude, ideal for repeatable straight-line flights.

**When to use:** Cinematic shots along a road or river, construction progress monitoring, power line inspections.
**Why it matters:** Produces smooth, repeatable footage along a defined line.

### Multi-Orbit

Stacked circular orbits at different altitudes around the same centre point. Each orbit layer captures the subject from a different vertical angle.

**When to use:** Comprehensive 3D reconstruction, complex structure documentation.
**Why it matters:** Vertical coverage at multiple heights produces far better 3D models than a single orbit.

### Oblique Grid

Advanced grid patterns with multiple camera angles. Supports nadir (straight-down), oblique (angled), double grid (two perpendicular passes), and multi-angle configurations.

**When to use:** Professional photogrammetry, 3D city mapping, detailed terrain modelling.
**Why it matters:** Accurate 3D models require images from multiple angles -- oblique captures provide the side views that nadir-only grids miss.

### Facade Scanner

Generates waypoint patterns for inspecting building faces. Can scan a single facade (defined by two points) or all faces of a building (from a polygon outline). Waypoints are positioned at multiple heights, facing the building surface.

**When to use:** Building envelope inspections, structural surveys, heritage documentation, asset condition reports.
**Why it matters:** Systematic vertical coverage ensures every part of a building face is captured without gaps.

### Terrain Following

Adjusts waypoint altitudes to maintain a constant height above ground level (AGL). Uses elevation data from the Open-Meteo API to compensate for terrain variations.

**When to use:** Survey flights over hilly or undulating terrain.
**Why it matters:** Maintains consistent ground sampling distance (GSD) across elevation changes, preventing areas of degraded image resolution.

### Path Tools

Refine planned routes without starting from scratch:

- **Reverse** -- flip the waypoint order (fly the route backwards)
- **Duplicate** -- copy an entire flight plan with all waypoints to create a variant
- **Import KMZ** -- load waypoints from an existing DJI mission file with automatic drone model detection

---

## Map and Visualisation Features

### Map Controls

- **Satellite / Street toggle** -- switch between satellite imagery and street map views
- **Measurement tools** -- measure distances and areas directly on the map
- **Polygon drawing** -- draw the flight area boundary for grid-based planning tools

### UK Airspace Overlay

Displays Flight Restriction Zones (FRZs), controlled airspace (CTRs), and advisory zones on the map. When waypoints fall inside restricted zones, the system flags which zones are violated.

**When to use:** Every flight -- checking airspace is a legal requirement under UK CAA regulations.
**Why it matters:** Flying in an FRZ or controlled airspace without permission is a criminal offence. The overlay makes it immediately visible whether your route is clear.

### Elevation Profile

Shows a terrain chart along the flight path, displaying ground elevation at each waypoint alongside the planned flight altitude. Calculates both AMSL (above mean sea level) and AGL (above ground level) for every point.

**When to use:** Planning flights in hilly areas or near terrain features.
**Why it matters:** A flat altitude plan over rising terrain can result in dangerously low clearance or inconsistent image quality.

### Coverage Heatmap

Visualises photo overlap across the flight area based on waypoint positions, camera parameters, and altitude. Shows where coverage is strong and where gaps exist.

**When to use:** After generating any survey pattern (grid, oblique, orbit).
**Why it matters:** Photogrammetry software needs sufficient overlap (typically 70-80%) to stitch images. The heatmap reveals gaps before you fly.

### 3D Preview

A Three.js-powered 3D view showing the terrain mesh, flight path, and camera frustum cones. Rotate, zoom, and pan to inspect the mission from any angle.

**When to use:** Complex terrain missions, client presentations, verifying camera coverage on structures.
**Why it matters:** A 2D map cannot show whether the drone will have line of sight or adequate camera angles. The 3D preview catches problems that are invisible on a flat map.

### GSD Calculator

Calculates ground sampling distance (cm/pixel) based on the selected drone's sensor, focal length, and flight altitude. Includes a flight planning assistant that recommends altitude based on desired GSD.

**When to use:** Photogrammetry and survey jobs where image resolution requirements are specified.
**Why it matters:** Clients often specify a required GSD (e.g. 2 cm/px for a land survey). The calculator tells you exactly what altitude to fly at.

<p>
<a href="screenshots/airspace-overlay.png"><img src="screenshots/airspace-overlay.png" width="400" alt="UK airspace overlay showing FRZ and controlled zones on the map"></a>
<a href="screenshots/elevation-profile.png"><img src="screenshots/elevation-profile.png" width="400" alt="Elevation profile chart along the flight path"></a>
</p>
<p>
<a href="screenshots/coverage-heatmap.png"><img src="screenshots/coverage-heatmap.png" width="400" alt="Coverage heatmap showing photo overlap across the flight area"></a>
<a href="screenshots/3d-preview.png"><img src="screenshots/3d-preview.png" width="400" alt="3D terrain preview with flight path and camera frustum cones"></a>
</p>
<p>
<a href="screenshots/gsd-calculator.png"><img src="screenshots/gsd-calculator.png" width="600" alt="GSD calculator with flight planning assistant"></a>
</p>

*Top row: UK airspace overlay and elevation profile. Middle row: coverage heatmap and 3D terrain preview. Bottom: GSD calculator.*

---

## Weather and Airspace

### Live Weather Panel

Pulls a 48-hour forecast from the Open-Meteo API for the flight location. Displays:

- **Current conditions** -- temperature, wind speed, wind direction, gusts, precipitation, cloud cover, visibility
- **Hourly forecast** -- 48 hours of data with the same parameters, plus precipitation probability

No API key required -- uses the free Open-Meteo service.

**When to use:** Before every flight and during pre-flight planning.
**Why it matters:** Weather is the leading cause of drone incidents. Live data lets you make informed go/no-go decisions.

### Drone-Specific Wind Warnings

The weather panel checks current conditions against the selected drone's specifications. Warnings are generated when:

- Wind speed exceeds the drone's maximum wind resistance
- Wind speed is above 80% of the limit (caution zone)
- Gusts exceed 120% of the drone's wind limit
- Precipitation is detected (most consumer drones are not waterproof)
- Visibility drops below VLOS thresholds (1,000m danger, 3,000m caution)

Each drone profile has its own wind limit (e.g. Mini 4 Pro: 10.7 m/s, Mavic 3: 12 m/s), so warnings are tailored to the aircraft being used.

### Airspace Checker

The airspace system loads UK airspace restriction data (bundled GeoJSON) and checks every waypoint against restricted zones. Results include:

- **FRZ (Flight Restriction Zones)** -- airport protection zones, typically 5 km radius
- **CTR (Controlled Airspace)** -- airspace under ATC control
- **Zone details** -- name, classification, upper/lower limits, distance from zone centre

**When to use:** During route planning, before exporting the mission.
**Why it matters:** UK CAA requires a pre-flight airspace check. The overlay and automated checks make this fast and reliable.

<p>
<a href="screenshots/weather-panel.png"><img src="screenshots/weather-panel.png" width="600" alt="Live weather panel showing current conditions and 48-hour forecast with drone-specific wind warnings"></a>
</p>

*The live weather panel with current conditions, hourly forecast, and drone-specific wind warnings.*

---

## Drone Support and Exports

### Supported Drone Profiles

FlyingPlan generates KMZ mission files with the correct DJI enum values for each drone. Select the drone model on the flight plan detail page -- all exports, GSD calculations, and wind warnings adjust automatically.

| Drone | Max Wind | Max Speed | Max Flight Time | Sensor |
|-------|----------|-----------|----------------|--------|
| DJI Mini 4 Pro | 10.7 m/s | 16 m/s | 34 min | 1/1.3" (48 MP) |
| DJI Mini 5 Pro | 10.7 m/s | 16 m/s | 37 min | 1/1.3" (48 MP) |
| DJI Mavic 3 | 12 m/s | 21 m/s | 46 min | 4/3" (20 MP) |
| DJI Mavic 3 Pro | 12 m/s | 21 m/s | 43 min | 4/3" (20 MP) |
| DJI Mavic 3 Classic | 12 m/s | 21 m/s | 46 min | 4/3" (20 MP) |
| DJI Mavic 4 Pro | 12 m/s | 21 m/s | 46 min | 4/3" (20 MP) |
| DJI Air 3 | 12 m/s | 19 m/s | 46 min | 1/1.3" (48 MP) |
| DJI Air 3S | 12 m/s | 19 m/s | 45 min | 1" (48 MP) |

### Export Formats

| Format | File | Use Case |
|--------|------|----------|
| **KMZ** | `FP-XXXXXXXX-XXXX.kmz` | Load directly into DJI Fly app for automated waypoint missions |
| **KML** | `.kml` | Google Earth visualisation and route sharing |
| **GeoJSON** | `.geojson` | GIS software, web mapping, data analysis |
| **Enhanced GeoJSON** | `_enhanced.geojson` | GeoJSON with drone metadata, camera parameters, and coverage info |
| **CSV** | `.csv` | Spreadsheets, custom processing, data import |
| **GPX** | `.gpx` | GPS devices, outdoor mapping apps, route tracking |
| **Litchi CSV** | `_litchi.csv` | Litchi flight controller app (alternative to DJI Fly) |
| **Photo Positions CSV** | `_photo_positions.csv` | Predicted photo capture positions for post-processing |

### KMZ Import

Upload an existing DJI KMZ mission file and FlyingPlan will:

1. Parse the wayline data (coordinates, altitude, speed, heading, gimbal pitch)
2. Auto-detect the drone model from the KMZ metadata
3. Load all waypoints onto the map, ready for editing or re-export

**When to use:** Migrating missions from DJI Fly, modifying existing routes, converting between formats.

### Mission Sharing

Generate a shareable public link for any flight plan. The link shows the mission map and details without requiring login. Links include a security token and configurable expiry (default: 30 days).

**When to use:** Sharing mission plans with clients, stakeholders, or subcontracted pilots who don't have system accounts.

<p>
<a href="screenshots/export-formats.png"><img src="screenshots/export-formats.png" width="400" alt="Export format options showing KMZ, KML, GeoJSON, CSV, GPX, and Litchi"></a>
<a href="screenshots/kmz-import.png"><img src="screenshots/kmz-import.png" width="400" alt="KMZ import with auto drone detection"></a>
</p>
<p>
<a href="screenshots/mission-sharing.png"><img src="screenshots/mission-sharing.png" width="600" alt="Mission sharing dialog with public link generation"></a>
</p>

*Top left: export format options. Top right: KMZ import with auto drone detection. Bottom: mission sharing link generation.*

---

## UK CAA Compliance

### Category Engine

FlyingPlan includes an automated UK CAA operational category determination engine based on:

- **UK Reg EU 2019/947** -- UAS operations regulation
- **ANO 2016** -- Air Navigation Order
- **CAP 722** -- CAA guidance on UAS operations
- **CAP 3017** -- Category determination guidance

The engine takes three inputs -- drone profile, pilot qualifications, and flight parameters -- and determines the correct operational category:

| Category | When It Applies |
|----------|----------------|
| **Open A1** | C0 drones or legacy drones under 250g. C1 drones under 900g. May fly close to people. |
| **Open A2** | C2 drones under 4 kg with A2 CofC. Legacy drones under 2 kg with A2 CofC. 30m or 5m (low-speed) from people. |
| **Open A3** | Everything else under 25 kg. 150m from residential areas, 50m from people and buildings. |
| **Specific (PDRA-01)** | BVLOS, over assemblies, or controlled airspace. Requires PDRA-01 authorisation. |
| **Specific (SORA)** | Complex operations requiring full SORA risk assessment and OA from the CAA. |
| **Certified** | Drones 25 kg or over. Out of scope for this system. |

The engine also handles:

- **Night flying checks** -- verifies green flashing light is configured, warns if external light weight pushes MTOM over a category boundary
- **Registration requirements** -- determines Flyer ID, Operator ID, Remote ID, and insurance requirements
- **Proximity validation** -- checks that minimum distances are met for the determined category
- **Upgrade suggestions** -- advises when an A2 CofC would unlock a less restrictive category

### Pilot Qualifications

Pilots can record their UK regulatory credentials:

| Field | Description |
|-------|-------------|
| **Flyer ID** | CAA registration as a drone operator (with expiry date) |
| **Operator ID** | CAA registration for the responsible organisation (with expiry date) |
| **A2 CofC** | Certificate of Competency for Open A2 operations (certificate number, expiry) |
| **GVC Level** | General VLOS Certificate -- levels include GVC, RPC L1 through RPC L4 |
| **GVC Certificate** | Certificate number, multi-rotor/fixed-wing expiry dates |
| **Operational Authorisation** | OA type (PDRA-01 or Full SORA), reference number, expiry |
| **Practical Competency** | Date of practical assessment, mentor/examiner name |
| **Article 16** | Acknowledgement of Article 16 obligations (with agreement date) |

### Equipment Registry

Pilot equipment records include drone-specific fields for category determination:

| Field | Description |
|-------|-------------|
| **Class Mark** | C0, C1, C2, C3, C4, or Legacy |
| **MTOM (grams)** | Maximum Take-Off Mass, including any external accessories |
| **Has Camera** | Whether the drone carries a camera (affects registration requirements) |
| **Green Light** | Type (built-in, external, none) and weight for night flying |
| **Low Speed Mode** | Whether the drone supports a low-speed mode (affects A2 minimum distances) |
| **Remote ID** | Whether the drone is Remote ID capable (mandatory from 2028) |

### Risk Assessment

The pre-flight risk assessment adapts based on the determined operational category. In addition to the standard 28-point checklist (7 core sections), category-specific sections are added:

- **Night Flying** -- green light fitted and on, VLOS maintainable, orientation visible (when flying at night/twilight)
- **A2 Assessment** -- distance from uninvolved people confirmed, low-speed mode active if required, segregation assessed (when Open A2)
- **A3 Assessment** -- 150m from residential/commercial areas, 50m from people, 50m from buildings (when Open A3)
- **Specific Operations** -- operations manual reviewed, insurance confirmed, OA valid (when Specific category)

**Why this matters:** UK commercial drone operators are legally required to understand their operational category and comply with its specific conditions. The category engine automates this determination, reducing the risk of operating in the wrong category.

<p>
<a href="screenshots/category-engine.png"><img src="screenshots/category-engine.png" width="400" alt="CAA category engine showing operational category determination"></a>
<a href="screenshots/pilot-qualifications.png"><img src="screenshots/pilot-qualifications.png" width="400" alt="Pilot qualifications panel with UK regulatory credentials"></a>
</p>
<p>
<a href="screenshots/equipment-registry.png"><img src="screenshots/equipment-registry.png" width="600" alt="Equipment registry showing class marks, MTOM, and Remote ID fields"></a>
</p>

*Top left: the category engine determining the operational category. Top right: pilot qualifications panel. Bottom: equipment registry with class marks and Remote ID capability.*

---

## Reports and Exports

### PDF Flight Reports

A detailed PDF report is generated for each order, containing:
- Customer and job details
- Flight plan summary with a static map image
- Risk assessment results (including GPS coordinates and weather data)
- Full activity log

Reports are branded with your business name, logo, and primary colour from the settings page.

<p>
<a href="screenshots/flying-plan-report.png"><img src="screenshots/flying-plan-report.png" width="400" alt="Flight plan report page 1"></a>
<a href="screenshots/flying-plan-report2.png"><img src="screenshots/flying-plan-report2.png" width="400" alt="Flight plan report page 2"></a>
</p>
<p>
<a href="screenshots/FlyingPlanReportPDF1.png"><img src="screenshots/FlyingPlanReportPDF1.png" width="250" alt="PDF report page 1"></a>
<a href="screenshots/FlyingPlanReportPDF2.png"><img src="screenshots/FlyingPlanReportPDF2.png" width="250" alt="PDF report page 2"></a>
<a href="screenshots/FlyingPlanReportPDF3.png"><img src="screenshots/FlyingPlanReportPDF3.png" width="250" alt="PDF report page 3"></a>
</p>

*Top: flight plan report in the app. Bottom: exported PDF report pages showing order details, risk assessment, and activity log.*

### KMZ Mission Files (DJI Compatible)

FlyingPlan exports **KMZ mission files** that you can load directly into the **DJI Fly app** on any supported DJI drone. You can also import them into **Google Earth Pro** to visualise the flight path before the mission.

<p>
<a href="screenshots/FP-20260316-1006-google-kmz.png"><img src="screenshots/FP-20260316-1006-google-kmz.png" width="400" alt="KMZ file opened in Google Earth Pro"></a>
<a href="screenshots/google-pro-with-imported-kmz.png"><img src="screenshots/google-pro-with-imported-kmz.png" width="400" alt="Google Earth Pro showing imported KMZ mission"></a>
</p>

*KMZ mission files imported into Google Earth Pro, showing the planned flight route and waypoints on satellite imagery.*

### Quality Reports

Photogrammetry quality reports analyse your mission plan and provide:
- Estimated photo count and spacing
- Overlap and sidelap percentages
- GSD (ground sampling distance) at the planned altitude
- Coverage quality assessment

### Photo Positions Export

Export predicted photo capture positions as a CSV file. Each row includes latitude, longitude, altitude, and camera parameters -- useful for importing into photogrammetry software or verifying coverage.

### Enhanced GeoJSON

An enriched GeoJSON export that includes drone metadata, camera parameters, sensor specifications, and coverage information alongside the standard waypoint geometry. Useful for GIS analysis and custom post-processing pipelines.

<p>
<a href="screenshots/quality-report.png"><img src="screenshots/quality-report.png" width="600" alt="Photogrammetry quality report with coverage analysis"></a>
</p>

*Quality report showing estimated photo count, overlap percentages, and GSD analysis.*

---

## Managing Your Pilots

As an admin, you manage your pilot team from the **Pilot Management** section:

- **View all pilots** with their availability status (Available / On Mission / Unavailable)
- **Create pilot profiles** with contact details, address, and bio
- **Regulatory credentials** -- Flyer ID, Operator ID, A2 CofC (with certificate numbers and expiry dates), GVC level and certificate details, Operational Authorisation type and reference
- **Track certifications** -- licence name, issuing body, certificate number, issue and expiry dates
- **Register equipment** -- drone models, serial numbers, registration IDs, class marks (C0-C4), MTOM, camera status, green light configuration, low-speed mode, Remote ID capability
- **Upload documents** -- certificates, insurance policies, licences (with expiry date tracking)

Pilots can also **manage their own profile** from the Pilot Portal, including updating their details, qualifications, adding certifications, and uploading documents.

<p>
<a href="screenshots/admin-pilot-lv.png"><img src="screenshots/admin-pilot-lv.png" width="400" alt="Admin pilot list view"></a>
<a href="screenshots/admin-pilot-dv.png"><img src="screenshots/admin-pilot-dv.png" width="400" alt="Admin pilot detail view"></a>
</p>
<p>
<a href="screenshots/pilot-my-profile-view.png"><img src="screenshots/pilot-my-profile-view.png" width="600" alt="Pilot self-service profile page"></a>
</p>

*Top left: the pilot list showing all team members and their status. Top right: a pilot's profile with certifications, equipment, and documents. Bottom: the pilot's own profile page where they can update their details.*

---

## Settings and Branding

Admins can customise the system from the **Settings** page:

- **Branding** -- your business name, logo URL, primary colour picker, tagline, and contact email
- **Dark Mode** -- system-wide dark theme toggle, saved as a global setting
- **Guide Mode** -- enable contextual tooltips and help prompts for new users
- **Form Visibility** -- toggle which sections appear on the customer request form:
  - "How did you hear about us?" field
  - Customer type toggle (private/business)
  - Purpose/output format fields
- **Job Types** -- create and manage the types of drone work you offer (Aerial Photography, Roof Inspection, Land Survey, etc.) with custom icons and categories (technical, creative, other)
- **Purpose Options** -- what the footage will be used for (Marketing, Insurance Claim, Progress Report, etc.)
- **Referral Sources** -- how customers found you (Google, Social Media, Referral, etc.)

<p>
<a href="screenshots/settings-panel.png"><img src="screenshots/settings-panel.png" width="600" alt="Admin settings panel"></a>
</p>

*The settings panel where you configure branding, form options, and job types.*

<p>
<a href="screenshots/dark-mode.png"><img src="screenshots/dark-mode.png" width="400" alt="Dark mode interface"></a>
<a href="screenshots/guide-mode.png"><img src="screenshots/guide-mode.png" width="400" alt="Guide mode showing contextual tooltips"></a>
</p>

*Left: dark mode interface. Right: guide mode with contextual tooltips for new users.*

### Help Page

FlyingPlan includes a searchable in-app help page (`/help`) with documentation on features and workflows.

---

## Order Status Reference

Every order moves through these statuses. The system enforces the correct order -- you can't skip steps.

| Status | What It Means | Who Changes It |
|--------|--------------|----------------|
| **Pending Assignment** | Order created, no pilot assigned yet | Admin |
| **Assigned** | Pilot has been assigned, waiting for them to respond | Admin |
| **Accepted** | Pilot has accepted the job | Pilot |
| **In Progress** | Pilot is on-site and flying (requires completed risk assessment) | Pilot |
| **Flight Complete** | Pilot has finished flying and uploaded deliverables | Pilot |
| **Delivered** | All files have been handed over | Pilot |
| **Closed** | Job is done, admin has signed off | Admin |
| **Declined** | Pilot declined the job (admin can reassign to another pilot) | Pilot |

**Important:** The transition from "Accepted" to "In Progress" is **blocked** until the pilot completes the pre-flight risk assessment. If the risk assessment determines a blocker (e.g. no OA for Specific category, no green light for night flight, MTOM over 25 kg), the flight cannot proceed. If the pilot selects "Abort", the flight cannot proceed at all.

<p>
<a href="screenshots/admin-orders-lv.png"><img src="screenshots/admin-orders-lv.png" width="400" alt="Admin orders list view"></a>
<a href="screenshots/admin-order-dv-rejected.png"><img src="screenshots/admin-order-dv-rejected.png" width="400" alt="Admin order detail - rejected/declined"></a>
</p>

*Left: the order list filtered by status. Right: an order that was declined by a pilot, showing the decline reason and option to reassign.*

---

## Who Can Do What -- User Roles

FlyingPlan has three roles. Each higher role can do everything the lower roles can do, plus more.

| Role | What They Can Do |
|------|-----------------|
| **Pilot** | See their own assigned orders, accept/decline jobs, complete risk assessments, fly missions, upload deliverables, manage their own profile (qualifications, equipment, documents) |
| **Manager** | Everything a Pilot can do, plus: see all flight plans and orders, plan routes, use all planning tools, create orders, assign pilots, export all formats, view all pilot profiles, check weather and airspace, share missions |
| **Admin** | Everything a Manager can do, plus: create and edit pilot accounts, manage app settings and branding, configure job types and form options, toggle dark mode and guide mode |

**Security:** Pilots can only see orders assigned to them. They cannot access other pilots' orders or any admin functions.

---

## Installation

FlyingPlan is available in two versions: a **PHP version** for shared hosting (recommended for production) and the original **Python version** for development or VPS deployment.

### PHP Version (Shared Hosting)

The PHP version runs on any standard shared hosting with PHP 8.1+ and MySQL. No command line access required -- everything is configured through a web-based installer.

**[Download the PHP installer package (zip)](https://github.com/AmigoUK/FlyingPlan/raw/feature/php-rewrite/php/public/install.php)** -- or clone the repo and use the `php/` directory.

#### Requirements

- PHP 8.1 or newer
- MySQL 5.7+ or MariaDB 10.3+
- Required PHP extensions: mysqli, intl, mbstring, json, xml, gd, curl, zip

#### Quick Start

1. **Upload** the `FlyingPlan/` folder to your hosting (e.g. into `public_html/`)
2. **Point** your domain's document root to `FlyingPlan/public/`
3. **Open** your site in a browser -- the installer launches automatically
4. **Walk through** 5 setup steps:
   - **Requirements** -- checks PHP version and extensions
   - **Database** -- enter your MySQL credentials, tables are created automatically
   - **Branding** -- set business name, primary colour, dark mode
   - **Admin Account** -- create your login
   - **Finalize** -- writes config and completes setup
5. **Log in** at `/login` with the admin account you created
6. **Delete the installer** file when prompted (for security)

#### PHP Tech Stack

| Component | Technology |
|-----------|-----------|
| Backend | CodeIgniter 4.6, PHP 8.1+ |
| Database | MySQL / MariaDB |
| PDF Reports | mPDF 8.3 |
| Frontend | Bootstrap 5.3, Leaflet.js, Three.js |
| Maps | OpenStreetMap tiles |
| Weather & Elevation | Open-Meteo API (free, no key) |
| Mission Export | KMZ (DJI), KML, GeoJSON, CSV, GPX, Litchi |

#### PHP Security Checklist

- [ ] Delete `install.php` after setup (the installer prompts you to do this)
- [ ] Change the default admin password to something strong
- [ ] Ensure `writable/` directory is not publicly accessible (`.htaccess` handles this)
- [ ] Use HTTPS (most shared hosts offer free SSL via Let's Encrypt)
- [ ] Set up regular database backups

---

### Python Version (Original)

The original Python/Flask version is suitable for VPS or local development.

#### Prerequisites

- Python 3.10 or newer
- pip (Python package manager)
- System libraries for WeasyPrint PDF generation:

```bash
sudo apt install -y libpango-1.0-0 libpangocairo-1.0-0 libcairo2 libgdk-pixbuf-2.0-0 libffi-dev
```

#### Quick Start

```bash
# Clone the repository
git clone https://github.com/AmigoUK/FlyingPlan.git
cd FlyingPlan

# Create a virtual environment
python3 -m venv venv
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt

# Start the application
python3 app.py
```

Open your browser and go to `https://localhost:5002`. The database and default users are created automatically on the first run.

> **Note:** The app runs with HTTPS using self-signed certificates. Your browser will show a security warning -- this is expected for self-signed certs.

#### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `SECRET_KEY` | `fp-dev-key-change-in-production` | Flask session encryption key. **Change this in production.** |
| `DATABASE_URL` | `sqlite:///flyingplan.db` | Database connection string. SQLite by default; set to a PostgreSQL URL for production. |
| `GOOGLE_CLIENT_ID` | *(empty)* | Google OAuth client ID (for Google login integration) |
| `GOOGLE_CLIENT_SECRET` | *(empty)* | Google OAuth client secret |

#### SSL Setup

The app expects SSL certificates at `certs/cert.pem` and `certs/key.pem`. To generate self-signed certificates:

```bash
mkdir -p certs
openssl req -x509 -newkey rsa:2048 -keyout certs/key.pem -out certs/cert.pem -days 365 -nodes -subj "/CN=localhost"
```

#### Production Deployment (systemd)

```ini
[Unit]
Description=FlyingPlan Flask Application
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/var/www/html/FlyingPlan
ExecStart=/usr/bin/python3 app.py
Restart=always
RestartSec=3
Environment=FLASK_ENV=production

[Install]
WantedBy=multi-user.target
```

Save as `/etc/systemd/system/flyingplan.service`, then:

```bash
sudo systemctl daemon-reload
sudo systemctl enable flyingplan
sudo systemctl start flyingplan
```

#### Python Security Checklist

- [ ] Change the default `SECRET_KEY` to a long random string
- [ ] Change the default admin and pilot passwords
- [ ] Set `FLASK_ENV=production`
- [ ] Consider PostgreSQL instead of SQLite for concurrent access
- [ ] Use a reverse proxy (nginx/Caddy) for SSL termination and static file serving
- [ ] Run as a non-root user
- [ ] Set up regular database backups
- [ ] Restrict firewall access to ports 80/443 only

---

## Running Tests

```bash
# Run all tests
python3 -m pytest tests/ -v

# Run a specific test file
python3 -m pytest tests/test_risk_assessment.py -v
```

---

## Demo Data

FlyingPlan includes a command to fill the system with realistic sample data for testing:

```bash
flask seed-demo
```

**Warning:** This wipes all existing data and creates:
- 6 users (1 admin + 5 pilots)
- 15 flight plans with realistic UK locations
- 15 orders in various statuses
- 8 risk assessments (including an abort scenario)
- 47 waypoints and 10 points of interest
- 72 activity log entries

---

## Default Login Credentials

| Username | Password | Role |
|----------|----------|------|
| `admin` | `admin123` | Admin |
| `pilot1` | `pilot123` | Pilot |

After running `flask seed-demo`, all demo users use password **`demo123`**.

> **Change these immediately if you deploy to a live server.**

---

## Tech Stack

### PHP Version (Shared Hosting)

| Component | Technology |
|-----------|-----------|
| Backend | CodeIgniter 4.6, PHP 8.1+ |
| Database | MySQL / MariaDB (via MySQLi) |
| Authentication | Session-based with bcrypt passwords |
| Frontend | Bootstrap 5.3.3 |
| Maps | Leaflet.js, Leaflet.Draw |
| 3D Visualisation | Three.js |
| Weather & Elevation | Open-Meteo API (free, no key required) |
| PDF Reports | mPDF 8.3 |
| Map Tiles | OpenStreetMap |
| Mission Export | KMZ (DJI), KML, GeoJSON, CSV, GPX, Litchi CSV |
| Tests | PHPUnit (63 tests) |

### Python Version (Original)

| Component | Technology |
|-----------|-----------|
| Backend | Flask 3.1, Python 3.12 |
| Database | SQLite (via SQLAlchemy) |
| Authentication | Flask-Login, Google OAuth (optional) |
| Frontend | Bootstrap 5.3 |
| Maps | Leaflet.js |
| 3D Visualisation | Three.js |
| Weather & Elevation | Open-Meteo API (free, no key required) |
| PDF Reports | WeasyPrint |
| Static Maps | staticmap (for PDF report map images) |
| Map Tiles | OpenStreetMap |
| Mission Export | Custom KMZ generator (DJI compatible) |
| Tests | pytest |

---

## Licence

This project is licensed under the [GNU General Public License v3.0](LICENSE).
