<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_visibility_snapshots', function (Blueprint $table) {
            // Structured workbook tables used by the AI Visibility report:
            // daily GSC, monthly GA4 channels/AI platforms/funnel, Clarity tables,
            // Semrush overview and topics, plus each sheet's caption.
            $table->json('report_tables')->nullable()->after('semrush_top_topics');
        });
    }

    public function down(): void
    {
        Schema::table('ai_visibility_snapshots', function (Blueprint $table) {
            $table->dropColumn('report_tables');
        });
    }
};
