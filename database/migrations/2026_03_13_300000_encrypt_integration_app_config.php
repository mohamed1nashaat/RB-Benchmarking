<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // Encrypt existing plaintext app_config values
        // The model now uses 'encrypted:array' cast, so we need to encrypt stored data
        DB::table('integrations')->whereNotNull('app_config')->orderBy('id')->chunk(100, function ($integrations) {
            foreach ($integrations as $integration) {
                $value = $integration->app_config;

                // Skip if already encrypted (not valid JSON)
                if ($value && !$this->isJson($value)) {
                    continue;
                }

                if ($value) {
                    try {
                        $encrypted = Crypt::encryptString($value);
                        DB::table('integrations')
                            ->where('id', $integration->id)
                            ->update(['app_config' => $encrypted]);
                    } catch (\Exception $e) {
                        Log::warning("Failed to encrypt app_config for integration {$integration->id}: {$e->getMessage()}");
                    }
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

                if ($value && !$this->isJson($value)) {
                    try {
                        $decrypted = Crypt::decryptString($value);
                        DB::table('integrations')
                            ->where('id', $integration->id)
                            ->update(['app_config' => $decrypted]);
                    } catch (\Exception $e) {
                        Log::warning("Failed to decrypt app_config for integration {$integration->id}: {$e->getMessage()}");
                    }
                }
            }
        });
    }

    private function isJson(?string $string): bool
    {
        if ($string === null) {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
};
