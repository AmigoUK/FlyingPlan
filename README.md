# FlyingPlan

**Drone flight management system** -- from customer request to mission delivery.

FlyingPlan handles the entire drone job workflow: your customers submit flight requests, you plan the mission, assign a pilot, and the pilot carries out the flight with full CAA-compliant safety checks. Everything is tracked, documented, and exportable.

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
- [Reports and Exports](#reports-and-exports)
- [Managing Your Pilots](#managing-your-pilots)
- [Settings and Branding](#settings-and-branding)
- [Order Status Reference](#order-status-reference)
- [Who Can Do What -- User Roles](#who-can-do-what----user-roles)
- [Installation](#installation)
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

From the flight plan detail page, you can plan the drone's exact route using an **interactive map**:

- **Click on the map** to add waypoints (the points the drone will fly to)
- **Set parameters** for each waypoint: altitude, speed, heading (direction), gimbal (camera) pitch
- **Add Points of Interest (POIs)** -- the drone can be told to face these while flying
- **Export a KMZ file** -- this generates a mission file you can load directly into the DJI Fly app on a DJI Mini 4 Pro

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

The assessment covers **7 sections**:

| Section | What It Checks |
|---------|---------------|
| **Site Assessment** | Ground hazards, obstacles, safe distance from people and buildings |
| **Airspace Check** | Flight restriction zones, restricted airspace, NOTAMs, max altitude |
| **Weather Assessment** | Wind speed and direction, visibility, precipitation, temperature |
| **Equipment Check** | Drone condition, battery level, propellers, GPS lock, remote control, Remote ID |
| **Pilot Fitness (IMSAFE)** | Illness, medication, stress, alcohol, fatigue, nutrition |
| **Permissions & Compliance** | Flyer ID, Operator ID, insurance, authorisations |
| **Emergency Procedures** | Emergency landing site, contacts, contingency plan |

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
2. **Loads the KMZ mission file** into their DJI Fly app (if using automated waypoints)
3. **Flies the mission** and captures footage/photos
4. **Uploads deliverables** -- videos, photos, PDFs, ZIP files (up to 32 MB each)
5. **Marks the flight as complete**

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

## Reports and Exports

FlyingPlan generates professional reports and mission files:

### PDF Flight Reports

A detailed PDF report is generated for each order, containing:
- Customer and job details
- Flight plan summary with a static map
- Risk assessment results (including GPS coordinates and weather data)
- Full activity log

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

FlyingPlan exports **KMZ mission files** that you can load directly into the **DJI Fly app** on a DJI Mini 4 Pro. You can also import them into **Google Earth Pro** to visualise the flight path before the mission.

<p>
<a href="screenshots/FP-20260316-1006-google-kmz.png"><img src="screenshots/FP-20260316-1006-google-kmz.png" width="400" alt="KMZ file opened in Google Earth Pro"></a>
<a href="screenshots/google-pro-with-imported-kmz.png"><img src="screenshots/google-pro-with-imported-kmz.png" width="400" alt="Google Earth Pro showing imported KMZ mission"></a>
</p>

*KMZ mission files imported into Google Earth Pro, showing the planned flight route and waypoints on satellite imagery.*

---

## Managing Your Pilots

As an admin, you manage your pilot team from the **Pilot Management** section:

- **View all pilots** with their availability status (Available / On Mission / Unavailable)
- **Create pilot profiles** with contact details, regulatory IDs (Flyer ID, Operator ID), and insurance information
- **Track certifications** -- licence name, issuing body, certificate number, issue and expiry dates
- **Register equipment** -- drone models, serial numbers, registration IDs
- **Upload documents** -- certificates, insurance policies, licences (with expiry date tracking)

Pilots can also **manage their own profile** from the Pilot Portal, including updating their details, adding certifications, and uploading documents.

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

- **Branding** -- your business name, logo, primary colour, tagline, and contact email
- **Form Visibility** -- toggle which sections appear on the customer request form (e.g. hide the "How did you hear about us?" field if you don't need it)
- **Job Types** -- create and manage the types of drone work you offer (Aerial Photography, Roof Inspection, Land Survey, etc.)
- **Purpose Options** -- what the footage will be used for (Marketing, Insurance Claim, Progress Report, etc.)
- **Referral Sources** -- how customers found you (Google, Social Media, Referral, etc.)

<p>
<a href="screenshots/settings-panel.png"><img src="screenshots/settings-panel.png" width="600" alt="Admin settings panel"></a>
</p>

*The settings panel where you configure branding, form options, and job types.*

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

**Important:** The transition from "Accepted" to "In Progress" is **blocked** until the pilot completes the 28-point pre-flight risk assessment. If the pilot selects "Abort" in the assessment, the flight cannot proceed at all.

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
| **Pilot** | See their own assigned orders, accept/decline jobs, complete risk assessments, fly missions, upload deliverables, manage their own profile |
| **Manager** | Everything a Pilot can do, plus: see all flight plans and orders, plan routes, create orders, assign pilots, export KMZ files, view all pilot profiles |
| **Admin** | Everything a Manager can do, plus: create and edit pilot accounts, manage app settings and branding, configure job types and form options |

**Security:** Pilots can only see orders assigned to them. They cannot access other pilots' orders or any admin functions.

---

## Installation

### What You Need

- Python 3.10 or newer
- pip (Python package manager)

### Setup

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

Open your browser and go to `http://localhost:5002`. The database and default users are created automatically on the first run.

---

## Running Tests

```bash
# Run all 75 tests
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

| Component | Technology |
|-----------|-----------|
| Backend | Flask 3.1, Python 3.12 |
| Database | SQLite (via SQLAlchemy) |
| Authentication | Flask-Login |
| Frontend | Bootstrap 5.3 |
| Maps | Leaflet.js |
| Mission Export | Custom KMZ generator (DJI Mini 4 Pro) |
| Tests | pytest (75 tests) |

---

## Licence

This project is licensed under the [GNU General Public License v3.0](LICENSE).
