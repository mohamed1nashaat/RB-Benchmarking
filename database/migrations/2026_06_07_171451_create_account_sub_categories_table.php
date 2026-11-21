<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_sub_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_category_id')->constrained('account_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['account_category_id', 'name']);
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_sub_categories');
    }
};
