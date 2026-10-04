const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/student-material-context.html';

test('material preserves course and cohort context without becoming a second dashboard',async({page})=>{
  await page.goto(url);
  const context=page.getByTestId('study-context');
  await expect(context).toBeVisible();
  await expect(context).toContainText('Positivo direto em filme de raio-X');
  await expect(context).toContainText('Turma Outubro 2026');
  await expect(page.getByTestId('back-course')).toHaveAttribute('href','/aluno/cursos.php?cohort=cohort-a');
  await expect(page.getByTestId('questions')).toHaveAttribute('href','/aluno/duvidas.php?cohort=cohort-a');
  await expect(context.locator('a[rel="prev"]')).toHaveAttribute('href',/cohort=cohort-a/);
  await expect(context.locator('a[rel="next"]')).toHaveAttribute('href',/cohort=cohort-a/);
  await expect(page.locator('.cms-student-context')).toHaveCount(0);
});

test('material is a continuous editorial reading surface with annotations beside it',async({page})=>{
  await page.goto(url);
  await expect(page.getByTestId('editorial-material')).toBeVisible();
  await expect(page.getByTestId('editorial-material').locator('[data-cms-section]')).toHaveCount(2);
  await expect(page.getByTestId('notes-panel')).toBeVisible();
  await page.getByTestId('notes-panel').locator('summary').click();
  await expect(page.getByTestId('notes-panel')).toHaveAttribute('open','');
  await expect(page.getByTestId('notes-panel')).toContainText('Comparar EI 200 e EI 400');
});

test('material context and notes remain usable on a narrow phone',async({page})=>{
  await page.setViewportSize({width:360,height:800});
  await page.goto(url);
  await expect(page.getByTestId('study-context')).toBeVisible();
  await expect(page.getByTestId('editorial-material')).toBeVisible();
  await expect(page.getByTestId('notes-panel')).toBeVisible();
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
});
