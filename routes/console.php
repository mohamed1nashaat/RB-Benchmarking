<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Helper: log scheduled task failures
$onFailure = function (string $command) {
    return function () use ($command) {
        Log::critical("Scheduled task failed: {$command}", [
            'command' => $command,
            'timestamp' => now()->toISOString(),
        ]);
    };
};

// Google Sheets Automation Schedules
Schedule::command('google-sheets:auto-sync --dry-run')
    ->dailyAt('02:00')
    ->onFailure($onFailure('google-sheets:auto-sync --dry-run'))
    ->description('Daily dry-run check for missing Google Sheets');

Schedule::command('google-sheets:auto-sync')
    ->weeklyOn(1, '03:00') // Every Monday at 3 AM
    ->onFailure($onFailure('google-sheets:auto-sync'))
    ->description('Weekly auto-creation of Google Sheets for new campaigns');

Schedule::command('google-sheets:sync-conversions --hours=1')
    ->hourly()
    ->onFailure($onFailure('google-sheets:sync-conversions --hours=1'))
    ->description('Hourly sync of conversions to Google Sheets');

Schedule::command('google-sheets:sync-conversions --hours=24')
    ->dailyAt('04:00')
    ->onFailure($onFailure('google-sheets:sync-conversions --hours=24'))
    ->description('Daily comprehensive conversion sync');

// Healing broken integrations
Artisan::command('google-sheets:heal-integrations', function () {
    $this->info('Starting integration healing process...');

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
        $this->error("{$results['errors']} errors occurred during healing");
        return 1;
    }

    $this->info("Integration healing completed successfully");
    return 0;
})->purpose('Heal broken Google Sheets integrations');

Schedule::command('google-sheets:heal-integrations')
    ->twiceDaily(6, 18) // 6 AM and 6 PM
    ->onFailure($onFailure('google-sheets:heal-integrations'))
    ->description('Heal broken Google Sheets integrations twice daily');

// ==========================================
// Unified Daily Sync — All Platforms
// ==========================================
// Replaces individual daily schedules (accounts, campaigns, deep metrics, creatives)
// with a single orchestrated command. Each sub-command is isolated:
// one platform failure does not block the rest.
Schedule::command('sync:daily-all')
    ->dailyAt('01:00')
    ->onFailure($onFailure('sync:daily-all'))
    ->description('Daily unified sync: accounts, campaigns, deep metrics (30d), creatives for all platforms');

// Google Ads weekly full sync (kept separate — heavier than daily)
Schedule::command('google-ads:sync')
    ->weeklyOn(0, '02:00') // Every Sunday at 2 AM
    ->onFailure($onFailure('google-ads:sync'))
    ->description('Weekly full sync of Google Ads campaigns and metrics');

// ==========================================
// Frequent Metrics Sync — Every 2 Hours
// Catches API delays and corrects recent data (last 2–3 days)
// ==========================================
Schedule::command('google-ads:sync --metrics --days=2')
    ->everyTwoHours()
    ->onFailure($onFailure('google-ads:sync --metrics --days=2'))
    ->description('Sync Google Ads metrics every 2 hours (last 2 days)');

Schedule::command('facebook:sync-metrics --days=3')
    ->everyTwoHours()
    ->onFailure($onFailure('facebook:sync-metrics --days=3'))
    ->description('Sync Facebook metrics every 2 hours (last 3 days)');

Schedule::command('tiktok:sync-metrics --days=3')
    ->everyTwoHours()
    ->onFailure($onFailure('tiktok:sync-metrics --days=3'))
    ->description('Sync TikTok metrics every 2 hours (last 3 days)');

Schedule::command('snapchat:sync-metrics --days=3')
    ->everyTwoHours()
    ->onFailure($onFailure('snapchat:sync-metrics --days=3'))
    ->description('Sync Snapchat metrics every 2 hours (last 3 days)');

// LinkedIn — previously missing frequent metrics
Schedule::command('sync:linkedin-campaigns --days=3')
    ->everyTwoHours()
    ->onFailure($onFailure('sync:linkedin-campaigns --days=3'))
    ->description('Sync LinkedIn metrics every 2 hours (last 3 days)');

// Twitter/X — previously missing frequent metrics
Schedule::command('sync:twitter-campaigns --days=3')
    ->everyTwoHours()
    ->onFailure($onFailure('sync:twitter-campaigns --days=3'))
    ->description('Sync X/Twitter metrics every 2 hours (last 3 days)');

// ==========================================
// Platform-Specific Token Refresh
// ==========================================
Schedule::command('snapchat:refresh-token')
    ->everyThirtyMinutes()
    ->onFailure($onFailure('snapchat:refresh-token'))
    ->description('Auto-refresh Snapchat OAuth token before expiry');

// Queue worker — process jobs dispatched from UI (manual syncs)
Schedule::command('queue:work --stop-when-empty --max-time=300')
    ->everyMinute()
    ->withoutOverlapping()
    ->onFailure($onFailure('queue:work'))
    ->description('Process queued sync jobs from UI');

// Alert Monitoring Schedules
Schedule::command('alerts:check')
    ->everyFifteenMinutes()
    ->onFailure($onFailure('alerts:check'))
    ->description('Check and evaluate all active alerts every 15 minutes');

// Scheduled Reports Generation
Schedule::command('reports:generate')
    ->everyFiveMinutes()
    ->onFailure($onFailure('reports:generate'))
    ->description('Generate scheduled reports that are due');

// Sync Health Monitoring
Schedule::command('sync:check-health')
    ->hourly()
    ->onFailure($onFailure('sync:check-health'))
    ->description('Check health of all platform syncs and alert on issues');

// ==========================================
// Historical Data Backfill Schedules
// ==========================================
// These schedules prevent data loss by capturing historical data before it ages out

// Facebook: Backfill full available history (37-month API limit)
// Runs monthly to ensure complete data within the API window
Schedule::command('facebook:backfill-metrics --full-history')
    ->monthlyOn(1, '03:00')  // 1st of each month at 3 AM
    ->timezone('America/Los_Angeles')
    ->onFailure($onFailure('facebook:backfill-metrics'))
    ->description('Monthly Facebook full history backfill (37-month API limit)');

// Google Ads: Full backfill (captures any new historical data)
Schedule::command('google-ads:backfill-metrics --full-history')
    ->monthlyOn(5, '04:00')  // 5th of each month at 4 AM
    ->timezone('America/Los_Angeles')
    ->onFailure($onFailure('google-ads:backfill-metrics'))
    ->description('Monthly Google Ads full history backfill');

// LinkedIn: Backfill last 10 years
Schedule::command('linkedin:backfill-metrics --full-history')
    ->monthlyOn(10, '05:00')  // 10th of each month at 5 AM
    ->timezone('America/Los_Angeles')
    ->onFailure($onFailure('linkedin:backfill-metrics'))
    ->description('Monthly LinkedIn full history backfill');

// TikTok: Backfill full available history
Schedule::command('tiktok:backfill-metrics --full-history')
    ->monthlyOn(15, '04:00')  // 15th of each month at 4 AM
    ->timezone('America/Los_Angeles')
    ->onFailure($onFailure('tiktok:backfill-metrics'))
    ->description('Monthly TikTok full history backfill');

// Snapchat: Backfill full available history
Schedule::command('snapchat:backfill-metrics --full-history')
    ->monthlyOn(20, '04:00')  // 20th of each month at 4 AM
    ->timezone('America/Los_Angeles')
    ->onFailure($onFailure('snapchat:backfill-metrics'))
    ->description('Monthly Snapchat full history backfill');
