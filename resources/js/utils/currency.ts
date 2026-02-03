/**
 * Currency utility functions for SAR display
 */

export interface CurrencyInfo {
  display_currency: string
  display_symbol: string
  original_currencies: string[]
  note: string
}

/**
 * Format amount in SAR with proper symbol (text version)
 */
export function formatSAR(amount: number | string): string {
  const num = typeof amount === 'string' ? parseFloat(amount) : amount
  if (isNaN(num)) return '<img src="https://upload.wikimedia.org/wikipedia/commons/9/98/Saudi_Riyal_Symbol.svg" alt="SR" class="inline-block w-4 h-4 mr-1" />0.00'
  return `<img src="https://upload.wikimedia.org/wikipedia/commons/9/98/Saudi_Riyal_Symbol.svg" alt="SR" class="inline-block w-4 h-4 mr-1" />${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

/**
 * Format amount in SAR with SVG symbol (for Vue components)
 * Returns an object that can be used with the CurrencyDisplay component
 */
export function formatSARWithIcon(amount: number | string, compact: boolean = false) {
  const num = typeof amount === 'string' ? parseFloat(amount) : amount
  return {
    amount: isNaN(num) ? 0 : num,
    currency: 'SAR',
    compact
  }
}

/**
 * Format amount in compact SAR format (K/M)
 */
export function formatSARCompact(amount: number | string): string {
  const num = typeof amount === 'string' ? parseFloat(amount) : amount
  if (isNaN(num)) return '<img src="https://upload.wikimedia.org/wikipedia/commons/9/98/Saudi_Riyal_Symbol.svg" alt="SR" class="inline-block w-4 h-4 mr-1" />0'

  if (num >= 1000000) {
    return `<img src="https://upload.wikimedia.org/wikipedia/commons/9/98/Saudi_Riyal_Symbol.svg" alt="SR" class="inline-block w-4 h-4 mr-1" />${(num / 1000000).toFixed(1)}M`
  } else if (num >= 1000) {
    return `<img src="https://upload.wikimedia.org/wikipedia/commons/9/98/Saudi_Riyal_Symbol.svg" alt="SR" class="inline-block w-4 h-4 mr-1" />${(num / 1000).toFixed(1)}K`
  }
  return formatSAR(num)
}

/**
 * Parse SAR formatted string back to number
 */
export function parseSAR(sarString: string): number {
  const cleanString = sarString.replace(/[^\d.-]/g, '')
  return parseFloat(cleanString) || 0
}

/**
 * Get currency symbol for a given currency code
 */
export function getCurrencySymbol(currency: string): string {
  const symbols: Record<string, string> = {
    'USD': '$',
    'EUR': '€',
    'GBP': '£',
    'AED': 'د.إ',
    'SAR': 'SR',
    'EGP': 'ج.م',
    'JOD': 'د.أ',
    'KWD': 'د.ك',
    'QAR': 'ر.ق',
  }
  return symbols[currency] || currency
}

/**
 * Get currency name for a given currency code
 */
export function getCurrencyName(currency: string): string {
  const names: Record<string, string> = {
    'USD': 'US Dollar',
    'EUR': 'Euro',
    'GBP': 'British Pound',
    'AED': 'UAE Dirham',
    'SAR': 'Saudi Riyal',
    'EGP': 'Egyptian Pound',
    'JOD': 'Jordanian Dinar',
    'KWD': 'Kuwaiti Dinar',
    'QAR': 'Qatari Riyal',
  }
  return names[currency] || currency
}

/**
 * Default currency configuration
 */
export const DEFAULT_CURRENCY = 'SAR'
export const DEFAULT_CURRENCY_SYMBOL = 'SR'
export const DEFAULT_CURRENCY_NAME = 'Saudi Riyal'