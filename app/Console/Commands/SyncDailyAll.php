<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SyncDailyAll extends Command
{
    protected $signature = 'sync:daily-all
                            {--platform= : Sync only a specific platform (google-ads, facebook, linkedin, tiktok, snapchat, twitter)}
                            {--skip-creatives : Skip creative syncs}';

    protected $description = 'Run all daily syncs for all advertising platforms sequentially';

    public function handle(): int
    {
        $platformFilter = $this->option('platform');
        $skipCreatives = $this->option('skip-creatives');
        $startTime = now();
        $results = [];

        $syncs = [
            'google-ads' => [
                ['cmd' => 'google-ads:sync-accounts', 'opts' => [], 'desc' => 'Google Ads account discovery'],
                ['cmd' => 'google-ads:sync', 'opts' => ['--campaigns' => true], 'desc' => 'Google Ads campaigns'],
                ['cmd' => 'google-ads:sync', 'opts' => ['--metrics' => true, '--days' => 30], 'desc' => 'Google Ads deep metrics (30d)'],
                ['cmd' => 'google-ads:sync-creatives', 'opts' => [], 'desc' => 'Google Ads creatives'],
            ],
            'facebook' => [
                ['cmd' => 'facebook:sync-campaigns', 'opts' => [], 'desc' => 'Facebook campaigns'],
                ['cmd' => 'facebook:sync-metrics', 'opts' => ['--days' => 30], 'desc' => 'Facebook deep metrics (30d)'],
                ['cmd' => 'facebook:sync-creatives', 'opts' => [], 'desc' => 'Facebook creatives'],
            ],
            'linkedin' => [
                ['cmd' => 'sync:linkedin-campaigns', 'opts' => ['--days' => 30], 'desc' => 'LinkedIn campaigns + deep metrics (30d)'],
                ['cmd' => 'linkedin:sync-creatives', 'opts' => [], 'desc' => 'LinkedIn creatives'],
            ],
            'tiktok' => [
                ['cmd' => 'tiktok:sync-campaigns', 'opts' => [], 'desc' => 'TikTok campaigns'],
                ['cmd' => 'tiktok:sync-metrics', 'opts' => ['--days' => 30], 'desc' => 'TikTok deep metrics (30d)'],
                ['cmd' => 'tiktok:sync-creatives', 'opts' => [], 'desc' => 'TikTok creatives'],
            ],
            'snapchat' => [
                ['cmd' => 'snapchat:sync-campaigns', 'opts' => [], 'desc' => 'Snapchat campaigns'],
                ['cmd' => 'snapchat:sync-metrics', 'opts' => ['--days' => 30], 'desc' => 'Snapchat deep metrics (30d)'],
                ['cmd' => 'snapchat:sync-creatives', 'opts' => [], 'desc' => 'Snapchat creatives'],
            ],
            'twitter' => [
                ['cmd' => 'sync:twitter-campaigns', 'opts' => ['--days' => 30], 'desc' => 'X/Twitter campaigns + deep metrics (30d)'],
                // Note: Twitter creatives sync is not yet implemented
            ],
        ];

        foreach ($syncs as $platformKey => $commands) {
            if ($platformFilter && $platformKey !== $platformFilter) {
                continue;
            }

            $this->newLine();
            $this->info("▶ {$platformKey}");

            foreach ($commands as $sync) {
                if ($skipCreatives && str_contains($sync['desc'], 'creative')) {
                    $this->line("  ⊘ Skipped (creatives): {$sync['desc']}");
                    continue;
                }

                try {
                    $this->line("  → {$sync['desc']}");
                    $code = Artisan::call($sync['cmd'], $sync['opts']);
                    $output = trim(Artisan::output());

                    if ($code !== 0) {
                        $this->warn("    ⚠ Exit code {$code}");
                        if ($output) {
                            foreach (explode("\n", $output) as $line) {
                                $this->line("    {$line}");
                            }
                        }
                    } else {
                        $this->info("    ✓ Done");
                    }

                    $results[] = [
                        'platform' => $platformKey,
                        'cmd' => $sync['cmd'],
                        'status' => $code === 0 ? 'ok' : 'fail',
                        'output' => $output,
                    ];
                } catch (\Exception $e) {
                    $this->error("    ✗ Exception: " . $e->getMessage());
                    Log::error('Daily sync sub-command failed', [
                        'platform' => $platformKey,
                        'cmd' => $sync['cmd'],
                        'error' => $e->getMessage(),
                    ]);
                    $results[] = [
                        'platform' => $platformKey,
                        'cmd' => $sync['cmd'],
                        'status' => 'error',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        $elapsed = $startTime->diffForHumans(now(), true);
        $total = count($results);
        $success = count(array_filter($results, fn($r) => $r['status'] === 'ok'));
        $fail = $total - $success;

        $this->newLine();
        $this->info("═══════════════════════════════════════");
        $this->info("Daily Sync Complete");
        $this->info("Elapsed: {$elapsed}");
        $this->info("Success: {$success} / {$total}");

        if ($fail > 0) {
            $this->error("Failures: {$fail}");
            foreach ($results as $r) {
                if ($r['status'] !== 'ok') {
                    $this->warn("  • {$r['platform']}: {$r['cmd']} — {$r['status']}");
                }
            }
            return 1;
        }

        return 0;
    }
}
