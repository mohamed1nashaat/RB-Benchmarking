/**
 * Campaign Objective Auto-Detection Utility
 * Automatically detects campaign objectives from campaign names using pattern matching
 */

export type CampaignObjective = 
  | 'awareness'
  | 'engagement' 
  | 'traffic'
  | 'messages'
  | 'app_installs'
  | 'in_app_actions'
  | 'leads'
  | 'website_sales'
  | 'retention'

export type TargetSegment = 
  | 'luxury'
  | 'premium'
  | 'mid_class'
  | 'value'
  | 'mass_market'
  | 'niche'

export type AgeGroup = 
  | 'gen_z'
  | 'millennials'
  | 'gen_x'
  | 'boomers'
  | 'mixed_age'

export type GeoTargeting = 
  | 'local'
  | 'regional'
  | 'national'
  | 'international'

export type MessagingTone = 
  | 'professional'
  | 'casual'
  | 'luxury'
  | 'urgent'
  | 'educational'
  | 'emotional'

interface ObjectivePattern {
  objective: CampaignObjective
  patterns: string[]
  keywords: string[]
  priority: number // Higher number = higher priority when multiple matches
}

// Comprehensive patterns for each campaign objective
const OBJECTIVE_PATTERNS: ObjectivePattern[] = [
  // Awareness campaigns
  {
    objective: 'awareness',
    patterns: [
      /brand[\s_-]?awareness/i,
      /reach[\s_-]?campaign/i,
      /impression[\s_-]?campaign/i,
      /brand[\s_-]?building/i,
      /brand[\s_-]?recognition/i
    ],
    keywords: [
      'awareness', 'brand', 'reach', 'impressions', 'branding', 'recognition',
      'visibility', 'exposure', 'mindshare', 'recall', 'consideration'
    ],
    priority: 8
  },
  
  // Engagement campaigns  
  {
    objective: 'engagement',
    patterns: [
      /engagement[\s_-]?campaign/i,
      /social[\s_-]?engagement/i,
      /video[\s_-]?views?/i,
      /post[\s_-]?engagement/i,
      /interaction/i
    ],
    keywords: [
      'engagement', 'interaction', 'social', 'video', 'views', 'likes',
      'shares', 'comments', 'reactions', 'vtr', 'watch'
    ],
    priority: 7
  },

  // Traffic campaigns
  {
    objective: 'traffic',
    patterns: [
      /traffic[\s_-]?campaign/i,
      /website[\s_-]?traffic/i,
      /drive[\s_-]?traffic/i,
      /landing[\s_-]?page/i,
      /click[\s_-]?campaign/i
    ],
    keywords: [
      'traffic', 'website', 'landing', 'click', 'visit', 'ctr', 'clicks',
      'page', 'site', 'web', 'url', 'link'
    ],
    priority: 6
  },

  // Messages/Lead Gen via messaging
  {
    objective: 'messages',
    patterns: [
      /message[\s_-]?campaign/i,
      /messenger[\s_-]?ads?/i,
      /whatsapp[\s_-]?campaign/i,
      /chat[\s_-]?campaign/i,
      /dm[\s_-]?campaign/i
    ],
    keywords: [
      'message', 'messages', 'messenger', 'whatsapp', 'chat', 'dm',
      'conversation', 'inquiry', 'contact'
    ],
    priority: 9
  },

  // App Install campaigns
  {
    objective: 'app_installs',
    patterns: [
      /app[\s_-]?install/i,
      /mobile[\s_-]?app/i,
      /download[\s_-]?campaign/i,
      /install[\s_-]?campaign/i,
      /app[\s_-]?promotion/i
    ],
    keywords: [
      'app', 'install', 'download', 'mobile', 'ios', 'android',
      'appstore', 'playstore', 'application'
    ],
    priority: 10
  },

  // In-app actions
  {
    objective: 'in_app_actions',
    patterns: [
      /in[\s_-]?app[\s_-]?action/i,
      /app[\s_-]?engagement/i,
      /mobile[\s_-]?engagement/i,
      /app[\s_-]?usage/i,
      /add[\s_-]?to[\s_-]?cart/i
    ],
    keywords: [
      'in-app', 'app-engagement', 'mobile-engagement', 'cart', 'atc',
      'purchase', 'subscription', 'premium', 'upgrade', 'level'
    ],
    priority: 9
  },

  // Lead Generation
  {
    objective: 'leads',
    patterns: [
      /lead[\s_-]?gen/i,
      /lead[\s_-]?generation/i,
      /lead[\s_-]?campaign/i,
      /sign[\s_-]?up/i,
      /registration/i,
      /newsletter/i,
      /form[\s_-]?fill/i
    ],
    keywords: [
      'lead', 'leads', 'signup', 'registration', 'form', 'newsletter',
      'contact', 'inquiry', 'demo', 'trial', 'quote', 'cpl'
    ],
    priority: 8
  },

  // Website Sales/E-commerce
  {
    objective: 'website_sales',
    patterns: [
      /e[\s_-]?commerce/i,
      /online[\s_-]?sales?/i,
      /website[\s_-]?sales?/i,
      /purchase[\s_-]?campaign/i,
      /conversion[\s_-]?campaign/i,
      /sales?[\s_-]?campaign/i,
      /checkout/i,
      /buy[\s_-]?now/i
    ],
    keywords: [
      'sales', 'ecommerce', 'purchase', 'buy', 'order', 'checkout',
      'conversion', 'revenue', 'roas', 'aov', 'shop', 'store'
    ],
    priority: 10
  },

  // Retention/Remarketing
  {
    objective: 'retention',
    patterns: [
      /retention[\s_-]?campaign/i,
      /remarketing/i,
      /retargeting/i,
      /re[\s_-]?engagement/i,
      /win[\s_-]?back/i,
      /loyal/i
    ],
    keywords: [
      'retention', 'remarket', 'retarget', 'winback', 'loyal',
      'repeat', 'existing', 'customer', 'ltv', 'lifetime'
    ],
    priority: 7
  }
]

