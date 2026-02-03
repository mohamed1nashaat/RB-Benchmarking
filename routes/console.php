<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Google Sheets Automation Schedules
Schedule::command('google-sheets:auto-sync --dry-run')
    ->dailyAt('02:00')
    ->description('Daily dry-run check for missing Google Sheets');

Schedule::command('google-sheets:auto-sync')
    ->weeklyOn(1, '03:00') // Every Monday at 3 AM
    ->description('Weekly auto-creation of Google Sheets for new campaigns');

Schedule::command('google-sheets:sync-conversions --hours=1')
    ->hourly()
    ->description('Hourly sync of conversions to Google Sheets');

Schedule::command('google-sheets:sync-conversions --hours=24')
    ->dailyAt('04:00')
    ->description('Daily comprehensive conversion sync');

// Healing broken integrations
Artisan::command('google-sheets:heal-integrations', function () {
    $this->info('🔧 Starting integration healing process...');

    $autoSheetManager = app(\App\Services\AutoSheetManagerService::class);
    $results = $autoSheetManager->healBrokenIntegrations();

    $this->table(
        ['Metric', 'Count'],
        [
            ['Campaigns Checked', $results['campaigns_checked']],
            ['Integrations Healed', $results['integrations_healed']],
            ['Errors', $results['errors']]
        ]
    );

    if ($results['errors'] > 0) {
        $this->error("⚠️  {$results['errors']} errors occurred during healing");
        return 1;
    }

    $this->info("✅ Integration healing completed successfully");
    return 0;
})->purpose('Heal broken Google Sheets integrations');

Schedule::command('google-sheets:heal-integrations')
    ->twiceDaily(6, 18) // 6 AM and 6 PM
    ->description('Heal broken Google Sheets integrations twice daily');

// Google Ads Automation Schedules
Schedule::command('google-ads:sync --campaigns')
    ->dailyAt('01:00')
    ->description('Daily sync of Google Ads campaigns');

Schedule::command('google-ads:sync --metrics --days=2')
    ->everyTwoHours()
    ->description('Sync Google Ads metrics every 2 hours (last 2 days)');

Schedule::command('google-ads:sync --metrics --days=30')
    ->dailyAt('05:00')
    ->description('Daily comprehensive metrics sync (last 30 days)');

Schedule::command('google-ads:sync')
    ->weeklyOn(0, '02:00') // Every Sunday at 2 AM
    ->description('Weekly full sync of campaigns and metrics');

// Facebook Ads Automation Schedules
Schedule::command('facebook:sync-campaigns')
    ->dailyAt('00:30')
    ->description('Daily sync of Facebook campaigns');

Schedule::command('facebook:sync-metrics --days=2')
    ->everyTwoHours()
    ->description('Sync Facebook metrics every 2 hours (last 2 days)');

Schedule::command('facebook:sync-metrics --days=30')
    ->dailyAt('04:30')
    ->description('Daily comprehensive Facebook metrics sync (last 30 days)');

// LinkedIn Ads Automation Schedules
Schedule::command('sync:linkedin-campaigns')
    ->dailyAt('01:30')
    ->description('Daily sync of LinkedIn campaigns and metrics (yesterday)');

// TikTok Ads Automation Schedules
Schedule::command('tiktok:sync-campaigns')
    ->dailyAt('02:00')
    ->description('Daily sync of TikTok campaigns');

Schedule::command('tiktok:sync-metrics --days=2')
    ->everyTwoHours()
    ->description('Sync TikTok metrics every 2 hours (last 2 days)');

Schedule::command('tiktok:sync-metrics --days=30')
    ->dailyAt('05:45')
    ->description('Daily comprehensive TikTok metrics sync (last 30 days)');

// Snapchat Ads Automation Schedules
// Refresh Snapchat token every 45 minutes (token expires in 1 hour)
Schedule::command('snapchat:refresh-token')
    ->everyThirtyMinutes()
    ->description('Auto-refresh Snapchat OAuth token before expiry');

Schedule::command('snapchat:sync-campaigns')
    ->dailyAt('01:45')
    ->description('Daily sync of Snapchat campaigns');

Schedule::command('snapchat:sync-metrics --days=2')
    ->everyTwoHours()
    ->description('Sync recent Snapchat metrics every 2 hours');

Schedule::command('snapchat:sync-metrics --days=30')
    ->dailyAt('05:30')
    ->description('Daily comprehensive Snapchat metrics sync');

// Alert Monitoring Schedules
Schedule::command('alerts:check')
    ->everyFifteenMinutes()
    ->description('Check and evaluate all active alerts every 15 minutes');

// Scheduled Reports Generation
Schedule::command('reports:generate')
    ->everyFiveMinutes()
    ->description('Generate scheduled reports that are due');

// Sync Health Monitoring
Schedule::command('sync:check-health')
    ->hourly()
    ->description('Check health of all platform syncs and alert on issues');

// ==========================================
// Historical Data Backfill Schedules
// ==========================================
// These schedules prevent data loss by capturing historical data before it ages out

// Facebook: Backfill last 37 months (before 37-month API limit expires)
// Runs monthly to capture data that's approaching the 37-month cutoff
Schedule::command('facebook:backfill-metrics --start-date=' . now()->subMonths(37)->format('Y-m-d'))
    ->monthlyOn(1, '03:00')  // 1st of each month at 3 AM
    ->timezone('America/Los_Angeles')
    ->description('Monthly Facebook backfill (captures data before 37-month API limit)');

// Google Ads: Full backfill (captures any new historical data)
Schedule::command('google-ads:backfill-metrics --full-history')
    ->monthlyOn(5, '04:00')  // 5th of each month at 4 AM
    ->timezone('America/Los_Angeles')
    ->description('Monthly Google Ads full history backfill');

// LinkedIn: Backfill last 10 years
Schedule::command('linkedin:backfill-metrics --full-history')
    ->monthlyOn(10, '05:00')  // 10th of each month at 5 AM
    ->timezone('America/Los_Angeles')
    ->description('Monthly LinkedIn full history backfill');
