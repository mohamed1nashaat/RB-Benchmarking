<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One tracking profile per client: the brand's domain, extra spellings and engines to ask.
        Schema::create('ai_tracking_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('domain')->nullable();
            $table->json('aliases')->nullable();
            $table->json('engines')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // The buyer questions asked every week.
        Schema::create('ai_tracked_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->text('prompt');
            $table->char('prompt_hash', 40);
            $table->string('topic')->nullable();
            $table->unsignedInteger('volume')->nullable();
            $table->char('country', 2)->nullable();
            $table->string('source', 20)->default('manual');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'prompt_hash']);
        });

        // One answer per prompt, engine and run date.
        Schema::create('ai_prompt_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_tracked_prompt_id')->constrained()->cascadeOnDelete();
            $table->string('engine', 20);
            $table->string('model', 80)->nullable();
            $table->date('run_on');
            $table->string('status', 10)->default('ok');
            $table->longText('answer')->nullable();
            $table->boolean('brand_mentioned')->default(false);
            $table->unsignedTinyInteger('brand_rank')->nullable();
            $table->boolean('brand_cited')->default(false);
            $table->json('brands')->nullable();
            $table->json('sources')->nullable();
            $table->string('sentiment', 10)->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->unique(['ai_tracked_prompt_id', 'engine', 'run_on']);
            $table->index(['tenant_id', 'run_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_runs');
        Schema::dropIfExists('ai_tracked_prompts');
        Schema::dropIfExists('ai_tracking_profiles');
    }
};
