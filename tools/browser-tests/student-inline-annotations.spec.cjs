const {test,expect}=require('@playwright/test');
const url='http://127.0.0.1:8099/tools/browser-fixture/student-inline-annotations.html';

async function selectSubstring(page,selector,needle){
  await page.evaluate(({selector,needle})=>{
    const block=document.querySelector(selector);if(!block)throw new Error('block missing');
    const walker=document.createTreeWalker(block,NodeFilter.SHOW_TEXT);let node=null,index=-1;
    while(walker.nextNode()){const candidate=walker.currentNode;index=(candidate.nodeValue||'').indexOf(needle);if(index>=0){node=candidate;break;}}
    if(!node)throw new Error('text missing');
    const range=document.createRange();range.setStart(node,index);range.setEnd(node,index+needle.length);
    const selection=window.getSelection();selection.removeAllRanges();selection.addRange(range);
    block.dispatchEvent(new MouseEvent('mouseup',{bubbles:true,clientX:100,clientY:100}));
  },{selector,needle});
}

test('annotations relink to stable text and preserve altered or removed sources',async({page})=>{
  await page.goto(url,{waitUntil:'networkidle'});
  await expect(page.locator('mark.student-inline-note-mark')).toHaveCount(1);
  await expect(page.locator('mark.student-inline-note-mark')).toContainText('fotografia experimental exige atenção ao processo');
  await expect(page.locator('[data-annotation-status="1"]')).toHaveText('Vinculada ao texto.');
  await expect(page.locator('[data-annotation-status="2"]')).toContainText('Trecho alterado');
  await expect(page.locator('[data-annotation-status="3"]')).toContainText('Trecho original removido');
  await page.locator('mark.student-inline-note-mark').click();
  await expect(page.locator('[data-student-notes-panel]')).toHaveAttribute('open','');
});

test('selecting source text opens an annotation composer with redundant anchors',async({page})=>{
  await page.goto(url,{waitUntil:'networkidle'});
  await selectSubstring(page,'[data-student-anchor-block="introducao:p:2"]','trecho permanece disponível');
  const action=page.locator('.student-selection-note-action');
  await expect(action).toBeVisible();await expect(action).toHaveText('Anotar');await action.click();
  const compose=page.locator('[data-inline-note-compose]');
  await expect(compose).toBeVisible();
  await expect(compose.locator('[data-anchor-preview]')).toHaveText('trecho permanece disponível');
  await expect(compose.locator('[data-anchor-section]')).toHaveValue('introducao');
  await expect(compose.locator('[data-anchor-block]')).toHaveValue('introducao:p:2');
  await expect(compose.locator('[data-anchor-exact]')).toHaveValue('trecho permanece disponível');
  await expect(compose.locator('[data-anchor-prefix]')).not.toHaveValue('');
  await expect(compose.locator('[data-anchor-suffix]')).not.toHaveValue('');
  await expect(compose.locator('[data-anchor-hash]')).not.toHaveValue('');
  await expect(compose.locator('[data-annotation-action]')).toHaveValue('create_selection');
});

test('orphaned notes can be reassociated without changing their body',async({page})=>{
  await page.goto(url,{waitUntil:'networkidle'});
  await page.locator('.student-notes-entry a').click();
  await expect(page.locator('[data-student-notes-panel]')).toHaveAttribute('open','');
  await page.locator('[data-annotation-item="2"]').evaluate(element=>element.scrollIntoView({block:'center'}));
  await page.locator('[data-annotation-reanchor="2"]').dispatchEvent('click');
  await expect(page.locator('.student-reanchor-hint')).toBeVisible();
  await selectSubstring(page,'[data-student-anchor-block="processo:p:1"]','parágrafo foi alterado');
  const action=page.locator('.student-selection-note-action');await expect(action).toHaveText('Reassociar');await action.click();
  const compose=page.locator('[data-inline-note-compose]');
  await expect(compose).toBeVisible();
  await expect(compose.locator('[data-annotation-action]')).toHaveValue('reanchor');
  await expect(compose.locator('[data-annotation-id]')).toHaveValue('2');
  await expect(compose.locator('[data-anchor-preview]')).toHaveText('parágrafo foi alterado');
  await expect(compose.locator('[data-inline-note-body]')).toBeHidden();
  await expect(compose.locator('[data-inline-note-submit]')).toHaveText('Confirmar reassociação');
});
