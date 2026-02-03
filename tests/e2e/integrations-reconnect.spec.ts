import { test, expect } from '@playwright/test';

test.describe('Integrations Reconnect Button Test', () => {
  test.beforeEach(async ({ page }) => {
    // Login first
    await page.goto('https://rb-benchmarks.redbananas.com/login');
    await page.fill('input[type="email"]', 'admin@demo.com');
    await page.fill('input[type="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
  });

  test('should show reconnect button when tokens are expired', async ({ page }) => {
    // Navigate to integrations page
    await page.goto('https://rb-benchmarks.redbananas.com/integrations');
    await page.waitForLoadState('networkidle');

    // Wait for the page to load
    await page.waitForTimeout(3000);

    // Check if the token-status API returns data
    const tokenStatusResponse = await page.evaluate(async () => {
      const response = await fetch('/api/integrations/token-status');
      return response.json();
    });

    console.log('Token status response:', JSON.stringify(tokenStatusResponse, null, 2));

    // Check if reconnect button is visible (if there are expired tokens)
    const reconnectButton = page.locator('button:has-text("Reconnect")');
    const buttonCount = await reconnectButton.count();

    console.log('Reconnect button count:', buttonCount);

    // Take a screenshot
    await page.screenshot({ path: '/tmp/integrations-page.png', fullPage: true });

    if (tokenStatusResponse.needs_reconnection > 0) {
      console.log('Expected button to be visible - needs_reconnection: ' + tokenStatusResponse.needs_reconnection);
      expect(buttonCount).toBeGreaterThan(0);
    } else {
      console.log('No tokens need reconnection, button may not be visible');
    }
  });

  test('should open reconnect modal when clicking reconnect button', async ({ page }) => {
    await page.goto('https://rb-benchmarks.redbananas.com/integrations');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    // Take initial screenshot
    await page.screenshot({ path: '/tmp/integrations-before-click.png', fullPage: true });

    // Try to find the reconnect button in header
    const reconnectButton = page.locator('button:has-text("Reconnect")').first();
    const buttonVisible = await reconnectButton.isVisible().catch(() => false);

    console.log('Reconnect button visible:', buttonVisible);

    if (buttonVisible) {
      await reconnectButton.click();
      await page.waitForTimeout(1000);

      // Check if modal opened
      const modalTitle = page.locator('h3:has-text("Reconnect Platforms")');
      const isModalVisible = await modalTitle.isVisible().catch(() => false);

      console.log('Modal visible:', isModalVisible);
      await page.screenshot({ path: '/tmp/reconnect-modal.png', fullPage: true });

      expect(isModalVisible).toBeTruthy();
    } else {
      console.log('No reconnect button visible - checking why...');

      // Debug: Check the page content
      const debugInfo = await page.evaluate(() => {
        return {
          url: window.location.href,
          bodyText: document.body.innerText.substring(0, 500)
        };
      });
      console.log('Debug info:', JSON.stringify(debugInfo, null, 2));
    }
  });

  test('should verify token-status API works', async ({ page }) => {
    await page.goto('https://rb-benchmarks.redbananas.com/integrations');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Call the API directly
    const response = await page.evaluate(async () => {
      try {
        const res = await fetch('/api/integrations/token-status');
        const data = await res.json();
        return { status: res.status, data };
      } catch (e) {
        return { error: String(e) };
      }
    });

    console.log('API Response:', JSON.stringify(response, null, 2));

    expect(response.status).toBe(200);
    expect(response.data).toHaveProperty('data');
    expect(response.data).toHaveProperty('needs_reconnection');
  });
});
