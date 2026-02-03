/**
 * Industry Aggregator Utility
 * Aggregates ad account data by industry
 */

import { convertToSAR } from './currencyConverter'

export interface AdAccount {
  id: number
  account_name: string
  external_account_id: string
  platform: string
  status: string
  industry: string | null
  category: string | null
  currency: string
  campaigns_count: number
  total_spend: number
  total_impressions: number
  total_clicks: number
  total_conversions: number
  total_revenue: number
  created_at: string
  updated_at: string
  campaigns?: any[]
}

export interface CategoryData {
  category: string
  category_display: string
  accounts_count: number
  total_impressions: number
  total_spend: number
  total_clicks: number
  total_conversions: number
  total_revenue: number
  impressions_formatted: string
}

export interface IndustryData {
  industry: string
  industry_display: string
  accounts_count: number
  total_impressions: number
  total_spend: number
  total_clicks?: number
  total_conversions?: number
  total_revenue?: number
  impressions_formatted: string
  currencies: string[]
  categories?: CategoryData[]
  has_categories?: boolean
}

export interface AggregatedData {
  industries: IndustryData[]
  totals: {
    total_industries: number
    total_accounts: number
    total_impressions: number
    total_spend: number
  }
}

/**
 * Format a number to a human-readable string (e.g., 1.5M, 234K)
 */
function formatNumber(num: number): string {
  if (num >= 1000000000) {
    return (num / 1000000000).toFixed(1) + 'B'
  } else if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toString()
}

/**
 * Get display name for industry
 */
