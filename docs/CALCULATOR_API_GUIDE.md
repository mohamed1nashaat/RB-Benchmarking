# RB Benchmarks — Calculator API Integration Guide

This guide explains how to call the RB Benchmarks calculator from an external website or service. The calculator returns expected campaign results (impressions, clicks, leads/conversions, cost-per-result) based on the real benchmarks stored in the platform.

---

## Base URL

| Environment | Base URL |
|-------------|----------|
| Production  | `https://rb-benchmarks.redbananas.com/api` |
| Staging     | `https://rb-staging.red/api` *(when live)* |

All endpoints below are prefixed with this base URL.

---

## Authentication

The calculator endpoints require a **Sanctum bearer token**.

### 1. Get a token via login

```http
POST /auth/login
Content-Type: application/json
```

**Body:**

```json
{
  "email": "test@redbananas.com",
  "password": "your-password"
}
```

**Response:**

```json
{
  "user": { ... },
  "token": "1|laravel_sanctum_token_here",
  "is_super_admin": true,
  "tenants": [ ... ]
}
```

Store the `token` value and send it in every request:

```http
Authorization: Bearer 1|laravel_sanctum_token_here
```

### 2. Generate a token from the server (for integrations)

SSH into the server and run:

```bash
php artisan app:generate-api-token test@redbananas.com --name="Website Calculator"
```

The command prints the plain-text token. Copy it into your website’s environment variable or secret manager.

---

## Calculator Endpoints

### Get available industries

Returns the industries that currently have benchmark data. The calculator UI is restricted to **Real Estate**, **Retail**, and **E-commerce**.

```http
GET /benchmarks/industries
Authorization: Bearer <token>
```

**Response:**

```json
{
  "industries": {
    "real_estate": "Real Estate",
    "retail": "Retail & E-commerce",
    "ecommerce": "E-commerce & Retail"
  },
  "industries_with_data": ["real_estate", "retail"]
}
```

### Get filter options

Returns the available platforms, funnel stages, and user journeys you can pass to the calculator.

```http
GET /benchmarks/filter-options
Authorization: Bearer <token>
```

**Response:**

```json
{
  "platforms": ["facebook", "google", "tiktok", "snapchat", "linkedin"],
  "funnel_stages": ["TOF", "MOF", "BOF"],
  "user_journeys": ["instant_form", "landing_page"],
  "funnel_stage_labels": { ... },
  "user_journey_labels": { ... },
  "platform_labels": { ... }
}
```

### Calculate expected results

```http
POST /benchmarks/calculate
Authorization: Bearer <token>
Content-Type: application/json
```

**Body:**

```json
{
  "spend": 10000,
  "industry": "real_estate",
  "objective": "leads",
  "platform": "facebook",
  "country": "Saudi Arabia",
  "funnel_stage": "BOF",
  "user_journey": "landing_page"
}
```

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `spend` | yes* | number | Monthly budget in SAR (1 – 100,000,000). |
| `industry` | yes | string | One of the industries returned by `/benchmarks/industries`. |
| `objective` | no | string | Default: `leads`. See objective list below. |
| `platform` | no | string | `facebook`, `google`, `tiktok`, `snapchat`, `linkedin`. |
| `country` | no | string | Country name, e.g. `Saudi Arabia`. |
| `funnel_stage` | no | string | `TOF`, `MOF`, or `BOF`. |
| `user_journey` | no | string | `instant_form` or `landing_page`. |
| `mode` | no | string | `budget_to_results` (default) or `target_to_budget`. |
| `target` | yes** | number | Required when `mode=target_to_budget`. Desired number of results. |

\* `spend` is required when `mode` is omitted or `budget_to_results`.  
\*\* `target` is required when `mode=target_to_budget`.

### Target → Budget mode

Pass `mode: target_to_budget` and `target` instead of `spend`:

```json
{
  "mode": "target_to_budget",
  "target": 500,
  "industry": "retail",
  "objective": "leads",
  "platform": "facebook",
  "country": "Saudi Arabia"
}
```

The response includes a `budget` object:

```json
{
  "data": {
    "input": { "mode": "target_to_budget", "target": 500, ... },
    "budget": {
      "recommended": 12500.00,
      "poor": 18500.00,
      "average": 12500.00,
      "good": 8500.00,
      "excellent": 5200.00
    },
    "predictions": { ... }
  }
}
```

**Objectives:**

`leads`, `messages`, `calls`, `sales`, `conversions`, `catalog_sales`, `store_visits`, `traffic`, `link_clicks`, `engagement`, `video_views`, `page_likes`, `awareness`, `reach`, `impressions`, `app_installs`, `app_events`.

**Response:**

