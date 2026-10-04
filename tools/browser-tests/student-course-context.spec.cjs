const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/student-course-context.html';

test('multiple enrollments require an explicit reversible course choice',async({page})=>{
  await page.goto(url);
  await expect(page.locator('h1')).toHaveText('Escolha a matrícula');
  await expect(page.locator('[data-testid^="course-"]')).toHaveCount(2);
  await page.getByTestId('course-b').click();
  await expect(page).toHaveURL(/cohort=cohort-b/);
  await expect(page.locator('h1')).toHaveText('O Fazer Intuitivo');
  await expect(page.getByTestId('empty-material')).toBeVisible();
  await page.getByTestId('all-courses').click();
  await expect(page.locator('h1')).toHaveText('Escolha a matrícula');
});

test('course organizes material by editorial page and keeps lesson releases secondary',async({page})=>{
  await page.goto(url+'?cohort=cohort-a');
  await expect(page.getByTestId('material-list').locator('.student-academic-material-row')).toHaveCount(3);
  await expect(page.locator('[data-state="available"]')).toHaveText('Disponível');
  await expect(page.locator('[data-state="partial"]')).toHaveText('Parcial');
  await expect(page.locator('[data-state="scheduled"]')).toHaveText('Agendado');
  await expect(page.locator('.student-academic-release-summary')).toContainText('Próximas liberações');
  await expect(page.locator('.student-course-dashboard')).toHaveCount(0);
});

test('selected course remains readable on a phone viewport without horizontal overflow',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url+'?cohort=cohort-a');
  await expect(page.getByTestId('material-list')).toBeVisible();
  await expect(page.locator('.student-mobile-nav')).toBeVisible();
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
});