/**
 * Detects campaign objective from campaign name
 */
export function detectCampaignObjective(campaignName: string): CampaignObjective | null {
  if (!campaignName || typeof campaignName !== 'string') {
    return null
  }

  const normalizedName = campaignName.toLowerCase().trim()
  const matches: { objective: CampaignObjective; score: number }[] = []

  for (const pattern of OBJECTIVE_PATTERNS) {
    let score = 0

    // Check regex patterns (highest weight)
    for (const regex of pattern.patterns) {
      if (regex.test(normalizedName)) {
        score += 10 * pattern.priority
        break // Only count one pattern match per objective
      }
    }

    // Check keyword matches (medium weight)
    const keywordMatches = pattern.keywords.filter(keyword => 
      normalizedName.includes(keyword.toLowerCase())
    )
    score += keywordMatches.length * 3 * pattern.priority

    if (score > 0) {
      matches.push({ objective: pattern.objective, score })
    }
  }

  // Return the objective with the highest score
  if (matches.length === 0) {
    return null
  }

  matches.sort((a, b) => b.score - a.score)
  return matches[0].objective
}

/**
 * Detects objective with confidence score
 */
export function detectCampaignObjectiveWithConfidence(campaignName: string): {
  objective: CampaignObjective | null
  confidence: 'high' | 'medium' | 'low' | 'none'
  score: number
} {
  if (!campaignName || typeof campaignName !== 'string') {
    return { objective: null, confidence: 'none', score: 0 }
  }

  const normalizedName = campaignName.toLowerCase().trim()
  let maxScore = 0
  let bestObjective: CampaignObjective | null = null

  for (const pattern of OBJECTIVE_PATTERNS) {
    let score = 0

    // Check regex patterns
    for (const regex of pattern.patterns) {
      if (regex.test(normalizedName)) {
        score += 10 * pattern.priority
        break
      }
    }

    // Check keyword matches
    const keywordMatches = pattern.keywords.filter(keyword => 
      normalizedName.includes(keyword.toLowerCase())
    )
    score += keywordMatches.length * 3 * pattern.priority

    if (score > maxScore) {
      maxScore = score
      bestObjective = pattern.objective
    }
  }

  // Determine confidence level
  let confidence: 'high' | 'medium' | 'low' | 'none'
  if (maxScore >= 80) {
    confidence = 'high'
  } else if (maxScore >= 40) {
    confidence = 'medium'  
  } else if (maxScore >= 20) {
    confidence = 'low'
  } else {
    confidence = 'none'
  }

  return {
    objective: bestObjective,
    confidence,
    score: maxScore
  }
}

