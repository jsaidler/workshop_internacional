const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-inline-insert.html';

async function fixture(page){
  await page.setViewportSize({width:1280,height:900});
  await page.goto(url);
  const frame=page.frameLocator('#page-frame');
  await expect(frame.locator('[data-cms-page-main]')).toBeVisible();
  await expect(frame.locator('.cms-inline-layer')).toHaveCount(1);
  return frame;
}

test('inline insertion UI is editor-only and does not enter page content',async({page})=>{
  const frame=await fixture(page);
  await expect(frame.locator('[data-cms-page-main] .cms-inline-layer')).toHaveCount(0);
  await expect(frame.locator('body > .cms-inline-layer[data-cms-editor-ui="1"]')).toHaveCount(1);
  await expect(frame.locator('.cms-inline-add')).toContainText(['Adicionar conteúdo']);
});

test('empty column can receive a paragraph directly from the canvas',async({page})=>{
  const frame=await fixture(page);
  await frame.locator('#columns').click({position:{x:10,y:10}});
  await expect(frame.locator('.cms-inline-add')).toHaveCount(2);
  await expect(frame.locator('.cms-inline-add', {hasText:'+ Adicionar'})).toHaveCount(1);

  await frame.locator('.cms-inline-add', {hasText:'+ Adicionar'}).click();
  await expect(frame.locator('.cms-inline-palette')).toBeVisible();
  await frame.locator('.cms-inline-palette [data-inline-type="paragraph"]').click();

  await expect(frame.locator('#column-b [data-cms-component="paragraph"]')).toHaveCount(1);
  await expect(frame.locator('#column-a [data-cms-component="paragraph"]')).toHaveCount(1);
  await page.waitForTimeout(1000);
  await expect(frame.locator('#column-b [data-cms-component="paragraph"]')).toHaveCount(1);
  await expect(frame.locator('[data-cms-page-main] .cms-inline-layer')).toHaveCount(0);
});

test('selected component exposes before and after insertion points',async({page})=>{
  const frame=await fixture(page);
  await frame.locator('#divider').click({position:{x:10,y:5}});
  await expect(frame.locator('.cms-inline-add', {hasText:'+ Antes'})).toHaveCount(1);
  await expect(frame.locator('.cms-inline-add', {hasText:'+ Depois'})).toHaveCount(1);

  await frame.locator('.cms-inline-add', {hasText:'+ Antes'}).click();
  await frame.locator('.cms-inline-palette [data-inline-type="heading"]').click();
  await expect(frame.locator('#divider').evaluate(el=>el.previousElementSibling?.dataset.cmsComponent||'')).resolves.toBe('heading');
  await page.waitForTimeout(1000);
  await expect(frame.locator('#divider').evaluate(el=>el.previousElementSibling?.dataset.cmsComponent||'')).resolves.toBe('heading');
});
