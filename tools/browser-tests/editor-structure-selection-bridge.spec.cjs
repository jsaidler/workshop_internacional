const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-structure-selection-bridge.html';

test('sidebar structure node activates the corresponding canvas node without inspector tree',async({page})=>{
  await page.goto(url,{waitUntil:'domcontentloaded'});
  await expect(page.locator('#inspector .cms-structure-tree')).toHaveCount(0);
  await page.locator('[data-page-tree-node="0"]').click();
  await expect(page.locator('body')).toHaveAttribute('data-selected-node','p1');
});
