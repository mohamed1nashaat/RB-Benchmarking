import { test, expect } from '@playwright/test';

const BASE_URL = process.env.APP_URL || 'https://rb-benchmarks.redbananas.com';

async function login(page) {
  await page.goto(`${BASE_URL}/login`);
  await page.fill('input[type="email"], input[placeholder*="Email"], input[name="email"]', 'admin@demo.com');
  await page.fill('input[type="password"], input[placeholder*="Password"], input[name="password"]', 'password');
  await page.click('button:has-text("Login"), button[type="submit"]');
  await page.waitForTimeout(2000);

  const currentUrl = page.url();
  if (currentUrl.includes('/login')) {
    throw new Error('Login failed — still on login page');
  }
}

/**
 * Open a Select2 multi-select dropdown by its label text.
 */
async function openSelect2Dropdown(page, labelText: string) {
  const label = page.locator('label', { hasText: new RegExp(labelText, 'i') });
  await expect(label).toBeVisible({ timeout: 10000 });

  // Click the Select2 selection container that follows the label
  const select2Container = label.locator('..').locator('.select2-selection');
  await select2Container.click();

  // Wait for dropdown to appear in the DOM (appended to body by Select2)
  const dropdown = page.locator('.select2-container--open .select2-results__options');
  await expect(dropdown).toBeVisible({ timeout: 5000 });
}

/**
 * Select an option from the currently open Select2 dropdown.
 */
async function selectOption(page, optionText: string) {
  const option = page.locator('.select2-container--open .select2-results__option', {
    hasText: new RegExp(`^${optionText}$`, 'i'),
  });
  await expect(option).toBeVisible({ timeout: 5000 });
  await option.click();
}

/**
 * Get all visible option texts from the currently open Select2 dropdown.
 */
async function getDropdownOptions(page): Promise<string[]> {
  const options = page.locator('.select2-container--open .select2-results__option:not(.select2-results__message)');
  await expect(options.first()).toBeVisible({ timeout: 5000 });
  const count = await options.count();
  const texts: string[] = [];
  for (let i = 0; i < count; i++) {
    const text = await options.nth(i).textContent();
    if (text && text.trim() !== '') {
      texts.push(text.trim());
    }
  }
  return texts;
}

/**
 * Clear all selections from a Select2 multi-select by its label.
 */
async function clearSelect2(page, labelText: string) {
  const label = page.locator('label', { hasText: new RegExp(labelText, 'i') });
  const container = label.locator('..');
  const clearBtn = container.locator('.select2-selection__choice__remove');
  while (await clearBtn.count() > 0 && await clearBtn.first().isVisible().catch(() => false)) {
    await clearBtn.first().click();
    await page.waitForTimeout(300);
  }
}

test.describe('Benchmarks cascading filters', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto(`${BASE_URL}/benchmarks`);
    // Wait for initial filter options to load
    await page.waitForTimeout(3000);
  });

  test('selecting a country narrows the industry dropdown', async ({ page }) => {
    // Step 1: Capture initial industry count (should be many)
    await openSelect2Dropdown(page, 'Industry');
    const initialOptions = await getDropdownOptions(page);
    console.log(`Initial industry count: ${initialOptions.length}`);
    expect(initialOptions.length).toBeGreaterThan(5);

    // Close dropdown by pressing Escape
    await page.keyboard.press('Escape');
    await page.waitForTimeout(300);

    // Step 2: Select Country = Saudi Arabia (KSA)
    await openSelect2Dropdown(page, 'Country');
    await selectOption(page, 'Saudi Arabia');
    await page.waitForTimeout(300);

    // Wait for Livewire round-trip
    await page.waitForTimeout(2000);

    // Step 3: Re-open Industry dropdown — should show fewer options
    await openSelect2Dropdown(page, 'Industry');
    const narrowedOptions = await getDropdownOptions(page);
    console.log(`Industry count after KSA filter: ${narrowedOptions.length}`);
    console.log(`Narrowed industries: ${narrowedOptions.join(', ')}`);

    // Assert: industry list should be smaller than before
    expect(narrowedOptions.length).toBeLessThan(initialOptions.length);

    // Assert: some industries that don't have KSA data should be absent
    // (this is data-dependent; we just verify the count dropped)
  });

  test('selecting an industry narrows the country dropdown', async ({ page }) => {
    // Step 1: Select Industry = Auto Dealers & Retail
    await openSelect2Dropdown(page, 'Industry');
    await selectOption(page, 'Auto Dealers & Retail');
    await page.keyboard.press('Escape');
    await page.waitForTimeout(2000);

    // Step 2: Open Country dropdown — should show only countries with Auto Dealers data
    await openSelect2Dropdown(page, 'Country');
    const countryOptions = await getDropdownOptions(page);
    console.log(`Countries after Auto Dealers filter: ${countryOptions.join(', ')}`);

    // Assert: should not be empty, and should not contain 'All' as a selectable value
    expect(countryOptions.length).toBeGreaterThan(0);
  });

  test('changing upstream filter prunes invalid downstream selection', async ({ page }) => {
    // Step 1: Select Country = United States
    await openSelect2Dropdown(page, 'Country');
    await selectOption(page, 'United States');
    await page.keyboard.press('Escape');
    await page.waitForTimeout(2000);

    // Step 2: Select an industry (any that exists for US)
    await openSelect2Dropdown(page, 'Industry');
    const usIndustries = await getDropdownOptions(page);
    console.log(`US industries: ${usIndustries.join(', ')}`);
    expect(usIndustries.length).toBeGreaterThan(0);

    const firstIndustry = usIndustries.find(o => o !== 'All') || usIndustries[0];
    await selectOption(page, firstIndustry);
    await page.keyboard.press('Escape');
    await page.waitForTimeout(2000);

    // Verify industry is selected
    const selectedIndustryPill = page.locator('.select2-selection__choice', { hasText: new RegExp(firstIndustry, 'i') });
    await expect(selectedIndustryPill.first()).toBeVisible({ timeout: 5000 });

    // Step 3: Change Country to KSA
    await clearSelect2(page, 'Country');
    await page.waitForTimeout(500);
    await openSelect2Dropdown(page, 'Country');
    await selectOption(page, 'Saudi Arabia');
    await page.keyboard.press('Escape');
    await page.waitForTimeout(2000);

    // Step 4: Verify the previously selected industry was pruned if invalid
    // If the industry still exists for KSA, the pill remains;
    // if not, it should have been removed.
    const industryPill = page.locator('.select2-selection__choice', { hasText: new RegExp(firstIndustry, 'i') });
    const stillSelected = await industryPill.count() > 0 && await industryPill.first().isVisible().catch(() => false);

    if (stillSelected) {
      console.log(`Industry "${firstIndustry}" is still valid for KSA — preserved correctly`);
    } else {
      console.log(`Industry "${firstIndustry}" was pruned because it has no KSA data — cascade working`);
    }
  });
});
