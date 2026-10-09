const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-notebook-compare.html';

test('comparison is contextual, non-persistent, and only analyzes two records',async({page})=>{
  await page.goto(`${base}?screen=select`,{waitUntil:'networkidle'});
  await expect(page.locator('[data-compare-mode-open]')).toHaveCount(0);
  await expect(page.locator('.student-compare-pick')).toHaveCount(0);
  await expect(page.locator('.student-research-picker')).toBeVisible();
  await expect(page.locator('.student-research-picked')).toHaveCount(1);
  await expect(page.locator('.student-research-picked')).toContainText('Registro A');
  await expect(page.locator('.student-research-picker select')).toHaveCount(1);
  await expect(page.getByRole('button',{name:'Comparar registros'})).toBeVisible();
  await expect(page.locator('.student-research-differences')).toHaveCount(0);

  await page.goto(`${base}?screen=analysis`,{waitUntil:'networkidle'});
  await expect(page.locator('.student-research-differences')).toBeVisible();
  await expect(page.locator('.student-research-difference-row')).toHaveCount(3);
  await expect(page.locator('.student-research-result-card')).toHaveCount(2);
  await expect(page.locator('.student-research-next-form .student-research-basis-options input')).toHaveCount(2);
  await expect(page.locator('.student-research-next-form textarea')).toBeVisible();
});

test('derived records expose lineage through the record menu instead of permanent compare mode',async({page})=>{
  await page.goto(`${base}?screen=derived`,{waitUntil:'networkidle'});
  await expect(page.locator('[data-compare-mode-open]')).toHaveCount(0);
  await expect(page.locator('.student-compare-pick')).toHaveCount(0);
  await expect(page.locator('.student-research-card-lineage')).toContainText('Continuação de');
  const summary=page.locator('.student-record-summary');
  await expect(summary).toBeVisible();
  await expect(summary.locator('span')).toHaveCount(3);
  await expect(summary).toContainText('Exposição ainda não registrada');
  await expect(summary).toContainText('Processamento ainda não registrado');
  await expect(summary).toContainText('Resultado ainda não registrado');
  await expect(page.locator('.student-record-progress')).toHaveCount(0);
  const menu=page.locator('.student-record-menu');
  const compareOrigin=menu.getByRole('link',{name:'Comparar com origem'});
  await expect(menu).toBeVisible();
  await expect(compareOrigin).toBeHidden();
  await menu.locator('summary').click();
  await expect(compareOrigin).toBeVisible();
});
