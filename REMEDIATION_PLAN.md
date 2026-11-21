# Remediation Plan — RB Benchmarks Security Audit

**Priority**: P0 (Critical) → P1 (High) → P2 (Medium) → P3 (Low)
**Estimated Effort**: 16–24 engineering hours

---

## P0 — Critical (Do Today)

### P0-1: Rotate All Secrets and Harden On-Disk Exposure
**Finding**: CRITICAL-001, CRITICAL-002, HIGH-001, HIGH-002
**Effort**: 4 hours
**Owner**: DevOps + Backend
**Status**: 🔄 Git history verified clean; on-disk permissions hardened; secret rotation in progress.

**Investigation Result**:
- `.env` and `storage/app/google/*.json` have **never** been committed to git history (`git rev-list --all` returns 0 commits for these files).
- Both are properly blocked by `.gitignore`.
- However, `.env` was world-readable (`755`, owned by `root`) and Google JSON keys were group-writable (`664`).

**Steps Completed**:
1. ✅ Fixed file permissions:
   ```bash
   chmod 600 .env
   chown redbananas-rb-benchmarks:redbananas-rb-benchmarks .env
   chmod 600 storage/app/google/*.json
   ```
2. ✅ Verified `.gitignore`:
   ```bash
   git check-ignore -v .env            # .gitignore:9:.env
   git check-ignore -v storage/app/google/*.json   # storage/app/.gitignore:1:*
   ```
3. ✅ Verified git history is clean across all branches (0 commits contain `.env` or `*.json` secrets).

**Steps Remaining — Secret Rotation**:
Follow [SECRET_ROTATION_GUIDE.md](SECRET_ROTATION_GUIDE.md) for step-by-step rotation of all 12 credentials.

**Immediate priority order**:
1. Revoke Facebook Default Access Token (full ad account access)
2. Revoke Google Service Account keys
3. Change MySQL database password
4. Rotate remaining OAuth secrets and API keys
5. Rotate `APP_KEY` and re-encrypt `Integration.app_config`

**Verification**: Clone fresh repo, confirm `.env` and JSON key files are absent from history; verify file permissions are `600`.

---

### P0-2: Patch PhpSpreadsheet Critical Vulnerability
**Finding**: CRITICAL-003 (CVE-2026-34084)
**Effort**: 15 minutes
**Owner**: Backend

**Steps**:
```bash
composer update phpoffice/phpspreadsheet
composer audit
```

**Verification**: `composer audit` shows 0 critical/high vulnerabilities for phpspreadsheet.

---

### P0-3: Remove or Protect `/api/debug/request`
**Finding**: HIGH-004
**Effort**: 15 minutes
**Owner**: Backend

**Steps**:
Option A — Remove entirely:
```php
// Delete lines 501–512 from routes/api.php
```

Option B — Gate behind super admin + local env:
```php
Route::get('/debug/request', function (Request $request) {
    if (!app()->isLocal() || !auth()->user()?->isSuperAdmin()) {
        abort(404);
    }
    // ... existing logic
});
```

**Verification**: `curl -I https://rb-benchmarks.redbananas.com/api/debug/request` returns 404 or 401.

---

## P1 — High (This Week)

### P1-1: Create and Register DashboardPolicy
**Finding**: HIGH-003
**Effort**: 1 hour
**Owner**: Backend

**Steps**:
1. Create `app/Policies/DashboardPolicy.php`:
   ```php
   class DashboardPolicy
   {
       public function view(User $user, Dashboard $dashboard): bool
       {
           return $dashboard->user_id === $user->id || $user->isSuperAdmin();
       }

       public function update(User $user, Dashboard $dashboard): bool
       {
           return $dashboard->user_id === $user->id || $user->isSuperAdmin();
       }

       public function delete(User $user, Dashboard $dashboard): bool
       {
           return $dashboard->user_id === $user->id || $user->isSuperAdmin();
       }
   }
   ```
2. Register in `AppServiceProvider` or create `AuthServiceProvider`:
   ```php
   Gate::policy(Dashboard::class, DashboardPolicy::class);
   ```

**Verification**: Attempt to access another user's dashboard via API → expect 403.

---

### P1-2: Enable Session Encryption
**Finding**: HIGH-005
**Effort**: 15 minutes
**Owner**: DevOps

**Steps**:
1. Update `.env`:
   ```env
   SESSION_ENCRYPT=true
   ```
2. Clear existing sessions:
   ```bash
   php artisan session:gc
   ```

