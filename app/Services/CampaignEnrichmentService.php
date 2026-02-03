<?php

namespace App\Services;

use App\Models\AdCampaign;
use Illuminate\Support\Facades\Log;

class CampaignEnrichmentService
{
    /**
     * Enrich a campaign with all missing metadata fields
     */
    public function enrichCampaign(AdCampaign $campaign): array
    {
        $updated = false;
        $changes = [];

        // Enrich target segment
        if (!$campaign->target_segment) {
            $targetSegment = $this->detectTargetSegment($campaign);
            if ($targetSegment) {
                $campaign->target_segment = $targetSegment;
                $changes[] = "target_segment: $targetSegment";
                $updated = true;
            }
        }

        // Enrich age group
        if (!$campaign->age_group) {
            $ageGroup = $this->detectAgeGroup($campaign);
            if ($ageGroup) {
                $campaign->age_group = $ageGroup;
                $changes[] = "age_group: $ageGroup";
                $updated = true;
            }
        }

        // Enrich geo targeting
        if (!$campaign->geo_targeting) {
            $geoTargeting = $this->detectGeoTargeting($campaign);
            if ($geoTargeting) {
                $campaign->geo_targeting = $geoTargeting;
                $changes[] = "geo_targeting: $geoTargeting";
                $updated = true;
            }
        }

        // Enrich message tone
        if (!$campaign->message_tone) {
            $messageTone = $this->detectMessageTone($campaign);
            if ($messageTone) {
                $campaign->message_tone = $messageTone;
                $changes[] = "message_tone: $messageTone";
                $updated = true;
            }
        }

        // Fill in missing objective
        if (!$campaign->objective) {
            $campaign->objective = 'awareness';
            $changes[] = 'objective: awareness (default)';
            $updated = true;
        }

        // Fill in missing funnel_stage (derive from objective)
        if (!$campaign->funnel_stage && $campaign->objective) {
            $funnelStage = $this->mapObjectiveToFunnelStage($campaign->objective);
            if ($funnelStage) {
                $campaign->funnel_stage = $funnelStage;
                $changes[] = "funnel_stage: $funnelStage";
                $updated = true;
            }
        }

        // Fill in missing user_journey
        if (!$campaign->user_journey) {
            $campaign->user_journey = 'landing_page';
            $changes[] = 'user_journey: landing_page (default)';
            $updated = true;
        }

        if ($updated) {
            $campaign->save();
        }

        return [
            'updated' => $updated,
            'changes' => $changes
        ];
    }

    /**
     * Detect target segment from campaign name and objective
     * Valid values: luxury, premium, mid_class, value, mass_market, niche
     */
    private function detectTargetSegment(AdCampaign $campaign): ?string
    {
        $name = strtolower($campaign->name);
        $objective = strtolower($campaign->objective ?? '');

        // Luxury indicators
        if (preg_match('/\b(luxury|high-end|ultra|elite|prestige)\b/i', $name)) {
            return 'luxury';
        }

        // Premium indicators
        if (preg_match('/\b(premium|vip|exclusive|select)\b/i', $name)) {
            return 'premium';
        }

        // Value/Budget indicators
        if (preg_match('/\b(budget|discount|sale|deal|cheap|affordable|value|economy)\b/i', $name)) {
            return 'value';
        }

        // Niche indicators
        if (preg_match('/\b(niche|specialized|specific|targeted|custom)\b/i', $name)) {
            return 'niche';
        }

        // Mid-class indicators
        if (preg_match('/\b(mid|middle|standard|regular|mainstream)\b/i', $name)) {
            return 'mid_class';
        }

        // Based on objective
        if (in_array($objective, ['leads', 'website_sales', 'sales'])) {
            return 'mid_class';
        }

        // Default to mass market
        return 'mass_market';
    }

    /**
     * Detect age group from campaign name
     * Valid values: gen_z, millennials, gen_x, boomers, mixed_age
     */
    private function detectAgeGroup(AdCampaign $campaign): ?string
    {
        $name = strtolower($campaign->name);

        // Gen Z indicators (18-27)
        if (preg_match('/\b(gen z|genz|tiktok|snapchat|teen|youth|18-24|18-27)\b/i', $name)) {
            return 'gen_z';
        }

        // Millennials indicators (28-43)
        if (preg_match('/\b(millennial|young professional|startup|tech-savvy|25-34|28-43)\b/i', $name)) {
            return 'millennials';
        }

        // Gen X indicators (44-59)
        if (preg_match('/\b(gen x|genx|mid-career|established|35-44|44-59)\b/i', $name)) {
            return 'gen_x';
        }

        // Boomers indicators (60+)
        if (preg_match('/\b(boomer|senior|retirement|mature|elderly|55\+|60\+)\b/i', $name)) {
            return 'boomers';
        }

        // Mixed age indicators (parents, family, all ages)
        if (preg_match('/\b(parent|mom|dad|family|all ages|everyone|mixed)\b/i', $name)) {
            return 'mixed_age';
        }

        // Default to mixed age
        return 'mixed_age';
    }

