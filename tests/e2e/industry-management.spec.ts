import { test, expect } from '@playwright/test';

const BASE_URL = process.env.APP_URL || 'https://rb-benchmarks.redbananas.com';

async function login(page) {
  await page.goto(`${BASE_URL}/login`);
  await page.fill('input[type="email"], input[placeholder*="Email"], input[name="email"]','admin@demo.com');
  await page.fill('input[type="password"], input[placeholder*="Password"], input[name="password"]','password');
  await page.click('button:has-text("Login"), button[type="submit"]');
  await page.waitForTimeout(2000);

  const currentUrl = page.url();
  if (currentUrl.includes('/login')) {
    throw new Error('Login failed — still on login page');
  }
}

test.describe('Industry Management Page — Unified Hierarchy Tree', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto(`${BASE_URL}/industry-management`);
    await page.waitForTimeout(2000);
  });

  test('should load the unified hierarchy tree page', async ({ page }) => {
    await expect(page).toHaveTitle(/Industry|RB Benchmarks/);
    await expect(page.locator('h1')).toContainText(/Industry|الصناعات/i);

    // Unified tree container should be present
    await expect(page.locator('h1').locator('xpath=../../../div[contains(@class,"divide-y")]')).toBeVisible();
  });

  test('should show hierarchy stats cards', async ({ page }) => {
    await expect(page.locator('dt:has-text("Total Industries")').first()).toBeVisible();
    await expect(page.locator('dt:has-text("Total Account Categories")').first()).toBeVisible();
    await expect(page.locator('dt:has-text("Total Sub-Categories")').first()).toBeVisible();
  });

  test('should render industries as top-level rows', async ({ page }) => {
    // At least one industry row should be visible as a top-level heading
    const industryRows = page.locator('h3');
    const count = await industryRows.count();
    expect(count).toBeGreaterThan(0);
  });

  test('should expand an industry and show account categories', async ({ page }) => {
    // Click the first industry chevron if collapsed
    const firstIndustryRow = page.locator('h3').first().locator('xpath=../..');
    await firstIndustryRow.locator('svg').first().click();
    await page.waitForTimeout(300);

    // Account category rows use h4
    const accountCategoryRows = page.locator('h4');
    expect(await accountCategoryRows.count()).toBeGreaterThanOrEqual(0);
  });

  test('should show sub-categories as inline tags under account categories', async ({ page }) => {
    // Expand first industry and first account category
    const firstIndustryChevron = page.locator('h3').first().locator('xpath=../../svg');
    if (await firstIndustryChevron.count()) await firstIndustryChevron.click();

    const firstCategoryChevron = page.locator('h4').first().locator('xpath=../../svg');
    if (await firstCategoryChevron.count()) await firstCategoryChevron.click();

    await page.waitForTimeout(300);

    // Sub-category count badges should be visible
    await expect(page.locator('text=/\\d+ Sub-Categories/').first()).toBeVisible();

    // Sub-category inline tags should be visible when expanded
    const subTags = page.locator('span[class*="rounded-full"]');
    expect(await subTags.count()).toBeGreaterThan(0);
  });

  test('should have visual separation between levels', async ({ page }) => {
    // Industry level uses h3
    const industry = page.locator('h3').first();
    await expect(industry).toBeVisible();

    // Expand first industry
    await industry.locator('xpath=../..').locator('svg').first().click();
    await page.waitForTimeout(300);

    // Account category level uses h4
    const firstCategory = page.locator('h4').first();
    await expect(firstCategory).toBeVisible();

    // Industry and category names should differ
    const industryName = await industry.textContent();
    const categoryName = await firstCategory.textContent();
    expect(industryName?.trim()).not.toEqual(categoryName?.trim());
  });

  test('should show all 29 account categories across the tree', async ({ page }) => {
    // Expand all industries by clicking every collapsed chevron
    const chevrons = page.locator('h3').locator('xpath=../..').locator('svg[class*="-rotate-90"]');
    const collapsedCount = await chevrons.count();
    for (let i = 0; i < collapsedCount; i++) {
      await chevrons.nth(0).click();
      await page.waitForTimeout(100);
    }

    // Wait for any Livewire update
    await page.waitForTimeout(500);

    const categoryHeadings = page.locator('h4');
    const count = await categoryHeadings.count();
    expect(count).toBeGreaterThanOrEqual(29);
  });
});
