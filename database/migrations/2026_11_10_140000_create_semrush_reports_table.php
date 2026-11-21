<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semrush_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->boolean('is_client')->default(false);
            $table->string('report_month', 7);
            $table->date('captured_at');
            $table->json('topics');
            $table->json('prompts');
            $table->json('source_files');
            $table->timestamps();
            $table->unique(['tenant_id', 'domain', 'report_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semrush_reports');
    }
};
