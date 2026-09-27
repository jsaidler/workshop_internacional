const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/student-tests-context.html';

test('tests index requires explicit enrollment context when several courses exist',async({page})=>{
  await page.goto(url);
  await expect(page.locator('h1')).toHaveText('Testes por curso');
  await expect(page.locator('[data-testid^="tests-course-"]')).toHaveCount(2);
  await expect(page.getByTestId('owned-tests')).toHaveCount(0);

  await page.getByTestId('tests-course-a').click();
  await expect(page).toHaveURL(/cohort=cohort-a/);
  await expect(page.locator('h1')).toHaveText('Testes');
  await expect(page.locator('input[name="cohort_id"]')).toHaveValue('10');
  await expect(page.locator('select[name="cohort_id"]')).toHaveCount(0);
  await expect(page.getByTestId('owned-current')).toContainText('Teste A');
  await expect(page.getByTestId('shared-cohort-a')).toContainText('Compartilhado A');
  await expect(page.getByTestId('shared-course-a')).toContainText('outra turma do Curso A');
  await expect(page.getByText('Teste B',{exact:true})).toHaveCount(0);
});

test('selected tests workspace remains usable on a phone viewport',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url+'?cohort=cohort-a');
  await expect(page.getByTestId('create-test')).toBeVisible();
  await expect(page.getByTestId('owned-tests')).toBeVisible();
  await expect(page.getByTestId('shared-tests')).toBeVisible();
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
});
