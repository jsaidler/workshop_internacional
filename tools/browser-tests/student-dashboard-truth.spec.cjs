const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/student-dashboard-truth.html';

test('student dashboard distinguishes released, scheduled and blocked lessons',async({page})=>{
  await page.goto(url);
  await expect(page.getByTestId('progress')).toHaveText('1/3 aulas liberadas');
  await expect(page.getByTestId('lesson-released').locator('small')).toHaveText('liberada');
  await expect(page.getByTestId('lesson-scheduled').locator('small')).toHaveText('agendada');
  await expect(page.getByTestId('lesson-blocked').locator('small')).toHaveText('aguardando');
  await expect(page.getByTestId('lesson-scheduled')).not.toHaveClass(/is-released/);
  await expect(page.locator('.student-course-primary')).toHaveCount(3);
});

test('student dashboard remains usable on a phone viewport',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url);
  await expect(page.getByTestId('course-card')).toBeVisible();
  await expect(page.getByTestId('lesson-scheduled').locator('small')).toHaveText('agendada');
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
});
