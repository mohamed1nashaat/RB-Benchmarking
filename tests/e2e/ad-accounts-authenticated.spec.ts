import { test, expect } from '@playwright/test';

test('ad-accounts page with authentication', async ({ page }) => {
  console.log('=== AD-ACCOUNTS AUTHENTICATED TEST ===\n');

  console.log('Step 1: Logging in...');

  // Navigate to login page
  await page.goto('https://rb-benchmarks.redbananas.com/login');

  // Fill in login credentials
  await page.fill('input[type="email"], input[placeholder*="Email"], input[name="email"]', 'admin@demo.com');
  await page.fill('input[type="password"], input[placeholder*="Password"], input[name="password"]', 'password');

  // Click login button
  await page.click('button:has-text("Login"), button[type="submit"]');

  // Wait for navigation to complete
  await page.waitForTimeout(2000);

  const currentUrl = page.url();
  console.log(`After login, URL: ${currentUrl}`);

  // Check if we're logged in by looking for logout option or dashboard
  const isLoggedIn = !currentUrl.includes('/login');
  console.log(`Login successful: ${isLoggedIn}\n`);

  if (!isLoggedIn) {
    console.error('❌ Login failed - still on login page');
    await page.screenshot({ path: '/tmp/login-failed.png' });
    return;
  }

  console.log('Step 2: Navigating to ad-accounts page...');

  // Track API calls
  const apiCalls: any[] = [];
  page.on('response', response => {
    if (response.url().includes('/api/')) {
      apiCalls.push({
        url: response.url(),
        status: response.status()
      });
    }
  });

  // Navigate to ad-accounts
  await page.goto('https://rb-benchmarks.redbananas.com/ad-accounts', {
    waitUntil: 'networkidle'
  });

  await page.waitForTimeout(3000);

  console.log('\nStep 3: Checking page content...\n');

  // Check if we're on the correct page
  const pageTitle = await page.title();
  console.log(`Page title: ${pageTitle}`);

  // Check for main heading
  const heading = page.locator('h2:has-text("Ad Accounts"), h2:has-text("ad_accounts")');
  const headingVisible = await heading.isVisible().catch(() => false);
  console.log(`Main heading visible: ${headingVisible}`);

  // Check for stats cards
  const statsCards = page.locator('div.grid div.bg-white.overflow-hidden.shadow-md');
  const statsCount = await statsCards.count();
  console.log(`Stats cards found: ${statsCount}`);

  // Check for table
  const table = page.locator('table');
  const tableVisible = await table.isVisible().catch(() => false);
  console.log(`Table visible: ${tableVisible}`);

  // Check for any overlays
  const bulkModalOverlay = page.locator('div.fixed.inset-0.bg-gray-600.bg-opacity-50');
  const overlayVisible = await bulkModalOverlay.isVisible().catch(() => false);
  console.log(`Overlay visible: ${overlayVisible}`);

  if (overlayVisible) {
    console.log('  ⚠️  WARNING: Overlay is blocking the page!');
  }

  // Check loading state
  const loadingSpinner = page.locator('div.animate-spin');
  const loadingVisible = await loadingSpinner.isVisible().catch(() => false);
  console.log(`Loading spinner: ${loadingVisible}`);

  // Check for debug info
  const debugInfo = page.locator('div.bg-blue-50:has-text("Debug")');
  const debugVisible = await debugInfo.isVisible().catch(() => false);

  if (debugVisible) {
    const debugText = await debugInfo.textContent();
    console.log(`\nDebug info: ${debugText}`);
  }

  console.log('\nStep 4: API Calls...\n');
  apiCalls.forEach(call => {
    const url = new URL(call.url);
    console.log(`  ${call.status} - ${url.pathname}`);
  });

  console.log('\nStep 5: Taking screenshot...\n');
  await page.screenshot({
    path: '/tmp/ad-accounts-authenticated.png',
    fullPage: true
  });
  console.log('Screenshot saved: /tmp/ad-accounts-authenticated.png');

  console.log('\n=== TEST COMPLETE ===\n');

  // Summary
  if (overlayVisible) {
    console.log('❌ ISSUE CONFIRMED: Overlay is covering the page');
    console.log('FIX NEEDED: Check modal/sidebar state initialization');
  } else if (!headingVisible || !tableVisible) {
    console.log('⚠️  ISSUE: Page content not rendering properly');
    console.log('Possible causes: API errors, Vue rendering issue, or tenant filtering');
  } else {
    console.log('✅ Page appears to be working correctly');
  }
});
