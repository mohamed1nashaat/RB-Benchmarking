# Historical Data Recovery Guide

## Overview

This guide will help you recover **$40-51M USD** in missing historical advertising data.

**Current Status:** $21.95M USD tracked
**After Recovery:** $62-73M USD total
**Recovery Potential:** $40-51M USD

---

## Summary of Missing Data

| Platform | Current Data | Missing Data | Recovery Method | Priority |
|----------|-------------|--------------|-----------------|----------|
| **Facebook** | $9.3M (Nov 2022+) | **$30-40M** | CSV Export | 🔥 CRITICAL |
| **Google Ads** | $2.9M (Mar 2021+) | **$9.4M** | CSV Export | 🔥 CRITICAL |
| **LinkedIn** | $0.5M (Aug 2022+) | **$1-2M** | API Backfill | ⚠️ MEDIUM |
| **Snapchat** | $0.6M (2025) | $0 | N/A (inactive) | ❌ NONE |

---

## PHASE 1: Facebook CSV Exports (CRITICAL - $30-40M Recovery)

### Why Facebook CSV Exports?

- ❌ Facebook API has **permanent 37-month limit** (cannot be bypassed)
- ✅ Facebook Ads Manager UI allows **lifetime exports** (back to 2015 or earlier)
- ✅ CSV importer already built: `/app/Console/Commands/ImportFacebookCsv.php`

### Priority Accounts to Export

Based on your database analysis, start with these high-value accounts:

**1. Mancini's Sleepworld - $10.6M missing**
- Account ID: `act_2371774123112717`
- Current Data: $353,747 (May 2023 - Nov 2025)
- Expected Total: $11M USD
- Missing Period: 2015-2022 + Jan-April 2023

**2. Other High-Value Accounts**
- Process the remaining 55 Facebook accounts
- Focus on accounts with recent activity (indicates historical spend likely exists)

---

## Step-by-Step: Facebook CSV Export Process

### Step 1: Access Facebook Ads Manager

1. Go to: https://business.facebook.com/adsmanager/
2. Select the ad account (e.g., Mancini)
3. Ensure you're viewing the **Campaigns** tab

### Step 2: Set Date Range to Historical Period

**For Mancini Example:**
1. Click the **date picker** in top right corner
2. Select **"Custom"**
3. Set dates: **January 1, 2015** to **October 31, 2022**
   - This covers all data BEFORE the 37-month API limit
4. Click **"Update"**

**Pro Tip:** For large datasets, export by year:
- 2015.csv, 2016.csv, 2017.csv, etc.
- Prevents Excel/CSV size limitations

### Step 3: Customize Columns (CRITICAL!)

Facebook won't include all necessary columns by default. Follow these steps carefully:

1. Click **"Columns: Performance"** dropdown in top right
2. Select **"Customize Columns"**
3. **Remove all default columns** (click X on each)
4. **Add these essential columns** (search and enable):

**Required Columns:**
- ✅ **Campaign name** - Campaign identifier
- ✅ **Campaign ID** - Unique campaign ID
- ✅ **Reporting starts** - Date column (CRITICAL)
- ✅ **Amount spent** - Total spend (in SAR or USD)
- ✅ **Impressions** - Ad impressions
- ✅ **Reach** - Unique users reached
- ✅ **Link clicks** - Clicks to destination
- ✅ **Results** - Primary objective completions
- ✅ **Leads** - Lead form submissions (if applicable)
- ✅ **Purchases** - E-commerce purchases (if applicable)

5. Click **"Apply"**

**Important:** The importer requires "Reporting starts" column for dates. Other date columns (like "Date start") may not work.

### Step 4: Set Daily Breakdown

This ensures you get daily granularity (not monthly aggregates):

1. Click **"Breakdown"** dropdown
2. Navigate to **"By Time"**
3. Select **"Day"**
4. The table will now show one row per campaign per day

### Step 5: Export to CSV

