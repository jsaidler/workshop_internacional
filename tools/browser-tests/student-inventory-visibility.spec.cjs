const {test,expect}=require('@playwright/test');

test('process amount field is never orphaned from inventory choice',async({page})=>{
  await page.goto('http://127.0.0.1:8099/tools/browser-fixture/student-process-ux.html?id=17',{waitUntil:'networkidle'});
  const guided=page.locator('.student-process-choice-button');
  await guided.nth(0).click();
  const amount=page.locator('input[name="inventory_amount_simple"]');
  await expect(amount).toBeHidden();
  await guided.nth(1).click();
  await expect(amount).toBeHidden();
  const inventory=page.locator('select[name="inventory_item_id_simple"]');
  await inventory.selectOption('4');
  await expect(amount).toBeVisible();
});
