const {test,expect}=require('@playwright/test');

test('Design uses Google Font selectors and additional CSS keeps final precedence',async({page})=>{
  await page.goto('http://127.0.0.1:8099/tools/browser-fixture/design-cascade.html');
  const frame=page.frameLocator('#design-preview-frame');
  const probe=frame.locator('#probe');
  const mono=frame.locator('#mono');

  await expect.poll(()=>probe.evaluate(el=>getComputedStyle(el).fontSize)).toBe('31px');
  await expect.poll(()=>probe.evaluate(el=>getComputedStyle(el).fontWeight)).toBe('400');
  await expect.poll(()=>frame.locator('body').evaluate(el=>getComputedStyle(el).fontFamily)).toContain('IBM Plex Sans');
  await expect.poll(()=>probe.evaluate(el=>getComputedStyle(el).fontFamily)).toContain('Saira Extra Condensed');
  await expect.poll(()=>mono.evaluate(el=>getComputedStyle(el).fontFamily)).toContain('IBM Plex Mono');
  await expect.poll(()=>frame.locator('#cms-live-google-fonts').getAttribute('href')).toContain('family=IBM+Plex+Sans');
  await expect.poll(()=>frame.locator('#cms-live-google-fonts').getAttribute('href')).toContain('family=Saira+Extra+Condensed');
  await expect.poll(()=>frame.locator('#cms-live-google-fonts').getAttribute('href')).toContain('family=IBM+Plex+Mono');
  await expect.poll(()=>frame.locator('html').evaluate(el=>el.style.getPropertyValue('--cms-body-size'))).toBe('');
  await expect.poll(()=>frame.locator('head').evaluate(head=>head.lastElementChild?.id||'')).toBe('cms-live-custom');
  await expect.poll(()=>frame.locator('#cms-live-design-vars').evaluate(el=>el.textContent)).toContain('@layer cms-system');
  await expect.poll(()=>frame.locator('#cms-live-design-vars').evaluate(el=>el.textContent)).toContain('--cms-body-size:17px');
  await expect.poll(()=>frame.locator('#cms-live-design-vars').evaluate(el=>el.textContent)).toContain('--body:"IBM Plex Sans", Arial, sans-serif');
  await expect.poll(()=>frame.locator('#cms-live-design-vars').evaluate(el=>el.textContent)).toContain('--title:"Saira Extra Condensed", "Arial Narrow", Arial, sans-serif');
  await expect.poll(()=>frame.locator('#cms-live-design-vars').evaluate(el=>el.textContent)).toContain('--mono:"IBM Plex Mono", Consolas, monospace');

  const displayFont=page.locator('select[name="type[displayFont]"]');
  await displayFont.selectOption('Oswald');
  await expect.poll(()=>probe.evaluate(el=>getComputedStyle(el).fontFamily)).toContain('Oswald');
  await expect.poll(()=>frame.locator('#cms-live-google-fonts').getAttribute('href')).toContain('family=Oswald');
  await expect.poll(()=>frame.locator('#cms-live-google-fonts').getAttribute('href')).not.toContain('family=Saira+Extra+Condensed');

  const custom=page.locator('textarea[name="advanced[customCss]"]');
  await custom.fill(':root{--cms-body-size:29px}div{font-weight:350}');
  await expect.poll(()=>probe.evaluate(el=>getComputedStyle(el).fontSize)).toBe('29px');
  await expect.poll(()=>probe.evaluate(el=>getComputedStyle(el).fontWeight)).toBe('350');
  await expect.poll(()=>frame.locator('head').evaluate(head=>head.lastElementChild?.id||'')).toBe('cms-live-custom');
});

test('public additional CSS outranks system and dynamically loaded registration CSS',async({page})=>{
  await page.goto('http://127.0.0.1:8099/tools/browser-fixture/public-css-cascade.html');
  const probe=page.locator('#probe');
  const registration=page.locator('#registration-probe');
  await expect(probe).toBeVisible();
  await expect(registration).toBeVisible();
  await expect.poll(()=>probe.evaluate(el=>getComputedStyle(el).backgroundColor)).toBe('rgb(7, 8, 9)');
  await expect.poll(()=>probe.evaluate(el=>getComputedStyle(el).color)).toBe('rgb(1, 2, 3)');
  await expect.poll(()=>registration.evaluate(el=>getComputedStyle(el).borderTopWidth)).toBe('0px');
  await expect.poll(()=>page.locator('[data-registration-css]').evaluate(el=>el.textContent)).toContain('layer(cms-system)');
  await expect(page.locator('link[data-registration-css]')).toHaveCount(0);
  await expect(page.locator('link[data-cms-responsive]')).toHaveCount(0);
});
