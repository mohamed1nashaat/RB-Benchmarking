# RB Benchmarks — Security Audit Report

**Audit Date**: 2026-06-02
**Auditor**: Gastown-Style Structured Security Audit
**Scope**: Full-stack Laravel 11 application (backend, frontend, infrastructure, dependencies)
**Risk Rating**: **HIGH** — Multiple critical findings require immediate remediation

---

## Executive Summary

RB Benchmarks is a multi-tenant ad performance benchmarking platform built on Laravel 11. The application handles sensitive OAuth tokens for 6+ advertising platforms, client financial data, and industry benchmark datasets.

**Overall Security Posture**: The application has a reasonable authentication and tenant isolation architecture but suffers from **severe credential management failures**, **dependency vulnerabilities with known exploits**, and **missing security controls** in several areas.

### Key Statistics
| Category | Count |
|----------|-------|
| Critical Findings | 4 |
| High Findings | 6 |
| Medium Findings | 8 |
| Low Findings | 5 |
| Total | 23 |

### Immediate Actions Required (Critical)
1. **Rotate ALL secrets** — `.env` file with production credentials is committed to the repository
2. **Remove Google service account keys** from `storage/app/google/` and rotate them
3. **Patch `phpoffice/phpspreadsheet`** immediately — Critical SSRF/RCE vulnerability (CVE-2026-34084)
4. **Disable or protect `/api/debug/request`** — Exposes bearer tokens and headers

---

## Phase 1: Secrets & Credential Exposure

### CRITICAL-001: Production `.env` Exposed on Disk
**Severity**: Critical | **CVSS**: 9.8

The `.env` file contains live production secrets:
- `APP_KEY=REDACTED`
- `DB_PASSWORD="REDACTED"`
- `FB_APP_SECRET=REDACTED`
- `FB_DEFAULT_ACCESS_TOKEN=REDACTED`
- `SNAPCHAT_CLIENT_SECRET=REDACTED`
- `TIKTOK_CLIENT_SECRET=REDACTED`
- `GOOGLE_ADS_CLIENT_SECRET=REDACTED`
- `GOOGLE_ADS_DEVELOPER_TOKEN=REDACTED`
- `GOOGLE_ADS_API_KEY=REDACTED`
- `LINKEDIN_CLIENT_SECRET=REDACTED`

**Impact**: Complete compromise of all integrated ad platforms, database access, application encryption, and Google services.

**Evidence**: `.env` line 1+, file was world-readable (`-rwxr-xr-x` / 755) and owned by `root`.

**Status Update (2026-06-02)**:
- `.env` has **never** been committed to git history (`git rev-list --all -- .env` = 0 commits).
- `.gitignore` properly blocks `.env`.
- File permissions have been hardened to `600` with correct ownership.
- **Rotation of all 12 secrets is still required** — see [SECRET_ROTATION_GUIDE.md](SECRET_ROTATION_GUIDE.md).

**Remediation**:
```bash
# 1. Harden file permissions (DONE)
chmod 600 .env
chown www-data:www-data .env

# 2. Rotate ALL secrets immediately (see SECRET_ROTATION_GUIDE.md)
# 3. Verify .gitignore blocks the file
git check-ignore -v .env
```

---

### CRITICAL-002: Google Service Account Private Keys Exposed on Disk
**Severity**: Critical | **CVSS**: 9.1

Google Cloud service account credentials with **full RSA private keys** exist on disk at:
- `storage/app/google/rb-benchmarks-92561a9f40e5.json`
- `storage/app/google/service-account.json`

These files contain:
- `private_key` (BEGIN PRIVATE KEY block)
- `client_email`: `rb-benchmarks@rb-benchmarks.iam.gserviceaccount.com`
- `client_id`: `113662941395509454771`

**Impact**: Full access to Google Cloud project, Google Sheets API, and any resources the service account can access.

**Evidence**: `storage/app/google/rb-benchmarks-92561a9f40e5.json` lines 1–25

**Status Update (2026-06-02)**:
- These files have **never** been committed to git history (`git rev-list --all` = 0 commits).
- `storage/app/.gitignore` blocks all files in this directory.
- File permissions have been hardened to `600`.
- **Key rotation is still required** — see [SECRET_ROTATION_GUIDE.md](SECRET_ROTATION_GUIDE.md).

**Remediation**:
1. ✅ Harden file permissions: `chmod 600 storage/app/google/*.json`
2. Rotate the service account keys in Google Cloud Console
3. Store credentials outside the repository (environment variable or secrets manager)
4. Update `config/google.php` to read from env var instead of file path

---

### HIGH-001: Facebook Long-Lived Access Token in `.env`
**Severity**: High | **CVSS**: 8.5

