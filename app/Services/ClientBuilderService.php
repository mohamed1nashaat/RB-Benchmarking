<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\AdAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientBuilderService
{
    /**
     * Analyze ad accounts and suggest client information
     */
    public function suggestClientInfo(array $accountIds): array
    {
        $accounts = AdAccount::whereIn('id', $accountIds)
            ->with('integration')
            ->get();

        if ($accounts->isEmpty()) {
            return $this->getEmptySuggestions();
        }

        // Detect patterns and generate suggestions
        $industry = $this->detectIndustry($accounts);
        $companyName = $this->detectCompanyName($accounts);
        $platforms = $this->getPlatforms($accounts);
        $totalSpend = $this->calculateTotalSpend($accounts);
        $subscriptionTier = $this->inferSubscriptionTier($accounts->count(), $totalSpend);
        $monthlyBudget = $this->calculateRecommendedBudget($totalSpend);
        $contactInfo = $this->extractContactInfo($accounts);

        return [
            'suggested' => [
                'name' => $companyName,
                'industry' => $industry,
                'description' => $this->generateDescription($accounts->count(), $platforms, $industry),
                'subscription_tier' => $subscriptionTier,
                'monthly_budget' => $monthlyBudget,
                'contact_info' => $contactInfo,
            ],
            'accounts_summary' => [
                'total_accounts' => $accounts->count(),
                'platforms' => array_values($platforms),
                'total_spend' => $totalSpend,
                'industries' => $this->getIndustries($accounts),
                'active_accounts' => $accounts->where('status', 'active')->count(),
            ],
        ];
    }

    /**
     * Detect most common industry from accounts
     */
    public function detectIndustry($accounts): ?string
    {
        $industries = $accounts->pluck('industry')->filter()->toArray();

        if (empty($industries)) {
            return null;
        }

        // Get most common industry
        $industryCounts = array_count_values($industries);
        arsort($industryCounts);

        return array_key_first($industryCounts);
    }

    /**
     * Detect company name from account names
     */
    public function detectCompanyName($accounts): string
    {
        // Try to find common prefix/pattern in account names
        $names = $accounts->pluck('account_name')->toArray();

        if (empty($names)) {
            return 'New Client';
        }

        // If only one account, clean up the name
        if (count($names) === 1) {
            return $this->cleanCompanyName($names[0]);
        }

        // Find common prefix in account names
        $commonPrefix = $this->findCommonPrefix($names);

        if ($commonPrefix && strlen($commonPrefix) > 3) {
            return $this->cleanCompanyName($commonPrefix);
        }

        // Fallback: use first account name
        return $this->cleanCompanyName($names[0]);
    }

    /**
     * Get unique platforms from accounts
     */
    private function getPlatforms($accounts): array
    {
        return $accounts->pluck('integration.platform')
            ->unique()
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Calculate total spend across accounts
     */
    private function calculateTotalSpend($accounts): float
    {
        $accountIds = $accounts->pluck('id');

        return (float) DB::table('ad_metrics')
            ->whereIn('ad_account_id', $accountIds)
            ->sum('spend');
    }

    /**
     * Infer subscription tier based on account count and spend
     */
    public function inferSubscriptionTier(int $accountCount, float $totalSpend): string
    {
        // Enterprise: 16+ accounts OR spend > 100k
        if ($accountCount >= 16 || $totalSpend > 100000) {
            return 'enterprise';
        }

        // Pro: 6-15 accounts OR spend > 25k
        if ($accountCount >= 6 || $totalSpend > 25000) {
            return 'pro';
        }

        // Basic: 1-5 accounts OR spend <= 25k
        return 'basic';
    }

    /**
     * Calculate recommended monthly budget
     */
    public function calculateRecommendedBudget(float $totalSpend): float
    {
        if ($totalSpend === 0) {
            return 5000; // Default
        }

        // Get average monthly spend from last 6 months
        $monthlyAverage = $totalSpend / 12; // Rough estimate

        // Round up to nearest 1000
        return ceil($monthlyAverage / 1000) * 1000;
    }

    /**
     * Extract contact information from accounts
     */
    private function extractContactInfo($accounts): array
    {
        $emails = [];
        $phones = [];

        foreach ($accounts as $account) {
            $config = $account->account_config ?? [];

            // Look for email patterns in config
            if (isset($config['contact_email'])) {
                $emails[] = $config['contact_email'];
            }

            if (isset($config['email'])) {
                $emails[] = $config['email'];
            }

            // Look for phone patterns
            if (isset($config['phone'])) {
                $phones[] = $config['phone'];
            }
        }

        return [
            'emails_found' => array_unique(array_filter($emails)),
            'phones_found' => array_unique(array_filter($phones)),
        ];
    }

    /**
     * Generate client description
     */
    private function generateDescription(int $accountCount, array $platforms, ?string $industry): string
    {
        $platformText = count($platforms) > 0
            ? implode(', ', array_map('ucfirst', $platforms))
            : 'multiple platforms';

        $industryText = $industry ? ' in the ' . str_replace('_', ' ', $industry) . ' industry' : '';

        return "{$accountCount} advertising " . Str::plural('account', $accountCount)
               . " across {$platformText}{$industryText}";
    }

    /**
     * Get all unique industries
     */
    private function getIndustries($accounts): array
    {
        return $accounts->pluck('industry')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Clean company name from account name
     */
    private function cleanCompanyName(string $name): string
    {
        // Remove common suffixes
        $suffixes = [' - Facebook', ' - Google', ' - Instagram', ' FB', ' IG', ' ADS', ' Ads'];
        foreach ($suffixes as $suffix) {
            $name = str_ireplace($suffix, '', $name);
        }

        // Remove trailing numbers/dates
        $name = preg_replace('/\s+\d{4}$/', '', $name);
        $name = preg_replace('/\s+\d{1,2}$/', '', $name);

        // Trim and title case
        $name = trim($name);

        return $name ?: 'New Client';
    }

    /**
     * Find common prefix in array of strings
     */
    private function findCommonPrefix(array $strings): string
    {
        if (empty($strings)) {
            return '';
        }

        $prefix = $strings[0];
        $length = strlen($prefix);

        foreach ($strings as $string) {
            $length = min($length, strlen($string));

            for ($i = 0; $i < $length; $i++) {
                if ($prefix[$i] !== $string[$i]) {
                    $length = $i;
                    break;
                }
            }

            $prefix = substr($prefix, 0, $length);
        }

        // Trim to last complete word
        $lastSpace = strrpos(trim($prefix), ' ');
        if ($lastSpace !== false) {
            $prefix = substr($prefix, 0, $lastSpace);
        }

        return trim($prefix);
    }

    /**
     * Get empty suggestions structure
     */
    private function getEmptySuggestions(): array
    {
        return [
            'suggested' => [
                'name' => 'New Client',
                'industry' => null,
                'description' => '',
                'subscription_tier' => 'basic',
                'monthly_budget' => 5000,
                'contact_info' => [
                    'emails_found' => [],
                    'phones_found' => [],
                ],
            ],
            'accounts_summary' => [
                'total_accounts' => 0,
                'platforms' => [],
                'total_spend' => 0,
                'industries' => [],
                'active_accounts' => 0,
            ],
        ];
    }

    /**
     * Populate existing tenant with client data from its ad accounts
     */
    public function populateTenantFromAccounts(Tenant $tenant, bool $force = false): array
    {
        $accounts = $tenant->adAccounts;

        if ($accounts->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No ad accounts found for this tenant',
                'changes' => [],
            ];
        }

        $suggestions = $this->suggestClientInfo($accounts->pluck('id')->toArray());
        $changes = [];

        // Only update empty/null fields unless force is true
        $updateData = [];

        if ($force || empty($tenant->industry)) {
            $updateData['industry'] = $suggestions['suggested']['industry'];
            $changes[] = 'industry';
        }

        if ($force || empty($tenant->description)) {
            $updateData['description'] = $suggestions['suggested']['description'];
            $changes[] = 'description';
        }

        if ($force || empty($tenant->subscription_tier)) {
            $updateData['subscription_tier'] = $suggestions['suggested']['subscription_tier'];
            $changes[] = 'subscription_tier';
        }

        if ($force || empty($tenant->monthly_budget)) {
            $updateData['monthly_budget'] = $suggestions['suggested']['monthly_budget'];
            $changes[] = 'monthly_budget';
        }

        if ($force || empty($tenant->country)) {
            $country = $accounts->pluck('country')->filter()->countBy()->sortDesc()->keys()->first();
            if ($country) {
                $updateData['country'] = $country;
                $changes[] = 'country';
            }
        }

        // Update contact info if emails found
        if (!empty($suggestions['suggested']['contact_info']['emails_found'])) {
            if ($force || empty($tenant->contact_email)) {
                $updateData['contact_email'] = $suggestions['suggested']['contact_info']['emails_found'][0];
                $changes[] = 'contact_email';
            }
        }

        $tenant->update($updateData);

        return [
            'success' => true,
            'message' => 'Tenant populated successfully',
            'changes' => $changes,
            'data' => $suggestions,
        ];
    }
}
