<?php

namespace App\Services;

use App\Models\AdCampaign;
use App\Models\AdAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesReadyPixelService
{
    private GoogleSheetsService $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        $this->googleSheetsService = $googleSheetsService;
    }

    /**
     * Process lead conversion from instant forms
     */
    public function processLeadConversion(array $leadData): array
    {
        try {
            Log::info('Processing lead conversion', ['lead_data' => $leadData]);

            // Clean and validate lead data
            $cleanedLead = $this->validateAndCleanLeadData($leadData);

            // Store lead in database/cache for sales team access
            $leadId = $this->storeLeadData($cleanedLead);

            // Sync to relevant Google Sheets
            $this->syncLeadToSheets($cleanedLead);

            return [
                'success' => true,
                'lead_id' => $leadId,
                'message' => 'Lead processed and synced to sales sheets'
            ];

        } catch (\Exception $e) {
            Log::error('Lead conversion processing failed', [
                'error' => $e->getMessage(),
                'lead_data' => $leadData
            ]);

            return [
                'success' => false,
                'error' => 'Failed to process lead conversion',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process sales conversion from e-commerce tracking
     */
    public function processSalesConversion(array $saleData): array
    {
        try {
            Log::info('Processing sales conversion', ['sale_data' => $saleData]);

            // Clean and validate sale data
            $cleanedSale = $this->validateAndCleanSaleData($saleData);

            // Store sale in database/cache
            $saleId = $this->storeSaleData($cleanedSale);

            // Sync to relevant Google Sheets
            $this->syncSaleToSheets($cleanedSale);

            return [
                'success' => true,
                'sale_id' => $saleId,
                'message' => 'Sale processed and synced to sales sheets'
            ];

        } catch (\Exception $e) {
            Log::error('Sales conversion processing failed', [
                'error' => $e->getMessage(),
                'sale_data' => $saleData
            ]);

            return [
                'success' => false,
                'error' => 'Failed to process sales conversion',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process call conversion from phone tracking
     */
    public function processCallConversion(array $callData): array
    {
        try {
            Log::info('Processing call conversion', ['call_data' => $callData]);

            // Clean and validate call data
            $cleanedCall = $this->validateAndCleanCallData($callData);

            // Store call in database/cache
            $callId = $this->storeCallData($cleanedCall);

            // Sync to relevant Google Sheets
            $this->syncCallToSheets($cleanedCall);

            return [
                'success' => true,
                'call_id' => $callId,
                'message' => 'Call processed and synced to sales sheets'
            ];

        } catch (\Exception $e) {
            Log::error('Call conversion processing failed', [
                'error' => $e->getMessage(),
                'call_data' => $callData
            ]);

            return [
                'success' => false,
                'error' => 'Failed to process call conversion',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get sales-ready lead data for a campaign
     */
    public function getSalesReadyLeads(int $campaignId, array $options = []): array
    {
        try {
            $startDate = $options['start_date'] ?? now()->subDays(30);
            $limit = $options['limit'] ?? 1000;

            $cacheKey = "sales_leads_{$campaignId}_" . md5(serialize($options));

            return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($campaignId, $startDate, $limit) {
                // Get leads from cache/database
                $leads = $this->getCachedLeads($campaignId, $startDate, $limit);

                // Format for sales team
                $salesReadyLeads = array_map([$this, 'formatLeadForSalesTeam'], $leads);

                return [
                    'success' => true,
                    'leads' => $salesReadyLeads,
                    'total_count' => count($salesReadyLeads),
                    'campaign_id' => $campaignId
                ];
            });

        } catch (\Exception $e) {
            Log::error('Failed to get sales-ready leads', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'leads' => [],
                'total_count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sync batch of conversions to appropriate sheets
     */
    public function batchSyncConversions(array $conversions): array
    {
        $results = [
            'total_processed' => 0,
            'successful_syncs' => 0,
            'errors' => 0,
            'sheets_updated' => []
        ];

        foreach ($conversions as $conversion) {
            $results['total_processed']++;

            try {
                $this->syncSingleConversion($conversion);
                $results['successful_syncs']++;

                if (!in_array($conversion['sheet_id'] ?? 'unknown', $results['sheets_updated'])) {
                    $results['sheets_updated'][] = $conversion['sheet_id'];
                }

            } catch (\Exception $e) {
                $results['errors']++;
                Log::warning('Failed to sync individual conversion', [
                    'conversion_id' => $conversion['conversion_id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    // Private helper methods

    private function validateAndCleanLeadData(array $data): array
    {
        return [
            'lead_id' => $data['lead_id'] ?? 'lead_' . uniqid(),
            'conversion_id' => $data['conversion_id'] ?? 'conv_' . uniqid(),
            'campaign_id' => $data['campaign_id'] ?? null,
            'timestamp' => $data['timestamp'] ?? now()->toISOString(),
            'form_id' => $data['form_id'] ?? null,

            // Lead contact information
            'lead_full_name' => $this->sanitizeText($data['full_name'] ?? $data['name'] ?? ''),
            'lead_email' => $this->sanitizeEmail($data['email'] ?? ''),
            'lead_phone' => $this->sanitizePhone($data['phone'] ?? $data['phone_number'] ?? ''),
            'lead_company' => $this->sanitizeText($data['company'] ?? ''),
            'lead_job_title' => $this->sanitizeText($data['job_title'] ?? ''),

            // Technical tracking
            'utm_source' => $data['utm_source'] ?? '',
            'utm_medium' => $data['utm_medium'] ?? '',
            'utm_campaign' => $data['utm_campaign'] ?? '',
            'utm_content' => $data['utm_content'] ?? '',
            'utm_term' => $data['utm_term'] ?? '',
            'page_url' => $data['page_url'] ?? '',
            'referrer' => $data['referrer'] ?? '',
            'device_type' => $data['device_type'] ?? 'unknown',
            'browser' => $data['browser'] ?? 'unknown',
            'ip_address' => $this->anonymizeIp($data['ip_address'] ?? ''),
            'user_location' => $data['location'] ?? '',

            // Sales team fields (initially empty)
            'sales_lead_status' => 'New',
            'contact_attempt_status' => 'Not Contacted',
            'lead_qualification_notes' => '',
            'next_follow_up_date' => '',
            'assigned_sales_rep' => '',
            'estimated_lead_value' => 0,
            'conversion_probability' => 0,
            'sales_team_notes' => '',

            // Auto-calculated fields
            'lead_quality_score' => $this->calculateLeadQualityScore($data),
            'lead_source_page' => $data['page_url'] ?? '',
            'form_fields_completed' => count(array_filter([
                $data['full_name'] ?? '',
                $data['email'] ?? '',
                $data['phone'] ?? '',
                $data['company'] ?? ''
            ]))
        ];
    }

    private function validateAndCleanSaleData(array $data): array
    {
        return [
            'conversion_id' => $data['conversion_id'] ?? 'sale_' . uniqid(),
            'campaign_id' => $data['campaign_id'] ?? null,
            'order_id' => $data['order_id'] ?? $data['transaction_id'] ?? null,
            'timestamp' => $data['timestamp'] ?? now()->toISOString(),

            // Product information
            'product_id' => $data['product_id'] ?? '',
            'product_name' => $this->sanitizeText($data['product_name'] ?? ''),
            'quantity' => (int) ($data['quantity'] ?? 1),
            'unit_price' => (float) ($data['unit_price'] ?? $data['price'] ?? 0),
            'order_total_value' => (float) ($data['value'] ?? $data['total_value'] ?? 0),
            'currency' => strtoupper($data['currency'] ?? 'USD'),

            // Customer information
            'customer_email' => $this->sanitizeEmail($data['email'] ?? $data['customer_email'] ?? ''),
            'customer_phone' => $this->sanitizePhone($data['phone'] ?? $data['customer_phone'] ?? ''),
            'payment_method' => $data['payment_method'] ?? '',
            'shipping_address' => $this->sanitizeText($data['shipping_address'] ?? ''),

            // Technical tracking
            'utm_source' => $data['utm_source'] ?? '',
            'utm_medium' => $data['utm_medium'] ?? '',
            'utm_campaign' => $data['utm_campaign'] ?? '',
            'page_url' => $data['page_url'] ?? '',
            'device_type' => $data['device_type'] ?? 'unknown',

            // Sales team fields (initially empty)
            'order_fulfillment_status' => 'Pending',
            'order_verification_status' => 'Unverified',
            'customer_lifetime_value' => 0,
            'upsell_potential' => '',
            'customer_satisfaction_score' => 0,
            'assigned_account_manager' => '',
            'sales_order_notes' => ''
        ];
    }

    private function validateAndCleanCallData(array $data): array
    {
        return [
            'conversion_id' => $data['conversion_id'] ?? 'call_' . uniqid(),
            'campaign_id' => $data['campaign_id'] ?? null,
            'timestamp' => $data['timestamp'] ?? now()->toISOString(),

            // Call information
            'call_duration_seconds' => (int) ($data['duration'] ?? $data['call_duration'] ?? 0),
            'caller_phone_number' => $this->sanitizePhone($data['phone'] ?? $data['caller_id'] ?? ''),
            'call_recording_url' => $data['recording_url'] ?? '',
            'call_result' => $data['outcome'] ?? $data['result'] ?? 'Unknown',

            // Technical tracking
            'utm_source' => $data['utm_source'] ?? '',
            'utm_medium' => $data['utm_medium'] ?? '',
            'utm_campaign' => $data['utm_campaign'] ?? '',
            'page_url' => $data['page_url'] ?? '',
            'referrer' => $data['referrer'] ?? '',

            // Sales team fields (initially empty)
            'call_qualification_status' => 'Unqualified',
            'prospect_interest_level' => 'Unknown',
            'follow_up_meeting_scheduled' => false,
            'call_summary_notes' => '',
            'recommended_next_action' => '',
            'marked_as_hot_lead' => false,
            'sales_rep_call_notes' => '',

            // Auto-calculated fields
            'call_quality_score' => $this->calculateCallQualityScore($data)
        ];
    }

    private function storeLeadData(array $leadData): string
    {
        $leadId = $leadData['lead_id'];

        // Store in cache for quick access
        Cache::put("lead_{$leadId}", $leadData, now()->addDays(90));

        // Add to campaign leads list
        if (isset($leadData['campaign_id'])) {
            $campaignKey = "campaign_leads_{$leadData['campaign_id']}";
            $existingLeads = Cache::get($campaignKey, []);

            // Add to beginning of array (most recent first)
            array_unshift($existingLeads, $leadData);

            // Keep only last 10000 leads per campaign
            if (count($existingLeads) > 10000) {
                $existingLeads = array_slice($existingLeads, 0, 10000);
            }

            Cache::put($campaignKey, $existingLeads, now()->addDays(90));
        }

        return $leadId;
    }

    private function storeSaleData(array $saleData): string
    {
        $saleId = $saleData['conversion_id'];

        // Store in cache
        Cache::put("sale_{$saleId}", $saleData, now()->addDays(90));

        // Add to campaign sales list
        if (isset($saleData['campaign_id'])) {
            $campaignKey = "campaign_sales_{$saleData['campaign_id']}";
            $existingSales = Cache::get($campaignKey, []);
            array_unshift($existingSales, $saleData);

            if (count($existingSales) > 5000) {
                $existingSales = array_slice($existingSales, 0, 5000);
            }

            Cache::put($campaignKey, $existingSales, now()->addDays(90));
        }

        return $saleId;
    }

    private function storeCallData(array $callData): string
    {
        $callId = $callData['conversion_id'];

        // Store in cache
        Cache::put("call_{$callId}", $callData, now()->addDays(90));

        // Add to campaign calls list
        if (isset($callData['campaign_id'])) {
            $campaignKey = "campaign_calls_{$callData['campaign_id']}";
            $existingCalls = Cache::get($campaignKey, []);
            array_unshift($existingCalls, $callData);

            if (count($existingCalls) > 5000) {
                $existingCalls = array_slice($existingCalls, 0, 5000);
            }

            Cache::put($campaignKey, $existingCalls, now()->addDays(90));
        }

        return $callId;
    }

    private function syncLeadToSheets(array $leadData): void
    {
        if (!isset($leadData['campaign_id'])) {
            return;
        }

        $campaign = AdCampaign::find($leadData['campaign_id']);
        if (!$campaign || !$campaign->google_sheet_id) {
            return;
        }

        try {
            // Sync to campaign-specific sheet
            $this->googleSheetsService->logConversion(
                $campaign->google_sheet_id,
                $leadData,
                $campaign->sheet_mapping ?? []
            );

            // Sync to account instant forms sheet
            $formsSheetId = Cache::get("forms_sheet_{$campaign->ad_account_id}");
            if ($formsSheetId) {
                $this->googleSheetsService->logConversion($formsSheetId, $leadData, []);
            }

            Log::info('Lead synced to sheets', [
                'lead_id' => $leadData['lead_id'],
                'campaign_id' => $campaign->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync lead to sheets', [
                'lead_id' => $leadData['lead_id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    private function syncSaleToSheets(array $saleData): void
    {
        if (!isset($saleData['campaign_id'])) {
            return;
        }

        $campaign = AdCampaign::find($saleData['campaign_id']);
        if (!$campaign || !$campaign->google_sheet_id) {
            return;
        }

        try {
            // Sync to campaign-specific sheet
            $this->googleSheetsService->logConversion(
                $campaign->google_sheet_id,
                $saleData,
                $campaign->sheet_mapping ?? []
            );

            Log::info('Sale synced to sheets', [
                'order_id' => $saleData['order_id'],
                'campaign_id' => $campaign->id,
                'value' => $saleData['order_total_value']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync sale to sheets', [
                'order_id' => $saleData['order_id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    private function syncCallToSheets(array $callData): void
    {
        if (!isset($callData['campaign_id'])) {
            return;
        }

        $campaign = AdCampaign::find($callData['campaign_id']);
        if (!$campaign || !$campaign->google_sheet_id) {
            return;
        }

        try {
            // Sync to campaign-specific sheet
            $this->googleSheetsService->logConversion(
                $campaign->google_sheet_id,
                $callData,
                $campaign->sheet_mapping ?? []
            );

            Log::info('Call synced to sheets', [
                'call_id' => $callData['conversion_id'],
                'campaign_id' => $campaign->id,
                'duration' => $callData['call_duration_seconds']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync call to sheets', [
                'call_id' => $callData['conversion_id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getCachedLeads(int $campaignId, $startDate, int $limit): array
    {
        $campaignKey = "campaign_leads_{$campaignId}";
        $allLeads = Cache::get($campaignKey, []);

        // Filter by date and limit
        $filteredLeads = array_filter($allLeads, function ($lead) use ($startDate) {
            $leadDate = Carbon::parse($lead['timestamp'] ?? now());
            return $leadDate->gte($startDate);
        });

        return array_slice($filteredLeads, 0, $limit);
    }

    private function formatLeadForSalesTeam(array $lead): array
    {
        return array_merge($lead, [
            'formatted_date' => Carbon::parse($lead['timestamp'])->format('Y-m-d H:i:s'),
            'days_since_lead' => Carbon::parse($lead['timestamp'])->diffInDays(now()),
            'contact_priority' => $this->calculateContactPriority($lead),
            'lead_score_display' => $this->formatLeadScore($lead['lead_quality_score'] ?? 0)
        ]);
    }

    private function calculateLeadQualityScore(array $data): int
    {
        $score = 50; // Base score

        // Email provided
        if (!empty($data['email'])) $score += 20;

        // Phone provided
        if (!empty($data['phone'])) $score += 20;

        // Company provided
        if (!empty($data['company'])) $score += 10;

        // Job title provided
        if (!empty($data['job_title'])) $score += 5;

        // UTM tracking complete
        if (!empty($data['utm_source']) && !empty($data['utm_campaign'])) $score += 5;

        return min(100, max(0, $score));
    }

    private function calculateCallQualityScore(array $data): int
    {
        $score = 50; // Base score
        $duration = (int) ($data['duration'] ?? 0);

        // Duration scoring
        if ($duration > 300) $score += 30; // 5+ minutes
        elseif ($duration > 120) $score += 20; // 2-5 minutes
        elseif ($duration > 60) $score += 10; // 1-2 minutes
        elseif ($duration < 30) $score -= 20; // Less than 30 seconds

        // Outcome scoring
        $outcome = strtolower($data['outcome'] ?? '');
        if (str_contains($outcome, 'interested')) $score += 20;
        elseif (str_contains($outcome, 'callback')) $score += 15;
        elseif (str_contains($outcome, 'not interested')) $score -= 30;

        return min(100, max(0, $score));
    }

    private function calculateContactPriority(array $lead): string
    {
        $score = $lead['lead_quality_score'] ?? 0;
        $daysOld = Carbon::parse($lead['timestamp'])->diffInDays(now());

        if ($score >= 80 && $daysOld <= 1) return 'URGENT';
        if ($score >= 70 && $daysOld <= 2) return 'HIGH';
        if ($score >= 50 && $daysOld <= 7) return 'MEDIUM';
        return 'LOW';
    }

    private function formatLeadScore(int $score): string
    {
        if ($score >= 80) return "🟢 Excellent ($score)";
        if ($score >= 60) return "🟡 Good ($score)";
        if ($score >= 40) return "🟠 Fair ($score)";
        return "🔴 Poor ($score)";
    }

    private function sanitizeText(string $text): string
    {
        return trim(strip_tags($text));
    }

    private function sanitizeEmail(string $email): string
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL) ?: '';
    }

    private function sanitizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+\-\(\)\s]/', '', trim($phone));
    }

    private function anonymizeIp(string $ip): string
    {
        if (empty($ip)) return '';

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }

        return 'anonymized';
    }

    private function syncSingleConversion(array $conversion): void
    {
        // Implementation for syncing individual conversion
        // This would determine the type and route to appropriate sync method
        $type = $conversion['type'] ?? $conversion['conversion_type'] ?? 'unknown';

        switch ($type) {
            case 'lead':
            case 'form_submission':
                $this->syncLeadToSheets($conversion);
                break;
            case 'sale':
            case 'purchase':
                $this->syncSaleToSheets($conversion);
                break;
            case 'call':
            case 'phone_call':
                $this->syncCallToSheets($conversion);
                break;
            default:
                // Generic conversion sync
                if (isset($conversion['campaign_id'])) {
                    $campaign = AdCampaign::find($conversion['campaign_id']);
                    if ($campaign && $campaign->google_sheet_id) {
                        $this->googleSheetsService->logConversion(
                            $campaign->google_sheet_id,
                            $conversion,
                            $campaign->sheet_mapping ?? []
                        );
                    }
                }
                break;
        }
    }

    /**
     * Process general conversion with sales-ready enhancements
     */
    public function processConversion(array $conversionData, AdCampaign $campaign): array
    {
        try {
            Log::info('Processing sales-ready conversion', [
                'campaign_id' => $campaign->id,
                'conversion_type' => $conversionData['conversion_type'] ?? 'unknown'
            ]);

            // Generate unique conversion ID
            $conversionId = 'conv_' . $campaign->id . '_' . time() . '_' . uniqid();

            // Enrich conversion data for sales team
            $enrichedData = $this->enrichConversionForSalesTeam($conversionData, $campaign);

            // Store conversion data
            $this->storeConversionData($enrichedData, $conversionId);

            // Sync to appropriate tab in workbook
            $tabName = $this->getTargetTabName($conversionData);
            $success = $this->syncConversionToWorkbookTab($campaign, $enrichedData, $tabName);

            // Calculate lead score
            $leadScore = $this->calculateLeadScore($conversionData);

            // Determine sales stage
            $salesStage = $this->determineSalesStage($conversionData);

            return [
                'success' => true,
                'conversion_id' => $conversionId,
                'lead_score' => $leadScore,
                'sales_stage' => $salesStage,
                'sheet_synced' => $success,
                'sheet_tab' => $tabName,
                'message' => 'Conversion processed and synced to sales workbook'
            ];

        } catch (\Exception $e) {
            Log::error('Sales-ready conversion processing failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
                'conversion_data' => $conversionData
            ]);

            return [
                'success' => false,
                'conversion_id' => null,
                'error' => 'Failed to process conversion',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Determine target tab name based on conversion type
     */
    private function getTargetTabName(array $conversionData): string
    {
        $conversionType = $conversionData['conversion_type'] ?? 'conversion';

        switch (strtolower($conversionType)) {
            case 'lead':
            case 'lead_generation':
            case 'form_submission':
                return 'Leads';

            case 'purchase':
            case 'sale':
            case 'transaction':
                return 'Conversions';

            case 'call':
            case 'phone_call':
                return 'Conversions';

            default:
                return 'Conversions';
        }
    }

    /**
     * Get mapping for specific tab
     */
    private function getTabMapping(string $tabName): array
    {
        switch ($tabName) {
            case 'Leads':
                return [
                    'Date Submitted' => 'created_time',
                    'Campaign Name' => 'campaign_name',
                    'Lead ID' => 'lead_id',
                    'Full Name' => 'full_name',
                    'Email' => 'email',
                    'Phone' => 'phone',
                    'Company' => 'company',
                    'Lead Quality' => 'lead_quality',
                    'Lead Status' => 'lead_status',
                    'Assigned To' => 'assigned_to',
                    'Follow Up Date' => 'follow_up_date',
                    'Notes' => 'notes'
                ];

            case 'Conversions':
                return [
                    'Timestamp' => 'timestamp',
                    'Campaign Name' => 'campaign_name',
                    'Conversion Type' => 'conversion_type',
                    'Conversion Value' => 'conversion_value',
                    'User ID' => 'user_id',
                    'Page URL' => 'page_url',
                    'Lead Score' => 'lead_score',
                    'Sales Stage' => 'sales_stage'
                ];

            default:
                return [
                    'Timestamp' => 'timestamp',
                    'Data' => 'data'
                ];
        }
    }

    /**
     * Sync conversion to specific workbook tab
     */
    private function syncConversionToWorkbookTab(AdCampaign $campaign, array $conversionData, string $tabName): bool
    {
        try {
            if (!$campaign->google_sheet_id) {
                return false;
            }

            $mapping = $this->getTabMapping($tabName);

            // Add to specific tab using the new method
            return $this->googleSheetsService->logConversionToTab(
                $campaign->google_sheet_id,
                $tabName,
                $conversionData,
                $mapping
            );

        } catch (\Exception $e) {
            Log::warning('Failed to sync conversion to workbook tab', [
                'campaign_id' => $campaign->id,
                'tab_name' => $tabName,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Store conversion data for later analysis
     */
    private function storeConversionData(array $conversionData, string $conversionId): void
    {
        // Store in cache for immediate access
        Cache::put("conversion_{$conversionId}", $conversionData, now()->addDays(7));

        // Store in database if needed (implement based on requirements)
        // This would go to a conversions table
    }

    /**
     * Calculate lead score based on conversion data
     */
    private function calculateLeadScore(array $conversionData): int
    {
        $score = 50; // Base score

        // Increase score based on various factors
        if (isset($conversionData['conversion_value']) && $conversionData['conversion_value'] > 0) {
            $score += min(($conversionData['conversion_value'] / 100) * 10, 30);
        }

        if (isset($conversionData['email']) && !empty($conversionData['email'])) {
            $score += 10;
        }

        if (isset($conversionData['phone']) && !empty($conversionData['phone'])) {
            $score += 15;
        }

        if (isset($conversionData['company']) && !empty($conversionData['company'])) {
            $score += 10;
        }

        return min(max($score, 0), 100);
    }

    /**
     * Determine sales stage based on conversion data
     */
    private function determineSalesStage(array $conversionData): string
    {
        $conversionType = strtolower($conversionData['conversion_type'] ?? 'unknown');

        switch ($conversionType) {
            case 'lead':
            case 'form_submission':
                return 'New';

            case 'call':
            case 'phone_call':
                return 'Contacted';

            case 'purchase':
            case 'sale':
                return 'Converted';

            default:
                return 'New';
        }
    }
}