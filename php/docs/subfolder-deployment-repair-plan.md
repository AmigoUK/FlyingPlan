# FlyingPlan — Subfolder Deployment Repair Plan

**Date:** 2026-04-01
**Audited by:** Automated code audit (3 parallel scans)
**Scope:** All JS, PHP views, controllers, services — looking for hardcoded paths, data type mismatches, and security gaps

---

## Executive Summary

The app was written assuming root deployment (`/`). When deployed in a subfolder (`/flyingplan/public/`), **105 hardcoded path issues** and **3 additional bugs** break functionality. The JavaScript layer is now clean (fixed earlier), but the PHP controller layer has widespread hardcoded redirects.

---

## Issues Found

### CRITICAL — Hardcoded `redirect()->to('/')` in Controllers (103 instances)

Every `redirect()->to('/some/path')` breaks in subfolder deployment because CI4 redirects to the domain root (`attv.uk/admin/`) instead of the app root (`attv.uk/flyingplan/public/admin/`).

**Fix:** Replace `redirect()->to('/path')` with `redirect()->to(site_url('path'))` in all controllers.

| Controller | Count | Example Lines |
|-----------|-------|---------------|
| `Pilot.php` | 34 | 94, 99, 146, 150, 157, 162, 172, 178, 183, 199, 207, 270, 342, 370, 405, 409, 421, 426, 443, 458, 466, 475, 483, 489, 497, 505, 511, 531, 539, 545, 548, 563, 575, 584 |
| `Pilots.php` | 25 | 26, 34, 61, 65, 81, 92, 101, 113, 120, 123, 132, 143, 150, 153, 162, 187, 194, 197, 206, 211, 231, 238, 243, 253, 260 |
| `Settings.php` | 24 | 46, 62, 73, 78, 95, 106, 118, 134, 145, 149, 160, 165, 178, 188, 194, 210, 220, 231, 236, 249, 259, 265, 281, 291 |
| `Orders.php` | 9 | 58, 89, 134, 153, 167, 191, 205, 215, 222 |
| `Auth.php` | 5 | 46, 75, 77, 81, 94 |
| `Admin.php` | 4 | 485, 539, 552, 565 |
| `PublicForm.php` | 2 | 48, 164 |

**Transformation pattern:**
```php
// BEFORE (broken in subfolder):
return redirect()->to('/pilot/profile')->with('flash_success', 'Updated.');

// AFTER (works everywhere):
return redirect()->to(site_url('pilot/profile'))->with('flash_success', 'Updated.');
```

**Automated fix approach:** A single `sed`-like regex replacement across all controllers:
- Pattern: `redirect()->to('/(.*?)')` → `redirect()->to(site_url('$1'))`
- Must handle both single and double quotes
- Must NOT touch external URLs or empty strings

---

### CRITICAL — CSRF Protection Disabled

**File:** `app/Config/Filters.php` line 75

CSRF filter is commented out in global filters:
```php
'globals' => [
    'before' => [
        // 'csrf',  ← DISABLED
    ],
```

The JavaScript correctly sends `X-CSRFToken` headers on all POST requests, but the server never validates them. Any POST endpoint is vulnerable to CSRF attacks.

**Fix:** Uncomment the CSRF line. Note: this requires ensuring all POST endpoints (including AJAX JSON endpoints) properly handle CSRF tokens. CI4's CSRF filter supports header-based tokens via `X-CSRFToken`.

**Risk:** Enabling CSRF may break some endpoints if the token isn't passed correctly. Test thoroughly after enabling.

---

### HIGH — Hardcoded fetch() in Pilot View

**File:** `app/Views/pilot/order_detail.php` line 240

```html
onclick="fetch('/pilot/orders/<?= esc($order->id) ?>/weather')..."
```

**Fix:**
```html
onclick="fetch('<?= site_url('pilot/orders/' . $order->id . '/weather') ?>')..."
```

---

### MEDIUM — Hardcoded Favicon Path

**File:** `app/Views/welcome_message.php` line 8

```html
<link rel="shortcut icon" type="image/png" href="/favicon.ico">
```

**Fix:**
```html
<link rel="shortcut icon" type="image/png" href="<?= base_url('favicon.ico') ?>">
```