/**
 * Batch detect objectives for multiple campaigns
 */
export function detectObjectivesForCampaigns(campaigns: Array<{name: string}>): Array<{
  name: string
  detectedObjective: CampaignObjective | null
  confidence: 'high' | 'medium' | 'low' | 'none'
}> {
  return campaigns.map(campaign => {
    const detection = detectCampaignObjectiveWithConfidence(campaign.name)
    return {
      name: campaign.name,
      detectedObjective: detection.objective,
      confidence: detection.confidence
    }
  })
}

/**
 * Get suggested KPIs for detected objective
 */
export function getKPIsForObjective(objective: CampaignObjective): string[] {
  const kpiMap: Record<CampaignObjective, string[]> = {
    awareness: ['spend', 'cpm', 'reach', 'vtr', 'ctr'],
    engagement: ['spend', 'ctr', 'frequency', 'reach', 'vtr'],
    traffic: ['spend', 'cpc', 'ctr', 'impressions', 'clicks', 'cpm'],
    messages: ['spend', 'cpc', 'ctr', 'conversations', 'impressions', 'clicks'],
    app_installs: ['spend', 'cpa', 'ctr', 'cpc', 'cvr', 'cpm'],
    in_app_actions: ['spend', 'cpa', 'ctr', 'atc', 'cpc', 'cvr'],
    leads: ['spend', 'cpl', 'cvr', 'ctr', 'cpc'],
    website_sales: ['spend', 'roas', 'cpa', 'revenue', 'aov', 'cvr'],
    retention: ['spend', 'cpa', 'retention_rate', 'ltv', 'ctr', 'cpc']
  }

  return kpiMap[objective] || []
}

/**
 * Funnel Stage Detection Types
 */
export type FunnelStage = 'TOF' | 'MOF' | 'BOF'

interface FunnelStagePattern {
  stage: FunnelStage
  patterns: RegExp[]
  keywords: string[]
  priority: number
}

// Patterns for detecting funnel stages
const FUNNEL_STAGE_PATTERNS: FunnelStagePattern[] = [
  // Top of Funnel (Awareness, Discovery)
  {
    stage: 'TOF',
    patterns: [
      /top[\s_-]?of[\s_-]?funnel/i,
      /awareness[\s_-]?campaign/i,
      /brand[\s_-]?awareness/i,
      /discovery/i,
      /reach[\s_-]?campaign/i,
      /cold[\s_-]?audience/i,
      /prospecting/i
    ],
    keywords: [
      'tof', 'awareness', 'brand', 'reach', 'discovery', 'cold', 'prospect',
      'introduce', 'visibility', 'exposure', 'new', 'broad', 'impressions'
    ],
    priority: 10
  },
  
  // Middle of Funnel (Consideration, Interest)  
  {
    stage: 'MOF',
    patterns: [
      /middle[\s_-]?of[\s_-]?funnel/i,
      /consideration/i,
      /interest[\s_-]?campaign/i,
      /engagement[\s_-]?campaign/i,
      /warm[\s_-]?audience/i,
      /lead[\s_-]?gen/i,
      /nurtur/i
    ],
    keywords: [
      'mof', 'consideration', 'interest', 'engage', 'warm', 'leads', 'nurture',
      'demo', 'trial', 'learn', 'compare', 'research', 'whitepaper', 'webinar'
    ],
    priority: 10
  },
  
  // Bottom of Funnel (Conversion, Decision)
  {
    stage: 'BOF',
    patterns: [
      /bottom[\s_-]?of[\s_-]?funnel/i,
      /conversion[\s_-]?campaign/i,
      /purchase[\s_-]?campaign/i,
      /sales?[\s_-]?campaign/i,
      /hot[\s_-]?audience/i,
      /retargeting/i,
      /remarketing/i
    ],
    keywords: [
      'bof', 'conversion', 'purchase', 'buy', 'sale', 'hot', 'retarget',
      'remarket', 'checkout', 'order', 'convert', 'close', 'decision'
    ],
    priority: 10
  }
]

