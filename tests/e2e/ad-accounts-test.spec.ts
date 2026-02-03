import { test, expect } from '@playwright/test';

test('test ad-accounts page', async ({ page }) => {
  // Listen for API calls
  const apiCalls: any[] = [];
  page.on('response', response => {
    if (response.url().includes('/api/')) {
      apiCalls.push({
        url: response.url(),
        status: response.status()
      });
    }
  });

  console.log('Navigating to ad-accounts page...');
  const response = await page.goto('https://rb-benchmarks.redbananas.com/ad-accounts');
  console.log('Response status:', response?.status());

  await page.waitForTimeout(3000);

  const title = await page.title();
  console.log('Page title:', title);

  // Check for error messages in the DOM
  const errorElements = await page.locator('[role="alert"], .error, .alert-error, [class*="error"]').all();
  console.log('Found error elements:', errorElements.length);

  for (const el of errorElements) {
    const text = await el.textContent();
    if (text && text.trim()) {
      console.log('Error message:', text.trim());
    }
  }

  // Log all API calls
  console.log('\nAPI Calls made:');
  apiCalls.forEach(call => {
    console.log(`  ${call.url} - Status: ${call.status}`);
  });

  // Get body text
  const bodyText = await page.locator('body').textContent();
  if (bodyText?.includes('500') || bodyText?.includes('Error')) {
    console.log('\nBody contains error text');
  }

  // Take a screenshot
  await page.screenshot({ path: '/tmp/ad-accounts-screenshot.png', fullPage: true });
  console.log('\nScreenshot saved to /tmp/ad-accounts-screenshot.png');
});
