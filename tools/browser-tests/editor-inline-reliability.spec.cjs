const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-inline-insert.html';

test('inline palette stays above editor overlays and pointer activation inserts content',async({page})=>{
  await page.setViewportSize({width:1280,height:900});
  await page.goto(url);
  const frame=page.frameLocator('#page-frame');
  await expect(frame.locator('[data-cms-page-main]')).toBeVisible();
  await expect(frame.locator('style[data-cms-inline-reliability="1"]')).toHaveCount(1);

  await frame.locator('#column-b').click({position:{x:20,y:20}});
  await frame.locator('.cms-inline-add',{hasText:'+ Adicionar'}).click();
  const palette=frame.locator('.cms-inline-palette');
  await expect(palette).toBeVisible();
  const layerZ=await frame.locator('.cms-inline-layer').evaluate(el=>Number(getComputedStyle(el).zIndex));
  expect(layerZ).toBeGreaterThan(2147483200);

  await palette.locator('[data-inline-type="paragraph"]').click();
  await expect(frame.locator('#column-b [data-cms-component="paragraph"]')).toHaveCount(1);
});
