<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class IndustryDetectionService
{
    private array $industryKeywords = [
        'ecommerce' => [
            'shop', 'store', 'retail', 'ecommerce', 'e-commerce', 'marketplace', 'fashion', 'clothing', 'apparel',
            'electronics', 'gadgets', 'accessories', 'jewelry', 'beauty', 'cosmetics', 'home', 'furniture'
        ],
        'healthcare' => [
            'health', 'medical', 'clinic', 'hospital', 'dental', 'pharmacy', 'wellness', 'fitness', 'nutrition',
            'therapy', 'care', 'treatment', 'medicine', 'doctor', 'nurse', 'patient'
        ],
        'finance' => [
            'bank', 'finance', 'financial', 'insurance', 'loan', 'credit', 'investment', 'trading', 'wealth',
            'mortgage', 'accounting', 'tax', 'advisor', 'broker', 'fund', 'capital'
        ],
        'education' => [
            'education', 'school', 'university', 'college', 'academy', 'learning', 'training', 'course',
            'tutor', 'teach', 'student', 'academic', 'curriculum', 'degree', 'certification'
        ],
        'technology' => [
            'tech', 'software', 'app', 'digital', 'web', 'mobile', 'development', 'programming', 'coding',
            'cloud', 'ai', 'artificial intelligence', 'saas', 'platform', 'system', 'solution'
        ],
        'real_estate' => [
            'real estate', 'property', 'home', 'house', 'apartment', 'rental', 'mortgage', 'realtor',
            'construction', 'building', 'development', 'commercial', 'residential', 'land'
        ],
        'automotive' => [
            'car', 'auto', 'vehicle', 'automotive', 'truck', 'motorcycle', 'dealership', 'repair',
            'maintenance', 'parts', 'service', 'garage', 'mechanic', 'driving'
        ],
        'food' => [
            'restaurant', 'food', 'dining', 'cafe', 'bar', 'catering', 'delivery', 'takeout',
            'pizza', 'burger', 'kitchen', 'chef', 'menu', 'recipe', 'cooking'
        ],
        'travel' => [
            'travel', 'hotel', 'vacation', 'tourism', 'flight', 'airline', 'booking', 'resort',
            'tour', 'trip', 'destination', 'cruise', 'adventure', 'hospitality'
        ],
        'entertainment' => [
            'entertainment', 'music', 'movie', 'game', 'gaming', 'sports', 'event', 'concert',
            'theater', 'show', 'artist', 'performer', 'media', 'streaming'
        ],
        'professional_services' => [
            'consulting', 'legal', 'law', 'attorney', 'lawyer', 'marketing', 'advertising', 'agency',
            'design', 'architecture', 'engineering', 'professional', 'service', 'business'
        ]
    ];

    public function detectIndustry(string $text): ?string
    {
        if (empty($text)) {
            return null;
        }

        $text = strtolower($text);
        $industryScores = [];

        foreach ($this->industryKeywords as $industry => $keywords) {
            $score = 0;

            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $score += $this->getKeywordWeight($keyword);
                }
            }

            if ($score > 0) {
                $industryScores[$industry] = $score;
            }
        }

        if (empty($industryScores)) {
            Log::info('Industry detection: No matches found', ['text' => $text]);
            return null;
        }

        arsort($industryScores);
        $detectedIndustry = array_key_first($industryScores);
        $confidence = $industryScores[$detectedIndustry];

        Log::info('Industry detection result', [
            'text' => $text,
            'detected_industry' => $detectedIndustry,
            'confidence_score' => $confidence,
            'all_scores' => $industryScores
        ]);

        return $detectedIndustry;
    }

    private function getKeywordWeight(string $keyword): int
    {
        $length = strlen($keyword);

        if ($length <= 3) {
            return 1;
        } elseif ($length <= 6) {
            return 2;
        } elseif ($length <= 10) {
            return 3;
        } else {
            return 4;
        }
    }

    public function getIndustryDisplayName(string $industry): string
    {
        return match ($industry) {
            'ecommerce' => 'E-commerce & Retail',
            'healthcare' => 'Healthcare & Wellness',
            'finance' => 'Financial Services',
            'education' => 'Education & Training',
            'technology' => 'Technology & Software',
            'real_estate' => 'Real Estate',
            'automotive' => 'Automotive',
            'food' => 'Food & Beverage',
            'travel' => 'Travel & Hospitality',
            'entertainment' => 'Entertainment & Media',
            'professional_services' => 'Professional Services',
            default => ucfirst(str_replace('_', ' ', $industry))
        };
    }

    public function getAllIndustries(): array
    {
        return array_keys($this->industryKeywords);
    }

    public function getIndustryKeywords(string $industry): array
    {
        return $this->industryKeywords[$industry] ?? [];
    }
}