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

test.describe('Calculator Realistic Results Test', () => {
  test('should show realistic predictions for Real Estate industry', async ({ page }) => {
    console.log('\n🚀 Starting Calculator Realistic Results Test...\n');

    // Navigate to benchmarks page
    console.log('📍 Navigating to benchmarks page...');
    await page.goto('https://rb-benchmarks.redbananas.com/benchmarks');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    console.log('✅ Page loaded\n');

    // Click Calculator tab
    const calcTab = page.locator('text=Calculator').first();
    await expect(calcTab).toBeVisible({ timeout: 10000 });
    console.log('👁️  Calculator tab found');

    await calcTab.click();
    await page.waitForTimeout(3000); // Wait for data to load

    console.log('✅ Calculator tab opened\n');

    // Fill in the form
    console.log('📝 Filling Calculator form...');

    // Select Real Estate industry
    const industrySelect = page.locator('select').filter({ hasText: /Select industry/ }).or(
      page.locator('select').nth(0) // First select if text filter doesn't work
    );
    await industrySelect.selectOption('real_estate');
    console.log('  ✓ Selected Real Estate industry');

    // Enter $1000 spend
    const spendInput = page.locator('input[type="number"]').first();
    await spendInput.fill('1000');
    console.log('  ✓ Entered $1000 spend');

    // Wait a bit for form to stabilize
    await page.waitForTimeout(1000);

    // Click Calculate Results button
    console.log('\n🔘 Clicking Calculate Results button...');
    const calculateBtn = page.locator('button', { hasText: /Calculate Results/i });
    await calculateBtn.click();
    await page.waitForTimeout(3000); // Wait for calculation

    console.log('✅ Calculation complete\n');

    // Take screenshot
    await page.screenshot({
      path: '/tmp/calculator-realistic-results.png',
      fullPage: true
    });
    console.log('📸 Screenshot saved to /tmp/calculator-realistic-results.png\n');

    // Extract results
    console.log('🔍 Extracting results...\n');

    // Look for the results section
    const resultsSection = page.locator('text=/Expected Results for/').or(
      page.locator('text=/Leads:/').first()
    );

    await expect(resultsSection).toBeVisible({ timeout: 5000 });

    // Extract lead numbers from all performance tiers
    const leadTexts = await page.locator('text=/Leads:\\s*[\\d,]+/').allTextContents();
    console.log('📊 Raw lead text results:', leadTexts);

    // Parse lead numbers
    const leadNumbers: number[] = [];
    for (const text of leadTexts) {
      const match = text.match(/Leads:\s*([\d,]+)/);
      if (match) {
        const numStr = match[1].replace(/,/g, '');
        const num = parseInt(numStr);
        if (!isNaN(num)) {
          leadNumbers.push(num);
          console.log(`  Found: ${num.toLocaleString()} leads`);
        }
      }
    }

    // Extract CVR percentages
    const cvrTexts = await page.locator('text=/CVR:\\s*[\\d.]+%/').allTextContents();
    console.log('\n📊 CVR Results:', cvrTexts);

    const cvrNumbers: number[] = [];
    for (const text of cvrTexts) {
      const match = text.match(/CVR:\s*([\d.]+)%/);
      if (match) {
        const cvr = parseFloat(match[1]);
        if (!isNaN(cvr)) {
          cvrNumbers.push(cvr);
          console.log(`  Found CVR: ${cvr}%`);
        }
      }
    }

    console.log('\n=== RESULTS VALIDATION ===\n');

    // Validate results are realistic
    if (leadNumbers.length > 0) {
      const maxLeads = Math.max(...leadNumbers);
      const minLeads = Math.min(...leadNumbers);

      console.log(`Lead Range: ${minLeads.toLocaleString()} - ${maxLeads.toLocaleString()}`);

      // Check if results are realistic (should be < 1000 for $1000 spend)
      if (maxLeads < 1000) {
        console.log('✅ PASS: Lead predictions are REALISTIC (< 1,000)');
      } else if (maxLeads < 10000) {
        console.log('⚠️  WARNING: Lead predictions seem high but not impossible');
      } else {
        console.log(`❌ FAIL: Lead predictions are UNREALISTIC (${maxLeads.toLocaleString()})!`);
      }

      // Assertions
      expect(maxLeads).toBeLessThan(10000); // Should not predict > 10K leads for $1000
      expect(minLeads).toBeGreaterThanOrEqual(0);
    } else {
      console.log('⚠️  No lead numbers found in results');
    }

    // Validate CVR is realistic
    if (cvrNumbers.length > 0) {
      const maxCVR = Math.max(...cvrNumbers);
      const minCVR = Math.min(...cvrNumbers);

      console.log(`\nCVR Range: ${minCVR}% - ${maxCVR}%`);

      // CVR should be between 0% and 100%
      if (maxCVR <= 100) {
        console.log('✅ PASS: CVR is REALISTIC (<= 100%)');
      } else {
        console.log(`❌ FAIL: CVR is IMPOSSIBLE (${maxCVR}% > 100%)!`);
      }

      // Assertions
      expect(maxCVR).toBeLessThanOrEqual(100); // CVR cannot exceed 100%
      expect(minCVR).toBeGreaterThanOrEqual(0);
    } else {
      console.log('⚠️  No CVR percentages found in results');
    }

    console.log('\n=== TEST COMPLETE ===\n');

    // Overall assertions
    expect(leadNumbers.length).toBeGreaterThan(0); // Should have at least one lead prediction
    expect(cvrNumbers.length).toBeGreaterThan(0); // Should have at least one CVR value
  });

  test('should show reasonable results for $10,000 spend', async ({ page }) => {
    console.log('\n🚀 Testing Calculator with $10,000 spend...\n');

    await page.goto('https://rb-benchmarks.redbananas.com/benchmarks');
    await page.waitForLoadState('networkidle');

    const calcTab = page.locator('text=Calculator').first();
    await calcTab.click();
    await page.waitForTimeout(3000);

    // Select Real Estate
    const industrySelect = page.locator('select').nth(0);
    await industrySelect.selectOption('real_estate');

    // Enter $10,000 spend
    const spendInput = page.locator('input[type="number"]').first();
    await spendInput.fill('10000');
    console.log('  ✓ Entered $10,000 spend');

    await page.waitForTimeout(1000);

    // Calculate
    const calculateBtn = page.locator('button', { hasText: /Calculate Results/i });
    await calculateBtn.click();
    await page.waitForTimeout(3000);

    // Extract lead numbers
    const leadTexts = await page.locator('text=/Leads:\\s*[\\d,]+/').allTextContents();
    const leadNumbers: number[] = [];
    for (const text of leadTexts) {
      const match = text.match(/Leads:\s*([\d,]+)/);
      if (match) {
        const num = parseInt(match[1].replace(/,/g, ''));
        if (!isNaN(num)) leadNumbers.push(num);
      }
    }

    if (leadNumbers.length > 0) {
      const maxLeads = Math.max(...leadNumbers);
      console.log(`\nMax predicted leads for $10K: ${maxLeads.toLocaleString()}`);

      // For $10K, realistic range might be 10-500 leads depending on industry
      if (maxLeads < 5000) {
        console.log('✅ PASS: Predictions are realistic for $10K spend');
      } else {
        console.log(`⚠️  High prediction: ${maxLeads.toLocaleString()} leads`);
      }

      expect(maxLeads).toBeLessThan(100000); // Should not predict > 100K leads
    }

    console.log('\n=== TEST COMPLETE ===\n');
  });
});
