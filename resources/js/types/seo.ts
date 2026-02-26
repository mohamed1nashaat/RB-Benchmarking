/**
 * SEO Report Types
 */

export interface SearchConsoleSite {
  site_url: string
  permission_level: string
}

export interface SearchConsoleRow {
  key: string
  clicks: number
  impressions: number
  ctr: number
  position: number
}

export interface SearchConsoleTimeSeries {
  date: string
  clicks: number
  impressions: number
  ctr: number
  position: number
}

export interface SearchConsoleSummary {
  total_clicks: number
  total_impressions: number
  avg_ctr: number
  avg_position: number
}

export interface SearchConsoleData {
  summary: SearchConsoleSummary
  timeseries: SearchConsoleTimeSeries[]
  queries: SearchConsoleRow[]
  pages: SearchConsoleRow[]
  error?: string
}

export interface GA4Property {
  property_id: string
  display_name: string
  account_name: string
}

export interface GA4Summary {
  sessions: number
  total_users: number
  bounce_rate: number
  conversions: number
  page_views: number
  avg_session_duration: number
  new_users: number
  active_users: number
  ecommerce_purchases: number
  total_revenue: number
}

export interface GA4TimeseriesPoint {
  date: string
  sessions: number
  users: number
  bounce_rate: number
  active_users: number
}

export interface GA4TopPage {
  page_path: string
  page_views: number
  users: number
  sessions: number
}

export interface GA4TrafficSource {
  channel: string
  sessions: number
  share: number
  revenue: number
}

export interface GA4Device {
  device: string
  users: number
  sessions: number
  share: number
}

export interface GA4GeoEntry {
  country: string
  sessions: number
  users: number
  share: number
  engagement_rate: number
}

export interface GA4Data {
  summary: GA4Summary
  timeseries: GA4TimeseriesPoint[]
  top_pages: GA4TopPage[]
  traffic_sources: GA4TrafficSource[]
  devices: GA4Device[]
  geo: GA4GeoEntry[]
  error?: string
}

export interface CoreWebVital {
  value: number | null
  display: string
  score: 'good' | 'needs_improvement' | 'poor' | 'unknown'
}

export interface PageSpeedScores {
  performance: number
  seo: number
  accessibility: number
  best_practices: number
}

export interface PageSpeedResult {
  scores: PageSpeedScores
  core_web_vitals: Record<string, CoreWebVital>
}

export interface SeoProperties {
  search_console_site: string | null
  ga4_property_id: string | null
  ga4_property_name: string | null
  pagespeed_url: string | null
}

export interface SeoReportData {
  search_console: SearchConsoleData | null
  ga4: GA4Data | null
  pagespeed_mobile: PageSpeedResult | null
  pagespeed_desktop: PageSpeedResult | null
  pagespeed_error?: boolean
}

export interface SeoStatus {
  has_integration: boolean
  has_search_console_scope: boolean
  message?: string
}
