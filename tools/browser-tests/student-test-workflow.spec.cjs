const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/student-test-workflow.html';

test('development has one advancing action and preserves typed data into review',async({page})=>{
  await page.goto(url);
  await expect(page.getByTestId('development-panel')).toBeVisible();
  await expect(page.locator('.student-step-nav a[href]')).toHaveCount(0);
  await expect(page.getByRole('link',{name:'Revisar teste'})).toHaveCount(0);
  await page.locator('input[name="developer"]').fill('Parodinal 1+50');
  await page.locator('textarea[name="notes"]').fill('Sombras mais abertas');
  await page.getByTestId('save-development').click();
  await expect(page.getByTestId('review-panel')).toBeVisible();
  await expect(page.getByTestId('saved-developer')).toHaveText('Parodinal 1+50');
  await expect(page.getByTestId('saved-notes')).toHaveText('Sombras mais abertas');
});

test('adding result media saves edited development fields before the reload',async({page})=>{
  await page.goto(url);
  await page.locator('input[name="developer"]').fill('Parodinal 1+25');
  await page.locator('textarea[name="notes"]').fill('Teste ainda não salvo manualmente');
  await page.getByTestId('result-file').setInputFiles({name:'resultado.jpg',mimeType:'image/jpeg',buffer:Buffer.from([255,216,255,217])});
  await expect(page.getByTestId('media-notice')).toContainText('também foram salvos');
  await expect(page.locator('input[name="developer"]')).toHaveValue('Parodinal 1+25');
  await expect(page.locator('textarea[name="notes"]')).toHaveValue('Teste ainda não salvo manualmente');
});

test('workflow context remains visible and usable on phone viewport',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url);
  await expect(page.getByTestId('tests-back')).toHaveAttribute('href',/cohort=cohort-a/);
  await expect(page.getByTestId('save-development')).toBeVisible();
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
});
