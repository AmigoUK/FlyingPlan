# FlyingPlan

Drone flight management system for requesting, planning, and executing drone missions. Covers the full workflow from customer submission through admin planning to pilot execution and delivery, with built-in CAA regulatory compliance.

Built with Flask, SQLite, Bootstrap 5, and Leaflet.js.

---

## Table of Contents

- [Features](#features)
- [Architecture](#architecture)
- [Information Flow](#information-flow)
- [User Roles & Access Control](#user-roles--access-control)
- [Customer Portal](#customer-portal)
- [Admin Dashboard](#admin-dashboard)
- [Order Management](#order-management)
- [Pilot Management](#pilot-management)
- [Pilot Portal](#pilot-portal)
- [Pre-Flight Risk Assessment](#pre-flight-risk-assessment)
- [Settings & Configuration](#settings--configuration)
- [KMZ Mission Export](#kmz-mission-export)
- [Screenshots](#screenshots)
- [Installation](#installation)
- [Running Tests](#running-tests)
- [Default Credentials](#default-credentials)
- [Project Structure](#project-structure)
- [Tech Stack](#tech-stack)
- [Licence](#licence)

---

## Features

- **Customer flight request form** — 5-step wizard with map-based location selection, area polygon drawing, and POI markers
- **Admin flight plan dashboard** — review submissions, edit waypoints on an interactive map, manage statuses
- **DJI KMZ export** — generate mission files compatible with DJI Mini 4 Pro
- **Order management** — create orders from flight plans, assign to pilots, track through lifecycle
- **Pilot management** — manage pilot profiles, certifications, equipment, and credential documents
- **Pilot portal** — dedicated dashboard for pilots to accept/decline orders, upload deliverables, manage profile
- **Pre-flight risk assessment** — mandatory 28-check CAA-compliant safety checklist with GPS capture before every flight
- **Role-based access** — three-tier RBAC (Pilot / Manager / Admin) with ownership enforcement
- **Configurable settings** — customisable branding, job types, purpose options, form visibility toggles
- **Activity logging** — full audit trail on every order status change
- **75 automated tests** — comprehensive coverage across all features

---

## Architecture

```
                         ┌─────────────────────┐
                         │   Customer Browser   │
                         └──────────┬──────────┘
                                    │ POST /submit
                         ┌──────────▼──────────┐
                         │   Public Blueprint   │
                         │   (Flight Request)   │
                         └──────────┬──────────┘
                                    │ FlightPlan created
                    ┌───────────────┼───────────────┐
                    │               │               │
         ┌──────────▼──┐  ┌────────▼────────┐  ┌───▼──────────┐
         │    Admin     │  │     Orders      │  │   Settings   │
         │  Dashboard   │  │   Management    │  │   (Admin)    │
         │ /admin       │  │ /admin/orders   │  │ /admin/      │
         │              │  │                 │  │  settings    │
         └──────┬───────┘  └───────┬─────────┘  └──────────────┘
                │                  │
                │ Edit waypoints   │ Assign pilot
                │ Export KMZ       │
                │                  │
         ┌──────▼──────────────────▼─────────┐
         │            SQLite DB              │
         │  flight_plans · orders · users    │
         │  risk_assessments · waypoints     │
         │  order_activities · deliverables  │
         └──────────────────┬────────────────┘
                            │
                   ┌────────▼────────┐
                   │  Pilot Portal   │
                   │  /pilot         │
                   │                 │
                   │ Accept order    │
                   │ Risk assessment │
                   │ Start flight    │
                   │ Upload files    │
                   │ Mark delivered  │
                   └─────────────────┘
```

### Blueprint Map

| Blueprint | URL Prefix | Role Required | Purpose |
|-----------|-----------|---------------|---------|
| `auth` | `/` | — | Login / Logout |
| `public` | `/` | — | Customer flight request form |
| `admin` | `/admin` | Manager+ | Flight plan dashboard & detail |
| `settings` | `/admin/settings` | Admin | App configuration |
| `pilots` | `/admin/pilots` | Manager+ | Pilot CRUD & credential management |
| `orders` | `/admin/orders` | Manager+ | Order lifecycle management |
| `pilot` | `/pilot` | Pilot | Pilot self-service portal |

---

## Information Flow

### End-to-End Workflow

```
Customer submits         Admin reviews &          Admin creates order
flight request    ──►    plans waypoints    ──►   & assigns pilot
     │                        │                        │
     │                        │                        │
     ▼                        ▼                        ▼
┌─────────┐           ┌─────────────┐           ┌──────────┐
│FlightPlan│           │  Waypoints  │           │  Order   │
│ created  │           │  saved +    │           │ status:  │
│ status:  │           │  KMZ export │           │ assigned │
│ "new"    │           │  available  │           │          │
└─────────┘           └─────────────┘           └────┬─────┘
                                                     │
                              ┌───────────────────────┘
                              ▼
                    Pilot accepts order
                    (status: "accepted")
                              │
                              ▼
                    Pilot completes 28-check
                    risk assessment on-site
                    (GPS location captured)
                              │
                     ┌────────┴────────┐
                     │                 │
                  Proceed            Abort
                     │                 │
                     ▼                 ▼
              Gate opens          Gate stays closed
              (can fly)           (cannot fly)
                     │
                     ▼
              Pilot starts flight
              (status: "in_progress")
                     │
                     ▼
              Pilot completes flight
              uploads deliverables
              (status: "flight_complete")
                     │
                     ▼
              Pilot marks delivered
              (status: "delivered")
                     │
                     ▼
              Admin closes order
              (status: "closed")
```

### Order Status Lifecycle

```
pending_assignment ──► assigned ──► accepted ──► in_progress ──► flight_complete ──► delivered ──► closed
                          │                          ▲
                          │                          │
                          ▼               risk_assessment_completed
                       declined                  = True
```

The `accepted → in_progress` transition is **gated** — the pilot must first complete the pre-flight risk assessment. If the pilot selects "Abort", the assessment is recorded but the gate stays closed and the flight cannot proceed.

### Flight Plan Status Lifecycle

```
new ──► in_review ──► route_planned ──► completed
                                    ──► cancelled
```

---

## User Roles & Access Control

FlyingPlan uses a rank-based RBAC system. Each role inherits all permissions of lower roles.

| Role | Rank | Access |
|------|------|--------|
| **Pilot** | 0 | Own orders only, personal profile, risk assessments |
| **Manager** | 1 | All flight plans, all orders, assign pilots, all pilot profiles |
| **Admin** | 2 | Everything + user management, app settings, branding |

**Ownership enforcement:** Pilots can only view/modify orders assigned to them. Attempting to access another pilot's order returns HTTP 403.

**Rate limiting:** Login is limited to 5 failed attempts per 30 seconds per IP address.

---

## Customer Portal

The public-facing form at `/` allows customers to submit drone flight requests without authentication.

### 5-Step Form Wizard

1. **Your Details** — name, email, phone, company, how they heard about you
2. **Job Brief** — job type (from admin-configured list), description, preferred dates, time window, urgency, special requirements
3. **Location** — address search, interactive map pin, optional area polygon for multi-point missions, POI markers
4. **Flight Preferences** — altitude (preset or custom), camera angle, video resolution, photo mode, no-fly zones, privacy notes
5. **Review & Submit** — consent checkbox, optional file attachments (PNG/JPG/PDF/DOC, 32 MB max)

On submission, a unique reference is generated (`FP-YYYYMMDD-####`) and the customer sees a confirmation page.

> **Screenshot placeholder:**
> ![Customer Form - Step 1: Your Details](screenshots/customer-form-step1.png)
> *The first step of the flight request wizard collecting customer contact information and company details.*

> **Screenshot placeholder:**
> ![Customer Form - Step 3: Location](screenshots/customer-form-step3.png)
> *Interactive Leaflet map where customers drop a pin for the flight location, draw area polygons, and add points of interest.*

> **Screenshot placeholder:**
> ![Confirmation Page](screenshots/confirmation-page.png)
> *Confirmation page shown after successful submission with the unique flight plan reference number.*

---

## Admin Dashboard

Managers and admins access the flight plan dashboard at `/admin`.

### Flight Plan List

- Filterable by **status** (new, in review, route planned, completed, cancelled)
- Filterable by **job type** (aerial photography, inspection, survey, etc.)
- Searchable by customer name, email, reference, or company
- Each row shows reference, customer, job type, status badge, creation date
- Quick-action button to create an order from any flight plan

> **Screenshot placeholder:**
> ![Admin Dashboard](screenshots/admin-dashboard.png)
> *Flight plan dashboard with status filters, search bar, and the list of customer submissions.*

### Flight Plan Detail

- Full customer submission details
- Interactive Leaflet map for editing waypoints (click to add, drag to reorder)
- Waypoint panel with altitude, speed, heading, gimbal pitch controls
- DJI KMZ export button (generates mission file for DJI Mini 4 Pro)
- Status selector and admin notes
- Order creation modal with pilot assignment

> **Screenshot placeholder:**
> ![Flight Plan Detail - Map & Waypoints](screenshots/admin-detail-map.png)
> *Admin flight plan detail page showing the interactive map with waypoints and the waypoint editing panel.*

> **Screenshot placeholder:**
> ![Flight Plan Detail - KMZ Export](screenshots/admin-detail-kmz.png)
> *KMZ export button and waypoint list — generates DJI-compatible mission files for the Mini 4 Pro.*

---

## Order Management

Orders are created from flight plans and track the full pilot assignment lifecycle.

### Creating an Order

From the flight plan detail page, admins can:
1. Click "Create Order"
2. Optionally assign a pilot immediately
3. Set scheduled date/time
4. Add assignment notes

### Order Detail (Admin View)

- Flight plan summary with link to full plan
- Pilot assignment card with reassignment form
- Scheduled date/time display
- **Risk assessment summary card** (after pilot completes assessment) showing risk level, decision, GPS coordinates, weather data, and battery level
- Deliverables table with download links
- Activity log showing all status changes with timestamps and who made them
- Admin notes

> **Screenshot placeholder:**
> ![Order Detail - Admin View](screenshots/admin-order-detail.png)
> *Admin order detail showing pilot assignment, risk assessment summary, deliverables, and the full activity log.*

### Order List

- Filterable by status and pilot
- Shows reference, customer, pilot name, status badge, scheduled date

> **Screenshot placeholder:**
> ![Order List](screenshots/admin-order-list.png)
> *Order management list with status and pilot filters.*

---

## Pilot Management

Admins manage pilot profiles, certifications, equipment, and documents at `/admin/pilots`.

### Pilot Profile (Admin View)

- Contact information, regulatory IDs (Flying ID, Operator ID)
- Insurance details (provider, policy number, expiry)
- Availability status (available / on mission / unavailable)
- **Certifications** — name, issuing body, cert number, issue/expiry dates
- **Equipment** — drone model, serial number, registration ID, notes
- **Documents** — uploaded credential files (certificates, insurance, licences) with expiry tracking

> **Screenshot placeholder:**
> ![Pilot Management - List](screenshots/admin-pilots-list.png)
> *Pilot list showing all registered pilots with their availability status and active/inactive badges.*

> **Screenshot placeholder:**
> ![Pilot Management - Profile](screenshots/admin-pilot-detail.png)
> *Pilot detail page with certifications, equipment list, and uploaded credential documents.*

---

## Pilot Portal

Pilots access their dedicated portal at `/pilot` after logging in. The navigation automatically switches to the pilot-specific navbar.

### Pilot Dashboard

- Lists all orders assigned to the logged-in pilot
- Orders grouped by status: pending action, active, completed, declined
- Quick access to each order's detail page

> **Screenshot placeholder:**
> ![Pilot Dashboard](screenshots/pilot-dashboard.png)
> *Pilot dashboard showing assigned orders organised by status.*

### Order Detail (Pilot View)

- Customer and job brief information
- Flight preferences (altitude, camera, resolution, special requirements)
- Location map (Leaflet with marker)
- **Accept / Decline** buttons (for assigned orders)
- **Risk Assessment CTA** — prominent card showing whether assessment is required, complete, or locked
- **Status progression** buttons — gated: "In Progress" is disabled until risk assessment is complete
- Pilot notes section
- Deliverable upload (video, photos, PDFs, ZIPs up to 32 MB each)
- Activity log

> **Screenshot placeholder:**
> ![Pilot Order Detail - Accept/Decline](screenshots/pilot-order-accept.png)
> *Pilot order detail showing the accept/decline action card for a newly assigned order.*

> **Screenshot placeholder:**
> ![Pilot Order Detail - Risk Assessment Required](screenshots/pilot-order-ra-required.png)
> *Order detail showing the risk assessment warning card and the locked "In Progress" button.*

> **Screenshot placeholder:**
> ![Pilot Order Detail - Assessment Complete](screenshots/pilot-order-ra-complete.png)
> *Order detail after risk assessment completion — the "In Progress" button is now enabled.*

### Pilot Profile

- Self-service profile editing (display name, contact info, regulatory IDs, insurance)
- Password change
- Manage own certifications, equipment, and credential documents

> **Screenshot placeholder:**
> ![Pilot Profile](screenshots/pilot-profile.png)
> *Pilot self-service profile page with certifications, equipment, and document management.*

---

## Pre-Flight Risk Assessment

**CAA Compliance:** UK CAA regulations (CAP 722, ANO 2016, UK Regulation EU 2019/947, Drone Code 2025/2026) require a documented pre-flight risk assessment completed **on-site** before every flight. Records must be retained for 2+ years and be available for CAA inspection.

### How It Works

1. Pilot accepts an order (status becomes `accepted`)
2. Pilot navigates to the flight location
3. Pilot opens the risk assessment form from the order detail page
4. Pilot works through **7 sections with 28 mandatory checks**
5. Pilot selects a risk level and decision
6. Pilot signs the declaration
7. GPS location is automatically captured via the browser's Geolocation API
8. If decision is **Proceed** or **Proceed with Mitigations** → gate opens, pilot can start flight
9. If decision is **Abort** → assessment is recorded but gate stays closed

### The 7 Sections

| # | Section | Checks | Data Inputs |
|---|---------|--------|-------------|
| 1 | **Site Assessment** | 4 | — |
| 2 | **Airspace Check** | 4 | Planned altitude (m) |
| 3 | **Weather Assessment** | 1 | Wind speed, direction, visibility, precipitation, temperature |
| 4 | **Equipment Check** | 6 | Battery level (%) |
| 5 | **IMSAFE Pilot Fitness** | 6 | — |
| 6 | **Permissions & Compliance** | 4 | — |
| 7 | **Emergency Procedures** | 3 | — |
| | **Total** | **28** | |

**Section details:**

1. **Site Assessment** — Ground hazards assessed, obstacles mapped, 50m separation from uninvolved persons, 150m from residential/commercial/industrial areas
2. **Airspace Check** — FRZ checked, restricted airspace checked, NOTAMs reviewed (UTC), max altitude confirmed (120m Open Category), planned altitude input
3. **Weather Assessment** — Conditions acceptable for flight, plus wind speed (km/h), wind direction, visibility (km), precipitation, temperature (C)
4. **Equipment Check** — Drone airworthiness, battery adequate (% input), propellers OK, GPS/GNSS lock, remote control functional, Remote ID active (required from Jan 2026)
5. **IMSAFE Pilot Fitness** — standard CAA aviation checklist: **I**llness free, no impairing **M**edication, manageable **S**tress, no **A**lcohol (8+ hrs), adequately rested (**F**atigue), properly nourished (**E**ating)
6. **Permissions & Compliance** — Flyer ID valid, Operator ID displayed, insurance valid, authorizations obtained
7. **Emergency Procedures** — Landing site identified, emergency contacts confirmed, contingency plan reviewed

### Decision Options

| Decision | Effect |
|----------|--------|
| **Proceed** | Gate opens — pilot can advance to "In Progress" |
| **Proceed with Mitigations** | Gate opens — mitigation notes required and recorded |
| **Abort** | Gate stays closed — flight cannot proceed, assessment retained for records |

### UI Features

- **Accordion layout** — each section expands/collapses independently
- **Live progress bar** — shows checked count out of 28 with colour change at 100%
- **Section badges** — each accordion header shows completion count (e.g., 3/4)
- **Sticky decision panel** — risk level, decision, mitigation notes, and submit button stay visible while scrolling
- **Submit button disabled** until all 28 checks confirmed + risk level + decision + declaration
- **Read-only view** — after completion, the form shows all checks with green tick marks and the decision summary

> **Screenshot placeholder:**
> ![Risk Assessment Form - Accordion](screenshots/risk-assessment-form.png)
> *The 7-section accordion form with the Site Assessment section expanded, showing the 4 mandatory checks.*

> **Screenshot placeholder:**
> ![Risk Assessment Form - Decision Panel](screenshots/risk-assessment-decision.png)
> *The sticky decision panel showing the progress bar, risk level selector, decision dropdown, and pilot declaration.*

> **Screenshot placeholder:**
> ![Risk Assessment - Read-Only View](screenshots/risk-assessment-readonly.png)
> *Read-only view of a completed risk assessment showing all green checks and the decision summary card.*

> **Screenshot placeholder:**
> ![Risk Assessment - Admin Summary](screenshots/risk-assessment-admin.png)
> *Risk assessment summary card on the admin order detail page showing risk level, decision, GPS coordinates, and weather data.*

---

## Settings & Configuration

Admins configure the application at `/admin/settings`.

### Branding

- Business name (displayed in navbar and page titles)
- Logo URL
- Primary colour (CSS variable `--fp-primary`)
- Contact email
- Tagline

### Form Visibility Toggles

Control which sections appear on the customer form:
- Show "How did you hear about us?" field
- Show private/business customer toggle
- Show footage purpose fields
- Show output format field

### Lookup Table Management

Admins can create, edit, toggle, and delete:
- **Job Types** — categories like Aerial Photography, Inspection, Survey (with Bootstrap icon and category)
- **Purpose Options** — footage usage options (Marketing, Insurance, Progress Report, etc.)
- **Heard-About Options** — referral source tracking (Google Search, Social Media, Referral, etc.)

Deleting a lookup option is blocked if it's currently in use by any flight plan.

> **Screenshot placeholder:**
> ![Settings - Branding](screenshots/settings-branding.png)
> *Admin settings page showing branding configuration and form visibility toggles.*

> **Screenshot placeholder:**
> ![Settings - Job Types](screenshots/settings-job-types.png)
> *Job type management with inline toggle, edit, and delete controls.*

---

## KMZ Mission Export

FlyingPlan generates DJI-compatible KMZ mission files for the DJI Mini 4 Pro.

### How It Works

1. Admin opens a flight plan detail page
2. Edits waypoints on the interactive map (click to add points, set altitude/speed/heading per waypoint)
3. Clicks "Export KMZ"
4. System generates a ZIP file containing:
   - `wpmz/template.kml` — UI display template for DJI Fly app
   - `wpmz/waylines.wpml` — executable flight instructions

### Waypoint Parameters

Each waypoint supports:
- Latitude/longitude (WGS84)
- Altitude (metres, relative to start point)
- Speed (m/s)
- Heading (degrees, 0-360)
- Gimbal pitch (degrees)
- Turn mode (e.g., `toPointAndStopWithDiscontinuityCurvature`)
- Hover time (seconds)
- Camera action (takePhoto, video, etc.)
- POI reference

---

## Screenshots

Below is a guide to the key screens in FlyingPlan. Add screenshots to a `screenshots/` directory.

| # | Filename | Description |
|---|----------|-------------|
| 1 | `customer-form-step1.png` | Customer form wizard — Step 1: Your Details (name, email, phone, company) |
| 2 | `customer-form-step3.png` | Customer form wizard — Step 3: Location with Leaflet map, pin, polygon drawing |
| 3 | `confirmation-page.png` | Submission confirmation with unique reference number |
| 4 | `admin-dashboard.png` | Admin flight plan dashboard with status filters and search |
| 5 | `admin-detail-map.png` | Flight plan detail with interactive waypoint map and editing panel |
| 6 | `admin-detail-kmz.png` | KMZ export section showing waypoint list and download button |
| 7 | `admin-order-list.png` | Order list with status and pilot filters |
| 8 | `admin-order-detail.png` | Admin order detail — pilot assignment, risk assessment summary, deliverables, activity log |
| 9 | `admin-pilots-list.png` | Pilot management list with availability and active status |
| 10 | `admin-pilot-detail.png` | Pilot profile — certifications, equipment, uploaded documents |
| 11 | `pilot-dashboard.png` | Pilot dashboard showing assigned orders by status |
| 12 | `pilot-order-accept.png` | Pilot order detail — accept/decline action card |
| 13 | `pilot-order-ra-required.png` | Pilot order detail — risk assessment required warning, locked In Progress button |
| 14 | `pilot-order-ra-complete.png` | Pilot order detail — assessment complete, In Progress button enabled |
| 15 | `pilot-profile.png` | Pilot self-service profile with certifications and equipment |
| 16 | `risk-assessment-form.png` | Risk assessment accordion form with Site Assessment section expanded |
| 17 | `risk-assessment-decision.png` | Risk assessment sticky decision panel — progress bar, risk level, declaration |
| 18 | `risk-assessment-readonly.png` | Completed risk assessment read-only view with green check marks |
| 19 | `risk-assessment-admin.png` | Risk assessment summary card on admin order detail page |
| 20 | `settings-branding.png` | Admin settings — branding and form visibility toggles |
| 21 | `settings-job-types.png` | Admin settings — job type management with toggle/edit/delete |
| 22 | `login-page.png` | Login page with username/password and remember-me checkbox |

---

## Installation

### Prerequisites

- Python 3.10+
- pip

### Setup

```bash
# Clone the repository
git clone https://github.com/AmigoUK/FlyingPlan.git
cd FlyingPlan

# Create virtual environment
python3 -m venv venv
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt

# Run the application
python3 app.py
```

The app starts on `http://localhost:5002`. The database and default users are created automatically on first run.

### Environment Variables (Optional)

| Variable | Default | Description |
|----------|---------|-------------|
| `SECRET_KEY` | (set in config) | Flask session secret |
| `DATABASE_URL` | `sqlite:///flyingplan.db` | Database connection string |

---

## Running Tests

```bash
# Run all 75 tests
python3 -m pytest tests/ -v

# Run specific test file
python3 -m pytest tests/test_risk_assessment.py -v

# Run with coverage (if pytest-cov installed)
python3 -m pytest tests/ --cov=. --cov-report=term-missing
```

### Test Files

| File | Tests | Coverage |
|------|-------|----------|
| `test_public.py` | 5 | Customer form submission, validation, confirmation |
| `test_admin.py` | 8 | Dashboard, detail, waypoints, status, notes, filters |
| `test_models.py` | 6 | User password, seeding, flight plan relationships, cascade delete |
| `test_kmz.py` | 5 | KMZ structure, KML/WPML validity, coordinate format, export route |
| `test_orders.py` | 10 | Order CRUD, pilot assignment, status transitions, activity log |
| `test_pilot.py` | 15 | Dashboard, accept/decline, status, ownership, profile, certs, equipment |
| `test_risk_assessment.py` | 12 | Form loading, validation, gate enforcement, abort flow, activity log |
| `test_roles.py` | 14 | Role hierarchy, route protection, login redirects, seeded roles |

---

## Default Credentials

| Username | Password | Role | Purpose |
|----------|----------|------|---------|
| `admin` | `admin123` | Admin | Full system access |
| `pilot1` | `pilot123` | Pilot | Demo pilot account |

> **Change these immediately in production.**

---

## Project Structure

```
FlyingPlan/
├── app.py                          # Flask app factory, migrations, seeding
├── config.py                       # Configuration (DB, uploads, secrets)
├── extensions.py                   # SQLAlchemy, LoginManager, CSRF init
├── requirements.txt                # Python dependencies
│
├── blueprints/
│   ├── auth/                       # Login / logout
│   │   ├── routes.py
│   │   └── decorators.py           # @role_required decorator
│   ├── public/                     # Customer flight request form
│   │   └── routes.py
│   ├── admin/                      # Flight plan dashboard & detail
│   │   └── routes.py
│   ├── orders/                     # Order CRUD & assignment
│   │   └── routes.py
│   ├── pilots/                     # Pilot management (admin side)
│   │   └── routes.py
│   ├── pilot/                      # Pilot self-service portal
│   │   └── routes.py
│   └── settings/                   # App settings & lookup tables
│       └── routes.py
│
├── models/
│   ├── flight_plan.py              # Core flight request entity
│   ├── user.py                     # Users with role-based access
│   ├── order.py                    # Pilot assignment & status tracking
│   ├── order_activity.py           # Audit log for orders
│   ├── order_deliverable.py        # Pilot-uploaded files
│   ├── risk_assessment.py          # 28-check pre-flight safety form
│   ├── waypoint.py                 # DJI flight path points
│   ├── poi.py                      # Points of interest
│   ├── pilot_certification.py      # Pilot licences
│   ├── pilot_equipment.py          # Pilot drone inventory
│   ├── pilot_document.py           # Uploaded credential files
│   ├── upload.py                   # Customer file attachments
│   ├── app_settings.py             # Singleton app configuration
│   ├── job_type.py                 # Configurable job categories
│   ├── purpose_option.py           # Footage purpose options
│   └── heard_about_option.py       # Marketing source tracking
│
├── services/
│   └── kmz_generator.py            # DJI Mini 4 Pro KMZ file builder
│
├── templates/
│   ├── base.html                   # Base layout with Bootstrap 5
│   ├── public/                     # Customer-facing pages
│   │   ├── form.html               # 5-step wizard
│   │   └── confirmation.html
│   ├── admin/                      # Admin pages
│   │   ├── login.html
│   │   ├── dashboard.html
│   │   ├── detail.html             # Flight plan with map
│   │   ├── settings.html
│   │   ├── pilots/                 # Pilot management views
│   │   │   ├── list.html
│   │   │   ├── detail.html
│   │   │   └── form.html
│   │   └── orders/                 # Order management views
│   │       ├── list.html
│   │       └── detail.html
│   ├── pilot/                      # Pilot portal views
│   │   ├── dashboard.html
│   │   ├── profile.html
│   │   ├── order_detail.html
│   │   └── risk_assessment.html    # 7-section accordion form
│   └── partials/                   # Shared components
│       ├── _navbar.html            # Admin/manager navbar
│       ├── _pilot_navbar.html      # Pilot navbar
│       ├── _assign_modal.html      # Order assignment modal
│       └── _step[1-5]_*.html       # Form wizard step partials
│
├── static/
│   └── css/
│       └── style.css               # Custom styles, status badges
│
├── tests/                          # 75 automated tests
│   ├── test_public.py
│   ├── test_admin.py
│   ├── test_models.py
│   ├── test_kmz.py
│   ├── test_orders.py
│   ├── test_pilot.py
│   ├── test_risk_assessment.py
│   └── test_roles.py
│
└── instance/
    ├── flyingplan.db               # SQLite database (auto-created)
    └── uploads/                    # File storage
        ├── orders/<id>/            # Pilot deliverables
        └── pilots/<id>/            # Pilot credential documents
```

---

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Backend | Flask 3.1, Python 3.12 |
| Database | SQLite (SQLAlchemy ORM) |
| Auth | Flask-Login, PBKDF2:SHA256 |
| CSRF | Flask-WTF |
| Frontend | Bootstrap 5.3, Bootstrap Icons |
| Maps | Leaflet.js 1.9, Leaflet.Draw |
| Mission Export | Custom KMZ generator (DJI Mini 4 Pro) |
| Tests | pytest |

---

## Licence

All rights reserved.
