<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_categories', function (Blueprint $table) {
            $table->foreignId('industry_id')
                ->nullable()
                ->after('id')
                ->constrained('industries')
                ->nullOnDelete();

            $table->index('industry_id');
        });
    }

    public function down(): void
    {
        Schema::table('account_categories', function (Blueprint $table) {
            $table->dropForeign(['industry_id']);
            $table->dropColumn('industry_id');
        });
    }
};
