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

function overlaps(a,b){
  return a.x<b.x+b.width&&a.x+a.width>b.x&&a.y<b.y+b.height&&a.y+a.height>b.y;
}

test('inline insertion UI is editor-only and does not enter page content',async({page})=>{
  const frame=await fixture(page);
  await expect(frame.locator('[data-cms-page-main] .cms-inline-layer')).toHaveCount(0);
  await expect(frame.locator('body > .cms-inline-layer[data-cms-editor-ui="1"]')).toHaveCount(1);
  await expect(frame.locator('.cms-inline-add')).toContainText(['Adicionar conteúdo']);
});

test('empty column can receive a paragraph directly from the canvas',async({page})=>{
  const frame=await fixture(page);
  await frame.locator('#column-b').click({position:{x:20,y:20}});
  await expect(frame.locator('.cms-inline-add')).toHaveCount(1);
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

test('selected component exposes separated before and after insertion points',async({page})=>{
  const frame=await fixture(page);
  await frame.locator('#divider').evaluate(el=>el.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true})));
  const before=frame.locator('.cms-inline-add', {hasText:'+ Antes'});
  const after=frame.locator('.cms-inline-add', {hasText:'+ Depois'});
  await expect(before).toHaveCount(1);
  await expect(after).toHaveCount(1);
  await page.waitForTimeout(180);
  const beforeBox=await before.boundingBox();
  const afterBox=await after.boundingBox();
  expect(beforeBox).not.toBeNull();
  expect(afterBox).not.toBeNull();
  expect(overlaps(beforeBox,afterBox)).toBe(false);

  await before.click();
  await frame.locator('.cms-inline-palette [data-inline-type="heading"]').click();
  expect(await frame.locator('#divider').evaluate(el=>el.previousElementSibling?.dataset.cmsComponent||'')).toBe('heading');
  await page.waitForTimeout(1000);
  expect(await frame.locator('#divider').evaluate(el=>el.previousElementSibling?.dataset.cmsComponent||'')).toBe('heading');
});

test('structure sidebar exposes the page hierarchy and selects nested components',async({page})=>{
  const frame=await fixture(page);
  await page.locator('[data-structure-view="tree"]').click();
  await expect(page.locator('#structure-panel-title')).toHaveText('Estrutura da página');
  await expect(page.locator('#page-structure-tree')).toBeVisible();
  await expect(page.locator('#page-structure-tree [data-tree-level="section"] .cms-page-tree-main strong')).toHaveText('Fixture');
  await expect(page.locator('#page-structure-tree [data-tree-level="node"]')).toHaveCount(5);
  const tree=page.locator('#page-structure-tree');
  for(const label of ['Grupo de colunas','Coluna 1','Texto existente','Coluna 2','Divisor'])await expect(tree).toContainText(label);

  await page.locator('#page-structure-tree [data-tree-level="node"] .cms-page-tree-main', {hasText:'Texto existente'}).click();
  await expect(frame.locator('#column-a [data-cms-component="paragraph"]')).toHaveClass(/cms-structure-selected/);
  await expect(page.locator('#page-structure-tree [data-tree-level="node"].is-selected .cms-page-tree-main')).toContainText('Texto existente');

  await page.locator('#page-structure-tree [data-page-tree-toggle="0"]').click();
  await expect(page.locator('#page-structure-tree [data-tree-level="node"]')).toHaveCount(0);
  await page.locator('#page-structure-tree [data-page-tree-toggle="0"]').click();
  await expect(page.locator('#page-structure-tree [data-tree-level="node"]')).toHaveCount(5);
});

test('rich component palette inserts list quote video and gallery as first-class nodes',async({page})=>{
  const frame=await fixture(page);
  await frame.locator('#column-b').click({position:{x:20,y:20}});

  for(const [type,label] of [['list','Lista'],['quote','Citação'],['video','Vídeo'],['gallery','Galeria']]){
    await frame.locator('.cms-inline-add', {hasText:/Adicionar|Conteúdo/}).first().click();
    const palette=frame.locator('.cms-inline-palette');
    await expect(palette).toBeVisible();
    await expect(palette.locator(`[data-rich-inline-type="${type}"]`)).toHaveText(label);
    await palette.locator(`[data-rich-inline-type="${type}"]`).click();
    await expect(frame.locator(`#column-b [data-cms-component="${type}"]`)).toHaveCount(1);
    await page.waitForTimeout(850);
    await frame.locator('#column-b').click({position:{x:20,y:20}});
  }

  await expect(frame.locator('#column-b [data-cms-component="list"] li[data-cms-editable]')).toHaveCount(3);
  await expect(frame.locator('#column-b [data-cms-component="quote"] [data-cms-editable]')).toHaveCount(2);
  await expect(frame.locator('#column-b [data-cms-component="video"] video')).toHaveAttribute('controls','');
  await expect(frame.locator('#column-b [data-cms-component="gallery"] [data-cms-image-placeholder]')).toHaveCount(3);
});

test('rich components persist and are named in the structure tree',async({page})=>{
  const frame=await fixture(page);
  await frame.locator('#column-b').click({position:{x:20,y:20}});
  await frame.locator('.cms-inline-add', {hasText:'+ Adicionar'}).click();
  await frame.locator('.cms-inline-palette [data-rich-inline-type="list"]').click();
  await page.waitForTimeout(950);

  await page.locator('[data-structure-view="tree"]').click();
  const tree=page.locator('#page-structure-tree');
  await expect(tree).toContainText('Lista');
  await page.locator('#page-structure-tree .cms-page-tree-main', {hasText:'Lista'}).click();
  await expect(frame.locator('[data-cms-component="list"]')).toHaveClass(/cms-structure-selected/);
  await expect(page.locator('#inspector')).toContainText('Lista');
  await expect(page.locator('#inspector [data-rich-list-add]')).toHaveCount(1);
  await page.locator('#inspector [data-rich-list-add]').click();
  await page.waitForTimeout(950);
  await expect(frame.locator('[data-cms-component="list"] li[data-cms-editable]')).toHaveCount(4);
});
