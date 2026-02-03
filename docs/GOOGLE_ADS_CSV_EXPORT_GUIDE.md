# Google Ads CSV Export Guide

## Overview

This guide explains how to export **campaign-level historical data** from Google Ads to recover missing advertising metrics.

**Recovery Potential:** $9.4M USD in missing historical data

**Key Accounts:**
- Mancini: Missing $9.1M USD (2015-2021)
- Other accounts: ~$300K USD

---

## IMPORTANT: Campaign Performance vs Account Summary

### ❌ WRONG: Account Summary Report

Do NOT export the account summary from the main dashboard. This format shows:
- One row per account
- Total aggregated spend
- **NO daily breakdown**
- **NO campaign details**

Example of WRONG format:
```
Account ID, Account, Cost, Currency, Clicks, Impressions
123-456-7890, Mancini, 12043394.60, USD, 5000000, 100000000
```

This format **CANNOT be imported** because it lacks:
- Daily date breakdown
- Campaign names and IDs
- Per-campaign metrics

### ✅ CORRECT: Campaign Performance Report

You MUST export campaign performance reports with daily breakdown. This format shows:
- One row per campaign per day
- Daily granularity
- Campaign names and IDs
- All performance metrics

Example of CORRECT format:
```
Day, Campaign, Campaign ID, Cost, Currency, Clicks, Impressions, Conversions
2021-03-15, Brand Campaign, 123456789, 1250.50, USD, 500, 25000, 12
2021-03-16, Brand Campaign, 123456789, 1180.25, USD, 475, 23500, 10
```

This format can be imported successfully.

---

## Step-by-Step: Export Campaign Performance Reports

### Method 1: Using Google Ads Reports (Recommended)

#### Step 1: Access Google Ads

1. Go to: https://ads.google.com/
2. Select the account you want to export (e.g., Mancini: 819-554-9637)
3. If you manage multiple accounts, ensure you're viewing the specific account (not MCC level)

#### Step 2: Navigate to Reports

1. Click **"Reports"** icon in the top menu (📊 chart icon)
2. Select **"Predefined reports (Dimensions)"**
3. Navigate to **"Basic" → "Campaign"**

This opens the Campaign Performance report.

#### Step 3: Set Date Range

For historical data recovery:

1. Click the **date picker** in top right
2. Select **"All time"** from the dropdown
   - OR set custom dates: **January 1, 2015** to **today**
3. Click **"Apply"**

**Pro Tip for Large Datasets:**
- If "All time" fails or times out, export by year:
  - 2015.csv (Jan 1, 2015 - Dec 31, 2015)
  - 2016.csv (Jan 1, 2016 - Dec 31, 2016)
  - Continue through current year
- This prevents timeouts and makes files more manageable

#### Step 4: Add Segmentation by Day (CRITICAL!)

This ensures daily granularity:

1. Click **"Segment"** dropdown near the top
2. Navigate to **"Time" → "Day"**
3. The report will now show **one row per campaign per day**

**IMPORTANT:** Without this segmentation, you'll get monthly or total aggregates, which reduces data granularity.

#### Step 5: Customize Columns

Ensure the report includes all necessary columns:

1. Click **"Columns"** icon (or "Modify columns")
2. **Remove** unnecessary columns
3. **Add** these essential columns:

**Required Columns:**
- ✅ **Day** - Date column (segment)
- ✅ **Campaign** - Campaign name
- ✅ **Campaign ID** - Unique campaign identifier
- ✅ **Cost** - Total spend
- ✅ **Currency** - Currency code (USD, SAR, TRY, etc.)
- ✅ **Clicks** - Total clicks
- ✅ **Impressions** - Ad impressions
- ✅ **Conversions** - Total conversions

**Optional (Recommended) Columns:**
- Conversion value
- Cost per conversion
- Click-through rate (CTR)
- Average CPC

4. Click **"Apply"**

#### Step 6: Download CSV

1. Click the **download icon** (⬇️) in top right
2. Select format: **"CSV"** or **"CSV for Excel"**
3. Click **"Download"**
4. File will download as `Campaign_Report_YYYYMMDD.csv`

**File Naming Convention:**
Rename your file for clarity:
- `mancini_819_554_9637_alltime.csv`
- `mancini_2015_2020.csv` (if exporting by year range)
- `accountname_customerid_daterange.csv`

---

### Method 2: Using Google Ads Editor (Alternative)

If the web interface times out or has issues:

1. Download **Google Ads Editor**: https://ads.google.com/home/tools/ads-editor/
2. Sign in and download account data
3. Go to **"Tools" → "Statistics"**
4. Select **"Campaign"** level statistics
5. Set date range and export to CSV

---

### Method 3: Using Google Ads API (Advanced)

If you're comfortable with API calls, you can use the Google Ads API to export data:

```bash
# Example query (requires authentication setup)
SELECT
  campaign.id,
  campaign.name,
  segments.date,
  metrics.cost_micros,
  metrics.impressions,
  metrics.clicks,
  metrics.conversions
FROM campaign
WHERE segments.date BETWEEN '2015-01-01' AND '2025-11-24'
```

