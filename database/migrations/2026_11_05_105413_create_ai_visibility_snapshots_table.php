<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_visibility_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('snapshot_date'); // YYYY-MM-DD, date data was captured
            $table->string('report_month', 7); // YYYY-MM, which month this upload covers

            // AI platform breakdown (GA4 total across all months in report)
            $table->json('ai_platforms')->nullable(); // [{platform, sessions, share, key_events, revenue}]

            // GSC AI pages (top 10 by AI impressions)
            $table->json('top_ai_pages')->nullable(); // [{url, impressions}]

            // GSC AI countries (top 10)
            $table->json('top_ai_countries')->nullable(); // [{country, impressions}]

            // AI shopping funnel (GA4 events for AI channel)
            $table->json('ai_funnel')->nullable(); // [{event, total, ...months}]

            // Clarity / Copilot citations
            $table->integer('clarity_citations')->nullable();
            $table->json('clarity_data')->nullable(); // full key-value pairs from Clarity_AI sheet

            // Semrush AI visibility
            $table->integer('semrush_score')->nullable(); // 0-100
            $table->integer('semrush_topics_count')->nullable();
            $table->integer('semrush_prompts_count')->nullable();
            $table->json('semrush_top_topics')->nullable(); // [{topic, market, visibility, difficulty, mentions, volume}]

            // Source file info
            $table->string('filename')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'report_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_visibility_snapshots');
    }
};
