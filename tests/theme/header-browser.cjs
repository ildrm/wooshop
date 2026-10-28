/* Local-only real browser regression for WooCommerce's automatic Navigation siblings. */
const { chromium, expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');

(async () => {
    const output = path.resolve('artifacts/theme-header-regression');
    fs.mkdirSync(output, { recursive: true });
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    const results = [];
    try {
        for (const [site, port] of [['outdoor', 18880], ['smart-home', 18881]]) {
            for (const [view, width, height] of [['desktop', 1440, 1000], ['mobile', 390, 844]]) {
                const context = await browser.newContext({ viewport: { width, height } });
                try {
                    const page = await context.newPage();
                    const response = await page.goto(`http://localhost:${port}/`, { waitUntil: 'networkidle' });
                    expect(response.status()).toBe(200);
                    const header = page.locator('header');
                    await expect(header.locator('.wooshop-account')).toHaveCount(1);
                    await expect(header.getByRole('link', { name: 'Sign in', exact: true })).toBeVisible();
                    await expect(header.getByRole('link', { name: 'Cart', exact: true })).toBeVisible();
                    await expect(header.locator('.wooshop-utilities .wc-block-mini-cart')).toHaveCount(1);
                    await expect(header.locator('.wooshop-primary-nav .wp-block-woocommerce-customer-account')).toHaveCount(0);
                    await expect(header.locator('.wooshop-primary-nav .wc-block-mini-cart')).toHaveCount(0);
                    const navHeight = await header.locator('.wooshop-header-nav').evaluate(element => element.getBoundingClientRect().height);
                    if (view === 'desktop') expect(navHeight).toBeLessThanOrEqual(64);
                    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1);
                    expect(overflow).toBe(false);
                    const summary = header.locator('details.wooshop-categories > summary');
                    await summary.click();
                    await expect(header.locator('details.wooshop-categories')).toHaveJSProperty('open', true);
                    await page.keyboard.press('Escape');
                    await expect(header.locator('details.wooshop-categories')).toHaveJSProperty('open', false);
                    await expect(summary).toBeFocused();
                    await page.screenshot({ path: path.join(output, `${site}-${view}.png`), fullPage: false });
                    results.push({ site, view, result: 'PASS', navigation_height: navHeight,
                        duplicate_automatic_account_and_cart: false, intentional_utility_links: 'Sign in and full Cart',
                        native_mini_cart_count: 1, category_escape_focus_restored: true });
                } finally { await context.close(); }
            }
        }
    } finally { await browser.close(); }
    const result = { result: 'PASS', cases: results, limitation: 'Header only; no full mini-cart state, design approval, full keyboard/AT conformance or purchase claim.' };
    fs.writeFileSync(path.join(output, 'results.json'), JSON.stringify(result, null, 2) + '\n');
    console.log(JSON.stringify(result));
})().catch(error => { console.error(error.message); process.exitCode = 1; });