1. Click the **"Export"** button (📥 download icon) in top right
2. Select **"Export table data"**
3. Choose format: **"CSV (Excel .csv)"**
4. Click **"Export"**
5. File will download (e.g., `campaigns_2015_2022.csv`)

**File Naming Convention:**
- `mancini_2015_2022.csv`
- `mancini_2015.csv` (if exporting by year)
- `account_name_YYYY_YYYY.csv`

### Step 6: Import CSV to Database

Once you have the CSV file exported, import it:

**Test Import First (Dry Run):**
```bash
php artisan facebook:import-csv /path/to/mancini_2015_2022.csv \
  --account-id=act_2371774123112717 \
  --dry-run
```

This will show you:
- How many rows will be processed
- Detected CSV format
- Sample data preview
- Column mappings
- **NO data is written to database**

**Review the Output:**
- Check "Detected format" matches your export
- Verify column mappings look correct
- Ensure row count matches your expectations

**Actual Import:**
```bash
php artisan facebook:import-csv /path/to/mancini_2015_2022.csv \
  --account-id=act_2371774123112717
```

**Monitor Progress:**
The command shows real-time progress:
```
=== Facebook CSV Import ===
File: /path/to/mancini_2015_2022.csv
Mode: LIVE

Using Integration ID: 4 (Tenant: Demo Company)
Ad Account: act_2371774123112717

Starting CSV import...

Detected columns: campaign name, campaign id, reporting starts, amount spent, impressions...
Detected format: Facebook Campaign Insights

Processing rows: 7500 / 15000 [====================>---------] 50%

Import Summary:
┌───────────────────────┬─────────┐
│ Metric                │ Count   │
├───────────────────────┼─────────┤
│ Rows Processed        │  15,234 │
│ Campaigns Created     │      87 │
│ Metrics Created       │   8,456 │
│ Metrics Updated       │   6,778 │
│ Errors                │       0 │
└───────────────────────┴─────────┘
```

### Step 7: Verify Import Success

Check the database to verify data was imported:

```bash
php artisan tinker --execute="
// Get Mancini account
\$manciniAccount = \App\Models\AdAccount::where('external_account_id', 'act_2371774123112717')->first();

if (\$manciniAccount) {
    \$campaigns = \App\Models\AdCampaign::where('ad_account_id', \$manciniAccount->id)->get();
    \$metrics = \App\Models\AdMetric::whereIn('ad_campaign_id', \$campaigns->pluck('id'))->get();

    \$oldestMetric = \$metrics->min('date');
    \$newestMetric = \$metrics->max('date');
    \$totalSpend = \$metrics->sum('spend');

    echo '=== MANCINI ACCOUNT ===' . PHP_EOL;
    echo 'Total Campaigns: ' . \$campaigns->count() . PHP_EOL;
    echo 'Total Metrics: ' . number_format(\$metrics->count()) . PHP_EOL;
    echo 'Date Range: ' . \$oldestMetric . ' to ' . \$newestMetric . PHP_EOL;
    echo 'Total Spend: SAR ' . number_format(\$totalSpend, 2) . ' (USD $' . number_format(\$totalSpend/3.75, 2) . ')' . PHP_EOL;
}
"
```

**Expected Results for Mancini:**
- Before: $353,747 USD (May 2023 - Nov 2025)
- After: **~$11M USD** (2015 - Nov 2025)
- Recovery: **+$10.6M USD**

### Step 8: Repeat for All Facebook Accounts

Process remaining Facebook accounts in priority order:

1. List all Facebook accounts:
```bash
php artisan tinker --execute="
\$accounts = \App\Models\AdAccount::whereHas('integration', fn(\$q) => \$q->where('platform', 'facebook'))->get();
foreach (\$accounts as \$acc) {
    echo \$acc->account_name . ' (' . \$acc->external_account_id . ')' . PHP_EOL;
}
"
```

2. Export historical CSVs for top 10-20 accounts
3. Import each CSV using the command above
4. Verify each import

