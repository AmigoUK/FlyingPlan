# FlyingPlan — Master Implementation Plan

**Date:** 2026-04-03
**Consolidates:** product-improvement-analysis.md, configurable-templates-design.md, lite-vs-modular-decision.md
**Decision:** One codebase with APP_TIER feature gating (unanimous 3-0 vote against splitting)

---

## Architecture Decision

No fork. FlyingPlan remains one codebase. Different operator profiles are served by a template-based configuration system with `APP_TIER` in `.env` and JSON settings columns in `app_settings`.

---

## Implementation Phases (ordered by dependency + impact)

### Phase 0: Bug Fixes & Quick Wins
**No dependencies. Ship independently.**

| # | Task | Files | Effort |
|---|---|---|---|
| 0.1 | Fix customer language: ABN→"Company Reg / VAT", postcode hint, "Flight Prefs"→"Flight Preferences", camera angle labels, "No-Fly Zone"→"Nearby Restrictions" | `app/Views/public/form.php` | Small |
| 0.2 | Fix conflicting `ADMIN_VALID_TRANSITIONS` — remove dead code from OrderModel, keep controller version as single source of truth | `app/Models/OrderModel.php` | Tiny |
| 0.3 | Add `cancelled` status to orders | `app/Models/OrderModel.php`, `app/Controllers/Orders.php`, order views | Small |
| 0.4 | Add pagination to dashboard and orders list (25 per page) | `app/Controllers/Admin.php`, `app/Controllers/Orders.php`, dashboard + orders views | Small |
| 0.5 | Improve confirmation page — add response time expectation, reference code usage note, email confirmation text | `app/Views/public/confirmation.php` | Tiny |
| 0.6 | Add `source` field to flight_plans (`public_form` / `operator_created`) | Migration + `FlightPlanModel` | Tiny |

### Phase 1: Templates Foundation
**Database + config infrastructure. No UI yet.**

| # | Task | Files | Effort |
|---|---|---|---|
| 1.1 | Database migration: add 7 new columns to `app_settings` (`active_template`, `modules_json`, `solo_mode`, `default_drone_model`, `form_fields_json`, `planning_panels_json`, `pilot_steps_json`) | Migration script, `AppSettingsModel` | Small |
| 1.2 | Create `app/Config/TemplateDefinitions.php` — 5 template arrays with complete field/module/panel configs | New file | Small |
| 1.3 | Create `app/Config/FormFieldRegistry.php` — 34 field definitions (label, step, default mode, always-required flag) | New file | Small |
| 1.4 | Add 4 helper methods to `AppSettingsModel`: `isModuleEnabled()`, `getFieldMode()`, `isPanelEnabled()`, `isPilotStepEnabled()` | `app/Models/AppSettingsModel.php` | Small |
| 1.5 | Implement `AppSettingsModel::applyTemplate()` — reads template definition, writes JSON columns, toggles job_types.is_active | `app/Models/AppSettingsModel.php` | Small |
| 1.6 | Create `app/Filters/ModuleGate.php` — CI4 Before Filter checking module flags | New file + `app/Config/Filters.php` | Small |
| 1.7 | Register `module:planning`, `module:compliance`, `module:team` filter aliases on route groups | `app/Config/Routes.php` | Small |
| 1.8 | Convert old boolean settings to new JSON format in migration (preserve existing behaviour) | Migration script | Small |

### Phase 2: Template Selection UI
**First visible change for operators.**

| # | Task | Files | Effort |
|---|---|---|---|
| 2.1 | Create `app/Views/admin/settings_templates.php` — responsive card grid for 5 templates (mobile: 1 col, tablet: 2, desktop: 3) | New view file | Medium |
| 2.2 | Add `Settings::applyTemplate()` POST action | `app/Controllers/Settings.php` | Small |
| 2.3 | Restructure `settings.php` as shell that includes either templates or configure view | `app/Views/admin/settings.php` | Small |
| 2.4 | Active template badge + "Current Setup" indicator on cards | CSS + view logic | Small |
| 2.5 | Add template selection step to installer | `public/install.php` | Small |

### Phase 3: Manual Configuration UI
**The "Customise" screen — replaces current flat toggles.**

