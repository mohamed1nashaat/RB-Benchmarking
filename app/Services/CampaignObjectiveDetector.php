<?php

namespace App\Services;

class CampaignObjectiveDetector
{
    /**
     * Campaign objective keywords mapping
     */
    private static array $objectiveKeywords = [
        'leads' => [
            'lead', 'leads', 'leadgen', 'lead gen', 'lead generation', 'instant forms', 'form',
            'website leads', 'conversion', 'conversions', 'cpl', 'instant-leads',
            'registration', 'signup', 'subscribe'
        ],
        'traffic' => [
            'traffic', 'website traffic', 'link clicks', 'website visits', 'visits',
            'website visitors', 'landing page', 'website', 'clicks'
        ],
        'awareness' => [
            'awareness', 'reach', 'brand awareness', 'impressions', 'brand',
            'branding', 'tof', 'top funnel', 'cpm'
        ],
        'engagement' => [
            'engagement', 'post engagement', 'engage', 'followers', 'likes',
            'comments', 'shares', 'post boost', 'boost', 'ppe'
        ],
        'video_views' => [
            'video views', 'video', 'views', 'watch', 'vtr', 'video boost'
        ],
        'messages' => [
            'messages', 'message', 'dm', 'dms', 'whatsapp', 'messenger',
            'chat', 'conversation', 'cost per message'
        ],
        'app_installs' => [
            'app installs', 'app install', 'install', 'download', 'mobile app',
            'appinst', 'cpi'
        ],
        'conversions' => [
            'purchase', 'purchases', 'sales', 'buy', 'shop', 'shopping',
            'catalog sales', 'ecommerce', 'e-commerce', 'store visits',
            'add to cart', 'atc', 'checkout', 'initiate checkout'
        ],
        'calls' => [
            'calls', 'call', 'phone calls', 'cost per call', 'calling'
        ]
    ];

    /**
     * Detect campaign objective based on campaign name
     */
    public static function detectObjective(string $campaignName): ?string
    {
        $campaignNameLower = strtolower($campaignName);
        
        // Create a scoring system to handle multiple matches
        $scores = [];
        
        foreach (self::$objectiveKeywords as $objective => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                // Exact word match gets higher score
                if (preg_match('/\b' . preg_quote(strtolower($keyword), '/') . '\b/', $campaignNameLower)) {
                    $score += 10;
                } 
                // Partial match gets lower score
                elseif (strpos($campaignNameLower, strtolower($keyword)) !== false) {
                    $score += 3;
                }
            }
            
            if ($score > 0) {
                $scores[$objective] = $score;
            }
        }
        
        if (empty($scores)) {
            return null;
        }
        
        // Return the objective with the highest score
        arsort($scores);
        return array_key_first($scores);
    }

    /**
     * Get all available campaign objectives
     */
    public static function getAvailableObjectives(): array
    {
        return array_keys(self::$objectiveKeywords);
    }

    /**
     * Get campaign objective display name
     */
    public static function getObjectiveDisplayName(string $objective): string
    {
        $displayNames = [
            'leads' => 'Lead Generation',
            'traffic' => 'Traffic',
            'awareness' => 'Brand Awareness',
            'engagement' => 'Engagement',
            'video_views' => 'Video Views',
            'messages' => 'Messages',
            'app_installs' => 'App Installs',
            'conversions' => 'Conversions',
            'calls' => 'Calls'
        ];

        return $displayNames[$objective] ?? ucfirst(str_replace('_', ' ', $objective));
    }

    /**
     * Get Facebook objective mapping
     */
    public static function getFacebookObjectiveMapping(): array
    {
        return [
            'leads' => 'LEAD_GENERATION',
            'traffic' => 'LINK_CLICKS', 
            'awareness' => 'BRAND_AWARENESS',
            'engagement' => 'POST_ENGAGEMENT',
            'video_views' => 'VIDEO_VIEWS',
            'messages' => 'MESSAGES',
            'app_installs' => 'APP_INSTALLS',
            'conversions' => 'CONVERSIONS',
            'calls' => 'CONVERSIONS'
        ];
    }

    /**
     * Batch detect objectives for multiple campaigns
     */
    public static function batchDetectObjectives(array $campaignNames): array
    {
        $results = [];
        
        foreach ($campaignNames as $id => $name) {
            $results[$id] = self::detectObjective($name);
        }
        
        return $results;
    }

    /**
     * Get confidence score for detected objective
     */
    public static function getDetectionConfidence(string $campaignName, string $detectedObjective): float
    {
        if (!$detectedObjective || !isset(self::$objectiveKeywords[$detectedObjective])) {
            return 0.0;
        }

        $campaignNameLower = strtolower($campaignName);
        $keywords = self::$objectiveKeywords[$detectedObjective];
        $totalPossibleScore = count($keywords) * 10; // Max score per keyword
        $actualScore = 0;

        foreach ($keywords as $keyword) {
            if (preg_match('/\b' . preg_quote(strtolower($keyword), '/') . '\b/', $campaignNameLower)) {
                $actualScore += 10;
            } elseif (strpos($campaignNameLower, strtolower($keyword)) !== false) {
                $actualScore += 3;
            }
        }

        return min(1.0, $actualScore / ($totalPossibleScore * 0.1)); // Normalize to 0-1
    }
}