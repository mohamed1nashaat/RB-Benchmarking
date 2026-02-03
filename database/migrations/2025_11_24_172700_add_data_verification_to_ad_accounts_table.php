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
        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->enum('data_verification_status', ['pending', 'approved', 'declined'])->default('pending')->after('status');
            $table->text('verification_notes')->nullable()->after('data_verification_status');
            $table->timestamp('verified_at')->nullable()->after('verification_notes');
            $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->dropColumn(['data_verification_status', 'verification_notes', 'verified_at', 'verified_by']);
        });
    }
};
