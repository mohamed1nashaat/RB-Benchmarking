# Architecture Review — RB Benchmarks

**Date**: 2026-06-02
**Scope**: Code quality, performance, design patterns, maintainability

---

## Overall Assessment

RB Benchmarks follows Laravel conventions reasonably well but has several areas for architectural improvement. The codebase is moderately complex (~62k files, heavy use of console commands and service classes) and would benefit from clearer separation of concerns in sync/integration logic.

**Grade**: B+ (Good foundations, some technical debt in sync layer)

---

## 1. Architecture Patterns

### 1.1 Multi-Tenancy Implementation
**Rating**: Good

The custom tenant isolation using `TenantScope` + `BelongsToTenant` trait is a clean, lightweight approach. It avoids the complexity of full tenancy packages while providing row-level security.

**Strengths**:
- Global scope automatically filters all queries
- `BelongsToTenant::bootBelongsToTenant()` auto-assigns `tenant_id` on create
- Super admin bypass is cleanly implemented

**Concerns**:
- `withoutGlobalScopes()` is used 25+ times, creating risk of accidental data leakage
- `WebTenantMiddleware` auto-assigns tenants via session, which can be confusing for API clients
- No formal tenancy package means edge cases (tenant switching, impersonation) must be hand-rolled

**Recommendation**: Consider adding a `Tenancy` service class that centralizes all tenant context operations, reducing direct `app()->instance()` calls.

---

### 1.2 Role-Based Access Control
**Rating**: Good

The custom `TenantRole` → `Permission` system with pivot tables is well-designed. It supports granular permissions while maintaining backward compatibility with legacy `role` strings.

**Strengths**:
- `User::hasPermissionForTenant()` cleanly handles both legacy and new permission models
- Super admin bypass is explicit and consistent
- `CheckPermission` middleware supports OR-logic for multiple permissions

**Concerns**:
- Only `TenantUserPolicy` exists — `DashboardPolicy` is missing despite `$this->authorize()` calls
- No caching layer for permission lookups (N+1 risk on permission checks)

**Recommendation**: Add `DashboardPolicy`, cache permission lookups per-request, and consider Laravel's built-in `Gate` policies for new resources.

---

### 1.3 Service Layer Design
**Rating**: Moderate

The `app/Services/` directory contains 40+ service classes. While this shows good separation of concerns, there is some inconsistency in patterns.

**Strengths**:
- Each platform has dedicated sync services (`FacebookMetricsSyncService`, `GoogleAdsMetricsSyncService`, etc.)
- Calculator pattern for objective-based KPIs is clean and extensible
- `DashboardExportService` handles complex PDF/Excel generation

**Concerns**:
- Some services are very large (600+ lines) and mix API client logic with business logic
- `FacebookIntegrationController` is 556 lines — should be split into controller + service
- Direct `Http::get/post` calls scattered across services make testing difficult
- No API client abstraction layer (e.g., `FacebookApiClient` class)

**Recommendation**: Extract API clients into dedicated classes:
```
app/Services/ApiClients/
  FacebookApiClient.php
  GoogleAdsApiClient.php
  TikTokApiClient.php
```

---

## 2. Code Quality

### 2.1 Consistency
**Rating**: Good

- PSR-12 formatting is generally followed
- Type hints are used consistently in new code
- `declare(strict_types=1)` is not used but not required for Laravel

### 2.2 Documentation
**Rating**: Moderate

- PHPDoc blocks are present on most public methods
- Complex logic (e.g., `AdAccount::syncTenantId()`) has good inline comments
- Console commands lack usage examples in docblocks

### 2.3 Error Handling
**Rating**: Moderate

**Strengths**:
- Try/catch blocks around all external API calls
- Structured logging with context

**Concerns**:
- Generic error messages (`'An unexpected error occurred'`) returned to clients make debugging hard
- Some exceptions swallow the original message and return a generic string
- `getTraceAsString()` logged at `error` level in production

**Recommendation**: Implement a structured error response format:
```json
{
  "error": "facebook_sync_failed",
  "message": "Failed to sync Facebook campaigns",
  "request_id": "uuid-for-tracing"
}
```

---

## 3. Performance

### 3.1 Database Queries
**Rating**: Moderate

**Strengths**:
- Eager loading used in most index endpoints (`with()`, `withCount()`)
- `DB::raw()` aggregations for metrics are efficient

**Concerns**:
- `AdAccountExportController` loads all accounts + metrics into memory for Excel generation
- `BenchmarkService` has multiple `withoutGlobalScope()` calls that bypass query optimization
- No database indexing strategy is documented
- `AdMetric` table likely grows very large (daily metrics per campaign per platform) — no partitioning observed

**Recommendations**:
1. Add composite indexes:
   ```sql
   CREATE INDEX idx_ad_metrics_lookup ON ad_metrics(ad_account_id, ad_campaign_id, date, platform);
   CREATE INDEX idx_ad_campaigns_lookup ON ad_campaigns(ad_account_id, external_campaign_id);
   ```
