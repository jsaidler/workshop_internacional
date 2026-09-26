const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-inline-insert.html';

test('inline palette stays above editor overlays and pointer activation inserts content',async({page})=>{
  await page.setViewportSize({width:1600,height:1300});
  await page.goto(url);
  await page.locator('#page-frame').scrollIntoViewIfNeeded();
  const frame=page.frameLocator('#page-frame');
  await expect(frame.locator('[data-cms-page-main]')).toBeVisible();
  await expect(frame.locator('style[data-cms-inline-reliability="1"]')).toHaveCount(1);

  await frame.locator('#column-b').click({position:{x:20,y:20}});
  await frame.locator('.cms-inline-add',{hasText:'+ Adicionar'}).click();
  const palette=frame.locator('.cms-inline-palette');
  await expect(palette).toBeVisible();
  const layerZ=await frame.locator('.cms-inline-layer').evaluate(el=>Number(getComputedStyle(el).zIndex));
  expect(layerZ).toBeGreaterThan(2147483200);

  const box=await palette.locator('[data-inline-type="paragraph"]').boundingBox();
  expect(box).not.toBeNull();
  expect(box.x).toBeGreaterThanOrEqual(0);
  expect(box.y).toBeGreaterThanOrEqual(0);
  expect(box.x+box.width).toBeLessThanOrEqual(1600);
  expect(box.y+box.height).toBeLessThanOrEqual(1300);
  await palette.locator('[data-inline-type="paragraph"]').click();
  await expect(frame.locator('#column-b [data-cms-component="paragraph"]')).toHaveCount(1);
});