/**
 * User Journey Detection Types
 */
export type UserJourney = 'instant_form' | 'landing_page'

interface UserJourneyPattern {
  journey: UserJourney
  patterns: RegExp[]
  keywords: string[]
  priority: number
}

// Patterns for detecting user journey types
const USER_JOURNEY_PATTERNS: UserJourneyPattern[] = [
  // Instant Form (Lead forms, in-platform forms)
  {
    journey: 'instant_form',
    patterns: [
      /instant[\s_-]?form/i,
      /lead[\s_-]?form/i,
      /in[\s_-]?platform[\s_-]?form/i,
      /facebook[\s_-]?form/i,
      /meta[\s_-]?form/i,
      /native[\s_-]?form/i
    ],
    keywords: [
      'instant', 'form', 'leadform', 'native', 'inplatform', 'quick',
      'easy', 'simple', 'fast', 'leadgen', 'signup'
    ],
    priority: 10
  },
  
  // Landing Page (External website, dedicated pages)
  {
    journey: 'landing_page',
    patterns: [
      /landing[\s_-]?page/i,
      /website[\s_-]?visit/i,
      /external[\s_-]?link/i,
      /dedicated[\s_-]?page/i,
      /custom[\s_-]?page/i,
      /lp[\s_-]/i
    ],
    keywords: [
      'landing', 'website', 'page', 'external', 'link', 'visit',
      'dedicated', 'custom', 'detailed', 'comprehensive'
    ],
    priority: 8
  }
]

// Target segment detection patterns
interface TargetSegmentPattern {
  segment: TargetSegment
  patterns: RegExp[]
  keywords: string[]
  priority: number
}

const TARGET_SEGMENT_PATTERNS: TargetSegmentPattern[] = [
  {
    segment: 'luxury',
    patterns: [
      /luxury/i, /premium/i, /high[\s_-]?end/i, /exclusive/i, /elite/i, /vip/i,
      /prestige/i, /upscale/i, /affluent/i, /wealthy/i, /rich/i
    ],
    keywords: ['luxury', 'premium', 'exclusive', 'elite', 'vip', 'prestige', 'upscale', 'affluent', 'rich', 'expensive', 'sophisticated'],
    priority: 10
  },
  {
    segment: 'premium',
    patterns: [
      /premium/i, /quality/i, /professional/i, /executive/i, /upmarket/i
    ],
    keywords: ['premium', 'quality', 'professional', 'executive', 'upmarket', 'superior', 'enhanced', 'advanced'],
    priority: 8
  },
  {
    segment: 'mid_class',
    patterns: [
      /middle[\s_-]?class/i, /mainstream/i, /standard/i, /regular/i, /average/i,
      /mid[\s_-]?market/i, /mid[\s_-]?range/i, /moderate/i
    ],
    keywords: ['middle', 'mainstream', 'standard', 'regular', 'average', 'moderate', 'typical', 'normal'],
    priority: 6
  },
  {
    segment: 'value',
    patterns: [
      /budget/i, /affordable/i, /cheap/i, /low[\s_-]?cost/i, /discount/i,
      /value/i, /economical/i, /inexpensive/i, /bargain/i
    ],
    keywords: ['budget', 'affordable', 'cheap', 'value', 'discount', 'economical', 'inexpensive', 'bargain', 'deal'],
    priority: 9
  },
  {
    segment: 'mass_market',
    patterns: [
      /mass[\s_-]?market/i, /general/i, /broad/i, /everyone/i, /all/i,
      /universal/i, /wide[\s_-]?audience/i
    ],
    keywords: ['mass', 'general', 'broad', 'everyone', 'all', 'universal', 'wide', 'comprehensive'],
    priority: 5
  },
  {
    segment: 'niche',
    patterns: [
      /niche/i, /specialized/i, /specific/i, /targeted/i, /focused/i,
      /specialist/i, /expert/i, /custom/i
    ],
    keywords: ['niche', 'specialized', 'specific', 'targeted', 'focused', 'specialist', 'expert', 'custom', 'unique'],
    priority: 7
  }
]

