<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->json('layout')->nullable()->after('sections');
            $table->string('client_name', 255)->nullable()->after('name');
            $table->string('share_token', 64)->nullable()->unique()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['layout', 'client_name', 'share_token']);
        });
    }
};
