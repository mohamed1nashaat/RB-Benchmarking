/**
 * Benchmark Calculator Utility
 * Calculates industry benchmarks from ad account metrics data
 */

import type { AdAccount } from './industryAggregator'

export interface BenchmarkMetric {
  actual: number | null
  benchmark: {
    min: number
    max: number
    avg: number
  }
  performance: number | null
  status: string
}

export interface IndustryBenchmark {
  industry: string
  accounts_count: number
  account_names?: string[]
  total_spend: number
  total_impressions: number
  total_clicks: number
  total_leads: number
  metrics: Record<string, BenchmarkMetric>
}

interface Filters {
  platform?: string
  objective?: string
  funnel_stage?: string
  user_journey?: string
  industry?: string
  sub_industry?: string
  has_pixel_data?: string
  target_segment?: string
  age_group?: string
  geo_targeting?: string
  messaging_tone?: string
}

/**
 * Calculate derived metrics from account data
 */
function calculateMetrics(accounts: AdAccount[]) {
  // Spend and revenue are already in SAR in the database - no conversion needed
  const totalSpend = accounts.reduce((sum, acc) => sum + (acc.total_spend || 0), 0)
  const totalImpressions = accounts.reduce((sum, acc) => sum + (acc.total_impressions || 0), 0)
  const totalClicks = accounts.reduce((sum, acc) => sum + (acc.total_clicks || 0), 0)
  const totalConversions = accounts.reduce((sum, acc) => sum + (acc.total_conversions || 0), 0)
  const totalRevenue = accounts.reduce((sum, acc) => sum + (acc.total_revenue || 0), 0)

  // Calculate derived metrics
  const ctr = totalImpressions > 0 ? (totalClicks / totalImpressions) * 100 : 0
  const cpc = totalClicks > 0 ? totalSpend / totalClicks : 0
  const cpm = totalImpressions > 0 ? (totalSpend / totalImpressions) * 1000 : 0
  const cvr = totalClicks > 0 ? (totalConversions / totalClicks) * 100 : 0
  const cpl = totalConversions > 0 ? totalSpend / totalConversions : 0
  const roas = totalSpend > 0 ? totalRevenue / totalSpend : 0

  return {
    totalSpend,
    totalImpressions,
    totalClicks,
    totalConversions,
    totalRevenue,
    ctr,
    cpc,
    cpm,
    cvr,
    cpl,
    roas
  }
}

/**
 * Calculate per-account metrics for min/max/avg benchmarks
 */
export function calculateAccountMetrics(account: AdAccount) {
  // Spend and revenue are already in SAR in the database - no conversion needed
  const spend = account.total_spend || 0
  const impressions = account.total_impressions || 0
  const clicks = account.total_clicks || 0
  const conversions = account.total_conversions || 0
  const revenue = account.total_revenue || 0

  return {
    ctr: impressions > 0 ? (clicks / impressions) * 100 : 0,
    cpc: clicks > 0 ? spend / clicks : 0,
    cpm: impressions > 0 ? (spend / impressions) * 1000 : 0,
    cvr: clicks > 0 ? (conversions / clicks) * 100 : 0,
    cpl: conversions > 0 ? spend / conversions : 0,
    roas: spend > 0 ? revenue / spend : 0
  }
}

/**
 * Remove statistical outliers using IQR method with domain-specific thresholds
 */
function removeOutliers(values: number[], metric: string): number[] {
  if (values.length < 4) {
    return values // Need at least 4 values for IQR
  }

  const sorted = [...values].sort((a, b) => a - b)
  const q1Index = Math.floor(sorted.length * 0.25)
  const q3Index = Math.floor(sorted.length * 0.75)
  const q1 = sorted[q1Index]
  const q3 = sorted[q3Index]

  // Calculate IQR
  const iqr = q3 - q1

  // Define bounds (1.5 × IQR is standard for outlier detection)
  let lowerBound = q1 - (1.5 * iqr)
  let upperBound = q3 + (1.5 * iqr)

  // Apply domain-specific minimum thresholds for cost metrics
  const domainMinThresholds: Record<string, number> = {
    cpc: 0.40,  // CPC below SAR 0.40 is typically data quality issues or brand campaigns
    cpl: 5.00,  // CPL below SAR 5.00 is unrealistic for lead generation
    cpm: 1.00,  // CPM below SAR 1.00 is unrealistic for performance campaigns
  }

  if (domainMinThresholds[metric]) {
    lowerBound = Math.max(lowerBound, domainMinThresholds[metric])
  }

  // Filter outliers
  return sorted.filter(v => v >= lowerBound && v <= upperBound)
}

