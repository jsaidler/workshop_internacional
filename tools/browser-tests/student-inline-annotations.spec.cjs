const {test,expect}=require('@playwright/test');
const url='http://127.0.0.1:8099/tools/browser-fixture/student-inline-annotations.html';

async function selectSubstring(page,selector,needle,{mouseUp=true,selectionChange=false}={}){
  await page.evaluate(({selector,needle,mouseUp,selectionChange})=>{
    const block=document.querySelector(selector);if(!block)throw new Error('block missing');
    const walker=document.createTreeWalker(block,NodeFilter.SHOW_TEXT);let node=null,index=-1;
    while(walker.nextNode()){const candidate=walker.currentNode;index=(candidate.nodeValue||'').indexOf(needle);if(index>=0){node=candidate;break;}}
    if(!node)throw new Error('text missing');
    const range=document.createRange();range.setStart(node,index);range.setEnd(node,index+needle.length);
    const selection=window.getSelection();selection.removeAllRanges();selection.addRange(range);
    if(mouseUp)block.dispatchEvent(new MouseEvent('mouseup',{bubbles:true,clientX:100,clientY:100}));
    if(selectionChange)document.dispatchEvent(new Event('selectionchange',{bubbles:true}));
  },{selector,needle,mouseUp,selectionChange});
}

async function scrollBlockIntoViewInstantly(block){
  await block.evaluate(element=>{
    const root=document.documentElement;
    const previous=root.style.scrollBehavior;
    root.style.scrollBehavior='auto';
    element.scrollIntoView({block:'center',behavior:'auto'});
    root.style.scrollBehavior=previous;
  });
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

test('selecting source text opens an annotation composer even if focus collapses the native selection before click',async({page})=>{
  await page.goto(url,{waitUntil:'networkidle'});
  await selectSubstring(page,'[data-student-anchor-block="introducao:p:2"]','trecho permanece disponível');
  const action=page.locator('.student-selection-note-action');
  await expect(action).toBeVisible();await expect(action).toHaveText('Anotar seleção');
  await page.evaluate(()=>{window.getSelection()?.removeAllRanges();document.dispatchEvent(new Event('selectionchange'));});
  await expect(action).toBeVisible();
  await action.click();
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

test('touch layout detects a selection even when the browser emits no mouseup or synthetic selectionchange',async({page})=>{
  await page.setViewportSize({width:412,height:915});
  await page.goto(url,{waitUntil:'networkidle'});
  await expect(page.locator('.student-notes-entry')).toBeHidden();
  await expect(page.locator('[data-student-notes-panel]>summary')).toBeVisible();
  await selectSubstring(page,'[data-student-anchor-block="introducao:p:2"]','trecho permanece disponível',{mouseUp:false,selectionChange:false});
  const action=page.locator('.student-selection-note-action');
  await expect(action).toBeVisible({timeout:2000});
  await expect(action).toHaveText('Anotar seleção');
  await expect(action).toHaveClass(/is-touch-selection/);
  const box=await action.boundingBox();
  expect(box).not.toBeNull();
  expect(box.x).toBeGreaterThanOrEqual(0);
  expect(box.y).toBeGreaterThanOrEqual(0);
  expect(box.x+box.width).toBeLessThanOrEqual(412);
  expect(box.y+box.height).toBeLessThanOrEqual(915);
  await expect(page.locator('[data-student-notes-panel]>summary')).toBeHidden();
});

test('touch selection survives a transient collapsed selection event long enough to tap annotate',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url,{waitUntil:'networkidle'});
  await selectSubstring(page,'[data-student-anchor-block="introducao:p:2"]','trecho permanece disponível',{mouseUp:false,selectionChange:true});
  const action=page.locator('.student-selection-note-action');await expect(action).toBeVisible({timeout:2000});
  await page.evaluate(()=>{const selection=window.getSelection();selection.removeAllRanges();document.dispatchEvent(new Event('selectionchange'));});
  await expect(action).toBeVisible();
  await action.click();
  await expect(page.locator('[data-inline-note-compose]')).toBeVisible();
  await expect(page.locator('[data-anchor-preview]')).toHaveText('trecho permanece disponível');
});

test('saving an inline annotation stays on the same page and reading position',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.route('**/aluno/material-anotacao.php',async route=>{
    const request=route.request();
    expect(request.method()).toBe('POST');
    expect(request.headers().accept).toContain('application/json');
    await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify({ok:true,action:'create_selection',annotation_id:4,annotation:{id:4,anchorType:'selection',sectionKey:'processo',body:'Minha nota sem reload',quoteExact:'parágrafo foi alterado',quotePrefix:'Este ',quoteSuffix:' depois da anotação original.',blockKey:'processo:p:1',start:5,end:26,sourceBlockHash:'fixture4',sourcePageRevision:'rev-2'}})});
  });
  await page.goto(url,{waitUntil:'networkidle'});
  const block=page.locator('[data-student-anchor-block="processo:p:1"]');
  await scrollBlockIntoViewInstantly(block);
  const before=await block.evaluate(element=>({top:element.getBoundingClientRect().top,y:window.scrollY,url:location.href}));
  await selectSubstring(page,'[data-student-anchor-block="processo:p:1"]','parágrafo foi alterado',{mouseUp:false,selectionChange:true});
  const action=page.locator('.student-selection-note-action');await expect(action).toBeVisible({timeout:2000});await action.click();
  const compose=page.locator('[data-inline-note-compose]');await expect(compose).toBeVisible();
  await compose.locator('textarea[name="body"]').fill('Minha nota sem reload');
  await compose.locator('[data-inline-note-submit]').click();
  await expect(compose).toBeHidden();
  await expect(page.locator('[data-annotation-item="4"] textarea[name="body"]')).toHaveValue('Minha nota sem reload');
  await expect(page.locator('mark.student-inline-note-mark[data-annotation-id="4"]')).toContainText('parágrafo foi alterado');
  expect(await page.evaluate(()=>location.href)).toBe(before.url);
  await expect.poll(async()=>block.evaluate((element,top)=>Math.abs(element.getBoundingClientRect().top-top),before.top)).toBeLessThan(4);
  await expect.poll(async()=>page.evaluate(y=>Math.abs(window.scrollY-y),before.y)).toBeLessThan(4);
});

