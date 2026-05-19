# FlyingPlan — Deployment Issues Report

**Date:** 2026-04-01
**Environment:** one.com shared hosting (attv.uk)
**Path:** `/flyingplan/public/`
**Stack:** PHP 8.3 / CodeIgniter 4.6 / MySQL (MariaDB)

---

## Summary

During deployment of FlyingPlan to one.com shared hosting under a subfolder (`attv.uk/flyingplan/`), several issues were encountered. All stem from two root causes: **hardcoded values assuming a root-level or local deployment**, and **missing configuration for subfolder hosting on shared environments**.

---

## Issue 1: Hardcoded baseURL redirects to local development server

**Symptom:** After installation, all navigation redirected to `https://linuxserv1.tailc29352.ts.net:5005/` instead of `attv.uk`.

**Root Cause:** `app/Config/App.php` line 19 contains:
```php
public string $baseURL = 'https://linuxserv1.tailc29352.ts.net:5005/';
```
This is hardcoded to the development Tailscale URL. While `.env` can override it, the installer does not create or populate a `.env` file with the correct `app.baseURL` value.

**Fix Applied:** Manually created `.env` with `app.baseURL = 'https://attv.uk/flyingplan/public/'`.

**Recommended Fix:**
- The installer (`install.php`) should include a step that detects or asks for the site URL and writes it to `.env`.
- `App.php` should use a generic default like `http://localhost:8080/` or an empty string, not a specific development URL.

**Severity:** Critical — site is completely unusable without manual intervention.

---

## Issue 2: Installer attempts CREATE DATABASE on shared hosting

**Symptom:** `Fatal error: Access denied for user ... to database` at `install.php:40`.

**Root Cause:** The installer offers a "Create database" checkbox and runs:
```sql
CREATE DATABASE IF NOT EXISTS `$dbName` ...
```
On shared hosting, MySQL users do not have `CREATE DATABASE` privileges. Databases must be pre-created in the hosting control panel.

**Fix Applied:** Unchecked the "Create database" option in the installer UI.

**Recommended Fix:**
- Default the "Create database" checkbox to **unchecked**.
- Add a note in the installer UI: *"On shared hosting, create the database in your hosting panel first."*
- Wrap the `CREATE DATABASE` call in a try/catch and show a user-friendly message instead of a fatal error.

**Severity:** Medium — installer crashes instead of guiding the user.

---

## Issue 3: Installer connects without database name, then calls select_db()

**Symptom:** `Access denied for user ... to database` at `install.php:42` even with "Create database" unchecked.

**Root Cause:** `install.php` line 27–31 connects to MySQL with an empty database name:
```php
$db = new mysqli($_POST['db_host'], $_POST['db_user'], $_POST['db_pass'], '', (int)$port);
```
Then calls `$db->select_db($dbName)` at line 42. On some shared hosting providers, connecting without a database name and then switching via `select_db()` is denied by permission policies.

**Fix Applied:** None directly — worked around by ensuring correct credentials were entered.

**Recommended Fix:**
- Connect directly with the database name in the `mysqli` constructor:
  ```php
  $db = new mysqli($host, $user, $pass, $dbName, $port);
  ```
- Only use the empty-database-name + `CREATE DATABASE` flow when the user explicitly opts in, and handle the permission error gracefully.

**Severity:** Medium — confusing error on shared hosting even with correct credentials.

---

## Issue 4: Database name mismatch (shared hosting naming convention)

**Symptom:** `Access denied for user 'attv_ukflyingplan2026' to database 'flyingplan2026'`.

**Root Cause:** On one.com, database names are prefixed with the account name. The user assumed the database name was `flyingplan2026` but the actual name was `attv_ukflyingplan2026` (same as the username).

**Fix Applied:** Corrected database name in `.env`.

**Recommended Fix:**
- The installer should display a hint: *"On shared hosting, the database name is often prefixed with your account name (e.g., `accountname_dbname`)."*
- Consider auto-suggesting the database name based on the username if they share a common prefix.

**Severity:** Low — user education issue, but causes a confusing error.

---

## Issue 5: Special characters in database password cause authentication failure

**Symptom:** `Access denied for user ... (using password: YES)` despite correct credentials.

**Root Cause:** The database password contained special characters (`$`, `/`, `=`) which can cause escaping issues in `.env` file parsing or shell contexts.

**Fix Applied:** User regenerated a simpler password in the hosting panel.

**Recommended Fix:**
- Document that `.env` password values with special characters should be wrapped in quotes.
- The installer should test the database connection before writing config, and display a clear error if authentication fails, suggesting password quoting or simplification.

