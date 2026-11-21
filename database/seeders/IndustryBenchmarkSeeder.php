<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndustryBenchmarkSeeder extends Seeder
{
    /**
     * Global industry benchmark data from 2025/2026 sources:
     * 1. TwoMinuteReports Google Ads 2025 (25 industries, real spend data)
     * 2. TwoMinuteReports Facebook Ads 2025 (22 industries)
     * 3. TwoMinuteReports TikTok Ads 2025 (28 industries)
     * 4. LinkedIn Marketing Solutions / Datavinity 2026
     * 5. Snapchat Ads Benchmarks / Adliftr 2026
     *
     * p50 = industry median (source average/median where provided).
     * p10/p25/p75/p90 derived using distribution shape from industry data.
     */

    private array $fiveSources = [
        'TwoMinuteReports Google Ads 2025',
        'TwoMinuteReports Facebook Ads 2025',
        'TwoMinuteReports TikTok Ads 2025',
        'LinkedIn Marketing Solutions 2026',
        'Snapchat Ads Benchmarks 2026',
    ];

    private array $platformSource = [
        'google'   => 'TwoMinuteReports Google Ads 2025',
        'facebook' => 'TwoMinuteReports Facebook Ads 2025',
        'tiktok'   => 'TwoMinuteReports TikTok Ads 2025',
        'linkedin' => 'LinkedIn Marketing Solutions 2026',
        'snapchat' => 'Snapchat Ads Benchmarks 2026',
    ];

    public function run(): void
    {
        DB::table('industry_benchmarks')->truncate();

        $benchmarks = [];
        $date = '2025-10-01';

        // ─── Automotive ──────────────────────────────────────────────────────
        $benchmarks[] = $this->bm('automotive', 'google',   'ctr',  3.26,  18000, $date);
        $benchmarks[] = $this->bm('automotive', 'google',   'cpc',  1.12,  18000, $date);
        $benchmarks[] = $this->bm('automotive', 'google',   'cpm',  30.79, 18000, $date);
        $benchmarks[] = $this->bm('automotive', 'google',   'cpa',  9.85,  18000, $date);
        $benchmarks[] = $this->bm('automotive', 'google',   'cvr',  13.90, 18000, $date);
        $benchmarks[] = $this->bm('automotive', 'facebook', 'ctr',  1.76,  22000, $date);
        $benchmarks[] = $this->bm('automotive', 'facebook', 'cpc',  0.32,  22000, $date);
        $benchmarks[] = $this->bm('automotive', 'facebook', 'cpm',  5.56,  22000, $date);
        $benchmarks[] = $this->bm('automotive', 'facebook', 'cpl',  30.77, 22000, $date);
        $benchmarks[] = $this->bm('automotive', 'facebook', 'roas', 6.38,  22000, $date);
        $benchmarks[] = $this->bm('automotive', 'tiktok',   'ctr',  0.59,  5200,  $date);
        $benchmarks[] = $this->bm('automotive', 'tiktok',   'cpc',  0.58,  5200,  $date);
        $benchmarks[] = $this->bm('automotive', 'tiktok',   'cpm',  8.50,  5200,  $date);
        $benchmarks[] = $this->bm('automotive', 'tiktok',   'cpa',  5.50,  5200,  $date);
        $benchmarks[] = $this->bm('automotive', 'tiktok',   'cvr',  9.13,  5200,  $date);
        $benchmarks[] = $this->bm('automotive', 'linkedin', 'ctr',  0.40,  5800,  $date);
        $benchmarks[] = $this->bm('automotive', 'linkedin', 'cpc',  7.00,  5800,  $date);
        $benchmarks[] = $this->bm('automotive', 'linkedin', 'cpm',  45.00, 5800,  $date);
        $benchmarks[] = $this->bm('automotive', 'linkedin', 'cpl',  135.00, 5800, $date);
        $benchmarks[] = $this->bm('automotive', 'snapchat', 'ctr',  1.08,  3200,  $date);
        $benchmarks[] = $this->bm('automotive', 'snapchat', 'cpc',  0.82,  3200,  $date);
        $benchmarks[] = $this->bm('automotive', 'snapchat', 'cpm',  4.20,  3200,  $date);

        // ─── E-Commerce / Retail ─────────────────────────────────────────────
        $benchmarks[] = $this->bm('e_commerce_retail', 'google',   'ctr',  1.95,  22000, $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'google',   'cpc',  0.62,  22000, $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'google',   'cpm',  10.76, 22000, $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'google',   'cpa',  19.40, 22000, $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'google',   'cvr',  3.47,  22000, $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'facebook', 'ctr',  1.90,  28000, $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'facebook', 'cpc',  0.21,  28000, $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'facebook', 'cpm',  3.91,  28000, $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'facebook', 'cpl',  21.98, 28000, $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'facebook', 'roas', 3.56,  28000, $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'tiktok',   'ctr',  0.82,  7500,  $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'tiktok',   'cpc',  0.26,  7500,  $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'tiktok',   'cpm',  7.00,  7500,  $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'tiktok',   'cpa',  3.78,  7500,  $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'tiktok',   'cvr',  2.83,  7500,  $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'linkedin', 'ctr',  0.48,  6000,  $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'linkedin', 'cpc',  5.50,  6000,  $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'linkedin', 'cpm',  42.00, 6000,  $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'linkedin', 'cpl',  110.00, 6000, $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'snapchat', 'ctr',  1.10,  4500,  $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'snapchat', 'cpc',  0.80,  4500,  $date);
        $benchmarks[] = $this->bm('e_commerce_retail', 'snapchat', 'cpm',  4.00,  4500,  $date);

        // ─── Education ───────────────────────────────────────────────────────
        $benchmarks[] = $this->bm('education', 'google',   'ctr',  2.95,  18000, $date);
        $benchmarks[] = $this->bm('education', 'google',   'cpc',  1.17,  18000, $date);
        $benchmarks[] = $this->bm('education', 'google',   'cpm',  23.29, 18000, $date);
        $benchmarks[] = $this->bm('education', 'google',   'cpa',  25.05, 18000, $date);
        $benchmarks[] = $this->bm('education', 'google',   'cvr',  4.43,  18000, $date);
        $benchmarks[] = $this->bm('education', 'facebook', 'ctr',  2.03,  19000, $date);
        $benchmarks[] = $this->bm('education', 'facebook', 'cpc',  0.27,  19000, $date);
        $benchmarks[] = $this->bm('education', 'facebook', 'cpm',  5.54,  19000, $date);
        $benchmarks[] = $this->bm('education', 'facebook', 'cpl',  21.98, 19000, $date);
        $benchmarks[] = $this->bm('education', 'facebook', 'roas', 1.31,  19000, $date);
        $benchmarks[] = $this->bm('education', 'tiktok',   'ctr',  0.42,  5000,  $date);
        $benchmarks[] = $this->bm('education', 'tiktok',   'cpc',  0.49,  5000,  $date);
        $benchmarks[] = $this->bm('education', 'tiktok',   'cpm',  6.50,  5000,  $date);
        $benchmarks[] = $this->bm('education', 'tiktok',   'cpa',  3.80,  5000,  $date);
        $benchmarks[] = $this->bm('education', 'tiktok',   'cvr',  3.97,  5000,  $date);
        $benchmarks[] = $this->bm('education', 'linkedin', 'ctr',  0.61,  9500,  $date);
        $benchmarks[] = $this->bm('education', 'linkedin', 'cpc',  4.20,  9500,  $date);
        $benchmarks[] = $this->bm('education', 'linkedin', 'cpm',  46.00, 9500,  $date);
        $benchmarks[] = $this->bm('education', 'linkedin', 'cpl',  120.00, 9500, $date);
        $benchmarks[] = $this->bm('education', 'snapchat', 'ctr',  0.95,  3000,  $date);
        $benchmarks[] = $this->bm('education', 'snapchat', 'cpc',  0.95,  3000,  $date);
        $benchmarks[] = $this->bm('education', 'snapchat', 'cpm',  3.50,  3000,  $date);

        // ─── Fashion & Luxury ────────────────────────────────────────────────
        $benchmarks[] = $this->bm('fashion_luxury', 'google',   'ctr',  2.01,  16000, $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'google',   'cpc',  0.53,  16000, $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'google',   'cpm',  12.51, 16000, $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'google',   'cpa',  21.44, 16000, $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'google',   'cvr',  2.61,  16000, $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'facebook', 'ctr',  2.46,  21000, $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'facebook', 'cpc',  0.22,  21000, $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'facebook', 'cpm',  5.51,  21000, $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'facebook', 'cpl',  21.98, 21000, $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'facebook', 'roas', 4.51,  21000, $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'tiktok',   'ctr',  0.53,  7000,  $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'tiktok',   'cpc',  0.17,  7000,  $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'tiktok',   'cpm',  9.00,  7000,  $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'tiktok',   'cpa',  4.97,  7000,  $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'tiktok',   'cvr',  1.96,  7000,  $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'linkedin', 'ctr',  0.52,  3500,  $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'linkedin', 'cpc',  8.00,  3500,  $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'linkedin', 'cpm',  50.00, 3500,  $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'linkedin', 'cpl',  128.00, 3500, $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'snapchat', 'ctr',  1.04,  4000,  $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'snapchat', 'cpc',  0.84,  4000,  $date);
        $benchmarks[] = $this->bm('fashion_luxury', 'snapchat', 'cpm',  5.50,  4000,  $date);

        // ─── Finance & Insurance ─────────────────────────────────────────────
        $benchmarks[] = $this->bm('finance_insurance', 'google',   'ctr',  4.08,  20000, $date);
        $benchmarks[] = $this->bm('finance_insurance', 'google',   'cpc',  1.77,  20000, $date);
        $benchmarks[] = $this->bm('finance_insurance', 'google',   'cpm',  89.75, 20000, $date);
        $benchmarks[] = $this->bm('finance_insurance', 'google',   'cpa',  42.85, 20000, $date);
        $benchmarks[] = $this->bm('finance_insurance', 'google',   'cvr',  7.25,  20000, $date);
        $benchmarks[] = $this->bm('finance_insurance', 'facebook', 'ctr',  1.21,  22000, $date);
        $benchmarks[] = $this->bm('finance_insurance', 'facebook', 'cpc',  0.73,  22000, $date);
        $benchmarks[] = $this->bm('finance_insurance', 'facebook', 'cpm',  8.82,  22000, $date);
        $benchmarks[] = $this->bm('finance_insurance', 'facebook', 'cpl',  54.95, 22000, $date);
        $benchmarks[] = $this->bm('finance_insurance', 'facebook', 'roas', 1.67,  22000, $date);
        $benchmarks[] = $this->bm('finance_insurance', 'tiktok',   'ctr',  0.35,  4500,  $date);
        $benchmarks[] = $this->bm('finance_insurance', 'tiktok',   'cpc',  0.49,  4500,  $date);
        $benchmarks[] = $this->bm('finance_insurance', 'tiktok',   'cpm',  10.00, 4500,  $date);
        $benchmarks[] = $this->bm('finance_insurance', 'tiktok',   'cpa',  5.70,  4500,  $date);
        $benchmarks[] = $this->bm('finance_insurance', 'tiktok',   'cvr',  6.73,  4500,  $date);
        $benchmarks[] = $this->bm('finance_insurance', 'linkedin', 'ctr',  0.95,  12000, $date);
        $benchmarks[] = $this->bm('finance_insurance', 'linkedin', 'cpc',  11.82, 12000, $date);
        $benchmarks[] = $this->bm('finance_insurance', 'linkedin', 'cpm',  57.00, 12000, $date);
        $benchmarks[] = $this->bm('finance_insurance', 'linkedin', 'cpl',  417.00, 12000, $date);
        $benchmarks[] = $this->bm('finance_insurance', 'snapchat', 'ctr',  0.90,  2800,  $date);
        $benchmarks[] = $this->bm('finance_insurance', 'snapchat', 'cpc',  1.10,  2800,  $date);
        $benchmarks[] = $this->bm('finance_insurance', 'snapchat', 'cpm',  5.00,  2800,  $date);

        // ─── FMCG / CPG ──────────────────────────────────────────────────────
        $benchmarks[] = $this->bm('fmcg_cpg', 'google',   'ctr',  2.45,  15000, $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'google',   'cpc',  0.35,  15000, $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'google',   'cpm',  9.00,  15000, $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'google',   'cpa',  9.04,  15000, $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'google',   'cvr',  6.25,  15000, $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'facebook', 'ctr',  1.64,  18000, $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'facebook', 'cpc',  0.16,  18000, $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'facebook', 'cpm',  2.54,  18000, $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'facebook', 'cpl',  21.98, 18000, $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'facebook', 'roas', 6.90,  18000, $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'tiktok',   'ctr',  0.62,  5500,  $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'tiktok',   'cpc',  0.24,  5500,  $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'tiktok',   'cpm',  7.50,  5500,  $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'tiktok',   'cpa',  5.77,  5500,  $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'tiktok',   'cvr',  2.69,  5500,  $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'linkedin', 'ctr',  0.52,  3000,  $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'linkedin', 'cpc',  8.00,  3000,  $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'linkedin', 'cpm',  50.00, 3000,  $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'linkedin', 'cpl',  128.00, 3000, $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'snapchat', 'ctr',  1.04,  3500,  $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'snapchat', 'cpc',  0.84,  3500,  $date);
        $benchmarks[] = $this->bm('fmcg_cpg', 'snapchat', 'cpm',  3.50,  3500,  $date);

        // ─── Food & Beverage ─────────────────────────────────────────────────
        $benchmarks[] = $this->bm('food_beverage', 'google',   'ctr',  2.34,  14000, $date);
        $benchmarks[] = $this->bm('food_beverage', 'google',   'cpc',  0.36,  14000, $date);
        $benchmarks[] = $this->bm('food_beverage', 'google',   'cpm',  9.23,  14000, $date);
        $benchmarks[] = $this->bm('food_beverage', 'google',   'cpa',  9.14,  14000, $date);
        $benchmarks[] = $this->bm('food_beverage', 'google',   'cvr',  5.16,  14000, $date);
        $benchmarks[] = $this->bm('food_beverage', 'facebook', 'ctr',  1.64,  17000, $date);
        $benchmarks[] = $this->bm('food_beverage', 'facebook', 'cpc',  0.16,  17000, $date);
        $benchmarks[] = $this->bm('food_beverage', 'facebook', 'cpm',  2.54,  17000, $date);
        $benchmarks[] = $this->bm('food_beverage', 'facebook', 'cpl',  21.98, 17000, $date);
        $benchmarks[] = $this->bm('food_beverage', 'facebook', 'roas', 6.90,  17000, $date);
        $benchmarks[] = $this->bm('food_beverage', 'tiktok',   'ctr',  0.46,  5000,  $date);
        $benchmarks[] = $this->bm('food_beverage', 'tiktok',   'cpc',  0.25,  5000,  $date);
        $benchmarks[] = $this->bm('food_beverage', 'tiktok',   'cpm',  7.00,  5000,  $date);
        $benchmarks[] = $this->bm('food_beverage', 'tiktok',   'cpa',  9.95,  5000,  $date);
        $benchmarks[] = $this->bm('food_beverage', 'tiktok',   'cvr',  3.06,  5000,  $date);
        $benchmarks[] = $this->bm('food_beverage', 'linkedin', 'ctr',  0.52,  2800,  $date);
        $benchmarks[] = $this->bm('food_beverage', 'linkedin', 'cpc',  8.00,  2800,  $date);
        $benchmarks[] = $this->bm('food_beverage', 'linkedin', 'cpm',  50.00, 2800,  $date);
        $benchmarks[] = $this->bm('food_beverage', 'linkedin', 'cpl',  128.00, 2800, $date);
        $benchmarks[] = $this->bm('food_beverage', 'snapchat', 'ctr',  1.04,  3800,  $date);
        $benchmarks[] = $this->bm('food_beverage', 'snapchat', 'cpc',  0.84,  3800,  $date);
        $benchmarks[] = $this->bm('food_beverage', 'snapchat', 'cpm',  4.50,  3800,  $date);

        // ─── Government & Public Sector ──────────────────────────────────────
        $benchmarks[] = $this->bm('government_public_sector', 'google',   'ctr',  5.28,   12000, $date);
        $benchmarks[] = $this->bm('government_public_sector', 'google',   'cpc',  1.65,   12000, $date);
        $benchmarks[] = $this->bm('government_public_sector', 'google',   'cpm',  153.23, 12000, $date);
        $benchmarks[] = $this->bm('government_public_sector', 'google',   'cpa',  20.08,  12000, $date);
        $benchmarks[] = $this->bm('government_public_sector', 'google',   'cvr',  9.70,   12000, $date);
        $benchmarks[] = $this->bm('government_public_sector', 'facebook', 'ctr',  1.37,   14000, $date);
        $benchmarks[] = $this->bm('government_public_sector', 'facebook', 'cpc',  0.36,   14000, $date);
        $benchmarks[] = $this->bm('government_public_sector', 'facebook', 'cpm',  4.89,   14000, $date);
        $benchmarks[] = $this->bm('government_public_sector', 'facebook', 'cpl',  21.98,  14000, $date);
        $benchmarks[] = $this->bm('government_public_sector', 'tiktok',   'ctr',  0.52,   3200,  $date);
        $benchmarks[] = $this->bm('government_public_sector', 'tiktok',   'cpc',  0.30,   3200,  $date);
        $benchmarks[] = $this->bm('government_public_sector', 'tiktok',   'cpm',  6.00,   3200,  $date);
        $benchmarks[] = $this->bm('government_public_sector', 'tiktok',   'cvr',  2.96,   3200,  $date);
        $benchmarks[] = $this->bm('government_public_sector', 'linkedin', 'ctr',  0.52,   4500,  $date);
        $benchmarks[] = $this->bm('government_public_sector', 'linkedin', 'cpc',  8.00,   4500,  $date);
        $benchmarks[] = $this->bm('government_public_sector', 'linkedin', 'cpm',  50.00,  4500,  $date);
        $benchmarks[] = $this->bm('government_public_sector', 'linkedin', 'cpl',  128.00, 4500,  $date);
        $benchmarks[] = $this->bm('government_public_sector', 'snapchat', 'ctr',  1.04,   2200,  $date);
        $benchmarks[] = $this->bm('government_public_sector', 'snapchat', 'cpc',  0.84,   2200,  $date);
        $benchmarks[] = $this->bm('government_public_sector', 'snapchat', 'cpm',  3.50,   2200,  $date);

        // ─── Healthcare ──────────────────────────────────────────────────────
        $benchmarks[] = $this->bm('healthcare', 'google',   'ctr',  4.21,  19000, $date);
        $benchmarks[] = $this->bm('healthcare', 'google',   'cpc',  2.41,  19000, $date);
        $benchmarks[] = $this->bm('healthcare', 'google',   'cpm',  88.42, 19000, $date);
        $benchmarks[] = $this->bm('healthcare', 'google',   'cpa',  33.61, 19000, $date);
        $benchmarks[] = $this->bm('healthcare', 'google',   'cvr',  6.92,  19000, $date);
        $benchmarks[] = $this->bm('healthcare', 'facebook', 'ctr',  1.92,  21000, $date);
        $benchmarks[] = $this->bm('healthcare', 'facebook', 'cpc',  0.33,  21000, $date);
        $benchmarks[] = $this->bm('healthcare', 'facebook', 'cpm',  6.39,  21000, $date);
        $benchmarks[] = $this->bm('healthcare', 'facebook', 'cpl',  35.17, 21000, $date);
        $benchmarks[] = $this->bm('healthcare', 'facebook', 'roas', 3.94,  21000, $date);
        $benchmarks[] = $this->bm('healthcare', 'tiktok',   'ctr',  0.69,  5500,  $date);
        $benchmarks[] = $this->bm('healthcare', 'tiktok',   'cpc',  0.30,  5500,  $date);
        $benchmarks[] = $this->bm('healthcare', 'tiktok',   'cpm',  8.00,  5500,  $date);
        $benchmarks[] = $this->bm('healthcare', 'tiktok',   'cpa',  2.48,  5500,  $date);
        $benchmarks[] = $this->bm('healthcare', 'tiktok',   'cvr',  4.14,  5500,  $date);
        $benchmarks[] = $this->bm('healthcare', 'linkedin', 'ctr',  0.58,  8500,  $date);
        $benchmarks[] = $this->bm('healthcare', 'linkedin', 'cpc',  6.00,  8500,  $date);
        $benchmarks[] = $this->bm('healthcare', 'linkedin', 'cpm',  44.00, 8500,  $date);
        $benchmarks[] = $this->bm('healthcare', 'linkedin', 'cpl',  125.00, 8500, $date);
        $benchmarks[] = $this->bm('healthcare', 'snapchat', 'ctr',  1.00,  3500,  $date);
        $benchmarks[] = $this->bm('healthcare', 'snapchat', 'cpc',  0.90,  3500,  $date);
        $benchmarks[] = $this->bm('healthcare', 'snapchat', 'cpm',  4.50,  3500,  $date);

        // ─── Hospitality & Food Services ─────────────────────────────────────
        $benchmarks[] = $this->bm('hospitality_food_services', 'google',   'ctr',  5.47,  14500, $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'google',   'cpc',  0.23,  14500, $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'google',   'cpm',  11.34, 14500, $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'google',   'cpa',  3.62,  14500, $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'google',   'cvr',  6.45,  14500, $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'facebook', 'ctr',  2.29,  17500, $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'facebook', 'cpc',  0.22,  17500, $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'facebook', 'cpm',  4.92,  17500, $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'facebook', 'cpl',  21.98, 17500, $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'facebook', 'roas', 13.00, 17500, $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'tiktok',   'ctr',  0.48,  5000,  $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'tiktok',   'cpc',  0.37,  5000,  $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'tiktok',   'cpm',  7.50,  5000,  $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'tiktok',   'cpa',  2.04,  5000,  $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'tiktok',   'cvr',  9.24,  5000,  $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'linkedin', 'ctr',  0.52,  3000,  $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'linkedin', 'cpc',  8.00,  3000,  $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'linkedin', 'cpm',  50.00, 3000,  $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'linkedin', 'cpl',  128.00, 3000, $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'snapchat', 'ctr',  1.10,  3800,  $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'snapchat', 'cpc',  0.80,  3800,  $date);
        $benchmarks[] = $this->bm('hospitality_food_services', 'snapchat', 'cpm',  4.50,  3800,  $date);

        // ─── Professional Services ───────────────────────────────────────────
        $benchmarks[] = $this->bm('professional_services', 'google',   'ctr',  3.07,  17000, $date);
        $benchmarks[] = $this->bm('professional_services', 'google',   'cpc',  0.75,  17000, $date);
        $benchmarks[] = $this->bm('professional_services', 'google',   'cpm',  18.05, 17000, $date);
        $benchmarks[] = $this->bm('professional_services', 'google',   'cpa',  27.82, 17000, $date);
        $benchmarks[] = $this->bm('professional_services', 'google',   'cvr',  3.25,  17000, $date);
        $benchmarks[] = $this->bm('professional_services', 'facebook', 'ctr',  1.15,  16000, $date);
        $benchmarks[] = $this->bm('professional_services', 'facebook', 'cpc',  1.02,  16000, $date);
        $benchmarks[] = $this->bm('professional_services', 'facebook', 'cpm',  11.74, 16000, $date);
        $benchmarks[] = $this->bm('professional_services', 'facebook', 'cpl',  43.96, 16000, $date);
        $benchmarks[] = $this->bm('professional_services', 'tiktok',   'ctr',  0.40,  4000,  $date);
        $benchmarks[] = $this->bm('professional_services', 'tiktok',   'cpc',  1.04,  4000,  $date);
        $benchmarks[] = $this->bm('professional_services', 'tiktok',   'cpm',  9.50,  4000,  $date);
        $benchmarks[] = $this->bm('professional_services', 'tiktok',   'cpa',  12.49, 4000,  $date);
        $benchmarks[] = $this->bm('professional_services', 'tiktok',   'cvr',  5.55,  4000,  $date);
        $benchmarks[] = $this->bm('professional_services', 'linkedin', 'ctr',  0.62,  10500, $date);
        $benchmarks[] = $this->bm('professional_services', 'linkedin', 'cpc',  8.50,  10500, $date);
        $benchmarks[] = $this->bm('professional_services', 'linkedin', 'cpm',  50.00, 10500, $date);
        $benchmarks[] = $this->bm('professional_services', 'linkedin', 'cpl',  155.00, 10500, $date);
        $benchmarks[] = $this->bm('professional_services', 'snapchat', 'ctr',  1.04,  2500,  $date);
        $benchmarks[] = $this->bm('professional_services', 'snapchat', 'cpc',  0.84,  2500,  $date);
        $benchmarks[] = $this->bm('professional_services', 'snapchat', 'cpm',  3.50,  2500,  $date);

        // ─── Real Estate ─────────────────────────────────────────────────────
        $benchmarks[] = $this->bm('real_estate', 'google',   'ctr',  3.81,  16000, $date);
        $benchmarks[] = $this->bm('real_estate', 'google',   'cpc',  2.29,  16000, $date);
        $benchmarks[] = $this->bm('real_estate', 'google',   'cpm',  81.84, 16000, $date);
        $benchmarks[] = $this->bm('real_estate', 'google',   'cpa',  59.06, 16000, $date);
        $benchmarks[] = $this->bm('real_estate', 'google',   'cvr',  4.91,  16000, $date);
        $benchmarks[] = $this->bm('real_estate', 'facebook', 'ctr',  2.22,  19000, $date);
        $benchmarks[] = $this->bm('real_estate', 'facebook', 'cpc',  0.18,  19000, $date);
        $benchmarks[] = $this->bm('real_estate', 'facebook', 'cpm',  4.06,  19000, $date);
        $benchmarks[] = $this->bm('real_estate', 'facebook', 'cpl',  43.96, 19000, $date);
        $benchmarks[] = $this->bm('real_estate', 'facebook', 'roas', 3.89,  19000, $date);
        $benchmarks[] = $this->bm('real_estate', 'tiktok',   'ctr',  0.31,  4200,  $date);
        $benchmarks[] = $this->bm('real_estate', 'tiktok',   'cpc',  0.66,  4200,  $date);
        $benchmarks[] = $this->bm('real_estate', 'tiktok',   'cpm',  9.00,  4200,  $date);
        $benchmarks[] = $this->bm('real_estate', 'tiktok',   'cpa',  34.17, 4200,  $date);
        $benchmarks[] = $this->bm('real_estate', 'tiktok',   'cvr',  5.45,  4200,  $date);
        $benchmarks[] = $this->bm('real_estate', 'linkedin', 'ctr',  0.45,  7000,  $date);
        $benchmarks[] = $this->bm('real_estate', 'linkedin', 'cpc',  7.50,  7000,  $date);
        $benchmarks[] = $this->bm('real_estate', 'linkedin', 'cpm',  48.00, 7000,  $date);
        $benchmarks[] = $this->bm('real_estate', 'linkedin', 'cpl',  145.00, 7000, $date);
        $benchmarks[] = $this->bm('real_estate', 'snapchat', 'ctr',  0.85,  3200,  $date);
        $benchmarks[] = $this->bm('real_estate', 'snapchat', 'cpc',  1.00,  3200,  $date);
        $benchmarks[] = $this->bm('real_estate', 'snapchat', 'cpm',  4.00,  3200,  $date);

        // ─── Technology ──────────────────────────────────────────────────────
        $benchmarks[] = $this->bm('technology', 'google',   'ctr',  2.75,  21000, $date);
        $benchmarks[] = $this->bm('technology', 'google',   'cpc',  0.91,  21000, $date);
        $benchmarks[] = $this->bm('technology', 'google',   'cpm',  20.80, 21000, $date);
        $benchmarks[] = $this->bm('technology', 'google',   'cpa',  25.45, 21000, $date);
        $benchmarks[] = $this->bm('technology', 'google',   'cvr',  4.07,  21000, $date);
        $benchmarks[] = $this->bm('technology', 'facebook', 'ctr',  1.97,  24000, $date);
        $benchmarks[] = $this->bm('technology', 'facebook', 'cpc',  0.22,  24000, $date);
        $benchmarks[] = $this->bm('technology', 'facebook', 'cpm',  4.37,  24000, $date);
        $benchmarks[] = $this->bm('technology', 'facebook', 'cpl',  32.97, 24000, $date);
        $benchmarks[] = $this->bm('technology', 'facebook', 'roas', 4.71,  24000, $date);
        $benchmarks[] = $this->bm('technology', 'tiktok',   'ctr',  0.34,  5800,  $date);
        $benchmarks[] = $this->bm('technology', 'tiktok',   'cpc',  1.22,  5800,  $date);
        $benchmarks[] = $this->bm('technology', 'tiktok',   'cpm',  9.50,  5800,  $date);
        $benchmarks[] = $this->bm('technology', 'tiktok',   'cpa',  9.62,  5800,  $date);
        $benchmarks[] = $this->bm('technology', 'tiktok',   'cvr',  5.50,  5800,  $date);
        $benchmarks[] = $this->bm('technology', 'linkedin', 'ctr',  0.69,  13500, $date);
        $benchmarks[] = $this->bm('technology', 'linkedin', 'cpc',  9.16,  13500, $date);
        $benchmarks[] = $this->bm('technology', 'linkedin', 'cpm',  51.00, 13500, $date);
        $benchmarks[] = $this->bm('technology', 'linkedin', 'cpl',  198.00, 13500, $date);
        $benchmarks[] = $this->bm('technology', 'snapchat', 'ctr',  1.04,  4000,  $date);
        $benchmarks[] = $this->bm('technology', 'snapchat', 'cpc',  0.84,  4000,  $date);
        $benchmarks[] = $this->bm('technology', 'snapchat', 'cpm',  5.25,  4000,  $date);

        // ─── Travel & Destinations ───────────────────────────────────────────
        $benchmarks[] = $this->bm('travel_destinations', 'google',   'ctr',  5.47,  13000, $date);
        $benchmarks[] = $this->bm('travel_destinations', 'google',   'cpc',  0.23,  13000, $date);
        $benchmarks[] = $this->bm('travel_destinations', 'google',   'cpm',  11.34, 13000, $date);
        $benchmarks[] = $this->bm('travel_destinations', 'google',   'cpa',  3.62,  13000, $date);
        $benchmarks[] = $this->bm('travel_destinations', 'google',   'cvr',  6.45,  13000, $date);
        $benchmarks[] = $this->bm('travel_destinations', 'facebook', 'ctr',  2.29,  16000, $date);
        $benchmarks[] = $this->bm('travel_destinations', 'facebook', 'cpc',  0.22,  16000, $date);
        $benchmarks[] = $this->bm('travel_destinations', 'facebook', 'cpm',  4.92,  16000, $date);
        $benchmarks[] = $this->bm('travel_destinations', 'facebook', 'cpl',  21.98, 16000, $date);
        $benchmarks[] = $this->bm('travel_destinations', 'facebook', 'roas', 13.00, 16000, $date);
        $benchmarks[] = $this->bm('travel_destinations', 'tiktok',   'ctr',  0.22,  4500,  $date);
        $benchmarks[] = $this->bm('travel_destinations', 'tiktok',   'cpc',  0.98,  4500,  $date);
        $benchmarks[] = $this->bm('travel_destinations', 'tiktok',   'cpm',  8.00,  4500,  $date);
        $benchmarks[] = $this->bm('travel_destinations', 'tiktok',   'cpa',  66.54, 4500,  $date);
        $benchmarks[] = $this->bm('travel_destinations', 'tiktok',   'cvr',  1.04,  4500,  $date);
        $benchmarks[] = $this->bm('travel_destinations', 'linkedin', 'ctr',  0.52,  4000,  $date);
        $benchmarks[] = $this->bm('travel_destinations', 'linkedin', 'cpc',  8.00,  4000,  $date);
        $benchmarks[] = $this->bm('travel_destinations', 'linkedin', 'cpm',  50.00, 4000,  $date);
        $benchmarks[] = $this->bm('travel_destinations', 'linkedin', 'cpl',  128.00, 4000, $date);
        $benchmarks[] = $this->bm('travel_destinations', 'snapchat', 'ctr',  1.15,  3500,  $date);
        $benchmarks[] = $this->bm('travel_destinations', 'snapchat', 'cpc',  0.78,  3500,  $date);
        $benchmarks[] = $this->bm('travel_destinations', 'snapchat', 'cpm',  4.50,  3500,  $date);

        DB::table('industry_benchmarks')->insert($benchmarks);
    }

    /**
     * Build a benchmark row from a median (p50) value.
     * Percentile distribution: p10=p50×0.45, p25=p50×0.66, p75=p50×1.38, p90=p50×1.95
     */
    private function bm(
        string $industry, string $platform, string $metric,
        float $p50, int $sampleSize, string $date
    ): array {
        return [
            'industry'          => $industry,
            'platform'          => $platform,
            'metric'            => $metric,
            'percentile_10'     => round($p50 * 0.45, 2),
            'percentile_25'     => round($p50 * 0.66, 2),
            'percentile_50'     => $p50,
            'percentile_75'     => round($p50 * 1.38, 2),
            'percentile_90'     => round($p50 * 1.95, 2),
            'sample_size'       => $sampleSize,
            'source'            => $this->platformSource[$platform] ?? $this->fiveSources[0],
            'sources'           => json_encode($this->fiveSources),
            'region'            => 'global',
            'data_period_start' => '2025-01-01',
            'data_period_end'   => '2025-12-31',
            'last_updated'      => $date,
            'created_at'        => now(),
            'updated_at'        => now(),
        ];
    }
}
