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
  await expect(context.locator('a[rel="next"]')).toContainText('Segunda revelação e acabamento');
  await expect(page.locator('.cms-student-context')).toHaveCount(0);
  await expect(page.locator('[data-cms-public-header] .brand')).toHaveAttribute('href','/aluno/');
  const desktopNav=page.locator('.student-desktop-nav');
  await expect(desktopNav.getByText('Início',{exact:true})).toHaveAttribute('href','/aluno/');
  await expect(desktopNav.getByText('Cursos',{exact:true})).toHaveAttribute('href','/aluno/cursos.php');
  await expect(desktopNav.getByText('Caderno',{exact:true})).toHaveAttribute('href','/aluno/caderno.php');
  await expect(desktopNav.getByText('Laboratório',{exact:true})).toHaveAttribute('href','/aluno/ferramentas.php');
  await expect(desktopNav.getByText('Cursos',{exact:true})).toHaveAttribute('aria-current','page');
});

test('material is a continuous editorial reading surface with annotations beside it',async({page})=>{
  await page.goto(url);
  const material=page.getByTestId('editorial-material');
  await expect(material).toBeVisible();
  await expect(material.locator('[data-cms-section]')).toHaveCount(2);
  await expect(material).not.toContainText('aula correspondente já foi liberada');
  await expect(material).not.toContainText('não aparece neste HTML');
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
  const mobileNav=page.locator('.student-mobile-nav');
  await expect(mobileNav).toBeVisible();
  await expect(mobileNav.locator('a')).toHaveCount(4);
  await expect(mobileNav.locator('a').nth(0)).toHaveText('Início');
  await expect(mobileNav.locator('a').nth(1)).toHaveText('Cursos');
  await expect(mobileNav.locator('a').nth(2)).toHaveText('Caderno');
  await expect(mobileNav.locator('a').nth(3)).toHaveText('Laboratório');
  await expect(mobileNav.getByText('Cursos',{exact:true})).toHaveAttribute('aria-current','page');
  const chrome=await page.evaluate(()=>{const nav=document.querySelector('.student-mobile-nav'),body=document.body,r=nav.getBoundingClientRect(),s=getComputedStyle(body);return{position:getComputedStyle(nav).position,bottom:r.bottom,height:r.height,viewport:innerHeight,paddingBottom:parseFloat(s.paddingBottom||'0')};});
  expect(chrome.position).toBe('fixed');
  expect(Math.abs(chrome.bottom-chrome.viewport)).toBeLessThanOrEqual(1);
  expect(chrome.paddingBottom).toBeGreaterThanOrEqual(chrome.height);
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
});