// Age group detection patterns
interface AgeGroupPattern {
  ageGroup: AgeGroup
  patterns: RegExp[]
  keywords: string[]
  priority: number
}

const AGE_GROUP_PATTERNS: AgeGroupPattern[] = [
  {
    ageGroup: 'gen_z',
    patterns: [
      /gen[\s_-]?z/i, /generation[\s_-]?z/i, /teen/i, /young[\s_-]?adult/i,
      /16[\s_-]?25/i, /18[\s_-]?24/i, /college/i, /student/i
    ],
    keywords: ['genz', 'teen', 'young', 'student', 'college', 'tiktok', 'snap', 'gaming', 'tech'],
    priority: 10
  },
  {
    ageGroup: 'millennials',
    patterns: [
      /millennial/i, /gen[\s_-]?y/i, /generation[\s_-]?y/i, /young[\s_-]?professional/i,
      /26[\s_-]?40/i, /25[\s_-]?35/i, /30[\s_-]?something/i
    ],
    keywords: ['millennial', 'professional', 'career', 'family', 'startup', 'urban', 'instagram'],
    priority: 10
  },
  {
    ageGroup: 'gen_x',
    patterns: [
      /gen[\s_-]?x/i, /generation[\s_-]?x/i, /middle[\s_-]?aged/i,
      /41[\s_-]?55/i, /40[\s_-]?something/i, /50[\s_-]?something/i
    ],
    keywords: ['genx', 'middle-aged', 'established', 'experienced', 'senior', 'manager', 'facebook'],
    priority: 10
  },
  {
    ageGroup: 'boomers',
    patterns: [
      /boomer/i, /baby[\s_-]?boomer/i, /senior/i, /elder/i, /retirement/i,
      /56\+/i, /60\+/i, /65\+/i, /retiree/i
    ],
    keywords: ['boomer', 'senior', 'elder', 'retirement', 'retiree', 'mature', 'experienced'],
    priority: 10
  },
  {
    ageGroup: 'mixed_age',
    patterns: [
      /all[\s_-]?ages/i, /mixed[\s_-]?age/i, /broad[\s_-]?age/i,
      /18[\s_-]?65/i, /wide[\s_-]?age/i, /general[\s_-]?audience/i
    ],
    keywords: ['all-ages', 'mixed', 'broad', 'wide', 'general', 'everyone', 'universal'],
    priority: 6
  }
]

// Geographic targeting patterns
interface GeoTargetingPattern {
  geoTargeting: GeoTargeting
  patterns: RegExp[]
  keywords: string[]
  priority: number
}

const GEO_TARGETING_PATTERNS: GeoTargetingPattern[] = [
  {
    geoTargeting: 'local',
    patterns: [
      /local/i, /city/i, /neighborhood/i, /area/i, /nearby/i, /community/i,
      /downtown/i, /suburb/i, /district/i, /zip[\s_-]?code/i
    ],
    keywords: ['local', 'city', 'neighborhood', 'area', 'nearby', 'community', 'downtown', 'suburb'],
    priority: 10
  },
  {
    geoTargeting: 'regional',
    patterns: [
      /regional/i, /state/i, /province/i, /region/i, /territory/i,
      /multi[\s_-]?city/i, /statewide/i, /metro/i
    ],
    keywords: ['regional', 'state', 'province', 'region', 'territory', 'statewide', 'metro', 'multi-city'],
    priority: 8
  },
  {
    geoTargeting: 'national',
    patterns: [
      /national/i, /country/i, /nationwide/i, /countrywide/i,
      /domestic/i, /usa/i, /canada/i, /uk/i
    ],
    keywords: ['national', 'country', 'nationwide', 'countrywide', 'domestic', 'usa', 'canada'],
    priority: 6
  },
  {
    geoTargeting: 'international',
    patterns: [
      /international/i, /global/i, /worldwide/i, /multi[\s_-]?country/i,
      /cross[\s_-]?border/i, /overseas/i, /export/i
    ],
    keywords: ['international', 'global', 'worldwide', 'multi-country', 'overseas', 'export', 'cross-border'],
    priority: 7
  }
]

