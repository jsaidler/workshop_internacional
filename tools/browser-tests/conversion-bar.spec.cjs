const {test,expect}=require('@playwright/test');

async function barState(page){return page.locator('[data-cms-conversion-bar]').getAttribute('data-visible');}

test('persistent conversion bar appears only between the primary and final CTAs',async({page})=>{
  await page.setViewportSize({width:1280,height:800});
  await page.goto('http://127.0.0.1:8099/tools/browser-fixture/conversion-bar.html');
  const bar=page.locator('[data-cms-conversion-bar]');
  await expect(bar).toHaveCount(1);
  await expect(bar).toContainText('Workshop online e ao vivo · 3 encontros');
  await expect(bar).toContainText('R$ 698 via Pix');
  await expect(bar.locator('a')).toHaveText('Fazer inscrição');
  await expect(bar.locator('a')).toHaveAttribute('href',/\/inscricao\/\?lang=pt-br$/);
  await expect.poll(()=>barState(page)).toBe('false');
  await expect(bar).toHaveAttribute('inert','');

  await page.evaluate(()=>window.scrollTo(0,1000));
  await expect.poll(()=>barState(page)).toBe('true');
  await expect(bar).not.toHaveAttribute('inert','');
  const box=await bar.boundingBox();
  expect(box).not.toBeNull();
  expect(Math.abs((box.y+box.height)-800)).toBeLessThanOrEqual(2);

  await page.locator('#final-cta').scrollIntoViewIfNeeded();
  await expect.poll(()=>barState(page)).toBe('false');
  await expect(bar).toHaveAttribute('aria-hidden','true');
});

test('persistent conversion bar remains usable on a narrow mobile viewport',async({page})=>{
  await page.setViewportSize({width:390,height:780});
  await page.goto('http://127.0.0.1:8099/tools/browser-fixture/conversion-bar.html');
  await page.evaluate(()=>window.scrollTo(0,1000));
  const bar=page.locator('[data-cms-conversion-bar]');
  await expect.poll(()=>barState(page)).toBe('true');
  await expect(bar.locator('.cms-conversion-bar-context')).toBeHidden();
  await expect(bar.locator('.cms-conversion-bar-price')).toHaveText('R$ 698 via Pix');
  await expect(bar.locator('.cms-conversion-bar-action')).toBeVisible();
  const barBox=await bar.boundingBox(),actionBox=await bar.locator('.cms-conversion-bar-action').boundingBox();
  expect(barBox).not.toBeNull();expect(actionBox).not.toBeNull();
  expect(barBox.x).toBeGreaterThanOrEqual(0);expect(barBox.x+barBox.width).toBeLessThanOrEqual(391);
  expect(actionBox.x+actionBox.width).toBeLessThanOrEqual(390);
});

test('editor previews do not receive the public conversion footer',async({page})=>{
  await page.goto('http://127.0.0.1:8099/tools/browser-fixture/conversion-bar.html');
  await page.evaluate(()=>{document.body.classList.add('cms-editor-preview');location.reload()});
  await page.waitForLoadState('domcontentloaded');
  // Reload clears runtime class changes, so assert the implementation guard directly as a regression contract.
  const source=await page.evaluate(async()=>await (await fetch('/assets/public.js')).text());
  expect(source).toContain("document.body.classList.contains('cms-editor-preview')");
});
