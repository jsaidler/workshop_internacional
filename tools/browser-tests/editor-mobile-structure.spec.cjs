const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-mobile-structure.html?page=1';

test('mobile editor keeps page structure reachable without covering the canvas permanently',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url);
  const toggle=page.locator('#editor-structure-mobile');
  const panel=page.locator('#editor-structure-panel');
  const backdrop=page.locator('#editor-structure-backdrop');

  await expect(toggle).toBeVisible();
  await expect(toggle).toHaveAttribute('aria-expanded','false');
  expect((await panel.boundingBox()).x).toBeLessThan(0);

  await toggle.click();
  await expect(toggle).toHaveAttribute('aria-expanded','true');
  await expect(page.locator('body')).toHaveClass(/structure-mobile-open/);
  await expect(backdrop).toBeVisible();
  await expect.poll(async()=>Math.round((await panel.boundingBox()).x)).toBeGreaterThanOrEqual(0);

  await page.locator('.section-row-main').click();
  await expect(page.locator('body')).not.toHaveClass(/structure-mobile-open/);
  await expect(toggle).toHaveAttribute('aria-expanded','false');
});

test('mobile structure drawer closes with escape and desktop layout stays unchanged',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url);
  const toggle=page.locator('#editor-structure-mobile');
  await toggle.click();
  await page.keyboard.press('Escape');
  await expect(page.locator('body')).not.toHaveClass(/structure-mobile-open/);

  await page.setViewportSize({width:1280,height:900});
  await expect(toggle).toBeHidden();
  await expect(page.locator('#editor-structure-panel')).toBeVisible();
  await expect.poll(async()=>Math.round((await page.locator('#editor-structure-panel').boundingBox()).x)).toBeGreaterThanOrEqual(0);
});
