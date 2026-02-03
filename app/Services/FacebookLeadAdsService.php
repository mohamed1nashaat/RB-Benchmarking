<?php

namespace App\Services;

use App\Models\AdAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class FacebookLeadAdsService
{
    private const FACEBOOK_API_VERSION = 'v19.0';
    private const BASE_URL = 'https://graph.facebook.com';

    /**
     * Fetch real lead data from Facebook Lead Ads API
     */
    public function getLeadAdsData(AdAccount $adAccount): Collection
    {
        try {
            // Access token is stored in app_config array, not as direct property
            $accessToken = $adAccount->integration->app_config['access_token'] ?? null;
            if (!$accessToken) {
                Log::warning('No access token available for Facebook Lead Ads', [
                    'account_id' => $adAccount->id,
                    'integration_id' => $adAccount->integration->id
                ]);
                return collect();
            }

            Log::info('Found Facebook access token, attempting to fetch real leads', [
                'account_id' => $adAccount->id,
                'token_preview' => substr($accessToken, 0, 20) . '...'
            ]);

            // Get ad account ID (remove 'act_' prefix if present)
            $accountId = str_replace('act_', '', $adAccount->external_account_id);

            // First, get all campaigns for this ad account
            $campaigns = $this->getCampaigns($accountId, $accessToken);

            $allLeads = collect();

            foreach ($campaigns as $campaign) {
                // Get ads for each campaign
                $ads = $this->getCampaignAds($campaign['id'], $accessToken);

                foreach ($ads as $ad) {
                    // Get leads for each ad
                    $leads = $this->getAdLeads($ad['id'], $accessToken);

                    foreach ($leads as $lead) {
                        $leadData = $this->formatLeadData($lead, $campaign, $ad);
                        $allLeads->push($leadData);
                    }
                }
            }

            Log::info('Successfully fetched Facebook leads', [
                'account_id' => $adAccount->id,
                'leads_count' => $allLeads->count()
            ]);

            return $allLeads;

        } catch (\Exception $e) {
            Log::error('Failed to fetch Facebook Lead Ads data', [
                'account_id' => $adAccount->id,
                'error' => $e->getMessage()
            ]);

            return collect();
        }
    }

    /**
     * Get campaigns for ad account
     */
    private function getCampaigns(string $accountId, string $accessToken): array
    {
        $url = self::BASE_URL . '/' . self::FACEBOOK_API_VERSION . '/act_' . $accountId . '/campaigns';

        $response = Http::get($url, [
            'access_token' => $accessToken,
            'fields' => 'id,name,status,objective',
            'limit' => 100
        ]);

        if ($response->successful()) {
            return $response->json('data', []);
        }

        Log::warning('Failed to fetch Facebook campaigns', [
            'account_id' => $accountId,
            'error' => $response->json('error.message', 'Unknown error')
        ]);

        return [];
    }

    /**
     * Get ads for a campaign
     */
    private function getCampaignAds(string $campaignId, string $accessToken): array
    {
        $url = self::BASE_URL . '/' . self::FACEBOOK_API_VERSION . '/' . $campaignId . '/ads';

        $response = Http::get($url, [
            'access_token' => $accessToken,
            'fields' => 'id,name,status',
            'limit' => 100
        ]);

        if ($response->successful()) {
            return $response->json('data', []);
        }

        return [];
    }

    /**
     * Get leads for a specific ad
     */
    private function getAdLeads(string $adId, string $accessToken): array
    {
        $url = self::BASE_URL . '/' . self::FACEBOOK_API_VERSION . '/' . $adId . '/leads';

        $response = Http::get($url, [
            'access_token' => $accessToken,
            'fields' => 'id,created_time,field_data,ad_id,form_id,campaign_id',
            'limit' => 100
        ]);

        if ($response->successful()) {
            return $response->json('data', []);
        }

        return [];
    }

    /**
     * Format lead data for Google Sheets
     */
    private function formatLeadData(array $lead, array $campaign, array $ad): array
    {
        $fieldData = [];

        // Process field_data array to extract form fields
        if (isset($lead['field_data']) && is_array($lead['field_data'])) {
            foreach ($lead['field_data'] as $field) {
                $fieldName = $field['name'] ?? 'unknown';
                $fieldValues = $field['values'] ?? [];
                $fieldData[$fieldName] = implode(', ', $fieldValues);
            }
        }

        // Map common form fields
        $fullName = $fieldData['full_name'] ?? $fieldData['first_name'] . ' ' . $fieldData['last_name'] ?? 'Unknown';
        $email = $fieldData['email'] ?? '';
        $phone = $fieldData['phone_number'] ?? $fieldData['phone'] ?? '';
        $company = $fieldData['company_name'] ?? $fieldData['company'] ?? '';
        $jobTitle = $fieldData['job_title'] ?? '';

        // Determine lead quality based on completeness
        $leadQuality = 'Medium';
        if (!empty($email) && !empty($phone) && !empty($company)) {
            $leadQuality = 'High';
        } elseif (empty($email) && empty($phone)) {
            $leadQuality = 'Low';
        }

        return [
            'created_time' => $lead['created_time'] ?? now()->toISOString(),
            'campaign_name' => $campaign['name'] ?? 'Unknown Campaign',
            'ad_name' => $ad['name'] ?? 'Unknown Ad',
            'form_name' => 'Facebook Instant Form',
            'lead_id' => $lead['id'] ?? 'unknown',
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'company' => $company,
            'job_title' => $jobTitle,
            'lead_source' => 'facebook',
            'lead_quality' => $leadQuality,
            'lead_status' => 'New - From Facebook Lead Ads',
            'assigned_to' => '',
            'follow_up_date' => now()->addDays(1)->format('Y-m-d'),
            'notes' => 'Real lead from Facebook Lead Ads API - ' . json_encode($fieldData),
            'utm_source' => 'facebook',
            'utm_medium' => 'social',
            'utm_campaign' => strtolower(str_replace(' ', '_', $campaign['name'] ?? 'facebook_lead'))
        ];
    }

    /**
     * Test Facebook API connection
     */
    public function testConnection(AdAccount $adAccount): array
    {
        try {
            $accessToken = $adAccount->integration->app_config['access_token'] ?? null;
            if (!$accessToken) {
                return [
                    'success' => false,
                    'message' => 'No access token available'
                ];
            }

            // Test by getting basic account info
            $accountId = str_replace('act_', '', $adAccount->external_account_id);
            $url = self::BASE_URL . '/' . self::FACEBOOK_API_VERSION . '/act_' . $accountId;

            $response = Http::get($url, [
                'access_token' => $accessToken,
                'fields' => 'name,account_status'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'Facebook API connection successful',
                    'account_name' => $data['name'] ?? 'Unknown',
                    'account_status' => $data['account_status'] ?? 'Unknown'
                ];
            }

            return [
                'success' => false,
                'message' => 'Facebook API connection failed: ' . $response->json('error.message', 'Unknown error')
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ];
        }
    }
}