test('an annotation can publish a question without leaving the material screen',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.route('**/aluno/material-anotacao.php',async route=>{
    const request=route.request();const body=request.postData()||'';
    expect(request.method()).toBe('POST');expect(request.headers().accept).toContain('application/json');
    expect(body).toContain('name="create_question"');expect(body).toContain('name="question_title"');expect(body).toContain('Minha dúvida');
    await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify({ok:true,action:'create_selection',annotation_id:5,annotation:{id:5,anchorType:'selection',sectionKey:'processo',body:'Não entendi esta passagem',quoteExact:'parágrafo foi alterado',quotePrefix:'Este ',quoteSuffix:' depois da anotação original.',blockKey:'processo:p:1',start:5,end:26,sourceBlockHash:'fixture5',sourcePageRevision:'rev-2'},question:{id:21,title:'Minha dúvida',status:'open'},question_url:'/aluno/duvidas.php?cohort=turma-1&id=21'})});
  });
  await page.goto(url,{waitUntil:'networkidle'});const before=await page.evaluate(()=>location.href);
  await selectSubstring(page,'[data-student-anchor-block="processo:p:1"]','parágrafo foi alterado',{mouseUp:false,selectionChange:true});
  await page.locator('.student-selection-note-action').click();
  const compose=page.locator('[data-inline-note-compose]');await expect(compose).toBeVisible();
  await compose.locator('textarea[name="body"]').fill('Não entendi esta passagem');
  await compose.locator('[data-note-question]>summary').click();
  await expect(compose.locator('[data-note-question]')).toHaveAttribute('open','');
  await expect(page.locator('[data-student-notes-panel]')).toHaveClass(/is-questioning/);
  await expect(compose.locator('[data-note-question]>summary')).toHaveText('← Voltar à anotação');
  await expect(compose.locator('[data-inline-note-body]')).toBeHidden();
  await expect(compose.locator('.student-inline-note-primary-actions')).toHaveCount(1);
  await expect(compose.locator('.student-inline-note-primary-actions')).toBeHidden();
  await compose.locator('[name="question_title"]').fill('Minha dúvida');
  await compose.locator('[name="question_visibility"][value="cohort"]').check();
  await compose.locator('[data-note-question-publish]').click();
  await expect(page.locator('[data-annotation-item="5"] textarea[name="body"]')).toHaveValue('Não entendi esta passagem');
  await expect(page.locator('[data-annotation-item="5"] a',{hasText:'Ver dúvida'})).toHaveAttribute('href','/aluno/duvidas.php?cohort=turma-1&id=21');
  expect(await page.evaluate(()=>location.href)).toBe(before);
});

