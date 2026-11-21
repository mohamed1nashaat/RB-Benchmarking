<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('total_budget', 14, 2);
            $table->char('currency', 3)->default('SAR');
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('objective', ['awareness', 'traffic', 'leads', 'conversions'])->default('leads');
            $table->string('industry', 128)->nullable();
            $table->json('platforms');                  // ['facebook','google','tiktok',...]
            $table->json('platform_allocations')->nullable(); // {platform: {budget, pct, expected_leads}}
            $table->json('funnel_split')->nullable();   // {tof: 40, mof: 35, bof: 25}
            $table->json('scenarios')->nullable();      // {conservative:{...}, recommended:{...}, aggressive:{...}}
            $table->json('calendar')->nullable();       // monthly breakdown with Ramadan flag
            $table->enum('status', ['draft', 'approved', 'archived'])->default('draft');
            $table->string('selected_scenario', 32)->nullable(); // conservative|recommended|aggressive
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_plans');
    }
};
