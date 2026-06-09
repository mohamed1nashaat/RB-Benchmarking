<?php

namespace Database\Seeders;

use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountCategorySeeder extends Seeder
{
    /**
     * Curated semantic hierarchy: each AccountCategory has 2–5 meaningful
     * Sub-Categories that belong only to it.
     */
    private array $hierarchy = [
        'Marketing & Advertising' => [
            'Digital Marketing',
            'Brand Strategy',
            'Creative Agency',
            'Media Buying',
            'SEO / SEM',
        ],
        'Fashion & Apparel' => [
            "Women's Fashion",
            "Men's Fashion",
            "Children's Fashion",
            'Accessories',
            'Luxury Fashion',
        ],
        'Software / SaaS' => [
            'B2B Software',
            'Cloud Services',
            'Productivity Tools',
            'Security Software',
        ],
        'Residential Projects' => [
            'Apartments',
            'Villas',
            'Townhouses',
            'Mixed-Use Developments',
        ],
        'Ministries & Authorities' => [
            'Government Services',
            'Public Administration',
            'Regulatory Bodies',
        ],
        'K–12 Schools' => [
            'Private Schools',
            'International Schools',
            'Public Schools',
        ],
        'Home & Furniture' => [
            'Living Room',
            'Bedroom',
            'Kitchen & Dining',
            'Office Furniture',
            'Outdoor',
        ],
        'Banking' => [
            'Retail Banking',
            'Corporate Banking',
            'Digital Banking',
            'Islamic Banking',
        ],
        'Personal Care' => [
            'Body Care',
            'Haircare',
            'Wellness',
            'Hygiene Products',
        ],
        'Investments & Wealth Management' => [
            'Asset Management',
            'Private Equity',
            'Wealth Advisory',
            'Brokerage',
        ],
        'Local Attractions & Experiences' => [
            'Museums',
            'Adventure Parks',
            'Cultural Sites',
            'Entertainment',
        ],
        'Consulting' => [
            'Management Consulting',
            'IT Consulting',
            'Financial Advisory',
            'HR Consulting',
        ],
        'Hotels & Resorts' => [
            'Luxury Hotels',
            'Business Hotels',
            'Resorts',
            'Boutique Hotels',
        ],
        'Training & Certification Centers' => [
            'Corporate Training',
            'Professional Certification',
            'Vocational Training',
        ],
        'Direct-to-Consumer (D2C)' => [
            'E-commerce Brand',
            'Subscription Service',
            'Online Retail',
        ],
        'Restaurants & Cafes' => [
            'Fine Dining',
            'Fast Casual',
            'Cafés',
            'Food Trucks',
            'Cloud Kitchen',
        ],
        'Fintech' => [
            'Payments',
            'Lending',
            'Insurtech',
            'Cryptocurrency',
            'Neobanking',
        ],
        'Grocery & Food Delivery' => [
            'Supermarkets',
            'Meal Kits',
            'Quick Commerce',
            'Organic Foods',
        ],
        'Luxury Goods' => [
            'Watches & Jewelry',
            'Designer Fashion',
            'Luxury Home',
            'Collectibles',
        ],
        'Real Estate Agencies' => [
            'Residential Sales',
            'Commercial Leasing',
            'Property Management',
        ],
        'Hospitals & Clinics' => [
            'General Hospitals',
            'Specialized Clinics',
            'Diagnostic Centers',
            'Dental',
        ],
        'Online Courses / EdTech' => [
            'E-Learning Platforms',
            'Corporate Training Tech',
            'Language Learning',
            'Test Prep',
        ],
        'Vehicles' => [
            'New Cars',
            'Used Cars',
            'Luxury Vehicles',
            'Commercial Vehicles',
        ],
        'Designer Apparel' => [
            'Haute Couture',
            'Ready-to-Wear',
            'Sustainable Fashion',
            'Streetwear',
        ],
        'Beauty & Cosmetics' => [
            'Makeup',
            'Skincare',
            'Fragrances',
            'Professional Salon',
        ],
        'Food & Beverages' => [
            'Beverages',
            'Snacks',
            'Dairy',
            'Frozen Foods',
            'Organic',
        ],
        'Telecommunications' => [
            'Mobile Networks',
            'Internet Service',
            'Enterprise Solutions',
            'IoT',
        ],
        'Tourism Boards' => [
            'Destination Marketing',
            'Travel Agencies',
            'Tour Operators',
        ],
        'Sports & Events' => [
            'Sporting Goods',
            'Event Management',
            'Fitness Centers',
            'Ticketing',
        ],
    ];

    /**
     * Map old ad_accounts.sub_industry values to preferred new sub-category names.
     * Keys are lowercase for case-insensitive matching.
     */
    private array $legacySubIndustryMap = [
        'consulting' => 'Management Consulting',
        'fashion & apparel' => "Women's Fashion",
        'software / saas' => 'B2B Software',
        'residential projects' => 'Apartments',
        'ministries & authorities' => 'Government Services',
        'k–12 schools' => 'Private Schools',
        'banking' => 'Retail Banking',
        'food & beverages' => 'Beverages',
        'restaurants & cafes' => 'Fine Dining',
        'tourism boards' => 'Destination Marketing',
        'hospitals & clinics' => 'General Hospitals',
        'luxury goods' => 'Watches & Jewelry',
        'vehicles' => 'New Cars',
        'streaming services' => 'Event Management',
        'marketing & advertising' => 'Digital Marketing',
        'personal care' => 'Body Care',
    ];

    public function run(): void
    {
        // Wipe old sub-categories and re-seed with proper hierarchy
        DB::table('account_sub_categories')->truncate();

        $categories = AccountCategory::all()->keyBy('display_name');

        foreach ($this->hierarchy as $categoryDisplayName => $subCategories) {
            $category = $categories->get($categoryDisplayName);

            if (! $category) {
                $this->command->warn("Category not found: {$categoryDisplayName}");
                continue;
            }

            foreach ($subCategories as $index => $subDisplayName) {
                AccountSubCategory::create([
                    'account_category_id' => $category->id,
                    'name' => Str::slug($subDisplayName, '_'),
                    'display_name' => $subDisplayName,
                    'description' => null,
                    'is_active' => true,
                    'sort_order' => $index,
                ]);
            }
        }

        $this->migrateAdAccountSubIndustries();

        $totalCategories = AccountCategory::count();
        $totalSubCategories = AccountSubCategory::count();
        $this->command->info("Seeded {$totalCategories} account categories with {$totalSubCategories} sub-categories.");
    }

    private function migrateAdAccountSubIndustries(): void
    {
        // Build a lookup: category_id => [sub_category_name => sub_category_display_name]
        $subCategoriesByCategory = AccountSubCategory::all()
            ->groupBy('account_category_id')
            ->map(fn ($subs) => $subs->keyBy(fn ($s) => strtolower($s->display_name)));

        // Get all ad_accounts with a category and sub_industry
        $accounts = DB::table('ad_accounts')
            ->whereNotNull('category')
            ->whereNotNull('sub_industry')
            ->select('id', 'category', 'sub_industry')
            ->get();

        $updated = 0;
        $nulled = 0;

        // Map category display_name => id
        $categoryIds = AccountCategory::pluck('id', 'display_name');

        foreach ($accounts as $account) {
            $categoryId = $categoryIds->get($account->category);

            if (! $categoryId) {
                DB::table('ad_accounts')->where('id', $account->id)->update(['sub_industry' => null]);
                $nulled++;
                continue;
            }

            $availableSubs = $subCategoriesByCategory->get($categoryId);

            if (! $availableSubs || $availableSubs->isEmpty()) {
                DB::table('ad_accounts')->where('id', $account->id)->update(['sub_industry' => null]);
                $nulled++;
                continue;
            }

            // 1. Try exact match on legacy map
            $mappedName = $this->legacySubIndustryMap[strtolower($account->sub_industry)] ?? null;

            if ($mappedName) {
                $matchedSub = $availableSubs->get(strtolower($mappedName));
                if ($matchedSub) {
                    DB::table('ad_accounts')->where('id', $account->id)->update(['sub_industry' => $matchedSub->name]);
                    $updated++;
                    continue;
                }
            }

            // 2. Try fuzzy match: does any new sub-category display name contain the old value (or vice versa)?
            $oldLower = strtolower($account->sub_industry);
            $matchedSub = $availableSubs->first(function ($sub) use ($oldLower) {
                return str_contains(strtolower($sub->display_name), $oldLower)
                    || str_contains($oldLower, strtolower($sub->display_name));
            });

            if ($matchedSub) {
                DB::table('ad_accounts')->where('id', $account->id)->update(['sub_industry' => $matchedSub->name]);
                $updated++;
                continue;
            }

            // 3. Fallback: set to null (old data was illogical anyway)
            DB::table('ad_accounts')->where('id', $account->id)->update(['sub_industry' => null]);
            $nulled++;
        }

        $this->command->info("Migrated ad_accounts.sub_industry: {$updated} matched, {$nulled} nulled.");
    }
}