**Verification**: Inspect `sessions` table — `payload` should be encrypted binary data, not JSON.

---

### P1-3: Patch Dependency Vulnerabilities
**Finding**: HIGH-006, HIGH-007, HIGH-008, MEDIUM-011, MEDIUM-012
**Effort**: 1 hour
**Owner**: Backend + Frontend

**Steps**:
```bash
# PHP
composer update google/protobuf league/commonmark symfony/routing symfony/yaml
composer audit

# Node
npm audit fix
npm audit
```

**Verification**: Both `composer audit` and `npm audit` return 0 critical/high findings.

---

### P1-4: Fix Open Redirects
**Finding**: MEDIUM-002
**Effort**: 30 minutes
**Owner**: Backend

**Steps**:
Replace `request()->header('Referer')` with validated internal redirect:
```php
// LanguageSwitcher.php & TenantSelector.php
$fallback = '/ad-accounts';
$referer = request()->header('Referer');

// Only redirect to same-origin URLs
if ($referer && str_starts_with($referer, config('app.url'))) {
    return $this->redirect($referer, navigate: false);
}

return $this->redirect($fallback, navigate: false);
```

**Verification**: Attempt redirect to `https://evil.com` via Referer → lands on fallback page.

---

## P2 — Medium (Next Sprint)

### P2-1: Audit All `withoutGlobalScopes()` Usage
**Finding**: MEDIUM-003
**Effort**: 2 hours
**Owner**: Backend

**Steps**:
1. List all usages:
   ```bash
   grep -rn "withoutGlobalScopes\|withoutGlobalScope" app/ > /tmp/scope_audit.txt
   ```
2. For each usage, verify:
   - Is it inside an authenticated controller/service?
   - Is there an explicit tenant/ownership check before/after?
   - Can the query result leak to a different tenant?
3. Replace `withoutGlobalScopes()` with `withoutGlobalScope(TenantScope::class)` where possible (more granular)
4. Add comments justifying each bypass

**Verification**: Code review sign-off on all 25+ usages.

---

### P2-2: Harden OAuth State Parameters
**Finding**: MEDIUM-008
**Effort**: 2 hours
**Owner**: Backend

**Steps**:
1. Create a signed state helper:
   ```php
   class OAuthStateHelper
   {
       public static function generate(array $data): string
       {
           $json = json_encode($data);
           $signature = hash_hmac('sha256', $json, config('app.key'));
           return base64_encode($signature . '|' . $json);
       }

       public static function validate(string $state): ?array
       {
           $decoded = base64_decode(str_replace(' ', '+', $state));
           [$sig, $json] = explode('|', $decoded, 2);
           $expected = hash_hmac('sha256', $json, config('app.key'));
           if (!hash_equals($expected, $sig)) return null;
           return json_decode($json, true);
       }
   }
   ```
2. Apply to all OAuth controllers (Facebook, Snapchat, TikTok, LinkedIn, Twitter, Google Ads)

**Verification**: Tamper with state parameter in OAuth URL → callback fails with "Invalid state".

---

### P2-3: Sanitize Error Logging
**Finding**: MEDIUM-005
**Effort**: 1 hour
**Owner**: Backend

**Steps**:
1. Create a log sanitizer helper:
   ```php
   class LogSanitizer
   {
       public static function exception(\Exception $e, int $traceLevel = 'debug'): array
       {
           $data = ['message' => $e->getMessage()];
           if ($traceLevel === 'debug') {
               $data['trace'] = $e->getTraceAsString();
           }
           return $data;
       }
   }
   ```
2. Replace all `Log::error('...', ['trace' => $e->getTraceAsString()])` with sanitized versions
3. Never log `access_token`, `client_secret`, or `password` values

**Verification**: Search codebase for `getTraceAsString` in error-level logs → 0 results.

---

### P2-4: Validate OAuth Redirect URIs
**Finding**: MEDIUM-007
**Effort**: 1 hour
**Owner**: Backend

**Steps**:
1. Store expected `redirect_uri` in the OAuth state:
   ```php
   $stateData = [
       'redirect_uri' => config('services.facebook.redirect_uri'),
       // ... other fields
   ];
   ```
2. During callback, validate:
   ```php
   if ($stateData['redirect_uri'] !== config('services.facebook.redirect_uri')) {
       abort(400, 'Invalid redirect URI');
   }
   ```

**Verification**: Modify redirect_uri in OAuth callback → request rejected.

---

