<?php

namespace App\Services;

use App\Models\Industry;
use App\Models\SubIndustry;

class CategoryMapper
{
    /**
     * Get all categories (sub_industries) for a specific industry from database
     */
    public static function getCategoriesForIndustry(?string $industry): array
    {
        if (!$industry) {
            return [];
        }

        // Find the industry by name (slug)
        $industryModel = Industry::where('name', $industry)->first();

        if (!$industryModel) {
            return [];
        }

        // Get all sub_industries (account categories) for this industry
        return $industryModel->subIndustries()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->pluck('display_name')
            ->toArray();
    }

    /**
     * Get all unique categories across all industries
     */
    public static function getAllCategories(): array
    {
        return SubIndustry::where('is_active', true)
            ->orderBy('display_name')
            ->distinct()
            ->pluck('display_name')
            ->toArray();
    }

    /**
     * Get the default category for an industry (first one in the list)
     */
    public static function getDefaultCategory(?string $industry): ?string
    {
        $categories = self::getCategoriesForIndustry($industry);

        return $categories[0] ?? null;
    }

    /**
     * Validate if a category belongs to the specified industry
     */
    public static function isValidCategoryForIndustry(?string $category, ?string $industry): bool
    {
        if (!$category || !$industry) {
            return false;
        }

        $validCategories = self::getCategoriesForIndustry($industry);

        return in_array($category, $validCategories);
    }

    /**
     * Auto-detect category from account name
     */
    public static function detectCategory(string $accountName, ?string $industry): ?string
    {
        if (!$industry) {
            return null;
        }

        $accountNameLower = strtolower($accountName);
        $categories = self::getCategoriesForIndustry($industry);

        // Try to match category keywords in account name
        foreach ($categories as $category) {
            $categoryKeywords = explode(' ', strtolower($category));

            foreach ($categoryKeywords as $keyword) {
                if (strlen($keyword) > 3 && str_contains($accountNameLower, $keyword)) {
                    return $category;
                }
            }
        }

        // Return default category if no match found
        return self::getDefaultCategory($industry);
    }

    /**
     * Get industry display name with category structure
     */
    public static function getIndustryCategoryTree(): array
    {
        $tree = [];

        $industries = Industry::with(['subIndustries' => function ($query) {
            $query->where('is_active', true)->orderBy('sort_order')->orderBy('display_name');
        }])->where('is_active', true)->orderBy('sort_order')->get();

        foreach ($industries as $industry) {
            $tree[$industry->name] = [
                'industry' => $industry->name,
                'display_name' => $industry->display_name,
                'categories' => $industry->subIndustries->pluck('display_name')->toArray(),
                'category_count' => $industry->subIndustries->count(),
            ];
        }

        return $tree;
    }
}
