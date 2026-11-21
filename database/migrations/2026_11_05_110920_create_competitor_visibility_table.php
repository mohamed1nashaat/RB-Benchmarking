<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_visibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competitor_id')->constrained('competitors')->cascadeOnDelete();
            $table->string('report_month', 7); // YYYY-MM

            // Google AI Overviews (GSC)
            $table->integer('ai_impressions')->nullable();

            // GA4 AI assistant sessions
            $table->integer('ai_sessions')->nullable();

            // Semrush AI visibility
            $table->integer('semrush_score')->nullable(); // 0-100
            $table->integer('semrush_topics_count')->nullable();

            // Clarity / Copilot
            $table->integer('clarity_citations')->nullable();

            // Notes / source
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['competitor_id', 'report_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_visibility');
    }
};