// Messaging tone patterns
interface MessagingTonePattern {
  messagingTone: MessagingTone
  patterns: RegExp[]
  keywords: string[]
  priority: number
}

const MESSAGING_TONE_PATTERNS: MessagingTonePattern[] = [
  {
    messagingTone: 'professional',
    patterns: [
      /professional/i, /business/i, /corporate/i, /b2b/i, /enterprise/i,
      /formal/i, /executive/i, /industry/i
    ],
    keywords: ['professional', 'business', 'corporate', 'b2b', 'enterprise', 'formal', 'executive'],
    priority: 9
  },
  {
    messagingTone: 'casual',
    patterns: [
      /casual/i, /friendly/i, /informal/i, /relaxed/i, /conversational/i,
      /fun/i, /easy/i, /simple/i
    ],
    keywords: ['casual', 'friendly', 'informal', 'relaxed', 'conversational', 'fun', 'easy', 'simple'],
    priority: 7
  },
  {
    messagingTone: 'luxury',
    patterns: [
      /luxury/i, /sophisticated/i, /elegant/i, /premium/i, /exclusive/i,
      /refined/i, /upscale/i
    ],
    keywords: ['luxury', 'sophisticated', 'elegant', 'premium', 'exclusive', 'refined', 'upscale'],
    priority: 10
  },
  {
    messagingTone: 'urgent',
    patterns: [
      /urgent/i, /limited[\s_-]?time/i, /sale/i, /hurry/i, /act[\s_-]?now/i,
      /deadline/i, /expires/i, /last[\s_-]?chance/i
    ],
    keywords: ['urgent', 'limited', 'sale', 'hurry', 'now', 'deadline', 'expires', 'last-chance'],
    priority: 8
  },
  {
    messagingTone: 'educational',
    patterns: [
      /educational/i, /learn/i, /guide/i, /how[\s_-]?to/i, /tips/i,
      /tutorial/i, /informative/i, /helpful/i
    ],
    keywords: ['educational', 'learn', 'guide', 'how-to', 'tips', 'tutorial', 'informative', 'helpful'],
    priority: 6
  },
  {
    messagingTone: 'emotional',
    patterns: [
      /emotional/i, /heartfelt/i, /touching/i, /inspiring/i, /motivational/i,
      /feel/i, /love/i, /care/i, /support/i
    ],
    keywords: ['emotional', 'heartfelt', 'touching', 'inspiring', 'motivational', 'feel', 'love', 'care'],
    priority: 8
  }
]

/**
 * Detect funnel stage from campaign name
 */
export function detectFunnelStage(campaignName: string): FunnelStage | null {
  if (!campaignName || typeof campaignName !== 'string') {
    return null
  }

  const normalizedName = campaignName.toLowerCase().trim()
  const matches: { stage: FunnelStage; score: number }[] = []

  for (const pattern of FUNNEL_STAGE_PATTERNS) {
    let score = 0

    // Check regex patterns
    for (const regex of pattern.patterns) {
      if (regex.test(normalizedName)) {
        score += 10 * pattern.priority
        break
      }
    }

    // Check keyword matches
    const keywordMatches = pattern.keywords.filter(keyword => 
      normalizedName.includes(keyword.toLowerCase())
    )
    score += keywordMatches.length * 3 * pattern.priority

    if (score > 0) {
      matches.push({ stage: pattern.stage, score })
    }
  }

  if (matches.length === 0) {
    return null
  }

  matches.sort((a, b) => b.score - a.score)
  return matches[0].stage
}

/**
 * Detect user journey from campaign name
 */
export function detectUserJourney(campaignName: string): UserJourney | null {
  if (!campaignName || typeof campaignName !== 'string') {
    return null
  }

  const normalizedName = campaignName.toLowerCase().trim()
  const matches: { journey: UserJourney; score: number }[] = []

  for (const pattern of USER_JOURNEY_PATTERNS) {
    let score = 0

    // Check regex patterns
    for (const regex of pattern.patterns) {
      if (regex.test(normalizedName)) {
        score += 10 * pattern.priority
        break
      }
    }

    // Check keyword matches
    const keywordMatches = pattern.keywords.filter(keyword => 
      normalizedName.includes(keyword.toLowerCase())
    )
    score += keywordMatches.length * 3 * pattern.priority

    if (score > 0) {
      matches.push({ journey: pattern.journey, score })
    }
  }

  if (matches.length === 0) {
    return null
  }

  matches.sort((a, b) => b.score - a.score)
  return matches[0].journey
}