**Batch Import Script:**
```bash
# If you have multiple CSV files in a directory:
for file in /path/to/exports/facebook/*.csv; do
  echo "Importing $file..."
  php artisan facebook:import-csv "$file" \
    --account-id=act_XXXXXXXXX \
    --tenant-id=1
  echo "Completed $file"
  echo "---"
done
```

---

## PHASE 2: Google Ads CSV Exports (CRITICAL - $9.4M Recovery)

### Why Google Ads CSV Exports?

- ❌ Google Ads API returned 0 metrics for historical periods (accounts were inactive pre-2021)
- ✅ Google Ads Reports UI allows **all-time exports** back to account inception
- ✅ CSV importer built: `/app/Console/Commands/ImportGoogleAdsCsv.php`

### Priority Accounts to Export

Based on database analysis and user-provided "All Time" report:

**1. Mancini - $9.1M missing**
- Customer ID: `819-554-9637`
- Current Data: $2.9M USD (March 2021 - Nov 2025)
- Total Historical Spend: $12.04M USD (from Google Ads "All Time" report)
- Missing Period: 2015 - February 2021
- **Currency:** USD (IMPORTANT: Always verify currency code!)

**2. Other Google Ads Accounts - ~$300K missing**
- Process remaining Google Ads accounts after Mancini
- Focus on accounts with significant historical spend

### Quick Export Instructions

**Detailed guide:** `/docs/GOOGLE_ADS_CSV_EXPORT_GUIDE.md`

**Quick Steps:**
1. Go to: https://ads.google.com/
2. Select account (e.g., Mancini: 819-554-9637)
3. Reports → Predefined Reports → Basic → Campaign
4. Date Range: **All time** (or custom: 2015-01-01 to 2021-02-28 for Mancini)
5. **Segment by Day** (CRITICAL: Time → Day)
6. Columns: Day, Campaign, Campaign ID, Cost, Currency, Clicks, Impressions, Conversions
7. Download → CSV
8. Import:
   ```bash
   php artisan google-ads:import-csv /path/to/file.csv --customer-id=819-554-9637
   ```

**Expected Results for Mancini:**
- Before: $2.9M USD
- After: **$12M USD**
- Recovery: **+$9.1M USD**

**Web UI Import:**
- Go to: https://rb-benchmarks.redbananas.com/integrations
- Click "Import Historical CSV" on Google Ads card
- Upload CSV and enter customer ID

---

## PHASE 3: LinkedIn Backfill (MEDIUM - $1-2M Recovery)

LinkedIn API supports 10+ years of historical data. A backfill command needs to be created (similar to Google Ads).

**Status:** ⏳ To be developed

**Once Complete, Run:**
```bash
php artisan linkedin:backfill-metrics \
  --start-date=2015-01-01 \
  --full-history
```

---

## PHASE 3: Prevent Future Data Loss

### Set Up Monthly Backfill Jobs

Facebook's 37-month limit means data ages out every month. Capture it before it's lost:

**Add to `/routes/console.php`:**
```php
// Backfill Facebook data monthly (before it ages past 37 months)
$schedule->command('facebook:backfill-metrics --start-date=' . now()->subMonths(36)->format('Y-m-d'))
    ->monthly()
    ->at('03:00')
    ->timezone('America/Los_Angeles');

// Backfill other platforms
$schedule->command('google-ads:backfill-metrics')
    ->monthly()
    ->at('04:00');

$schedule->command('linkedin:backfill-metrics')
    ->monthly()
    ->at('05:00');
```

### Set Up Data Archiving

Export metrics to long-term storage monthly:

```bash
# Create archive export command (to be developed)
php artisan metrics:export-archive --output=/backups/metrics_$(date +%Y%m).csv
```

Store archives in:
- S3 / Cloud Storage
- BigQuery / Data Warehouse
- Local backups (at minimum)

---

## Troubleshooting

### Issue 1: "Campaign name not found" Error

