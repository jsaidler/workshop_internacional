const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/student-navigation-history.html';

async function expectActive(page,label){
  const mobile=page.locator('.student-mobile-nav');
  await expect(mobile).toBeVisible();
  await expect(mobile.getByText(label,{exact:true})).toHaveAttribute('aria-current','page');
}

test('native Back and Forward traverse course context without trapping history',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url+'?screen=home');
  await expectActive(page,'Início');

  await page.locator('.student-mobile-nav').getByText('Cursos',{exact:true}).click();
  await expect(page.locator('h1')).toHaveText('Cursos');
  await expectActive(page,'Cursos');

  await page.getByTestId('course-a').click();
  await expect(page).toHaveURL(/screen=course&cohort=cohort-a/);
  await expect(page.locator('h1')).toHaveText('Curso A');
  await expectActive(page,'Cursos');

  await page.getByTestId('open-material').click();
  await expect(page).toHaveURL(/screen=material&cohort=cohort-a/);
  await expect(page.locator('h1')).toHaveText('Material 2');
  await expectActive(page,'Cursos');

  const beforeMicrostate=await page.evaluate(()=>({href:location.href,length:history.length}));
  await page.getByTestId('microstate').locator('summary').click();
  const afterMicrostate=await page.evaluate(()=>({href:location.href,length:history.length}));
  expect(afterMicrostate).toEqual(beforeMicrostate);

  await page.getByTestId('open-question').click();
  await expect(page).toHaveURL(/screen=question&cohort=cohort-a/);
  await expect(page.locator('h1')).toHaveText('Dúvida');
  await expectActive(page,'Cursos');

  await page.goBack();await expect(page.locator('h1')).toHaveText('Material 2');await expect(page).toHaveURL(/cohort=cohort-a/);await expectActive(page,'Cursos');
  await page.goBack();await expect(page.locator('h1')).toHaveText('Curso A');await expect(page).toHaveURL(/cohort=cohort-a/);await expectActive(page,'Cursos');
  await page.goBack();await expect(page.locator('h1')).toHaveText('Cursos');await expectActive(page,'Cursos');
  await page.goBack();await expect(page.locator('h1')).toHaveText('Início');await expectActive(page,'Início');

  await page.goForward();await expect(page.locator('h1')).toHaveText('Cursos');
  await page.goForward();await expect(page.locator('h1')).toHaveText('Curso A');
  await page.goForward();await expect(page.locator('h1')).toHaveText('Material 2');await expect(page).toHaveURL(/cohort=cohort-a/);
  await page.goForward();await expect(page.locator('h1')).toHaveText('Dúvida');await expect(page).toHaveURL(/cohort=cohort-a/);await expectActive(page,'Cursos');
});

test('deep links keep a logical parent and the correct global context',async({page})=>{
  await page.setViewportSize({width:390,height:844});

  await page.goto(url+'?screen=step&id=42&step=7');
  await expect(page.locator('h1')).toHaveText('Editar etapa');
  await expectActive(page,'Caderno');
  await expect(page.getByTestId('context-back')).toHaveAttribute('href','?screen=record&id=42');
  await page.getByTestId('context-back').click();
  await expect(page).toHaveURL(/screen=record&id=42/);
  await expectActive(page,'Caderno');

  await page.goto(url+'?screen=item&item=9');
  await expect(page.locator('h1')).toHaveText('Editar item');
  await expectActive(page,'Laboratório');
  await expect(page.getByTestId('context-back')).toHaveAttribute('href','?screen=inventory');
  await page.getByTestId('context-back').click();
  await expect(page.locator('h1')).toHaveText('Inventário');
  await expectActive(page,'Laboratório');

  await expect(page.locator('.student-wordmark')).toHaveAttribute('data-canonical-href','/aluno/');
});