/**
 * Detect pixel data likelihood from campaign name and objective
 */
export function detectPixelDataLikelihood(campaignName: string, objective?: CampaignObjective): boolean {
  if (!campaignName || typeof campaignName !== 'string') {
    return false
  }

  const normalizedName = campaignName.toLowerCase().trim()
  
  // Keywords that suggest pixel data usage
  const pixelKeywords = [
    'conversion', 'purchase', 'sale', 'checkout', 'order', 'revenue',
    'retarget', 'remarket', 'custom', 'lookalike', 'pixel', 'tracking'
  ]
  
  // Check for pixel-related keywords
  const hasPixelKeywords = pixelKeywords.some(keyword => 
    normalizedName.includes(keyword)
  )
  
  // Objectives that typically use pixel data
  const pixelObjectives: CampaignObjective[] = [
    'website_sales', 'leads', 'retention', 'in_app_actions'
  ]
  
  const objectiveUsesPixel = objective && pixelObjectives.includes(objective)
  
  return hasPixelKeywords || !!objectiveUsesPixel
}

/**
 * Detect target segment from campaign name
 */
export function detectTargetSegment(campaignName: string): TargetSegment | null {
  if (!campaignName || typeof campaignName !== 'string') {
    return null
  }

  const normalizedName = campaignName.toLowerCase().trim()
  const matches: { segment: TargetSegment; score: number }[] = []

  for (const pattern of TARGET_SEGMENT_PATTERNS) {
    let score = 0

    // Check regex patterns
    for (const regex of pattern.patterns) {
      if (regex.test(normalizedName)) {
        score += 10 * pattern.priority
        break
      }
    }

    // Check keyword matches
    const keywordMatches = pattern.keywords.filter(keyword => 
      normalizedName.includes(keyword.toLowerCase())
    )
    score += keywordMatches.length * pattern.priority

    if (score > 0) {
      matches.push({ segment: pattern.segment, score })
    }
  }

  return matches.length > 0 
    ? matches.sort((a, b) => b.score - a.score)[0].segment 
    : null
}

/**
 * Detect age group from campaign name
 */
export function detectAgeGroup(campaignName: string): AgeGroup | null {
  if (!campaignName || typeof campaignName !== 'string') {
    return null
  }

  const normalizedName = campaignName.toLowerCase().trim()
  const matches: { ageGroup: AgeGroup; score: number }[] = []

  for (const pattern of AGE_GROUP_PATTERNS) {
    let score = 0

    // Check regex patterns
    for (const regex of pattern.patterns) {
      if (regex.test(normalizedName)) {
        score += 10 * pattern.priority
        break
      }
    }

    // Check keyword matches
    const keywordMatches = pattern.keywords.filter(keyword => 
      normalizedName.includes(keyword.toLowerCase())
    )
    score += keywordMatches.length * pattern.priority

    if (score > 0) {
      matches.push({ ageGroup: pattern.ageGroup, score })
    }
  }

  return matches.length > 0 
    ? matches.sort((a, b) => b.score - a.score)[0].ageGroup 
    : null
}

/**
 * Detect geographic targeting from campaign name
 */
export function detectGeoTargeting(campaignName: string): GeoTargeting | null {
  if (!campaignName || typeof campaignName !== 'string') {
    return null
  }

  const normalizedName = campaignName.toLowerCase().trim()
  const matches: { geoTargeting: GeoTargeting; score: number }[] = []

  for (const pattern of GEO_TARGETING_PATTERNS) {
    let score = 0

    // Check regex patterns
    for (const regex of pattern.patterns) {
      if (regex.test(normalizedName)) {
        score += 10 * pattern.priority
        break
      }
    }

    // Check keyword matches
    const keywordMatches = pattern.keywords.filter(keyword => 
      normalizedName.includes(keyword.toLowerCase())
    )
    score += keywordMatches.length * pattern.priority

    if (score > 0) {
      matches.push({ geoTargeting: pattern.geoTargeting, score })
    }
  }

  return matches.length > 0 
    ? matches.sort((a, b) => b.score - a.score)[0].geoTargeting 
    : null
}

