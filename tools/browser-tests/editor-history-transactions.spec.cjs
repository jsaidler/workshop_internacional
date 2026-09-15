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

async function commit(page){
  await page.evaluate(()=>window.CmsEditorHistory.commit());
}

test('duplicate and remove remain undoable across preview reloads',async({page})=>{
  const frame=await fixture(page);

  // Keep one real editor action in this regression: duplicate saves and reloads the preview.
  await selectTreeNode(page,'Divisor');
  await page.locator('#inspector [data-block-duplicate]').click();
  await expect(frame.locator('[data-cms-component="divider"]')).toHaveCount(2);
  await expect(page.locator('#undo')).toBeEnabled();

  await page.locator('#undo').click();
  await expect(frame.locator('[data-cms-component="divider"]')).toHaveCount(1);
  await expect(page.locator('#redo')).toBeEnabled();

  await page.locator('#redo').click();
  await expect(frame.locator('[data-cms-component="divider"]')).toHaveCount(2);

  // Return to one divider, then exercise the public transaction API directly.
  // Component removal itself has separate UI regressions; this test owns history semantics.
  await page.locator('#undo').click();
  await expect(frame.locator('[data-cms-component="divider"]')).toHaveCount(1);
  await frame.locator('#divider').evaluate(node=>node.remove());
  await commit(page);
  await expect(frame.locator('[data-cms-component="divider"]')).toHaveCount(0);
  await expect(page.locator('#undo')).toBeEnabled();

  await page.locator('#undo').click();
  await expect(frame.locator('[data-cms-component="divider"]')).toHaveCount(1);
});

test('insert and move between containers can be undone and redone',async({page})=>{
  const frame=await fixture(page);

  await frame.locator('#column-b').evaluate(column=>{
    const paragraph=column.ownerDocument.createElement('p');
    paragraph.dataset.cmsComponent='paragraph';
    paragraph.dataset.cmsEditable='';
    paragraph.dataset.cmsNodeId='history-added-paragraph';
    paragraph.textContent='Novo parágrafo.';
    column.append(paragraph);
  });
  await commit(page);
  await expect(frame.locator('#column-b > [data-cms-node-id="history-added-paragraph"]')).toHaveCount(1);
  await expect(page.locator('#undo')).toBeEnabled();

  await page.locator('#undo').click();
  await expect(frame.locator('#column-b > [data-cms-node-id="history-added-paragraph"]')).toHaveCount(0);
  await page.locator('#redo').click();
  await expect(frame.locator('#column-b > [data-cms-node-id="history-added-paragraph"]')).toHaveCount(1);

  await frame.locator('#divider').evaluate(divider=>{
    divider.ownerDocument.querySelector('#column-b').append(divider);
  });
  await commit(page);
  await expect(frame.locator('#column-b > #divider')).toHaveCount(1);

  await page.locator('#undo').click();
  await expect(frame.locator('#column-b > #divider')).toHaveCount(0);
  await expect(frame.locator('#divider')).toHaveCount(1);
  await page.locator('#redo').click();
  await expect(frame.locator('#column-b > #divider')).toHaveCount(1);
});

test('editor-only rename participates in history and a new edit clears redo',async({page})=>{
  const frame=await fixture(page);

  // Rename still goes through the actual editor UI because it is one of the integrations
  // this history layer explicitly owns.
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

  // A new transaction after undo must discard the old redo branch.
  await frame.locator('#column-b').evaluate(column=>{
    const divider=column.ownerDocument.createElement('div');
    divider.dataset.cmsComponent='divider';
    divider.dataset.cmsNodeId='history-new-divider';
    divider.className='cms-component-divider';
    divider.innerHTML='<hr>';
    column.append(divider);
  });
  await commit(page);
  await expect(frame.locator('#column-b > [data-cms-node-id="history-new-divider"]')).toHaveCount(1);
  await expect(page.locator('#redo')).toBeDisabled();
});
