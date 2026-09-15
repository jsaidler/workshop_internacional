const {test,expect}=require('@playwright/test');

const url='http://127.0.0.1:8099/tools/browser-fixture/editor-inline-insert.html';

async function fixture(page){
  await page.setViewportSize({width:1280,height:900});
  await page.goto(url);
  const frame=page.frameLocator('#page-frame');
  await expect(frame.locator('[data-cms-page-main]')).toBeVisible();
  await expect.poll(()=>page.evaluate(()=>window.CmsEditorHistory?.getState()?.length||0)).toBeGreaterThan(0);
  await page.locator('[data-structure-view="tree"]').click();
  await expect(page.locator('#page-structure-tree')).toBeVisible();
  return frame;
}

async function selectTreeNode(page,text,{last=false}={}){
  const matches=page.locator('#page-structure-tree [data-tree-level="node"] .cms-page-tree-main',{hasText:text});
  const target=last?matches.last():matches.first();
  await expect(target).toBeVisible();
  await target.click();
}

async function selectCanvasNode(frame,selector){
  await frame.locator(selector).evaluate(el=>el.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true})));
}

test('duplicate and remove remain undoable across preview reloads',async({page})=>{
  const frame=await fixture(page);
  page.on('dialog',dialog=>dialog.accept());

  await selectTreeNode(page,'Divisor');
  await page.locator('#inspector [data-block-duplicate]').click();
  await expect(frame.locator('[data-cms-component="divider"]')).toHaveCount(2);
  await expect(page.locator('#undo')).toBeEnabled();

  await page.locator('#undo').click();
  await expect(frame.locator('[data-cms-component="divider"]')).toHaveCount(1);
  await expect(page.locator('#redo')).toBeEnabled();

  await page.locator('#redo').click();
  await expect(frame.locator('[data-cms-component="divider"]')).toHaveCount(2);

  // Return to the unambiguous one-divider state, then verify removal as its own history transaction.
  await page.locator('#undo').click();
  await expect(frame.locator('[data-cms-component="divider"]')).toHaveCount(1);
  await selectCanvasNode(frame,'#divider');
  await expect(page.locator('#inspector [data-block-delete]')).toBeVisible();
  await page.locator('#inspector [data-block-delete]').click();
  await expect(frame.locator('[data-cms-component="divider"]')).toHaveCount(0);

  await page.locator('#undo').click();
  await expect(frame.locator('[data-cms-component="divider"]')).toHaveCount(1);
});

test('insert and move between containers can be undone and redone',async({page})=>{
  const frame=await fixture(page);

  await selectTreeNode(page,'Coluna 2');
  await page.locator('#inspector [data-add-component="paragraph"]').click();
  await expect(frame.locator('#column-b > [data-cms-component="paragraph"]')).toHaveCount(1);
  await expect(frame.locator('#column-b')).toContainText('Novo parágrafo.');

  await page.locator('#undo').click();
  await expect(frame.locator('#column-b > [data-cms-component="paragraph"]')).toHaveCount(0);
  await page.locator('#redo').click();
  await expect(frame.locator('#column-b > [data-cms-component="paragraph"]')).toHaveCount(1);

  // Reuse the canvas move path already exercised by the editor: move the divider into column 2.
  await selectCanvasNode(frame,'#divider');
  const handle=frame.locator('.cms-direct-move-handle');
  await expect(handle).toBeVisible();
  await handle.dragTo(frame.locator('#column-b'));
  await expect(frame.locator('#column-b > #divider')).toHaveCount(1);

  await page.locator('#undo').click();
  await expect(frame.locator('#column-b > #divider')).toHaveCount(0);
  await expect(frame.locator('#divider')).toHaveCount(1);
  await page.locator('#redo').click();
  await expect(frame.locator('#column-b > #divider')).toHaveCount(1);
});

test('editor-only rename participates in history and a new edit clears redo',async({page})=>{
  const frame=await fixture(page);

  await selectTreeNode(page,'Divisor');
  const input=page.locator('#inspector [data-cms-editor-label-input]');
  await input.fill('Separador editorial');
  await input.press('Enter');
  await expect(frame.locator('#divider')).toHaveAttribute('data-cms-editor-label','Separador editorial');
  await expect(page.locator('#undo')).toBeEnabled();

  await page.locator('#undo').click();
  await expect(frame.locator('#divider')).not.toHaveAttribute('data-cms-editor-label','Separador editorial');
  await expect(page.locator('#redo')).toBeEnabled();

  await page.locator('#redo').click();
  await expect(frame.locator('#divider')).toHaveAttribute('data-cms-editor-label','Separador editorial');

  await page.locator('#undo').click();
  await expect(page.locator('#redo')).toBeEnabled();
  await selectTreeNode(page,'Coluna 2');
  await page.locator('#inspector [data-add-component="divider"]').click();
  await expect(frame.locator('#column-b > [data-cms-component="divider"]')).toHaveCount(1);
  await expect(page.locator('#redo')).toBeDisabled();
});
