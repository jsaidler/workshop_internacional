const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-home.php';
test('Home keeps next meeting, assessment and work visible simultaneously',async({page})=>{
  await page.goto(base+'?state=active');
  await expect(page.locator('[data-home-meeting]')).toBeVisible();
  await expect(page.locator('[data-home-feedback]')).toBeVisible();
  await expect(page.getByRole('heading',{name:'Continuar'})).toBeVisible();
  await expect(page.getByRole('heading',{name:'Caderno recente'})).toBeVisible();
});
test('Home opens material for a single enrollment without extra choice',async({page})=>{
  await page.goto(base+'?state=study');
  await expect(page.locator('[data-home-study-feature] a')).toHaveAttribute('href',/cohort=cohort-a/);
});
test('multiple enrollments expose scoped links, not a selector gate',async({page})=>{
  await page.goto(base+'?state=multiple');
  await expect(page.locator('.student-v2-course')).toHaveCount(2);
  await expect(page.locator('.student-home-course-actions a[href*="cohort-b"]')).toHaveCount(2);
});
test('mobile first viewport contains primary action and bottom navigation',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(base+'?state=active');
  await expect(page.locator('[data-home-meeting] .student-v2-cta')).toBeInViewport();
  await expect(page.locator('.student-v2-footer-actions [data-record-create-open]')).toHaveCount(1);
  await expect(page.locator('.student-mobile-nav')).toBeVisible();
});
