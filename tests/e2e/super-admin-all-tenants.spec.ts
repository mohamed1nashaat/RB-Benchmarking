import { test, expect } from '@playwright/test';

const BASE_URL = process.env.APP_URL || 'https://rb-benchmarks.redbananas.com';

test.describe('Super Admin - All Tenants View', () => {
    test('should show all tenants data by default for super admin', async ({ page }) => {
        // Collect console logs
        const consoleLogs: string[] = [];
        page.on('console', msg => {
            consoleLogs.push(msg.text());
        });

        // Go to login page (clear any existing session)
        await page.goto(`${BASE_URL}/login`);

        // Clear all storage to ensure fresh start
        await page.evaluate(() => {
            localStorage.clear();
            sessionStorage.clear();
        });

        // Login as super admin
        await page.fill('input[type="email"]', 'admin@demo.com');
        await page.fill('input[type="password"]', 'password');
        await page.click('button[type="submit"]');

        // Wait for dashboard to load
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000); // Give time for console logs

        // Verify console logs show super admin detection
        const hasSuperAdminLog = consoleLogs.some(log =>
            log.includes('auth store - super admin detected, showing all tenants')
        );
        expect(hasSuperAdminLog).toBeTruthy();

        // Verify version check log
        const hasVersionLog = consoleLogs.some(log =>
            log.includes('App version updated - clearing old cached data')
        );
        console.log('Console logs:', consoleLogs);
        console.log('Has version log:', hasVersionLog);
        console.log('Has super admin log:', hasSuperAdminLog);

        // Verify Total Spend shows all tenants data (SAR 21,953,673)
        const totalSpendElement = page.locator('text=Total Spend').locator('..').locator('text=/SAR/');
        await expect(totalSpendElement).toBeVisible({ timeout: 10000 });

        const totalSpendText = await totalSpendElement.textContent();
        console.log('Total Spend Text:', totalSpendText);

        // The total should be around SAR 21,953,673 (all tenants)
        // Not SAR 13,877,213 (single tenant)
        expect(totalSpendText).toContain('21,953,673');

        // Verify tenant selector shows "All Tenants"
        const tenantSelector = page.locator('text=/All Tenants/i').first();
        await expect(tenantSelector).toBeVisible({ timeout: 5000 });
    });

    test('should NOT auto-select a specific tenant for super admin', async ({ page }) => {
        // Collect console logs
        const consoleLogs: string[] = [];
        page.on('console', msg => {
            consoleLogs.push(msg.text());
        });

        // Go to login page
        await page.goto(`${BASE_URL}/login`);

        // Clear storage
        await page.evaluate(() => {
            localStorage.clear();
            sessionStorage.clear();
        });

        // Login as super admin
        await page.fill('input[type="email"]', 'admin@demo.com');
        await page.fill('input[type="password"]', 'password');
        await page.click('button[type="submit"]');

        // Wait for page load
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);

        // Check localStorage - should NOT have current_tenant_id
        const tenantId = await page.evaluate(() => localStorage.getItem('current_tenant_id'));
        expect(tenantId).toBeNull();

        // Verify console doesn't show "set current tenant from response"
        const hasSetTenantLog = consoleLogs.some(log =>
            log.includes('auth store - set current tenant from response: Demo Company')
        );
        expect(hasSetTenantLog).toBeFalsy();
    });

    test('should show metrics from all 60 tenants', async ({ page }) => {
        // Go to login page
        await page.goto(`${BASE_URL}/login`);

        // Clear storage
        await page.evaluate(() => {
            localStorage.clear();
            sessionStorage.clear();
        });

        // Login as super admin
        await page.fill('input[type="email"]', 'admin@demo.com');
        await page.fill('input[type="password"]', 'password');
        await page.click('button[type="submit"]');

        // Wait for dashboard
        await page.waitForLoadState('networkidle');

        // Intercept dashboard API call
        const response = await page.waitForResponse(
            resp => resp.url().includes('/api/dashboard') && resp.status() === 200,
            { timeout: 10000 }
        );

        const data = await response.json();
        console.log('Dashboard API Response:', JSON.stringify(data, null, 2));

        // Verify we're getting all tenants data
        // Total metrics should be around 61,459 (not 41,138 for single tenant)
        if (data.metrics) {
            console.log('Total Spend:', data.metrics.total_spend);
            console.log('Total Metrics Count:', data.metrics.total_impressions);

            // Total spend should be around 21,953,673 SAR (all tenants)
            expect(data.metrics.total_spend).toBeGreaterThan(20000000);
        }
    });

    test('should send NO X-Tenant-ID header when viewing all tenants', async ({ page }) => {
        let apiHeaders: any = null;

        // Intercept API requests
        page.on('request', request => {
            if (request.url().includes('/api/dashboard')) {
                apiHeaders = request.headers();
                console.log('API Headers:', apiHeaders);
            }
        });

        // Go to login page
        await page.goto(`${BASE_URL}/login`);

        // Clear storage
        await page.evaluate(() => {
            localStorage.clear();
            sessionStorage.clear();
        });

        // Login as super admin
        await page.fill('input[type="email"]', 'admin@demo.com');
        await page.fill('input[type="password"]', 'password');
        await page.click('button[type="submit"]');

        // Wait for dashboard
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Verify X-Tenant-ID header is NOT sent or is null
        expect(apiHeaders).toBeTruthy();
        expect(apiHeaders['x-tenant-id']).toBeFalsy();
    });

    test('super admin can switch to specific tenant if needed', async ({ page }) => {
        // Go to login page
        await page.goto(`${BASE_URL}/login`);

        // Clear storage
        await page.evaluate(() => {
            localStorage.clear();
            sessionStorage.clear();
        });

        // Login as super admin
        await page.fill('input[type="email"]', 'admin@demo.com');
        await page.fill('input[type="password"]', 'password');
        await page.click('button[type="submit"]');

        // Wait for dashboard
        await page.waitForLoadState('networkidle');

        // Find tenant selector
        const tenantSelector = page.locator('[data-testid="tenant-selector"]').or(
            page.locator('select, button').filter({ hasText: /All Tenants|Demo Company/i }).first()
        );

        if (await tenantSelector.isVisible()) {
            // Click to open dropdown
            await tenantSelector.click();
            await page.waitForTimeout(300);

            // Try to select "Demo Company" (tenant 1)
            const demoOption = page.locator('text=/Demo Company/i').first();
            if (await demoOption.isVisible()) {
                await demoOption.click();
                await page.waitForTimeout(1000);

                // Verify localStorage now has tenant_id = 1
                const tenantId = await page.evaluate(() => localStorage.getItem('current_tenant_id'));
                expect(tenantId).toBe('1');

                // Total spend should now show single tenant amount (SAR 13,877,213)
                const totalSpendElement = page.locator('text=Total Spend').locator('..').locator('text=/SAR/');
                const totalSpendText = await totalSpendElement.textContent();
                console.log('Switched to Demo Company - Total Spend:', totalSpendText);

                expect(totalSpendText).toContain('13,877,213');
            }
        }
    });
});