**Cause:** CSV doesn't have required column

**Solution:**
- Ensure "Campaign name" is in customized columns
- Check column header spelling (Facebook sometimes uses "Campaign_name" or "campaign_name")
- Try re-exporting with correct columns

### Issue 2: No Data in CSV Export

**Cause:** Date range has no activity

**Solution:**
- Check if account was actually active during that period
- Try exporting more recent dates first to test
- Account may genuinely have no spend during those years

### Issue 3: "Import failed: Invalid date format"

**Cause:** Date column format not recognized

**Solution:**
- Ensure "Reporting starts" column is included (not "Date start")
- Check CSV uses standard date format (YYYY-MM-DD or MM/DD/YYYY)
- Open CSV in text editor to verify format

### Issue 4: Import Shows 0 Campaigns Created

**Cause:** Campaigns already exist in database

**Solution:**
- This is normal! The importer uses `updateOrCreate`
- Check "Metrics Created" count instead
- If "Metrics Created" = 0, data may already be imported

### Issue 5: CSV File Too Large

**Cause:** Exporting 5+ years at once creates huge files

**Solution:**
- Export by year instead (2015.csv, 2016.csv, etc.)
- Facebook exports have size limits (~1M rows)
- Yearly exports are more manageable

---

## Expected Timeline

### Week 1: Critical CSV Exports (Facebook + Google Ads)
- **Day 1:** Mancini Facebook export + import (Expected: +$10.6M)
- **Day 2:** Mancini Google Ads export + import (Expected: +$9.1M)
- **Day 3-4:** Top 10 Facebook accounts export + import (Expected: +$15-20M)
- **Day 5:** Remaining Google Ads accounts (Expected: +$300K)

**Week 1 Total Recovery:** $35-40M USD

### Week 2: Remaining Platforms + Verification
- **Day 1-2:** Remaining Facebook accounts (Expected: +$5-10M)
- **Day 3:** LinkedIn backfill development + execution (Expected: +$1-2M)
- **Day 4:** Verify all imports and data integrity
- **Day 5:** Set up monthly schedules

**Week 2 Total Recovery:** +$6-12M USD

### Final Total
- **Current:** $21.95M USD
- **After Recovery:** **$62-73M USD**
- **Total Recovered:** **$40-51M USD**

---

## Quick Reference Commands

### Check Total Spend
```bash
php artisan tinker --execute="
echo 'Total Spend: USD $' . number_format(\App\Models\AdMetric::sum('spend')/3.75, 2);
"
```

### Check Platform Breakdown
```bash
php artisan tinker --execute="
\$platforms = ['facebook', 'google', 'linkedin', 'snapchat'];
foreach (\$platforms as \$platform) {
    \$spend = \App\Models\AdMetric::whereHas('adCampaign.adAccount.integration', function(\$q) use (\$platform) {
        \$q->where('platform', \$platform);
    })->sum('spend');
    echo strtoupper(\$platform) . ': USD $' . number_format(\$spend/3.75, 2) . PHP_EOL;
}
"
```

### List Facebook Accounts
```bash
php artisan tinker --execute="
\App\Models\AdAccount::whereHas('integration', fn(\$q) => \$q->where('platform', 'facebook'))
    ->get()
    ->each(fn(\$a) => print(\$a->account_name . ' (' . \$a->external_account_id . ')' . PHP_EOL));
"
```

---

## Support

If you encounter issues:
1. Check the Laravel logs: `tail -f storage/logs/laravel.log`
2. Run imports with `--dry-run` first
3. Verify CSV format matches expected columns
4. Ensure ad account ID is correct (starts with `act_`)

For the Facebook CSV export guide specifically: See `/docs/FACEBOOK_CSV_EXPORT_GUIDE.md`

---

**Last Updated:** November 24, 2025
**Total Expected Recovery:** $40-51M USD
**Critical Path:** Facebook CSV Exports ($30-40M) + Google Ads CSV Exports ($9.4M)