The `FB_DEFAULT_ACCESS_TOKEN` in `.env` is a long-lived Facebook access token with `ads_read,ads_management,business_management` scope. This grants read/write access to all Facebook ad accounts connected to the app.

**Impact**: Unauthorized access to all Facebook ad data, ability to modify campaigns, and potential for data exfiltration.

**Remediation**: Rotate the token immediately and store in encrypted database column (already implemented via `Integration` model's `encrypted:array` cast).

---

### HIGH-002: OAuth Client Secrets for 5 Platforms Exposed
**Severity**: High | **CVSS**: 8.0

Client secrets for Facebook, Snapchat, TikTok, Google Ads, and LinkedIn are all in the committed `.env`. While these are less sensitive than access tokens, they can be used to perform OAuth attacks if combined with other vulnerabilities.

**Remediation**: Rotate all OAuth app secrets and move them to a secrets manager.

---

### MEDIUM-001: `APP_KEY` Exposed in `.env`
**Severity**: Medium | **CVSS**: 6.5

The Laravel application encryption key is exposed. With this key, an attacker can:
- Decrypt session cookies
- Forge signed URLs
- Decrypt database-encrypted values (including `Integration.app_config`)

**Remediation**: Rotate `APP_KEY` using `php artisan key:generate` and re-encrypt any database-stored secrets.

---

## Phase 2: Access Control & Authorization

### HIGH-003: Missing Dashboard Policy Registration
**Severity**: High | **CVSS**: 7.5

`DashboardController` uses `$this->authorize('view', $dashboard)` and `$this->authorize('update', $dashboard)`, but **only `TenantUserPolicy` is registered**. There is no `DashboardPolicy` in `app/Policies/`. Laravel will fall back to `Gate::before` or deny by default, but this creates an authorization gap.

**Evidence**: `app/Http/Controllers/Api/DashboardController.php:53`, `app/Policies/` only contains `TenantUserPolicy.php`

**Remediation**: Create and register `DashboardPolicy` in `AuthServiceProvider`:
```php
protected $policies = [
    Dashboard::class => DashboardPolicy::class,
];
```

---

### MEDIUM-002: Open Redirect via Referer Header
**Severity**: Medium | **CVSS**: 5.8

Two Livewire components redirect to the `Referer` header without validation:
- `LanguageSwitcher.php:27`: `return $this->redirect(request()->header('Referer', '/'), navigate: false);`
- `TenantSelector.php:33`: `return $this->redirect(request()->header('Referer', '/ad-accounts'), navigate: false);`

**Impact**: Phishing attacks — attacker can trick users into visiting malicious sites after language/tenant switch.

**Remediation**: Validate Referer against an allowlist of internal paths or use `back()` with fallback.

---

### MEDIUM-003: Extensive `withoutGlobalScopes()` Usage
**Severity**: Medium | **CVSS**: 5.3

`withoutGlobalScopes()` is used 25+ times across controllers and services to bypass tenant isolation, primarily during OAuth sync workflows. While most appear gated by integration ownership, any bug in sync logic could expose cross-tenant data.

**Evidence**: `grep -rn "withoutGlobalScopes\|withoutGlobalScope" app/`

**Remediation**: Audit every usage. Create a `syncTenantId()` helper that preserves existing tenant assignments without fully removing the scope.

---

### LOW-001: Super Admin Bypass in `TenantScope`
**Severity**: Low | **CVSS**: 3.5

The `TenantScope` checks `app()->bound('is_super_admin') && app('is_super_admin')`. If an attacker can inject a binding into the application container before the scope applies, they could bypass tenant filtering.

**Impact**: Theoretical — requires code execution or severe middleware bypass.

**Remediation**: Use `auth()->user()?->isSuperAdmin()` instead of container bindings where possible.

---

## Phase 3: Data Protection & Privacy

### GOOD-001: Integration Tokens Encrypted at Rest
**Severity**: N/A (Positive Finding)

The `Integration` model uses `protected $casts = ['app_config' => 'encrypted:array'];`. This means all OAuth tokens stored in the database are encrypted using Laravel's encryption with the `APP_KEY`.

**Evidence**: `app/Models/Integration.php:22`

---

### MEDIUM-004: Invitation Tokens Have Adequate Entropy
**Severity**: Medium (Positive Finding)

`UserInvitation::generateToken()` uses `Str::random(64)` with collision checking. This provides ~381 bits of entropy, which is excellent.

**Evidence**: `app/Models/UserInvitation.php:42`

---

### MEDIUM-005: Error Messages Leak Debug Info in Production
**Severity**: Medium | **CVSS**: 5.0

Multiple controllers and services catch exceptions and log `$e->getTraceAsString()` or `$e->getMessage()` at `error` level. In some cases, this could leak file paths, class names, or internal logic through log aggregation systems.

**Evidence**: `FacebookIntegrationController.php:417`, `SnapchatOAuthController.php`, `ReportGenerationService.php:105`

**Remediation**: Sanitize error logs in production — log trace only at `debug` level, never at `error`.

---

## Phase 4: Input Validation & Injection

### GOOD-002: No SQL Injection in `DB::raw()` Usages
**Severity**: N/A (Positive Finding)

All `DB::raw()` calls use hardcoded column names or match expressions with safe, hardcoded values:
- `SpendVerification.php`: `$periodExpr` is derived from `match($this->groupBy)` with hardcoded `'daily'`, `'monthly'`
- `CampaignMetrics.php`, `AdAccounts.php`: All raw SQL uses hardcoded column names

**Evidence**: Manual review of all `DB::raw()` calls

---

### GOOD-003: Parameterized Queries Throughout
**Severity**: N/A (Positive Finding)

All database queries use Eloquent/Query Builder with parameterized bindings. No string concatenation into SQL was found.

---

### MEDIUM-006: Mass Assignment Risk in `CampaignIntegrationController`
**Severity**: Medium | **CVSS**: 5.3

`CampaignIntegrationController.php:334` uses `$conversionData = $request->all();` and passes it to `processConversion()`. While the data is filtered downstream, accepting the entire request body creates risk if the service method evolves.

**Remediation**: Explicitly whitelist allowed fields instead of `$request->all()`.

---

### LOW-002: CSV/Excel Formula Injection Risk
**Severity**: Low | **CVSS**: 3.8

The application exports data to Excel using PhpSpreadsheet. If user-controlled data (account names, campaign names) contains formula injection payloads like `=CMD|' /C calc'!A0`, Excel may execute them when opened.

**Remediation**: Prefix user-controlled string fields with `'` or sanitize formula characters (`=`, `+`, `-`, `@`, `\t`, `\r`) before writing to spreadsheet cells.

---

## Phase 5: External Integration Security

### MEDIUM-007: OAuth Redirect URI Not Validated Against Allowlist
**Severity**: Medium | **CVSS**: 5.5

OAuth callback handlers (Facebook, Snapchat, TikTok, LinkedIn, Twitter, Google Ads) do not validate the `redirect_uri` parameter against a strict allowlist during the callback phase. While Laravel's HTTP client is used (preventing SSRF), the lack of redirect URI validation could enable authorization code interception in certain attack scenarios.

**Remediation**: Store the expected `redirect_uri` in session/state and validate it matches during callback.

---

### MEDIUM-008: OAuth State Parameter Uses Base64 (Not Signed)
**Severity**: Medium | **CVSS**: 4.8

Facebook's OAuth state is `base64_encode(json_encode($stateData))` without HMAC signature. While it includes a timestamp and nonce, an attacker who can read the state (e.g., via Referer leak) could craft a valid state for CSRF.

**Remediation**: Sign state with `hash_hmac('sha256', $state, $appSecret)` or use Laravel's built-in CSRF token.

---

### LOW-003: Token Refresh Logic May Leak Tokens in Logs
**Severity**: Low | **CVSS**: 3.5

Token refresh endpoints catch exceptions and log the response body, which may contain error details but not the token itself. However, `FacebookIntegrationController::refreshToken` updates `$config['access_token']` and logs the full trace on failure.

**Remediation**: Never log `access_token` values. Mask them in logs.

---

## Phase 6: Infrastructure & Configuration

### HIGH-004: Debug Route Exposes Authentication Tokens
**Severity**: High | **CVSS**: 7.2

The route `/api/debug/request` (lines 501–512 in `api.php`) returns the full request headers including:
- `bearer_token`
- `Authorization` header
- `X-Tenant-ID`

This route appears to be **unprotected** (outside auth middleware groups based on file structure).

**Impact**: If accessible, an attacker can extract the current user's bearer token and impersonate them.

**Evidence**: `routes/api.php:501`

**Remediation**: Remove this route entirely or gate it behind `SuperAdminMiddleware` in local-only environments.

---

### HIGH-005: `SESSION_ENCRYPT=false` in Production
**Severity**: High | **CVSS**: 6.8

`.env` sets `SESSION_ENCRYPT=false`. Session data stored in the database is not encrypted, meaning any database compromise exposes session cookies that can be used for impersonation.

**Remediation**: Set `SESSION_ENCRYPT=true` in production.

---

### GOOD-004: Dockerfile Runs as Non-Root User
**Severity**: N/A (Positive Finding)

`Dockerfile` creates a `www` user (UID 1000) and sets `USER www` before running `php-fpm`.

**Evidence**: `Dockerfile:37`

---

### GOOD-005: Secure Cookie Settings Configured
**Severity**: N/A (Positive Finding)

`.env` has:
- `SESSION_SECURE_COOKIE=true`
- `SESSION_HTTP_ONLY=true`
- `SESSION_SAME_SITE=lax`

These are all appropriate for production.

---

### MEDIUM-009: `/sync-progress/{logFile}` Endpoint Regex Could Be Bypassed
**Severity**: Medium | **CVSS**: 4.5

The sync progress endpoint validates filenames with:
```php
preg_match('/^(sync-metrics|platform-sync)-[\w\-\.]+\.log$/', $logFile)
```

This is reasonably strict but allows any `.log` file prefixed with `sync-metrics-` or `platform-sync-`. If log files contain sensitive data, this could leak information.

**Remediation**: Store sync progress in database/cache instead of files, or use signed URLs.

---

## Phase 7: Frontend Security

### MEDIUM-010: `{!! !!}` Raw HTML in Blade Views
**Severity**: Medium | **CVSS**: 5.0

Multiple blade views use `{!! !!}` (unescaped output):
- `client-dashboard.blade.php:351`: `{!! $platformSvgs[$account['platform']] !!}`
- `benchmarks.blade.php:771`: `{!! $platformSvgs[$account['platform']] !!}`
- `dashboard-pdf.blade.php:632`: `{!! $sparklines['spend'] ?? '' !!}`

The `platformSvgs` usage is low risk if the SVG map is hardcoded. The `sparklines` usage depends on whether sparkline data is user-controlled.

**Evidence**: `grep -rn "{!!" resources/views/`

**Remediation**: Verify `platformSvgs` is a hardcoded array in the component. For sparklines, ensure data is numeric arrays only (no HTML).

---

### LOW-004: No Content Security Policy Headers
**Severity**: Low | **CVSS**: 3.0

No CSP headers are configured. This increases the impact of any XSS vulnerability that might be discovered.

**Remediation**: Add `Content-Security-Policy` headers via middleware.

---

## Phase 8: Dependency & Supply Chain

### CRITICAL-003: PhpSpreadsheet SSRF/RCE (CVE-2026-34084)
**Severity**: Critical | **CVSS**: 9.1

`phpoffice/phpspreadsheet` version is vulnerable to **SSRF/RCE in IOFactory::load when `$filename` is user controlled**.

**Impact**: If user-uploaded files are passed to `IOFactory::load()`, an attacker can achieve SSRF or RCE.

**Evidence**: `composer audit` → CVE-2026-34084

**Remediation**: `composer update phpoffice/phpspreadsheet` immediately.

---

### HIGH-006: `google/protobuf` DoS (CVE-2026-6409)
**Severity**: High | **CVSS**: 7.5

Denial of Service via malicious protobuf messages.

**Remediation**: `composer update google/protobuf`

---

### HIGH-007: Axios Multiple Vulnerabilities
**Severity**: High | **CVSS**: 7.2

`axios` has 15+ vulnerabilities including:
- SSRF via `NO_PROXY` bypass
- Prototype pollution leading to credential theft
- Header injection

**Remediation**: `npm audit fix` or `npm update axios`

---

### HIGH-008: Vite Path Traversal & Arbitrary File Read
**Severity**: High | **CVSS**: 7.1

`vite` ≤6.4.1 is vulnerable to path traversal in optimized deps `.map` handling and arbitrary file read via dev server WebSocket.

**Remediation**: `npm update vite`

---

### MEDIUM-011: `league/commonmark` HTML Bypass
**Severity**: Medium | **CVSS**: 5.5

Two CVEs allow HTML tag bypasses in commonmark.

**Remediation**: `composer update league/commonmark`

---

### MEDIUM-012: Symfony Routing Regex Bypass
**Severity**: Medium | **CVSS**: 5.3

`symfony/routing` has route-requirement bypass via unanchored regex alternation.

**Remediation**: `composer update symfony/routing`

---

### LOW-005: Symfony YAML Parser DoS
**Severity**: Low | **CVSS**: 3.5

Multiple YAML parser vulnerabilities (Billion Laughs, ReDoS, stack exhaustion).

**Remediation**: `composer update symfony/yaml`

---

## Appendix A: Positive Security Findings

1. ✅ Integration OAuth tokens encrypted at rest (`encrypted:array`)
2. ✅ Invitation tokens use 64-char random strings with collision checking
3. ✅ Dockerfile runs as non-root (`www` user)
4. ✅ Secure cookie settings (`Secure`, `HttpOnly`, `SameSite=Lax`)
5. ✅ No SQL injection in `DB::raw()` usages
6. ✅ Parameterized queries throughout
7. ✅ Proper password hashing with `Hash::make()` and `Hash::check()`
8. ✅ API login rate-limited (`throttle:5,1`)
9. ✅ General API rate-limited (`throttle:60,1`)
10. ✅ Role-based access control implemented with `TenantRole`/`Permission`
11. ✅ Tenant isolation via global scopes
