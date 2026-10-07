const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/student-account-experience.html';

test('Conta exposes profile, security and session actions',async({page})=>{
  await page.goto(url);
  await expect(page.locator('h1')).toHaveText('Conta');
  await expect(page.getByTestId('security')).toBeVisible();
  await expect(page.getByTestId('session')).toBeVisible();
  await expect(page.getByTestId('password-link')).toHaveText('Alterar senha');
  await expect(page.getByTestId('logout')).toHaveText('Sair');
});

test('Conta profile becomes one column on a phone and keeps account actions visible',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url);
  const columns=await page.getByTestId('profile-grid').evaluate(node=>getComputedStyle(node).gridTemplateColumns.split(' ').filter(Boolean).length);
  expect(columns).toBe(1);
  await expect(page.getByTestId('password-link')).toBeVisible();
  await expect(page.getByTestId('logout')).toBeVisible();
  const mobileNav=page.locator('.student-mobile-nav');
  await expect(mobileNav.locator('a')).toHaveCount(4);
  expect(await mobileNav.locator('a').allTextContents()).toEqual(['Início','Cursos','Caderno','Laboratório']);
  await expect(mobileNav.locator('[aria-current="page"]')).toHaveCount(0);
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
});
