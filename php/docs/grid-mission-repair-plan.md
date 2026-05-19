# FlyingPlan — Grid Mission Planner: Repair Plan

**Date:** 2026-04-03
**Sources:** 3 independent code analyses (grid generator, terrain follow, 3D preview)
**Issues:** 3 (from tester report by Tomasz Lewandowski)

---

## Issue 3: Photo Coverage Gaps (Priority 1)

### Root Cause

`GridGenerator::generateScanLines()` (lines 94-107) emits **exactly 2 waypoints per scan line** — start and end. No intermediate photo capture points are inserted along the pass. The GSD calculator correctly computes `photo_interval_m` but its output is **never fed into the grid generator** — the two systems are completely decoupled.

### Current Data Flow

```
User clicks "Generate Grid"
  → grid-planner.js sends: { spacing_m, angle_deg, altitude_m, speed_ms, pattern }
    → Admin::generateGrid() passes config to GridGenerator
      → GridGenerator creates 2 waypoints per pass (start + end)
        → No photo interval, no drone model, no overlap %
```

### Fix

**A. Connect GSD to Grid Generator** (`Admin.php:generateGrid`)
- Read `$fp->drone_model` from the flight plan
- Call `GsdCalculator::calculateGsd()` with altitude + overlap to get `photo_interval_m`
- Inject `photo_interval_m` into config before passing to GridGenerator

**B. Add intermediate waypoints** (`GridGenerator.php:generateScanLines`)
- When `photo_interval_m` is set, interpolate points along each `yStart→yEnd` segment at that spacing
- Each intermediate point gets `action_type: 'takePhoto'`
- Turn points keep existing action_type

**C. Add overlap field to grid panel** (`grid-planner.js`)
- Add "Overlap (%)" input (default 70)
- Include in getConfig() → sent to backend

**Files:**
| File | Change |
|------|--------|
| `app/Services/GridGenerator.php` | Add interpolation loop in `generateScanLines()` |
| `app/Controllers/Admin.php` | Call GsdCalculator, inject photo_interval_m |
| `static/js/grid-planner.js` | Add overlap % input |

---

## Issue 1: Terrain Follow (Priority 2)

### Current State

**Substantially implemented and working.** `TerrainFollower::applyTerrainFollowing()` correctly recalculates waypoint altitudes as `groundElev + targetAglM` with 5 interpolated intermediate points per segment. Uses Open-Meteo Elevation API (free, no key).

### Gaps Found

| Gap | Location | Severity |
|-----|----------|----------|
| Silent zero-elevation on API failure | `Elevation.php:75-85` | Critical — saves wrong altitude |
| No below-terrain detection in chart | `elevation-profile.js:88` | Critical — `clearance < 0` invisible |
| Chart not refreshed after terrain follow | `map-admin.js:1200` | High — stale data shown |
| Chart uses only waypoints, no path interpolation | `Elevation::getWaypointElevations` | Medium — terrain spikes invisible |
| No min altitude floor | `TerrainFollower.php:32` | Medium — 0m AGL accepted |
| No DB transaction on replaceWaypoints | `Admin.php:668` | Low — partial failure risk |

### Fix

**A. Elevation API failure handling** (`Elevation.php`)
- Instead of silently returning 0.0, return `null` for failed points
- Controller checks for nulls and returns error if elevation data incomplete

**B. Below-terrain chart highlighting** (`elevation-profile.js`)
- Remove `clearance >= 0` lower bound (line 88)
- Add solid red fill for `clearance < 0` (terrain penetration)
- Add orange zone for `0 <= clearance < 20m`

**C. Auto-refresh chart after terrain follow** (`map-admin.js`)
- After successful terrain follow response, trigger elevation profile fetch + re-render

**D. Min altitude validation** (`TerrainFollower.php`)
- Clamp `target_agl_m` to minimum 5m
- Controller validates input: reject negative/zero values

**Files:**
| File | Change |
|------|--------|
| `app/Services/Elevation.php` | Null instead of 0 on failure |
| `static/js/elevation-profile.js` | Fix clearance < 0 rendering |
| `static/js/map-admin.js` | Auto-refresh chart after terrain follow |
| `app/Services/TerrainFollower.php` | Min altitude floor |
| `app/Controllers/Admin.php` | Input validation, transaction wrap |

---

## Issue 2: 3D Preview Not Interactive (Priority 3)

### Root Cause

`detail.php` line 763 loads OrbitControls from a **non-existent CDN path**:
```html
<script src="https://unpkg.com/three@0.160.0/examples/js/controls/OrbitControls.js"></script>
```

The `examples/js/` directory was **removed in three.js r148**. Version r160 only ships `examples/jsm/` (ES modules). The CDN returns 404 silently. `THREE.OrbitControls` is never defined.

`three-preview.js` line 34 guards with `if (typeof THREE.OrbitControls !== "undefined")` — evaluates to `false` → controls never initialized → 3D scene renders but cannot be rotated/zoomed/panned.

### Fix

**A. Fix OrbitControls CDN** (`detail.php`)

Option 1 (simplest): Downgrade to three.js r147 where `examples/js/` still exists:
```html
<script src="https://unpkg.com/three@0.147.0/build/three.min.js"></script>
<script src="https://unpkg.com/three@0.147.0/examples/js/controls/OrbitControls.js"></script>
```

Option 2 (modern): Use r160 with importmap:
```html
<script type="importmap">
{ "imports": { "three": "https://unpkg.com/three@0.160.0/build/three.module.js" } }
</script>
```
Requires refactoring three-preview.js to ES modules.

**Recommendation:** Option 1 — downgrade to r147. Minimal change, maximum compatibility, no module refactoring.

**B. Add re-initialization guard** (`three-preview.js`)
- Check `_initialized` at start of `init()` and clean up existing renderer before creating new one

**C. Colour-code flight path by AGL clearance** (`three-preview.js`)
- Green: > 20m AGL
- Yellow: 10-20m AGL
- Red: < 10m AGL

**Files:**
| File | Change |
|------|--------|
| `app/Views/admin/detail.php` | Fix Three.js CDN version (lines 762-763) |
| `static/js/three-preview.js` | Re-init guard, AGL colour coding |

---

## Implementation Order

| Phase | Issue | What | Effort | Impact |
|-------|-------|------|--------|--------|
| R.1 | Issue 3 | Grid photo interval interpolation | Medium | Core functionality fix |
| R.2 | Issue 2 | Fix Three.js CDN → OrbitControls works | Small | Instant interactivity |
| R.3 | Issue 1 | Elevation chart fixes + terrain follow UX | Medium | Safety improvements |

### R.1: Grid Photo Coverage (estimated ~3 files, ~50 lines changed)
1. Add overlap % to grid-planner.js
2. Admin controller calls GsdCalculator for photo_interval_m
3. GridGenerator interpolates intermediate waypoints along passes

### R.2: 3D Interactive Preview (estimated ~1 file, ~2 lines changed)
1. Change Three.js CDN from r160 to r147 in detail.php
2. OrbitControls loads → existing code works → rotation/zoom/pan enabled

### R.3: Terrain Follow Safety (estimated ~4 files, ~30 lines changed)
1. Fix elevation API failure handling
2. Fix below-terrain chart rendering
3. Auto-refresh chart after terrain follow
4. Add min altitude validation

---

## Total Scope

- **8 files** modified
- **~80 lines** of code changed
- **Zero new files** needed
- **Zero schema changes**
- All fixes are backwards-compatible — existing flight plans unaffected
