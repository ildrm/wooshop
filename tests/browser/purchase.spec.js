const { test, expect } = require('@playwright/test');
const { spawnSync } = require('node:child_process');
const { randomUUID } = require('node:crypto');

// No authentication artifacts, order keys, or checkout screenshots are recorded.
test.use({ screenshot: 'off', trace: 'off', video: 'off' });

function fixture(operation, run, orderId) {
  const args = ['proxy', 'python3', 'tools/dev.py', 'wp', 'outdoor', 'eval-file',
    '/workspace/tests/core/native-checkout-fixture.php', operation, run];
  if (orderId) args.push(String(orderId));
  const process = spawnSync('rtk', args, { encoding: 'utf8', timeout: 60000 });
  const line = (process.stdout || '').split('\n').find(value => value.startsWith('WOOSHOP_NATIVE_CHECKOUT '));
  const result = line ? JSON.parse(line.slice('WOOSHOP_NATIVE_CHECKOUT '.length)) : undefined;
  // Do not expose arbitrary CLI stderr or settings in a browser report.
  if (process.error || process.status !== 0 || !result) {
    throw new Error(`Guarded native fixture ${operation} failed (exit ${process.status ?? 'unknown'}; ${result?.result || 'no safe result'}).`);
  }
  return result;
}

test('native offline BACS checkout retains an unpaid synthetic order without download entitlement', async ({ page }, testInfo) => {
  testInfo.setTimeout(150000);
  const run = `native-${randomUUID()}`;
  let setup;
  let proof;
  try {
    setup = fixture('setup', run);
    expect(setup.result).toBe('SETUP');
    expect(setup.runtime_mail_blocked).toBe(true);
    const expected = setup.fixture;
    await page.goto(expected.product_url, { waitUntil: 'networkidle' });
    await page.locator('button.single_add_to_cart_button').click();
    await expect(page.getByRole('alert').filter({ hasText: /added to your cart/i })).toContainText(expected.product_name);
    await page.goto(expected.checkout_url, { waitUntil: 'networkidle' });
    await expect(page.getByRole('main')).toHaveCount(1);
    await page.getByRole('textbox', { name: /email address/i }).fill(expected.email);
    // These are synthetic contact fields; the virtual item needs no carrier.
    // Native country/state changes can clear the postcode, so select them first.
    const country = page.locator('#billing-country');
    if (await country.count()) await country.selectOption('US');
    const state = page.locator('#billing-state');
    if (await state.count()) await state.selectOption('CA');
    await page.locator('#billing-first_name').fill('Synthetic');
    await page.locator('#billing-last_name').fill('Checkout Fixture');
    await page.locator('#billing-address_1').fill('100 Synthetic Fixture Street');
    await page.locator('#billing-city').fill('Testville');
    await page.locator('#billing-postcode').fill('90001');
    const phone = page.locator('#billing-phone');
    if (await phone.count()) await phone.fill('2025550100');
    await page.locator('#billing-postcode').blur();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('#billing-postcode')).toHaveValue('90001');
    await expect(page.getByText('Synthetic local BACS — no funds transferred', { exact: true })).toBeVisible();
    await expect(page.getByRole('main')).toContainText(expected.product_name);
    await expect(page.getByRole('main')).toContainText(expected.total);
    const cart = await (await page.request.get('/wp-json/wc/store/v1/cart')).json();
    expect(cart.items.map(item => ({ id: item.id, quantity: item.quantity }))).toEqual([{ id: expected.product_id, quantity: 1 }]);
    expect(cart.needs_shipping).toBe(false);
    expect(cart.totals.currency_code).toBe(expected.currency);
    expect(cart.totals.total_price).toBe(String(Math.round(Number(expected.total) * (10 ** expected.decimals))));
    const nativeResponse = page.waitForResponse(response => response.request().method() === 'POST'
      && new URL(response.url()).pathname.endsWith('/wc/store/v1/checkout'), { timeout: 30000 });
    await page.getByRole('button', { name: /place order/i }).click();
    const response = await nativeResponse;
    const nativeOrder = await response.json();
    expect({ status: response.status(), code: nativeOrder.code || null }).toEqual({ status: 200, code: null });
    const orderId = Number(nativeOrder.order_id);
    expect(Number.isInteger(orderId) && orderId > 0).toBe(true);
    // Verify and annotate the saved native order even if its confirmation UI fails.
    proof = fixture('verify', run, orderId);
    expect(proof.result).toBe('VERIFIED');
    await testInfo.attach('native-order-safe-verification', { body: JSON.stringify(proof, null, 2), contentType: 'application/json' });
    // Only inspect the pathname: the native redirect also contains a private order key.
    await expect.poll(() => new URL(page.url()).pathname, { timeout: 30000 }).toMatch(/\/order-received\/\d+\/?$/);
    expect(Number(new URL(page.url()).pathname.match(/\/order-received\/(\d+)/)[1])).toBe(orderId);
    await page.waitForLoadState('networkidle');
    await expect(page.getByRole('main')).toContainText(/thank you|order received/i);
    await expect(page.getByRole('main')).toContainText(expected.product_name);
    await expect(page.getByRole('main')).toContainText(expected.total);
    await expect(page.locator('a[href*="download_file="]')).toHaveCount(0);
    expect(proof.status).toBe('on-hold');
    expect(proof.currency).toBe(expected.currency);
    expect(proof.total).toBe(expected.total);
    expect(Object.values(proof.checks).every(Boolean)).toBe(true);
  } finally {
    if (setup) {
      const restored = fixture('restore', run);
      expect(restored.result).toBe('RESTORED');
      expect(restored.option_values_and_existence_match).toBe(true);
      await testInfo.attach('native-gateway-option-restoration', { body: JSON.stringify(restored, null, 2), contentType: 'application/json' });
      testInfo.annotations.push({ type: 'fixture-effects', description: proof
        ? `Retained synthetic native order ${proof.order_id}, on-hold and unpaid; no download permission; exact temporary option restoration verified. Native transport fence blocked mail; no funds transferred.`
        : 'Temporary local gateway and guest-checkout options restored exactly. A failed browser attempt may leave a native synthetic checkout draft; no order is deleted or marked paid.' });
    }
  }
});
