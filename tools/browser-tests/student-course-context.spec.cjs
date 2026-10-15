const {test,expect}=require('@playwright/test');
const url='http://127.0.0.1:8099/tools/browser-fixture/student-courses.php';
test('multiple enrollments select a course explicitly and keep a reversible path',async({page})=>{
  await page.goto(url+'?state=multiple');
  const selector=page.locator('[data-courses-selection]');
  await expect(selector.locator('a')).toHaveCount(2);
  await expect(selector.locator('a').first()).toHaveAttribute('href','/aluno/cursos.php?cohort=cohort-a');
  await expect(page.locator('[data-course-material]')).toHaveCount(0);
});
test('course material uses CMS page order and keeps scheduled release information secondary',async({page})=>{
  await page.goto(url+'?state=detail');
  const rows=page.locator('.student-v2-course-page');
  await expect(rows).toHaveCount(3);
  await expect(rows.nth(0).locator('[data-state]')).toHaveAttribute('data-state','available');
  await expect(rows.nth(1).locator('[data-state]')).toHaveAttribute('data-state','partial');
  await expect(rows.nth(2).locator('[data-state]')).toHaveAttribute('data-state','scheduled');
  await expect(page.locator('.student-v2-course-releases')).toHaveCount(1);
  await expect(page.locator('.student-course-dashboard')).toHaveCount(0);
});
test('selected course and global navigation fit on a narrow mobile viewport',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url+'?state=detail');
  await expect(page.locator('[data-course-material]')).toBeVisible();
  const bar=page.locator('.student-mobile-nav');
  await expect(bar.locator('a')).toHaveCount(5);
  await expect(bar.locator('a .student-nav-label')).toHaveText(['Início','Agenda','Cursos','Caderno','Laboratório']);
  await expect(bar.locator('a[href="/aluno/cursos.php"]')).toHaveAttribute('aria-current','page');
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
});