test('orphaned notes can be reassociated without changing their body',async({page})=>{
  await page.goto(url,{waitUntil:'networkidle'});
  await page.locator('[data-student-notes-panel]>summary').click();
  await expect(page.locator('[data-student-notes-panel]')).toHaveAttribute('open','');
  await page.locator('[data-annotation-item="2"] [data-annotation-edit]').click();
  await expect(page.locator('[data-student-notes-panel]')).toHaveClass(/is-editing/);
  await page.locator('[data-annotation-reanchor="2"]').click();
  await expect(page.locator('.student-reanchor-hint')).toBeVisible();
  await selectSubstring(page,'[data-student-anchor-block="processo:p:1"]','parágrafo foi alterado');
  const action=page.locator('.student-selection-note-action');await expect(action).toHaveText('Reassociar seleção');await action.click();
  const compose=page.locator('[data-inline-note-compose]');
  await expect(compose).toBeVisible();
  await expect(compose.locator('[data-annotation-action]')).toHaveValue('reanchor');
  await expect(compose.locator('[data-annotation-id]')).toHaveValue('2');
  await expect(compose.locator('[data-anchor-preview]')).toHaveText('parágrafo foi alterado');
  await expect(compose.locator('[data-inline-note-body]')).toBeHidden();
  await expect(compose.locator('[data-inline-note-submit]')).toHaveText('Confirmar reassociação');
});

test('desktop drawer separates note and question, and publishes to every cohort of the course',async({page})=>{
  await page.setViewportSize({width:1280,height:820});
  let submitted=false;
  await page.route('**/aluno/material-anotacao.php',async route=>{
    const body=route.request().postData()||'';
    expect(body).toContain('name="question_visibility"');
    expect(body).toContain('\r\n\r\ncourse\r\n');
    submitted=true;
    await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify({
      ok:true,action:'create_selection',annotation_id:9,
      annotation:{id:9,anchorType:'selection',sectionKey:'processo',body:'Testando as tonalidades',quoteExact:'parágrafo foi alterado',quotePrefix:'Este ',quoteSuffix:' depois da anotação original.',blockKey:'processo:p:1',start:5,end:26,sourceBlockHash:'fixture9',sourcePageRevision:'rev-2'},
      question:{id:31,title:'Compartilhar com o curso',status:'open'},
      question_url:'/aluno/duvidas.php?cohort=turma-1&id=31'
    })});
  });
  await page.goto(url,{waitUntil:'networkidle'});
  await selectSubstring(page,'[data-student-anchor-block="processo:p:1"]','parágrafo foi alterado');
  await page.locator('.student-selection-note-action').click();
  const panel=page.locator('[data-student-notes-panel]');
  const compose=panel.locator('[data-inline-note-compose]');
  await expect(compose).toBeVisible();
  await expect(compose.locator('.student-inline-note-primary-actions')).toBeVisible();
  await expect(panel.locator('.student-notes-head')).toBeHidden();
  await compose.locator('textarea[name="body"]').fill('Testando as tonalidades');
  await compose.locator('[data-note-question]>summary').click();
  await expect(panel).toHaveClass(/is-questioning/);
  await expect(compose.locator('[data-inline-note-body]')).toBeHidden();
  await expect(compose.locator('.student-inline-note-primary-actions')).toBeHidden();
  await expect(compose.locator('[data-note-question]>summary')).toHaveText('← Voltar à anotação');
  await expect(compose.locator('input[name="question_visibility"]')).toHaveCount(3);
  await expect(compose.locator('[name="question_visibility"][value="course"]')).toBeVisible();
  await compose.locator('[data-note-question]>summary').click();
  await expect(panel).not.toHaveClass(/is-questioning/);
  await expect(compose.locator('textarea[name="body"]')).toHaveValue('Testando as tonalidades');
  await compose.locator('[data-note-question]>summary').click();
  await compose.locator('[name="question_title"]').fill('Compartilhar com o curso');
  await compose.locator('[name="question_visibility"][value="course"]').check();
  await compose.locator('[data-note-question-publish]').click();
  await expect(page.locator('[data-annotation-item="9"] a',{hasText:'Ver dúvida'})).toHaveAttribute('href','/aluno/duvidas.php?cohort=turma-1&id=31');
  await expect(panel).not.toHaveAttribute('open','');
  await panel.locator('summary').first().click();
  await expect(page.locator('[data-annotation-item="9"] a',{hasText:'Ver dúvida'})).toBeVisible();
  expect(submitted).toBe(true);
});


