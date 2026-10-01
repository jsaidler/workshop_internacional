const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-notebook-compare.html';

test('comparison is temporary and requires exactly two records',async({page})=>{
  await page.goto(base,{waitUntil:'networkidle'});
  const open=page.locator('[data-compare-mode-open]');
  await expect(open).toBeVisible();
  await expect(page.locator('.student-compare-pick')).toHaveCount(3);
  await expect(page.locator('.student-compare-pick').first()).toBeHidden();
  await open.click();
  await expect(page.locator('.student-compare-selection')).toBeVisible();
  await expect(page.locator('.student-compare-pick').first()).toBeVisible();
  const go=page.locator('[data-compare-go]');
  await expect(go).toBeDisabled();
  await page.locator('.student-compare-pick input').nth(0).check();
  await expect(go).toBeDisabled();
  await page.locator('.student-compare-pick input').nth(1).check();
  await expect(go).toBeEnabled();
  await expect(page.locator('[data-compare-count]')).toHaveText('2 registros selecionados');
  await page.locator('[data-compare-cancel]').click();
  await expect(page.locator('.student-compare-selection')).toBeHidden();
  await expect(open).toBeVisible();
});