/**
 * Calculate benchmark stats (min, max, avg) for a metric across accounts
 */
function calculateBenchmarkStats(values: number[], metric: string = ''): { min: number; max: number; avg: number } {
  if (values.length === 0) {
    return { min: 0, max: 0, avg: 0 }
  }

  // Filter out zero values for more realistic benchmarks
  let nonZeroValues = values.filter(v => v > 0)

  if (nonZeroValues.length === 0) {
    return { min: 0, max: 0, avg: 0 }
  }

  // Apply outlier filtering for cost metrics
  if (metric && ['cpc', 'cpl', 'cpm'].includes(metric)) {
    nonZeroValues = removeOutliers(nonZeroValues, metric)

    if (nonZeroValues.length === 0) {
      return { min: 0, max: 0, avg: 0 }
    }
  }

  const min = Math.min(...nonZeroValues)
  const max = Math.max(...nonZeroValues)
  const avg = nonZeroValues.reduce((sum, v) => sum + v, 0) / nonZeroValues.length

  return { min, max, avg }
}

/**
 * Calculate performance score (0-100) comparing actual to benchmark
 * For cost metrics (CPC, CPL, CPM), lower is better
 * For performance metrics (CTR, CVR, ROAS), higher is better
 */
function calculatePerformance(actual: number, min: number, max: number, lowerIsBetter: boolean = false): number {
  if (max === min) return 50 // If no range, return average

  let score: number

  if (lowerIsBetter) {
    // For cost metrics, lower is better
    // If actual is at min (best), score = 100
    // If actual is at max (worst), score = 0
    score = ((max - actual) / (max - min)) * 100
  } else {
    // For performance metrics, higher is better
    // If actual is at max (best), score = 100
    // If actual is at min (worst), score = 0
    score = ((actual - min) / (max - min)) * 100
  }

  // Clamp between 0 and 100
  return Math.max(0, Math.min(100, score))
}

/**
 * Get status label based on performance score
 */
function getStatus(performance: number): string {
  if (performance >= 85) return 'excellent'
  if (performance >= 70) return 'good'
  if (performance >= 55) return 'average'
  return 'below_average'
}

/**
 * Calculate industry benchmarks from ad accounts data
 */