for(const [device,width,height] of [['phone',390,844],['desktop',1280,820]]){
  test(`saved annotation editor is focused, with list and question as separate states — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});
    await page.goto(url,{waitUntil:'networkidle'});
    const panel=page.locator('[data-student-notes-panel]');
    await panel.locator('summary').first().click();
    await expect(panel.locator('.student-notes-head')).toBeVisible();
    await expect(panel.locator('[data-student-note-new]')).not.toHaveAttribute('open','');
    await expect(panel.locator('[data-annotation-item="1"] form')).toBeHidden();
    await expect(panel.locator('[data-annotation-item="1"] .student-note-body-preview')).toBeVisible();
    await panel.locator('[data-annotation-item="2"] [data-annotation-edit]').click();
    await expect(panel).toHaveClass(/is-editing/);
    await expect(panel.locator('[data-student-notes-head]')).toHaveCount(0);
    await expect(panel.locator('.student-notes-head')).toBeHidden();
    await expect(panel.locator('[data-annotation-item="1"]')).toBeHidden();
    await expect(panel.locator('[data-student-note-new]')).toBeHidden();
    await expect(panel.locator('[data-annotation-item="2"] textarea[name="body"]')).toBeVisible();
    await expect(panel.locator('[data-annotation-item="2"] [name="annotation_action"][value="update"]')).toBeVisible();
    const quote=panel.locator('[data-annotation-item="2"] .student-note-quote');
    await expect(quote).toContainText('Uma receita pode ser um excelente ponto de partida');
    const selected=panel.locator('[data-annotation-item="2"]');
    await selected.locator('[data-note-question]>summary').click();
    await expect(panel).toHaveClass(/is-existing-questioning/);
    await expect(selected.locator('.student-note-edit-field')).toBeHidden();
    await expect(selected.locator('.student-note-actions-group')).toBeHidden();
    await expect(selected.locator('[name="question_visibility"][value="course"]')).toBeVisible();
    await selected.locator('[data-note-question]>summary').click();
    await expect(selected.locator('textarea[name="body"]')).toBeVisible();
    await panel.locator('[data-student-notes-back-button]').click();
    await expect(panel).not.toHaveClass(/is-editing/);
    await expect(panel.locator('.student-notes-head')).toBeVisible();
    await expect(panel.locator('[data-annotation-item="2"] form')).toBeHidden();
    await panel.locator('[data-student-note-new]>summary').click();
    await expect(panel.locator('[data-student-note-new] form')).toBeVisible();
    await panel.locator('[data-student-note-new] [data-note-question]>summary').click();
    await expect(panel).toHaveClass(/is-page-questioning/);
    await expect(panel.locator('[data-student-note-new]>form>label')).toBeHidden();
    await expect(panel.locator('[data-student-note-new] [name="question_visibility"][value="course"]')).toBeVisible();
  });
}
