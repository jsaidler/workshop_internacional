const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-section-library-authority.html';

test('section add entry points use only the consolidated library',async({page})=>{
  await page.goto(url);
  await expect(page.locator('#pro-add-component')).toBeHidden();
  await expect(page.locator('[data-pro-tab="components"]')).toHaveText('Seções prontas');
  await expect(page.locator('[data-pro-tab="blocks"]')).toHaveText('Blocos salvos');
  await expect(page.locator('[data-pro-component="free2"]')).toBeHidden();

  await expect.poll(()=>page.evaluate(()=>document.querySelector('#add-section').onclick===null)).toBe(true);
  await expect.poll(()=>page.evaluate(()=>document.querySelector('#add-section-side').onclick===null)).toBe(true);

  await page.locator('#add-section').click();
  await expect(page.locator('#pro-components-dialog')).toHaveJSProperty('open',true);
  await expect.poll(()=>page.evaluate(()=>window.modernSectionLibraryCalls)).toBe(1);
  await expect.poll(()=>page.evaluate(()=>window.legacySectionLibraryCalls)).toBe(0);

  await page.evaluate(()=>document.querySelector('#pro-components-dialog').close());
  await page.locator('#add-section-side').click();
  await expect(page.locator('#pro-components-dialog')).toHaveJSProperty('open',true);
  await expect.poll(()=>page.evaluate(()=>window.modernSectionLibraryCalls)).toBe(2);
  await expect.poll(()=>page.evaluate(()=>window.legacySectionLibraryCalls)).toBe(0);
});

test('editor runtime no longer ships the legacy section dialog',async({page})=>{
  await page.goto(url);
  await expect(page.locator('#section-dialog')).toHaveCount(0);
  await expect(page.locator('#section-library')).toHaveCount(0);
});
