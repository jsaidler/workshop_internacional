const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/student-dashboard-truth.html';

test('dashboard prioritizes unfinished Caderno work instead of metrics',async({page})=>{
  await page.goto(url+'?state=active');
  await expect(page.locator('[data-fixture-state="active"]')).toBeVisible();
  await expect(page.locator('[data-fixture-state="study"]')).not.toBeVisible();
  await expect(page.locator('[data-fixture-state="multiple"]')).not.toBeVisible();
  await expect(page.locator('[data-fixture-state="active"] h2')).toHaveText('Retrato 04 — janela lateral');
  await expect(page.locator('[data-fixture-state="active"] .button')).toHaveText(/Continuar registro/);
  await expect(page.locator('.student-dashboard-links')).toBeVisible();
  await expect(page.locator('.student-home-primary')).toHaveCount(0);
});

test('dashboard opens material directly for one enrollment when no record needs action',async({page})=>{
  await page.goto(url+'?state=study');
  await expect(page.locator('[data-fixture-state="study"]')).toBeVisible();
  await expect(page.locator('[data-fixture-state="active"]')).not.toBeVisible();
  await expect(page.locator('[data-fixture-state="multiple"]')).not.toBeVisible();
  await expect(page.locator('[data-fixture-state="study"] h2')).toHaveText('Exposição pensando no positivo');
  await expect(page.locator('[data-fixture-state="study"] .button')).toHaveText(/Abrir material/);
});

test('multiple enrollments create one reversible course decision',async({page})=>{
  await page.goto(url+'?state=multiple');
  await expect(page.locator('[data-fixture-state="multiple"]')).toBeVisible();
  await expect(page.locator('[data-fixture-state="active"]')).not.toBeVisible();
  await expect(page.locator('[data-fixture-state="study"]')).not.toBeVisible();
  await expect(page.locator('[data-fixture-state="multiple"] h2')).toHaveText('Escolha o curso');
  await expect(page.locator('[data-course-meta]')).toHaveText('2 matrículas ativas.');
});

test('dashboard remains usable on a phone viewport',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url+'?state=study');
  await expect(page.locator('[data-fixture-state="study"] .button')).toBeVisible();
  await expect(page.locator('[data-fixture-state="active"]')).not.toBeVisible();
  await expect(page.locator('[data-fixture-state="multiple"]')).not.toBeVisible();
  await expect(page.locator('.student-mobile-nav')).toBeVisible();
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
});
