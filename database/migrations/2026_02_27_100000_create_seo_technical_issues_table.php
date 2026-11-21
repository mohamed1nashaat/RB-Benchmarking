<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_technical_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('issue_type', 100);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('page_url', 2048)->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->enum('current_status', ['open', 'in_progress', 'resolved', 'wont_fix'])->default('open');
            $table->text('recommended_fix')->nullable();
            $table->string('assigned_to', 255)->nullable();
            $table->date('date_found')->nullable();
            $table->date('date_fixed')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'current_status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_technical_issues');
    }
};