| # | Task | Files | Effort |
|---|---|---|---|
| 3.1 | Create `app/Views/admin/settings_configure.php` — accordion (mobile) / sidebar (desktop) with 6 sections | New view file | Large |
| 3.2 | Modules section — 4 toggle cards (Planning, Compliance, Team, Analytics) + Solo/Team switch, AJAX save | View + Settings controller | Medium |
| 3.3 | Customer Form section — 34 fields with 3-state dropdowns (Required/Optional/Hidden), grouped by wizard step | View + `Settings::saveFormFields()` | Medium |
| 3.4 | Planning Tools section — 9 panel toggle cards | View + `Settings::savePlanningPanels()` | Small |
| 3.5 | Pilot Workflow section — flight params + risk assessment toggles | View + `Settings::savePilotSteps()` | Small |
| 3.6 | "Preview Customer Form" button — modal on mobile, side panel on desktop | JS + view | Medium |
| 3.7 | "Reset to template defaults" per section with confirmation | JS + controller | Small |
| 3.8 | Carry over existing Branding, Job Types, Purposes, Heard About sections unchanged | Existing code, minor restructure | Small |

### Phase 4: View Gating
**Make hidden features actually disappear.**

| # | Task | Files | Effort |
|---|---|---|---|
| 4.1 | Update `form.php` — wrap all 34 configurable fields with `getFieldMode()` checks. Hidden = not rendered. Required = `required` attribute. | `app/Views/public/form.php` | Medium |
| 4.2 | Update `form-wizard.js` — skip wizard steps where ALL fields are hidden (auto-advance) | `static/js/form-wizard.js` | Small |
| 4.3 | Update `admin/detail.php` — wrap 9 planning panels with `isPanelEnabled()` | `app/Views/admin/detail.php` | Medium |
| 4.4 | Update navigation partial — hide Pilots/Orders links when team module off | `app/Views/partials/navbar.php` | Small |
| 4.5 | Update pilot order detail — hide flight-params/risk-assessment buttons when compliance off | `app/Views/pilot/order_detail.php` | Small |
| 4.6 | Fix customer-facing language (Phase 0.1 items) while touching form.php | `app/Views/public/form.php` | Combined |
| 4.7 | Dynamic server-side validation in `PublicForm::submit()` — build rules from FormFieldRegistry + settings | `app/Controllers/PublicForm.php` | Small |

### Phase 5: Operator Quick-Create
**Admin can create flight plans from phone/email.**

| # | Task | Files | Effort |
|---|---|---|---|
| 5.1 | Add `Admin::quickCreate()` action — minimal modal: name, email, phone, job type, description, location (free text) | `app/Controllers/Admin.php` | Small |
| 5.2 | Create quick-create modal view (Bootstrap modal in dashboard) | `app/Views/admin/partials/quick_create_modal.php` | Small |
| 5.3 | Route: `POST /admin/quick-create` | `app/Config/Routes.php` | Tiny |
| 5.4 | Set `source = 'operator_created'` on manually created plans | Controller logic | Tiny |
| 5.5 | Add "New Brief" button to dashboard header | `app/Views/admin/dashboard.php` | Tiny |

### Phase 6: Solo Operator Mode
**Collapse admin+pilot into one workflow.**

| # | Task | Files | Effort |
|---|---|---|---|
| 6.1 | Auto-assignment: when `solo_mode = true`, new flight plans auto-create order assigned to admin user with status `accepted` | `app/Controllers/PublicForm.php`, `app/Controllers/Admin.php` | Medium |
| 6.2 | Merged detail view: solo admin sees customer brief + map + planning tools + flight params + risk assessment + deliverables on one page | `app/Views/admin/detail.php` (extend) | Large |
| 6.3 | Skip order creation step in solo mode — dashboard "Open" button goes straight to detail | `app/Views/admin/dashboard.php` | Small |
| 6.4 | Hide Pilots nav, simplify Orders list in solo mode | Navigation partial + views | Small |
| 6.5 | Solo pilot can upload deliverables from admin detail view | Controller + view | Medium |

### Phase 7: Draft/Resume System
**Save incomplete customer submissions.**

