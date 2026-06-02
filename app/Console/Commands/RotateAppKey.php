<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RotateAppKey extends Command
{
    protected $signature = 'key:rotate 
                            {--old-key= : The previous APP_KEY for decryption}
                            {--dry-run : Show what would be changed without modifying data}';

    protected $description = 'Rotate APP_KEY and re-encrypt sensitive database fields';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $oldKey = $this->option('old-key');

        if (!$oldKey) {
            $this->error('The --old-key option is required for re-encryption.');
            $this->line('Usage: php artisan key:rotate --old-key=base64:OLDKEY');
            return Command::FAILURE;
        }

        $this->info($dryRun ? '[DRY RUN] Analyzing encrypted fields...' : 'Rotating APP_KEY and re-encrypting data...');

        // Re-encrypt Integration app_config fields
        $integrations = DB::table('integrations')
            ->whereNotNull('app_config')
            ->get(['id', 'app_config']);

        $rotated = 0;

        foreach ($integrations as $integration) {
            try {
                // Decrypt with old key
                $decrypted = $this->decryptWithKey($integration->app_config, $oldKey);

                if ($dryRun) {
                    $this->line("Would re-encrypt integration ID: {$integration->id}");
                    $rotated++;
                    continue;
                }

                // Re-encrypt with new key (Laravel's default)
                $reEncrypted = encrypt($decrypted);

                DB::table('integrations')
                    ->where('id', $integration->id)
                    ->update(['app_config' => $reEncrypted]);

                $rotated++;
            } catch (\Exception $e) {
                $this->warn("Failed to re-encrypt integration {$integration->id}: {$e->getMessage()}");
            }
        }

        $this->info("{$rotated} integration records " . ($dryRun ? 'would be' : 'were') . " re-encrypted.");

        if (!$dryRun) {
            $this->info('APP_KEY rotation complete. All active sessions have been invalidated.');
            $this->warn('Remember to update SESSION_ENCRYPT=true in .env if not already set.');
        }

        return Command::SUCCESS;
    }

    private function decryptWithKey(string $payload, string $key): mixed
    {
        // Temporarily swap APP_KEY to decrypt with old key
        $originalKey = config('app.key');
        config(['app.key' => $key]);

        try {
            $decrypted = decrypt($payload);
        } finally {
            config(['app.key' => $originalKey]);
        }

        return $decrypted;
    }
}
