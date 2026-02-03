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

test.describe('Calculator Data Source Verification', () => {
  test('should verify Calculator uses real ad account data, not demo data', async ({ page }) => {
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
      } else if (text.includes('✅') || text.includes('❌') || text.includes('🔍') || text.includes('📊')) {
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
          console.log(`🌐 API Response: ${url} - Status: ${response.status()} (not JSON)`);
        }
      }
    });

    console.log('\n🚀 Starting Calculator Data Source Test...\n');

    // Navigate to benchmarks page
    console.log('📍 Navigating to benchmarks page...');
    await page.goto('https://rb-benchmarks.redbananas.com/benchmarks');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000); // Give Vue time to render

    console.log('✅ Page loaded\n');

    // Check if Calculator tab exists
    const calcTab = page.locator('text=Calculator').first();
    await expect(calcTab).toBeVisible({ timeout: 10000 });
    console.log('👁️  Calculator tab found\n');

    // Click Calculator tab
    console.log('🖱️  Clicking Calculator tab...');
    await calcTab.click();
    await page.waitForTimeout(3000); // Wait for data to load

    console.log('✅ Tab clicked, waiting for data...\n');

    // Take screenshot for visual inspection
    await page.screenshot({
      path: '/tmp/calculator-data-source.png',
      fullPage: true
    });
    console.log('📸 Screenshot saved to /tmp/calculator-data-source.png\n');

    console.log('📋 Analyzing Console Logs...\n');

    // Find the critical log that shows data source
    const realDataLog = consoleLogs.find(log => log.includes('✅ REAL AD ACCOUNTS LOADED'));
    const demoDataLog = consoleLogs.find(log => log.includes('❌ AD ACCOUNTS API FAILED - USING DEMO DATA'));

    if (realDataLog) {
      console.log('✅ CONFIRMED: Using REAL AD ACCOUNT DATA');
      console.log('   ' + realDataLog);

      // Extract account count
      const match = realDataLog.match(/(\d+) accounts/);
      if (match) {
        const accountCount = parseInt(match[1]);
        console.log(`   Total accounts loaded: ${accountCount}`);

        if (accountCount === 0) {
          console.log('⚠️  WARNING: 0 accounts loaded - database may be empty!');
        }
      }
    } else if (demoDataLog) {
      console.log('❌ CONFIRMED: Using DEMO/FAKE DATA (API failed)');
      console.log('   ' + demoDataLog);

      // Find the error details
      const errorLog = consoleLogs.find(log => log.includes('Error details:'));
      if (errorLog) {
        console.log('   ' + errorLog);
      }
    } else {
      console.log('⚠️  WARNING: Could not determine data source (no debug logs found)');
      console.log('   This might indicate the debug logging is not working');
    }

    console.log('\n🌐 API Responses Analysis...\n');

    // Analyze API responses
    const adAccountsResponse = apiResponses.find(r => r.url.includes('/api/ad-accounts') && !r.url.includes('industries'));
    if (adAccountsResponse) {
      console.log(`✅ /api/ad-accounts API called - Status: ${adAccountsResponse.status}`);

      if (adAccountsResponse.status === 200) {
        const accounts = adAccountsResponse.data?.data || adAccountsResponse.data || [];
        console.log(`   Returned ${accounts.length} accounts`);

        if (accounts.length > 0) {
          console.log('   Sample accounts:');
          accounts.slice(0, 3).forEach((acc: any) => {
            console.log(`     - ${acc.account_name} (${acc.platform}, ${acc.industry})`);
            console.log(`       Impressions: ${acc.total_impressions?.toLocaleString() || 0}, Clicks: ${acc.total_clicks?.toLocaleString() || 0}`);
          });
        } else {
          console.log('   ⚠️  Database returned 0 accounts - no data to calculate from!');
        }
      } else if (adAccountsResponse.status === 401) {
        console.log('   ❌ Authentication failed - this is why demo data is being used');
      } else {
        console.log(`   ❌ Unexpected status: ${adAccountsResponse.status}`);
      }
    } else {
      console.log('❌ /api/ad-accounts API was never called');
      console.log('   This indicates a problem with the Calculator tab data loading');
    }

    const industriesResponse = apiResponses.find(r => r.url.includes('/api/ad-accounts/industries'));
    if (industriesResponse) {
      console.log(`\n✅ /api/ad-accounts/industries API called - Status: ${industriesResponse.status}`);
      if (industriesResponse.status === 200 && industriesResponse.data?.industries) {
        const industries = Object.keys(industriesResponse.data.industries);
        console.log(`   Found ${industries.length} industries: ${industries.join(', ')}`);
      }
    }

    console.log('\n🔴 Errors:', consoleErrors.length > 0 ? consoleErrors : 'None');

    console.log('\n=== DIAGNOSTIC SUMMARY ===\n');

    console.log('Data Source:');
    if (realDataLog) {
      console.log('  ✅ Using REAL ad account data from database');

      // Check if accounts are actually being used in calculations
      const calculationLogs = consoleLogs.filter(log =>
        log.includes('🔍 CALCULATOR:') ||
        log.includes('📊 Total ad accounts') ||
        log.includes('🏭 Accounts in')
      );

      if (calculationLogs.length > 0) {
        console.log('  ✅ Accounts are being used in calculations:');
        calculationLogs.forEach(log => console.log(`     ${log}`));
      } else {
        console.log('  ⚠️  No calculation logs found - may not be using the accounts');
      }
    } else if (demoDataLog) {
      console.log('  ❌ Using DEMO/FAKE data (API authentication failed)');
    } else {
      console.log('  ❓ Unknown - debug logs not found');
    }

    console.log('\nAPI Status:');
    console.log(`  - /api/ad-accounts: ${adAccountsResponse ? `${adAccountsResponse.status}` : 'Not called'}`);
    console.log(`  - /api/ad-accounts/industries: ${industriesResponse ? `${industriesResponse.status}` : 'Not called'}`);

    console.log('\n========================\n');

    // Assertions
    expect(realDataLog).toBeTruthy(); // Should be using real data, not demo
    expect(demoDataLog).toBeFalsy(); // Should NOT be falling back to demo data

    if (adAccountsResponse) {
      expect(adAccountsResponse.status).toBe(200); // API should succeed
    }
  });
});