    /**
     * Detect geo targeting from campaign name and account
     */
    private function detectGeoTargeting(AdCampaign $campaign): ?string
    {
        $name = strtolower($campaign->name);

        // Check account name for geo clues
        $accountName = strtolower($campaign->adAccount->account_name ?? '');

        // International indicators
        if (preg_match('/\b(international|global|worldwide|multi-country)\b/i', $name)) {
            return 'international';
        }

        // National indicators
        if (preg_match('/\b(national|ksa|saudi|uae|gcc|mena)\b/i', $name) ||
            preg_match('/\b(national|ksa|saudi|uae|gcc|mena)\b/i', $accountName)) {
            return 'national';
        }

        // Regional indicators
        if (preg_match('/\b(region|area|zone|district)\b/i', $name)) {
            return 'regional';
        }

        // Local/City indicators
        if (preg_match('/\b(riyadh|jeddah|dammam|makkah|madinah|local|city)\b/i', $name) ||
            preg_match('/\b(riyadh|jeddah|dammam|makkah|madinah)\b/i', $accountName)) {
            return 'local';
        }

        // Default based on account industry
        $industry = $campaign->adAccount->industry ?? null;
        if (in_array($industry, ['retail_ecommerce', 'food_beverage'])) {
            return 'local';
        }

        // Default to national
        return 'national';
    }

    /**
     * Detect message tone from campaign name and objective
     */
    private function detectMessageTone(AdCampaign $campaign): ?string
    {
        $name = strtolower($campaign->name);
        $objective = strtolower($campaign->objective ?? '');

        // Urgent/Promotional indicators
        if (preg_match('/\b(sale|offer|discount|deal|limited|hurry|now|today|flash)\b/i', $name)) {
            return 'urgent';
        }

        // Educational indicators
        if (preg_match('/\b(learn|guide|tips|how to|tutorial|webinar|course|info)\b/i', $name)) {
            return 'educational';
        }

        // Inspirational indicators
        if (preg_match('/\b(inspire|dream|achieve|transform|success|story|journey)\b/i', $name)) {
            return 'inspirational';
        }

        // Conversational indicators
        if (preg_match('/\b(chat|talk|connect|join|community|engage)\b/i', $name)) {
            return 'conversational';
        }

        // Based on objective
        if (in_array($objective, ['website_sales', 'sales', 'calls'])) {
            return 'promotional';
        }

        if (in_array($objective, ['awareness', 'reach'])) {
            return 'inspirational';
        }

        if (in_array($objective, ['leads', 'engagement'])) {
            return 'conversational';
        }

        // Default
        return 'promotional';
    }

    /**
     * Map objective to funnel stage
     */
    private function mapObjectiveToFunnelStage(string $objective): ?string
    {
        $funnelMap = [
            'awareness' => 'TOF',
            'reach' => 'TOF',
            'engagement' => 'TOF',
            'traffic' => 'MOF',
            'leads' => 'MOF',
            'app_installs' => 'MOF',
            'website_sales' => 'BOF',
            'sales' => 'BOF',
            'calls' => 'BOF',
            'retention' => 'BOF',
        ];

        return $funnelMap[strtolower($objective)] ?? 'MOF';
    }

    /**
     * Enrich all campaigns
     */
    public function enrichAllCampaigns(): array
    {
        $campaigns = AdCampaign::all();
        $totalProcessed = 0;
        $totalUpdated = 0;
        $results = [];

        foreach ($campaigns as $campaign) {
            $totalProcessed++;

            try {
                $result = $this->enrichCampaign($campaign);

                if ($result['updated']) {
                    $totalUpdated++;
                    $results[] = [
                        'id' => $campaign->id,
                        'name' => $campaign->name,
                        'changes' => $result['changes']
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Failed to enrich campaign', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'total_processed' => $totalProcessed,
            'total_updated' => $totalUpdated,
            'results' => $results
        ];
    }
}
