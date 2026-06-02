import { test, expect } from '@playwright/test';

const BASE_URL = process.env.APP_URL || 'https://rb-benchmarks.redbananas.com';
const CLIENT_ID = 54;
const DASHBOARD_SEO_URL = `${BASE_URL}/clients/${CLIENT_ID}/dashboard?seo`;

/**
 * Find a card/section by its h3 heading text, then assert the table inside has >= minRows data rows.
 */
async function assertTableHasRows(page, headingText: string, minRows = 1) {
    const section = page.locator('.bg-white.shadow.rounded-lg', {
        has: page.locator(`h3:has-text("${headingText}")`),
    });
    await expect(section).toBeVisible({ timeout: 15000 });
    const rows = section.locator('table tbody tr');
    const count = await rows.count();
    expect(count, `"${headingText}" table should have >= ${minRows} row(s), got ${count}`).toBeGreaterThanOrEqual(minRows);
}

/**
 * Login via the Livewire login form.
 */
async function livewireLogin(page) {
    const emailInput = page.locator('input[type="email"]');
    const passwordInput = page.locator('input[type="password"]');

    await emailInput.click();
    await emailInput.fill('');
    await emailInput.type('admin@demo.com', { delay: 30 });
    await emailInput.dispatchEvent('change');

    await passwordInput.click();
    await passwordInput.fill('');
    await passwordInput.type('password', { delay: 30 });
    await passwordInput.dispatchEvent('change');

    // Pause for Livewire to sync wire:model values
    await page.waitForTimeout(500);

    await page.click('button[type="submit"]');
    // Post-login redirects to /ad-accounts
    await page.waitForURL(/\/(ad-accounts|clients|benchmarks)/, { timeout: 15000 });
}

test.describe('Client 54 SEO Dashboard - Table Rendering', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(DASHBOARD_SEO_URL);

        // Handle login redirect (Livewire form)
        if (page.url().includes('/login')) {
            await livewireLogin(page);
            await page.goto(DASHBOARD_SEO_URL);
        }

        await page.waitForLoadState('networkidle');

        // Wait for Livewire SEO data to load — the loading spinner disappears
        // and the KPI "Key Metrics" heading appears when data is ready
        await page.waitForSelector('.animate-spin', { state: 'hidden', timeout: 60000 }).catch(() => {});
        await page.waitForSelector('h3:has-text("Key Metrics")', { timeout: 60000 });
    });

    /** Click a sub-tab button by its label text */
    async function clickSubTab(page, label: string) {
        const btn = page.locator(`nav button:has-text("${label}")`).last();
        await btn.click();
        await page.waitForTimeout(300); // allow Alpine transition
    }

    // --- GA4 tables (in Analytics sub-tab) ---

    test('Traffic Sources table has data rows', async ({ page }) => {
        await clickSubTab(page, 'Analytics');
        await assertTableHasRows(page, 'Traffic Sources');
    });

    test('Top Pages (GA4) table has data rows', async ({ page }) => {
        await clickSubTab(page, 'Analytics');
        await assertTableHasRows(page, 'Top Pages (GA4)');
    });

    test('Devices table has data rows', async ({ page }) => {
        await clickSubTab(page, 'Analytics');
        await assertTableHasRows(page, 'Devices');
    });

    test('Geographic Data table has data rows', async ({ page }) => {
        await clickSubTab(page, 'Analytics');
        await assertTableHasRows(page, 'Geographic Data');
    });

    test('Browser / Technology table has data rows', async ({ page }) => {
        await clickSubTab(page, 'Analytics');
        await assertTableHasRows(page, 'Browser / Technology');
    });

    // --- Search Console tables (in Search Console sub-tab) ---

    test('Top Pages (Search Console) table has data rows', async ({ page }) => {
        await clickSubTab(page, 'Search Console');
        const heading = page.locator('h3:has-text("Top Pages (Search Console)")');
        const isVisible = await heading.isVisible().catch(() => false);
        test.skip(!isVisible, 'Search Console data not available for this client');
        await assertTableHasRows(page, 'Top Pages (Search Console)');
    });

    test('Top Search Queries table has data rows', async ({ page }) => {
        await clickSubTab(page, 'Search Console');
        const heading = page.locator('h3:has-text("Top Search Queries")');
        const isVisible = await heading.isVisible().catch(() => false);
        test.skip(!isVisible, 'Search Console data not available for this client');
        await assertTableHasRows(page, 'Top Search Queries');
    });

    // --- Ecommerce tables (in Ecommerce sub-tab) ---

    test('Ecommerce pages table has data rows', async ({ page }) => {
        const ecomTab = page.locator('nav button:has-text("Ecommerce")').last();
        const isPresent = await ecomTab.isVisible().catch(() => false);
        test.skip(!isPresent, 'Ecommerce tab not present for this client');
        await ecomTab.click();
        await page.waitForTimeout(300);
        const heading = page.locator('h3:has-text("Top Ecommerce Pages")');
        const isVisible = await heading.isVisible().catch(() => false);
        test.skip(!isVisible, 'Ecommerce pages not present');
        await assertTableHasRows(page, 'Top Ecommerce Pages');
    });

    // --- Summary test: all GA4 tables (in Analytics tab) are present ---

    test('all GA4 tables are present with data', async ({ page }) => {
        await clickSubTab(page, 'Analytics');
        const ga4Headings = [
            'Traffic Sources',
            'Top Pages (GA4)',
            'Devices',
            'Geographic Data',
            'Browser / Technology',
        ];

        for (const heading of ga4Headings) {
            const section = page.locator('.bg-white.shadow.rounded-lg', {
                has: page.locator(`h3:has-text("${heading}")`),
            });
            await expect(section, `Section "${heading}" should be visible`).toBeVisible({ timeout: 10000 });
            const rows = section.locator('table tbody tr');
            const count = await rows.count();
            expect(count, `"${heading}" table should have data rows`).toBeGreaterThanOrEqual(1);
        }
    });
});
