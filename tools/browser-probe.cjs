const { chromium } = require('@playwright/test');
const fs = require('node:fs');
(async () => {
  fs.mkdirSync('artifacts/browser', {recursive:true});
  const browser = await chromium.launch({channel:'chrome',headless:true});
  const context = await browser.newContext({viewport:{width:1440,height:1000}});
  const page = await context.newPage();
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  for(const [site,port] of [['outdoor',18880],['smart-home',18881]]) {
    const response=await page.goto(`http://localhost:${port}/`,{waitUntil:'networkidle',timeout:90000});
    await page.screenshot({path:`artifacts/browser/${site}-desktop.png`,fullPage:true});
    console.log(JSON.stringify({site,status:response.status(),title:await page.title(),headings:await page.locator('h1,h2').allTextContents(),buttons:await page.getByRole('button').allTextContents(),links:(await page.getByRole('link').allTextContents()).slice(0,20),errors}));
    const products=await context.request.get(`http://localhost:${port}/wp-json/wc/store/v1/products?per_page=30`);
    console.log(JSON.stringify({site,storeAPIStatus:products.status(),products:(await products.json()).slice(0,4).map(p=>({id:p.id,name:p.name,type:p.type,permalink:p.permalink}))}));
  }
  await browser.close();
})().catch(e=>{console.error(e.message);process.exit(1);});