### P2-5: Fix Mass Assignment Risk in Conversion Tracking
**Finding**: MEDIUM-006
**Effort**: 30 minutes
**Owner**: Backend

**Steps**:
In `CampaignIntegrationController.php:334`:
```php
// BEFORE (risky)
$conversionData = $request->all();

// AFTER (safe)
$conversionData = $request->only([
    'conversion_value',
    'currency',
    'event_name',
    'event_time',
    'user_data',
]);
```

**Verification**: Send extra fields in conversion request → they are ignored.

---

### P2-6: Add Content Security Policy
**Finding**: LOW-004
**Effort**: 1 hour
**Owner**: Backend

**Steps**:
Create middleware `CspMiddleware`:
```php
$response->headers->set('Content-Security-Policy', 
    "default-src 'self'; " .
    "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
    "style-src 'self' 'unsafe-inline'; " .
    "img-src 'self' data: https://*.facebook.com https://*.google.com; " .
    "connect-src 'self' https://*.facebook.com https://*.google.com;"
);
```

**Verification**: Check response headers in browser DevTools.

---

## P3 — Low (Backlog)

### P3-1: Sanitize Excel Export Data Against Formula Injection
**Finding**: LOW-002
**Effort**: 1 hour
**Owner**: Backend

**Steps**:
Create a spreadsheet sanitizer:
```php
public static function sanitizeCell(string $value): string
{
    if (in_array($value[0] ?? '', ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $value;
    }
    return $value;
}
```
Apply to all `setCellValue()` calls with user-controlled data.

---

### P3-2: Review `{!! !!}` Blade Usage
**Finding**: MEDIUM-010
**Effort**: 30 minutes
**Owner**: Backend

**Steps**:
1. Verify `platformSvgs` is a hardcoded array in Livewire components
2. Verify `sparklines` only contains SVG path data (no user input)
3. If safe, add `/** @var array<string, string> Hardcoded SVG map */` docblocks for audit trail

---

### P3-3: Improve `/sync-progress` Security
**Finding**: MEDIUM-009
**Effort**: 1 hour
**Owner**: Backend

**Steps**:
Option A: Move sync progress to Redis/Cache instead of files
Option B: Use signed URLs:
```php
URL::signedRoute('sync-progress', ['logFile' => $logFile]);
```

---

## Timeline

| Week | Tasks |
|------|-------|
| **Day 1** | P0-1 (Rotate secrets, purge git history), P0-2 (Patch PhpSpreadsheet), P0-3 (Remove debug route) |
| **Day 2–3** | P1-1 (DashboardPolicy), P1-2 (Session encrypt), P1-3 (Dependency patches), P1-4 (Open redirects) |
| **Week 2** | P2-1 (Scope audit), P2-2 (OAuth state), P2-3 (Log sanitization), P2-4 (Redirect URI validation) |
| **Week 3** | P2-5 (Mass assignment), P2-6 (CSP), P3-1 (Excel sanitization), P3-2 (Blade review) |

---

## Verification Checklist

| # | Item | Status |
|---|------|--------|
| 1 | `.env` not in git history | ✅ Verified — 0 commits in `git rev-list --all` |
| 2 | `storage/app/google/*.json` not in git history | ✅ Verified — 0 commits in `git rev-list --all` |
| 3 | `.env` file permissions are `600` | ✅ Fixed |
| 4 | `storage/app/google/*.json` permissions are `600` | ✅ Fixed |
| 5 | `composer audit` shows 0 critical/high | ✅ 0 advisories |
| 6 | `npm audit` shows 0 critical/high | ✅ 0 vulnerabilities |
| 7 | `/api/debug/request` returns 404 | ✅ Route removed |
| 8 | Session data is encrypted in database | 🔄 Set `SESSION_ENCRYPT=true` in production `.env` |
| 9 | Dashboard access control returns 403 for unauthorized users | ✅ `DashboardPolicy` created |
| 10 | OAuth state tampering fails | ✅ `OAuthStateHelper` with HMAC-SHA256 |
| 11 | Referer redirect to external domain falls back to safe URL | ✅ `LanguageSwitcher` & `TenantSelector` validated |
| 12 | All error logs exclude traces at `error` level | ✅ `SecureLogger` implemented |
| 13 | All 12 secrets rotated | 🔄 See [SECRET_ROTATION_GUIDE.md](SECRET_ROTATION_GUIDE.md) |
| 14 | Performance indexes migration applied | ✅ `2026_06_02_000000_add_performance_indexes` migrated |
