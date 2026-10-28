const { test, expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');

const credentials = JSON.parse(fs.readFileSync(path.resolve('.local/credentials.json'), 'utf8'));

for (const [site, port, hero] of [['outdoor',18880,'Your next route starts here.'],['smart-home',18881,'Make room for a smarter everyday.']]) {
  test(`${site}: original native storefront, responsive navigation and screenshot`, async ({page}) => {
    const errors=[];page.on('pageerror',error=>errors.push(error.message));
    const response=await page.goto(`http://localhost:${port}/`,{waitUntil:'networkidle'});
    expect(response.status()).toBe(200);
    await expect(page.getByRole('heading',{name:hero,exact:true})).toBeVisible();
    await expect(page.getByRole('link',{name:'Skip to content'})).toHaveAttribute('href',/#/);
    await expect(page.getByRole('searchbox')).toBeVisible();
    await page.screenshot({path:`artifacts/browser/${site}-desktop.png`,fullPage:true});
    await page.setViewportSize({width:390,height:844});
    await expect(page.getByRole('searchbox')).toBeVisible();
    const menu=page.locator('header details summary').first();
    await menu.click();
    await expect(page.locator('header details').first()).toHaveJSProperty('open',true);
    await page.keyboard.press('Escape');
    await expect(page.locator('header details').first()).toHaveJSProperty('open',false);
    await expect(menu).toBeFocused();
    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>window.innerWidth+1);
    expect(overflow).toBe(false);
    await page.screenshot({path:`artifacts/browser/${site}-mobile.png`,fullPage:true});
    expect(errors).toEqual([]);
  });
}

test('native simple product → cart → checkout interface uses real WooCommerce state', async ({page,request}) => {
  const products=await (await request.get('/wp-json/wc/store/v1/products?per_page=100')).json();
  const product=products.find(p=>p.name==='Synthetic Trail Pack 35L');
  expect(product).toBeTruthy();
  await page.goto(product.permalink,{waitUntil:'networkidle'});
  await expect(page.getByRole('main')).toHaveCount(1);
  await expect(page.getByRole('heading',{level:1,name:product.name})).toBeVisible();
  const beforeCart=await (await page.request.get('/wp-json/wc/store/v1/cart')).json();
  const beforeQuantity=beforeCart.items.filter(item=>item.id===product.id).reduce((sum,item)=>sum+item.quantity,0);
  await page.locator('button.single_add_to_cart_button').click();
  await expect(page.getByRole('alert').filter({hasText:/added to your cart/i})).toContainText(product.name);
  await expect.poll(async()=>{
    const cart=await (await page.request.get('/wp-json/wc/store/v1/cart')).json();
    return cart.items.filter(item=>item.id===product.id).reduce((sum,item)=>sum+item.quantity,0);
  }).toBe(beforeQuantity+1);
  await page.locator('header').getByRole('link',{name:'Cart',exact:true}).click();
  await page.waitForLoadState('networkidle');
  await expect(page.getByRole('main')).toHaveCount(1);
  await expect(page.getByRole('main')).toContainText(product.name);
  await page.screenshot({path:'artifacts/browser/native-cart.png',fullPage:true});
  await page.getByRole('link',{name:/proceed to checkout/i}).click();
  await page.waitForLoadState('networkidle');
  await expect(page.getByRole('textbox',{name:/email address/i})).toBeVisible();
  await expect(page.getByRole('main')).toHaveCount(1);
  await expect(page.getByRole('main')).toContainText(product.name);
  await page.screenshot({path:'artifacts/browser/native-checkout.png',fullPage:true});
  test.info().annotations.push({type:'fixture-effects',description:'One item added to an isolated guest cart;checkout interface visited;no order/payment submission or external supplier navigation performed.'});
});

test('external product remains a supplier action and variable product requires selection', async ({page,request}) => {
  const products=await (await request.get('/wp-json/wc/store/v1/products?per_page=100')).json();
  const external=products.find(p=>p.type==='external');
  const variable=products.find(p=>p.type==='variable');
  expect(external).toBeTruthy();expect(variable).toBeTruthy();
  const beforeCart=await (await page.request.get('/wp-json/wc/store/v1/cart')).json();
  await page.goto(external.permalink,{waitUntil:'networkidle'});
  await expect(page.getByRole('main')).toHaveCount(1);
  const supplierForm=page.locator('form.cart');
  await expect(supplierForm).toHaveAttribute('action',/^https:\/\/example\.invalid/);
  await expect(supplierForm).toHaveAttribute('method','get');
  await expect(supplierForm.locator('button.single_add_to_cart_button[type="submit"]')).toBeVisible();
  await expect(supplierForm.locator('[name="add-to-cart"]')).toHaveCount(0);
  const afterExternal=await (await page.request.get('/wp-json/wc/store/v1/cart')).json();
  expect(afterExternal.items.map(item=>({id:item.id,quantity:item.quantity}))).toEqual(beforeCart.items.map(item=>({id:item.id,quantity:item.quantity})));
  await page.goto(variable.permalink,{waitUntil:'networkidle'});
  await expect(page.getByRole('main')).toHaveCount(1);
  await expect(page.locator('form.variations_form')).toBeVisible();
  await expect(page.locator('form.variations_form select[name^="attribute_"]').first()).toHaveValue('');
  await expect(page.locator('button.single_add_to_cart_button')).toHaveClass(/disabled/);
});

test('actual WordPress cookie authentication, REST nonce and command replay boundaries', async ({page,context}) => {
  // WordPress installs login focus handlers during load; wait before entering secrets.
  await page.goto('/wp-login.php',{waitUntil:'networkidle'});
  await page.locator('#user_login').fill('fixture-admin');
  await page.locator('#user_pass').fill(credentials.admin);
  expect(await page.locator('#user_login').inputValue()==='fixture-admin').toBe(true);
  await expect(page.locator('#user_pass')).toHaveAttribute('type','password');
  await page.locator('#wp-submit').click();
  await page.waitForURL(/wp-admin/);
  const nonce=await page.evaluate(()=>window.wpApiSettings?.nonce);
  expect(typeof nonce).toBe('string');
  const noNonce=await context.request.get('/wp-json/commerce/v1/diagnostics');
  expect([401,403]).toContain(noNonce.status());
  const auth=await context.request.get('/wp-json/commerce/v1/diagnostics',{headers:{'X-WP-Nonce':nonce}});
  expect(auth.status()).toBe(200);
  const diagnostic=await auth.json();expect(diagnostic.checks.some(c=>c.code==='marketplace_activation'&&c.state==='warn')).toBe(true);
  const headers={'X-WP-Nonce':nonce,'Idempotency-Key':`browser-preview-${Date.now()}`};
  const data={schema_version:1,settings:[{key:'wishlist.max_items',value:123,scope:'site'}]};
  const first=await context.request.post('/wp-json/commerce/v1/settings/preview',{headers,data});
  expect(first.status()).toBe(200);
  const firstResult=await first.json();
  const replay=await context.request.post('/wp-json/commerce/v1/settings/preview',{headers,data});
  expect(replay.status()).toBe(200);expect((await replay.json()).preview_id).toBe(firstResult.preview_id);
  const changed=await context.request.post('/wp-json/commerce/v1/settings/preview',{headers,data:{...data,settings:[{key:'wishlist.max_items',value:124,scope:'site'}]}});
  expect(changed.status()).toBe(409);
  const escalation=await context.request.post('/wp-json/commerce/v1/settings/preview',{headers:{...headers,'Idempotency-Key':`browser-forbidden-${Date.now()}`},data:{schema_version:1,settings:[{key:'marketplace.enabled',value:true,scope:'site'}]}});
  expect(escalation.status()).toBe(409);
});

test('signed opt-out is login-free, uniform and exposes no recipient identity', async ({request,page}) => {
  const invalid=await request.post('/wp-json/commerce/v1/notifications/unsubscribe',{data:{token:'invalid-but-long-enough-for-transport-validation'}});
  expect(invalid.status()).toBe(204);
  expect(await invalid.text()).toBe('');
  await page.goto('/unsubscribe/',{waitUntil:'networkidle'});
  await expect(page.locator('main')).toContainText(/unsubscribe|notification/i);
});
