const {test,expect}=require('@playwright/test');

test('Design additional CSS wins over generated tokens and system specificity',async({page})=>{
  await page.goto('http://127.0.0.1:8099/tools/browser-fixture/design-cascade.html');
  const frame=page.frameLocator('#design-preview-frame');
  const probe=frame.locator('#probe');

  await expect.poll(()=>probe.evaluate(el=>getComputedStyle(el).fontSize)).toBe('31px');
  await expect.poll(()=>probe.evaluate(el=>getComputedStyle(el).fontWeight)).toBe('400');
  await expect.poll(()=>frame.locator('html').evaluate(el=>el.style.getPropertyValue('--cms-body-size'))).toBe('');
  await expect.poll(()=>frame.locator('head').evaluate(head=>head.lastElementChild?.id||'')).toBe('cms-live-custom');
  await expect.poll(()=>frame.locator('#cms-live-design-vars').evaluate(el=>el.textContent)).toContain('@layer cms-system');
  await expect.poll(()=>frame.locator('#cms-live-design-vars').evaluate(el=>el.textContent)).toContain('--cms-body-size:17px');

  const custom=page.locator('textarea[name="advanced[customCss]"]');
  await custom.fill(':root{--cms-body-size:29px}div{font-weight:350}');
  await expect.poll(()=>probe.evaluate(el=>getComputedStyle(el).fontSize)).toBe('29px');
  await expect.poll(()=>probe.evaluate(el=>getComputedStyle(el).fontWeight)).toBe('350');
  await expect.poll(()=>frame.locator('head').evaluate(head=>head.lastElementChild?.id||'')).toBe('cms-live-custom');
});

test('public additional CSS outranks more specific system selectors',async({page})=>{
  await page.goto('http://127.0.0.1:8099/tools/browser-fixture/public-css-cascade.html');
  const probe=page.locator('#probe');
  await expect(probe).toBeVisible();
  await expect.poll(()=>probe.evaluate(el=>getComputedStyle(el).backgroundColor)).toBe('rgb(7, 8, 9)');
  await expect.poll(()=>probe.evaluate(el=>getComputedStyle(el).color)).toBe('rgb(1, 2, 3)');
  await expect.poll(()=>page.locator('head').evaluate(head=>head.lastElementChild?.id||'')).toBe('cms-custom-css');
});
