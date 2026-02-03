import { test, expect } from '@playwright/test';

test('diagnose ad-accounts overlay issue', async ({ page }) => {
  console.log('=== AD-ACCOUNTS OVERLAY DEBUG TEST ===\n');

  // Track API calls
  const apiCalls: any[] = [];
  page.on('response', response => {
    if (response.url().includes('/api/')) {
      apiCalls.push({
        url: response.url(),
        status: response.status(),
        statusText: response.statusText()
      });
    }
  });

  // Track console errors
  const consoleErrors: string[] = [];
  page.on('console', msg => {
    if (msg.type() === 'error') {
      consoleErrors.push(msg.text());
    }
  });

  console.log('Step 1: Navigating to ad-accounts page...');
  const response = await page.goto('https://rb-benchmarks.redbananas.com/ad-accounts', {
    waitUntil: 'networkidle'
  });
  console.log(`Response status: ${response?.status()}\n`);

  // Wait for page to settle
  await page.waitForTimeout(3000);

  console.log('Step 2: Checking for overlays...\n');

  // Check for bulk modal overlay
  const bulkModalOverlay = page.locator('div.fixed.inset-0.bg-gray-600.bg-opacity-50');
  const bulkModalVisible = await bulkModalOverlay.isVisible().catch(() => false);
  console.log(`Bulk Modal Overlay visible: ${bulkModalVisible}`);

  if (bulkModalVisible) {
    console.log('  ⚠️  FOUND: Bulk update modal overlay is showing!');
    const bulkModalContent = page.locator('div:has-text("Bulk Update")');
    const modalTextVisible = await bulkModalContent.isVisible().catch(() => false);
    console.log(`  Bulk modal content visible: ${modalTextVisible}`);
  }

  // Check for sidebar backdrop (mobile overlay)
  const sidebarBackdrop = page.locator('div.fixed.inset-0.bg-black.bg-opacity-50');
  const sidebarVisible = await sidebarBackdrop.isVisible().catch(() => false);
  console.log(`Sidebar Backdrop visible: ${sidebarVisible}`);

  if (sidebarVisible) {
    console.log('  ⚠️  FOUND: Sidebar backdrop overlay is showing!');
  }

  // Check for loading spinner
  const loadingSpinner = page.locator('div.animate-spin');
  const loadingVisible = await loadingSpinner.isVisible().catch(() => false);
  console.log(`Loading spinner visible: ${loadingVisible}`);

  if (loadingVisible) {
    console.log('  ⚠️  FOUND: Loading spinner is still showing!');
  }

  console.log('\nStep 3: Checking z-index stack...\n');

  // Get all elements with high z-index
  const highZIndexElements = await page.evaluate(() => {
    const elements = Array.from(document.querySelectorAll('*'));
    return elements
      .map(el => {
        const zIndex = window.getComputedStyle(el).zIndex;
        const position = window.getComputedStyle(el).position;
        const opacity = window.getComputedStyle(el).opacity;
        const display = window.getComputedStyle(el).display;
        return {
          tag: el.tagName,
          class: el.className,
          zIndex,
          position,
          opacity,
          display,
          isVisible: el.checkVisibility ? el.checkVisibility() : true
        };
      })
      .filter(el =>
        parseInt(el.zIndex) >= 40 &&
        el.position === 'fixed' &&
        el.display !== 'none'
      )
      .sort((a, b) => parseInt(b.zIndex) - parseInt(a.zIndex));
  });

  console.log('Elements with z-index >= 40:');
  highZIndexElements.forEach((el, i) => {
    console.log(`  ${i + 1}. ${el.tag} (z-index: ${el.zIndex}, opacity: ${el.opacity}, visible: ${el.isVisible})`);
    console.log(`     class: "${el.class}"`);
  });

  console.log('\nStep 4: Checking Vue component state...\n');

  // Check Vue data state
  const vueState = await page.evaluate(() => {
    // Try to access Vue devtools or component instances
    const app = (window as any).__VUE__;
    return {
      hasVue: !!app,
      windowKeys: Object.keys(window).filter(k => k.includes('vue') || k.includes('Vue'))
    };
  });

  console.log('Vue app detected:', vueState.hasVue);
  console.log('Vue-related window keys:', vueState.windowKeys);

  console.log('\nStep 5: Checking for clickability issues...\n');

  // Try to click on the page title
  const pageTitle = page.locator('h2:has-text("Ad Accounts")').first();
  const titleVisible = await pageTitle.isVisible().catch(() => false);
  console.log(`Page title visible: ${titleVisible}`);

  if (titleVisible) {
    const canClick = await pageTitle.isEnabled().catch(() => false);
    console.log(`Page title clickable: ${canClick}`);

    // Check if something is covering it
    const box = await pageTitle.boundingBox();
    if (box) {
      const elementAtPoint = await page.evaluate(({x, y}) => {
        const el = document.elementFromPoint(x, y);
        return {
          tag: el?.tagName,
          class: el?.className,
          id: el?.id,
          text: el?.textContent?.substring(0, 50)
        };
      }, { x: box.x + box.width / 2, y: box.y + box.height / 2 });

      console.log('Element at title center point:', elementAtPoint);
    }
  }

  console.log('\nStep 6: API Calls Summary...\n');

  apiCalls.forEach(call => {
    const url = new URL(call.url);
    console.log(`  ${call.status} - ${url.pathname}${url.search}`);
  });

  if (consoleErrors.length > 0) {
    console.log('\n⚠️  Console Errors:');
    consoleErrors.forEach(err => console.log(`  - ${err}`));
  }

  console.log('\nStep 7: Taking diagnostic screenshot...\n');
  await page.screenshot({
    path: '/tmp/ad-accounts-overlay-debug.png',
    fullPage: true
  });
  console.log('Screenshot saved: /tmp/ad-accounts-overlay-debug.png');

  console.log('\n=== DIAGNOSTIC COMPLETE ===\n');

  // Determine root cause
  if (bulkModalVisible) {
    console.log('ROOT CAUSE: Bulk modal overlay is stuck showing');
    console.log('FIX: Check showBulkModal ref initialization in AdAccounts.vue');
  } else if (sidebarVisible) {
    console.log('ROOT CAUSE: Sidebar backdrop is stuck showing');
    console.log('FIX: Check sidebarOpen ref initialization in AppLayout.vue');
  } else if (loadingVisible) {
    console.log('ROOT CAUSE: Loading state is stuck');
    console.log('FIX: Add timeout or ensure loading=false in error handlers');
  } else if (highZIndexElements.length > 0) {
    console.log('ROOT CAUSE: Other high z-index element blocking interaction');
    console.log('FIX: Check z-index conflicts');
  } else {
    console.log('✅ No overlay detected - page may be functioning correctly');
  }
});