export function calculateIndustryBenchmarks(
  accounts: AdAccount[],
  filters: Filters = {}
): Record<string, IndustryBenchmark> {
  // Filter accounts based on filters
  let filteredAccounts = accounts.filter(acc => acc.industry) // Only accounts with industry

  if (filters.platform) {
    filteredAccounts = filteredAccounts.filter(acc => acc.platform === filters.platform)
  }

  if (filters.industry) {
    filteredAccounts = filteredAccounts.filter(acc => acc.industry === filters.industry)
  }

  // Group accounts by industry
  const accountsByIndustry: Record<string, AdAccount[]> = {}

  filteredAccounts.forEach(account => {
    const industry = account.industry!
    if (!accountsByIndustry[industry]) {
      accountsByIndustry[industry] = []
    }
    accountsByIndustry[industry].push(account)
  })

  // Calculate benchmarks for each industry
  const benchmarks: Record<string, IndustryBenchmark> = {}

  Object.entries(accountsByIndustry).forEach(([industry, industryAccounts]) => {
    // Calculate aggregate metrics for the industry
    const aggregateMetrics = calculateMetrics(industryAccounts)

    // Calculate per-account metrics for benchmarks
    const accountMetrics = industryAccounts.map(acc => calculateAccountMetrics(acc))

    // Calculate benchmark stats for each metric
    const ctrStats = calculateBenchmarkStats(accountMetrics.map(m => m.ctr))
    const cpcStats = calculateBenchmarkStats(accountMetrics.map(m => m.cpc), 'cpc')
    const cpmStats = calculateBenchmarkStats(accountMetrics.map(m => m.cpm), 'cpm')
    const cvrStats = calculateBenchmarkStats(accountMetrics.map(m => m.cvr))
    const cplStats = calculateBenchmarkStats(accountMetrics.map(m => m.cpl), 'cpl')
    const roasStats = calculateBenchmarkStats(accountMetrics.map(m => m.roas))

    // Calculate performance scores
    const ctrPerformance = calculatePerformance(aggregateMetrics.ctr, ctrStats.min, ctrStats.max, false)
    const cpcPerformance = calculatePerformance(aggregateMetrics.cpc, cpcStats.min, cpcStats.max, true)
    const cpmPerformance = calculatePerformance(aggregateMetrics.cpm, cpmStats.min, cpmStats.max, true)
    const cvrPerformance = calculatePerformance(aggregateMetrics.cvr, cvrStats.min, cvrStats.max, false)
    const cplPerformance = calculatePerformance(aggregateMetrics.cpl, cplStats.min, cplStats.max, true)
    const roasPerformance = calculatePerformance(aggregateMetrics.roas, roasStats.min, roasStats.max, false)

    benchmarks[industry] = {
      industry,
      accounts_count: industryAccounts.length,
      account_names: industryAccounts.map(acc => acc.account_name),
      total_spend: aggregateMetrics.totalSpend,
      total_impressions: aggregateMetrics.totalImpressions,
      total_clicks: aggregateMetrics.totalClicks,
      total_leads: aggregateMetrics.totalConversions, // Using conversions as leads
      metrics: {
        ctr: {
          actual: aggregateMetrics.ctr,
          benchmark: ctrStats,
          performance: ctrPerformance,
          status: getStatus(ctrPerformance)
        },
        cpc: {
          actual: aggregateMetrics.cpc,
          benchmark: cpcStats,
          performance: cpcPerformance,
          status: getStatus(cpcPerformance)
        },
        cpm: {
          actual: aggregateMetrics.cpm,
          benchmark: cpmStats,
          performance: cpmPerformance,
          status: getStatus(cpmPerformance)
        },
        cvr: {
          actual: aggregateMetrics.cvr,
          benchmark: cvrStats,
          performance: cvrPerformance,
          status: getStatus(cvrPerformance)
        },
        cpl: {
          actual: aggregateMetrics.cpl,
          benchmark: cplStats,
          performance: cplPerformance,
          status: getStatus(cplPerformance)
        },
        roas: {
          actual: aggregateMetrics.roas,
          benchmark: roasStats,
          performance: roasPerformance,
          status: getStatus(roasPerformance)
        }
      }
    }
  })

  return benchmarks
}

/**
 * Calculate aggregated benchmarks from ALL ad accounts (without requiring industry classification)
 * Groups all accounts under "all_industries" key for cross-industry comparison
 */
export function calculateAggregatedBenchmarks(
  accounts: AdAccount[],
  filters: Filters = {}
): Record<string, IndustryBenchmark> {
  // Filter accounts - include ALL accounts with data (no industry requirement)
  let filteredAccounts = accounts.filter(acc =>
    (acc.total_impressions || 0) > 0 ||
    (acc.total_clicks || 0) > 0
  )

  if (filters.platform) {
    filteredAccounts = filteredAccounts.filter(acc => acc.platform === filters.platform)
  }

  // If no accounts with data, return empty
  if (filteredAccounts.length === 0) {
    return {}
  }

  // Calculate aggregate metrics for ALL accounts
  const aggregateMetrics = calculateMetrics(filteredAccounts)

  // Calculate per-account metrics for benchmarks
  const accountMetrics = filteredAccounts.map(acc => calculateAccountMetrics(acc))

  // Calculate benchmark stats for each metric
  const ctrStats = calculateBenchmarkStats(accountMetrics.map(m => m.ctr))
  const cpcStats = calculateBenchmarkStats(accountMetrics.map(m => m.cpc), 'cpc')
  const cpmStats = calculateBenchmarkStats(accountMetrics.map(m => m.cpm), 'cpm')
  const cvrStats = calculateBenchmarkStats(accountMetrics.map(m => m.cvr))
  const cplStats = calculateBenchmarkStats(accountMetrics.map(m => m.cpl), 'cpl')
  const roasStats = calculateBenchmarkStats(accountMetrics.map(m => m.roas))

  // Calculate performance scores
  const ctrPerformance = calculatePerformance(aggregateMetrics.ctr, ctrStats.min, ctrStats.max, false)
  const cpcPerformance = calculatePerformance(aggregateMetrics.cpc, cpcStats.min, cpcStats.max, true)
  const cpmPerformance = calculatePerformance(aggregateMetrics.cpm, cpmStats.min, cpmStats.max, true)
  const cvrPerformance = calculatePerformance(aggregateMetrics.cvr, cvrStats.min, cvrStats.max, false)
  const cplPerformance = calculatePerformance(aggregateMetrics.cpl, cplStats.min, cplStats.max, true)
  const roasPerformance = calculatePerformance(aggregateMetrics.roas, roasStats.min, roasStats.max, false)

  // Return as "all_industries" group
  return {
    'all_industries': {
      industry: 'all_industries',
      accounts_count: filteredAccounts.length,
      account_names: filteredAccounts.map(acc => acc.account_name),
      total_spend: aggregateMetrics.totalSpend,
      total_impressions: aggregateMetrics.totalImpressions,
      total_clicks: aggregateMetrics.totalClicks,
      total_leads: aggregateMetrics.totalConversions,
      metrics: {
        ctr: {
          actual: aggregateMetrics.ctr,
          benchmark: ctrStats,
          performance: ctrPerformance,
          status: getStatus(ctrPerformance)
        },
        cpc: {
          actual: aggregateMetrics.cpc,
          benchmark: cpcStats,
          performance: cpcPerformance,
          status: getStatus(cpcPerformance)
        },
        cpm: {
          actual: aggregateMetrics.cpm,
          benchmark: cpmStats,
          performance: cpmPerformance,
          status: getStatus(cpmPerformance)
        },
        cvr: {
          actual: aggregateMetrics.cvr,
          benchmark: cvrStats,
          performance: cvrPerformance,
          status: getStatus(cvrPerformance)
        },
        cpl: {
          actual: aggregateMetrics.cpl,
          benchmark: cplStats,
          performance: cplPerformance,
          status: getStatus(cplPerformance)
        },
        roas: {
          actual: aggregateMetrics.roas,
          benchmark: roasStats,
          performance: roasPerformance,
          status: getStatus(roasPerformance)
        }
      }
    }
  }
}

