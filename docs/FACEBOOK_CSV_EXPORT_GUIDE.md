# Facebook Historical Data Export Guide

This guide explains how to export historical advertising data from Facebook Ads Manager and import it into the RB Benchmarks platform.

## Why Export CSV Instead of Using the API?

The Facebook Marketing API has limitations:
- **37-month historical limit** - Cannot access data older than ~3 years
- **Deleted/archived campaigns** - API doesn't return metrics for campaigns that were deleted
- **Permission issues** - Some historical data may not be accessible via API

**CSV exports bypass these limitations** and allow you to recover data going back 10+ years.

---

## Step 1: Export Data from Facebook Ads Manager

### Method A: Campaign-Level Export (Recommended)

1. **Go to Facebook Ads Manager**
   - Navigate to: https://business.facebook.com/adsmanager/manage/campaigns
   - Select the ad account you want to export

2. **Set Date Range**
   - Click the date picker in the top right
   - Select "Lifetime" or custom range (e.g., "Jan 1, 2015 - Dec 31, 2022")
   - **TIP:** Export in yearly chunks for large datasets (e.g., 2015, 2016, 2017, etc.)

3. **Customize Columns** (Important!)
   - Click "Columns: Performance" dropdown
   - Select "Customize Columns"
   - Ensure these columns are selected:
     - ✅ Campaign Name
     - ✅ Campaign ID
     - ✅ Reporting Starts (Date)
     - ✅ Amount Spent
     - ✅ Impressions
     - ✅ Reach
     - ✅ Link Clicks (or Clicks)
     - ✅ Results (Conversions/Leads/Purchases)
     - ✅ Leads (if using lead forms)
   - Click "Apply"

4. **Breakdown by Day**
   - Click "Breakdown" dropdown
   - Select "By Time → Day"
   - This ensures daily metrics (not monthly aggregates)

5. **Export to CSV**
   - Click the "Export" button (📥 icon) in top right
   - Select "Export table data"
   - Choose format: "CSV (Excel .csv)"
   - Click "Export"
   - File will download (e.g., `campaigns_2015.csv`)

6. **Repeat for Each Year**
   - Change date range to next year
   - Export again
   - Continue until all historical data is exported

---

### Method B: Account-Level Export

For a quicker overview (less detailed):

1. Go to **Reports** tab in Ads Manager
2. Create "Custom Report"
3. Select date range (Lifetime or custom)
4. Choose metrics: Spend, Impressions, Clicks, Results
5. Add breakdowns: Campaign, Day
6. Export as CSV

---

## Step 2: Import CSV into RB Benchmarks

Once you have your CSV files exported, use the import command:

### Basic Import

```bash
php artisan facebook:import-csv /path/to/campaigns_2015.csv \
  --account-id=act_123456789
```

### With Options

```bash
# Dry run to preview (doesn't save to database)
php artisan facebook:import-csv /path/to/campaigns_2015.csv \
  --account-id=act_123456789 \
  --dry-run

# Specify integration and tenant
php artisan facebook:import-csv /path/to/campaigns_2015.csv \
  --account-id=act_123456789 \
  --integration-id=4 \
  --tenant-id=1
```

### Import Multiple Files

```bash
# Bash loop to import all CSV files in a directory
for file in /path/to/exports/*.csv; do
  echo "Importing $file..."
  php artisan facebook:import-csv "$file" --account-id=act_123456789
done
```

---

## Step 3: Verify Import

After importing, check the dashboard:

```bash
# Check total spend
php artisan tinker --execute="
\$total = \App\Models\AdMetric::sum('spend');
echo 'Total: SAR ' . number_format(\$total, 2) . ' (USD $' . number_format(\$total/3.75, 2) . ')' . PHP_EOL;
"

# Check metrics count
php artisan tinker --execute="
echo \App\Models\AdMetric::count() . ' total metrics' . PHP_EOL;
"
```

---

## Supported CSV Formats

The importer automatically detects these Facebook export formats:

### Format 1: Campaign Insights (Recommended)
```csv
Campaign name,Campaign ID,Reporting starts,Amount spent (SAR),Impressions,Reach,Link clicks,Results,Leads
```

### Format 2: Ad-Level Insights
```csv
Campaign name,Ad name,Ad ID,Reporting starts,Amount spent,Impressions,Clicks
```

### Format 3: Custom Reports
```csv
Campaign,Day,Spend,Impressions,Reach,Clicks,Conversions
```

**The importer is flexible** - it will work with any CSV that has:
- A date column (e.g., "Reporting starts", "Day", "Date")
- A campaign identifier (name or ID)
- Metrics columns (spend, impressions, etc.)

---

## Tips for Large Datasets

### For Mancini ($11M USD example):

1. **Export by year** instead of lifetime:
   - 2015.csv → Import → Verify
   - 2016.csv → Import → Verify
   - 2017.csv → Import → Verify
   - etc.

2. **Multiple ad accounts?**
   Export each account separately:
   ```bash
   php artisan facebook:import-csv mancini_2015.csv --account-id=act_2371774123112717
   php artisan facebook:import-csv mancini_2016.csv --account-id=act_2371774123112717
   ```

3. **Check for duplicates:**
   The importer uses `updateOrCreate` - it won't create duplicate metrics for the same campaign+date

---

## Common Issues & Solutions

### Issue 1: "Campaign name not found"
**Solution:** Ensure CSV has a column named "Campaign name", "Campaign_name", or "Campaign"

### Issue 2: "No metrics imported"
**Solution:** Check that CSV has a date column - try running with `--dry-run` first to see what's detected

### Issue 3: "Currency mismatch"
**Solution:** The importer assumes SAR. If your export is in USD/EUR, convert before import or adjust the importer

### Issue 4: CSV encoding errors
**Solution:** Save CSV as UTF-8 in Excel before importing

---

## Expected Results

For Mancini example:
- **Current data:** $3.27M USD (from API)
- **Missing data:** $7.73M USD (2015-2022 historical)
- **After CSV import:** $11M USD total ✅

For full platform:
- **Current:** $5.85M USD
- **After CSV imports:** ~$100M USD total 🎯

---

## Next Steps

1. Export your historical Facebook data (start with one year)
2. Run import with `--dry-run` to preview
3. Run actual import
4. Check dashboard to verify numbers
5. Repeat for remaining years/accounts

---

## Need Help?

If you encounter issues:
1. Check the Laravel logs: `tail -f storage/logs/laravel.log`
2. Run with `--dry-run` to see what would be imported
3. Check CSV format matches expected columns
4. Verify ad account ID is correct (starts with `act_`)

---

## Alternative: Google Ads & Snapchat

Similar CSV importers can be built for:
- **Google Ads** - Export from Google Ads Reports
- **Snapchat Ads** - Export from Snapchat Ads Manager
- **LinkedIn Ads** - Export from Campaign Manager

Let me know if you need importers for other platforms!
