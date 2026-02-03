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
        Schema::table('tenants', function (Blueprint $table) {
            // Basic Information
            $table->string('logo')->nullable()->after('settings');
            $table->text('description')->nullable()->after('logo');

            // Contact Information
            $table->string('contact_email')->nullable()->after('description');
            $table->string('contact_phone')->nullable()->after('contact_email');
            $table->string('contact_person')->nullable()->after('contact_phone');
            $table->text('address')->nullable()->after('contact_person');
            $table->string('website')->nullable()->after('address');

            // Business Information
            $table->string('industry')->nullable()->after('website');
            $table->string('vertical')->nullable()->after('industry');

            // Billing & Contract Information
            $table->string('billing_email')->nullable()->after('vertical');
            $table->date('contract_start_date')->nullable()->after('billing_email');
            $table->date('contract_end_date')->nullable()->after('contract_start_date');
            $table->string('subscription_tier')->nullable()->after('contract_end_date');
            $table->decimal('monthly_budget', 15, 2)->nullable()->after('subscription_tier');

            // Notes
            $table->text('notes')->nullable()->after('monthly_budget');

            // Indexes for common queries
            $table->index('industry');
            $table->index('subscription_tier');
            $table->index('contract_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['industry']);
            $table->dropIndex(['subscription_tier']);
            $table->dropIndex(['contract_end_date']);

            // Drop columns
            $table->dropColumn([
                'logo',
                'description',
                'contact_email',
                'contact_phone',
                'contact_person',
                'address',
                'website',
                'industry',
                'vertical',
                'billing_email',
                'contract_start_date',
                'contract_end_date',
                'subscription_tier',
                'monthly_budget',
                'notes',
            ]);
        });
    }
};
