const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-home.php';

test('imminent authorized meeting outranks notifications, but does not hide them',async({page})=>{
  await page.goto(base+'?state=active',{waitUntil:'domcontentloaded'});
  await expect(page.locator('[data-home-meeting]')).toBeVisible();
  await expect(page.getByRole('link',{name:/Segunda revelação/})).toHaveAttribute('href',/cohort=cohort-a&id=42/);
  await expect(page.locator('.student-home-bell-count')).toHaveText('1');
  await expect(page.locator('[data-home-courses]')).toBeVisible();
});
test('single enrollment offers direct course-scoped Material without a selector',async({page})=>{
  await page.goto(base+'?state=study',{waitUntil:'domcontentloaded'});
  await expect(page.locator('.student-home-course')).toHaveCount(1);
  await expect(page.locator('.student-home-course .student-home-material')).toHaveAttribute('href',/cohort=cohort-a/);
});
test('multiple enrollments preserve course and cohort context',async({page})=>{
  await page.goto(base+'?state=multiple',{waitUntil:'domcontentloaded'});
  const courses=page.locator('.student-home-course');
  await expect(courses).toHaveCount(2);
  await expect(courses.nth(0).locator('.student-home-course-actions a')).toHaveCount(2);
  await expect(courses.nth(1).locator('.student-home-course-actions a')).toHaveCount(2);
  await expect(courses.nth(0).getByRole('link',{name:'Material'})).toHaveAttribute('href',/cohort=cohort-a/);
  await expect(courses.nth(1).getByRole('link',{name:'Material'})).toHaveAttribute('href',/cohort=cohort-b/);
  await expect(page.locator('[data-record-create-open]')).toHaveCount(0);
});
test('first phone screen exposes a real academic event and global navigation',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(base+'?state=active',{waitUntil:'networkidle'});
  await expect(page.locator('[data-home-meeting]')).toBeInViewport();
  await expect(page.locator('.student-mobile-nav')).toBeVisible();
  await expect(page.locator('.student-mobile-nav a')).toHaveCount(5);
  await expect(page.locator('[data-home-updates]')).toBeVisible();
});
