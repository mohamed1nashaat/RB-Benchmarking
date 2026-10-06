<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('stripe_price_id')->nullable();
            $table->decimal('price_sar', 10, 2)->default(0);
            $table->decimal('price_usd', 10, 2)->default(0);
            $table->enum('interval', ['monthly', 'annual'])->default('monthly');
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('subscription_plans')->insert([
            [
                'name'       => 'Starter',
                'slug'       => 'starter',
                'price_sar'  => 499,
                'price_usd'  => 133,
                'interval'   => 'monthly',
                'features'   => json_encode(['Up to 3 ad accounts', '5 AI reports/month', 'Media planner', 'Email support']),
                'limits'     => json_encode(['reports_per_month' => 5, 'ad_accounts' => 3, 'users' => 2]),
                'sort_order' => 1,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Growth',
                'slug'       => 'growth',
                'price_sar'  => 999,
                'price_usd'  => 266,
                'interval'   => 'monthly',
                'features'   => json_encode(['Up to 10 ad accounts', '20 AI reports/month', 'Media planner + approval flow', 'Client portal', 'Priority support']),
                'limits'     => json_encode(['reports_per_month' => 20, 'ad_accounts' => 10, 'users' => 5]),
                'sort_order' => 2,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Agency',
                'slug'       => 'agency',
                'price_sar'  => 1999,
                'price_usd'  => 533,
                'interval'   => 'monthly',
                'features'   => json_encode(['Unlimited ad accounts', 'Unlimited AI reports', 'Full media planner', 'White-label client portal', 'Arabic report generation', 'Dedicated support']),
                'limits'     => json_encode(['reports_per_month' => -1, 'ad_accounts' => -1, 'users' => 15]),
                'sort_order' => 3,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Enterprise',
                'slug'       => 'enterprise',
                'price_sar'  => 3499,
                'price_usd'  => 933,
                'interval'   => 'monthly',
                'features'   => json_encode(['Everything in Agency', 'Custom integrations', 'SLA 99.9% uptime', 'Dedicated account manager', 'On-premise option available']),
                'limits'     => json_encode(['reports_per_month' => -1, 'ad_accounts' => -1, 'users' => -1]),
                'sort_order' => 4,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
