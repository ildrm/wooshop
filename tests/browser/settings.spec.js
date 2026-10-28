const { test, expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');

test('native admin previews and restores configuration as a new revision', async ({page}) => {
  const credentials=JSON.parse(fs.readFileSync(path.resolve('.local/credentials.json'),'utf8'));
  await page.goto('/wp-login.php',{waitUntil:'networkidle'});
  await page.locator('#user_login').fill('fixture-admin');
  await page.locator('#user_pass').fill(credentials.admin);
  expect(await page.locator('#user_login').inputValue()==='fixture-admin').toBe(true);
  await expect(page.locator('#user_pass')).toHaveAttribute('type','password');
  await page.locator('#wp-submit').click();
  await page.waitForURL(/wp-admin/);
  const admin='/wp-admin/admin.php?page=wooshop-commerce';
  const readConfiguration=async()=>{
    const pending=page.waitForEvent('download');
    await page.getByRole('button',{name:'Export configuration JSON',exact:true}).click();
    const download=await pending;
    expect(download.suggestedFilename()).toBe('wooshop-configuration.json');
    const result=JSON.parse(fs.readFileSync(await download.path(),'utf8'));
    expect(result.schema_version).toBe(1);expect(result.kind).toBe('configuration');
    return result;
  };
  await page.goto(admin,{waitUntil:'networkidle'});
  const original=await readConfiguration();
  const initialVersion=Number(await page.locator('form:has(#wooshop-settings) input[name="version"]').inputValue());
  const oldLimit=original.settings.find(entry=>entry.key==='wishlist.max_items')?.value;
  const testLimit=oldLimit===77?78:77;
  let changed=false;
  const restoreOriginal=async()=>{
    await page.goto(admin,{waitUntil:'networkidle'});
    const option=page.locator('#wooshop-revision option').filter({hasText:new RegExp(`^Revision ${initialVersion} ·`)});
    await expect(option).toHaveCount(1);
    await page.locator('#wooshop-revision').selectOption(await option.getAttribute('value'));
    await page.getByRole('button',{name:'Preview restoration',exact:true}).click();
    await page.waitForURL(/restore_preview=/);
    await expect(page.getByRole('heading',{name:'Review configuration restoration',exact:true})).toBeVisible();
  };
  try {
    await page.locator('#wooshop-settings').fill(JSON.stringify([{key:'wishlist.max_items',value:testLimit,scope:'site'}]));
    await page.getByRole('button',{name:'Validate and save',exact:true}).click();
    await page.waitForURL(/saved=1/);changed=true;
    expect((await readConfiguration()).settings.find(entry=>entry.key==='wishlist.max_items').value).toBe(testLimit);
    await restoreOriginal();
    const row=page.locator('table').filter({has:page.getByRole('columnheader',{name:'Current override',exact:true})}).getByRole('row').filter({has:page.getByRole('rowheader',{name:'wishlist.max_items',exact:true})});
    await expect(row).toContainText(String(testLimit));
    // Preview must not publish the historical values.
    expect((await readConfiguration()).settings.find(entry=>entry.key==='wishlist.max_items').value).toBe(testLimit);
    await page.getByRole('button',{name:'Apply reviewed configuration',exact:true}).click();
    await page.waitForURL(/restored=1/);
    expect(await readConfiguration()).toEqual(original);
    expect(Number(await page.locator('form:has(#wooshop-settings) input[name="version"]').inputValue())).toBe(initialVersion+2);
    changed=false;
  } finally {
    if (changed) {
      await restoreOriginal();
      await page.getByRole('button',{name:'Apply reviewed configuration',exact:true}).click();
      await page.waitForURL(/restored=1/);
      expect(await readConfiguration()).toEqual(original);
    }
  }
});
