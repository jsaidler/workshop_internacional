const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-material-notes.html';

test('material notes have a visible page-level entry and remain outside content sections',async({page})=>{
  await page.goto(base,{waitUntil:'networkidle'});
  const entry=page.locator('.student-notes-entry a');
  await expect(entry).toBeVisible();
  await expect(entry).toContainText('Anotações');
  await expect(page.locator('section[data-cms-section] form')).toHaveCount(0);
  await entry.click();
  await expect(page.locator('#anotacoes')).toHaveAttribute('open','');
  await expect(page.locator('#anotacoes')).toContainText('Onde esta anotação se aplica?');
});
