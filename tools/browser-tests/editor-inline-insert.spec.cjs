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
  for(const [type,label] of [['list','Lista'],['quote','Citação'],['video','Vídeo'],['gallery','Galeria']]){
    const frame=await fixture(page);
    await frame.locator('#column-b').click({position:{x:20,y:20}});
    await frame.locator('.cms-inline-add', {hasText:'+ Adicionar'}).click();
    const palette=frame.locator('.cms-inline-palette');
    await expect(palette).toBeVisible();
    for(const [buttonType,buttonLabel] of [['list','Lista'],['quote','Citação'],['video','Vídeo'],['gallery','Galeria']]){
      await expect(palette.locator(`[data-rich-inline-type="${buttonType}"]`)).toHaveText(buttonLabel);
    }
    await palette.locator(`[data-rich-inline-type="${type}"]`).click();
    await expect(frame.locator(`#column-b [data-cms-component="${type}"]`)).toHaveCount(1);
    await page.waitForTimeout(850);
    await expect(frame.locator(`#column-b [data-cms-component="${type}"]`)).toHaveCount(1);
    if(type==='list')await expect(frame.locator('#column-b [data-cms-component="list"] li[data-cms-editable]')).toHaveCount(3);
    if(type==='quote')await expect(frame.locator('#column-b [data-cms-component="quote"] [data-cms-editable]')).toHaveCount(2);
    if(type==='video')await expect(frame.locator('#column-b [data-cms-component="video"] video')).toHaveAttribute('controls','');
    if(type==='gallery')await expect(frame.locator('#column-b [data-cms-component="gallery"] [data-cms-image-placeholder]')).toHaveCount(3);
  }
});

test('rich components persist, are named in the tree and expose their own controls',async({page})=>{
  const frame=await fixture(page);
  await frame.locator('#column-b').click({position:{x:20,y:20}});
  await frame.locator('.cms-inline-add', {hasText:'+ Adicionar'}).click();
  await frame.locator('.cms-inline-palette [data-rich-inline-type="list"]').click();
  await page.waitForTimeout(950);

  await page.locator('[data-structure-view="tree"]').click();
  await expect(page.locator('#page-structure-tree')).toContainText('Lista');

  await frame.locator('[data-cms-component="list"]').evaluate(el=>el.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true})));
  await expect(frame.locator('[data-cms-component="list"]')).toHaveClass(/cms-structure-selected/);
  await expect(page.locator('#inspector')).toContainText('Lista');
  await expect(page.locator('#inspector [data-rich-list-add]')).toHaveCount(1);
  await page.locator('#inspector').evaluate(el=>{el.style.display='block'});
  await page.locator('#inspector [data-rich-list-add]').click();
  await page.waitForTimeout(950);
  await expect(frame.locator('[data-cms-component="list"] li[data-cms-editable]')).toHaveCount(4);
});

test('structure tree drag moves a component into another column and persists',async({page})=>{
  const frame=await fixture(page);
  await page.locator('[data-structure-view="tree"]').click();
  const paragraphRow=page.locator('#page-structure-tree .cms-page-tree-row[data-tree-level="node"]',{hasText:'Texto existente'});
  const columnBRow=page.locator('#page-structure-tree .cms-page-tree-row[data-tree-level="node"]',{hasText:'Coluna 2'});
  await expect(paragraphRow.locator('.cms-page-tree-grip')).toBeVisible();
  await paragraphRow.locator('.cms-page-tree-grip').dragTo(columnBRow);
  await page.waitForTimeout(1050);
  await expect(frame.locator('#column-a > [data-cms-component="paragraph"]')).toHaveCount(0);
  await expect(frame.locator('#column-b > [data-cms-component="paragraph"]')).toHaveCount(1);
});

test('canvas move handle transfers a selected component directly between containers',async({page})=>{
  const frame=await fixture(page);
  await frame.locator('#divider').evaluate(el=>el.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true})));
  const handle=frame.locator('.cms-direct-move-handle');
  await expect(handle).toBeVisible();
  await expect(frame.locator('[data-cms-page-main] .cms-direct-move-handle')).toHaveCount(0);
  await handle.dragTo(frame.locator('#column-b'));
  await page.waitForTimeout(1050);
  await expect(frame.locator('#column-b > #divider')).toHaveCount(1);
  await expect(frame.locator('section > #divider')).toHaveCount(0);
});
