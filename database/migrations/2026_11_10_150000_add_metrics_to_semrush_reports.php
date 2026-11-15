<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semrush_reports', fn (Blueprint $table) => $table->json('metrics')->nullable());
    }

    public function down(): void
    {
        Schema::table('semrush_reports', fn (Blueprint $table) => $table->dropColumn('metrics'));
    }
};
