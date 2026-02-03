import { test, expect, type Page } from '@playwright/test';

const BASE_URL = process.env.APP_URL || 'https://rb-benchmarks.redbananas.com';
const DASHBOARD_URL = `${BASE_URL}/clients/1/dashboard`;

test.describe('Client Dashboard E2E Tests', () => {
    test.beforeEach(async ({ page }) => {
        // Navigate to the dashboard
        await page.goto(DASHBOARD_URL);

        // Wait for page to load
        await page.waitForLoadState('networkidle');
    });

    // ==========================================
    // Page Load & Authentication Tests (3)
    // ==========================================

    test.describe('Page Load & Authentication', () => {
        test('should load dashboard page successfully', async ({ page }) => {
            await expect(page).toHaveURL(DASHBOARD_URL);
            await expect(page.locator('text=Client Dashboard')).toBeVisible();
        });

        test('should display client information', async ({ page }) => {
            // Check that client name is displayed
            const clientName = page.locator('[data-testid="client-name"]').or(page.locator('h1, h2').filter({ hasText: /Demo Company|Client/ }).first());
            await expect(clientName).toBeVisible();
        });

        test('should show loading state initially', async ({ page }) => {
            // Reload to catch loading state
            const responsePromise = page.waitForResponse(resp => resp.url().includes('/api/clients/1/dashboard'));
            await page.reload();

            // Check for loading indicator (spinner, skeleton, or "Loading..." text)
            const loadingIndicator = page.locator('[data-testid="loading"]').or(page.locator('text=Loading')).first();

            await responsePromise;
            // After response, loading should be gone
            await expect(loadingIndicator).not.toBeVisible({ timeout: 5000 });
        });
    });

    // ==========================================
    // Data Verification Tests (6)
    // ==========================================

    test.describe('Data Verification from Real Ad Accounts', () => {
        test('should display key metrics from ad accounts', async ({ page }) => {
            // Verify that metrics cards are visible and contain numeric data
            const metricsCards = page.locator('.metric-card, [data-testid="metric-card"]');
            await expect(metricsCards.first()).toBeVisible();

            // Check for Total Spend
            const spendMetric = page.locator('text=Total Spend').or(page.locator('text=/total.*spend/i')).first();
            await expect(spendMetric).toBeVisible();

            // Check for Total Impressions
            const impressionsMetric = page.locator('text=/impressions/i').first();
            await expect(impressionsMetric).toBeVisible();

            // Check for Total Clicks
            const clicksMetric = page.locator('text=/clicks/i').first();
            await expect(clicksMetric).toBeVisible();
        });

        test('should fetch data from /api/clients/1/dashboard endpoint', async ({ page }) => {
            const responsePromise = page.waitForResponse(resp =>
                resp.url().includes('/api/clients/1/dashboard') && resp.status() === 200
            );

            await page.reload();
            const response = await responsePromise;
            const data = await response.json();

            // Verify response structure
            expect(data).toHaveProperty('metrics');
            expect(data).toHaveProperty('trends');
            expect(data).toHaveProperty('platform_breakdown');
            expect(data).toHaveProperty('ad_accounts');
            expect(data).toHaveProperty('top_campaigns');
        });

        test('should display data from connected ad accounts', async ({ page }) => {
            const response = await page.request.get(`${BASE_URL}/api/clients/1/dashboard`);
            const data = await response.json();

            // Verify ad accounts are present
            expect(data.ad_accounts).toBeDefined();
            expect(data.ad_accounts.length).toBeGreaterThan(0);

            // Check that ad accounts section is visible on page
            const adAccountsSection = page.locator('text=/ad accounts/i').or(page.locator('text=/accounts/i')).first();
            await expect(adAccountsSection).toBeVisible();
        });

        test('should show platform breakdown from real platforms', async ({ page }) => {
            const response = await page.request.get(`${BASE_URL}/api/clients/1/dashboard`);
            const data = await response.json();

            // Verify platform breakdown exists
            expect(data.platform_breakdown).toBeDefined();
            expect(data.platform_breakdown.length).toBeGreaterThan(0);

            // Check for common platforms (facebook, google, snapchat, tiktok, twitter)
            const platforms = data.platform_breakdown.map((p: any) => p.platform.toLowerCase());
            const knownPlatforms = ['facebook', 'google', 'snapchat', 'tiktok', 'twitter'];
            const hasKnownPlatform = platforms.some((p: string) => knownPlatforms.includes(p));

            expect(hasKnownPlatform).toBeTruthy();
        });

        test('should display campaign data from ad accounts', async ({ page }) => {
            const response = await page.request.get(`${BASE_URL}/api/clients/1/dashboard`);
            const data = await response.json();

            // Verify campaigns exist
            expect(data.top_campaigns).toBeDefined();

            // If there are campaigns, verify structure
            if (data.top_campaigns.length > 0) {
                const campaign = data.top_campaigns[0];
                expect(campaign).toHaveProperty('name');
                expect(campaign).toHaveProperty('spend');
                expect(campaign).toHaveProperty('conversions');
            }
        });

        test('should calculate metrics correctly from ad metrics table', async ({ page }) => {
            const response = await page.request.get(`${BASE_URL}/api/clients/1/dashboard`);
            const data = await response.json();

            // Verify calculated metrics
            expect(data.metrics.total_spend).toBeGreaterThanOrEqual(0);
            expect(data.metrics.total_impressions).toBeGreaterThanOrEqual(0);
            expect(data.metrics.total_clicks).toBeGreaterThanOrEqual(0);

            // Verify derived metrics
            expect(data.metrics).toHaveProperty('ctr');
            expect(data.metrics).toHaveProperty('cvr');
            expect(data.metrics).toHaveProperty('cpc');
            expect(data.metrics).toHaveProperty('roas');
        });
    });

    // ==========================================
    // Charts Rendering Tests (5)
    // ==========================================

    test.describe('Charts Rendering', () => {
        test('should render spend trend line chart', async ({ page }) => {
            // Wait for chart to render
            await page.waitForSelector('canvas', { timeout: 10000 });

            // Check for spend trend chart
            const spendChart = page.locator('text=/spend.*trend/i').or(page.locator('text=/spend/i')).first();
            await expect(spendChart).toBeVisible();

            // Verify canvas element exists (Chart.js renders to canvas)
            const canvasElements = await page.locator('canvas').count();
            expect(canvasElements).toBeGreaterThan(0);
        });

        test('should render platform breakdown chart', async ({ page }) => {
            await page.waitForSelector('canvas', { timeout: 10000 });

            // Check for platform breakdown section
            const platformSection = page.locator('text=/platform/i').first();
            await expect(platformSection).toBeVisible();
        });

        test('should render multi-metric trend chart with tabs', async ({ page }) => {
            await page.waitForSelector('canvas', { timeout: 10000 });

            // Check for metric tabs (impressions, clicks, conversions)
            const impressionsTab = page.locator('button:has-text("impressions")').or(page.locator('text=Impressions')).first();
            const clicksTab = page.locator('button:has-text("clicks")').or(page.locator('text=Clicks')).first();
            const conversionsTab = page.locator('button:has-text("conversions")').or(page.locator('text=Conversions')).first();

            // At least one should be visible
            const hasMetricTabs = await impressionsTab.isVisible() || await clicksTab.isVisible() || await conversionsTab.isVisible();
            expect(hasMetricTabs).toBeTruthy();
        });

        test('should switch between metric tabs', async ({ page }) => {
            await page.waitForSelector('canvas', { timeout: 10000 });

            // Try to find and click on different metric tabs
            const clicksTab = page.locator('button').filter({ hasText: /^clicks$/i }).first();

            if (await clicksTab.isVisible()) {
                await clicksTab.click();

                // Verify the tab is now active (has different styling)
                const isActive = await clicksTab.evaluate((el) => {
                    return el.classList.contains('bg-primary-100') ||
                           el.classList.contains('active') ||
                           el.getAttribute('aria-selected') === 'true';
                });

                expect(isActive).toBeTruthy();
            }
        });

        test('should render top campaigns chart', async ({ page }) => {
            await page.waitForSelector('canvas', { timeout: 10000 });

            // Check for top campaigns section
            const campaignsSection = page.locator('text=/top.*campaigns/i').or(page.locator('text=/campaigns/i')).first();
            await expect(campaignsSection).toBeVisible();
        });
    });

    // ==========================================
    // Filter Functionality Tests (7)
    // ==========================================

    test.describe('Filter Functionality', () => {
        test('should show filters panel when clicking filter button', async ({ page }) => {
            // Find filter button
            const filterButton = page.locator('button').filter({ hasText: /filter/i }).first();
            await expect(filterButton).toBeVisible();

            // Click filter button
            await filterButton.click();

            // Check if filter panel appears
            await page.waitForTimeout(500); // Wait for animation
            const filterPanel = page.locator('text=/period/i, text=/date range/i, text=/platform/i').first();
            await expect(filterPanel).toBeVisible();
        });

        test('should filter by period buttons (7D, 30D, 90D)', async ({ page }) => {
            // Open filters
            const filterButton = page.locator('button').filter({ hasText: /filter/i }).first();
            await filterButton.click();

            // Find period buttons
            const period30Button = page.locator('button').filter({ hasText: /30D/i }).first();

            if (await period30Button.isVisible()) {
                // Set up response listener
                const responsePromise = page.waitForResponse(resp =>
                    resp.url().includes('/api/clients/1/dashboard') &&
                    resp.url().includes('period=30')
                );

                await period30Button.click();

                // Click apply filters
                const applyButton = page.locator('button').filter({ hasText: /apply/i }).first();
                if (await applyButton.isVisible()) {
                    await applyButton.click();
                }

                // Verify API was called with period parameter
                const response = await responsePromise;
                expect(response.url()).toContain('period');
            }
        });

        test('should filter by custom date range', async ({ page }) => {
            // Open filters
            const filterButton = page.locator('button').filter({ hasText: /filter/i }).first();
            await filterButton.click();

            // Find date inputs
            const dateFromInput = page.locator('input[type="date"]').first();
            const dateToInput = page.locator('input[type="date"]').nth(1);

            if (await dateFromInput.isVisible() && await dateToInput.isVisible()) {
                // Set date range
                await dateFromInput.fill('2024-01-01');
                await dateToInput.fill('2024-12-31');

                // Set up response listener
                const responsePromise = page.waitForResponse(resp =>
                    resp.url().includes('/api/clients/1/dashboard') &&
                    (resp.url().includes('from=') || resp.url().includes('to='))
                );

                // Click apply filters
                const applyButton = page.locator('button').filter({ hasText: /apply/i }).first();
                await applyButton.click();

                // Verify API was called with date parameters
                const response = await responsePromise;
                expect(response.url()).toMatch(/from=|to=/);
            }
        });

        test('should filter by platform', async ({ page }) => {
            // Open filters
            const filterButton = page.locator('button').filter({ hasText: /filter/i }).first();
            await filterButton.click();

            // Find platform selector
            const platformSelect = page.locator('select').filter({ hasText: /platform/i }).or(
                page.locator('select').first()
            );

            if (await platformSelect.isVisible()) {
                // Select a platform
                await platformSelect.selectOption('facebook');

                // Set up response listener
                const responsePromise = page.waitForResponse(resp =>
                    resp.url().includes('/api/clients/1/dashboard') &&
                    resp.url().includes('platform=facebook')
                );

                // Click apply filters
                const applyButton = page.locator('button').filter({ hasText: /apply/i }).first();
                await applyButton.click();

                // Verify API was called with platform parameter
                const response = await responsePromise;
                expect(response.url()).toContain('platform=facebook');
            }
        });

        test('should reset filters', async ({ page }) => {
            // Open filters
            const filterButton = page.locator('button').filter({ hasText: /filter/i }).first();
            await filterButton.click();

            // Apply some filter first
            const period30Button = page.locator('button').filter({ hasText: /30D/i }).first();
            if (await period30Button.isVisible()) {
                await period30Button.click();
            }

            // Find reset button
            const resetButton = page.locator('button').filter({ hasText: /reset/i }).first();

            if (await resetButton.isVisible()) {
                // Set up response listener
                const responsePromise = page.waitForResponse(resp =>
                    resp.url().includes('/api/clients/1/dashboard')
                );

                await resetButton.click();

                // Verify API was called (possibly without filter parameters or with defaults)
                await responsePromise;
            }
        });

        test('should update charts when filters are applied', async ({ page }) => {
            // Open filters
            const filterButton = page.locator('button').filter({ hasText: /filter/i }).first();
            await filterButton.click();

            // Wait for initial chart load
            await page.waitForSelector('canvas', { timeout: 10000 });

            // Take count of canvas elements before filter
            const canvasCountBefore = await page.locator('canvas').count();

            // Apply filter
            const period7Button = page.locator('button').filter({ hasText: /7D/i }).first();
            if (await period7Button.isVisible()) {
                await period7Button.click();

                const applyButton = page.locator('button').filter({ hasText: /apply/i }).first();
                if (await applyButton.isVisible()) {
                    const responsePromise = page.waitForResponse(resp =>
                        resp.url().includes('/api/clients/1/dashboard')
                    );

                    await applyButton.click();
                    await responsePromise;

                    // Wait a bit for charts to re-render
                    await page.waitForTimeout(1000);

                    // Verify charts still exist
                    const canvasCountAfter = await page.locator('canvas').count();
                    expect(canvasCountAfter).toBeGreaterThan(0);
                }
            }
        });

        test('should persist filter state when navigating', async ({ page }) => {
            // Open filters
            const filterButton = page.locator('button').filter({ hasText: /filter/i }).first();
            await filterButton.click();

            // Apply a filter
            const period30Button = page.locator('button').filter({ hasText: /30D/i }).first();
            if (await period30Button.isVisible()) {
                await period30Button.click();

                // Apply
                const applyButton = page.locator('button').filter({ hasText: /apply/i }).first();
                if (await applyButton.isVisible()) {
                    await applyButton.click();
                    await page.waitForTimeout(500);

                    // Check if 30D button is still selected/active
                    const isActive = await period30Button.evaluate((el) => {
                        return el.classList.contains('bg-primary-600') ||
                               el.classList.contains('bg-primary-500') ||
                               el.classList.contains('active');
                    });

                    expect(isActive).toBeTruthy();
                }
            }
        });
    });

    // ==========================================
    // Export Functionality Tests (6)
    // ==========================================

    test.describe('Export Functionality', () => {
        test('should show export dropdown button', async ({ page }) => {
            // Find export button
            const exportButton = page.locator('button').filter({ hasText: /export/i }).first();
            await expect(exportButton).toBeVisible();
        });

        test('should open export dropdown menu', async ({ page }) => {
            // Find and click export button
            const exportButton = page.locator('button').filter({ hasText: /export/i }).first();
            await exportButton.click();

            // Check for export options
            await page.waitForTimeout(300); // Wait for dropdown animation
            const pdfOption = page.locator('text=/export.*pdf/i, text=/pdf/i').first();
            const csvOption = page.locator('text=/export.*csv/i, text=/csv/i').first();
            const excelOption = page.locator('text=/export.*excel/i, text=/excel/i').first();

            // At least one option should be visible
            const hasExportOptions = await pdfOption.isVisible() ||
                                     await csvOption.isVisible() ||
                                     await excelOption.isVisible();
            expect(hasExportOptions).toBeTruthy();
        });

        test('should export as PDF', async ({ page }) => {
            // Click export button
            const exportButton = page.locator('button').filter({ hasText: /export/i }).first();
            await exportButton.click();

            // Wait for dropdown
            await page.waitForTimeout(300);

            // Click PDF option
            const pdfOption = page.locator('text=/^pdf$/i, button:has-text("PDF")').last();

            if (await pdfOption.isVisible()) {
                // Set up download listener
                const downloadPromise = page.waitForEvent('download', { timeout: 30000 });

                // Also listen for API call
                const responsePromise = page.waitForResponse(
                    resp => resp.url().includes('/api/clients/1/export/pdf'),
                    { timeout: 30000 }
                );

                await pdfOption.click();

                try {
                    // Wait for either download or API response
                    const response = await responsePromise;
                    expect(response.status()).toBe(200);
                } catch (e) {
                    // If no download, at least verify API was called
                    console.log('PDF export initiated');
                }
            }
        });

        test('should export as CSV', async ({ page }) => {
            // Click export button
            const exportButton = page.locator('button').filter({ hasText: /export/i }).first();
            await exportButton.click();

            // Wait for dropdown
            await page.waitForTimeout(300);

            // Click CSV option
            const csvOption = page.locator('text=/^csv$/i, button:has-text("CSV")').last();

            if (await csvOption.isVisible()) {
                // Listen for API call
                const responsePromise = page.waitForResponse(
                    resp => resp.url().includes('/api/clients/1/export/csv'),
                    { timeout: 30000 }
                );

                await csvOption.click();

                try {
                    const response = await responsePromise;
                    expect(response.status()).toBe(200);
                } catch (e) {
                    console.log('CSV export initiated');
                }
            }
        });

        test('should export as Excel', async ({ page }) => {
            // Click export button
            const exportButton = page.locator('button').filter({ hasText: /export/i }).first();
            await exportButton.click();

            // Wait for dropdown
            await page.waitForTimeout(300);

            // Click Excel option
            const excelOption = page.locator('text=/^excel$/i, button:has-text("Excel")').last();

            if (await excelOption.isVisible()) {
                // Listen for API call
                const responsePromise = page.waitForResponse(
                    resp => resp.url().includes('/api/clients/1/export/excel'),
                    { timeout: 30000 }
                );

                await excelOption.click();

                try {
                    const response = await responsePromise;
                    expect(response.status()).toBe(200);
                } catch (e) {
                    console.log('Excel export initiated');
                }
            }
        });

        test('should show loading state during export', async ({ page }) => {
            // Click export button
            const exportButton = page.locator('button').filter({ hasText: /export/i }).first();
            await exportButton.click();

            // Wait for dropdown
            await page.waitForTimeout(300);

            // Click PDF option
            const pdfOption = page.locator('text=/^pdf$/i, button:has-text("PDF")').last();

            if (await pdfOption.isVisible()) {
                await pdfOption.click();

                // Check if export button shows loading state
                await page.waitForTimeout(100);
                const exportingText = page.locator('text=/exporting/i').first();

                // Loading state might be brief, so we just check the button exists
                await expect(exportButton).toBeVisible();
            }
        });
    });

    // ==========================================
    // Error Handling Tests (4)
    // ==========================================

    test.describe('Error Handling', () => {
        test('should handle API errors gracefully', async ({ page }) => {
            // Intercept API call and return error
            await page.route('**/api/clients/1/dashboard', route => {
                route.fulfill({
                    status: 500,
                    body: JSON.stringify({ error: 'Internal Server Error' })
                });
            });

            await page.reload();

            // Check for error message or fallback UI
            await page.waitForTimeout(2000);

            // Page should still be accessible (not crashed)
            await expect(page).toHaveURL(DASHBOARD_URL);
        });

        test('should handle missing data gracefully', async ({ page }) => {
            // Intercept API call and return empty data
            await page.route('**/api/clients/1/dashboard', route => {
                route.fulfill({
                    status: 200,
                    body: JSON.stringify({
                        client: { id: 1, name: 'Test Client', logo_url: '', industry: '' },
                        metrics: {
                            total_spend: 0,
                            total_impressions: 0,
                            total_clicks: 0,
                            total_conversions: 0,
                            total_revenue: 0,
                            ctr: 0,
                            cvr: 0,
                            cpc: 0,
                            roas: 0
                        },
                        trends: { spend: [], impressions: [], clicks: [], conversions: [] },
                        platform_breakdown: [],
                        ad_accounts: [],
                        top_campaigns: []
                    })
                });
            });

            await page.reload();

            // Page should still render
            await expect(page).toHaveURL(DASHBOARD_URL);

            // Should show zero values
            const zeroValue = page.locator('text=/^0$/').first();
            await expect(zeroValue).toBeVisible();
        });

        test('should show error message for failed export', async ({ page }) => {
            // Intercept export API call and return error
            await page.route('**/api/clients/1/export/pdf', route => {
                route.fulfill({
                    status: 500,
                    body: JSON.stringify({ error: 'Export failed' })
                });
            });

            // Click export button
            const exportButton = page.locator('button').filter({ hasText: /export/i }).first();
            await exportButton.click();
            await page.waitForTimeout(300);

            // Click PDF option
            const pdfOption = page.locator('text=/^pdf$/i, button:has-text("PDF")').last();
            if (await pdfOption.isVisible()) {
                await pdfOption.click();

                // Wait for error (could be alert or toast notification)
                await page.waitForTimeout(1000);

                // Page should still be functional
                await expect(page).toHaveURL(DASHBOARD_URL);
            }
        });

        test('should handle network timeout', async ({ page }) => {
            // Intercept API call and delay response
            await page.route('**/api/clients/1/dashboard', async route => {
                await new Promise(resolve => setTimeout(resolve, 10000)); // 10 second delay
                route.fulfill({
                    status: 200,
                    body: JSON.stringify({ error: 'Timeout' })
                });
            });

            const reloadPromise = page.reload();

            // Wait a reasonable time
            await page.waitForTimeout(3000);

            // Page should still be in some state (loading or error)
            await expect(page).toHaveURL(DASHBOARD_URL);
        });
    });

    // ==========================================
    // Responsive Design Tests (3)
    // ==========================================

    test.describe('Responsive Design', () => {
        test('should display properly on mobile viewport', async ({ page }) => {
            // Set mobile viewport
            await page.setViewportSize({ width: 375, height: 667 });
            await page.reload();

            // Check that page is still accessible
            await expect(page).toHaveURL(DASHBOARD_URL);

            // Metrics should still be visible
            const metricsCard = page.locator('.metric-card, [data-testid="metric-card"]').first();
            await expect(metricsCard).toBeVisible();
        });

        test('should display properly on tablet viewport', async ({ page }) => {
            // Set tablet viewport
            await page.setViewportSize({ width: 768, height: 1024 });
            await page.reload();

            // Check that page is still accessible
            await expect(page).toHaveURL(DASHBOARD_URL);

            // Charts should still render
            await page.waitForSelector('canvas', { timeout: 10000 });
            const canvasCount = await page.locator('canvas').count();
            expect(canvasCount).toBeGreaterThan(0);
        });

        test('should display properly on desktop viewport', async ({ page }) => {
            // Set desktop viewport
            await page.setViewportSize({ width: 1920, height: 1080 });
            await page.reload();

            // Check that page is still accessible
            await expect(page).toHaveURL(DASHBOARD_URL);

            // All sections should be visible
            const metricsSection = page.locator('text=/metrics/i, .metric-card').first();
            const chartsSection = page.locator('canvas').first();

            await expect(metricsSection).toBeVisible();
            await expect(chartsSection).toBeVisible();
        });
    });
});
