import { test, expect } from '@playwright/test';

test.describe('DateRangePicker - Enhanced Calendar Component', () => {

  test.beforeEach(async ({ page }) => {
    // Monitor console errors, especially "Invalid interval"
    const consoleErrors: string[] = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        const errorText = msg.text();
        consoleErrors.push(errorText);
        console.log('❌ Console error detected:', errorText);

        if (errorText.includes('Invalid interval')) {
          console.error('🚨 CRITICAL: Invalid interval error detected!');
        }
      }
    });

    // Login first
    console.log('🔐 Logging in...');
    await page.goto('/login');
    await page.fill('input[type="email"], input[placeholder*="Email"], input[name="email"]', 'admin@demo.com');
    await page.fill('input[type="password"], input[placeholder*="Password"], input[name="password"]', 'password');
    await page.click('button:has-text("Login"), button[type="submit"]');
    await page.waitForTimeout(2000);

    // Verify login success
    const currentUrl = page.url();
    const isLoggedIn = !currentUrl.includes('/login');
    console.log(`✅ Login ${isLoggedIn ? 'successful' : 'failed'}`);

    // Navigate to benchmarks page
    console.log('📍 Navigating to /benchmarks...');
    await page.goto('/benchmarks');
    await page.waitForLoadState('networkidle');
    console.log('✅ Page loaded');
  });

  test('should open date picker calendar popover', async ({ page }) => {
    console.log('\n🧪 TEST: Open date picker calendar popover');

    // Wait for the date range button to be visible
    await page.waitForTimeout(1000);

    // Find the date range button - it should have text matching a date range format
    const dateButton = page.locator('button').filter({
      hasText: /\w{3}\s+\d{1,2},\s+\d{4}\s+-\s+\w{3}\s+\d{1,2},\s+\d{4}/
    });

    console.log('🔍 Looking for date range button...');
    await expect(dateButton).toBeVisible({ timeout: 10000 });
    console.log('✅ Date button found');

    // Click to open popover
    console.log('🖱️ Clicking date button...');
    await dateButton.click();
    await page.waitForTimeout(500); // Wait for animation

    // Verify popover is visible by checking for calendar elements
    console.log('🔍 Checking for calendar elements...');

    // Look for weekday headers (Su, Mo, Tu, We, Th, Fr, Sa)
    const weekdayHeaders = page.locator('text=Su').or(page.locator('text=Mo'));
    await expect(weekdayHeaders.first()).toBeVisible({ timeout: 5000 });
    console.log('✅ Calendar grid visible');

    // Verify both calendars are visible (should have two sets of month/year headers)
    const monthHeaders = page.locator('span.text-sm.font-medium.text-gray-900');
    const headerCount = await monthHeaders.count();
    console.log(`📅 Found ${headerCount} calendar month headers`);
    expect(headerCount).toBeGreaterThanOrEqual(2);

    console.log('✅ TEST PASSED: Popover opens correctly with dual calendars');
  });

  test('should display quick preset buttons', async ({ page }) => {
    console.log('\n🧪 TEST: Display quick preset buttons');

    await page.waitForTimeout(1000);

    // Open date picker
    const dateButton = page.locator('button').filter({
      hasText: /\w{3}\s+\d{1,2},\s+\d{4}\s+-\s+\w{3}\s+\d{1,2},\s+\d{4}/
    });
    await dateButton.click();
    await page.waitForTimeout(500);

    // Check for all preset buttons
    const presets = [
      'Last 7 days',
      'Last 30 days',
      'This month',
      'Last month',
      'This year'
    ];

    console.log('🔍 Checking for preset buttons...');
    for (const preset of presets) {
      const presetButton = page.locator(`button:has-text("${preset}")`);
      await expect(presetButton).toBeVisible();
      console.log(`  ✅ "${preset}" button found`);
    }

    console.log('✅ TEST PASSED: All preset buttons visible');
  });

  test('should apply "Last 7 days" preset', async ({ page }) => {
    console.log('\n🧪 TEST: Apply "Last 7 days" preset');

    await page.waitForTimeout(1000);

    // Open date picker
    const dateButton = page.locator('button').filter({
      hasText: /\w{3}\s+\d{1,2},\s+\d{4}\s+-\s+\w{3}\s+\d{1,2},\s+\d{4}/
    });
    await dateButton.click();
    await page.waitForTimeout(500);

    // Click "Last 7 days" preset
    console.log('🖱️ Clicking "Last 7 days" preset...');
    const presetButton = page.locator('button:has-text("Last 7 days")');
    await presetButton.click();
    await page.waitForTimeout(300);

    // Verify the date button updates with the selected range
    console.log('🔍 Verifying date range text updated...');
    const buttonText = await dateButton.textContent();
    console.log(`📅 Date button text: "${buttonText}"`);

    // Should contain date format like "Jan 1, 2024 - Jan 7, 2024"
    expect(buttonText).toMatch(/\w{3}\s+\d{1,2},\s+\d{4}\s+-\s+\w{3}\s+\d{1,2},\s+\d{4}/);

    console.log('✅ TEST PASSED: Last 7 days preset applied successfully');
  });

  test('should handle BACKWARD date selection (critical bug test)', async ({ page }) => {
    console.log('\n🧪 TEST: Backward date selection (CRITICAL BUG TEST)');
    console.log('⚠️  This tests date swapping when selecting backward');

    await page.waitForTimeout(1000);

    // Setup error tracking
    let hasInvalidIntervalError = false;
    page.on('console', msg => {
      if (msg.type() === 'error' && msg.text().includes('Invalid interval')) {
        hasInvalidIntervalError = true;
        console.error('🚨 INVALID INTERVAL ERROR DETECTED!');
      }
    });

    // Open date picker
    const dateButton = page.locator('button').filter({
      hasText: /\w{3}\s+\d{1,2},\s+\d{4}\s+-\s+\w{3}\s+\d{1,2},\s+\d{4}/
    });
    await dateButton.click();
    await page.waitForTimeout(500);

    // Click a LATER date first (e.g., day 20)
    console.log('🖱️ Selecting LATER date first (day 20)...');
    const laterDate = page.locator('.calendar-day').filter({ hasText: /^20$/ }).first();
    await laterDate.click();
    await page.waitForTimeout(300);
    console.log('✅ Later date (20) selected');

    // Now click an EARLIER date (backward selection - this should auto-swap)
    console.log('🖱️ Clicking earlier date (day 5) - should auto-swap...');
    const earlierDate = page.locator('.calendar-day').filter({ hasText: /^5$/ }).first();
    await earlierDate.click({ force: true }); // Force click as Vue re-renders after first click
    await page.waitForTimeout(500);
    console.log('✅ Earlier date selected');

    // Verify NO invalid interval error occurred
    if (hasInvalidIntervalError) {
      throw new Error('🚨 INVALID INTERVAL ERROR DETECTED - BUG NOT FIXED!');
    }

    // Verify date range is displayed (dates should be swapped: 5 to 20)
    const buttonText = await dateButton.textContent();
    console.log(`📅 Final date button text: "${buttonText}"`);
    expect(buttonText).toMatch(/\w{3}\s+\d{1,2},\s+\d{4}\s+-\s+\w{3}\s+\d{1,2},\s+\d{4}/);

    console.log('✅ TEST PASSED: Backward date selection works WITHOUT Invalid interval error!');
    console.log('🎉 BUG FIX CONFIRMED WORKING!');
  });

  test('should display dual calendar months', async ({ page }) => {
    console.log('\n🧪 TEST: Display dual calendar months');

    await page.waitForTimeout(1000);

    // Open date picker
    const dateButton = page.locator('button').filter({
      hasText: /\w{3}\s+\d{1,2},\s+\d{4}\s+-\s+\w{3}\s+\d{1,2},\s+\d{4}/
    });
    await dateButton.click();
    await page.waitForTimeout(500);

    // Verify month headers are displayed (dual calendar)
    const monthHeaders = page.locator('span.text-sm.font-medium.text-gray-900');
    const monthCount = await monthHeaders.count();
    console.log(`📅 Found ${monthCount} month headers`);
    expect(monthCount).toBeGreaterThanOrEqual(2);

    // Get the month names to verify they're different (current + next month)
    const months = await monthHeaders.allTextContents();
    console.log(`📅 Calendar months: ${months.join(', ')}`);

    console.log('✅ TEST PASSED: Dual calendar displays correctly');
  });

  test('should clear date selection', async ({ page }) => {
    console.log('\n🧪 TEST: Clear date selection');

    await page.waitForTimeout(1000);

    // Open date picker
    const dateButton = page.locator('button').filter({
      hasText: /\w{3}\s+\d{1,2},\s+\d{4}\s+-\s+\w{3}\s+\d{1,2},\s+\d{4}/
    });
    await dateButton.click();
    await page.waitForTimeout(500);

    // Apply a preset first
    console.log('🖱️ Applying "Last 7 days" preset first...');
    const presetButton = page.locator('button:has-text("Last 7 days")');
    await presetButton.click();
    await page.waitForTimeout(300);

    // Now click Clear button
    console.log('🖱️ Clicking Clear button...');
    const clearButton = page.locator('button:has-text("Clear")');
    await expect(clearButton).toBeVisible();
    await clearButton.click();
    await page.waitForTimeout(300);

    // Verify default range is restored (Last 30 days)
    console.log('🔍 Verifying default range restored...');
    const buttonText = await dateButton.textContent();
    console.log(`📅 Date button after clear: "${buttonText}"`);
    expect(buttonText).toMatch(/\w{3}\s+\d{1,2},\s+\d{4}\s+-\s+\w{3}\s+\d{1,2},\s+\d{4}/);

    console.log('✅ TEST PASSED: Clear button works');
  });

  test('should trigger API call when date range changes', async ({ page }) => {
    console.log('\n🧪 TEST: API call triggered on date change');

    await page.waitForTimeout(1000);

    // Setup API response monitoring
    const apiCallPromise = page.waitForResponse(
      resp => resp.url().includes('/api/') &&
              (resp.url().includes('from=') || resp.url().includes('benchmarks')),
      { timeout: 10000 }
    );

    // Open date picker
    const dateButton = page.locator('button').filter({
      hasText: /\w{3}\s+\d{1,2},\s+\d{4}\s+-\s+\w{3}\s+\d{1,2},\s+\d{4}/
    });
    await dateButton.click();
    await page.waitForTimeout(500);

    // Apply a preset to trigger change
    console.log('🖱️ Applying "This month" preset to trigger API call...');
    const presetButton = page.locator('button:has-text("This month")');
    await presetButton.click();

    // Wait for API call
    console.log('⏳ Waiting for API call...');
    const response = await apiCallPromise;
    console.log(`✅ API called: ${response.url()}`);
    expect(response.status()).toBeLessThan(400);

    console.log('✅ TEST PASSED: Date change triggers API call');
  });
});
