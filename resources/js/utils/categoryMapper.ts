/**
 * Category Mapper Utility
 * Maps industries to their available categories
 * Ported from App\Services\CategoryMapper.php
 */

const industryCategories: Record<string, string[]> = {
  'automotive': [
    'Vehicles',
    'Auto Dealers & Retail',
    'Auto Parts & Services',
    'Electric Vehicles',
    'Rentals & Leasing',
    'Motorsports',
  ],
  'travel_tourism': [
    'Tourism Boards',
    'Airlines',
    'Hotels & Resorts',
    'Cruises',
    'Online Travel Agencies',
    'Local Attractions & Experiences',
  ],
  'retail_ecommerce': [
    'Fashion & Apparel',
    'Electronics',
    'Home & Furniture',
    'Beauty & Cosmetics',
    'Grocery & Food Delivery',
    'Marketplaces',
    'Direct-to-Consumer (D2C)',
  ],
  'retail': [
    'Fashion & Apparel',
    'Electronics',
    'Home & Furniture',
    'Beauty & Cosmetics',
    'Marketplaces',
  ],
  'education': [
    'K–12 Schools',
    'Universities & Colleges',
    'Online Courses / EdTech',
    'Training & Certification Centers',
    'Tutoring & Test Prep',
  ],
  'food_beverage': [
    'Food & Beverages',
    'Restaurants & Cafes',
    'Food Manufacturing',
    'Grocery & Food Delivery',
    'Delivery Apps / Cloud Kitchens',
  ],
  'real_estate': [
    'Residential Projects',
    'Commercial Developments',
    'Real Estate Agencies',
    'Property Portals',
    'Construction & Contracting',
  ],
  'finance_insurance': [
    'Banking',
    'Fintech',
    'Investments & Wealth Management',
    'Insurance',
    'Credit & Loans',
  ],
  'health_medicine': [
    'Hospitals & Clinics',
    'Health Insurance',
    'Pharmaceuticals',
    'Medical Devices',
    'Wellness & Nutrition',
    'Telemedicine',
  ],
  'healthcare': [
    'Hospitals & Clinics',
    'Health Insurance',
    'Pharmaceuticals',
    'Medical Devices',
    'Wellness & Nutrition',
    'Telemedicine',
  ],
  'technology': [
    'Software / SaaS',
    'Hardware & Devices',
    'IT Services',
    'Telecommunications',
    'Gaming & Esports',
    'AI & Emerging Tech',
  ],
  'media_publishing': [
    'Streaming Services',
    'News & Publishing',
    'Music & Film',
    'Influencers & Creators',
  ],
  'entertainment_media': [
    'Streaming Services',
    'Sports & Events',
    'Music & Film',
    'News & Publishing',
    'Influencers & Creators',
    'Gaming & Esports',
  ],
  'hospitality': [
    'Restaurants & Cafes',
    'Hotels & Resorts',
    'Catering & Events',
    'Delivery Apps / Cloud Kitchens',
  ],
  'energy_utilities': [
    'Oil & Gas',
    'Renewable Energy',
    'Power & Electricity',
    'Water & Waste Management',
    'Environmental Services',
  ],
  'construction_manufacturing': [
    'Construction Firms',
    'Industrial Manufacturing',
    'Building Materials',
    'Engineering & Contracting',
    'Heavy Equipment',
  ],
  'fashion_luxury': [
    'Luxury Goods',
    'Jewelry & Watches',
    'Designer Apparel',
    'Footwear & Accessories',
  ],
  'beauty_fitness': [
    'Beauty & Cosmetics',
    'Wellness & Nutrition',
    'Personal Care',
  ],
  'telecommunications': [
    'Mobile Carriers',
    'Internet Service Providers',
    'Communication Apps',
    'Smart Devices',
  ],
  'transportation_logistics': [
    'Shipping & Freight',
    'Courier & Delivery',
    'Ports & Aviation Logistics',
    'Railways & Metro Systems',
  ],
  'nonprofit': [
    'Charities',
    'Fundraising Platforms',
    'Awareness Campaigns',
    'Environmental & Humanitarian Causes',
  ],
  'agriculture': [
    'Farming & Agritech',
    'Food Manufacturing',
    'Fisheries & Livestock',
    'Agricultural Equipment',
  ],
  'professional_services': [
    'Consulting',
    'Marketing & Advertising',
    'Legal',
    'Accounting & Auditing',
    'HR & Recruitment',
  ],
  'home_garden': [
    'Home & Furniture',
    'Building Materials',
  ],
  'other': [
    'Other',
  ],
}

/**
 * Get all categories for a specific industry
 */
export function getCategoriesForIndustry(industry: string | null | undefined): string[] {
  if (!industry) {
    return []
  }

  const normalizedIndustry = industry.toLowerCase().replace(/[ -]/g, '_')

  return industryCategories[normalizedIndustry] || []
}

/**
 * Get all unique categories across all industries
 */
export function getAllCategories(): string[] {
  const allCategories: string[] = []

  Object.values(industryCategories).forEach(categories => {
    allCategories.push(...categories)
  })

  return Array.from(new Set(allCategories))
}

/**
 * Get the default category for an industry (first one in the list)
 */
export function getDefaultCategory(industry: string | null | undefined): string | null {
  const categories = getCategoriesForIndustry(industry)

  return categories[0] || null
}

/**
 * Validate if a category belongs to the specified industry
 */
export function isValidCategoryForIndustry(category: string | null | undefined, industry: string | null | undefined): boolean {
  if (!category || !industry) {
    return false
  }

  const validCategories = getCategoriesForIndustry(industry)

  return validCategories.includes(category)
}

/**
 * Auto-detect category from account name
 */
export function detectCategory(accountName: string, industry: string | null | undefined): string | null {
  if (!industry) {
    return null
  }

  const accountNameLower = accountName.toLowerCase()
  const categories = getCategoriesForIndustry(industry)

  // Try to match category keywords in account name
  for (const category of categories) {
    const categoryKeywords = category.toLowerCase().split(' ')

    for (const keyword of categoryKeywords) {
      if (keyword.length > 3 && accountNameLower.includes(keyword)) {
        return category
      }
    }
  }

  // Return default category if no match found
  return getDefaultCategory(industry)
}

/**
 * Get industry display name with category structure
 */
export function getIndustryCategoryTree(): Record<string, {
  industry: string
  display_name: string
  categories: string[]
  category_count: number
}> {
  const tree: Record<string, any> = {}

  Object.entries(industryCategories).forEach(([industry, categories]) => {
    const displayName = industry.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
    tree[industry] = {
      industry,
      display_name: displayName,
      categories,
      category_count: categories.length,
    }
  })

  return tree
}
