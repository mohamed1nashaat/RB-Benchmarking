<?php

namespace App\Services;

class IndustryDetector
{
    /**
     * Industry keywords mapping
     */
    private static array $industryKeywords = [
        'education' => [
            'school', 'academy', 'university', 'college', 'education', 'learning', 'institute', 
            'training', 'course', 'class', 'student', 'teacher', 'jadara', 'waad', 'althikr'
        ],
        'real_estate' => [
            'real estate', 'property', 'homes', 'apartments', 'villa', 'building', 'construction',
            'developer', 'burj', 'arkan', 'diyar', 'realty', 'housing'
        ],
        'retail' => [
            'store', 'shop', 'retail', 'shopping', 'market', 'mall', 'ecommerce', 'fashion',
            'clothing', 'alshiaka', 'shiaka', 'brand', 'boutique'
        ],
        'healthcare' => [
            'health', 'medical', 'hospital', 'clinic', 'doctor', 'care', 'wellness', 'pharmacy'
        ],
        'technology' => [
            'tech', 'software', 'app', 'digital', 'IT', 'computer', 'mobile', 'platform',
            'system', 'solution', 'shift', 'codestand'
        ],
        'automotive' => [
            'car', 'auto', 'vehicle', 'automotive', 'garage', 'mechanic', 'parts', 'autohub'
        ],
        'food_beverage' => [
            'food', 'restaurant', 'cafe', 'kitchen', 'dining', 'catering', 'beverage', 'ribs',
            'maddah', 'almaddah', 'testahel', 'esso'
        ],
        'travel_tourism' => [
            'travel', 'tourism', 'hotel', 'resort', 'destination', 'jeddah', 'shangri', 'teamlab'
        ],
        'finance' => [
            'bank', 'finance', 'financial', 'investment', 'insurance', 'sedco', 'holding'
        ],
        'entertainment' => [
            'entertainment', 'media', 'production', 'creative', 'design', 'agency', 'nahr'
        ],
        'sports' => [
            'sport', 'football', 'soccer', 'club', 'team', 'athletic', 'ittihad', 'united'
        ],
        'home_garden' => [
            'home', 'furniture', 'interior', 'design', 'kohler', 'bathroom', 'kitchen', 'sleepworld', 'mattress'
        ]
    ];

    /**
     * Detect industry based on account name
     */
    public static function detectIndustry(string $accountName): ?string
    {
        $accountNameLower = strtolower($accountName);
        
        foreach (self::$industryKeywords as $industry => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($accountNameLower, strtolower($keyword)) !== false) {
                    return $industry;
                }
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

    /**
     * Get industry display name
     */
    public static function getIndustryDisplayName(string $industry): string
    {
        $displayNames = [
            'education' => 'Education',
            'real_estate' => 'Real Estate',
            'retail' => 'Retail & E-commerce',
            'healthcare' => 'Healthcare',
            'technology' => 'Technology',
            'automotive' => 'Automotive',
            'food_beverage' => 'Food & Beverage',
            'travel_tourism' => 'Travel & Tourism',
            'finance' => 'Finance',
            'entertainment' => 'Entertainment & Media',
            'sports' => 'Sports',
            'home_garden' => 'Home & Garden'
        ];

        return $displayNames[$industry] ?? ucfirst(str_replace('_', ' ', $industry));
    }
}