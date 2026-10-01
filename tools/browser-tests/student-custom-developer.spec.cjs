const {test,expect}=require('@playwright/test');

test('custom developer field only explains itself when Outro is selected',async({page})=>{
  await page.goto('http://127.0.0.1:8099/tools/browser-fixture/student-process-ux.html?id=17',{waitUntil:'networkidle'});
  await page.locator('.student-process-other-stage summary').click();
  await page.locator('.student-process-other-stage select').selectOption('second_development');
  const select=page.locator('[data-developer-select]');
  await expect(select).toBeVisible();
  await expect(select).toBeEnabled();
  const field=page.locator('[data-custom-developer]');
  await expect(field).toBeHidden();
  await select.selectOption('other');
  await expect(field).toBeVisible();
  await expect(field).toContainText('Outro revelador');
  await select.selectOption('parodinal');
  await expect(field).toBeHidden();
});
