import { test, expect } from '@playwright/test';

test('super admin can see verification buttons on ad accounts page', async ({ page }) => {
  // Clear all storage first to ensure fresh login
  await page.goto('https://rb-benchmarks.redbananas.com/');
  await page.evaluate(() => {
    localStorage.clear();
    sessionStorage.clear();
  });

  // Login as super admin
  await page.goto('https://rb-benchmarks.redbananas.com/login');
  await page.fill('input[type="email"]', 'admin@demo.com');
  await page.fill('input[type="password"]', 'password');
  await page.click('button[type="submit"]');

  // Wait for successful login
  await page.waitForLoadState('networkidle');

  // Navigate to ad accounts page
  await page.goto('https://rb-benchmarks.redbananas.com/ad-accounts');
  await page.waitForLoadState('networkidle');

  // Wait for the page to load
  await page.waitForSelector('table');

  // Capture ALL console logs
  const logs: string[] = [];
  page.on('console', msg => {
    logs.push(msg.text());
  });

  // Wait a moment for page to fully load
  await page.waitForTimeout(3000);

  // Print relevant debug logs
  const relevantLogs = logs.filter(log =>
    log.includes('isAdmin') || log.includes('isSuperAdmin') || log.includes('fetchUser response')
  );
  console.log('=== Relevant Console Logs ===');
  relevantLogs.forEach(log => console.log(log));

  // Check if verification status column exists
  const verificationHeader = page.locator('th:has-text("Verification")');
  await expect(verificationHeader).toBeVisible();

  // Check if at least one approve button exists (green checkmark)
  const approveButtons = page.locator('button[title="Approve"]');
  const approveCount = await approveButtons.count();
  console.log(`Found ${approveCount} approve buttons`);

  if (approveCount > 0) {
    await expect(approveButtons.first()).toBeVisible();
    console.log('✓ Approve buttons are visible');
  }

  // Check if at least one reject button exists (red X)
  const rejectButtons = page.locator('button[title="Reject"]');
  const rejectCount = await rejectButtons.count();
  console.log(`Found ${rejectCount} reject buttons`);

  if (rejectCount > 0) {
    await expect(rejectButtons.first()).toBeVisible();
    console.log('✓ Reject buttons are visible');
  }

  // Check bulk buttons when accounts are selected
  const firstCheckbox = page.locator('input[type="checkbox"]').nth(1); // Skip "select all" checkbox
  await firstCheckbox.click();

  // Should see bulk approve/reject buttons
  const bulkApprove = page.locator('button:has-text("Approve")').first();
  const bulkReject = page.locator('button:has-text("Reject")').first();

  await expect(bulkApprove).toBeVisible();
  await expect(bulkReject).toBeVisible();

  console.log('✓ Bulk approve/reject buttons are visible');

  // Take a screenshot for verification
  await page.screenshot({ path: 'verification-buttons-test.png', fullPage: true });
});
