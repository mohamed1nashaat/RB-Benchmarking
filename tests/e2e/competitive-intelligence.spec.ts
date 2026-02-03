import { test, expect } from '@playwright/test';

// Test authentication helper
test.beforeEach(async ({ page }) => {
  // Navigate to benchmarks page
  await page.goto('https://rb-benchmarks.redbananas.com/benchmarks');

  // If redirected to login, handle authentication
  if (page.url().includes('/login')) {
    await page.fill('input[type="email"]', 'admin@demo.com');
    await page.fill('input[type="password"]', 'password');
    await page.click('button[type="submit"]');

    // Wait for redirect after login
    await page.waitForURL(/\/(benchmarks|clients|ad-accounts)/, { timeout: 15000 });

    // Navigate to benchmarks page if not already there
    if (!page.url().includes('/benchmarks')) {
      await page.goto('https://rb-benchmarks.redbananas.com/benchmarks');
      await page.waitForLoadState('networkidle');
    }
  }
});

test.describe('Competitive Intelligence Data Display', () => {
  test('should display all account data in competitive intelligence', async ({ page }) => {
    const consoleLogs: string[] = [];
    const consoleErrors: string[] = [];

    // Capture console logs
    page.on('console', msg => {
      const text = msg.text();
      consoleLogs.push(text);

      // Log to terminal as well
      if (msg.type() === 'error') {
        console.error('❌ Browser Error:', text);
        consoleErrors.push(text);
      } else if (text.includes('🎯') || text.includes('📊') || text.includes('🔍')) {
        console.log('📝', text);
      }
    });

    // Capture network requests to API
    const apiResponses: any[] = [];
    page.on('response', async response => {
      const url = response.url();
      if (url.includes('/api/ad-accounts') || url.includes('/api/benchmarks')) {
        try {
          const data = await response.json();
          apiResponses.push({ url, status: response.status(), data });
          console.log(`🌐 API Response: ${url} - Status: ${response.status()}`);
        } catch (e) {
          // Not JSON response
        }
      }
    });

    console.log('\n🚀 Starting Competitive Intelligence Test...\n');

    // Navigate to benchmarks page
    console.log('📍 Navigating to benchmarks page...');
    await page.goto('https://rb-benchmarks.redbananas.com/benchmarks');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000); // Give Vue time to render

    console.log('✅ Page loaded\n');

    // Check if Competitive Intelligence tab exists
    const ciTab = page.locator('text=Competitive Intelligence');
    await expect(ciTab).toBeVisible({ timeout: 10000 });
    console.log('👁️  Competitive Intelligence tab found\n');

    // Click Competitive Intelligence tab
    console.log('🖱️  Clicking Competitive Intelligence tab...');
    await ciTab.click();
    await page.waitForTimeout(3000); // Wait for data to load and calculate

    console.log('✅ Tab clicked, waiting for data...\n');

    // Take screenshot for visual inspection
    await page.screenshot({
      path: '/tmp/competitive-intelligence-display.png',
      fullPage: true
    });
    console.log('📸 Screenshot saved to /tmp/competitive-intelligence-display.png\n');

    // Extract displayed data
    console.log('🔍 Extracting displayed values...\n');

    // Get the account count text
    const accountText = await page.locator('text=/Based on .* of your account/').textContent().catch(() => null);
    console.log(`📊 Account Display: "${accountText}"`);

    // Get the percentile text
    const percentileText = await page.locator('text=/\\d+th %ile/').textContent().catch(() => null);
    console.log(`📊 Percentile Display: "${percentileText}"`);

    // Get the performance description
    const performanceText = await page.locator('text=/Performing .* of .* advertisers/').textContent().catch(() => null);
    console.log(`📊 Performance Text: "${performanceText}"`);

    // Get opportunity score
    const opportunityScore = await page.locator('text=/\\d+\\/100/').textContent().catch(() => null);
    console.log(`📊 Opportunity Score: "${opportunityScore}"`);

    console.log('\n📋 Analyzing Console Logs...\n');

    // Find relevant console logs
    const autoSelectLog = consoleLogs.find(log => log.includes('🎯 Auto-selected industry'));
    const accountsFilterLog = consoleLogs.find(log => log.includes('📊 Accounts filter'));
    const calculationLog = consoleLogs.find(log => log.includes('🔍 Competitive Intelligence Calculation'));
    const metricsLog = consoleLogs.find(log => log.includes('📊 Calculated Metrics'));

    console.log('🎯 Auto-selection:', autoSelectLog || '❌ NOT FOUND');
    console.log('📊 Accounts filter:', accountsFilterLog || '❌ NOT FOUND');
    console.log('🔍 Calculation data:', calculationLog || '❌ NOT FOUND');
    console.log('📊 Metrics:', metricsLog || '❌ NOT FOUND');

    console.log('\n🌐 API Responses Analysis...\n');

    // Analyze API responses
    const adAccountsResponse = apiResponses.find(r => r.url.includes('/api/ad-accounts'));
    if (adAccountsResponse) {
      const accounts = adAccountsResponse.data?.data || adAccountsResponse.data || [];
      console.log(`✅ Ad Accounts API returned ${accounts.length} accounts`);

      // Count accounts with data
      const accountsWithData = accounts.filter((acc: any) =>
        (acc.total_impressions || 0) > 0 ||
        (acc.total_clicks || 0) > 0 ||
        (acc.total_conversions || 0) > 0
      );
      console.log(`📊 Accounts with metrics data: ${accountsWithData.length} / ${accounts.length}`);

      // Group by industry
      const byIndustry = accounts.reduce((acc: any, account: any) => {
        const ind = account.industry || 'unknown';
        if (!acc[ind]) {
          acc[ind] = { total: 0, withData: 0, impressions: 0, clicks: 0 };
        }
        acc[ind].total++;
        if ((account.total_impressions || 0) > 0 ||
            (account.total_clicks || 0) > 0 ||
            (account.total_conversions || 0) > 0) {
          acc[ind].withData++;
          acc[ind].impressions += account.total_impressions || 0;
          acc[ind].clicks += account.total_clicks || 0;
        }
        return acc;
      }, {});

      console.log('\n📊 Breakdown by Industry:');
      Object.entries(byIndustry)
        .sort(([, a]: any, [, b]: any) => b.impressions - a.impressions)
        .forEach(([industry, stats]: any) => {
          console.log(`  ${industry}: ${stats.total} total, ${stats.withData} with data, ${stats.impressions.toLocaleString()} impressions, ${stats.clicks.toLocaleString()} clicks`);
        });
    } else {
      console.log('❌ No ad accounts API response found');
    }

    console.log('\n🔴 Errors:', consoleErrors.length > 0 ? consoleErrors : 'None');

    console.log('\n=== DIAGNOSTIC SUMMARY ===\n');

    console.log('Display Status:');
    console.log(`  - Account count: ${accountText ? '✅ Showing' : '❌ Missing'}`);
    console.log(`  - Percentile: ${percentileText ? '✅ Showing' : '❌ Missing'}`);
    console.log(`  - Performance text: ${performanceText ? '✅ Showing' : '❌ Missing'}`);
    console.log(`  - Opportunity score: ${opportunityScore ? '✅ Showing' : '❌ Missing'}`);

    console.log('\nDebug Logs Status:');
    console.log(`  - Auto-selection: ${autoSelectLog ? '✅ Working' : '❌ Not working'}`);
    console.log(`  - Accounts filter: ${accountsFilterLog ? '✅ Working' : '❌ Not working'}`);
    console.log(`  - Calculation: ${calculationLog ? '✅ Working' : '❌ Not working'}`);
    console.log(`  - Metrics: ${metricsLog ? '✅ Working' : '❌ Not working'}`);

    console.log('\n========================\n');

    // Assertions
    expect(accountText).toBeTruthy();
    expect(percentileText).toBeTruthy();
  });
});