function getIndustryDisplayName(industry: string): string {
  const displayNames: Record<string, string> = {
    'automotive': 'Automotive',
    'beauty_fitness': 'Beauty & Fitness',
    'business_industrial': 'Business & Industrial',
    'computers_electronics': 'Computers & Electronics',
    'education': 'Education',
    'entertainment': 'Entertainment',
    'finance_insurance': 'Finance & Insurance',
    'food_beverage': 'Food & Beverage',
    'health_medicine': 'Health & Medicine',
    'home_garden': 'Home & Garden',
    'law_government': 'Law & Government',
    'lifestyle': 'Lifestyle',
    'media_publishing': 'Media & Publishing',
    'nonprofit': 'Non-Profit',
    'real_estate': 'Real Estate',
    'retail_ecommerce': 'Retail & E-commerce',
    'sports_recreation': 'Sports & Recreation',
    'technology': 'Technology',
    'travel_tourism': 'Travel & Tourism',
    'transportation_logistics': 'Transportation & Logistics',
    'other': 'Other'
  }

  return displayNames[industry] || industry.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

/**
 * Get display name for category
 */
function getCategoryDisplayName(category: string | null): string {
  if (!category) return 'Uncategorized'
  return category.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

/**
 * Aggregate accounts by industry
 */
export function aggregateByIndustry(accounts: AdAccount[]): AggregatedData {
  // Filter accounts with industry set
  const accountsWithIndustry = accounts.filter(acc => acc.industry)

  // Group by industry
  const industryGroups: Record<string, AdAccount[]> = {}

  accountsWithIndustry.forEach(account => {
    const industry = account.industry!
    if (!industryGroups[industry]) {
      industryGroups[industry] = []
    }
    industryGroups[industry].push(account)
  })

  // Aggregate data for each industry
  const industries: IndustryData[] = Object.entries(industryGroups).map(([industry, accounts]) => {
    let totalSpendSAR = 0
    let totalImpressions = 0
    let totalClicks = 0
    let totalConversions = 0
    let totalRevenue = 0
    const currencies = new Set<string>()

    accounts.forEach(account => {
      // Spend is already in SAR in the database - no conversion needed
      const spendSAR = account.total_spend || 0
      totalSpendSAR += spendSAR

      // Sum all metrics
      totalImpressions += account.total_impressions || 0
      totalClicks += account.total_clicks || 0
      totalConversions += account.total_conversions || 0
      totalRevenue += account.total_revenue || 0

      currencies.add(account.currency)
    })

    return {
      industry,
      industry_display: getIndustryDisplayName(industry),
      accounts_count: accounts.length,
      total_impressions: totalImpressions,
      total_spend: totalSpendSAR,
      total_clicks: totalClicks,
      total_conversions: totalConversions,
      total_revenue: totalRevenue,
      impressions_formatted: formatNumber(totalImpressions),
      currencies: Array.from(currencies)
    }
  })

  // Sort by total spend descending
  industries.sort((a, b) => b.total_spend - a.total_spend)

  // Calculate totals
  const totals = {
    total_industries: industries.length,
    total_accounts: accountsWithIndustry.length,
    total_impressions: industries.reduce((sum, ind) => sum + ind.total_impressions, 0),
    total_spend: industries.reduce((sum, ind) => sum + ind.total_spend, 0)
  }

  return {
    industries,
    totals
  }
}

/**
 * Aggregate accounts by industry with category breakdown
 */
export function aggregateByIndustryWithCategories(accounts: AdAccount[]): AggregatedData {
  // Filter accounts with industry set
  const accountsWithIndustry = accounts.filter(acc => acc.industry)

  // Group by industry
  const industryGroups: Record<string, AdAccount[]> = {}

  accountsWithIndustry.forEach(account => {
    const industry = account.industry!
    if (!industryGroups[industry]) {
      industryGroups[industry] = []
    }
    industryGroups[industry].push(account)
  })

  // Aggregate data for each industry with categories
  const industries: IndustryData[] = Object.entries(industryGroups).map(([industry, accounts]) => {
    let totalSpendSAR = 0
    let totalImpressions = 0
    let totalClicks = 0
    let totalConversions = 0
    let totalRevenue = 0
    const currencies = new Set<string>()

    // Group accounts by category within this industry
    const categoryGroups: Record<string, AdAccount[]> = {}

    accounts.forEach(account => {
      const category = account.category || 'uncategorized'
      if (!categoryGroups[category]) {
        categoryGroups[category] = []
      }
      categoryGroups[category].push(account)

      // Also calculate industry totals
      // Spend is already in SAR in the database - no conversion needed
      const spendSAR = account.total_spend || 0
      totalSpendSAR += spendSAR
      totalImpressions += account.total_impressions || 0
      totalClicks += account.total_clicks || 0
      totalConversions += account.total_conversions || 0
      totalRevenue += account.total_revenue || 0
      currencies.add(account.currency)
    })

    // Aggregate data for each category
    const categories: CategoryData[] = Object.entries(categoryGroups).map(([category, categoryAccounts]) => {
      let catSpendSAR = 0
      let catImpressions = 0
      let catClicks = 0
      let catConversions = 0
      let catRevenue = 0

      categoryAccounts.forEach(account => {
        // Spend is already in SAR in the database - no conversion needed
        catSpendSAR += account.total_spend || 0
        catImpressions += account.total_impressions || 0
        catClicks += account.total_clicks || 0
        catConversions += account.total_conversions || 0
        catRevenue += account.total_revenue || 0
      })

      return {
        category: category,
        category_display: getCategoryDisplayName(category === 'uncategorized' ? null : category),
        accounts_count: categoryAccounts.length,
        total_impressions: catImpressions,
        total_spend: catSpendSAR,
        total_clicks: catClicks,
        total_conversions: catConversions,
        total_revenue: catRevenue,
        impressions_formatted: formatNumber(catImpressions)
      }
    })

    // Sort categories by spend descending
    categories.sort((a, b) => b.total_spend - a.total_spend)

    return {
      industry,
      industry_display: getIndustryDisplayName(industry),
      accounts_count: accounts.length,
      total_impressions: totalImpressions,
      total_spend: totalSpendSAR,
      total_clicks: totalClicks,
      total_conversions: totalConversions,
      total_revenue: totalRevenue,
      impressions_formatted: formatNumber(totalImpressions),
      currencies: Array.from(currencies),
      categories: categories,
      has_categories: categories.length > 0
    }
  })

  // Sort by total spend descending
  industries.sort((a, b) => b.total_spend - a.total_spend)

  // Calculate totals
  const totals = {
    total_industries: industries.length,
    total_accounts: accountsWithIndustry.length,
    total_impressions: industries.reduce((sum, ind) => sum + ind.total_impressions, 0),
    total_spend: industries.reduce((sum, ind) => sum + ind.total_spend, 0)
  }

  return {
    industries,
    totals
  }
}

/**
 * Get unique industries from accounts
 */
export function getUniqueIndustries(accounts: AdAccount[]): string[] {
  const industries = new Set<string>()

  accounts.forEach(account => {
    if (account.industry) {
      industries.add(account.industry)
    }
  })

  return Array.from(industries).sort()
}

/**
 * Filter accounts by industry
 */
export function filterByIndustry(accounts: AdAccount[], industry: string): AdAccount[] {
  return accounts.filter(acc => acc.industry === industry)
}

/**
 * Filter accounts by platform
 */
export function filterByPlatform(accounts: AdAccount[], platform: string): AdAccount[] {
  if (!platform) return accounts
  return accounts.filter(acc => acc.platform === platform)
}

/**
 * Filter accounts by status
 */
export function filterByStatus(accounts: AdAccount[], status: string): AdAccount[] {
  if (!status) return accounts
  return accounts.filter(acc => acc.status === status)
}
