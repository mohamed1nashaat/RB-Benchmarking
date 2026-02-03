<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BusinessLookupService
{
    /**
     * Known business mappings for common Saudi/regional companies
     * Direct mapping of business names to industries
     */
    private static array $knownBusinesses = [
        // Real Estate Companies
        'jadara' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'jadarah' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'al-diyar' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'aldiyar' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'diyar' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'bin saedan' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'bin seadan' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'saedan' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'alfursan' => ['industry' => 'real_estate', 'category' => 'Real Estate Agencies'],
        'tamakkon' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'hajar' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'burj assila' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'rowad' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'rua al madinah' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],

        // Finance & Investment
        'sedco' => ['industry' => 'finance_insurance', 'category' => 'Investments & Wealth Management'],
        'waed' => ['industry' => 'finance_insurance', 'category' => 'Investments & Wealth Management'],
        'wa\'ed' => ['industry' => 'finance_insurance', 'category' => 'Investments & Wealth Management'],
        'monshaat' => ['industry' => 'finance_insurance', 'category' => 'Fintech'],

        // Technology
        'zkra' => ['industry' => 'technology', 'category' => 'Software / SaaS'],
        'mainzilia' => ['industry' => 'technology', 'category' => 'Software / SaaS'],
        'shift' => ['industry' => 'technology', 'category' => 'Software / SaaS'],
        'digital mobility' => ['industry' => 'technology', 'category' => 'Software / SaaS'],

        // Sports & Entertainment
        'al-itihad' => ['industry' => 'entertainment_media', 'category' => 'Sports & Events'],
        'al-ittihad' => ['industry' => 'entertainment_media', 'category' => 'Sports & Events'],
        'itihad' => ['industry' => 'entertainment_media', 'category' => 'Sports & Events'],
        'ittihad' => ['industry' => 'entertainment_media', 'category' => 'Sports & Events'],
        'fc' => ['industry' => 'entertainment_media', 'category' => 'Sports & Events'],
        'teamlab' => ['industry' => 'entertainment_media', 'category' => 'Local Attractions & Experiences'],
        'borderless' => ['industry' => 'entertainment_media', 'category' => 'Local Attractions & Experiences'],
        'makkah expo' => ['industry' => 'entertainment_media', 'category' => 'Sports & Events'],
        'expo' => ['industry' => 'entertainment_media', 'category' => 'Sports & Events'],

        // Home & Garden
        'kohler' => ['industry' => 'home_garden', 'category' => 'Home & Furniture'],
        'mancini' => ['industry' => 'home_garden', 'category' => 'Home & Furniture'],
        'sleepworld' => ['industry' => 'home_garden', 'category' => 'Home & Furniture'],

        // Beauty & Wellness
        'spa ceylon' => ['industry' => 'beauty_fitness', 'category' => 'Personal Care'],
        'spa' => ['industry' => 'beauty_fitness', 'category' => 'Wellness & Nutrition'],
        'cura' => ['industry' => 'healthcare', 'category' => 'Hospitals & Clinics'],

        // Education
        'academy' => ['industry' => 'education', 'category' => 'Training & Certification Centers'],
        'school' => ['industry' => 'education', 'category' => 'K–12 Schools'],

        // Marketing & Advertising Agency
        'red bananas' => ['industry' => 'professional_services', 'category' => 'Marketing & Advertising'],
        'rb benchmarks' => ['industry' => 'professional_services', 'category' => 'Marketing & Advertising'],

        // HR & Recruitment
        'hrc' => ['industry' => 'professional_services', 'category' => 'HR & Recruitment'],
        'ghs' => ['industry' => 'professional_services', 'category' => 'Consulting'],
        'nahr' => ['industry' => 'professional_services', 'category' => 'HR & Recruitment'],

        // Retail
        'testahel' => ['industry' => 'retail_ecommerce', 'category' => 'Direct-to-Consumer (D2C)'],
        'alrashed' => ['industry' => 'retail_ecommerce', 'category' => 'Marketplaces'],
        'peony' => ['industry' => 'retail_ecommerce', 'category' => 'Fashion & Apparel'],

        // Additional businesses
        'aldyar' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'ventures' => ['industry' => 'finance_insurance', 'category' => 'Investments & Wealth Management'],

        // More Saudi businesses
        'dar alarkan' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'alarkan' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'jaddarah' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'al-shiaka' => ['industry' => 'fashion_luxury', 'category' => 'Designer Apparel'],
        'alshiaka' => ['industry' => 'fashion_luxury', 'category' => 'Designer Apparel'],
        'shiaka' => ['industry' => 'fashion_luxury', 'category' => 'Designer Apparel'],
        'dar althikr' => ['industry' => 'education', 'category' => 'K–12 Schools'],
        'althikr' => ['industry' => 'education', 'category' => 'K–12 Schools'],
        'albalad' => ['industry' => 'entertainment_media', 'category' => 'Local Attractions & Experiences'],
        'al balad' => ['industry' => 'entertainment_media', 'category' => 'Local Attractions & Experiences'],
        'shangrila' => ['industry' => 'hospitality', 'category' => 'Hotels & Resorts'],
        'shangri-la' => ['industry' => 'hospitality', 'category' => 'Hotels & Resorts'],
        'roca' => ['industry' => 'home_garden', 'category' => 'Building Materials'],
        'nashar' => ['industry' => 'retail_ecommerce', 'category' => 'Direct-to-Consumer (D2C)'],
        'bloom' => ['industry' => 'retail_ecommerce', 'category' => 'Fashion & Apparel'],
        'ribsyard' => ['industry' => 'food_beverage', 'category' => 'Restaurants & Cafes'],
        'ribs' => ['industry' => 'food_beverage', 'category' => 'Restaurants & Cafes'],
        'qudra' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'atharna' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'ruaa' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'rua' => ['industry' => 'real_estate', 'category' => 'Residential Projects'],
        'codestan' => ['industry' => 'technology', 'category' => 'Software / SaaS'],
        'viatra' => ['industry' => 'technology', 'category' => 'Software / SaaS'],
        'rmeez' => ['industry' => 'technology', 'category' => 'Software / SaaS'],
        'hala' => ['industry' => 'technology', 'category' => 'Software / SaaS'],
        'kal' => ['industry' => 'retail_ecommerce', 'category' => 'Fashion & Apparel'],

        // Additional mappings for remaining accounts
        'balad' => ['industry' => 'entertainment_media', 'category' => 'Local Attractions & Experiences'],
        'rb' => ['industry' => 'professional_services', 'category' => 'Marketing & Advertising'],
        'bc' => ['industry' => 'professional_services', 'category' => 'Marketing & Advertising'],
        'cas' => ['industry' => 'professional_services', 'category' => 'Consulting'],
    ];

    /**
     * Industry keywords for classification
     * Maps search terms to our industry taxonomy
     */
    private static array $industryKeywords = [
        'real_estate' => [
            'real estate', 'property', 'properties', 'residential', 'commercial', 'developer',
            'development', 'construction', 'building', 'housing', 'apartment', 'villa', 'land',
            'realty', 'homes', 'broker', 'عقار', 'عقارات', 'تطوير عقاري',
        ],
        'retail_ecommerce' => [
            'retail', 'store', 'shop', 'ecommerce', 'e-commerce', 'online shopping', 'marketplace',
            'fashion', 'clothing', 'apparel', 'electronics', 'furniture', 'متجر', 'تسوق',
        ],
        'technology' => [
            'technology', 'tech', 'software', 'saas', 'it services', 'digital', 'app', 'platform',
            'startup', 'innovation', 'ai', 'artificial intelligence', 'cloud', 'data', 'تقنية',
        ],
        'healthcare' => [
            'healthcare', 'health', 'medical', 'hospital', 'clinic', 'pharmaceutical', 'pharma',
            'medicine', 'doctor', 'patient', 'wellness', 'طبي', 'صحة', 'مستشفى',
        ],
        'finance_insurance' => [
            'finance', 'financial', 'bank', 'banking', 'insurance', 'investment', 'fintech',
            'loan', 'credit', 'wealth', 'asset', 'مالي', 'بنك', 'تأمين',
        ],
        'education' => [
            'education', 'school', 'university', 'college', 'training', 'learning', 'course',
            'academy', 'institute', 'tutoring', 'edtech', 'تعليم', 'مدرسة', 'جامعة',
        ],
        'food_beverage' => [
            'food', 'restaurant', 'cafe', 'coffee', 'catering', 'beverage', 'dining',
            'delivery', 'kitchen', 'cuisine', 'مطعم', 'طعام', 'قهوة',
        ],
        'travel_tourism' => [
            'travel', 'tourism', 'hotel', 'resort', 'airline', 'flight', 'vacation',
            'booking', 'trip', 'destination', 'سياحة', 'سفر', 'فندق',
        ],
        'automotive' => [
            'automotive', 'car', 'vehicle', 'auto', 'motor', 'dealer', 'automobile',
            'electric vehicle', 'ev', 'rental', 'سيارة', 'سيارات',
        ],
        'entertainment_media' => [
            'entertainment', 'media', 'streaming', 'music', 'film', 'movie', 'gaming',
            'sports', 'event', 'news', 'publishing', 'ترفيه', 'إعلام',
        ],
        'beauty_fitness' => [
            'beauty', 'cosmetics', 'skincare', 'fitness', 'gym', 'salon', 'spa',
            'wellness', 'personal care', 'جمال', 'لياقة',
        ],
        'fashion_luxury' => [
            'fashion', 'luxury', 'designer', 'jewelry', 'watches', 'apparel',
            'clothing', 'accessories', 'أزياء', 'مجوهرات',
        ],
        'telecommunications' => [
            'telecom', 'telecommunications', 'mobile', 'carrier', 'internet', 'isp',
            'network', 'communication', 'اتصالات',
        ],
        'energy_utilities' => [
            'energy', 'oil', 'gas', 'petroleum', 'renewable', 'solar', 'power',
            'electricity', 'utility', 'طاقة', 'نفط',
        ],
        'construction_manufacturing' => [
            'construction', 'manufacturing', 'industrial', 'factory', 'building materials',
            'engineering', 'contractor', 'بناء', 'تصنيع',
        ],
        'professional_services' => [
            'consulting', 'consultant', 'legal', 'law firm', 'accounting', 'marketing agency',
            'advertising', 'hr', 'recruitment', 'استشارات', 'قانوني',
        ],
        'transportation_logistics' => [
            'logistics', 'shipping', 'freight', 'courier', 'delivery', 'transport',
            'warehouse', 'supply chain', 'نقل', 'شحن',
        ],
        'agriculture' => [
            'agriculture', 'farming', 'agritech', 'food production', 'livestock',
            'crops', 'زراعة',
        ],
        'nonprofit' => [
            'nonprofit', 'non-profit', 'charity', 'foundation', 'ngo', 'humanitarian',
            'خيري', 'مؤسسة خيرية',
        ],
        'hospitality' => [
            'hospitality', 'hotel', 'resort', 'restaurant', 'catering', 'events',
            'ضيافة',
        ],
    ];

    /**
     * Clean account name for search
     */
    public function cleanAccountName(string $accountName): string
    {
        // Remove common prefixes
        $cleaned = preg_replace('/^(act_|urn:li:sponsoredAccount:)/i', '', $accountName);

        // Remove common suffixes
        $cleaned = preg_replace('/\s*(ads?|advertising|campaign|account|marketing)\s*$/i', '', $cleaned);

        // Remove special characters but keep Arabic
        $cleaned = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $cleaned);

        // Normalize whitespace
        $cleaned = preg_replace('/\s+/', ' ', trim($cleaned));

        return $cleaned;
    }

    /**
     * Check if account name matches a known business
     */
    private function matchKnownBusiness(string $accountName): ?array
    {
        $nameLower = strtolower($accountName);

        // Check longest matches first (more specific)
        $sortedBusinesses = self::$knownBusinesses;
        uksort($sortedBusinesses, fn($a, $b) => strlen($b) <=> strlen($a));

        foreach ($sortedBusinesses as $businessKey => $data) {
            if (str_contains($nameLower, strtolower($businessKey))) {
                return [
                    'industry' => $data['industry'],
                    'category' => $data['category'],
                    'confidence' => 0.95,
                    'source' => 'known_business',
                    'matched_keywords' => [$businessKey],
                ];
            }
        }

        return null;
    }

    /**
     * Lookup business information via web search
     */
    public function lookupBusiness(string $accountName): ?array
    {
        $cleanedName = $this->cleanAccountName($accountName);

        if (strlen($cleanedName) < 2) {
            return null;
        }

        // First, check known businesses (highest priority)
        $knownMatch = $this->matchKnownBusiness($cleanedName);
        if ($knownMatch) {
            return $knownMatch;
        }

        try {
            // Try to search for the business
            $searchQuery = "{$cleanedName} company industry";
            $searchResults = $this->performWebSearch($searchQuery);

            if ($searchResults) {
                $classification = $this->classifyFromSearchResults($searchResults, $cleanedName);

                if ($classification['confidence'] >= 0.5) {
                    return $classification;
                }
            }

            // Fallback to keyword-based detection from account name
            return $this->classifyFromAccountName($cleanedName);

        } catch (\Exception $e) {
            Log::warning("BusinessLookupService: Failed to lookup {$accountName}", [
                'error' => $e->getMessage(),
            ]);

            // Fallback to keyword-based detection
            return $this->classifyFromAccountName($cleanedName);
        }
    }

    /**
     * Perform web search using available methods
     */
    private function performWebSearch(string $query): ?string
    {
        // For now, we'll use keyword-based detection
        // In production, this could use:
        // - Google Custom Search API
        // - Bing Search API
        // - SerpAPI
        // - Clearbit Company API

        return null;
    }

    /**
     * Classify industry from search results
     */
    public function classifyFromSearchResults(string $content, string $accountName): array
    {
        $contentLower = strtolower($content);
        $scores = [];

        foreach (self::$industryKeywords as $industry => $keywords) {
            $score = 0;
            $matchedKeywords = [];

            foreach ($keywords as $keyword) {
                $keywordLower = strtolower($keyword);
                $count = substr_count($contentLower, $keywordLower);

                if ($count > 0) {
                    // Weight by keyword length (longer = more specific)
                    $weight = strlen($keyword) >= 6 ? 2 : 1;
                    $score += $count * $weight;
                    $matchedKeywords[] = $keyword;
                }
            }

            if ($score > 0) {
                $scores[$industry] = [
                    'score' => $score,
                    'keywords' => $matchedKeywords,
                ];
            }
        }

        if (empty($scores)) {
            return [
                'industry' => null,
                'category' => null,
                'confidence' => 0,
                'source' => 'no_match',
            ];
        }

        // Sort by score descending
        uasort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

        $topIndustry = array_key_first($scores);
        $topScore = $scores[$topIndustry]['score'];
        $totalScore = array_sum(array_column($scores, 'score'));

        // Confidence based on how dominant the top industry is
        $confidence = $totalScore > 0 ? min(1.0, $topScore / max($totalScore * 0.5, 1)) : 0;

        return [
            'industry' => $topIndustry,
            'category' => CategoryMapper::getDefaultCategory($topIndustry),
            'confidence' => round($confidence, 2),
            'source' => 'search_results',
            'matched_keywords' => $scores[$topIndustry]['keywords'] ?? [],
        ];
    }

    /**
     * Classify industry from account name using keyword matching
     */
    public function classifyFromAccountName(string $accountName): ?array
    {
        $nameLower = strtolower($accountName);
        $scores = [];

        foreach (self::$industryKeywords as $industry => $keywords) {
            $score = 0;
            $matchedKeywords = [];

            foreach ($keywords as $keyword) {
                $keywordLower = strtolower($keyword);

                // Check for exact word match or partial match
                if (str_contains($nameLower, $keywordLower)) {
                    // Weight longer keywords higher
                    $weight = strlen($keyword) >= 6 ? 3 : (strlen($keyword) >= 4 ? 2 : 1);
                    $score += $weight;
                    $matchedKeywords[] = $keyword;
                }
            }

            if ($score > 0) {
                $scores[$industry] = [
                    'score' => $score,
                    'keywords' => $matchedKeywords,
                ];
            }
        }

        if (empty($scores)) {
            return null;
        }

        // Sort by score descending
        uasort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

        $topIndustry = array_key_first($scores);
        $topScore = $scores[$topIndustry]['score'];

        // Confidence based on score (higher = more confident)
        $confidence = min(1.0, $topScore / 6); // 6 = max expected score from 2 keywords

        return [
            'industry' => $topIndustry,
            'category' => CategoryMapper::getDefaultCategory($topIndustry),
            'confidence' => round($confidence, 2),
            'source' => 'account_name',
            'matched_keywords' => $scores[$topIndustry]['keywords'] ?? [],
        ];
    }

    /**
     * Map external industry name to our taxonomy
     */
    public static function mapExternalIndustry(string $externalIndustry): ?string
    {
        $external = strtolower(trim($externalIndustry));

        // Direct mappings
        $mappings = [
            // Real Estate variations
            'real estate' => 'real_estate',
            'real estate development' => 'real_estate',
            'property development' => 'real_estate',
            'construction' => 'construction_manufacturing',
            'building' => 'construction_manufacturing',

            // Technology variations
            'technology' => 'technology',
            'software' => 'technology',
            'information technology' => 'technology',
            'it services' => 'technology',
            'saas' => 'technology',
            'software as a service' => 'technology',

            // Finance variations
            'finance' => 'finance_insurance',
            'financial services' => 'finance_insurance',
            'banking' => 'finance_insurance',
            'insurance' => 'finance_insurance',
            'fintech' => 'finance_insurance',

            // Retail variations
            'retail' => 'retail_ecommerce',
            'e-commerce' => 'retail_ecommerce',
            'ecommerce' => 'retail_ecommerce',
            'online retail' => 'retail_ecommerce',

            // Healthcare variations
            'healthcare' => 'healthcare',
            'health care' => 'healthcare',
            'medical' => 'healthcare',
            'pharmaceutical' => 'healthcare',
            'pharma' => 'healthcare',

            // Education variations
            'education' => 'education',
            'educational services' => 'education',
            'e-learning' => 'education',
            'edtech' => 'education',

            // Food & Beverage variations
            'food & beverage' => 'food_beverage',
            'food and beverage' => 'food_beverage',
            'restaurant' => 'food_beverage',
            'hospitality' => 'hospitality',

            // Travel variations
            'travel' => 'travel_tourism',
            'tourism' => 'travel_tourism',
            'travel & tourism' => 'travel_tourism',
            'hotels' => 'travel_tourism',

            // Automotive variations
            'automotive' => 'automotive',
            'automobile' => 'automotive',
            'motor vehicles' => 'automotive',

            // Media variations
            'media' => 'entertainment_media',
            'entertainment' => 'entertainment_media',
            'media & entertainment' => 'entertainment_media',

            // Telecom variations
            'telecommunications' => 'telecommunications',
            'telecom' => 'telecommunications',

            // Energy variations
            'energy' => 'energy_utilities',
            'oil & gas' => 'energy_utilities',
            'utilities' => 'energy_utilities',

            // Professional services
            'consulting' => 'professional_services',
            'professional services' => 'professional_services',
            'business services' => 'professional_services',
            'marketing' => 'professional_services',
            'advertising' => 'professional_services',

            // Logistics
            'logistics' => 'transportation_logistics',
            'transportation' => 'transportation_logistics',
            'shipping' => 'transportation_logistics',

            // Agriculture
            'agriculture' => 'agriculture',
            'farming' => 'agriculture',

            // Nonprofit
            'nonprofit' => 'nonprofit',
            'non-profit' => 'nonprofit',
            'charity' => 'nonprofit',
        ];

        // Try direct mapping
        if (isset($mappings[$external])) {
            return $mappings[$external];
        }

        // Try partial matching
        foreach ($mappings as $key => $industry) {
            if (str_contains($external, $key) || str_contains($key, $external)) {
                return $industry;
            }
        }

        return null;
    }

    /**
     * Get all available industries
     */
    public static function getAvailableIndustries(): array
    {
        return array_keys(self::$industryKeywords);
    }
}
