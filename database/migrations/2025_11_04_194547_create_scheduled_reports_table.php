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
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();

            // Report Configuration
            $table->enum('report_type', ['performance', 'benchmark', 'campaign', 'account', 'industry'])->default('performance');
            $table->json('metrics')->nullable(); // Array of metrics to include
            $table->json('filters')->nullable(); // Objectives, accounts, campaigns, date range

            // Schedule Configuration
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('weekly');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->nullable(); // For weekly
            $table->integer('day_of_month')->nullable(); // For monthly (1-31)
            $table->time('time_of_day')->default('09:00:00'); // When to send

            // Export Configuration
            $table->json('export_formats')->nullable(); // pdf, excel, csv
            $table->json('recipients')->nullable(); // Array of email addresses

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamp('next_generation_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'is_active']);
            $table->index('next_generation_at');
        });

        Schema::create('report_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->enum('status', ['pending', 'generating', 'completed', 'failed'])->default('pending');
            $table->json('filters_snapshot')->nullable(); // Snapshot of filters used
            $table->string('file_path')->nullable(); // Path to generated file
            $table->enum('format', ['pdf', 'excel', 'csv']);
            $table->integer('file_size')->nullable(); // In bytes
            $table->text('error_message')->nullable();

            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['scheduled_report_id', 'created_at']);
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_history');
        Schema::dropIfExists('scheduled_reports');
    }
};
