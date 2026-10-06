# RB Benchmarks — API Integration Guide

A practical guide for integrating an external website or calculator with the RB Benchmarks API. Use this to fetch industry benchmarks and calculate expected results (impressions, clicks, leads, sales, etc.) based on budget/spend.

> **Base URL:** `https://rb-benchmarks.redbananas.com/api`  
> **Auth:** Laravel Sanctum token (`Authorization: Bearer <token>`)  
> **Format:** JSON in / JSON out  
> **Rate limits:** `60 req/min` for authenticated routes, `5 req/min` for login

---

## Table of Contents

1. [Quick Start](#1-quick-start)
2. [Authentication](#2-authentication)
3. [Tenant Context](#3-tenant-context)
4. [Endpoints for a Website Calculator](#4-endpoints-for-a-website-calculator)
5. [Step-by-Step Example](#5-step-by-step-example)
6. [Response Reference](#6-response-reference)
7. [Error Handling](#7-error-handling)
8. [Demo / Test Endpoints](#8-demo--test-endpoints)
9. [Security Best Practices](#9-security-best-practices)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Quick Start

For a calculator that asks the user for **industry + budget** and returns **projected numbers**, the minimal flow is:

```
POST /auth/login                 → get Bearer token
GET  /me                         → confirm user + tenants
GET  /benchmarks/industries      → list industries
POST /benchmarks/calculate       → send spend + industry + objective
```

The main endpoint you will call repeatedly is:

```http
POST https://rb-benchmarks.redbananas.com/api/benchmarks/calculate
```

---

## 2. Authentication

RB Benchmarks uses **Laravel Sanctum** tokens.

### 2.1 Get a token via login

```bash
curl -X POST https://rb-benchmarks.redbananas.com/api/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "service@redbananas.com",
    "password": "your-password"
  }'
```

**Response:**

```json
{
  "user": { "id": 5, "name": "Service Account", "email": "service@redbananas.com", ... },
  "token": "1|abcdefghijklmnopqrstuvwxyz...",
  "is_super_admin": false,
  "tenants": [
    { "id": 11, "name": "CodeStan", "slug": "codestan", "role": "viewer" }
  ]
}
```

Store `token` securely on your backend. Every subsequent request must include:

```http
Authorization: Bearer <token>
```

### 2.2 Long-lived service token (recommended for server-to-server)

If you want a token that does not expire with the user session, create it from the Laravel side once:

```php
$user = User::where('email', 'service@redbananas.com')->first();
$token = $user->createToken('website-calculator')->plainTextToken;
```

Then store that token in your website backend’s environment variables (`RB_API_TOKEN`).

### 2.3 Logout / revoke token

```bash
curl -X POST https://rb-benchmarks.redbananas.com/api/auth/logout \
  -H "Authorization: Bearer <token>"
```

---

## 3. Tenant Context

RB Benchmarks is **multi-tenant**. Most data endpoints (including benchmarks) are filtered by the tenant you send.

### 3.1 How to specify tenant

Pass the tenant ID in the header:

```http
X-Tenant-ID: 11
```

If you omit it, the API falls back to the user’s `default_tenant_id`. If the user has no default tenant, you get:

```json
{ "message": "No tenant specified" }
```

### 3.2 Which tenant ID to use

- After login, `tenants[].id` tells you which tenants the user can access.
- Alternatively, `GET /api/me` returns the current default tenant.
- For a public website calculator, create a dedicated service user attached to a tenant that has ad-account data.

### 3.3 Cross-tenant / global data

- **Super admins** can omit `X-Tenant-ID` and see data across all tenants.
- Regular users see only their assigned tenant(s).
- Publicly registered users (`/register`) are created with `view_all_tenants = true`, so they bypass tenant filtering and see all account data.

---

## 4. Endpoints for a Website Calculator

### 4.1 `POST /auth/login`

| Field | Type | Required |
|-------|------|----------|
| `email` | string | yes |
| `password` | string | yes |

Returns user profile + Bearer token.

---

### 4.2 `GET /me`

Returns current user and available tenants.

```bash
curl https://rb-benchmarks.redbananas.com/api/me \
  -H "Authorization: Bearer <token>"
```

**Response:**

```json
{
  "user": { "id": 5, "name": "Service Account", ... },
  "current_tenant": { "id": 11, "name": "CodeStan" },
  "role": "viewer",
  "is_super_admin": false,
  "tenants": [
    { "id": 11, "name": "CodeStan", "slug": "codestan", "role": "viewer" }
  ]
}
```

---

### 4.3 `GET /benchmarks/industries`

Lists all industries that can be used for the calculator, plus which ones currently have benchmark data.

```bash
curl "https://rb-benchmarks.redbananas.com/api/benchmarks/industries" \
  -H "Authorization: Bearer <token>" \
  -H "X-Tenant-ID: 11"
```

**Response:**

```json
{
  "industries": {
    "technology": "Technology",
    "retail": "Retail",
    "finance": "Finance",
    ...
  },
  "industries_with_data": ["technology", "retail", "finance"]
}
```

Use the keys from `industries` (e.g., `technology`) as the `industry` parameter in `/benchmarks/calculate`.

---

### 4.4 `POST /benchmarks/calculate` ⭐

The main calculator endpoint. Send a budget/spend amount + industry + objective and get projected results.

```bash
curl -X POST "https://rb-benchmarks.redbananas.com/api/benchmarks/calculate" \
  -H "Authorization: Bearer <token>" \
  -H "X-Tenant-ID: 11" \
  -H "Content-Type: application/json" \
  -d '{
    "spend": 50000,
    "industry": "technology",
    "objective": "leads"
  }'
```

#### Request body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `spend` | numeric | yes | Budget amount (currency is the account’s primary currency, usually SAR) |
| `industry` | string | yes | Industry key from `/benchmarks/industries` |
| `objective` | string | no | `leads` (default), `messages`, `calls`, `sales`, `conversions`, `catalog_sales`, `traffic`, `link_clicks`, `engagement`, `video_views`, `awareness`, `reach`, `impressions`, `app_installs` |

#### Response shape

```json
{
  "data": {
    "input": {
      "spend": 50000,
      "industry": "technology",
      "objective": "leads"
    },
    "predictions": {
      "poor": { ... },
      "average": { ... },
      "good": { ... },
      "excellent": { ... }
    },
    "benchmark_info": {
      "calculation_method": "dynamic",
      "data_points": 127,
      "date_range": { "from": "2025-07-22", "to": "2026-07-22" }
    },
    "disclaimers": [ ... ]
  }
}
```

Each scenario (`poor`, `average`, `good`, `excellent`) contains:

```json
{
  "impressions": { "value": 1234567, "calculation": "..." },
  "clicks": { "value": 28456, "calculation": "..." },
  "primary_result": { "value": 3420, "label": "Leads", "calculation": "..." },
  "secondary_results": [],
  "metrics": {
    "ctr": "2.30%",
    "cpc": "SAR 1.76",
    "cpm": "SAR 40.50",
    "cvr": "12.02%",
    "cpl": "SAR 14.62"
  },
  "cost_per_result": {
    "cost_per_impression": "SAR 0.0405",
    "cost_per_click": "SAR 1.76",
    "cost_per_conversion": "SAR 14.62"
  },
  "leads": { "value": 3420, "label": "Leads", "calculation": "..." }
}
```

#### Supported objectives and what `primary_result` means

| Objective | Primary result label | Meaning |
|-----------|---------------------|---------|
| `leads` | Leads | Number of generated leads |
| `messages` | Messages | Conversations / messages |
| `calls` | Calls | Phone calls |
| `sales`, `conversions`, `catalog_sales` | Conversions | Purchases / conversions |
| `traffic`, `link_clicks` | Website Visits | Clicks / visits |
| `video_views` | Video Views | Estimated video views |
| `awareness`, `reach`, `impressions` | Reach | Unique users reached |
| `engagement` | Engagements | Post engagements |
| `app_installs` | App Installs | Estimated installs |

---

### 4.5 `GET /benchmarks/industry-benchmarks`

Raw industry benchmark ranges (25th / 50th / 75th percentile) for custom calculations.

```bash
curl "https://rb-benchmarks.redbananas.com/api/benchmarks/industry-benchmarks?from=2025-01-01&to=2026-01-01&industry=technology" \
  -H "Authorization: Bearer <token>" \
  -H "X-Tenant-ID: 11"
```

**Query params:** `from`, `to`, `platform` (`facebook|google|tiktok`), `objective`, `funnel_stage` (`TOF|MOF|BOF`), `user_journey`, `sub_industry`, `has_pixel_data`.

---

### 4.6 `GET /benchmarks/details`

Returns methodology, available industries, and account counts.

```bash
curl "https://rb-benchmarks.redbananas.com/api/benchmarks/details?from=2025-01-01&to=2026-01-01" \
  -H "Authorization: Bearer <token>" \
  -H "X-Tenant-ID: 11"
```

---

## 5. Step-by-Step Example

### Goal

Build a form on your website:

- Industry dropdown
- Budget input (SAR)
- Objective dropdown
- Show projected leads/clicks/etc.

### Backend flow (recommended)

```php
// 1. Store credentials in .env
$apiToken = env('RB_API_TOKEN');      // long-lived Sanctum token
$tenantId = env('RB_API_TENANT_ID');  // e.g., 11

// 2. Call the calculator
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiToken,
    'X-Tenant-ID'   => $tenantId,
    'Accept'        => 'application/json',
])->post('https://rb-benchmarks.redbananas.com/api/benchmarks/calculate', [
    'spend'     => (float) $request->budget,
    'industry'  => $request->industry,     // e.g., "technology"
    'objective' => $request->objective,    // e.g., "leads"
]);

$scenarios = $response->json('data.predictions');

// 3. Return to frontend
return response()->json([
    'budget' => $request->budget,
    'average_leads' => $scenarios['average']['primary_result']['value'] ?? null,
    'excellent_leads' => $scenarios['excellent']['primary_result']['value'] ?? null,
    'metrics' => $scenarios['average']['metrics'] ?? null,
]);
```

### Frontend flow

```javascript
fetch('/api/your-calculator-proxy', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    industry: 'technology',
    budget: 50000,
    objective: 'leads'
  })
})
.then(r => r.json())
.then(data => {
  console.log('Average leads:', data.average_leads);
});
```

> **Never expose the RB API token in browser-side JavaScript.** Always proxy through your own backend.

---

## 6. Response Reference

### Success

HTTP `200` with JSON body.

### Validation errors

HTTP `422`:

```json
{
  "message": "The spend field is required.",
  "errors": {
    "spend": ["The spend field is required."]
  }
}
```

### Auth errors

HTTP `401`:

```json
{ "message": "Unauthenticated" }
```

### Tenant errors

HTTP `400`:

```json
{ "message": "No tenant specified" }
```

HTTP `403`:

```json
{ "message": "Access denied to tenant" }
```

HTTP `404`:

```json
{ "message": "Tenant not found" }
```

---

## 7. Error Handling

The calculator endpoint returns `500` with a generic message if the industry has no benchmark data:

```json
{
  "error": "Failed to calculate expected results",
  "message": "An unexpected error occurred"
}
```

In practice this usually means the chosen industry has **too few accounts** in the system. Use `/benchmarks/industries` to show only `industries_with_data` in your dropdown.

Recommended retry / fallback strategy:

1. Call `/benchmarks/industries` daily and cache it.
2. Only allow selection of `industries_with_data`.
3. If `calculate` fails, show a friendly message: *“Not enough data for this industry yet.”*

---

## 8. Demo / Test Endpoints

No authentication required — useful for prototyping.

| Endpoint | Description |
|----------|-------------|
| `GET /api/demo/benchmarks/summary` | Sample summary response |
| `GET /api/demo/benchmarks/industry-benchmarks` | Sample benchmark ranges |
| `GET /api/demo/benchmarks/insights` | Sample insights |
| `GET /api/demo/benchmarks/trending-metrics?metric=ctr` | Sample trend data |
| `GET /api/demo/benchmarks/competitive-analysis` | Sample competitive data |

These return **static mock data**, not real account data.

---

## 9. Security Best Practices

1. **Never commit tokens.** Store `RB_API_TOKEN` in environment variables or a secrets manager.
2. **Proxy through your backend.** Do not call RB Benchmarks directly from the browser.
3. **Use HTTPS only.** Tokens are sent in headers.
4. **Create a dedicated service user** with `viewer` role and `view_all_tenants = true` if the calculator needs cross-tenant data.
5. **Rotate tokens** if compromised. Revoke via `/api/auth/logout` or delete the token from the database (`personal_access_tokens` table).
6. **Restrict IP / domain** on the RB Benchmarks Nginx side if only your website should call the API.

---

## 10. Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| `401 Unauthenticated` | Missing or invalid `Authorization` header | Verify token and prefix `Bearer ` |
| `400 No tenant specified` | User has no default tenant and `X-Tenant-ID` is missing | Send `X-Tenant-ID` header |
| `403 Access denied to tenant` | User not attached to that tenant | Attach user in `/admin/users` or pick a different tenant |
| `500 Failed to calculate expected results` | Industry has no data | Use only `industries_with_data` |
| Empty benchmark results | Tenant has no synced ad accounts/metrics | Connect ad accounts and run sync |
| Demo endpoints return data but real endpoints don’t | Auth/tenant issue | Check token and `X-Tenant-ID` |

---

## Code Map

| Concern | Path |
|---------|------|
| API routes | `routes/api.php` |
| Auth controller | `app/Http/Controllers/Api/AuthController.php` |
| Benchmark controller | `app/Http/Controllers/Api/BenchmarkController.php` |
| Calculator logic | `app/Services/BenchmarkService.php::calculateExpectedResults()` |
| Tenant middleware | `app/Http/Middleware/TenantMiddleware.php` |
| User model / tokens | `app/Models/User.php` (uses `Laravel\Sanctum\HasApiTokens`) |

---

*End of guide.*