**Severity:** Low — but causes significant debugging time.

---

## Issue 6: No PATH_INFO support — "No input file specified"

**Symptom:** All CI4 routes return "No input file specified" after configuring `RewriteBase`.

**Root Cause:** The default `.htaccess` RewriteRule passes the path via PATH_INFO:
```apache
RewriteRule ^([\s\S]*)$ index.php/$1 [L,NC,QSA]
```
Many shared hosting providers (including one.com) run PHP in CGI/FastCGI mode, which does not support PATH_INFO in this form.

**Fix Applied:** Changed the rewrite rule to use query string format:
```apache
RewriteRule ^([\s\S]*)$ index.php?/$1 [L,NC,QSA]
```

**Recommended Fix:**
- The installer should detect whether PATH_INFO is supported and auto-configure the `.htaccess` accordingly.
- Alternatively, default to the query-string format (`index.php?/`) which works universally, and document the PATH_INFO variant as optional for servers that support it.
- Add this as a note in the deployment documentation.

**Severity:** Critical — all routes broken without this fix.

---

## Issue 7: Static assets (CSS/JS) not loading — wrong path resolution

**Symptom:** Form wizard not working (all steps visible at once), no styling. JavaScript files return 500 errors.

**Root Cause:** The `static/` directory is located at `flyingplan/static/` (sibling of `public/`), but `base_url('static/...')` resolves to `flyingplan/public/static/...` which doesn't exist. The CI4 `base_url()` function correctly points to the `public/` directory, but static assets were placed outside it.

An `.htaccess` rewrite rule was attempted (`RewriteRule ^static/(.*)$ /flyingplan/static/$1 [L]`) but caused 500 errors on one.com due to path traversal restrictions on shared hosting.

**Fix Applied:** Copied the entire `static/` directory into `public/static/` so files are served directly.

**Recommended Fix:**
- **Move `static/` into `public/` in the project structure.** This is the standard convention for web-accessible assets in CI4 and most frameworks. Assets that need to be publicly accessible should live under the document root.
- Alternatively, during the build/deployment process, include a step that copies or symlinks `static/` into `public/static/`.
- Update all `base_url('static/...')` calls if the directory structure changes.

**Severity:** Critical — CSS and JavaScript completely broken, application non-functional without the fix.

---

## Issue 8: Login redirect ignores subfolder path

**Symptom:** After installer completion, clicking "Login" redirected to `https://attv.uk/login` instead of `https://attv.uk/flyingplan/public/login`.

**Root Cause:** The `RewriteBase` in `public/.htaccess` was not configured for the subfolder. Without it, CI4's URL generation and Apache's redirect resolution defaulted to the domain root.

**Fix Applied:** Added `RewriteBase /flyingplan/public/` to `public/.htaccess`.

**Recommended Fix:**
- The installer should detect the subfolder path and auto-configure `RewriteBase` in `.htaccess`.
- Alternatively, add a deployment step that asks for the subfolder path if not at root.

**Severity:** High — login and all internal navigation broken.

---

## Summary Table

| # | Issue | Root Cause | Severity | Category |
|---|-------|-----------|----------|----------|
| 1 | Redirect to dev server | Hardcoded baseURL in App.php | Critical | Hardcoding |
| 2 | CREATE DATABASE fails | Assumes DB create privileges | Medium | Hosting assumptions |
| 3 | select_db() denied | Connects with empty DB name | Medium | Hosting assumptions |
| 4 | Wrong database name | Shared hosting naming conventions | Low | Documentation |
| 5 | Password auth failure | Special chars in .env | Low | Configuration |
| 6 | No input file specified | PATH_INFO not supported (CGI) | Critical | Hosting assumptions |
| 7 | Static assets 404/500 | static/ outside document root | Critical | Project structure |
| 8 | Login redirect to root | Missing RewriteBase for subfolder | High | Configuration |

---

## Recommendations for Future Deployments

1. **Never hardcode development URLs** in committed config files. Use `.env` exclusively for environment-specific values with safe defaults in `App.php`.
2. **Design the installer for shared hosting** — it is the primary target. Assume: no CLI, no CREATE DATABASE, CGI/FastCGI PHP, subfolder deployment.
3. **Place all web-accessible assets under `public/`** — this is the framework convention and avoids path resolution issues entirely.
4. **Auto-detect or ask for the deployment path** in the installer and configure `RewriteBase` and `baseURL` accordingly.
5. **Default to query-string URL format** (`index.php?/route`) which works on all hosting types, with PATH_INFO as a documented optional upgrade.
6. **Wrap all database operations in try/catch** with user-friendly error messages instead of exposing PHP fatal errors.