/**
 * Detect messaging tone from campaign name
 */
export function detectMessagingTone(campaignName: string): MessagingTone | null {
  if (!campaignName || typeof campaignName !== 'string') {
    return null
  }

  const normalizedName = campaignName.toLowerCase().trim()
  const matches: { messagingTone: MessagingTone; score: number }[] = []

  for (const pattern of MESSAGING_TONE_PATTERNS) {
    let score = 0

    // Check regex patterns
    for (const regex of pattern.patterns) {
      if (regex.test(normalizedName)) {
        score += 10 * pattern.priority
        break
      }
    }

    // Check keyword matches
    const keywordMatches = pattern.keywords.filter(keyword => 
      normalizedName.includes(keyword.toLowerCase())
    )
    score += keywordMatches.length * pattern.priority

    if (score > 0) {
      matches.push({ messagingTone: pattern.messagingTone, score })
    }
  }

  return matches.length > 0 
    ? matches.sort((a, b) => b.score - a.score)[0].messagingTone 
    : null
}

/**
 * Comprehensive campaign detection - detects all identifiers at once
 */
export function detectAllCampaignIdentifiers(campaignName: string): {
  objective: CampaignObjective | null
  objectiveConfidence: 'high' | 'medium' | 'low' | 'none'
  funnelStage: FunnelStage | null
  userJourney: UserJourney | null
  hasPixelData: boolean
  targetSegment: TargetSegment | null
  ageGroup: AgeGroup | null
  geoTargeting: GeoTargeting | null
  messagingTone: MessagingTone | null
  suggestions: string[]
} {
  if (!campaignName || typeof campaignName !== 'string') {
    return {
      objective: null,
      objectiveConfidence: 'none',
      funnelStage: null,
      userJourney: null,
      hasPixelData: false,
      targetSegment: null,
      ageGroup: null,
      geoTargeting: null,
      messagingTone: null,
      suggestions: ['Campaign name is required for auto-detection']
    }
  }

  const objectiveDetection = detectCampaignObjectiveWithConfidence(campaignName)
  const funnelStage = detectFunnelStage(campaignName)
  const userJourney = detectUserJourney(campaignName)
  const hasPixelData = detectPixelDataLikelihood(campaignName, objectiveDetection.objective)
  
  // Detect target audience identifiers
  const targetSegment = detectTargetSegment(campaignName)
  const ageGroup = detectAgeGroup(campaignName)
  const geoTargeting = detectGeoTargeting(campaignName)
  const messagingTone = detectMessagingTone(campaignName)
  
  const suggestions: string[] = []
  
  // Add suggestions based on detection confidence
  if (objectiveDetection.confidence === 'low') {
    suggestions.push('Objective detection has low confidence - please verify')
  }
  
  if (!funnelStage) {
    suggestions.push('Could not detect funnel stage - consider adding TOF/MOF/BOF keywords')
  }
  
  if (!userJourney) {
    suggestions.push('Could not detect user journey - consider adding "form" or "landing page" keywords')
  }
  
  // Add suggestions for target audience identifiers
  if (!targetSegment) {
    suggestions.push('Could not detect target segment - consider adding audience keywords (luxury, budget, etc.)')
  }
  
  if (!ageGroup) {
    suggestions.push('Could not detect age group - consider adding demographic keywords (gen z, millennials, etc.)')
  }
  
  if (!geoTargeting) {
    suggestions.push('Could not detect geographic targeting - consider adding location keywords (local, national, etc.)')
  }
  
  if (!messagingTone) {
    suggestions.push('Could not detect messaging tone - consider adding tone keywords (professional, casual, etc.)')
  }
  
  if (suggestions.length === 0) {
    suggestions.push('All identifiers detected successfully!')
  }

  return {
    objective: objectiveDetection.objective,
    objectiveConfidence: objectiveDetection.confidence,
    funnelStage,
    userJourney,
    hasPixelData,
    targetSegment,
    ageGroup,
    geoTargeting,
    messagingTone,
    suggestions
  }
}