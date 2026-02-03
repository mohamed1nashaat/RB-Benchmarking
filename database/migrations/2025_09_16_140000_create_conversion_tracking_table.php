<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversion_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('conversion_id')->unique()->index();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->string('user_id')->nullable();
            $table->string('session_id')->nullable();

            // Conversion details
            $table->string('conversion_type');
            $table->decimal('conversion_value', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            // Traffic source information
            $table->string('source')->nullable();
            $table->string('medium')->nullable();
            $table->string('channel')->nullable();

            // Device and browser information
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();

            // Technical information
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('page_url')->nullable();
            $table->text('referrer')->nullable();

            // UTM tracking parameters
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();

            // Additional custom data (JSON)
            $table->json('additional_data')->nullable();

            $table->timestamps();

            // Indexes for better query performance
            $table->index(['campaign_id', 'created_at']);
            $table->index(['conversion_type', 'created_at']);
            $table->index(['user_id', 'session_id']);
            $table->index('created_at');

            // Foreign key constraint
            $table->foreign('campaign_id')->references('id')->on('ad_campaigns')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversion_tracking');
    }
};