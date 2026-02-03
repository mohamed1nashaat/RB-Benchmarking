# Alternative API Methods Testing Guide

## Overview

This guide documents alternative API approaches to retrieve historical advertising data that standard daily-granularity queries may not access. These tests explore monthly/yearly aggregations and lifetime presets that may have different retention policies.

## Background

Current API backfills (Facebook, Google Ads, LinkedIn, Snapchat) returned **0 historical metrics** when querying with daily granularity. This testing explores whether alternative API parameters can access historical data through different aggregation levels.

## Test Commands Created

### 1. Facebook - Monthly Aggregation & Lifetime Presets

**Command:** `php artisan facebook:test-historical-monthly`

**Options:**
- `--account-id=` : Specific ad account ID to test (optional)
- `--start-date=` : Start date (default: 2015-01-01)
- `--end-date=` : End date (default: today)

**Methods Tested:**

1. **Monthly time_increment**
   - Changes `time_increment: 1` (daily) → `time_increment: 'monthly'`
   - **Hypothesis:** Facebook may retain monthly summaries beyond 37-month daily limit
   - **Likelihood:** 40%

2. **date_preset=maximum**
   - Uses `date_preset: 'maximum'` instead of custom `time_range`
   - **Hypothesis:** Preset may trigger different data retrieval logic
   - **Likelihood:** 35%

3. **date_preset=lifetime**
   - Uses `date_preset: 'lifetime'` for lifetime aggregates
   - **Hypothesis:** May return total lifetime metrics even without daily breakdown
   - **Likelihood:** 35%

4. **Lifetime aggregate (no time breakdown)**
   - Removes `time_increment` parameter entirely
   - **Hypothesis:** Single aggregate value might be available
   - **Likelihood:** 30%

**Example Usage:**
```bash
# Test Mancini account with historical date range
php artisan facebook:test-historical-monthly --start-date=2015-01-01 --end-date=2022-10-31

# Test specific account
php artisan facebook:test-historical-monthly --account-id=16 --start-date=2015-01-01
```

**Expected Output:**
- Comparison of 4 different API methods
- Total spend, impressions, clicks for each method
- Date ranges returned
- Success/failure indicators

---

### 2. Google Ads - Monthly/Yearly Segments

**Command:** `php artisan google-ads:test-historical-monthly`

**Options:**
- `--account-id=` : Specific ad account ID to test (optional)
- `--customer-id=` : Google Ads Customer ID (e.g., 819-554-9637)
- `--start-year=` : Start year (default: 2015)
- `--end-year=` : End year (default: current year)

**Methods Tested:**

1. **Yearly Aggregation (segments.year)**
   - Changes `segments.date` → `segments.year`
   - Query: `SELECT campaign.id, segments.year, metrics.* FROM campaign WHERE segments.year >= 2015`
   - **Hypothesis:** Google may have archived yearly summaries even if daily data returns 0
   - **Likelihood:** 55% (HIGHEST)

2. **Monthly Aggregation (segments.month)**
   - Changes `segments.date` → `segments.month`
   - Query: `SELECT campaign.id, segments.month, metrics.* FROM campaign WHERE segments.year >= 2015`
   - **Hypothesis:** Monthly summaries may persist longer than daily data
   - **Likelihood:** 50%

3. **Daily Segments (comparison)**
   - Current method for baseline comparison
   - Tests sample period (first month of start year)

**Example Usage:**
```bash
# Test Mancini account (known to have $9.1M missing data)
php artisan google-ads:test-historical-monthly --customer-id=819-554-9637 --start-year=2015 --end-year=2021

# Test specific database account
php artisan google-ads:test-historical-monthly --account-id=121 --start-year=2015
```

**Expected Output:**
- Yearly aggregation results (2015-2021)
- Monthly aggregation results
- Daily comparison (sample period)
- Total spend per aggregation method
- Number of campaigns with data

---

### 3. LinkedIn - Monthly & Total Granularity

**Command:** `php artisan linkedin:test-historical-monthly`

**Options:**
- `--account-id=` : Specific ad account ID to test (optional)
- `--start-date=` : Start date (default: 2015-01-01)
- `--end-date=` : End date (default: today)

**Methods Tested:**

1. **MONTHLY Granularity**
   - Changes `timeGranularity=DAILY` → `timeGranularity=MONTHLY`
   - **Hypothesis:** LinkedIn may retain monthly archives longer than daily
   - **Likelihood:** 50%

