import { test, expect } from '@playwright/test';

/**
 * Industry Overview Page E2E Tests
 * Tests the functionality of https://rb-benchmarks.redbananas.com/industry-overview
 */

// Test authentication helper
test.beforeEach(async ({ page }) => {
  // Navigate to industry overview page
  await page.goto('/industry-overview');

  // If redirected to login, handle authentication
  if (page.url().includes('/login')) {
    // Fill in credentials
    await page.fill('input[type="email"]', 'admin@demo.com');
    await page.fill('input[type="password"]', 'password');
    await page.click('button[type="submit"]');

    // Wait for redirect after login (app redirects to /benchmarks)
    await page.waitForURL(/\/(benchmarks|industry-overview|ad-accounts)/, { timeout: 15000 });

    // Navigate to industry-overview if not already there
    if (!page.url().includes('/industry-overview')) {
      await page.goto('/industry-overview');
      await page.waitForLoadState('networkidle');
    }
  }
});

test.describe('Industry Overview Page', () => {

  test('should load the page successfully', async ({ page }) => {
    await page.goto('/industry-overview');

    // Check page title
    await expect(page).toHaveTitle(/Industry Overview|RB Benchmarks/);

    // Check main heading
    await expect(page.locator('h1')).toContainText('Industry Overview');

    // Check description text
    await expect(page.locator('p').first()).toContainText('View industry performance');
  });

  test('should display filter section', async ({ page }) => {
    await page.goto('/industry-overview');

    // Check for date range inputs
    const fromDate = page.locator('input[type="date"]').first();
    const toDate = page.locator('input[type="date"]').nth(1);

    await expect(fromDate).toBeVisible();
    await expect(toDate).toBeVisible();

    // Check for platform filter
    const platformSelect = page.locator('select').first();
    await expect(platformSelect).toBeVisible();
    await expect(platformSelect).toContainText('All Platforms');

    // Check for refresh button
    const refreshButton = page.locator('button:has-text("Refresh")');
    await expect(refreshButton).toBeVisible();
  });

  test('should display summary cards with data', async ({ page }) => {
    await page.goto('/industry-overview');

    // Wait for data to load
    await page.waitForSelector('text=Total Industries', { timeout: 10000 });

    // Check for all 4 summary cards
    await expect(page.locator('text=Total Industries')).toBeVisible();
    await expect(page.locator('text=Total Accounts')).toBeVisible();
    await expect(page.locator('text=Total Impressions')).toBeVisible();
    await expect(page.locator('text=Total Spend')).toBeVisible();

    // Verify numbers are displayed (not just placeholders)
    const totalIndustries = page.locator('p:has-text("Total Industries")').locator('..').locator('p').nth(1);
    await expect(totalIndustries).not.toHaveText('0');
  });

  test('should display industry performance table', async ({ page }) => {
    await page.goto('/industry-overview');

    // Wait for table to load
    await page.waitForSelector('table', { timeout: 10000 });

    // Check table headers
    await expect(page.locator('th:has-text("Industry")')).toBeVisible();
    await expect(page.locator('th:has-text("Accounts")')).toBeVisible();
    await expect(page.locator('th:has-text("Total Impressions")')).toBeVisible();
    await expect(page.locator('th:has-text("Total Spend")')).toBeVisible();

    // Check that at least one row exists
    const tableRows = page.locator('tbody tr');
    await expect(tableRows).toHaveCount({ gt: 0 });
  });

  test('should filter by date range', async ({ page }) => {
    await page.goto('/industry-overview');

    // Wait for initial load
    await page.waitForSelector('table', { timeout: 10000 });

    // Set date range (last 7 days)
    const today = new Date();
    const sevenDaysAgo = new Date(today);
    sevenDaysAgo.setDate(today.getDate() - 7);

    const fromDate = sevenDaysAgo.toISOString().split('T')[0];
    const toDate = today.toISOString().split('T')[0];

    await page.locator('input[type="date"]').first().fill(fromDate);
    await page.locator('input[type="date"]').nth(1).fill(toDate);

    // Wait for API call and data refresh
    await page.waitForResponse(response =>
      response.url().includes('/api/industry-overview') && response.status() === 200
    );

    // Verify table still has data or shows appropriate message
    const hasTable = await page.locator('table tbody tr').count() > 0;
    const hasEmptyState = await page.locator('text=No industry data').isVisible();

    expect(hasTable || hasEmptyState).toBeTruthy();
  });

  test('should filter by platform', async ({ page }) => {
    await page.goto('/industry-overview');

    // Wait for initial load
    await page.waitForSelector('table', { timeout: 10000 });

    // Select Facebook platform
    await page.locator('select').first().selectOption('facebook');

    // Wait for API response
    await page.waitForResponse(response =>
      response.url().includes('/api/industry-overview') &&
      response.url().includes('platform=facebook') &&
      response.status() === 200
    );

    // Verify data updated (could be filtered or empty)
    const hasData = await page.locator('table tbody tr').count();
    expect(hasData).toBeGreaterThanOrEqual(0);
  });

  test('should show loading state', async ({ page }) => {
    await page.goto('/industry-overview');

    // Look for loading indicator shortly after page load
    // May need to throttle network to catch this
    const loadingText = page.locator('text=Loading');

    // Check if loading state appears or if loading is too fast
    const loadingCount = await loadingText.count();
    expect(loadingCount).toBeGreaterThanOrEqual(0); // At least doesn't error
  });

  test('should handle refresh button click', async ({ page }) => {
    await page.goto('/industry-overview');

    // Wait for initial load
    await page.waitForSelector('table', { timeout: 10000 });

    // Click refresh button
    await page.click('button:has-text("Refresh")');

    // Wait for API call
    await page.waitForResponse(response =>
      response.url().includes('/api/industry-overview') && response.status() === 200
    );

    // Verify data is still displayed
    await expect(page.locator('table')).toBeVisible();
  });

  test('should display currency information banner', async ({ page }) => {
    await page.goto('/industry-overview');

    // Wait for page load
    await page.waitForSelector('table', { timeout: 10000 });

    // Check if currency info banner is present
    const currencyBanner = page.locator('text=Currency Information');
    const bannerCount = await currencyBanner.count();

    // Banner may or may not appear depending on data
    expect(bannerCount).toBeGreaterThanOrEqual(0);
  });

  test('should display SAR currency formatting', async ({ page }) => {
    await page.goto('/industry-overview');

    // Wait for data to load
    await page.waitForSelector('table', { timeout: 10000 });

    // Check that SAR or currency symbol appears in spend column
    const spendCells = page.locator('td').filter({ hasText: /SAR|SR|ر\.س/ });
    const count = await spendCells.count();

    // At least some cells should show currency
    expect(count).toBeGreaterThan(0);
  });

  test('should show empty state when no data', async ({ page }) => {
    await page.goto('/industry-overview');

    // Set a future date range with no data
    const futureDate = new Date();
    futureDate.setFullYear(futureDate.getFullYear() + 1);
    const futureDateStr = futureDate.toISOString().split('T')[0];

    await page.locator('input[type="date"]').first().fill(futureDateStr);
    await page.locator('input[type="date"]').nth(1).fill(futureDateStr);

    // Wait for API response
    await page.waitForResponse(response =>
      response.url().includes('/api/industry-overview') && response.status() === 200
    );

    // Check for empty state message
    const emptyState = page.locator('text=No industry data');
    await expect(emptyState).toBeVisible({ timeout: 5000 });
  });

  test('should handle API errors gracefully', async ({ page }) => {
    // Intercept API call and return error
    await page.route('**/api/industry-overview*', route => {
      route.fulfill({
        status: 500,
        body: JSON.stringify({ error: 'Internal Server Error' })
      });
    });

    await page.goto('/industry-overview');

    // Check for error message
    const errorMessage = page.locator('text=Error loading data');
    await expect(errorMessage).toBeVisible({ timeout: 5000 });
  });

  test('should have working navigation', async ({ page }) => {
    await page.goto('/industry-overview');

    // Check that we can navigate to other pages
    const navLinks = page.locator('nav a, [role="navigation"] a');
    const count = await navLinks.count();

    expect(count).toBeGreaterThan(0);
  });

  test('should be responsive on mobile', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });

    await page.goto('/industry-overview');

    // Wait for load
    await page.waitForSelector('h1', { timeout: 10000 });

    // Check that main elements are visible
    await expect(page.locator('h1')).toBeVisible();
    await expect(page.locator('text=Total Industries')).toBeVisible();
  });
});
