<?php

namespace App\Services;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdAccountCategoryService
{
    private CategoryMapper $categoryMapper;

    public function __construct(CategoryMapper $categoryMapper)
    {
        $this->categoryMapper = $categoryMapper;
    }

    /**
     * Auto-populate categories for ad accounts based on their campaigns
     */
    public function autoPopulateAccountCategories(?int $accountId = null): array
    {
        $query = AdAccount::query();

        // If specific account ID provided, only process that account
        if ($accountId) {
            $query->where('id', $accountId);
        } else {
            // Otherwise, only process accounts without categories
            $query->whereNull('category');
        }

        $accounts = $query->get();

        $stats = [
            'total_processed' => 0,
            'total_updated' => 0,
            'total_skipped' => 0,
            'accounts' => []
        ];

        foreach ($accounts as $account) {
            $stats['total_processed']++;

            try {
                $result = $this->populateCategoryForAccount($account);

                $stats['accounts'][$account->id] = $result;

                if ($result['updated']) {
                    $stats['total_updated']++;
                } else {
                    $stats['total_skipped']++;
                }

            } catch (\Exception $e) {
                Log::error('Failed to populate category for account', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage()
                ]);

                $stats['accounts'][$account->id] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $stats['total_skipped']++;
            }
        }

        return $stats;
    }

    /**
     * Populate category for a specific account based on its campaigns
     */
    private function populateCategoryForAccount(AdAccount $account): array
    {
        // Get all campaigns for this account with categories
        $campaigns = AdCampaign::where('ad_account_id', $account->id)
            ->whereNotNull('category')
            ->get();

        if ($campaigns->isEmpty()) {
            // No campaigns with categories - try to use industry to suggest default
            $defaultCategory = $this->getDefaultCategoryFromIndustry($account->industry);

            if ($defaultCategory) {
                $account->category = $defaultCategory;
                $account->save();

                Log::info('Set default category from industry for account', [
                    'account_id' => $account->id,
                    'account_name' => $account->account_name,
                    'industry' => $account->industry,
                    'category' => $defaultCategory
                ]);

                return [
                    'success' => true,
                    'updated' => true,
                    'category' => $defaultCategory,
                    'source' => 'industry_default',
                    'campaigns_analyzed' => 0
                ];
            }

            return [
                'success' => true,
                'updated' => false,
                'reason' => 'no_campaigns_with_categories',
                'campaigns_analyzed' => 0
            ];
        }

        // Count occurrences of each category
        $categoryCounts = [];
        foreach ($campaigns as $campaign) {
            $category = $campaign->category;
            if ($category) {
                $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
            }
        }

        // Find the most common category
        arsort($categoryCounts);
        $mostCommonCategory = array_key_first($categoryCounts);
        $categoryCount = $categoryCounts[$mostCommonCategory];

        // Set the most common category as the account's category
        $account->category = $mostCommonCategory;
        $account->save();

        Log::info('Auto-populated category for account from campaigns', [
            'account_id' => $account->id,
            'account_name' => $account->account_name,
            'category' => $mostCommonCategory,
            'campaigns_with_category' => $categoryCount,
            'total_campaigns' => $campaigns->count(),
            'category_distribution' => $categoryCounts
        ]);

        return [
            'success' => true,
            'updated' => true,
            'category' => $mostCommonCategory,
            'source' => 'campaigns',
            'campaigns_analyzed' => $campaigns->count(),
            'campaigns_with_this_category' => $categoryCount,
            'category_distribution' => $categoryCounts
        ];
    }

    /**
     * Get default category suggestion based on industry
     */
    private function getDefaultCategoryFromIndustry(?string $industry): ?string
    {
        if (!$industry) {
            return null;
        }

        // Map industries to default categories
        $industryToCategoryMap = [
            'real_estate' => 'Residential Projects',
            'retail_ecommerce' => 'Fashion & Apparel',
            'technology' => 'Software & Technology',
            'education' => 'K-12 Schools',
            'healthcare' => 'Medical Services',
            'automotive' => 'Car Dealerships',
            'home_garden' => 'Home & Furniture',
            'finance_insurance' => 'Financial Services',
            'travel_hospitality' => 'Tourism Boards',
            'food_beverage' => 'Restaurants & Cafes',
            'sports_fitness' => 'Fitness Centers',
            'arts_entertainment' => 'Entertainment Venues',
            'professional_services' => 'Professional Services',
            'non_profit' => 'Non-Profit Organizations',
            'business_industrial' => 'B2B Services',
            // Additional industry mappings
            'entertainment' => 'Entertainment Venues',
            'finance' => 'Financial Services',
            'travel_tourism' => 'Tourism Boards',
            'media_publishing' => 'Media & Publishing',
            'sports_recreation' => 'Sports Organizations',
            'beauty_fitness' => 'Beauty & Wellness',
            'health_medicine' => 'Medical Services',
        ];

        return $industryToCategoryMap[$industry] ?? null;
    }

    /**
     * Populate a specific account's category
     */
    public function populateCategory(int $accountId): array
    {
        $account = AdAccount::find($accountId);

        if (!$account) {
            return [
                'success' => false,
                'error' => 'Account not found'
            ];
        }

        return $this->populateCategoryForAccount($account);
    }
}