2. **TOTAL Granularity (lifetime)**
   - Uses `timeGranularity=TOTAL` for lifetime aggregate
   - **Hypothesis:** May return lifetime totals even if daily/monthly unavailable
   - **Likelihood:** 40%

3. **DAILY Granularity (comparison)**
   - Current method for baseline

**Example Usage:**
```bash
# Test all LinkedIn accounts
php artisan linkedin:test-historical-monthly --start-date=2015-01-01 --end-date=2022-12-31

# Test specific account
php artisan linkedin:test-historical-monthly --account-id=131 --start-date=2015-01-01
```

**Expected Output:**
- Results for each of 3 granularity levels
- Comparison across methods
- Date ranges available
- Spend/impressions/clicks/conversions

---

### 4. Snapchat - Account-Level & Lifetime Stats

**Command:** `php artisan snapchat:test-historical-lifetime`

**Options:**
- `--account-id=` : Specific ad account ID to test (optional)
- `--start-date=` : Start date (default: 2015-01-01)
- `--end-date=` : End date (default: today)

**Methods Tested:**

1. **Account-level LIFETIME granularity**
   - Uses `/adaccounts/{id}/stats` with `granularity=LIFETIME`
   - **Hypothesis:** Account-level may have better historical retention than campaign-level
   - **Likelihood:** 45%

2. **Account-level TOTAL granularity**
   - Uses `/adaccounts/{id}/stats` with `granularity=TOTAL` (current method)
   - Baseline comparison

3. **Account-level DAILY breakdown**
   - Uses `granularity=DAY` for daily time series
   - Tests if daily data available at account level vs campaign level

4. **Campaign-level stats (comparison)**
   - Current campaign-by-campaign method
   - Baseline for comparison

**Example Usage:**
```bash
# Test all Snapchat accounts
php artisan snapchat:test-historical-lifetime --start-date=2015-01-01 --end-date=2024-12-31

# Test specific account
php artisan snapchat:test-historical-lifetime --account-id=145 --start-date=2023-01-01
```

**Expected Output:**
- Account-level vs campaign-level comparison
- Different granularity results
- Spend/impressions/swipes/video metrics

---

## Running All Tests

### Prerequisites

1. **Ensure active integrations with valid access tokens:**
   ```bash
   # Check integration status
   php artisan tinker
   >>> Integration::where('status', 'active')->pluck('platform', 'id')
   ```

2. **Refresh tokens if needed** via the integrations UI at:
   - https://rb-benchmarks.redbananas.com/integrations

3. **Verify Mancini accounts are in database:**
   - Facebook: Mancini's Sleepworld (act_2371774123112717)
   - Google Ads: Mancini (819-554-9637)

### Test Execution Plan

**Day 1: Google Ads Testing (HIGHEST PRIORITY)**
```bash
# Test Mancini account - known to have $9.1M missing (2015-2021)
php artisan google-ads:test-historical-monthly --customer-id=819-554-9637 --start-year=2015 --end-year=2020

# If successful, test all Google Ads accounts
php artisan google-ads:test-historical-monthly --start-year=2015 --end-year=2021
```

**Day 2: Facebook Testing**
```bash
# Test Mancini account - known to have $10.6M missing (2015-2022)
php artisan facebook:test-historical-monthly --start-date=2015-01-01 --end-date=2022-10-31

# If successful, test all Facebook accounts
php artisan facebook:test-historical-monthly --start-date=2015-01-01 --end-date=2022-10-31
```

**Day 3: LinkedIn & Snapchat Testing**
```bash
# LinkedIn
php artisan linkedin:test-historical-monthly --start-date=2015-01-01 --end-date=2022-12-31

# Snapchat
php artisan snapchat:test-historical-lifetime --start-date=2023-01-01 --end-date=2024-12-31
```

---

## Interpreting Results

### Success Indicators

**✓ SUCCESS:** Method returned historical data
- Spend > $0 for historical periods
- Date ranges extend into 2015-2021 period
- Record count > 0

**Partial Success:** Method returned some data
- Limited date range (e.g., only back to 2020 instead of 2015)
- Some campaigns have data, others don't

**✗ FAILURE:** Method returned 0 data
- 0 records returned
- Error messages
- Empty spend/impressions

### What To Do If Tests Succeed

If any method successfully retrieves historical data:

1. **Document the successful method:**
   - Which platform?
   - Which API parameter change?
   - Date range successfully retrieved?
   - Total spend recovered?

2. **Update the main service file:**
   - Modify corresponding service (Facebook/GoogleAds/LinkedIn/Snapchat)
   - Change API parameters to use successful method
   - Test on production data

