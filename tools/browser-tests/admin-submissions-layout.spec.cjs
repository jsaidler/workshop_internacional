const {test,expect}=require('@playwright/test');

test('submissions workspace uses the available width without crushing registration values',async({page})=>{
  await page.setViewportSize({width:1600,height:900});
  await page.goto('http://127.0.0.1:8099/tools/browser-fixture/admin-submissions-layout.html');

  const content=page.locator('.admin-content');
  const contextFirst=page.locator('#context-first');
  const list=page.locator('.inbox-list');
  const detail=page.locator('.inbox-detail');
  const email=page.locator('[data-field="email"] dd');
  const contactFields=page.locator('#contact-group .inbox-fields');
  const selected=page.locator('.submission-row.selected');
  const pending=page.locator('.registration-admin-status.is-pending');

  const contentBox=await content.boundingBox();
  const contextBox=await contextFirst.boundingBox();
  const listBox=await list.boundingBox();
  const detailBox=await detail.boundingBox();
  expect(contentBox.width).toBeGreaterThan(1200);
  expect(Math.abs(contentBox.x-contextBox.x)).toBeLessThanOrEqual(2);
  expect(listBox.width).toBeGreaterThanOrEqual(300);
  expect(listBox.width).toBeLessThanOrEqual(345);
  expect(detailBox.width).toBeGreaterThan(850);
  expect(listBox.height).toBeLessThan(200);
  expect(detailBox.height).toBeGreaterThan(listBox.height*2);
  await expect(detail).toHaveCSS('overflow-y','visible');
  await expect(detail).toHaveCSS('max-height','none');

  await expect.poll(()=>contactFields.evaluate(el=>getComputedStyle(el).gridTemplateColumns.split(' ').length)).toBe(2);
  const emailBox=await email.boundingBox();
  expect(emailBox.width).toBeGreaterThan(300);
  await expect(email).toHaveCSS('white-space','nowrap');
  await expect.poll(()=>email.evaluate(el=>el.scrollWidth<=el.clientWidth)).toBe(true);

  const selectedBg=await selected.evaluate(el=>getComputedStyle(el).backgroundColor);
  const pendingBg=await pending.evaluate(el=>getComputedStyle(el).backgroundColor);
  expect(selectedBg).not.toBe(pendingBg);
  await expect(page.locator('.submission-row-meta small[data-status="new"]')).toHaveCSS('color','rgb(135, 84, 15)');
  await expect(page.locator('.admin-wordmark-context')).toHaveCSS('display','block');
});

test('registration fields and master-detail collapse before values become unreadable',async({page})=>{
  await page.setViewportSize({width:1000,height:900});
  await page.goto('http://127.0.0.1:8099/tools/browser-fixture/admin-submissions-layout.html');
  const fields=page.locator('#contact-group .inbox-fields');
  await expect.poll(()=>fields.evaluate(el=>getComputedStyle(el).gridTemplateColumns.split(' ').length)).toBe(1);
  const email=page.locator('[data-field="email"] dd');
  const emailBox=await email.boundingBox();
  expect(emailBox.width).toBeGreaterThan(250);
  await expect.poll(()=>email.evaluate(el=>el.scrollWidth<=el.clientWidth)).toBe(true);

  await page.setViewportSize({width:800,height:900});
  await expect.poll(()=>page.locator('.inbox-layout').evaluate(el=>getComputedStyle(el).gridTemplateColumns.split(' ').length)).toBe(1);
  await expect(page.locator('.inbox-list')).toHaveCSS('position','static');
});
