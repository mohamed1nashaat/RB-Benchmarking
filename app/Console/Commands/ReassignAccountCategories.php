<?php

namespace App\Console\Commands;

use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\Industry;
use App\Services\BusinessLookupService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ReassignAccountCategories extends Command
{
    protected $signature = 'accounts:reassign-categories
                            {--dry-run : Preview changes without saving}
                            {--only-accounts : Only update accounts, not campaigns}
                            {--force : Update even if account already has valid category/sub-category}';

    protected $description = 'Reassign account categories and sub-categories from account names and known mappings, then propagate to campaigns';

    /**
     * Brand/client keyword → [industry_name, category_display, sub_category_display]
     */
    private array $brandMappings = [
        // E-commerce / Retail
        'kohler' => ['e_commerce_retail', 'Home & Furniture', 'Kitchen & Dining'],
        'roca' => ['e_commerce_retail', 'Home & Furniture', 'Home Decor'],
        'mancini' => ['e_commerce_retail', 'Home & Furniture', 'Bedroom'],
        'sleepworld' => ['e_commerce_retail', 'Home & Furniture', 'Bedroom'],
        'peony' => ['e_commerce_retail', 'Fashion & Apparel', "Women's Fashion"],
        'al-shiaka' => ['e_commerce_retail', 'Fashion & Apparel', "Men's Fashion"],
        'alshiaka' => ['e_commerce_retail', 'Fashion & Apparel', "Men's Fashion"],
        'shiaka' => ['e_commerce_retail', 'Fashion & Apparel', "Men's Fashion"],
        'al-itihad' => ['entertainment_media', 'Sports & Events', 'Professional Leagues'],
        'al-ittihad' => ['entertainment_media', 'Sports & Events', 'Professional Leagues'],
        'al-ittihad-fc' => ['entertainment_media', 'Sports & Events', 'Professional Leagues'],
        'testahel' => ['e_commerce_retail', 'Direct-to-Consumer (D2C)', 'Food & Drink D2C'],
        'nashar' => ['e_commerce_retail', 'Grocery & Food Delivery', 'Packaged Foods'],
        'alshifa honey' => ['e_commerce_retail', 'Grocery & Food Delivery', 'Packaged Foods'],
        'sunbulah' => ['e_commerce_retail', 'Grocery & Food Delivery', 'Packaged Foods'],

        // Hospitality / Food
        'shangrila' => ['travel_destinations', 'Hotels & Resorts', 'Luxury Hotels'],
        'shangri-la' => ['travel_destinations', 'Hotels & Resorts', 'Luxury Hotels'],
        'barn\'s' => ['fmcg_cpg', 'Food & Beverages', 'Beverages'],
        'ribsyard' => ['hospitality_food_services', 'Restaurants & Cafes', 'Casual Dining'],
        'ribs yard' => ['hospitality_food_services', 'Restaurants & Cafes', 'Casual Dining'],

        // Real Estate
        'al-diyar' => ['real_estate', 'Residential Projects', 'Apartments'],
        'alrashid' => ['real_estate', 'Residential Projects', 'Apartments'],
        'dar al arkan' => ['real_estate', 'Residential Projects', 'Apartments'],
        'qudra' => ['real_estate', 'Residential Projects', 'Villas'],
        'hajar' => ['real_estate', 'Residential Projects', 'Villas'],
        'burj assila' => ['real_estate', 'Residential Projects', 'Apartments'],
        'faisal bin saedan' => ['real_estate', 'Residential Projects', 'Mixed-Use Developments'],
        'faisal bin saadan' => ['real_estate', 'Residential Projects', 'Mixed-Use Developments'],
        'mahawer' => ['real_estate', 'Residential Projects', 'Apartments'],
        'mawten' => ['real_estate', 'Residential Projects', 'Apartments'],
        'alfursan' => ['real_estate', 'Residential Projects', 'Villas'],
        'arabian dyar' => ['real_estate', 'Residential Projects', 'Apartments'],
        'rua al madinah' => ['real_estate', 'Residential Projects', 'Apartments'],
        'ruaa al-madienah' => ['real_estate', 'Residential Projects', 'Apartments'],

        // Finance
        'sedco' => ['finance_insurance', 'Investments & Wealth Management', 'Wealth Advisory'],
        'rowad' => ['finance_insurance', 'Investments & Wealth Management', 'Private Equity'],
        'waed' => ['finance_insurance', 'Investments & Wealth Management', 'Private Equity'],
        'wa\'ed' => ['finance_insurance', 'Investments & Wealth Management', 'Private Equity'],
        'kadipay' => ['finance_insurance', 'Fintech', 'Neobanking'],
        'monshaat' => ['finance_insurance', 'Fintech', 'Lending Platforms'],

        // Technology / Software
        'zkra' => ['technology', 'Software / SaaS', 'B2B Software'],
        'mainzilia' => ['technology', 'Software / SaaS', 'B2B Software'],
        'mainzilla' => ['technology', 'Software / SaaS', 'B2B Software'],
        'hala' => ['technology', 'Software / SaaS', 'B2B Software'],
        'shift' => ['technology', 'Software / SaaS', 'B2B Software'],
        'digital mobility' => ['technology', 'Software / SaaS', 'B2B Software'],
        'viatra' => ['technology', 'Software / SaaS', 'B2B Software'],
        'nahr' => ['technology', 'Software / SaaS', 'Productivity Tools'],
        'rmeez' => ['technology', 'Software / SaaS', 'B2B Software'],

        // Healthcare
        'cura' => ['healthcare_pharmaceuticals', 'Hospitals & Clinics', 'Specialty Clinics'],
        'saudi moh' => ['government_public_sector', 'Ministries & Authorities', 'Government Services'],

        // Education
        'jadara' => ['education', 'K–12 Schools', 'Private Schools'],
        'jaddarah' => ['education', 'K–12 Schools', 'Private Schools'],
        'waad academy' => ['education', 'K–12 Schools', 'Private Schools'],
        'rowad journey' => ['education', 'Training & Certification Centers', 'Corporate Training'],

        // Travel / Destinations
        'jeddah al balad' => ['travel_destinations', 'Local Attractions & Experiences', 'Cultural Sites'],
        'al balad' => ['travel_destinations', 'Local Attractions & Experiences', 'Cultural Sites'],
        'teamLab' => ['travel_destinations', 'Local Attractions & Experiences', 'Entertainment'],
        'borderless' => ['travel_destinations', 'Local Attractions & Experiences', 'Entertainment'],
        'makkah expo' => ['entertainment_media', 'Sports & Events', 'Event Management'],
        'monshaat' => ['government_public_sector', 'Ministries & Authorities', 'Government Services'],
        'school' => ['education', 'K–12 Schools', 'Private Schools'],
        'jadarah school' => ['education', 'K–12 Schools', 'Private Schools'],
        'jaddarah school' => ['education', 'K–12 Schools', 'Private Schools'],
        'jaddarah' => ['education', 'K–12 Schools', 'Private Schools'],
        'waad academy' => ['education', 'K–12 Schools', 'Private Schools'],
        'waad-dar althikr' => ['education', 'K–12 Schools', 'Private Schools'],

        // FMCG / CPG
        'spa ceylon' => ['fmcg_cpg', 'Personal Care', 'Body Care'],

        // Automotive
        'autohub' => ['automotive', 'Auto Dealers & Retail', 'Franchise Dealers'],

        // Telecommunications
        'telecom forum' => ['telecommunications', 'Mobile Carriers', 'Postpaid Plans'],

        // Professional / Agency
        'red banana' => ['professional_services', 'Marketing & Advertising', 'Digital Marketing'],
        'red bananas' => ['professional_services', 'Marketing & Advertising', 'Digital Marketing'],
        'rb benchmarks' => ['professional_services', 'Marketing & Advertising', 'Digital Marketing'],
        'rb' => ['professional_services', 'Marketing & Advertising', 'Digital Marketing'],
        'bc' => ['professional_services', 'Marketing & Advertising', 'Digital Marketing'],
        'cas' => ['professional_services', 'Consulting', 'Management Consulting'],
        'ghs' => ['professional_services', 'Consulting', 'IT Consulting'],
        'mohamed nashaat' => ['professional_services', 'Consulting', 'Management Consulting'],

        // Government
        'hrc' => ['government_public_sector', 'Ministries & Authorities', 'Government Services'],
    ];

    /**
     * Display name patterns in account names that map directly to AccountCategory display names.
     */
    private array $categoryPatterns = [
        'home & furniture' => ['e_commerce_retail', 'Home & Furniture', 'Home Decor'],
        'grocery & food delivery' => ['e_commerce_retail', 'Grocery & Food Delivery', 'Packaged Foods'],
        'fashion & apparel' => ['e_commerce_retail', 'Fashion & Apparel', "Women's Fashion"],
        'beauty & cosmetics' => ['e_commerce_retail', 'Beauty & Cosmetics', 'Skincare'],
        'direct-to-consumer' => ['e_commerce_retail', 'Direct-to-Consumer (D2C)', 'D2C Brands'],
        'restaurants & cafes' => ['hospitality_food_services', 'Restaurants & Cafes', 'Casual Dining'],
        'hotels & resorts' => ['travel_destinations', 'Hotels & Resorts', 'Luxury Hotels'],
        'local attractions' => ['travel_destinations', 'Local Attractions & Experiences', 'Cultural Sites'],
        'residential projects' => ['real_estate', 'Residential Projects', 'Apartments'],
        'real estate' => ['real_estate', 'Residential Projects', 'Apartments'],
        'software / saas' => ['technology', 'Software / SaaS', 'B2B Software'],
        'banking' => ['finance_insurance', 'Banking', 'Retail Banking'],
        'fintech' => ['finance_insurance', 'Fintech', 'Payments'],
        'investments & wealth' => ['finance_insurance', 'Investments & Wealth Management', 'Wealth Advisory'],
        'consulting' => ['professional_services', 'Consulting', 'Management Consulting'],
        'marketing & advertising' => ['professional_services', 'Marketing & Advertising', 'Digital Marketing'],
        'food & beverages' => ['fmcg_cpg', 'Food & Beverages', 'Beverages'],
        'k-12 schools' => ['education', 'K–12 Schools', 'Private Schools'],
        'training & certification' => ['education', 'Training & Certification Centers', 'Corporate Training'],
        'hospitals & clinics' => ['healthcare_pharmaceuticals', 'Hospitals & Clinics', 'General Hospitals'],
    ];

    private array $validIndustryCategories = [];
    private array $defaultSubCategories = [];
    private array $noMatchAccounts = [];
    private array $sampleUpdates = [];

    public function handle(): int
    {
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Reassign Account Categories & Sub-Categories');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');

        $isDryRun = $this->option('dry-run');
        $onlyAccounts = $this->option('only-accounts');
        $force = $this->option('force');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
            $this->info('');
        }

        $this->buildValidationCache();

        $accounts = AdAccount::all();
        $this->info("Processing {$accounts->count()} accounts...");
        $this->info('');

        $stats = [
            'accounts_processed' => 0,
            'accounts_updated' => 0,
            'accounts_no_match' => 0,
            'accounts_skipped_valid' => 0,
            'campaigns_updated' => 0,
        ];

        $progressBar = $this->output->createProgressBar($accounts->count());
        $progressBar->start();

        foreach ($accounts as $account) {
            $stats['accounts_processed']++;

            $determined = $this->determineCategory($account);

            if (!$determined) {
                // Keep existing if valid and not forcing
                if (!$force && $this->isValidClassification($account)) {
                    $stats['accounts_skipped_valid']++;
                } else {
                    $stats['accounts_no_match']++;
                    $this->noMatchAccounts[] = $account->account_name;
                }
                $progressBar->advance();
                continue;
            }

            [$industryName, $categoryDisplay, $subCategoryDisplay] = $determined;

            // If not forcing, skip if already correctly classified
            if (!$force && $this->isAlreadyClassified($account, $categoryDisplay, $subCategoryDisplay)) {
                $stats['accounts_skipped_valid']++;
                $progressBar->advance();
                continue;
            }

            $this->sampleUpdates[] = [
                'name' => $account->account_name,
                'old' => ($account->industry ?? '-') . ' / ' . ($account->category ?? '-') . ' / ' . ($account->sub_industry ?? '-'),
                'new' => $industryName . ' / ' . $categoryDisplay . ' / ' . $this->slugify($subCategoryDisplay),
            ];

            if (!$isDryRun) {
                $account->update([
                    'industry' => $industryName,
                    'category' => $categoryDisplay,
                    'sub_industry' => $this->slugify($subCategoryDisplay),
                ]);

                if (!$onlyAccounts) {
                    $updatedCount = AdCampaign::where('ad_account_id', $account->id)
                        ->update(['sub_industry' => $subCategoryDisplay]);
                    $stats['campaigns_updated'] += $updatedCount;
                }
            }

            $stats['accounts_updated']++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info('');
        $this->info('');

        $this->info('Stats:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Accounts processed', $stats['accounts_processed']],
                ['Accounts updated', $stats['accounts_updated']],
                ['Accounts skipped (already valid)', $stats['accounts_skipped_valid']],
                ['Accounts with no match', $stats['accounts_no_match']],
                ['Campaigns updated', $stats['campaigns_updated']],
            ]
        );

        if (!empty($this->sampleUpdates)) {
            $this->info('');
            $this->info('Sample updates (showing up to 30):');
            $this->table(
                ['Account', 'Old', 'New'],
                array_map(fn($u) => [
                    Str::limit($u['name'], 35),
                    Str::limit($u['old'], 45),
                    Str::limit($u['new'], 45),
                ], array_slice($this->sampleUpdates, 0, 30))
            );
        }

        if (!empty($this->noMatchAccounts)) {
            $this->info('');
            $this->warn('Accounts with no match (need manual assignment):');
            foreach ($this->noMatchAccounts as $name) {
                $this->line("  - {$name}");
            }
        }

        return 0;
    }

    private function buildValidationCache(): void
    {
        $this->validIndustryCategories = [];
        $this->defaultSubCategories = [];

        $categories = AccountCategory::with('subCategories')->get();
        foreach ($categories as $category) {
            $key = strtolower($category->display_name);
            $this->validIndustryCategories[$key] = [
                'industry' => $category->industry?->name,
                'category' => $category->display_name,
            ];

            // Default to first active sub-category
            $defaultSub = $category->subCategories->first();
            if ($defaultSub) {
                $this->defaultSubCategories[$key] = $defaultSub->display_name;
            }
        }
    }

    private function determineCategory(AdAccount $account): ?array
    {
        $nameLower = strtolower($account->account_name);

        // 1. Check explicit category patterns in account name
        foreach ($this->categoryPatterns as $pattern => $mapping) {
            if (str_contains($nameLower, strtolower($pattern))) {
                return $this->validateMapping($mapping);
            }
        }

        // 2. Check brand/client keyword mappings (longest first)
        $brands = $this->brandMappings;
        uksort($brands, fn($a, $b) => strlen($b) <=> strlen($a));

        foreach ($brands as $brand => $mapping) {
            if (str_contains($nameLower, strtolower($brand))) {
                return $this->validateMapping($mapping);
            }
        }

        // 3. Try BusinessLookupService fallback
        try {
            $lookup = app(BusinessLookupService::class)->lookupBusiness($account->account_name);
            if (!empty($lookup['industry']) && !empty($lookup['category'])) {
                $industryName = $this->mapBusinessLookupIndustry($lookup['industry']);
                if ($industryName) {
                    $subCategory = $this->defaultSubCategories[strtolower($lookup['category'])] ?? null;
                    return $this->validateMapping([$industryName, $lookup['category'], $subCategory ?? 'General']);
                }
            }
        } catch (\Throwable $e) {
            // Ignore lookup failures
        }

        return null;
    }

    private function validateMapping(array $mapping): ?array
    {
        [$industryName, $categoryDisplay, $subCategoryDisplay] = $mapping;

        // Verify industry exists
        $industry = Industry::where('name', $industryName)->first();
        if (!$industry) {
            return null;
        }

        // Verify category exists under industry
        $category = AccountCategory::where('display_name', $categoryDisplay)
            ->where('industry_id', $industry->id)
            ->first();
        if (!$category) {
            return null;
        }

        // Verify sub-category exists under category
        $subCategory = AccountSubCategory::where('display_name', $subCategoryDisplay)
            ->where('account_category_id', $category->id)
            ->first();
        if (!$subCategory) {
            // Fall back to first sub-category
            $subCategory = $category->subCategories()->first();
            if (!$subCategory) {
                return null;
            }
        }

        return [$industryName, $categoryDisplay, $subCategory->display_name];
    }

    private function isValidClassification(AdAccount $account): bool
    {
        if (empty($account->industry) || empty($account->category)) {
            return false;
        }

        $industry = Industry::where('name', $account->industry)->first();
        if (!$industry) {
            return false;
        }

        $category = AccountCategory::where('display_name', $account->category)
            ->where('industry_id', $industry->id)
            ->first();

        return $category !== null;
    }

    private function isAlreadyClassified(AdAccount $account, string $categoryDisplay, string $subCategoryDisplay): bool
    {
        return $account->category === $categoryDisplay
            && $account->sub_industry === $this->slugify($subCategoryDisplay);
    }

    private function mapBusinessLookupIndustry(string $externalIndustry): ?string
    {
        return BusinessLookupService::mapExternalIndustry($externalIndustry);
    }

    private function slugify(string $displayName): string
    {
        return Str::slug($displayName, '_');
    }
}
