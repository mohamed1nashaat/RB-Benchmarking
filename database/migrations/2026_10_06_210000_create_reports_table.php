<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ad_account_id')->nullable();
            $table->string('name');
            $table->enum('status', ['draft', 'generating', 'ready', 'failed'])->default('draft');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('platform', 64)->nullable();
            $table->string('industry', 128)->nullable();
            $table->text('executive_summary')->nullable();
            $table->json('performance_data')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('sections')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->foreign('ad_account_id')
                  ->references('id')->on('ad_accounts')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
