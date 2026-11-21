# Tenant Scope Audit Report — RB Benchmarks

**Date**: 2026-06-02  
**Scope**: All `withoutGlobalScopes()` / `withoutGlobalScope()` usages in `app/`  
**Auditor**: Automated audit + manual review  

---

## Executive Summary

| Metric | Value |
|--------|-------|
| Total usages reviewed | 24 |
| **Critical/High risk fixed** | **2** |
| Safe usages refactored | 20 |
| Remaining `withoutGlobalScopes()` | **0** |

**All blanket `withoutGlobalScopes()` calls have been replaced with granular `withoutGlobalScope(TenantScope::class)`.**

---

## 🔴 Critical / High Risk Findings (Fixed)

### 1. IDOR in `AdAccounts` Livewire Page
**File**: `app/Livewire/Pages/AdAccounts.php`  
**Lines**: 176, 187, 195, 203

**Issue**: Four methods (`updateAccountIndustry`, `updateAccountCountry`, `updateAccountCategory`, `updateAccountClient`) used `AdAccount::withoutGlobalScopes()->find($accountId)` with **no tenant or ownership check**. A malicious user could tamper with the `$accountId` Livewire payload to modify any account in the database, including accounts belonging to other tenants.

**Fix**: Removed `withoutGlobalScopes()` entirely. `TenantScope` already exempts super admins automatically; regular users are now correctly restricted to their own tenant's accounts.

```php
// BEFORE (vulnerable)
$account = AdAccount::withoutGlobalScopes()->find($accountId);

// AFTER (safe)
$account = AdAccount::find($accountId);
```

---

### 2. Data Leakage in `BenchmarkService::getAccountBenchmark()`
**File**: `app/Services/BenchmarkService.php`  
**Lines**: 782–784

**Issue**: The method queried a single account by ID with tenant scope removed, then returned full account metrics, spend, CTR, CPC, and performance scores. No ownership validation existed. A tenant-authenticated user could pass any `accountId` and read another tenant's data.

**Fix**: Added explicit tenant ownership check after `findOrFail`:

```php
$isSuperAdmin = app()->bound('is_super_admin') && app('is_super_admin');
$currentTenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : null;
if (!$isSuperAdmin && $account->tenant_id !== $currentTenantId) {
    abort(403, 'Unauthorized');
}
```

---

## 🟢 Safe Usages Refactored (20 occurrences)

The following usages were **functionally safe** (constrained by `integration_id` + `external_account_id`, with `tenant_id` explicitly handled via `syncTenantId()`), but all used the blanket `withoutGlobalScopes()` which also strips `SoftDeletes`. They have been refactored to `withoutGlobalScope(TenantScope::class)`:

| # | File | Line(s) | Purpose |
|---|------|---------|---------|
| 1 | `app/Console/Commands/SyncFacebookCampaigns.php` | 272 | Existence check before creating discovered account |
| 2 | `app/Console/Commands/SyncSnapchatCampaigns.php` | 136 | Existence check before creating discovered account |
| 3 | `app/Console/Commands/SyncTikTokCampaigns.php` | 180 | Existence check before creating discovered account |
| 4 | `app/Http/Controllers/Api/FacebookIntegrationController.php` | 197, 202 | OAuth callback account upsert |
| 5 | `app/Http/Controllers/Api/SnapchatIntegrationController.php` | 418, 423 | OAuth callback + sync account upsert |
| 6 | `app/Http/Controllers/Api/TikTokIntegrationController.php` | 413, 418 | OAuth callback + sync account upsert |
| 7 | `app/Http/Controllers/SnapchatOAuthController.php` | 263 | Legacy OAuth callback account upsert |
| 8 | `app/Models/AdAccount.php` | 106 | `syncTenantId()` helper — cross-tenant lookup to preserve existing tenant |
| 9 | `app/Services/LinkedInAdsService.php` | 279, 303 | Sync account upsert |
| 10 | `app/Services/TwitterAdsService.php` | 170, 195 | Sync account upsert |

**Why safe**: Each query is scoped by `integration_id` (which itself belongs to a single tenant) and `external_account_id`. The `tenant_id` is either read-only or set via `syncTenantId()` which preserves existing assignments.

**Why refactor**: `withoutGlobalScopes()` also removes `SoftDeletingScope`, meaning soft-deleted records could unexpectedly be matched or block re-creation. The granular call is semantically correct.

---

## 🟡 Aggregate Analytics (By Design)

The following usages in `BenchmarkService` are **intentionally cross-tenant** for anonymous industry benchmark calculations. They already used `withoutGlobalScope(TenantScope::class)`:

| Method | Lines | Risk | Justification |
|--------|-------|------|---------------|
| `loadAllAccountsWithMetrics` | 373, 382, 385, 392 | Low | Results are aggregated into anonymized industry benchmarks; no individual account data is returned to end users. |
| `calculateDynamicIndustryBenchmarks` | 419 | Low | Query is scoped to `whereIn('id', array_keys($accountAggregates))` — only touches pre-filtered IDs. |
| `getAvailableIndustries` | 1068, 1072 | Low | Returns only anonymized industry name strings. |

---

## Verification Commands

```bash
# Confirm zero blanket withoutGlobalScopes() remain
grep -rn "withoutGlobalScopes()" app/ --include="*.php"

# Confirm all tenant scope bypasses are granular
grep -rn "withoutGlobalScope" app/ --include="*.php" | grep -v "TenantScope::class"
# Expected: 0 results

# Run tests to ensure no regressions
php artisan test
```

---

## Remaining Recommendations

1. **Add automated tests** for tenant isolation:
   - Tenant A user requests Tenant B's account benchmark → expects 403
   - Tenant A user updates Tenant B's account industry → expects 404 (or 403)

2. **Audit `withoutGlobalScope(TenantScope::class)` usage quarterly** — any new addition should require code-review sign-off.

3. **Consider soft-delete audit** — verify no soft-deleted accounts were unintentionally resurrected by the prior `withoutGlobalScopes()` usage.
