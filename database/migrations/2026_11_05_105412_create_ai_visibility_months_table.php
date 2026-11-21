<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_visibility_months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('month', 7); // YYYY-MM
            $table->string('website')->nullable();

            // GSC: Google AI Overviews / AI Mode impressions
            $table->integer('ai_impressions')->nullable();
            $table->integer('ai_impressions_per_day')->nullable();
            $table->integer('total_impressions')->nullable();
            $table->decimal('ai_impression_share', 8, 6)->nullable(); // fraction of total impressions

            // GSC: organic clicks for context
            $table->integer('gsc_clicks')->nullable();
            $table->decimal('gsc_ctr', 8, 6)->nullable();

            // GA4: AI assistant sessions (ChatGPT, Gemini, Perplexity, etc.)
            $table->integer('ai_sessions')->nullable();
            $table->integer('ai_key_events')->nullable();
            $table->decimal('ai_revenue', 12, 4)->nullable();
            $table->decimal('ai_key_event_rate', 8, 6)->nullable();
            $table->decimal('ai_revenue_per_session', 12, 4)->nullable();
            $table->decimal('ai_session_share', 8, 6)->nullable(); // fraction of total sessions

            // GA4: site totals for context
            $table->integer('total_sessions')->nullable();
            $table->integer('total_key_events')->nullable();
            $table->decimal('total_revenue', 12, 4)->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_visibility_months');
    }
};
