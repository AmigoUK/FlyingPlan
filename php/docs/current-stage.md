# FlyingPlan — Current Stage

**Date:** 2026-04-01
**Branch:** `feature/php-rewrite`
**Live Demo:** https://attv.uk/flyingplan/public/
**Repo:** git@github.com:AmigoUK/FlyingPlan.git

---

## Deployment Status

**Platform:** one.com shared hosting (attv.uk)
**Stack:** PHP 8.3 / CodeIgniter 4.6 / MySQL (MariaDB) / Bootstrap 5.3.3
**Status:** Live demo — fully functional with demo data

---

## What's Done

### Core Application (from PHP rewrite)
- Full CI4 rewrite from Python/Flask original
- Public flight brief form (multi-step wizard)
- Admin dashboard with flight plan management
- Pilot dashboard with order management
- Risk assessment system
- Category determination engine (UK CAA regulations)
- Weather integration (Open-Meteo API)
- Map tools (Leaflet.js) — waypoints, routes, measurements
- Grid/pattern mission planning with GSD calculator
- Polygon drawing for admin (Leaflet.Draw)
- Elevation profiles (Open-Meteo Elevation API)
- Coverage analysis and heatmaps
- 3D terrain preview (Three.js)
- Airspace checking
- Flight parameter configuration with category engine
- Equipment management per pilot
- Certifications, memberships, documents tracking
- Shared links for customer view
- Export formats (KMZ, KML, GeoJSON, CSV, GPX)

### Deployment Fixes (this session)
- **103 hardcoded redirects** → `site_url()` across 7 controllers
- **19 hardcoded fetch paths** in JS → dynamic `appBase` variable
- **Static assets** copied to `public/static/` (CI4 document root)
- **PATH_INFO** → query string rewrite for shared hosting compatibility
- **RewriteBase** configured for subfolder deployment
- **Polygon string/array mismatch** → `resolvePolygon()` helper
- **CategoryEngine autoloader** → alias files for inner classes
- **Pilot weather fetch** path fixed in order_detail.php
- **Elevation service** json_decode validation added
- **Favicon** path fixed

### Risk Assessment Improvements (this session)
- **Flexible submit** — checklist items are optional guidance, not blockers. Only risk level + decision + declaration required
- **3-source weather fetch** dropdown:
  - Order location (backend API with drone-specific warnings)
  - Pilot's current GPS (browser geolocation)
  - Postcode/place lookup (Nominatim geocoding → Open-Meteo)
- **Weather warnings** displayed inline (wind, gusts, precipitation, visibility)
- **Battery level** changed from number input to 4-option dropdown

### Demo Setup
- **4 user accounts** with full profiles:
  - `admin` / `Admin123!` — Administrator
  - `pilot` / `Pilot123!` — James Mitchell (Photography)
  - `pilot.singh` / `Pilot123!` — Priya Singh (Inspection)
  - `pilot.chen` / `Pilot123!` — David Chen (Events)
- **7 drones** across 3 pilots (DJI Mavic 3 Pro, Mini 4 Pro, Mavic 3 Enterprise, Air 3S, Avata 2, Mavic 3 Classic)
- **8 flight plans** with varied statuses, job types, UK locations, polygons
- **6 orders** (closed, delivered, flight_complete, accepted, pending, declined)
- **28 audit trail entries**
- **3 risk assessments** (low/approved, medium/approved_with_conditions, low/approved)
- **17 waypoints**, **10 POIs**, **3 shared links**
- **9 certifications**, **6 memberships**
- **Demo mode blocks:** password changes and user creation disabled
- **Factory reset:** `https://attv.uk/flyingplan/public/demo-reset.php?token=FP-reset-2026-skyview`

---

## Known Issues / TODO

### Not Yet Fixed
- **CSRF protection disabled** (`app/Config/Filters.php` line 75) — needs careful testing before enabling
- **Spiral pattern** missing `points_per_rev` UI control in mission patterns
- **Nested config** code smell in mission patterns JS (works but confusing)

### Potential Improvements
- PDF report generation (mPDF installed but not tested on one.com)
- Email notifications (requires SMTP config)
- Image upload handling for deliverables
- Blog/news system for public form page
- Mobile-responsive improvements for pilot dashboard

### Documentation
- `docs/deployment-issues-report.md` — 8 issues found during initial deploy
- `docs/subfolder-deployment-repair-plan.md` — full audit of 108 path issues + repair plan
- `docs/current-stage.md` — this file

---

## Portfolio Integration

The FlyingPlan demo is linked from the attv.uk portfolio site:
- **Project page:** https://attv.uk/projects/flyingplan.html
- **Demo links:** Flight brief form + admin/pilot login with credentials
- **GitHub link:** https://github.com/AmigoUK/FlyingPlan

---

## File Locations

| What | Path |
|------|------|
| Local install | `/home/amigo/install/FlyingPlan/` |
| Git repo (cloned) | `/tmp/FlyingPlan/` (branch: feature/php-rewrite) |
| Remote server | `attv.uk:/customers/7/d/7/attv.uk/httpd.www/flyingplan/` |
| Portfolio site | `/var/www/html/attv_uk/` |
| .env (server) | `flyingplan/.env` |
| Error logs | `flyingplan/writable/logs/` |