This is the same method our backfill command uses, but it only returns data for periods where the account was active.

---

## Handling Multiple Currencies

Google Ads accounts can have different currencies:
- **Mancini:** USD
- **Turkish accounts:** TRY (Turkish Lira)
- **Saudi accounts:** SAR
- **European accounts:** EUR

**The CSV MUST include a "Currency" or "Currency code" column.**

Our importer automatically detects and converts all currencies to SAR for database consistency:
- USD → SAR (rate: 3.75)
- TRY → SAR (rate: 0.12)
- EUR → SAR (rate: 4.10)
- SAR → SAR (rate: 1.0)

---

## Priority Accounts to Export

Based on missing data analysis:

### 1. Mancini (Customer ID: 819-554-9637) - CRITICAL
- **Account URL:** https://ads.google.com/aw/campaigns?ocid=819554963
- **Current Data:** $2.9M USD (March 2021 - Nov 2025)
- **Total Historical Spend:** $12.04M USD (shown in "All Time" report)
- **Missing:** **$9.1M USD** (2015 - February 2021)
- **Priority:** 🔥 HIGHEST

**Export Instructions:**
1. Date Range: **January 1, 2015 - February 28, 2021**
2. Expected Rows: ~50,000 - 100,000 (depending on campaigns and daily spend)
3. Expected File Size: 5-15 MB

### 2. Other Google Ads Accounts (~$300K missing)

After Mancini, process remaining accounts. List all accounts:

```bash
php artisan tinker --execute="
\$accounts = \App\Models\AdAccount::whereHas('integration', fn(\$q) => \$q->where('platform', 'google'))
    ->get();
foreach (\$accounts as \$acc) {
    echo \$acc->account_name . ' (' . \$acc->external_account_id . ')' . PHP_EOL;
}
"
```

Export historical data for accounts with significant missing spend.

---

## Importing CSV Files

Once you have exported the CSV file:

### Test Import (Dry Run) - Recommended First Step

```bash
php artisan google-ads:import-csv /path/to/mancini_2015_2021.csv \
  --customer-id=819-554-9637 \
  --dry-run
```

**What Dry Run Shows:**
- Total rows detected
- CSV format detected
- Column mappings
- Sample data preview
- **NO data is written to database**

**Review the output carefully:**
- Check "Detected format" is correct
- Verify column names are recognized
- Ensure currency is detected
- Check row count matches expectations

### Actual Import

If dry run looks good, proceed with actual import:

```bash
php artisan google-ads:import-csv /path/to/mancini_2015_2021.csv \
  --customer-id=819-554-9637
```

**Monitor Progress:**
The command shows real-time progress:
```
=== Google Ads CSV Import ===
File: /path/to/mancini_2015_2021.csv
Mode: LIVE

Using Integration ID: 2 (Tenant: Demo Company)
Ad Account: 819-554-9637

Starting CSV import...

Detected columns: day, campaign, campaign id, cost, currency, clicks, impressions...
Detected format: campaign_performance

Processing rows: 25,000 / 50,000 [===================>----------] 50%

Import Summary:
┌───────────────────────┬─────────┐
│ Metric                │ Count   │
├───────────────────────┼─────────┤
│ Rows Processed        │  50,234 │
│ Campaigns Created     │      42 │
│ Metrics Created       │  48,156 │
│ Metrics Updated       │   2,078 │
│ Errors                │       0 │
└───────────────────────┴─────────┘

Import completed successfully!
```

### Verify Import Success

Check the database to confirm data was imported:

```bash
php artisan tinker --execute="
\$manciniAccount = \App\Models\AdAccount::where('external_account_id', 'LIKE', '%819554963%')->first();

if (\$manciniAccount) {
    \$campaigns = \App\Models\AdCampaign::where('ad_account_id', \$manciniAccount->id)->get();
    \$metrics = \App\Models\AdMetric::whereIn('ad_campaign_id', \$campaigns->pluck('id'))->get();

    \$oldestMetric = \$metrics->min('date');
    \$newestMetric = \$metrics->max('date');
    \$totalSpend = \$metrics->sum('spend');

    echo '=== MANCINI GOOGLE ADS ACCOUNT ===' . PHP_EOL;
    echo 'Total Campaigns: ' . \$campaigns->count() . PHP_EOL;
    echo 'Total Metrics: ' . number_format(\$metrics->count()) . PHP_EOL;
    echo 'Date Range: ' . \$oldestMetric . ' to ' . \$newestMetric . PHP_EOL;
    echo 'Total Spend (SAR): ' . number_format(\$totalSpend, 2) . PHP_EOL;
    echo 'Total Spend (USD): \$' . number_format(\$totalSpend/3.75, 2) . PHP_EOL;
}
"
```

**Expected Results for Mancini:**
- **Before:** $2.9M USD (March 2021 - Nov 2025)
- **After:** **~$12M USD** (2015 - Nov 2025)
- **Recovery:** **+$9.1M USD**

---

## Web UI Import (Alternative to Command Line)