```json
{
  "data": {
    "input": {
      "spend": 10000,
      "industry": "real_estate",
      "objective": "leads",
      "filters": {
        "platform": ["facebook"],
        "country": ["Saudi Arabia"],
        "funnel_stage": ["BOF"],
        "user_journey": ["landing_page"]
      }
    },
    "predictions": {
      "poor": { ... },
      "average": { ... },
      "good": { ... },
      "excellent": { ... }
    },
    "benchmark_info": {
      "calculation_method": "dynamic",
      "data_points": 24,
      "date_range": { "from": "2025-08-05", "to": "2026-08-05" }
    },
    "disclaimers": [ ... ]
  }
}
```

The response also includes these extra sections when data is available:

- `global_predictions` — same four scenarios calculated from external/global benchmark data.
- `media_plan` — recommended funnel-stage budget split for the selected objective.

Each prediction contains:

```json
{
  "impressions": { "value": 1234567, "calculation": "..." },
  "clicks": { "value": 12345, "calculation": "..." },
  "primary_result": { "value": 567, "label": "Leads", "calculation": "..." },
  "metrics": {
    "ctr": "2.15%",
    "cpc": "SAR 1.23",
    "cpm": "SAR 12.34",
    "cvr": "5.67%",
    "cpl": "SAR 45.67"
  },
  "cost_per_result": {
    "cost_per_impression": "SAR 0.0001",
    "cost_per_click": "SAR 1.23",
    "cost_per_conversion": "SAR 45.67"
  }
}
```

---

## Example: cURL

```bash
curl -X POST https://rb-benchmarks.redbananas.com/api/benchmarks/calculate \
  -H "Authorization: Bearer 1|your_token_here" \
  -H "Content-Type: application/json" \
  -d '{
    "spend": 25000,
    "industry": "retail",
    "objective": "sales",
    "platform": "facebook",
    "country": "Saudi Arabia",
    "funnel_stage": "BOF",
    "user_journey": "landing_page"
  }'
```

---

## Example: JavaScript / Fetch

```javascript
const API_URL = 'https://rb-benchmarks.redbananas.com/api/benchmarks/calculate';
const TOKEN = process.env.RB_BENCHMARKS_API_TOKEN;

async function getForecast(spend, industry, objective, platform) {
  const response = await fetch(API_URL, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${TOKEN}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      spend,
      industry,
      objective,
      platform,
      country: 'Saudi Arabia',
      funnel_stage: 'BOF',
      user_journey: 'landing_page',
    }),
  });

  if (!response.ok) {
    throw new Error(`RB Benchmarks API error: ${response.status}`);
  }

  const { data } = await response.json();
  return data.predictions.average;
}

// Usage
getForecast(10000, 'real_estate', 'leads', 'facebook')
  .then(forecast => console.log(forecast.primary_result));
```

---

## Example: PHP / Laravel HTTP Client

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken(config('services.rb_benchmarks.token'))
    ->post('https://rb-benchmarks.redbananas.com/api/benchmarks/calculate', [
        'spend' => 10000,
        'industry' => 'ecommerce',
        'objective' => 'sales',
        'platform' => 'google',
        'country' => 'Saudi Arabia',
    ]);

$forecast = $response->json('data.predictions.average');
```

---

## Errors

| HTTP Status | Meaning |
|-------------|---------|
| `401` | Missing or invalid bearer token. |
| `422` | Validation error (e.g. invalid industry, budget too low). Response includes the Laravel validation errors. |
| `500` | Calculation failed (usually not enough benchmark data for the selected filters). |

**Validation error example:**

```json
{
  "message": "The industry field must be one of the allowed values.",
  "errors": {
    "industry": ["The selected industry is invalid."]
  }
}
```

---

## How the numbers are calculated

1. The service collects synced ad metrics for the selected industry.
2. Optional filters (`platform`, `country`, `funnel_stage`, `user_journey`) narrow the dataset.
3. It removes statistical outliers using IQR and domain-specific minimum thresholds.
4. It computes percentiles (p25 / p50 / p75) for CTR, CPC, CPM, CVR, CPL, CPA, CPI.
5. It builds four scenarios: **poor**, **average**, **good**, **excellent**, using multipliers on top of the median benchmarks.
6. It derives impressions → clicks → primary result from the budget and those metrics.

If no data exists for the chosen combination, the API returns `500` with a message such as `Not enough data to calculate predictions for real_estate industry`. Loosen or remove filters to use a larger dataset.

---

## Notes

- The public (unauthenticated) `/api/benchmarks/calculate` endpoint returns **demo averages** and is intended for onboarding examples only. Use the authenticated endpoint for real data.
- Rate limit for authenticated endpoints: **60 requests per minute** by default.
- All monetary values are returned in **SAR** (displayed as `SR`).

---

## Need a token?

If you do not have API access yet, ask the platform owner to run:

```bash
php artisan app:generate-api-token your-email@redbananas.com --name="Website Calculator"
```

Only `@redbananas.com` email addresses can register or receive invitations on this instance.
