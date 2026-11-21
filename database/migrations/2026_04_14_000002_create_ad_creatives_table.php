<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_creatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('ad_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('ad_campaign_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('platform', ['facebook', 'google', 'snapchat', 'tiktok', 'linkedin', 'twitter']);
            $table->string('external_creative_id');
            $table->string('name')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->text('image_url')->nullable();
            $table->text('video_url')->nullable();
            $table->string('status')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'platform', 'ad_account_id'], 'ad_creatives_tenant_platform_account_idx');
            $table->index(['ad_campaign_id'], 'ad_creatives_campaign_idx');
            $table->unique(['platform', 'external_creative_id'], 'ad_creatives_platform_external_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_creatives');
    }
};
