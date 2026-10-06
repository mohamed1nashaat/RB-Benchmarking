<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_vectors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('entity_type', 64);
            $table->unsignedBigInteger('entity_id');
            $table->text('content');
            $table->json('vector');
            $table->string('model', 128)->default('nomic-embed-text');
            $table->timestamps();

            $table->unique(['tenant_id', 'entity_type', 'entity_id'], 'ai_vectors_unique');
            $table->index(['tenant_id', 'entity_type']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_vectors');
    }
};
