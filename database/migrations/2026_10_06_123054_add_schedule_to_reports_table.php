<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->enum('schedule_frequency', ['none', 'weekly', 'monthly'])->default('none')->after('sent_at');
            $table->json('delivery_emails')->nullable()->after('schedule_frequency');
            $table->timestamp('next_run_at')->nullable()->after('delivery_emails');
            $table->timestamp('last_auto_run_at')->nullable()->after('next_run_at');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['schedule_frequency', 'delivery_emails', 'next_run_at', 'last_auto_run_at']);
        });
    }
};
