/**
 * Client Management Types
 */

import type { AdAccount } from './adAccount'

export interface Client {
  id: number
  name: string
  slug: string
  status: 'active' | 'inactive' | 'suspended'

  // Basic Information
  logo: string | null
  logo_url: string | null
  description: string | null

  // Contact Information
  contact_email: string | null
  contact_phone: string | null
  contact_person: string | null
  address: string | null
  website: string | null

  // Business Information
  industry: string | null
  vertical: string | null

  // Billing & Contract
  billing_email: string | null
  contract_start_date: string | null
  contract_end_date: string | null
  subscription_tier: 'basic' | 'pro' | 'enterprise' | null
  monthly_budget: number | null

  // Notes
  notes: string | null

  // Timestamps
  created_at: string
  updated_at: string

  // Aggregated Data
  ad_accounts_count?: number
  total_spend?: number
  contract_active?: boolean
  days_until_contract_expires?: number | null

  // Relations
  ad_accounts?: AdAccount[]
  settings?: Record<string, any>
}

export interface ClientDashboardMetrics {
  total_spend: number
  total_impressions: number
  total_clicks: number
  total_conversions: number
  total_revenue: number
  ctr: number
  cvr: number
  cpc: number
  roas: number
}

export interface TrendData {
  date: string
  value: number
}

export interface ClientDashboardTrends {
  spend: TrendData[]
  impressions: TrendData[]
  clicks: TrendData[]
  conversions: TrendData[]
}

export interface PlatformBreakdown {
  platform: string
  accounts_count: number
  spend: number
  impressions: number
  clicks: number
  conversions: number
}

export interface AdAccountWithStatus {
  id: number
  name: string
  platform: string
  status: string
  total_spend: number
  recent_spend: number
  health: 'healthy' | 'warning' | 'critical' | 'inactive'
}

export interface TopCampaign {
  id: number
  name: string
  account_name: string
  spend: number
  impressions: number
  clicks: number
  conversions: number
  roas: number
}

export interface ClientDashboardData {
  client: {
    id: number
    name: string
    logo_url: string | null
    industry: string | null
    website?: string | null
  }
  metrics: ClientDashboardMetrics
  trends: ClientDashboardTrends
  platform_breakdown: PlatformBreakdown[]
  ad_accounts: AdAccountWithStatus[]
  top_campaigns: TopCampaign[]
}

export interface ClientFormData {
  name: string
  description?: string | null
  contact_email?: string | null
  contact_phone?: string | null
  contact_person?: string | null
  address?: string | null
  website?: string | null
  industry?: string | null
  vertical?: string | null
  billing_email?: string | null
  contract_start_date?: string | null
  contract_end_date?: string | null
  subscription_tier?: 'basic' | 'pro' | 'enterprise' | null
  monthly_budget?: number | null
  notes?: string | null
  status?: 'active' | 'inactive' | 'suspended'
}

export interface ClientFilters {
  search?: string
  status?: string
  industry?: string
  subscription_tier?: string
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  page?: number
  per_page?: number
}

export interface PaginatedClients {
  data: Client[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}
