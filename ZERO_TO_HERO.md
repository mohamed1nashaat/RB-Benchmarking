# RB Benchmarks — Zero-to-Hero Guide

A comprehensive, single-file reference for the **RB Benchmarks** platform: what it does, how it is built, how each feature works, and where the important code lives.

> **Last updated:** 2026-08-05  
> **Repository branch/context:** production (`rb-benchmarks.redbananas.com`)

---

## Table of Contents

1. [What is RB Benchmarks?](#1-what-is-rb-benchmarks)
2. [Tech Stack](#2-tech-stack)
3. [Project Structure](#3-project-structure)
4. [Architecture Patterns](#4-architecture-patterns)
5. [Multi-Tenancy & Authorization](#5-multi-tenancy--authorization)
6. [User Flows](#6-user-flows)
7. [Core Features](#7-core-features)
8. [Data Model](#8-data-model)
9. [Integrations & Sync Architecture](#9-integrations--sync-architecture)
10. [Frontend](#10-frontend)
11. [Console Commands & Scheduled Jobs](#11-console-commands--scheduled-jobs)
12. [Testing & CI/CD](#12-testing--cicd)
13. [Deployment & Operations](#13-deployment--operations)
14. [Security & Audit Summary](#14-security--audit-summary)
15. [Known Issues & Technical Debt](#15-known-issues--technical-debt)
16. [Quick Reference File Map](#16-quick-reference-file-map)

---

## 1. What is RB Benchmarks?

RB Benchmarks is a **multi-tenant ad-performance benchmarking platform** built for digital marketing agencies. It aggregates campaign metrics from the major advertising platforms, compares them against industry benchmarks, and produces client dashboards, reports, and alerts.

### Primary users

- **Agency account managers / media buyers** – monitor all clients, approve spend, run reports.
- **Client stakeholders** – view their own dashboards and performance.
- **Super admins** – manage tenants, users, roles, industry categories, and platform integrations.

### Core value proposition

- Unify data from **Facebook/Meta, Google Ads, TikTok, LinkedIn, Snapchat, and X/Twitter**.
- Benchmark KPIs (CPM, CPC, CTR, CPL, CPA, ROAS, CVR) against industry datasets.
- Generate **PDF/Excel client dashboards** and scheduled reports.
- Provide **real-time(ish) alerting** on anomalies, budget thresholds, and sync health.

---

## 2. Tech Stack

| Layer | Technology | Notes |
|-------|-----------|-------|
| Framework | Laravel 11.x (`^11.31`) | PHP 8.2+ |
| PHP | `^8.2` (platform pinned to `8.2.29`) | |
| Auth | Laravel Sanctum (`^4.2`) | API tokens + session auth |
| Database | MySQL 8 | Configured in `.env` |
| Queue | Database driver | `QUEUE_CONNECTION=database` |
| Cache | Database driver (Redis config present) | |
| Sessions | Database driver | Encrypted in production |
| PDF | `barryvdh/laravel-dompdf` `^3.1` | |
| Spreadsheets | `phpoffice/phpspreadsheet` `~2.4.5` | |
| Google APIs | `google/apiclient` `^2.18`, `googleads/google-ads-php` `^33.4` | |
| Icons | `blade-ui-kit/blade-heroicons` `^2.6` | |
| Server UI | Livewire `^3.0` | Full-page components |
| JS runtime / charts | Chart.js `^4.5.1`, jQuery `^4.0.0`, Select2 `^4.1.0-rc.0` | |
| Build tool | Vite `^6.0.11` | `laravel-vite-plugin` |
| CSS | TailwindCSS `^3.4.13` | |
| i18n | Laravel JSON translations | `resources/lang/en.json`, `ar.json` |
| E2E testing | Playwright `^1.56.1` | |
| Unit/Feature | PHPUnit `^11.0.1`, Laravel Pint, PHPStan | |

> **Note:** the `README.md` still mentions Vue 3 + TypeScript + Pinia, but the real frontend is **Blade + Livewire + Alpine.js + jQuery/Select2**.

---

## 3. Project Structure

```
app/
├── Console/Commands/       # 60+ artisan sync/maintenance commands
├── Events/                 # CampaignCreated
├── Helpers/                # OAuthStateHelper, SecureLogger, SpreadsheetSanitizer
├── Http/
│   ├── Controllers/        # Web + API controllers
│   └── Middleware/         # Tenant, permission, CSP, locale, request-id
├── Jobs/                   # RunPlatformSync
├── Listeners/              # AutoCreateGoogleSheet
├── Livewire/
│   ├── Auth/               # Login, Register, AcceptInvitation
│   ├── Components/         # UserMenu, TenantSelector, LanguageSwitcher, ExportMenu
│   └── Pages/              # Benchmarks, AdAccounts, ClientDashboard, etc.
├── Models/                 # Eloquent + custom scopes
├── Notifications/          # TenantInvitation, PerformanceAlert
├── Policies/               # DashboardPolicy, TenantUserPolicy
├── Providers/              # AppServiceProvider, GoogleSheetsServiceProvider
├── Services/               # 40+ service classes (sync, calculations, exports, SEO)
└── Traits/                 # BelongsToTenant

bootstrap/
├── app.php                 # Middleware aliases, exception handling
└── providers.php           # Service providers

config/                     # Laravel + package configs (google.php, dompdf.php, etc.)
database/
├── factories/
├── migrations/
└── seeders/                # PermissionSeeder, TenantRoleSeeder, IndustrySeeder, etc.

resources/
├── css/app.css             # Tailwind entry
├── js/app.js               # Vite entry (Chart.js, Select2 wrappers)
├── lang/en.json|ar.json    # Translations
└── views/                  # Blade layouts + Livewire views

routes/
├── web.php                 # Livewire pages, OAuth redirect helpers
├── api.php                 # REST API + OAuth callbacks
└── console.php             # Scheduled commands

tests/
├── Feature/                # PHPUnit feature tests
├── Unit/                   # PHPUnit unit tests
└── e2e/                    # Playwright specs
```

---

## 4. Architecture Patterns

### MVC + Service layer

- Controllers are thin; business logic lives in `app/Services/`.
- Platform sync, KPI calculation, PDF/Excel export, SEO auditing, and alert evaluation all have dedicated service classes.

### Livewire page components

Most authenticated pages are full-page Livewire components wired directly in `routes/web.php`:

```php
Route::get('/ad-accounts', Pages\AdAccounts::class)->name('ad-accounts');
```

This keeps UI state, validation, and actions in PHP while the browser only receives rendered HTML + Alpine.js sprinkles.

### Global tenancy scope

- `TenantScope` (`app/Models/Scopes/TenantScope.php`) is applied automatically via the `BelongsToTenant` trait.
- Every tenant-scoped query is filtered by the `current_tenant_id` bound in the container.
- Super admins and users with `view_all_tenants = true` bypass the scope.

### Custom RBAC

- `Permission`, `TenantRole`, and `TenantUser` models implement role-based access.
- Legacy fallback: `tenant_users.role` enum (`admin`/`viewer`).
- New model: `tenant_role_id` → `tenant_roles` → `role_permissions` → `permissions`.
- `CheckPermission` middleware supports OR-logic: `middleware('permission:view_users|invite_users')`.

---

## 5. Multi-Tenancy & Authorization

### Tenant isolation

| Piece | Path | Responsibility |
|-------|------|----------------|
| Global scope | `app/Models/Scopes/TenantScope.php` | Adds `where tenant_id = current_tenant_id` to every query; bypasses for super admins / global viewers |
| Trait | `app/Traits/BelongsToTenant.php` | Registers `TenantScope`; auto-assigns `tenant_id` on create |
| Web middleware | `app/Http/Middleware/WebTenantMiddleware.php` | Binds `is_super_admin`, `view_all_tenants`, and `current_tenant_id` for web requests; auto-assigns a tenant if session has none |
| API middleware | `app/Http/Middleware/TenantMiddleware.php` | Binds tenant context for API requests |

### Roles & permissions

- System roles are created automatically when a tenant is created (`Tenant::booted()` → `RolePermissionService::createSystemRolesForTenant()`):
  - `admin` – all permissions.
  - `viewer` – read-only permissions (`view_dashboard`, `view_reports`, `view_accounts`, `view_campaigns`, etc.).
- `User::hasPermissionForTenant()` checks the effective role for the current tenant.
- `AppServiceProvider` defines gates: `view_users`, `invite_users`, `manage_users`, `manage_roles`.
- `DashboardPolicy` handles ownership of custom dashboards.

### Public registration

- Route: `/register` → `app/Livewire/Auth/Register.php`
- Restricted to `@redbananas.com` emails.
- Creates a `User` with `is_super_admin = true` and `view_all_tenants = true`.
- Attaches the user to the first tenant with a `viewer` role and redirects to `/ad-accounts`.
- Existing `@redbananas.com` users are promoted to super admins via `database/migrations/2026_08_05_101500_promote_redbananas_users_to_super_admins.php`.

### Invitations

- Model: `app/Models/UserInvitation.php` – 64-char random token.
- Service: `app/Services/UserInvitationService.php`
- Component: `app/Livewire/Auth/AcceptInvitation.php`
- Route: `/invitation/{token}`
- Invitations also enforce the `@redbananas.com` domain.

---

## 6. User Flows

### Login

- `/login` → `app/Livewire/Auth/Login.php`
- Session-based auth.
- `WebTenantMiddleware` binds tenant context after login.

### Public sign-up

1. User fills `/register`.
2. Backend validates email domain.
3. Creates user with `view_all_tenants = true`.
4. Attaches to first tenant as viewer.
5. Sets `current_tenant_id` in session.
6. Redirects to `/ad-accounts`.

### Invitation accept

1. User clicks invitation link `/invitation/{token}`.
2. Backend validates token and email domain.
3. Creates or reuses user account.
4. Attaches user to the inviting tenant with the specified role.
5. Marks invitation accepted.

### Tenant context

- The session holds `current_tenant_id`.
- `WebTenantMiddleware` binds it to the container on every request.
- `TenantScope` uses it to filter data.
- Users with `view_all_tenants` bypass the filter and see cross-tenant data.

---

## 7. Core Features

### 7.1 Ad accounts management

- **Livewire:** `app/Livewire/Pages/AdAccounts.php`
- **View:** `resources/views/livewire/pages/ad-accounts.blade.php`
- **Route:** `GET /ad-accounts`
- **Export:** `GET /ad-accounts/export/excel` → `AdAccountExportController::excel()`

Capabilities:

- Search, filter by platform/status/industry/country/verification/client/date.
- Sorting and pagination.
- Bulk edit of industry/country/category/sub-category/client/status.
- Inline editing of account attributes.
- Data verification workflow (`pending` / `approved`).
- Quick create client (admin-only).
- Google Ads permission-denied banner + reset-to-active (admin-only).

### 7.2 Integrations / OAuth

All OAuth redirect helpers live in `routes/web.php`; callbacks are in `routes/api.php`.

| Platform | Initiation | Callback | Key Service |
|----------|------------|----------|-------------|
| Facebook | `Api\FacebookIntegrationController::initiateOAuth` | `Api\FacebookIntegrationController::handleCallback` | `FacebookMetricsSyncService` |
| Google Ads | `Api\GoogleAdsIntegrationController::getAuthUrl` | `Api\GoogleAdsIntegrationController::callback` | `GoogleAdsService`, `GoogleAdsCampaignSyncService`, `GoogleAdsMetricsSyncService` |
| Google Sheets | `Api\GoogleAuthController::getAuthUrl` | `Api\GoogleAuthController::handleCallback` | `GoogleSheetsService` |
| TikTok | `Api\TikTokIntegrationController::redirect` | `Api\TikTokIntegrationController::callback` | `TikTokAdsService` |
| Snapchat | `Api\SnapchatIntegrationController::redirect` | `Api\SnapchatIntegrationController::callback` | `SnapchatAdsService` |
| LinkedIn | `Api\LinkedInIntegrationController::initiateOAuth` | `Api\LinkedInIntegrationController::handleCallback` | `LinkedInAdsService` |
| Twitter/X | `Api\TwitterIntegrationController::initiateOAuth` | `Api\TwitterIntegrationController::handleCallback` | `TwitterAdsService` |

Integration tokens are encrypted at rest: `Integration::$casts['app_config' => 'encrypted:array']`.

### 7.3 Campaign & metrics sync

Sync can be triggered by:

- Console commands (scheduled or manual).
- API endpoints from the UI.
- Queued `RunPlatformSync` job.

Recent architecture uses a unified `sync:daily-all` command that orchestrates per-platform sub-commands.

### 7.4 Benchmarks

- **Livewire:** `app/Livewire/Pages/Benchmarks.php`
- **Service:** `app/Services/BenchmarkService.php`
- **Route:** `GET /benchmarks`

Features:

- Compare account/campaign metrics against industry benchmarks.
- Cascading filters: industry → country → category → sub-category → funnel stage.
- Objective-aware KPIs (Awareness, Leads, Sales, Traffic, Engagement).
- Sortable, resizable columns; sparklines; competitive intelligence placeholders.

### 7.5 Campaign Forecast Calculator

- **Livewire:** `app/Livewire/Pages/Calculator.php`
- **View:** `resources/views/livewire/pages/calculator.blade.php`
- **Service:** `app/Services/BenchmarkService.php`
- **Route:** `GET /calculator`

Capabilities:

- Select **industry** (restricted to *Real Estate*, *Retail*, and *E-commerce*), **objective**, and either **monthly budget** or **target results**.
- Optional filters: **platform** (Facebook, Google, TikTok, Snapchat, LinkedIn), **country**, **funnel stage** (TOF/MOF/BOF), and **user journey** (instant form / landing page).
- Two calculation modes: **Budget → Results** and **Target → Budget**.
- Produces four scenarios: **poor**, **average**, **good**, **excellent**.
- Displays KPI summary cards, scenario comparison chart, scenario cards, benchmark transparency.
- Shows **RB Benchmarks vs Global Benchmarks** comparison when external data exists.
- Generates a **Media Plan Estimate** with recommended TOF/MOF/BOF budget split for the selected objective.
- API equivalent: `POST /api/benchmarks/calculate` (see `docs/CALCULATOR_API_GUIDE.md`).

### 7.6 Industry overview / management

- **Industry Overview:** `GET /industry-overview` → `IndustryOverview.php` (super-admin only)
- **Industry Management:** `GET /industry-management` → `IndustryManagement.php` (super-admin only)
- **Service:** `app/Services/AdAccountCategoryService.php`

Hierarchy:

```
Industry
├── SubIndustry / CampaignCategory
└── AccountCategory
    └── AccountSubCategory
```

### 7.7 Client dashboards

- **Livewire:** `app/Livewire/Pages/ClientDashboard.php`
- **Service:** `app/Services/ClientDashboardService.php`
- **Route:** `GET /clients/{id}/dashboard`
- **Tabs:** Performance, SEO, Audit
- **Exports:** PDF/CSV via `DashboardExportService`

### 7.8 Spend verification

- **Livewire:** `app/Livewire/Pages/SpendVerification.php`
- **Route:** `GET /spend-verification` (super-admin only)
- Grouped spend rows (daily/monthly/total), verification status workflow, client/platform/date filters.

### 7.9 Alerts & monitoring

- **Model:** `app/Models/Alert.php`
- **Service:** `app/Services/AlertService.php`
- **Notification:** `app/Notifications/PerformanceAlertNotification.php`
- Alert types: threshold, anomaly, budget.
- `alerts:check` evaluates active alerts every 15 minutes.
- `sync:check-health` runs hourly to monitor sync health.

### 7.10 Reports & exports

- **Ad accounts export:** `AdAccountExportController::excel()` → XLSX.
- **Client dashboard export:** `DashboardExportService` → PDF/CSV.
- **SEO report export:** `SeoReportExportController` → PDF/Excel.
- **Scheduled reports:** `scheduled_reports` + `report_history` tables; `reports:generate` runs every 5 minutes.

### 7.11 Users, roles, settings

- **Settings:** `GET /settings` → `app/Livewire/Pages/Settings.php`
- **Users:** `GET /users` → `app/Livewire/Pages/Users.php` (gate: `view_users`)
- **Role Management:** `GET /roles` → `app/Livewire/Pages/RoleManagement.php` (gate: `manage_roles`)
- **Super-admin users API:** `app/Http/Controllers/Api/SuperAdminUserController.php`

---

## 8. Data Model

### Core entities

| Entity | Purpose |
|--------|---------|
| `Tenant` | Agency clients; multi-tenant boundary |
| `User` | Platform users; super-admin flag; default tenant |
| `TenantUser` | Many-to-many pivot with role/tenant_role |
| `Integration` | OAuth connection per platform per tenant; encrypted config |
| `AdAccount` | External ad account; industry/category/country/client mapping |
| `AdCampaign` | Campaigns; objective/funnel_stage/category/pixel config |
| `AdMetric` | Daily per-campaign metrics (large time-series table) |
| `Industry` | Benchmark industries |
| `SubIndustry` | Campaign-level sub-industries |
| `AccountCategory` / `AccountSubCategory` | Account classification hierarchy |
| `CampaignCategory` | Campaign categories per industry |
| `Alert` | User-defined threshold/anomaly/budget alerts |
| `ReportExport` | Queued report exports |
| `ScheduledReport` / `ReportHistory` | Recurring report jobs |
| `Dashboard` / `DashboardWidget` | User dashboards |
| `UserInvitation` | Email invitations |
| `SeoTechnicalIssue` | SEO audit findings |
| `ConversionTracking` | Conversion-pixel tracking config |

### Notable columns

- `Integration.app_config` → encrypted JSON (tokens, expiry, metadata).
- `AdAccount.account_config` → JSON with currency, manager-account flags, parent IDs.
- `AdAccount.data_verification_status`, `verified_at`, `verified_by`.
- `AdCampaign.sub_industry`, `category`, `sub_category`, `funnel_stage`, `user_journey`, `has_pixel_data`, `google_sheet_id`.
- `AdMetric.spend`, `impressions`, `reach`, `clicks`, `video_views`, `conversions`, `revenue`, `purchases`, `leads`, `calls`, `sessions`, `atc`, `app_installs`, `page_views`, `cost_per_result`, `checksum`.
- `Tenant.settings` → JSON including SEO config.
- `IndustryBenchmark` → percentile_10/25/50/75/90, sample_size, source, region.

### Pivot tables

- `tenant_users` (User ↔ Tenant)
- `role_permissions` (TenantRole ↔ Permission)

---

## 9. Integrations & Sync Architecture

### Facebook / Meta

- Graph API v23.0.
- OAuth scopes: `ads_read`, `ads_management`, `business_management`.
- Short-lived code → long-lived token exchange.
- Fetches `/insights` per campaign, daily granularity, parses `actions`/`action_values` for conversions/revenue.
- Commands: `facebook:sync-campaigns`, `facebook:sync-metrics`, `facebook:backfill-metrics`, `facebook:sync-creatives`.

### Google Ads

- Official SDK `googleads/google-ads-php` v22.
- OAuth refresh-token flow.
- Supports MCC manager accounts and child-account aggregation.
- Commands: `google-ads:sync`, `google-ads:backfill-metrics`, `google-ads:sync-accounts`, `google-ads:sync-creatives`.

### TikTok

- Service: `TikTokAdsService`.
- Commands: `tiktok:sync-campaigns`, `tiktok:sync-metrics`, `tiktok:backfill-metrics`, `tiktok:sync-creatives`.

### Snapchat

- Service: `SnapchatAdsService`.
- Token refresh scheduled every 30 minutes (`snapchat:refresh-token`).
- Commands: `snapchat:sync-campaigns`, `snapchat:sync-metrics`, `snapchat:backfill-metrics`, `snapchat:sync-creatives`.

### LinkedIn

- Service: `LinkedInAdsService`.
- Commands: `sync:linkedin-campaigns`, `linkedin:backfill-metrics`, `linkedin:sync-creatives`.

### Twitter/X

- Service: `TwitterAdsService`.
- Command: `sync:twitter-campaigns`.

### Google Sheets / Search Console / GA4 / PageSpeed

- `GoogleSheetsService`, `SearchConsoleService`, `GA4AnalyticsService`, `PageSpeedService`.
- `AutoCreateGoogleSheet` listener fires on `CampaignCreated`.
- `GoogleAuthController` handles OAuth for Search Console / GA4 / Sheets.

### Sync execution patterns

- Most syncs are triggered via artisan commands.
- Some UI-triggered syncs dispatch `RunPlatformSync` or run `exec()` background scripts stored in `storage/logs/`.
- `SyncProgressService` and `SyncMonitoringService` track health and progress.
- `CheckSyncHealth` runs hourly.

---

## 10. Frontend

### Stack reality

- **Templating:** Blade + Livewire 3.
- **Interactivity:** Alpine.js (bundled with Livewire), custom jQuery + Select2 wrappers.
- **Build:** Vite.
- **Styling:** TailwindCSS 3.
- **Charts:** Chart.js registered globally in `resources/js/app.js`.
- **i18n:** Laravel JSON translations (`resources/lang/en.json`, `ar.json`).
- **Directionality:** RTL support for Arabic; IBM Plex Sans Arabic font.

### Key Livewire components

- `Livewire/Pages/Benchmarks.php`
- `Livewire/Pages/AdAccounts.php`
- `Livewire/Pages/AdAccountDetail.php`
- `Livewire/Pages/CampaignMetrics.php`
- `Livewire/Pages/ClientDashboard.php`
- `Livewire/Pages/Clients.php`
- `Livewire/Pages/Integrations.php`
- `Livewire/Pages/SpendVerification.php`
- `Livewire/Pages/IndustryOverview.php`
- `Livewire/Pages/IndustryManagement.php`
- `Livewire/Pages/Users.php`
- `Livewire/Pages/RoleManagement.php`
- `Livewire/Pages/Settings.php`
- `Livewire/Pages/SeoReport.php`
- `Livewire/Auth/Login.php`, `Register.php`, `AcceptInvitation.php`
- `Livewire/Components/UserMenu.php`, `TenantSelector.php`, `LanguageSwitcher.php`, `ExportMenu.php`

### Layouts

- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/guest.blade.php`

---

## 11. Console Commands & Scheduled Jobs

### Scheduled tasks (`routes/console.php`)

| Schedule | Command | Purpose |
|----------|---------|---------|
| Daily 01:00 | `sync:daily-all` | Unified daily sync across all platforms |
| Sunday 02:00 | `google-ads:sync` | Weekly full Google Ads sync |
| Every 2 hours | `google-ads:sync --metrics --days=2` | Recent Google Ads metrics correction |
| Every 2 hours | `facebook:sync-metrics --days=3` | Recent Facebook metrics |
| Every 2 hours | `tiktok:sync-metrics --days=3` | Recent TikTok metrics |
| Every 2 hours | `snapchat:sync-metrics --days=3` | Recent Snapchat metrics |
| Every 2 hours | `sync:linkedin-campaigns --days=3` | Recent LinkedIn metrics |
| Every 2 hours | `sync:twitter-campaigns --days=3` | Recent X/Twitter metrics |
| Every 30 min | `snapchat:refresh-token` | Keep Snapchat token alive |
| Every minute | `queue:work --stop-when-empty --max-time=300` | Process queued UI sync jobs |
| Every 15 min | `alerts:check` | Evaluate active alerts |
| Every 5 min | `reports:generate` | Generate due scheduled reports |
| Hourly | `sync:check-health` | Sync health monitoring |
| Daily 02:00 | `google-sheets:auto-sync --dry-run` | Dry-run sheet check |
| Monday 03:00 | `google-sheets:auto-sync` | Auto-create missing sheets |
| Hourly | `google-sheets:sync-conversions --hours=1` | Hourly conversion sheet sync |
| Daily 04:00 | `google-sheets:sync-conversions --hours=24` | Daily full conversion sync |
| Twice daily | `google-sheets:heal-integrations` | Repair broken sheet integrations |
| Monthly 1st 03:00 PT | `facebook:backfill-metrics --full-history` | Facebook 37-month backfill |
| Monthly 5th 04:00 PT | `google-ads:backfill-metrics --full-history` | Google Ads full backfill |
| Monthly 10th 05:00 PT | `linkedin:backfill-metrics --full-history` | LinkedIn full backfill |
| Monthly 15th 04:00 PT | `tiktok:backfill-metrics --full-history` | TikTok full backfill |
| Monthly 20th 04:00 PT | `snapchat:backfill-metrics --full-history` | Snapchat full backfill |

### Notable command categories

**Sync:** `facebook:*`, `google-ads:*`, `tiktok:*`, `snapchat:*`, `sync:linkedin-campaigns`, `sync:twitter-campaigns`

**Maintenance/enrichment:** `sync:daily-all`, `alerts:check`, `reports:generate`, `sync:check-health`, `detect:anomalies`, `parse-campaign-names`, `enrich-campaign-data`, `populate-clients-from-accounts`, `merge-duplicate-clients`, `auto-populate-account-categories`, `reassign-account-categories`, `bulk-update-ad-account-industry`, `bulk-update-campaign-sub-industry`, `populate-industry-categories`, `import-ad-account-industries`

**Google Sheets:** `google-sheets:auto-sync`, `google-sheets:sync-conversions`, `google-sheets:heal-integrations`

**CSV import / diagnostics:** `import:facebook-csv`, `import:google-ads-csv`, `import:mcc-performance-report`, `audit:metrics-spend`, `diagnose:facebook-permissions`

---

## 12. Testing & CI/CD

### Test structure

```
tests/
├── Feature/
│   ├── AuthTest.php
│   ├── PublicRegistrationTest.php
│   ├── SyncFacebookCampaignsTest.php
│   ├── SyncFacebookMetricsTest.php
│   ├── SyncGoogleAdsTest.php
│   ├── SyncLinkedInCampaignsTest.php
│   ├── SyncSnapchatCampaignsTest.php
│   ├── SyncTikTokMetricsTest.php
│   └── UserManagementTest.php
├── Unit/
│   ├── BenchmarkServiceTest.php
│   ├── FacebookMetricsSyncServiceTest.php
│   ├── GoogleAdsMetricsSyncServiceTest.php
│   ├── ObjectiveCalculatorTest.php
│   ├── SnapchatEnsureValidTokenTest.php
│   ├── SyncMonitoringServiceTest.php
│   └── TenantIsolationTest.php
└── e2e/          # Playwright specs (18 specs)
    ├── benchmarks-cascading-filters.spec.ts
    ├── client-dashboard.spec.ts
    ├── industry-management.spec.ts
    └── ...
```

### CI/CD

- Workflow: `.github/workflows/ci.yml`
- Triggers: push/PR to `main` or `develop`.
- Jobs:
  - **test** – PHP 8.2 + MySQL 8 + Redis; composer install, npm ci, migrations, PHPStan, Laravel Pint, PHPUnit with `--coverage --min=80`, frontend build, Codecov upload.
  - **security** – `composer audit`.
  - **deploy** – artifact creation on `main` push.
  - **deploy-staging** – SSH deploy to staging on `develop` push.

### E2E

- `playwright.config.ts` targets `https://rb-benchmarks.redbananas.com`.
- Scripts: `npm run test:e2e`, `test:e2e:headed`, `test:e2e:ui`, `test:e2e:report`.

### External API consumers

- Sanctum bearer tokens are used for API authentication.
- Generate a token from the server: `php artisan app:generate-api-token user@redbananas.com --name="Website Calculator"`.
- Full calculator integration guide: `docs/CALCULATOR_API_GUIDE.md`.

---

## 13. Deployment & Operations

### Docker

- `Dockerfile` – PHP 8.2-FPM, Node.js/npm installed directly, runs as non-root `www` user (UID 1000).
- `docker-compose.yml` – defines `app` (PHP-FPM), `nginx`, `mysql` (8.0), `redis`, and `node` (Vite dev) services.
- **Caveat:** `docker-compose.yml` references `./docker/nginx/` which does not exist in the repo.
- **Caveat:** no `.dockerignore` currently; `Dockerfile` copies the whole working directory.

### Runtime environment

Key variables from `.env.example`:

- `APP_NAME`, `APP_URL`
- `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `QUEUE_CONNECTION=database`
- `CACHE_STORE=database` (Redis config present)
- Platform OAuth keys: `FACEBOOK_APP_ID`, `FB_APP_SECRET`, `GOOGLE_ADS_CLIENT_ID`, etc.
- `PAGESPEED_API_KEY`
- AWS keys for S3.
- Google Analytics / Search Console credentials.

> **Config mismatch:** `.env.example` uses `FACEBOOK_*` keys while `config/services.php` reads `FB_*`.

### Production deployment commands

```bash
composer install --optimize-autoloader --no-dev
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear
php artisan queue:restart
```

### Staging

- Auto-deploy from `develop` via `appleboy/ssh-action` to the staging host/path defined in GitHub secrets.
- Queue service `rb-staging-queue` is restarted after deploy.

---

## 14. Security & Audit Summary

The repository contains several audit/review documents (`AUDIT_REPORT.md`, `SCOPE_AUDIT_REPORT.md`, `ARCHITECTURE_REVIEW.md`, `BLADE_UNESCAPED_AUDIT.md`).

### Critical findings from `AUDIT_REPORT.md`

1. **Production `.env` exposed on disk** – live `APP_KEY`, DB password, Facebook/Snapchat/TikTok/Google/LinkedIn secrets. File permissions hardened but **all 12 secrets must be rotated**.
2. **Google service account private keys** on disk at `storage/app/google/`. Rotation required.
3. **PhpSpreadsheet SSRF/RCE** (CVE-2026-34084) – patch immediately.
4. **`/api/debug/request` exposes bearer tokens** – should be removed or heavily restricted.

### Other notable findings

- Missing `DashboardPolicy` registration – **fixed** (`DashboardPolicy` exists and is registered in `AppServiceProvider`).
- Open redirects via `Referer` header in `LanguageSwitcher.php` and `TenantSelector.php`.
- Extensive `withoutGlobalScopes()` usage – **audited and refactored** (see `SCOPE_AUDIT_REPORT.md`).
- `SESSION_ENCRYPT=false` in `.env` – should be `true` in production.
- OAuth redirect URI not validated against allowlist; OAuth state uses Base64 without HMAC.
- Dependency vulnerabilities in `axios`, `vite`, `google/protobuf`, `league/commonmark`, `symfony/routing`, `symfony/yaml`.

### Positive findings

- OAuth tokens encrypted at rest (`Integration` uses `encrypted:array`).
- Invitation tokens use 64-char random strings.
- Dockerfile runs as non-root.
- Secure cookie settings configured.
- Parameterized queries throughout; no SQL injection observed.

### Scope audit (`SCOPE_AUDIT_REPORT.md`)

- **24 usages** of `withoutGlobalScopes()` reviewed.
- **2 critical/high IDOR/data-leakage issues fixed:**
  - `AdAccounts::updateAccountIndustry/Country/Category/Client` removed `withoutGlobalScopes()` (was vulnerable to cross-tenant edits).
  - `BenchmarkService::getAccountBenchmark()` added explicit tenant ownership check.
- **20 safe usages refactored** from blanket `withoutGlobalScopes()` to granular `withoutGlobalScope(TenantScope::class)`.
- **Current status:** zero blanket `withoutGlobalScopes()` calls remain.

### Blade unescaped audit (`BLADE_UNESCAPED_AUDIT.md`)

- **14 total `{!! !!}` usages** found.
- **8 changed** to `{{ }}` in vendor Livewire pagination.
- **5 verified safe and retained:** sparkline SVGs and platform SVG maps.
- Risk level after audit: **Low**.

---

## 15. Known Issues & Technical Debt

From `ARCHITECTURE_REVIEW.md` and direct codebase observation:

- **README/frontend mismatch:** README advertises Vue 3 + TypeScript + Pinia; actual frontend is Blade + Livewire + Alpine.js.
- **Sync services are large:** some services exceed 600 lines and mix API client logic with business logic. Recommendation: extract `app/Services/ApiClients/` per platform.
- **Controller bloat:** `FacebookIntegrationController` is 556 lines.
- **Memory-heavy exports:** `AdAccountExportController` loads all accounts + metrics into memory. Recommendation: use cursor/pagination.
- **No `ad_metrics` partitioning:** the table will grow very large. Recommendation: partition by date or platform.
- **No database index strategy documented.** Recommended indexes:
  ```sql
  CREATE INDEX idx_ad_metrics_lookup ON ad_metrics(ad_account_id, ad_campaign_id, date, platform);
  CREATE INDEX idx_ad_campaigns_lookup ON ad_campaigns(ad_account_id, external_campaign_id);
  ```
- **No queue monitoring/dead-letter queue** observed.
- **No HTTP client caching** for external APIs.
- **No circuit breaker** pattern for platform APIs.
- **No `.dockerignore`**; `Dockerfile` copies `.env` and secrets into image layers.
- **Missing nginx config directory** referenced by `docker-compose.yml`.
- **Test coverage is low** despite recent additions.

---

## 16. Quick Reference File Map

| Concern | Path |
|---------|------|
| Entry web routes | `routes/web.php` |
| API routes + OAuth callbacks | `routes/api.php` |
| Scheduled commands | `routes/console.php` |
| Middleware aliases | `bootstrap/app.php` |
| Service providers | `bootstrap/providers.php` |
| Tenant scope | `app/Models/Scopes/TenantScope.php` |
| Belongs-to-tenant trait | `app/Traits/BelongsToTenant.php` |
| Web tenant middleware | `app/Http/Middleware/WebTenantMiddleware.php` |
| Permission middleware | `app/Http/Middleware/CheckPermission.php` |
| Super-admin middleware | `app/Http/Middleware/SuperAdminMiddleware.php` |
| CSP middleware | `app/Http/Middleware/ContentSecurityPolicy.php` |
| Main benchmark service | `app/Services/BenchmarkService.php` |
| Client dashboard service | `app/Services/ClientDashboardService.php` |
| Dashboard export service | `app/Services/DashboardExportService.php` |
| Facebook metrics sync | `app/Services/FacebookMetricsSyncService.php` |
| Google Ads service | `app/Services/GoogleAdsService.php` |
| Alert service | `app/Services/AlertService.php` |
| User invitations | `app/Services/UserInvitationService.php` |
| Public registration | `app/Livewire/Auth/Register.php` |
| Accept invitation | `app/Livewire/Auth/AcceptInvitation.php` |
| Calculator page | `app/Livewire/Pages/Calculator.php` |
| Redbananas super-admin migration | `database/migrations/2026_08_05_101500_promote_redbananas_users_to_super_admins.php` |
| API token generator | `app/Console/Commands/GenerateApiToken.php` |
| Calculator API guide | `docs/CALCULATOR_API_GUIDE.md` |
| Benchmarks page | `app/Livewire/Pages/Benchmarks.php` |
| Ad accounts page | `app/Livewire/Pages/AdAccounts.php` |
| Client dashboard page | `app/Livewire/Pages/ClientDashboard.php` |
| Integrations page | `app/Livewire/Pages/Integrations.php` |
| Users page | `app/Livewire/Pages/Users.php` |
| Role management | `app/Livewire/Pages/RoleManagement.php` |
| Ad account export | `app/Http/Controllers/AdAccountExportController.php` |
| SEO report export | `app/Http/Controllers/SeoReportExportController.php` |
| CI pipeline | `.github/workflows/ci.yml` |
| Docker setup | `Dockerfile`, `docker-compose.yml` |
| Security audit | `AUDIT_REPORT.md` |
| Scope audit | `SCOPE_AUDIT_REPORT.md` |
| Architecture review | `ARCHITECTURE_REVIEW.md` |
| Blade audit | `BLADE_UNESCAPED_AUDIT.md` |

---

*End of guide.*