| # | Task | Files | Effort |
|---|---|---|---|
| 7.1 | Add columns: `draft_token`, `last_step_reached`, `draft_expires_at` to flight_plans | Migration | Tiny |
| 7.2 | Add statuses: `draft`, `incomplete`, `abandoned` to FlightPlanModel::STATUSES | Model | Tiny |
| 7.3 | AJAX step-save endpoint: `POST /submit-draft` — saves on each "Next" click | `app/Controllers/PublicForm.php` | Medium |
| 7.4 | Admin view: `/admin/incomplete` — partial submissions list with contact info | Controller + view | Medium |
| 7.5 | Resume route: `GET /resume/{draft_token}` — pre-fills form from draft | `app/Controllers/PublicForm.php` | Medium |
| 7.6 | Auto-abandon cron: mark drafts older than 72h as `abandoned` | Standalone script or scheduled task | Small |
| 7.7 | Exclude draft/abandoned from main dashboard (filter default) | Dashboard controller | Tiny |

### Phase 8: Polish
**Lower priority, ship when ready.**

| # | Task | Effort |
|---|---|---|
| 8.1 | Waypoint reordering (drag in list panel) | Medium |
| 8.2 | Undo system for route editing | Medium |
| 8.3 | Mobile POI input (replace prompt() with Bootstrap modal + long-press) | Small |
| 8.4 | Column sorting on all list views | Small |
| 8.5 | Email notification system (SMTP config in settings, templates for new submission / status change) | Large |
| 8.6 | Per-job-type field overrides (exceptions on top of base config) | Medium |
| 8.7 | Pricing/estimate field on job types | Small |

---

## Dependency Map

```
Phase 0 (bug fixes) ──────────────────── can ship anytime
Phase 1 (templates foundation) ────────── prerequisite for everything below
Phase 2 (template selection UI) ───────── depends on Phase 1
Phase 3 (manual config UI) ───────────── depends on Phase 1
Phase 4 (view gating) ────────────────── depends on Phase 1, combines with Phase 0.1
Phase 5 (quick-create) ───────────────── depends on Phase 0.6, otherwise independent
Phase 6 (solo mode) ──────────────────── depends on Phase 1 + 4 (modules must gate first)
Phase 7 (draft/resume) ───────────────── depends on Phase 4 (form field gating)
Phase 8 (polish) ─────────────────────── independent items, ship when ready
```

---

## Total Scope

- **47 tasks** across 9 phases
- **~15 files modified**, **~8 new files created**
- **Zero new tables** (columns added to existing `app_settings` and `flight_plans`)
- **Zero code duplication** — one codebase, one repo, one deploy

---

## Files Reference

| File | Phases touched |
|---|---|
| `app/Models/AppSettingsModel.php` | 1 |
| `app/Models/FlightPlanModel.php` | 0, 7 |
| `app/Models/OrderModel.php` | 0 |
| `app/Config/TemplateDefinitions.php` (new) | 1 |
| `app/Config/FormFieldRegistry.php` (new) | 1 |
| `app/Config/Routes.php` | 1, 5 |
| `app/Config/Filters.php` | 1 |
| `app/Filters/ModuleGate.php` (new) | 1 |
| `app/Controllers/Settings.php` | 2, 3 |
| `app/Controllers/Admin.php` | 0, 5, 6 |
| `app/Controllers/Orders.php` | 0 |
| `app/Controllers/PublicForm.php` | 4, 6, 7 |
| `app/Controllers/Pilot.php` | (minor, Phase 4) |
| `app/Views/admin/settings.php` | 2, 3 |
| `app/Views/admin/settings_templates.php` (new) | 2 |
| `app/Views/admin/settings_configure.php` (new) | 3 |
| `app/Views/admin/dashboard.php` | 0, 5, 6 |
| `app/Views/admin/detail.php` | 4, 6 |
| `app/Views/public/form.php` | 0, 4 |
| `app/Views/public/confirmation.php` | 0 |
| `app/Views/partials/navbar.php` | 4 |
| `app/Views/pilot/order_detail.php` | 4 |
| `static/js/form-wizard.js` | 4 |
| `public/install.php` | 2 |
