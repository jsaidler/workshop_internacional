const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/student-material-continuity.html';

test('CMS material keeps the selected student workspace visible',async({page})=>{
  await page.goto(url);
  await expect(page.getByTestId('student-context')).toBeVisible();
  await expect(page.getByTestId('student-context')).toContainText('Positivo direto em filme de raio-X');
  await expect(page.getByTestId('student-context')).toContainText('Turma Setembro');
  await expect(page.getByTestId('course-link')).toHaveAttribute('href','/aluno/?cohort=cohort-a');
  await expect(page.getByTestId('tests-link')).toHaveAttribute('href','/aluno/testes.php');
  await expect(page.getByTestId('account-link')).toHaveAttribute('href','/aluno/perfil.php');
  await expect(page.getByTestId('cms-page-link')).toHaveAttribute('href',/cohort=cohort-a/);
  await expect(page.getByTestId('brand')).toHaveAttribute('href',/cohort=cohort-a/);
});

test('student material context remains usable on a phone viewport',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url);
  await expect(page.getByTestId('student-context')).toBeVisible();
  await expect(page.getByTestId('course-link')).toBeVisible();
  await expect(page.getByTestId('tests-link')).toBeVisible();
  await expect(page.getByTestId('account-link')).toBeVisible();
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
});