You can also import CSVs via the web interface:

1. Go to: https://rb-benchmarks.redbananas.com/integrations
2. Find the **Google Ads** integration card
3. Click **"Import Historical CSV"** button
4. Upload your CSV file
5. Enter the customer ID (e.g., 819-554-9637)
6. Click **"Preview File"** to validate format
7. Review preview and click **"Import Now"**

The web UI provides the same functionality as the command line but with a visual interface.

---

## Troubleshooting

### Issue 1: "No date column detected"

**Cause:** CSV doesn't have a date column

**Solution:**
- Ensure you added **"Day"** segmentation in Step 4
- Re-export with proper segmentation
- Check column headers include "Day" or "Date"

### Issue 2: "Currency not detected - defaulting to USD"

**Cause:** CSV doesn't have currency column

**Solution:**
- Add "Currency" column when customizing columns in Step 5
- If all spend is in one currency, you can manually specify it in the importer (future enhancement)
- Check logs to verify conversion rates are correct

### Issue 3: Export Times Out or Fails

**Cause:** "All time" date range is too large (10+ years of data)

**Solution:**
- Export by year instead:
  - 2015.csv, 2016.csv, 2017.csv, etc.
- Or export by 2-3 year ranges
- Import each file separately

### Issue 4: "Campaign not found" Errors

**Cause:** Campaign names or IDs don't match existing data

**Solution:**
- This is normal for historical campaigns that no longer exist
- The importer will auto-create campaigns from CSV data
- Check "Campaigns Created" count in summary

### Issue 5: Import Shows 0 Metrics Created

**Cause:** Data already exists in database

**Solution:**
- Check "Metrics Updated" count instead
- If both Created and Updated = 0, data may already be imported
- Verify by checking total spend in database

### Issue 6: Wrong Currency Conversion

**Cause:** Currency detection failed or rate is incorrect

**Solution:**
- Check Laravel logs for currency conversion messages:
  ```bash
  tail -f storage/logs/laravel.log | grep "Currency conversion"
  ```
- Verify "Currency" column in CSV has correct codes (USD, SAR, TRY)
- Update conversion rates in `/app/Console/Commands/ImportGoogleAdsCsv.php` if needed

---

## Expected Recovery Timeline

### Mancini Account (Priority 1)
- **Step 1:** Export CSV from Google Ads (15-30 mins)
  - Date Range: 2015-01-01 to 2021-02-28
  - Expected file size: 5-15 MB
- **Step 2:** Test import with --dry-run (2 mins)
- **Step 3:** Actual import (5-10 mins)
- **Step 4:** Verify data (2 mins)
- **Total Time:** ~45-60 minutes
- **Recovery:** **+$9.1M USD**

### Remaining Accounts (Priority 2)
- Process 5-10 other Google Ads accounts
- Estimated: 2-4 hours total
- **Recovery:** **+$300K USD**

### Total Google Ads Recovery
- **Current:** $2.9M USD
- **After Recovery:** **$12.5M USD**
- **Total Recovered:** **$9.4M USD**

---

## Important Notes

1. **Always check the Currency column** - This was critical in identifying the missing $9.1M for Mancini
2. **Use daily segmentation** - Monthly aggregates lose granularity
3. **Test with dry-run first** - Validate format before importing
4. **Export by year for large datasets** - Prevents timeouts
5. **Verify after import** - Always check total spend matches expectations

---

## Quick Reference Commands

### Check Current Google Ads Spend
```bash
php artisan tinker --execute="
echo 'Google Ads Total: USD \$' . number_format(\App\Models\AdMetric::whereHas('adCampaign.adAccount.integration', function(\$q) {
    \$q->where('platform', 'google');
})->sum('spend')/3.75, 2);
"
```

### List All Google Ads Accounts
```bash
php artisan tinker --execute="
\App\Models\AdAccount::whereHas('integration', fn(\$q) => \$q->where('platform', 'google'))
    ->get()
    ->each(fn(\$a) => print(\$a->account_name . ' (' . \$a->external_account_id . ')' . PHP_EOL));
"
```

### Import CSV (Command Line)
```bash
# Dry run
php artisan google-ads:import-csv /path/to/file.csv --customer-id=819-554-9637 --dry-run

# Actual import
php artisan google-ads:import-csv /path/to/file.csv --customer-id=819-554-9637
```

---

## Support

If you encounter issues:
1. Check Laravel logs: `tail -f storage/logs/laravel.log`
2. Run imports with `--dry-run` first
3. Verify CSV format matches expected structure
4. Ensure customer ID format is correct (123-456-7890 or 1234567890)
5. Check currency codes are correct (USD, SAR, TRY, EUR)

For more information:
- Overall recovery guide: `/docs/HISTORICAL_DATA_RECOVERY_GUIDE.md`
- Facebook CSV guide: `/docs/FACEBOOK_CSV_EXPORT_GUIDE.md`

---

**Last Updated:** November 24, 2025
**Recovery Potential:** $9.4M USD
**Critical Priority:** Mancini Account ($9.1M)
