<?php

namespace App\Services;

use App\Models\AdAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CampaignNamingParser
{
    /**
     * Known objective keywords mapped to canonical values
     */
    private array $objectiveKeywords = [
        'awareness'       => ['awareness', 'brand awareness', 'reach', 'impressions', 'brand', 'branding', 'tof', 'top funnel', 'top-funnel', 'cpm'],
        'traffic'         => ['traffic', 'website traffic', 'link clicks', 'website visits', 'visits', 'landing page', 'clicks', 'linkclicks', 'search'],
        'leads'           => ['leads', 'lead', 'leadgen', 'lead gen', 'lead generation', 'instant forms', 'form', 'website leads', 'cpl', 'instant-leads', 'registration', 'signup', 'subscribe', 'registrations', 'freelancer registration'],
        'engagement'      => ['engagement', 'post engagement', 'engage', 'followers', 'likes', 'comments', 'shares', 'post boost', 'boost', 'ppe', 'eng', 'engagements'],
        'video_views'     => ['video views', 'video', 'views', 'watch', 'vtr', 'video boost', 'videoview', 'video view'],
        'messages'        => ['messages', 'message', 'dm', 'dms', 'whatsapp', 'messenger', 'chat', 'conversation', 'cost per message'],
        'app_installs'    => ['app installs', 'app install', 'install', 'download', 'mobile app', 'appinst', 'cpi', 'app installs'],
        'sales'           => ['purchase', 'purchases', 'sales', 'buy', 'shop', 'shopping', 'catalog sales', 'ecommerce', 'e-commerce', 'store visits', 'add to cart', 'atc', 'checkout', 'initiate checkout', 'store', 'current sale', 'warehouse sale', 'pmax', 'p-max', 'performance max'],
        'calls'           => ['calls', 'call', 'phone calls', 'cost per call', 'calling'],
        'conversions'     => ['conversion', 'conversions', 'offline conversions'],
    ];

    /**
     * Funnel stage keywords
     */
    private array $funnelKeywords = [
        'TOF' => ['tof', 'top funnel', 'top-funnel', 'awareness', 'reach', 'brand'],
        'MOF' => ['mof', 'middle funnel', 'mid-funnel', 'consideration', 'traffic', 'engagement'],
        'BOF' => ['bof', 'bottom funnel', 'bot-funnel', 'conversion', 'purchase', 'sales', 'leads', 'checkout'],
    ];

    /**
     * Geo/location keywords mapped to ENUM values: local, regional, national, international
     */
    private array $geoKeywords = [
        'national'      => ['ksa', 'saudi', 'saudi arabia', 'kingdom of saudi arabia', 'uae', 'qatar', 'kuwait', 'bahrain', 'egypt', 'india'],
        'local'         => ['dubai', 'abudhabi', 'abu dhabi', 'sharjah', 'ajman', 'doha', 'riyadh', 'jeddah', 'dammam', 'makkah', 'mecca', 'madinah', 'medina', 'sacramento', 'california', 'cairo', 'alexandria'],
        'regional'      => ['mena', 'gcc', 'middle east', 'gulf', 'levant'],
        'international' => ['international', 'global', 'worldwide', 'usa', 'united states', 'america', 'multi-country'],
    ];

    /**
     * Channel/platform keywords
     */
    private array $channelKeywords = [
        'facebook'    => ['fb', 'facebook', 'meta'],
        'instagram'   => ['ig', 'instagram', 'insta'],
        'youtube'     => ['yt', 'youtube', 'yt shorts'],
        'google_search' => ['search', 'google search', 'gsearch'],
        'google_display' => ['gdn', 'display', 'google display'],
        'tiktok'      => ['tiktok', 'tt'],
        'snapchat'    => ['snapchat', 'snap'],
        'linkedin'    => ['linkedin', 'li'],
        'twitter'     => ['twitter', 'x'],
        'pmax'        => ['pmax', 'p-max', 'performance max', 'performance_max'],
        'whatsapp'    => ['whatsapp', 'wa'],
    ];

    /**
     * Agency prefixes
     */
    private array $agencyPrefixes = ['rb', 'vm', 'rbp'];

    /**
     * Platform prefixes (2-letter at start of name)
     */
    private array $platformPrefixes = ['fb', 'ig', 'yt', 'tt', 'li', 'vm'];

    /**
     * Client/brand to industry mapping (built dynamically + manual additions)
     */
    private array $clientIndustryMap = [];

    public function __construct()
    {
        $this->clientIndustryMap = $this->buildClientIndustryMap();
    }

    /**
     * Main entry point: parse a campaign name and return extracted fields
     */
    public function parse(string $campaignName, ?string $accountName = null): array
    {
        $result = [
            'format'        => 'unknown',
            'objective'     => null,
            'funnel_stage'  => null,
            'geo_targeting' => null,
            'channel_type'  => null,
            'industry'      => null,
            'category'      => null,
            'sub_industry'  => null,
            'sub_category'  => null,
            'language'      => null,
            'confidence'    => 0,
        ];

        if (empty($campaignName)) {
            return $result;
        }

        // Try format-specific parsers
        if (str_contains($campaignName, '|')) {
            $parsed = $this->parsePipeDelimited($campaignName);
            $result['format'] = 'pipe';
        } elseif (preg_match('/^[A-Za-z]{2}[_-]/', $campaignName)) {
            $parsed = $this->parsePrefixed($campaignName);
            $result['format'] = 'prefixed';
        } elseif (str_contains($campaignName, ' - ')) {
            $parsed = $this->parseDashDelimited($campaignName);
            $result['format'] = 'dash';
        } else {
            $parsed = $this->parseGeneric($campaignName);
            $result['format'] = 'generic';
        }

        // Merge parsed results
        foreach ($parsed as $key => $value) {
            if ($value !== null) {
                $result[$key] = $value;
            }
        }

        // Convert objective to canonical ENUM value
        if ($result['objective']) {
            $result['objective'] = self::canonicalObjective($result['objective']);
        }

        // Try to detect industry from client/brand name if not found
        if (empty($result['industry'])) {
            $result['industry'] = $this->detectIndustryFromName($campaignName, $accountName);
        }

        // Map industry to category
        if (!empty($result['industry']) && empty($result['category'])) {
            $result['category'] = CategoryMapper::getDefaultCategory($result['industry']);
            $result['sub_industry'] = $result['category'];
        }

        // Calculate confidence
        $result['confidence'] = $this->calculateConfidence($result);

        return $result;
    }

    /**
     * Parse pipe-delimited names: [RB] | Kohler | Awareness | ... | KSA | ...
     */
    private function parsePipeDelimited(string $name): array
    {
        $parts = array_map('trim', explode('|', $name));
        $result = [];
        $nameLower = strtolower($name);

        // Detect language
        if (str_contains($nameLower, 'ar & en') || str_contains($nameLower, 'ar&en')) {
            $result['language'] = 'ar_en';
        } elseif (str_contains($nameLower, ' & ')) {
            $result['language'] = ' bilingual';
        }

        // Full 9-part format: [RB] | Client | Objective | Date | Campaign | Channel | Geo | Format | Lang
        if (count($parts) >= 7) {
            $result['channel_type'] = $this->detectChannel($parts[5] ?? '');
            $result['geo_targeting'] = $this->detectGeo($parts[6] ?? '');
            $result['objective'] = $this->detectObjective($parts[2] ?? '');
            $result['sub_category'] = $this->cleanSubCategory($parts[4] ?? '');

            // Funnel from campaign type part
            $result['funnel_stage'] = $this->detectFunnel($parts[4] ?? '');
            if (empty($result['funnel_stage'])) {
                $result['funnel_stage'] = $this->objectiveToFunnel($result['objective']);
            }

            return $result;
        }

        // Compact 2-part format: RB | Dyar Alharam -BOF - Performance Max Campaign
        if (count($parts) === 2) {
            $rightSide = $parts[1];
            $result['objective'] = $this->detectObjective($rightSide);
            $result['funnel_stage'] = $this->detectFunnel($rightSide);
            $result['channel_type'] = $this->detectChannel($rightSide);
            $result['geo_targeting'] = $this->detectGeo($rightSide);

            // Extract sub-category: text between client name and objective/funnel
            $result['sub_category'] = $this->extractSubCategoryFromCompact($rightSide);

            if (empty($result['funnel_stage'])) {
                $result['funnel_stage'] = $this->objectiveToFunnel($result['objective']);
            }

            return $result;
        }

        // Multi-part but not full format — scan each part
        foreach ($parts as $part) {
            if (empty($result['objective'])) {
                $result['objective'] = $this->detectObjective($part);
            }
            if (empty($result['funnel_stage'])) {
                $result['funnel_stage'] = $this->detectFunnel($part);
            }
            if (empty($result['channel_type'])) {
                $result['channel_type'] = $this->detectChannel($part);
            }
            if (empty($result['geo_targeting'])) {
                $result['geo_targeting'] = $this->detectGeo($part);
            }
        }

        if (empty($result['funnel_stage'])) {
            $result['funnel_stage'] = $this->objectiveToFunnel($result['objective']);
        }

        return $result;
    }

    /**
     * Parse underscore-prefixed names: VM_Mancini's_Sleepworld_... or FB_Dar AlArkan...
     */
    private function parsePrefixed(string $name): array
    {
        $result = [];
        $nameLower = strtolower($name);

        // Check for 2-letter prefix at start
        if (preg_match('/^([a-z]{2})[_-]/i', $name, $matches)) {
            $prefix = strtolower($matches[1]);

            // Platform prefix (FB, IG, YT)
            if (in_array($prefix, ['fb', 'ig', 'yt'])) {
                $result['channel_type'] = $this->detectChannel($prefix);
            }
            // Agency prefix (RB, VM)
            elseif (in_array($prefix, ['rb', 'vm'])) {
                // Agency prefix doesn't indicate channel
            }
        }

        // Extract geo
        $result['geo_targeting'] = $this->detectGeo($name);

        // Extract objective
        $result['objective'] = $this->detectObjective($name);

        // Extract funnel
        $result['funnel_stage'] = $this->detectFunnel($name);
        if (empty($result['funnel_stage'])) {
            $result['funnel_stage'] = $this->objectiveToFunnel($result['objective']);
        }

        // Extract channel from content keywords if not found from prefix
        if (empty($result['channel_type'])) {
            $result['channel_type'] = $this->detectChannel($name);
        }

        // Extract sub-category from underscore segments
        $result['sub_category'] = $this->extractSubCategoryFromUnderscore($name);

        return $result;
    }

    /**
     * Parse dash-delimited names: Burj Assila EOY Campaign - Leads - Abu Dhabi
     */
    private function parseDashDelimited(string $name): array
    {
        $result = [];
        $parts = array_map('trim', explode(' - ', $name));

        // Last part often contains geo or objective
        $lastPart = end($parts);
        $secondLastPart = $parts[count($parts) - 2] ?? '';

        // Check last part for geo
        $geo = $this->detectGeo($lastPart);
        if ($geo) {
            $result['geo_targeting'] = $geo;
        }

        // Check second-to-last for objective
        $obj = $this->detectObjective($secondLastPart);
        if ($obj) {
            $result['objective'] = $obj;
        }

        // If no objective in second-to-last, check last part
        if (empty($result['objective'])) {
            $result['objective'] = $this->detectObjective($lastPart);
        }

        // Check all parts for objective if still not found
        if (empty($result['objective'])) {
            foreach ($parts as $part) {
                $result['objective'] = $this->detectObjective($part);
                if ($result['objective']) break;
            }
        }

        // Detect funnel
        $result['funnel_stage'] = $this->detectFunnel($name);
        if (empty($result['funnel_stage'])) {
            $result['funnel_stage'] = $this->objectiveToFunnel($result['objective']);
        }

        // Detect channel
        $result['channel_type'] = $this->detectChannel($name);

        // Detect geo from full name if not found in last part
        if (empty($result['geo_targeting'])) {
            $result['geo_targeting'] = $this->detectGeo($name);
        }

        // Extract sub-category from first part (campaign name descriptor)
        $result['sub_category'] = $this->extractSubCategoryFromDash($parts);

        return $result;
    }

    /**
     * Parse generic camelCase/concatenated names: ShangrilaIGShangPalaceMay22
     */
    private function parseGeneric(string $name): array
    {
        $result = [];
        $nameLower = strtolower($name);

        // Try to extract objective
        $result['objective'] = $this->detectObjective($name);

        // Try to extract funnel
        $result['funnel_stage'] = $this->detectFunnel($name);
        if (empty($result['funnel_stage'])) {
            $result['funnel_stage'] = $this->objectiveToFunnel($result['objective']);
        }

        // Try to extract channel from embedded platform codes
        if (str_contains($nameLower, 'ig')) {
            $result['channel_type'] = 'instagram';
        } elseif (str_contains($nameLower, 'fb')) {
            $result['channel_type'] = 'facebook';
        } elseif (str_contains($nameLower, 'yt')) {
            $result['channel_type'] = 'youtube';
        }

        // Detect geo
        $result['geo_targeting'] = $this->detectGeo($name);

        return $result;
    }

    /**
     * Normalize text for matching (replace underscores with spaces)
     */
    private function normalizeText(string $text): string
    {
        return strtolower(str_replace(['_', '-', '|'], ' ', $text));
    }

    /**
     * Detect objective from text using keyword matching
     */
    private function detectObjective(string $text): ?string
    {
        $textLower = $this->normalizeText($text);
        $bestObjective = null;
        $bestScore = 0;

        foreach ($this->objectiveKeywords as $objective => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                // Whole word match (higher score)
                if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/', $textLower)) {
                    $score += 10;
                }
                // Partial match (lower score) — only for keywords 3+ chars
                elseif (strlen($keyword) >= 3 && strpos($textLower, $keyword) !== false) {
                    $score += 3;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestObjective = $objective;
            }
        }

        return $bestObjective;
    }

    /**
     * Detect funnel stage from text
     */
    private function detectFunnel(string $text): ?string
    {
        $textLower = $this->normalizeText($text);

        foreach ($this->funnelKeywords as $stage => $keywords) {
            foreach ($keywords as $keyword) {
                if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/', $textLower)) {
                    return $stage;
                }
            }
        }

        return null;
    }

    /**
     * Map objective to funnel stage when explicit funnel keyword not found
     */
    private function objectiveToFunnel(?string $objective): ?string
    {
        return match ($objective) {
            'awareness', 'reach', 'video_views' => 'TOF',
            'traffic', 'engagement', 'messages' => 'MOF',
            'leads', 'sales', 'conversions', 'calls', 'app_installs' => 'BOF',
            default => null,
        };
    }

    /**
     * Detect geo targeting from text
     */
    private function detectGeo(string $text): ?string
    {
        $textLower = $this->normalizeText($text);

        foreach ($this->geoKeywords as $canonical => $keywords) {
            foreach ($keywords as $keyword) {
                if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/', $textLower)) {
                    return $canonical;
                }
            }
        }

        return null;
    }

    /**
     * Detect channel/platform from text
     */
    private function detectChannel(string $text): ?string
    {
        $textLower = $this->normalizeText($text);
        $bestChannel = null;
        $bestScore = 0;

        foreach ($this->channelKeywords as $channel => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/', $textLower)) {
                    $score += 10;
                } elseif (strlen($keyword) >= 3 && strpos($textLower, $keyword) !== false) {
                    // Only allow partial matches for keywords 3+ chars
                    $score += 3;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestChannel = $channel;
            }
        }

        return $bestChannel;
    }

    /**
     * Detect industry from client/brand name in campaign name or account name
     */
    private function detectIndustryFromName(string $campaignName, ?string $accountName): ?string
    {
        $searchTexts = [
            strtolower($campaignName),
            strtolower(str_replace([' ', '-', '_', "'", '[', ']', '(', ')', '&'], '', $campaignName)),
        ];

        if ($accountName) {
            $searchTexts[] = strtolower($accountName);
            $searchTexts[] = strtolower(str_replace([' ', '-', '_', "'", '[', ']', '(', ')', '&'], '', $accountName));
        }

        // Collect all matches across all texts, then return the longest match
        $bestMatch = null;
        $bestLength = 0;

        foreach ($searchTexts as $text) {
            foreach ($this->clientIndustryMap as $client => $industry) {
                if (strpos($text, $client) !== false && strlen($client) > $bestLength) {
                    $bestLength = strlen($client);
                    $bestMatch = $industry;
                }
            }
        }

        return $bestMatch;
    }

    /**
     * Build client-to-industry mapping from existing ad accounts + manual overrides
     */
    private function buildClientIndustryMap(): array
    {
        return Cache::remember('campaign_naming_parser:client_industry_map', 3600, function () {
            $mapping = [];

            // Build from existing ad accounts with industries
            $accounts = AdAccount::whereNotNull('industry')
                ->where('industry', '!=', 'N/A')
                ->get();

            foreach ($accounts as $account) {
                $names = [
                    strtolower($account->account_name),
                    strtolower(str_replace([' ', '-', '_', "'", '[', ']', '(', ')', '&'], '', $account->account_name)),
                    strtolower(str_replace([' ', '-', '_'], '', $account->account_name)),
                ];
                foreach ($names as $name) {
                    if (strlen($name) > 2) {
                        $mapping[$name] = $account->industry;
                    }
                }
            }

            // Manual overrides for common brand names that may not match account names exactly
            $overrides = [
                'kohler' => 'e_commerce_retail',
                'mancini' => 'e_commerce_retail',
                'mancinissleepworld' => 'e_commerce_retail',
                'sleepworld' => 'e_commerce_retail',
                'burjassila' => 'real_estate',
                'daralarkan' => 'real_estate',
                'daralarkanonline' => 'real_estate',
                'shangrila' => 'travel_tourism',
                'shangri-la' => 'travel_tourism',
                'teamlab' => 'travel_tourism',
                'hrc' => 'government_public_sector',
                'qudra' => 'education',
                'hajar' => 'education',
                'tamakkon' => 'healthcare',
                'atharna' => 'travel_tourism',
                'testahel' => 'food_beverage',
                'barn' => 'food_beverage',
                'nahr' => 'technology',
                'rowad' => 'finance_insurance',
                'alshiaka' => 'fashion_luxury',
                'albalad' => 'travel_tourism',
                'jeddahalbalad' => 'travel_tourism',
                'peony' => 'e_commerce_retail',
                'kal' => 'food_beverage',
                'kalcoffee' => 'food_beverage',
                'spaceylon' => 'fmcg_cpg',
                'roca' => 'home_garden',
                'binsaidan' => 'real_estate',
                'faisalbinsaedan' => 'real_estate',
                'mainzilia' => 'technology',
                'zkra' => 'technology',
                'hala' => 'hospitality_food_services',
                'battlepark' => 'entertainment',
                'waed' => 'finance_insurance',
                'alittihad' => 'e_commerce_retail',
                'dyaralharam' => 'real_estate',
                'bouvardia' => 'real_estate',
                'saheel' => 'real_estate',
                'fbs' => 'real_estate',
                'jadarah' => 'education',
                'qasrmall' => 'e_commerce_retail',
                'monshaat' => 'government_public_sector',
                'snd' => 'travel_tourism',
                'tempurpedic' => 'e_commerce_retail',
                'sealy' => 'e_commerce_retail',
                'beautyrest' => 'e_commerce_retail',
                'helix' => 'e_commerce_retail',
                'aireloom' => 'e_commerce_retail',
                'serta' => 'e_commerce_retail',
                'alrashid' => 'real_estate',
                'alfursan' => 'real_estate',
                'cura' => 'healthcare',
                'autohub' => 'automotive',
                'ghs' => 'professional_services',
                'rbp' => 'professional_services',
                'rb' => 'professional_services',
                'vm' => 'e_commerce_retail',
            ];

            return array_merge($mapping, $overrides);
        });
    }

    /**
     * Calculate confidence score for parsed result
     */
    private function calculateConfidence(array $result): float
    {
        $score = 0;
        $max = 7;

        if ($result['objective']) $score++;
        if ($result['funnel_stage']) $score++;
        if ($result['geo_targeting']) $score++;
        if ($result['channel_type']) $score++;
        if ($result['industry']) $score++;
        if ($result['category']) $score++;
        if ($result['sub_category']) $score++;

        return round($score / $max, 2);
    }

    /**
     * Known product/brand names for sub-category extraction
     */
    private array $knownProducts = [
        'tempurpedic', 'tempur', 'sealy', 'beautyrest', 'helix', 'aireloom', 'serta',
        's&f', 'stearns', 'stearns & foster', 'purple', 'nectar', 'casper',
        'kohler', 'roca', 'duravit', 'grohe', 'hansgrohe',
        'bouvardia', 'saheel',
    ];

    /**
     * Clean and validate sub-category string
     */
    private function cleanSubCategory(string $text): ?string
    {
        $text = trim($text, ' -_|');
        if (empty($text) || strlen($text) < 3) {
            return null;
        }

        // Skip if it's just a date, objective, geo, or language
        $skipPatterns = [
            '/^\d+\s*(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)/i',
            '/\b\d{1,2}\s*[\-\–]\s*\d{1,2}\s*(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)/i',  // "22 - 30 Sept"
            '/\b\d{1,2}(st|nd|rd|th)\s*(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)/i',  // "14th July"
            '/\b(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\b/i',  // any standalone month
            '/^\d{4}-\d{2}-\d{2}$/',  // ISO dates like "2026-05-06"
            '/^\d{4}$/',  // standalone years
            '/^(awareness|traffic|leads|sales|engagement|conversion|purchase|calls|app installs|video views|messages|video view|reach|impressions|clicks)$/i',
            '/^(tof|mof|bof)$/i',
            '/^(ksa|uae|qatar|usa|riyadh|jeddah|dammam|makkah|madinah|dubai|abudhabi|abu dhabi)$/i',
            '/^(ar|en|ar & en|bilingual)$/i',
            '/^(search|pmax|gdn|ig|fb|yt|meta|google)$/i',
            '/^(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)$/i',
        ];
        foreach ($skipPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return null;
            }
        }

        // Skip if the text contains objective words as whole words (e.g. "Taj Lifestyle Awareness 22")
        $objectiveWords = ['awareness', 'traffic', 'leads', 'sales', 'engagement', 'conversion', 'purchase', 'calls', 'video views', 'video view', 'reach', 'impressions', 'clicks', 'messages', 'lead generation', 'followers', 'branding', 'awarness', 'acquisition', 'website visits', 'instant forms', 'freelancer registration', 'registration', 'forms', 'ppe', 'demand gen', 'video consideration'];
        $textLower = strtolower($text);
        foreach ($objectiveWords as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $textLower)) {
                return null;
            }
        }

        // Skip if any individual word is a generic word (e.g. "latest Promotion" → "latest")
        $genericWordList = ['campaign', 'new', 'all', 'general', 'winning', 'copy', 'launch', 'promotion', 'organic', 'black', 'white', 'red', 'blue', 'green', 'old', 'web', 'cpc', 'index', 'followers', 'branding', 'post', 'real estate', 'website', 'brand', 'non-brand', 'non', 'demand gen', 'latest', 'generic', 'demand', 'gen', 'main', 'duplicated', 'warm', 'yt.com', 'm/c', 'bay', 'area', 'san', 'audience', 'east', 'spanish', 'box', 'locations'];
        foreach (preg_split('/[\s\-]+/', $textLower) as $word) {
            if (in_array($word, $genericWordList)) {
                return null;
            }
        }

        // Skip generic words that don't add value as sub-categories
        $genericWords = ['campaign', 'new', 'all', 'general', 'winning', 'copy', 'launch', 'promotion', 'organic', 'black', 'white', 'red', 'blue', 'green', 'old', 'web', 'cpc', 'index', 'followers', 'branding', 'post', 'real estate', 'website', 'brand', 'non-brand', 'non', 'demand gen', 'latest'];
        if (in_array(strtolower($text), $genericWords)) {
            return null;
        }

        // Skip test/caution campaign names
        if (preg_match('/\b(dont activate|test campaign|try anything|careful with budgets)\b/i', $text)) {
            return null;
        }

        // Skip purely numeric strings (e.g. "00044")
        if (preg_match('/^\d+$/', $text)) {
            return null;
        }

        // Skip non-Latin text (Arabic, Thai, etc.) — likely post text or objectives in other languages
        if (preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{0E00}-\x{0E7F}]/u', $text)) {
            return null;
        }

        // Skip if text is mostly channel/platform names
        $channelNames = ['google ads', 'youtube', 'linkedin', 'tiktok', 'facebook', 'instagram', 'snapchat', 'twitter', 'meta'];
        foreach ($channelNames as $ch) {
            if (preg_match('/\b' . preg_quote($ch, '/') . '\b/i', $text)) {
                return null;
            }
        }

        return $text;
    }

    /**
     * Extract sub-category from compact pipe format: "Client - Funnel - Campaign Type"
     * Compact format rarely has clear sub-categories; return null unless unambiguous.
     */
    private function extractSubCategoryFromCompact(string $rightSide): ?string
    {
        // Only extract if the right side has a clear campaign type descriptor
        // that is NOT the client name and NOT an objective/funnel/month
        $nameLower = strtolower($rightSide);

        // Check for known campaign type patterns
        if (preg_match('/\b(eoy|launch|promotion|sale|event|competition|giveaway|season)\b/i', $nameLower, $matches)) {
            // Extract the phrase containing the keyword
            if (preg_match('/\b\w*\s*(eoy|launch|promotion|sale|event|competition|giveaway|season)\s*\w*\b/i', $rightSide, $fullMatch)) {
                return $this->cleanSubCategory($fullMatch[0]);
            }
        }

        return null;
    }

    /**
     * Extract sub-category from underscore format segments
     */
    private function extractSubCategoryFromUnderscore(string $name): ?string
    {
        
        $segments = explode('_', $name);
        if (count($segments) < 4) {
            
            return null;
        }

        // Remove first segment (agency/platform prefix)
        array_shift($segments);

        // Build comprehensive skip list from client industry map + manual additions
        $skipWords = array_merge(
            array_keys($this->clientIndustryMap),
            [
                'mancini', 'mancinis', 'sleepworld', 'dar', 'arkan', 'alarkan', 'online',
                'new', 'all', 'general', 'winning', 'burj', 'assila', 'hajar', 'qudra',
                'hala', 'testahel', 'shangrila', 'kohler', 'battle', 'park', 'reberto',
                'cavali', 'qasrmall', 'daralarkanonline', 'daralarkan', 'monshaat',
                'binsaidan', 'faisalbinsaedan', 'mainzilia', 'zkra', 'ghs', 'rbp', 'rb', 'vm',
                'nashar', 'albalad', 'jeddahalbalad', 'alrashid', 'alfursan', 'dyaralharam',
                'bouvardia', 'saheel', 'fbs', 'peony', 'tamakkon', 'atharna', 'nahr',
                'alshiaka', 'rowad', 'waed', 'alittihad', 'autohub', 'cura', 'snd',
                'teamlab', 'spaceylon', 'spaceylonksa', 'organic', 'seco', 'dyar',
                'dyaralharam', 'fbs', 'afaq', 'nashar', 'monshaat', 'index',
            ]
        );

        // Build comprehensive skip list for this method
        $objectiveWords = ['purchase', 'awareness', 'engagement', 'traffic', 'sales', 'conversion', 'leads', 'videoview', 'video view', 'video views', 'store', 'shop', 'views', 'video', 'reach', 'impressions', 'clicks', 'messages', 'calls', 'lead', 'app', 'installs', 'acquisition', 'branding', 'followers', 'awarness', 'lead generation', 'post', 'gen'];
        $channelWords = ['google', 'ads', 'youtube', 'linkedin', 'tiktok', 'facebook', 'instagram', 'snapchat', 'twitter', 'meta'];
        $genericWords = ['web', 'cpc', 'all-lang', 'all', 'lang', 'index', 'real', 'estate', 'dont', 'activate', 'test', 'campaign', 'main', 'duplicated', 'warm', 'yt.com', 'm/c', 'san', 'audience', 'east', 'spanish', 'box', 'locations'];
        $allSkip = array_merge($objectiveWords, $channelWords, $genericWords, $skipWords);
        $segments = array_values(array_filter($segments, function ($seg) use ($allSkip) {
            $segLower = strtolower(trim($seg));
            if (in_array($segLower, $allSkip)) return false;
            // Also reject if any word inside is a skip word
            foreach (preg_split('/[\s\-]+/', $segLower) as $word) {
                if (in_array($word, $allSkip)) return false;
            }
            return true;
        }));

        // Check for known product names first
        $nameLower = strtolower(str_replace("'", '', $name));
        foreach ($this->knownProducts as $product) {
            if (strpos($nameLower, strtolower(str_replace("'", '', $product))) !== false) {
                
                return $product;
            }
        }

        foreach ($segments as $segment) {
            $segTrimmed = trim($segment);
            if (strlen($segTrimmed) < 3) {
                continue;
            }

            // Check concatenated version (e.g. "daralarkanonline")
            $segConcat = strtolower(str_replace(["'", ' ', '-', '_'], '', $segTrimmed));
            if (in_array($segConcat, $skipWords) || in_array($segConcat, ['ugc', 'meta'])) {
                continue;
            }

            // Check each individual word within the segment (e.g. "Dar AlArkan Online" → "dar", "alarkan", "online")
            $words = preg_split('/[\s\-]+/', $segTrimmed);
            $isClientName = false;
            foreach ($words as $word) {
                $wordClean = strtolower(preg_replace('/[^a-z0-9]/', '', $word));
                if (in_array($wordClean, $skipWords) || in_array($wordClean, ['ugc', 'meta'])) {
                    $isClientName = true;
                    break;
                }
            }
            if ($isClientName) {
                continue;
            }

            // Prefer segments with mixed case (likely product names), but validate first
            if (preg_match('/[A-Z]/', $segTrimmed) && !ctype_upper($segTrimmed)) {
                $cleaned = $this->cleanSubCategory($segTrimmed);
                if ($cleaned !== null) {
                    
                    return $cleaned;
                }
            }
        }

        
        return null;
    }

    /**
     * Extract sub-category from dash-delimited parts
     */
    private function extractSubCategoryFromDash(array $parts): ?string
    {
        if (count($parts) < 2) {
            return null;
        }

        // First part usually contains campaign name + descriptor
        $firstPart = $parts[0];

        // Skip if it's a social media post (very long, starts with "Post:")
        if (str_starts_with(strtolower(trim($firstPart)), 'post:') || strlen($firstPart) > 40) {
            return null;
        }

        // Build dynamic skip patterns from client industry map keys (min 3 chars)
        $clientPatterns = [];
        foreach ($this->clientIndustryMap as $client => $industry) {
            if (strlen($client) >= 3 && !in_array($client, ['rb', 'vm'])) {
                $clientPatterns[] = $client;
            }
        }

        // Static patterns for common multi-word names that need special handling
        $staticPatterns = [
            'burj assila', 'burjassila', 'kohler', 'mancini', "mancini's", 'shangrila',
            'testahel', 'hala', 'hajar', 'qudra', 'tamakkon', 'battle park', 'battlepark',
            'mainzilia', 'red bananas', 'dar al arkan', 'daralarkan', 'dar alarkan', 'dar alarkan online',
            'almaddah', 'ribs yard', 'ribsyard', 'barns', 'nashar', 'afaq', 'al rashid', 'alrashid',
            'alfursan', 'zkra', 'ghs', 'spaceylon', 'spaceylonksa', 'reberto cavali',
            'rebertocavali', 'qasr mall', 'qasrmall', 'monshaat', 'binsaidan',
            'faisal bin saedan', 'faisalbinsaedan', 'dyar alharam', 'dyaralharam',
            'bouvardia', 'saheel', 'fbs', 'jeddah albalad', 'jeddahalbalad', 'al balad',
            'albalad', 'teamlab', 'kalcoffee', 'kal coffee', 'peony sa', 'peonysa',
            'nahr', 'al shiaka', 'alshiaka', 'rowad sedco', 'rowadsedco', 'waed',
            'al ittihad', 'alittihad', 'auto hub', 'autohub', 'cura', 'snd',
            'tempurpedic', 'sealy', 'beautyrest', 'helix', 'aireloom', 'serta',
            'stearns & foster', 'stearns', 'foster', 'purple', 'nectar', 'casper',
        ];

        // Sort by length descending so longer matches replace first
        usort($staticPatterns, fn($a, $b) => strlen($b) <=> strlen($a));
        usort($clientPatterns, fn($a, $b) => strlen($b) <=> strlen($a));

        // Combine and deduplicate
        $allPatterns = array_unique(array_merge($staticPatterns, $clientPatterns));

        // Build regex alternation (chunk into groups of 50 to avoid regex length limits)
        $chunks = array_chunk($allPatterns, 50);
        foreach ($chunks as $chunk) {
            $regex = '/\b(' . implode('|', array_map(fn($p) => preg_quote($p, '/'), $chunk)) . ')\b/i';
            $firstPart = preg_replace($regex, '', $firstPart);
        }

        // Strip agency prefixes and brackets from beginning
        $firstPart = preg_replace('/^\s*\[?\s*(RB|VM|RBP)\s*\]?\s*/i', '', $firstPart);
        $firstPart = trim($firstPart, ' -_|');

        // If what's left is just a generic word, return null
        $remainingLower = strtolower($firstPart);
        if (in_array($remainingLower, ['awareness', 'traffic', 'leads', 'sales', 'engagement', 'conversion', 'campaign', ''])) {
            return null;
        }

        return $this->cleanSubCategory($firstPart);
    }

    /**
     * Map parsed objective to ad_campaigns.objective ENUM value
     */
    public static function canonicalObjective(?string $objective): ?string
    {
        return match ($objective) {
            'awareness', 'reach', 'video_views', 'traffic' => 'awareness',
            'leads', 'messages', 'app_installs', 'registration' => 'leads',
            'sales', 'conversions', 'purchase', 'shop', 'store' => 'sales',
            'calls', 'phone' => 'calls',
            'engagement', 'post_engagement', 'likes', 'comments', 'shares' => 'engagement',
            default => null,
        };
    }

    /**
     * Clear the cached client-industry mapping
     */
    public function clearCache(): void
    {
        Cache::forget('campaign_naming_parser:client_industry_map');
    }
}
