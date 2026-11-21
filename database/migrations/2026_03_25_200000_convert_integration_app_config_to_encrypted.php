<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change column from JSON to LONGTEXT so it can store encrypted values
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support MODIFY — column is already TEXT, no change needed
        } else {
            DB::statement('ALTER TABLE integrations MODIFY app_config LONGTEXT NULL');
        }

        // Encrypt existing plaintext JSON values
        DB::table('integrations')->whereNotNull('app_config')->orderBy('id')->chunk(100, function ($integrations) {
            foreach ($integrations as $integration) {
                $value = $integration->app_config;
                if (!$value) {
                    continue;
                }

                // Skip if already encrypted (not valid JSON)
                json_decode($value);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }

                try {
                    $encrypted = Crypt::encryptString($value);
                    DB::table('integrations')
                        ->where('id', $integration->id)
                        ->update(['app_config' => $encrypted]);
                } catch (\Exception $e) {
                    Log::warning("Failed to encrypt app_config for integration {$integration->id}");
                }
            }
        });
    }

    public function down(): void
    {
        // Decrypt back to plaintext
        DB::table('integrations')->whereNotNull('app_config')->orderBy('id')->chunk(100, function ($integrations) {
            foreach ($integrations as $integration) {
                $value = $integration->app_config;
                if (!$value) {
                    continue;
                }

                // Skip if already plaintext JSON
                json_decode($value);
                if (json_last_error() === JSON_ERROR_NONE) {
                    continue;
                }

                try {
                    $decrypted = Crypt::decryptString($value);
                    DB::table('integrations')
                        ->where('id', $integration->id)
                        ->update(['app_config' => $decrypted]);
                } catch (\Exception $e) {
                    Log::warning("Failed to decrypt app_config for integration {$integration->id}");
                }
            }
        });

        // Change column back to JSON
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE integrations MODIFY app_config JSON NOT NULL');
        }
    }
};
