import { test, expect } from '@playwright/test';

test('comprehensive ad-accounts clickability test', async ({ page }) => {
  console.log('=== AD-ACCOUNTS CLICKABILITY DIAGNOSTIC TEST ===\n');

  // Track console errors
  const consoleErrors: string[] = [];
  page.on('console', msg => {
    if (msg.type() === 'error') {
      consoleErrors.push(msg.text());
    }
  });

  console.log('Step 1: Login and navigate to ad-accounts...');
  await page.goto('https://rb-benchmarks.redbananas.com/login');
  await page.fill('input[type="email"]', 'admin@demo.com');
  await page.fill('input[type="password"]', 'password');
  await page.click('button:has-text("Login")');
  await page.waitForTimeout(2000);

  await page.goto('https://rb-benchmarks.redbananas.com/ad-accounts', {
    waitUntil: 'networkidle'
  });
  await page.waitForTimeout(3000);

  console.log('\nStep 2: Checking for blocking overlays...\n');

  // Check for any overlays
  const overlays = await page.evaluate(() => {
    const elements = Array.from(document.querySelectorAll('*'));
    return elements
      .filter(el => {
        const style = window.getComputedStyle(el);
        const rect = el.getBoundingClientRect();
        return (
          (style.position === 'fixed' || style.position === 'absolute') &&
          rect.width > 100 &&
          rect.height > 100 &&
          parseInt(style.zIndex) >= 10
        );
      })
      .map(el => ({
        tag: el.tagName,
        class: el.className,
        id: el.id,
        zIndex: window.getComputedStyle(el).zIndex,
        position: window.getComputedStyle(el).position,
        opacity: window.getComputedStyle(el).opacity,
        pointerEvents: window.getComputedStyle(el).pointerEvents,
        dimensions: {
          width: el.getBoundingClientRect().width,
          height: el.getBoundingClientRect().height,
          top: el.getBoundingClientRect().top,
          left: el.getBoundingClientRect().left
        }
      }));
  });

  console.log(`Found ${overlays.length} potential overlay elements:`);
  overlays.forEach((overlay, i) => {
    console.log(`  ${i + 1}. ${overlay.tag}.${overlay.class || 'no-class'}`);
    console.log(`     z-index: ${overlay.zIndex}, position: ${overlay.position}`);
    console.log(`     opacity: ${overlay.opacity}, pointer-events: ${overlay.pointerEvents}`);
    console.log(`     size: ${overlay.dimensions.width}x${overlay.dimensions.height}`);
  });

  console.log('\nStep 3: Testing Header Elements...\n');

  // Test Language Switcher
  const langSwitcher = page.locator('button:has-text("English"), button:has-text("العربية")').first();
  const langExists = await langSwitcher.count() > 0;
  console.log(`Language Switcher exists: ${langExists}`);

  if (langExists) {
    const langClickable = await langSwitcher.isEnabled();
    const langVisible = await langSwitcher.isVisible();
    console.log(`  Visible: ${langVisible}, Enabled: ${langClickable}`);

    try {
      await langSwitcher.click({ timeout: 3000 });
      console.log('  ✅ Language Switcher clicked successfully');
      await page.waitForTimeout(500);
    } catch (error) {
      console.log(`  ❌ Language Switcher click FAILED: ${error.message}`);

      // Check what's covering it
      const box = await langSwitcher.boundingBox();
      if (box) {
        const elementAtPoint = await page.evaluate(({x, y}) => {
          const el = document.elementFromPoint(x + 10, y + 10);
          return {
            tag: el?.tagName,
            class: el?.className,
            id: el?.id,
            zIndex: el ? window.getComputedStyle(el).zIndex : 'N/A',
            pointerEvents: el ? window.getComputedStyle(el).pointerEvents : 'N/A'
          };
        }, { x: box.x, y: box.y });
        console.log('  Element covering Language Switcher:', elementAtPoint);
      }
    }
  }

  // Test User Menu
  const userMenu = page.locator('button:has-text("Demo Admin"), [aria-label*="user"], [class*="user"]').first();
  const userExists = await userMenu.count() > 0;
  console.log(`\nUser Menu exists: ${userExists}`);

  if (userExists) {
    const userClickable = await userMenu.isEnabled();
    const userVisible = await userMenu.isVisible();
    console.log(`  Visible: ${userVisible}, Enabled: ${userClickable}`);

    try {
      await userMenu.click({ timeout: 3000 });
      console.log('  ✅ User Menu clicked successfully');
      await page.waitForTimeout(500);
    } catch (error) {
      console.log(`  ❌ User Menu click FAILED: ${error.message}`);
    }
  }

  console.log('\nStep 4: Testing Filter Dropdowns...\n');

  // Test Platform Filter
  const platformFilter = page.locator('select#platform-filter, select:has-text("All Platforms")').first();
  const platformExists = await platformFilter.count() > 0;
  console.log(`Platform Filter exists: ${platformExists}`);

  if (platformExists) {
    try {
      await platformFilter.click({ timeout: 3000 });
      console.log('  ✅ Platform Filter clicked successfully');
      await platformFilter.selectOption('facebook');
      console.log('  ✅ Platform Filter selection worked');
      await page.waitForTimeout(500);
    } catch (error) {
      console.log(`  ❌ Platform Filter click FAILED: ${error.message}`);
    }
  }

  // Test Search Input
  const searchInput = page.locator('input#search, input[placeholder*="Search"]').first();
  const searchExists = await searchInput.count() > 0;
  console.log(`\nSearch Input exists: ${searchExists}`);

  if (searchExists) {
    try {
      await searchInput.click({ timeout: 3000 });
      await searchInput.fill('test');
      const value = await searchInput.inputValue();
      console.log(`  ✅ Search Input worked, value: "${value}"`);
    } catch (error) {
      console.log(`  ❌ Search Input click FAILED: ${error.message}`);
    }
  }

  console.log('\nStep 5: Testing Action Buttons...\n');

  // Test Refresh Button
  const refreshBtn = page.locator('button:has-text("Refresh"), button:has-text("refresh")').first();
  const refreshExists = await refreshBtn.count() > 0;
  console.log(`Refresh Button exists: ${refreshExists}`);

  if (refreshExists) {
    try {
      await refreshBtn.click({ timeout: 3000 });
      console.log('  ✅ Refresh Button clicked successfully');
      await page.waitForTimeout(500);
    } catch (error) {
      console.log(`  ❌ Refresh Button click FAILED: ${error.message}`);
    }
  }

  console.log('\nStep 6: Testing Table Interactions...\n');

  // Test Select All Checkbox
  const selectAllCheckbox = page.locator('input[type="checkbox"]').first();
  const checkboxExists = await selectAllCheckbox.count() > 0;
  console.log(`Checkboxes exist: ${checkboxExists}`);

  if (checkboxExists) {
    try {
      await selectAllCheckbox.click({ timeout: 3000 });
      const isChecked = await selectAllCheckbox.isChecked();
      console.log(`  ✅ Checkbox clicked, checked state: ${isChecked}`);
    } catch (error) {
      console.log(`  ❌ Checkbox click FAILED: ${error.message}`);
    }
  }

  // Test Account Link
  const accountLink = page.locator('table a[href*="/ad-accounts/"]').first();
  const linkExists = await accountLink.count() > 0;
  console.log(`\nAccount Links exist: ${linkExists}`);

  if (linkExists) {
    try {
      const href = await accountLink.getAttribute('href');
      console.log(`  Link href: ${href}`);
      await accountLink.click({ timeout: 3000 });
      console.log('  ✅ Account Link clicked successfully');
      await page.waitForTimeout(1000);
      // Go back
      await page.goBack();
      await page.waitForTimeout(1000);
    } catch (error) {
      console.log(`  ❌ Account Link click FAILED: ${error.message}`);
    }
  }

  console.log('\nStep 7: Testing Table Dropdowns...\n');

  // Test Industry Dropdown in table
  const industryDropdown = page.locator('table select').first();
  const dropdownExists = await industryDropdown.count() > 0;
  console.log(`Table Dropdowns exist: ${dropdownExists}`);

  if (dropdownExists) {
    try {
      await industryDropdown.click({ timeout: 3000 });
      console.log('  ✅ Table Dropdown clicked successfully');
    } catch (error) {
      console.log(`  ❌ Table Dropdown click FAILED: ${error.message}`);
    }
  }

  console.log('\nStep 8: Testing Pagination...\n');

  // Test pagination button
  const nextPageBtn = page.locator('button:has-text("Next"), button[aria-label="Next"]').first();
  const paginationExists = await nextPageBtn.count() > 0;
  console.log(`Pagination exists: ${paginationExists}`);

  if (paginationExists) {
    try {
      const isDisabled = await nextPageBtn.isDisabled();
      console.log(`  Next button disabled: ${isDisabled}`);
      if (!isDisabled) {
        await nextPageBtn.click({ timeout: 3000 });
        console.log('  ✅ Pagination clicked successfully');
      }
    } catch (error) {
      console.log(`  ❌ Pagination click FAILED: ${error.message}`);
    }
  }

  console.log('\nStep 9: Global Clickability Check...\n');

  // Try clicking at various points on the page
  const testPoints = [
    { x: 100, y: 100, label: 'Top-left' },
    { x: 400, y: 100, label: 'Top-center' },
    { x: 400, y: 400, label: 'Center' },
    { x: 100, y: 700, label: 'Bottom-left' }
  ];

  for (const point of testPoints) {
    const elementAtPoint = await page.evaluate(({x, y}) => {
      const el = document.elementFromPoint(x, y);
      return {
        tag: el?.tagName,
        class: el?.className,
        id: el?.id,
        text: el?.textContent?.substring(0, 30),
        zIndex: el ? window.getComputedStyle(el).zIndex : 'N/A',
        pointerEvents: el ? window.getComputedStyle(el).pointerEvents : 'N/A',
        cursor: el ? window.getComputedStyle(el).cursor : 'N/A'
      };
    }, point);

    console.log(`${point.label} (${point.x}, ${point.y}):`, elementAtPoint);
  }

  if (consoleErrors.length > 0) {
    console.log('\n⚠️  Console Errors Detected:');
    consoleErrors.forEach(err => console.log(`  - ${err}`));
  }

  console.log('\nStep 10: Taking diagnostic screenshot...\n');
  await page.screenshot({
    path: '/tmp/ad-accounts-clickability.png',
    fullPage: true
  });
  console.log('Screenshot saved: /tmp/ad-accounts-clickability.png');

  console.log('\n=== DIAGNOSTIC COMPLETE ===\n');

  // Summary
  const clickableElements = [];
  const nonClickableElements = [];

  if (langExists) {
    try {
      await langSwitcher.click({ timeout: 1000 });
      clickableElements.push('Language Switcher');
    } catch {
      nonClickableElements.push('Language Switcher');
    }
  }

  if (searchExists) {
    try {
      await searchInput.click({ timeout: 1000 });
      clickableElements.push('Search Input');
    } catch {
      nonClickableElements.push('Search Input');
    }
  }

  console.log(`\n✅ Clickable: ${clickableElements.join(', ') || 'None'}`);
  console.log(`❌ Not Clickable: ${nonClickableElements.join(', ') || 'None'}`);

  if (overlays.length > 0) {
    console.log('\n⚠️  POTENTIAL ISSUE: Overlays detected that may be blocking clicks');
  }
});