/**
 * Competitive Intelligence Data Structure
 */
export interface CompetitiveIntelligence {
  market_rank: number | null
  percentile: number
  total_competitors: number
  opportunity_score: number
  insights: Array<{
    type: string
    title: string
    description: string
    impact_level: string
  }>
}

/**
 * Calculate competitive intelligence from ad accounts data
 * Compares user's accounts against all other accounts in the same industry
 */
export function calculateCompetitiveIntelligence(
  accounts: AdAccount[],
  filters: Filters = {}
): CompetitiveIntelligence {
  // Filter user's accounts by industry
  let userIndustryAccounts = accounts.filter(acc => acc.industry)

  if (filters.industry) {
    userIndustryAccounts = userIndustryAccounts.filter(acc => acc.industry === filters.industry)
  }

  // Filter out accounts with no metrics data (only include accounts with actual performance data)
  const accountsWithData = userIndustryAccounts.filter(acc =>
    (acc.total_impressions || 0) > 0 ||
    (acc.total_clicks || 0) > 0 ||
    (acc.total_conversions || 0) > 0
  )

  if (userIndustryAccounts.length === 0) {
    console.warn(`⚠️  No accounts with industry classification found. Visit /ad-accounts to classify your accounts for accurate benchmarks.`)
  } else {
    console.log(`📊 Competitive Analysis: ${userIndustryAccounts.length} accounts in industry, ${accountsWithData.length} with metrics data`)
  }

  // Use only accounts with data for calculations
  userIndustryAccounts = accountsWithData

  // If no industry filter, we can't calculate competitive intelligence
  if (!filters.industry || userIndustryAccounts.length === 0) {
    return {
      market_rank: null,
      percentile: 0,
      total_competitors: userIndustryAccounts.length,
      opportunity_score: 75,
      insights: [
        {
          type: 'opportunity',
          title: 'Select Industry for Competitive Intelligence',
          description: 'Choose an industry to see how your accounts perform against industry benchmarks.',
          impact_level: 'High'
        }
      ]
    }
  }

  // AGGREGATE all user accounts in this industry (they're all the user's accounts, not competitors)
  const aggregatedMetrics = userIndustryAccounts.reduce((total, acc) => ({
    totalSpend: total.totalSpend + (acc.total_spend || 0),
    totalImpressions: total.totalImpressions + (acc.total_impressions || 0),
    totalClicks: total.totalClicks + (acc.total_clicks || 0),
    totalConversions: total.totalConversions + (acc.total_conversions || 0),
    totalRevenue: total.totalRevenue + (acc.total_revenue || 0)
  }), {
    totalSpend: 0,
    totalImpressions: 0,
    totalClicks: 0,
    totalConversions: 0,
    totalRevenue: 0
  })

  // Check if we have any actual data
  if (aggregatedMetrics.totalImpressions === 0 &&
      aggregatedMetrics.totalClicks === 0 &&
      aggregatedMetrics.totalConversions === 0) {
    console.warn('⚠️ No metrics data available for competitive intelligence')
    return {
      market_rank: null,
      percentile: 0,
      total_competitors: userIndustryAccounts.length,
      opportunity_score: 50,
      insights: [{
        type: 'warning',
        title: 'No Performance Data Available',
        description: `We found ${userIndustryAccounts.length} account${userIndustryAccounts.length > 1 ? 's' : ''} in ${filters.industry?.replace(/_/g, ' ')} industry, but no metrics data is available yet. Sync your advertising data to see competitive intelligence and performance insights.`,
        impact_level: 'High'
      }]
    }
  }

  // Debug logging for diagnostics
  console.log('🔍 Competitive Intelligence Calculation:', {
    industry: filters.industry,
    accountsInIndustry: userIndustryAccounts.length,
    accountNames: userIndustryAccounts.slice(0, 5).map(a => a.account_name),
    aggregatedMetrics: {
      spend: aggregatedMetrics.totalSpend.toFixed(2),
      impressions: aggregatedMetrics.totalImpressions,
      clicks: aggregatedMetrics.totalClicks,
      conversions: aggregatedMetrics.totalConversions
    }
  })

  // Calculate aggregate performance metrics
  const userCTR = aggregatedMetrics.totalImpressions > 0
    ? (aggregatedMetrics.totalClicks / aggregatedMetrics.totalImpressions) * 100
    : 0
  const userCPC = aggregatedMetrics.totalClicks > 0
    ? aggregatedMetrics.totalSpend / aggregatedMetrics.totalClicks
    : 0
  const userCVR = aggregatedMetrics.totalClicks > 0
    ? (aggregatedMetrics.totalConversions / aggregatedMetrics.totalClicks) * 100
    : 0
  const userROAS = aggregatedMetrics.totalSpend > 0
    ? aggregatedMetrics.totalRevenue / aggregatedMetrics.totalSpend
    : 0

  console.log('📊 Calculated Metrics:', {
    CTR: userCTR.toFixed(2) + '%',
    CPC: userCPC.toFixed(2) + ' SAR',
    CVR: userCVR.toFixed(2) + '%',
    ROAS: userROAS.toFixed(2)
  })

  // Industry benchmark standards (based on typical industry data)
  // These would ideally come from the industry_benchmarks database table
  const industryStandards = {
    ctr: { p10: 0.5, p25: 1.0, p50: 1.5, p75: 2.5, p90: 4.0 },
    cpc: { p10: 0.5, p25: 1.0, p50: 2.0, p75: 3.5, p90: 6.0 }, // Lower is better
    cvr: { p10: 1.0, p25: 2.0, p50: 4.0, p75: 7.0, p90: 12.0 },
    roas: { p10: 0.5, p25: 1.0, p50: 2.0, p75: 3.5, p90: 6.0 }
  }

  // Calculate percentile position for each metric
  function calculatePercentile(value: number, benchmarks: any, lowerIsBetter = false): number {
    if (lowerIsBetter) {
      // For CPC - lower is better
      if (value <= benchmarks.p10) return 95
      if (value <= benchmarks.p25) return 80
      if (value <= benchmarks.p50) return 60
      if (value <= benchmarks.p75) return 40
      if (value <= benchmarks.p90) return 20
      return 10
    } else {
      // For CTR, CVR, ROAS - higher is better
      if (value >= benchmarks.p90) return 95
      if (value >= benchmarks.p75) return 80
      if (value >= benchmarks.p50) return 60
      if (value >= benchmarks.p25) return 40
      if (value >= benchmarks.p10) return 20
      return 10
    }
  }

  const ctrPercentile = calculatePercentile(userCTR, industryStandards.ctr)
  const cpcPercentile = calculatePercentile(userCPC, industryStandards.cpc, true)
  const cvrPercentile = calculatePercentile(userCVR, industryStandards.cvr)
  const roasPercentile = calculatePercentile(userROAS, industryStandards.roas)

  // Overall percentile (average of all metrics)
  const overallPercentile = Math.round((ctrPercentile + cpcPercentile + cvrPercentile + roasPercentile) / 4)

  // Calculate opportunity score
  let opportunityScore = 50 // Base score

  // Factor 1: Overall performance (30 points)
  if (overallPercentile >= 80) opportunityScore += 25
  else if (overallPercentile >= 60) opportunityScore += 20
  else if (overallPercentile >= 40) opportunityScore += 15
  else if (overallPercentile >= 20) opportunityScore += 10
  else opportunityScore += 5

  // Factor 2: Individual metric performance (25 points)
  const metricsAbove60 = [ctrPercentile, cpcPercentile, cvrPercentile, roasPercentile].filter(p => p >= 60).length
  opportunityScore += Math.round((metricsAbove60 / 4) * 25)

  // Clamp opportunity score between 0 and 100
  opportunityScore = Math.max(0, Math.min(100, opportunityScore))

  // Generate insights based on aggregate performance
  const insights: Array<{type: string; title: string; description: string; impact_level: string}> = []
  const industryName = filters.industry.replace(/_/g, ' ')

  // Insight 1: Overall Performance
  if (overallPercentile >= 75) {
    insights.push({
      type: 'success',
      title: 'Strong Industry Performance',
      description: `Your ${userIndustryAccounts.length} account${userIndustryAccounts.length > 1 ? 's are' : ' is'} performing in the top ${100 - overallPercentile}% of ${industryName} advertisers.`,
      impact_level: 'Medium'
    })
  } else if (overallPercentile >= 50) {
    insights.push({
      type: 'warning',
      title: 'Above Average Performance',
      description: `Your accounts are performing above industry median. Optimize key metrics to reach top performers.`,
      impact_level: 'Medium'
    })
  } else {
    insights.push({
      type: 'opportunity',
      title: 'Significant Growth Potential',
      description: `Strong opportunity to improve performance. Focus on optimizing CTR, CPC, and conversion rates.`,
      impact_level: 'High'
    })
  }

  // Insight 2: CTR Performance
  if (ctrPercentile >= 70) {
    insights.push({
      type: 'success',
      title: 'Excellent Click-Through Rate',
      description: `Your CTR of ${userCTR.toFixed(2)}% is performing at the ${ctrPercentile}th percentile. Your ad creative and targeting are working well.`,
      impact_level: 'Low'
    })
  } else if (ctrPercentile < 40) {
    insights.push({
      type: 'threat',
      title: 'Low Click-Through Rate',
      description: `Your CTR of ${userCTR.toFixed(2)}% is below industry average (${industryStandards.ctr.p50}%). Improve ad creative and targeting.`,
      impact_level: 'High'
    })
  }

  // Insight 3: CPC Performance
  if (cpcPercentile >= 70) {
    insights.push({
      type: 'success',
      title: 'Excellent Cost Efficiency',
      description: `Your CPC of ${userCPC.toFixed(2)} SAR is in the top ${100 - cpcPercentile}% for cost efficiency. Great bidding optimization.`,
      impact_level: 'Low'
    })
  } else if (cpcPercentile < 40) {
    insights.push({
      type: 'threat',
      title: 'High Cost Per Click',
      description: `Your CPC of ${userCPC.toFixed(2)} SAR is above industry average. Optimize bids and quality scores to reduce costs.`,
      impact_level: 'High'
    })
  }

  // Insight 4: Conversion Performance
  if (cvrPercentile >= 70) {
    insights.push({
      type: 'success',
      title: 'Strong Conversion Rate',
      description: `Your CVR of ${userCVR.toFixed(2)}% is at the ${cvrPercentile}th percentile. Excellent landing page and targeting alignment.`,
      impact_level: 'Low'
    })
  } else if (cvrPercentile < 40) {
    insights.push({
      type: 'opportunity',
      title: 'Conversion Rate Opportunity',
      description: `Your CVR of ${userCVR.toFixed(2)}% is below average. Focus on landing page optimization and audience targeting.`,
      impact_level: 'High'
    })
  }

  // Limit to top 4 insights
  const limitedInsights = insights.slice(0, 4)

  return {
    market_rank: null, // Not applicable when comparing against industry benchmarks
    percentile: overallPercentile,
    total_competitors: userIndustryAccounts.length, // Number of user's accounts, not competitors
    opportunity_score: opportunityScore,
    insights: limitedInsights
  }
}
