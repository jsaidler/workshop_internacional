const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/student-course-context.html';

test('multiple enrollments require an explicit course choice and preserve cohort state',async({page})=>{
  await page.goto(url);
  await expect(page.locator('h1')).toHaveText('Seus cursos');
  await expect(page.locator('[data-testid^="course-"]')).toHaveCount(2);

  await page.getByTestId('course-b').click();
  await expect(page).toHaveURL(/cohort=cohort-b/);
  await expect(page.locator('h1')).toHaveText('Curso B');
  await expect(page.locator('.student-status')).toHaveText('Turma encerrada');
  await expect(page.getByTestId('progress')).toHaveText('Sem aulas cadastradas');

  await page.getByTestId('all-courses').click();
  await expect(page.locator('h1')).toHaveText('Seus cursos');
  await expect(page).not.toHaveURL(/cohort=/);
});

test('selected course workspace remains usable on a phone viewport',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url+'?cohort=cohort-a');
  await expect(page.getByTestId('workspace')).toBeVisible();
  await expect(page.locator('.student-status')).toHaveText('Turma ativa');
  await expect(page.getByTestId('progress')).toHaveText('1/3 aulas liberadas');
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
});