3. **Re-run backfill commands:**
   ```bash
   # Example for Google Ads if yearly segments work
   php artisan google-ads:backfill-metrics --full-history
   ```

4. **Verify database:**
   ```sql
   SELECT platform,
          MIN(date) as earliest_date,
          MAX(date) as latest_date,
          SUM(spend) as total_spend,
          COUNT(*) as metrics_count
   FROM ad_metrics
   GROUP BY platform;
   ```

### What To Do If All Tests Fail

If all methods return 0 historical data:

1. **Confirm API retention limits are absolute:**
   - Facebook: 37-month hard limit confirmed
   - Google Ads: No data for inactive periods confirmed
   - LinkedIn: No historical data for inactive accounts confirmed
   - Snapchat: Limited retention confirmed

2. **Proceed with CSV import solution:**
   - CSV import tools already built and ready
   - Documentation already created
   - Web UI already integrated

3. **Update documentation to reflect exhaustive API testing:**
   - Note all methods tested
   - Confirm CSV import is the only viable solution

---

## Technical Details

### Current vs. Alternative API Parameters

| Platform | Current Method | Alternative Methods |
|----------|---------------|---------------------|
| **Facebook** | `time_increment: 1` (daily) | `time_increment: 'monthly'`<br>`date_preset: 'maximum'`<br>`date_preset: 'lifetime'`<br>No `time_increment` (aggregate) |
| **Google Ads** | `segments.date` | `segments.month`<br>`segments.year` |
| **LinkedIn** | `timeGranularity=DAILY` | `timeGranularity=MONTHLY`<br>`timeGranularity=TOTAL` |
| **Snapchat** | `/campaigns/{id}/stats` | `/adaccounts/{id}/stats`<br>`granularity=LIFETIME`<br>`granularity=DAY` |

### Why Alternative Methods Might Work

1. **Storage Optimization:**
   - APIs may delete daily data for storage but keep monthly/yearly summaries
   - Aggregated data takes less space

2. **Different Data Pathways:**
   - Account-level vs campaign-level queries may access different databases
   - Lifetime presets may trigger archive retrieval

3. **API Version Differences:**
   - Newer API endpoints may have different retention
   - Different query structures may bypass certain filters

### API Rate Limits

All test commands include:
- Timeout: 60-180 seconds
- Automatic error handling
- Logging for debugging
- Page size optimization (500 records)

---

## Next Steps

1. **Refresh integration tokens** via UI
2. **Run Google Ads test first** (highest likelihood of success)
3. **Document all results** in this file
4. **Update main services** if any method succeeds
5. **Proceed with CSV imports** if all methods fail

---

## Test Results

### Google Ads (Mancini 819-554-9637)

**Date Tested:** _Pending_

**Results:**
- [ ] Yearly Segments: _Pending_
- [ ] Monthly Segments: _Pending_
- [ ] Daily Segments (baseline): _Pending_

**Outcome:** _To be filled after testing_

### Facebook (Mancini act_2371774123112717)

**Date Tested:** _Pending_

**Results:**
- [ ] Monthly Increment: _Pending_
- [ ] Maximum Preset: _Pending_
- [ ] Lifetime Preset: _Pending_
- [ ] Lifetime Aggregate: _Pending_

**Outcome:** _To be filled after testing_

### LinkedIn

**Date Tested:** _Pending_

**Results:**
- [ ] MONTHLY Granularity: _Pending_
- [ ] TOTAL Granularity: _Pending_
- [ ] DAILY Granularity: _Pending_

**Outcome:** _To be filled after testing_

### Snapchat

**Date Tested:** _Pending_

**Results:**
- [ ] Account LIFETIME: _Pending_
- [ ] Account TOTAL: _Pending_
- [ ] Account DAILY: _Pending_
- [ ] Campaign-level: _Pending_

**Outcome:** _To be filled after testing_

---

## Conclusion

These alternative API tests represent an exhaustive exploration of every possible method to retrieve historical advertising data via APIs. If these tests fail, it confirms that **CSV import is the only viable solution** for recovering the missing $40-51M in historical data.

The CSV import infrastructure is already built and ready:
- ✓ Facebook CSV importer
- ✓ Google Ads CSV importer
- ✓ Web UI upload interface
- ✓ Export documentation
- ✓ Automated monthly backfills

**Estimated Total Recovery Time:** 2-4 hours of manual CSV exports (once tokens are refreshed and tests are run)
