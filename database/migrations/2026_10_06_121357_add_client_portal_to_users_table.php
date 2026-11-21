<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_client_portal')->default(false)->after('is_super_admin');
            $table->foreignId('client_ad_account_id')->nullable()->after('is_client_portal')
                ->constrained('ad_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['client_ad_account_id']);
            $table->dropColumn(['is_client_portal', 'client_ad_account_id']);
        });
    }
};
