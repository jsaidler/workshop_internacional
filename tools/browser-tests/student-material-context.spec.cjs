const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/student-material-context.html';

test('protected material keeps an explicit return path to the selected course',async({page})=>{
  await page.goto(url);
  const context=page.locator('[data-cms-student-context]');
  await expect(context).toBeVisible();
  await expect(context).toContainText('Positivo direto em filme de raio-X');
  await expect(context).toContainText('Turma Setembro');
  await expect(page.getByTestId('back-course')).toHaveAttribute('href','/aluno/?cohort=cohort-a');
  await expect(page.getByTestId('tests')).toHaveAttribute('href','/aluno/testes.php');
  await expect(page.getByTestId('account')).toHaveAttribute('href','/aluno/perfil.php');
  await expect(page.locator('.cms-student-access')).toHaveAttribute('href','/aluno/?cohort=cohort-a');
});

test('protected material context remains usable on a phone viewport',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url);
  const context=page.locator('[data-cms-student-context]');
  await expect(context).toBeVisible();
  await expect(page.getByTestId('back-course')).toBeVisible();
  await expect(page.getByTestId('tests')).toBeVisible();
  await expect(page.getByTestId('account')).toBeVisible();
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
});
