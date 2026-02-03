/**
 * Currency Converter Utility
 * Handles conversion of different currencies to SAR (Saudi Riyal)
 */

// Exchange rates to SAR (Saudi Riyal)
// These should ideally come from an API, but hardcoded for now
const EXCHANGE_RATES: Record<string, number> = {
  'SAR': 1.0,
  'USD': 3.75,  // 1 USD = 3.75 SAR
  'EUR': 4.10,  // 1 EUR = 4.10 SAR
  'GBP': 4.75,  // 1 GBP = 4.75 SAR
  'AED': 1.02,  // 1 AED = 1.02 SAR
  'EGP': 0.12,  // 1 EGP = 0.12 SAR
}

/**
 * Convert an amount from any currency to SAR
 */
export function convertToSAR(amount: number, fromCurrency: string): number {
  const rate = EXCHANGE_RATES[fromCurrency.toUpperCase()] || 1.0
  return amount * rate
}

/**
 * Convert an amount from SAR to another currency
 */
export function convertFromSAR(amount: number, toCurrency: string): number {
  const rate = EXCHANGE_RATES[toCurrency.toUpperCase()] || 1.0
  return amount / rate
}

/**
 * Get the exchange rate for a currency to SAR
 */
export function getExchangeRate(currency: string): number {
  return EXCHANGE_RATES[currency.toUpperCase()] || 1.0
}

/**
 * Check if a currency is supported
 */
export function isSupportedCurrency(currency: string): boolean {
  return currency.toUpperCase() in EXCHANGE_RATES
}

/**
 * Get all supported currencies
 */
export function getSupportedCurrencies(): string[] {
  return Object.keys(EXCHANGE_RATES)
}
