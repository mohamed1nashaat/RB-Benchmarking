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
        Schema::table('campaign_categories', function (Blueprint $table) {
            // Add industry_id column (nullable initially for existing data)
            $table->unsignedBigInteger('industry_id')->nullable()->after('id');
            $table->foreign('industry_id')->references('id')->on('industries')->onDelete('cascade');

            // Drop the unique constraint on name since same name can exist in different industries
            $table->dropUnique(['name']);

            // Add unique constraint for name within an industry
            $table->unique(['industry_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_categories', function (Blueprint $table) {
            $table->dropUnique(['industry_id', 'name']);
            $table->dropForeign(['industry_id']);
            $table->dropColumn('industry_id');
            $table->unique(['name']);
        });
    }
};
