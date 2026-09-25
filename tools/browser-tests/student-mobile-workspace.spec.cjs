const {test,expect}=require('@playwright/test');

const studentUrl='http://127.0.0.1:8099/tools/browser-fixture/student-mobile-workspace.html';
const adminUrl='http://127.0.0.1:8099/tools/browser-fixture/student-admin-private-media.html';

const overflow=async page=>page.evaluate(()=>document.documentElement.scrollWidth-window.innerWidth);

test('student test recorder behaves as a mobile application surface',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(studentUrl);

  await expect(page.locator('.student-bottom-nav')).toBeVisible();
  await expect(page.locator('.student-desktop-nav')).toBeHidden();
  await expect(page.locator('.student-test-progress a')).toHaveCount(3);
  await expect(page.getByText('Cena e exposição',{exact:true})).toBeVisible();
  await expect(page.getByText('Fotografar cena',{exact:true})).toBeVisible();

  const progress=await page.locator('.student-test-progress').boundingBox();
  expect(progress.x).toBeGreaterThanOrEqual(0);
  expect(progress.x+progress.width).toBeLessThanOrEqual(391);

  const capture=await page.locator('.student-capture-button').first().boundingBox();
  expect(capture.height).toBeGreaterThanOrEqual(47);
  expect(capture.width).toBeGreaterThan(130);

  const main=await page.locator('.student-main').boundingBox();
  expect(main.width).toBeGreaterThan(340);
  expect(await overflow(page)).toBeLessThanOrEqual(1);
});

test('student workspace remains site-like on desktop instead of a sparse parallel product',async({page})=>{
  await page.setViewportSize({width:1440,height:1000});
  await page.goto(studentUrl);

  await expect(page.locator('.student-desktop-nav')).toBeVisible();
  await expect(page.locator('.student-bottom-nav')).toBeHidden();
  await expect(page.locator('.student-wordmark')).toHaveText('João Saidler Fotografia');
  await expect(page.locator('.student-theme')).toBeVisible();

  const main=await page.locator('.student-main').boundingBox();
  expect(main.width).toBeGreaterThan(900);
  expect(main.width).toBeLessThanOrEqual(1181);
  expect(await overflow(page)).toBeLessThanOrEqual(1);
});

test('private media admin form never escapes its card',async({page})=>{
  for(const viewport of [{width:1440,height:900},{width:390,height:844}]){
    await page.setViewportSize(viewport);
    await page.goto(adminUrl);
    const form=page.locator('form.admin-form-grid[enctype="multipart/form-data"]');
    const card=page.locator('.admin-card');
    const formBox=await form.boundingBox();
    const cardBox=await card.boundingBox();
    expect(formBox.x).toBeGreaterThanOrEqual(cardBox.x-1);
    expect(formBox.x+formBox.width).toBeLessThanOrEqual(cardBox.x+cardBox.width+1);
    for(const selector of ['select[name="slot_key"]','input[name="media_title"]','input[name="media_file"]']){
      const box=await page.locator(selector).boundingBox();
      expect(box.x).toBeGreaterThanOrEqual(cardBox.x-1);
      expect(box.x+box.width).toBeLessThanOrEqual(cardBox.x+cardBox.width+1);
    }
    expect(await overflow(page)).toBeLessThanOrEqual(1);
  }
});
