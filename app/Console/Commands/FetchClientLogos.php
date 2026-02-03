<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\LogoFetchService;
use Illuminate\Console\Command;

class FetchClientLogos extends Command
{
    protected $signature = 'clients:fetch-logos
                            {--client= : Specific client ID or slug to fetch logo for}
                            {--force : Re-fetch logos even if they already exist}
                            {--limit= : Limit number of clients to process}';

    protected $description = 'Fetch logos for clients from Google Images and other sources';

    private LogoFetchService $logoService;

    public function __construct(LogoFetchService $logoService)
    {
        parent::__construct();
        $this->logoService = $logoService;
    }

    public function handle()
    {
        $specificClient = $this->option('client');
        $force = $this->option('force');
        $limit = $this->option('limit');

        if ($specificClient) {
            // Fetch for specific client
            $tenant = Tenant::where('id', $specificClient)
                ->orWhere('slug', $specificClient)
                ->first();

            if (!$tenant) {
                $this->error("Client not found: {$specificClient}");
                return 1;
            }

            return $this->fetchForClient($tenant, $force);
        }

        // Fetch for all clients
        $query = Tenant::query();

        if (!$force) {
            $query->whereNull('logo_path');
        }

        if ($limit) {
            $query->limit((int) $limit);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->info('No clients need logo fetching.');
            return 0;
        }

        $this->info("Fetching logos for {$tenants->count()} clients...");
        $this->newLine();

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        $success = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($tenants as $tenant) {
            $result = $this->logoService->fetchLogo($tenant, $force);

            if ($result['success']) {
                $success++;
            } elseif ($result['skipped'] ?? false) {
                $skipped++;
            } else {
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('=== Summary ===');
        $this->info("Successfully fetched: {$success}");
        $this->info("Failed: {$failed}");
        if ($skipped > 0) {
            $this->info("Skipped (already have logos): {$skipped}");
        }

        return 0;
    }

    private function fetchForClient(Tenant $tenant, bool $force): int
    {
        $this->info("Fetching logo for: {$tenant->name}");

        $result = $this->logoService->fetchLogo($tenant, $force);

        if ($result['success']) {
            $this->info("✓ Logo fetched successfully");
            $this->line("  Path: {$result['path']}");
        } else {
            $this->error("✗ Failed: {$result['message']}");
            return 1;
        }

        return 0;
    }
}