---

### MEDIUM — json_decode Error Handling in Elevation Service

**File:** `app/Services/Elevation.php` line 60

```php
$data = json_decode($response, true);  // No validation
$elevations = $data['elevation'] ?? [];
```

If API returns invalid JSON, `$data` is `null` → silent failure.

**Fix:** Add `is_array($data)` check and log warning.

---

### MAJOR (Functional) — Spiral Pattern Missing UI Control

**File:** `static/js/mission-patterns.js` lines 166-171

The spiral pattern config collects `num_revolutions` but not `points_per_rev`. PHP defaults it to 12. Users can't control spiral density.

**Fix:** Add `points_per_rev` input field for spiral mode and include in getConfig().

---

### LOW — Nested Config Code Smell

**File:** `static/js/map-admin.js` lines 775-784

Mission patterns double-nest the config object unnecessarily. Works but confusing.

---

## Repair Priority

| Priority | Issue | Instances | Effort | Impact |
|----------|-------|-----------|--------|--------|
| 1 | Controller redirects | 103 | Medium | All navigation breaks |
| 2 | CSRF disabled | 1 | Low | Security vulnerability |
| 3 | Pilot view fetch path | 1 | Low | Pilot weather broken |
| 4 | Elevation json_decode | 1 | Low | Silent API failures |
| 5 | Favicon path | 1 | Low | Missing icon |
| 6 | Spiral points_per_rev | 1 | Low | Feature incomplete |

---

## Repair Approach

### Step 1: Fix all 103 redirects (bulk operation)

For each of the 7 controller files:
1. Read the file
2. Replace all `redirect()->to('/` with `redirect()->to(site_url('/`
3. Verify `use CodeIgniter\Config\Services;` or that `site_url()` is available (it's a CI4 global helper, available by default)
4. Upload to server

**Regex:** `->to\('\/` → `->to(site_url('/`  
Also need to close the extra paren: `'\)` → `'))`

More precisely:
```
redirect()->to('/something')  →  redirect()->to(site_url('something'))
```

### Step 2: Fix pilot view fetch path

Single line edit in `app/Views/pilot/order_detail.php`.

### Step 3: Enable CSRF (with testing)

Uncomment in `Filters.php`. Test all forms and AJAX endpoints still work.

### Step 4: Fix Elevation service

Add null check after `json_decode`.

### Step 5: Fix favicon and spiral pattern

Minor single-file edits.

### Step 6: Upload all fixed files to server

Upload all modified files via SFTP and copy JS to `public/static/js/`.

### Step 7: Update demo-reset.php

If controller files changed, update the demo-reset script path references if needed.

---

## Files to Modify

| File | Changes |
|------|---------|
| `app/Controllers/Admin.php` | 4 redirect fixes |
| `app/Controllers/Pilot.php` | 34 redirect fixes |
| `app/Controllers/Pilots.php` | 25 redirect fixes |
| `app/Controllers/Settings.php` | 24 redirect fixes |
| `app/Controllers/Orders.php` | 9 redirect fixes |
| `app/Controllers/Auth.php` | 5 redirect fixes |
| `app/Controllers/PublicForm.php` | 2 redirect fixes |
| `app/Views/pilot/order_detail.php` | 1 fetch path fix |
| `app/Views/welcome_message.php` | 1 favicon fix |
| `app/Config/Filters.php` | 1 CSRF enable |
| `app/Services/Elevation.php` | 1 json_decode fix |
| `static/js/mission-patterns.js` | 1 spiral UI fix |

**Total: 12 files, ~108 changes**

---

## Verification Plan

After all fixes:
1. Login as admin → redirects to `/flyingplan/public/admin` (not `/admin`)
2. Login as pilot → redirects to `/flyingplan/public/pilot`
3. Submit public form → redirects to confirmation page
4. Edit pilot profile → flash message + stays on profile page
5. Assign order to pilot → redirect to order detail
6. Change settings → redirect back to settings
7. Pilot weather button works
8. Grid/Pattern/Coverage tools work
9. All POST endpoints still work with CSRF enabled
10. Elevation profile loads without errors
