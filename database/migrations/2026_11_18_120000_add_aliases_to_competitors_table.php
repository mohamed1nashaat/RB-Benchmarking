<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Other spellings of a competitor's name (for example its Arabic name), used to find
    // branded prompts and to spot the brand inside AI answers.
    public function up(): void
    {
        Schema::table('competitors', function (Blueprint $table) {
            $table->json('aliases')->nullable()->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('competitors', function (Blueprint $table) {
            $table->dropColumn('aliases');
        });
    }
};
