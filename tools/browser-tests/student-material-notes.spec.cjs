const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-material-notes.html';

test('material notes use a single page-level control and remain outside content sections',async({page})=>{
  await page.goto(base,{waitUntil:'networkidle'});
  await expect(page.locator('.student-notes-entry')).toBeHidden();
  const entry=page.locator('[data-student-notes-panel]>summary');
  await expect(entry).toBeVisible();
  await expect(entry).toContainText('Anotações');
  await expect(page.locator('section[data-cms-section] form')).toHaveCount(0);
  await entry.click();
  await expect(page.locator('#anotacoes')).toHaveAttribute('open','');
  await expect(page.locator('#anotacoes')).toContainText('Anotação da página');
  await expect(page.locator('#anotacoes')).toContainText('Também é uma dúvida?');
  await expect(page.locator('#anotacoes')).toContainText('Transformar em dúvida');
});