2. Implement table partitioning for `ad_metrics` by `date` or `platform`
3. Use Laravel's cursor/pagination for large exports instead of `->get()`
4. Cache benchmark calculations that don't change frequently

### 3.2 External API Calls
**Rating**: Moderate

- No HTTP client caching layer observed
- Facebook batch API is used for thumbnails (good)
- No circuit breaker pattern for external APIs

**Recommendation**: Add HTTP caching (Redis) for read-heavy API calls and implement circuit breakers for sync jobs.

### 3.3 Queue Usage
**Rating**: Good

- `RunPlatformSync` job exists for background sync
- `database` queue driver is configured

**Concerns**:
- No queue monitoring or dead letter queue observed
- Long-running syncs could block queue workers

---

## 4. Frontend Architecture

### 4.1 Technology Stack
**Rating**: Good

- Vue 3 + TypeScript + Pinia + TailwindCSS is a modern, maintainable stack
- Chart.js for data visualization
- Livewire for server-driven UI components

### 4.2 Concerns
**Rating**: Moderate

- Heavy reliance on Livewire means much UI logic is server-side PHP
- Vue components appear minimal — most UI is Blade + Livewire
- `app.js` is 16KB — likely bundles all JS

**Recommendation**: Consider extracting more interactive components (filters, charts) into dedicated Vue components to reduce server round-trips.

---

## 5. Testing

### 5.1 Test Coverage
**Rating**: Low

- Only `tests/Feature/` and `tests/Unit/` directories exist
- `.phpunit.result.cache` suggests tests are run, but no coverage reports observed
- No Playwright e2e tests in the `tests/e2e/` directory (despite `playwright.config.ts` existing)

**Recommendation**:
1. Add unit tests for `TenantScope`, `CheckPermission`, and auth middleware
2. Add feature tests for all API endpoints (especially OAuth callbacks)
3. Add tests for `withoutGlobalScopes()` usages to ensure they don't leak data
4. Implement CI-based coverage reporting

---

## 6. DevOps & Deployment

### 6.1 Docker
**Rating**: Good

**Strengths**:
- Non-root user (`www`)
- Multi-stage build not needed but acceptable for this size
- PHP 8.2 with required extensions

**Concerns**:
- `COPY . /var/www` copies everything including `.env` and potential secrets into image layers
- No `.dockerignore` observed
- Node.js and npm installed in PHP-FPM image (unusual — usually separate build stage)

**Recommendation**: Add `.dockerignore`:
```
.env
.env.*
.git
storage/app/google/*.json
node_modules
vendor
```

### 6.2 CI/CD
**Rating**: Good

- GitHub Actions workflow with MySQL and Redis services
- PHP 8.2 with xdebug for coverage

**Concerns**:
- No security scanning step in CI (no `composer audit`, no secret scanning)
- No static analysis (PHPStan/Psalm)

**Recommendation**: Add to CI workflow:
```yaml
- name: Security Audit
  run: composer audit

- name: Static Analysis
  run: vendor/bin/phpstan analyse --memory-limit=1G
```

---

## 7. Data Model Observations

### 7.1 Schema Design
**Rating**: Good

**Strengths**:
- Normalized schema with proper foreign keys
- Soft deletes on key entities (`Tenant`, `AdAccount`, `Integration`)
- JSON columns used appropriately for flexible config (`app_config`, `account_config`)

**Concerns**:
- `ad_metrics` table will grow extremely large — consider archiving or partitioning
- `industry_benchmarks` may need versioning if benchmarks change over time
- No unique constraints observed on `external_account_id` + `platform` combinations

### 7.2 Migrations
**Rating**: Good

- Migrations are well-organized and timestamped
- Enum modifications handled via separate migrations
- No raw SQL migrations observed

---

## 8. Security Architecture

### 8.1 Authentication
**Rating**: Good

- Laravel Sanctum for API tokens
- Proper password hashing (`Hash::make`/`Hash::check`)
- Rate limiting on login endpoint

### 8.2 Authorization
**Rating**: Moderate

- Tenant isolation is solid when scopes are active
- Missing policy registration for Dashboards
- `withoutGlobalScopes()` creates escape hatches that must be carefully audited

### 8.3 Data Protection
**Rating**: Good

- OAuth tokens encrypted at rest (`encrypted:array`)
- HTTPS enforced via secure cookies
- Session encryption configurable (currently off)

---

## Summary of Recommendations (Priority Order)

| Priority | Recommendation | Effort |
|----------|---------------|--------|
| 1 | Extract API clients from sync services | 4h |
| 2 | Add database indexes for `ad_metrics` | 1h |
| 3 | Add `DashboardPolicy` and register it | 1h |
| 4 | Implement HTTP client caching for external APIs | 3h |
| 5 | Add `.dockerignore` | 15m |
| 6 | Add `composer audit` and PHPStan to CI | 1h |
| 7 | Write tests for `TenantScope` and auth middleware | 4h |
| 8 | Add request ID tracing for error responses | 2h |
| 9 | Consider partitioning `ad_metrics` by date | 4h |
| 10 | Add circuit breakers for external API syncs | 4h |
