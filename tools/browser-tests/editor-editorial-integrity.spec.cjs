const {test,expect}=require('@playwright/test');

const root='http://127.0.0.1:8099/editor/index.html?page=301';
async function mockApp(page,{latency=0,published=1}={}){
  const state={
    revision:2,published,html:'<section data-cms-section="intro" data-cms-section-name="Introdução"><div class="section-inner"><h1 data-cms-editable>Laboratório</h1><p id="edited-text" data-cms-editable>Texto inicial</p><figure data-cms-image-block><img id="photo" data-cms-image src="/assets/media/hero-medusa.webp" alt="Retrato"></figure></div></section>',
    saved:[],publishedRequests:[],withdrawals:[]
  };
  const reply=(route,json,status=200)=>route.fulfill({status,contentType:'application/json',body:JSON.stringify(json)});
  await page.route('**/admin/api/cms-page-load.php*',route=>reply(route,{
    csrf:'fixture',page:{id:301,title:'Materiais',navTitle:'Materiais',slug:'materiais',locale:'pt-BR',
      activityId:1,isHome:false,showInNav:false,draftRevision:state.revision,publishedRevision:state.published,
      document:{version:2,theme:'auto',meta:{title:'Materiais',description:''},html:state.html}},
    activity:{id:1,slug:'workshop',title:'Workshop'},forms:[]
  }));
  await page.route('**/admin/api/editor-context.php*',route=>reply(route,{activity:{id:1,title:'Workshop'},pages:[],newSubmissions:0}));
  await page.route('**/admin/api/cms-access-options.php*',route=>reply(route,{csrf:'fixture',pageAccess:'activity',pageCohortId:0,cohorts:[],lessons:[]}));
  await page.route('**/admin/api/cms-page-save.php',async route=>{
    const data=route.request().postDataJSON();
    state.saved.push(data);
    if(latency)await new Promise(resolve=>setTimeout(resolve,latency));
    if(data.revision!==state.revision)return reply(route,{error:{code:'revision_conflict',message:'revision_conflict'}},409);
    state.revision++;
    state.html=data.document.html;
    return reply(route,{ok:true,page:{id:301,title:data.settings.title,navTitle:data.settings.nav_title,slug:data.settings.slug,
      isHome:false,locale:'pt-BR',showInNav:data.settings.show_in_nav,activityId:1,
      draftRevision:state.revision,publishedRevision:state.published,document:data.document}});
  });
  await page.route('**/admin/api/cms-page-publish.php',route=>{
    const data=route.request().postDataJSON();
    state.publishedRequests.push(data);
    if(data.revision!==state.revision)return reply(route,{error:{code:'revision_conflict'}},409);
    state.published=state.revision;
    return reply(route,{ok:true,publishedRevision:state.published,draftRevision:state.revision,publishedAt:'2026-10-21T15:00:00Z'});
  });
  await page.route('**/admin/api/cms-page-unpublish.php',route=>{
    const data=route.request().postDataJSON();
    state.withdrawals.push(data);
    if(data.revision!==state.revision)return reply(route,{error:{code:'revision_conflict'}},409);
    state.published=null;
    return reply(route,{ok:true,draftRevision:state.revision,publishedRevision:null,publishedAt:null});
  });
  await page.route('**/preview/?page=301*',route=>route.fulfill({
    status:200,contentType:'text/html',
    body:'<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"></head><body><main data-cms-page-main>'+state.html+'</main></body></html>'
  }));
  return state;
}
async function open(page){
  await page.goto(root);
  await expect(page.frameLocator('#page-frame').locator('[data-cms-page-main]')).toBeVisible();
  await expect.poll(()=>page.evaluate(()=>Boolean(window.CmsEditorDraft))).toBe(true);
}

test('an ACK from an old edit cannot overwrite a newer local edit',async({page})=>{
  const state=await mockApp(page,{latency:900,published:1});
  await open(page);
  const field=page.frameLocator('#page-frame').locator('#edited-text');
  await field.evaluate(el=>{el.textContent='Primeira revisão';el.dispatchEvent(new Event('input',{bubbles:true}))});
  const saveStarted=page.waitForRequest(req=>req.url().includes('cms-page-save.php'));
  const flushing=page.evaluate(()=>window.CmsEditorDraft.save());
  await saveStarted;
  await field.evaluate(el=>{el.textContent='Última revisão, feita durante o salvamento';el.dispatchEvent(new Event('input',{bubbles:true}))});
  await flushing;
  expect(state.saved.length).toBe(2);
  expect(state.saved[0].document.html).toContain('Primeira revisão');
  expect(state.saved[1].document.html).toContain('Última revisão');
  expect(state.html).toContain('Última revisão');
  await expect(page.locator('#save-status')).toContainText('Publicado · alterações em rascunho');
  expect(await page.evaluate(()=>window.CmsEditorDraft.getState().dirty)).toBe(false);
});

test('read-only image selection does not mark the document dirty',async({page})=>{
  const state=await mockApp(page,{published:1});
  await open(page);
  await page.frameLocator('#page-frame').locator('#photo').click();
  await expect(page.locator('#inspector #i-alt')).toBeVisible();
  expect(state.saved.length).toBe(0);
  const status=await page.evaluate(()=>window.CmsEditorDraft.getState());
  expect(status.dirty).toBe(false);
});

test('publishing uses exactly the acknowledged revision and can be reversed',async({page})=>{
  const state=await mockApp(page,{published:1});
  page.on('dialog',dialog=>dialog.accept());
  await open(page);
  await expect(page.locator('#save-status')).toContainText('Publicado · alterações em rascunho');
  await page.locator('#publish-page').click();
  await expect.poll(()=>state.publishedRequests.length).toBe(1);
  expect(state.publishedRequests[0].revision).toBe(2);
  await expect(page.locator('#save-status')).toContainText('Publicado · atualizado');
  await page.locator('#editor-more').evaluate(el=>el.open=true);
  await page.locator('#unpublish-page').click();
  await expect.poll(()=>state.withdrawals.length).toBe(1);
  expect(state.withdrawals[0].revision).toBe(2);
  await expect(page.locator('#save-status')).toContainText('Rascunho · fora do ar');
  expect(state.html).toContain('Texto inicial');
});

test('responsive toolbar keeps save status and distinct publication control visible',async({page})=>{
  await mockApp(page,{published:null});
  await page.setViewportSize({width:390,height:844});
  await open(page);
  await expect(page.locator('#save-status')).toBeVisible();
  await expect(page.locator('#save-status')).toHaveAttribute('data-short-label','Rascunho');
  await expect.poll(()=>page.locator('#save-status').evaluate(el=>getComputedStyle(el,'::after').content)).toContain('Rascunho');
  await expect(page.locator('#save-page')).toBeVisible();
  await expect(page.locator('#publish-page')).toBeVisible();
  require('fs').mkdirSync('test-results/editor-integrity',{recursive:true});
  await page.screenshot({path:'test-results/editor-integrity/mobile-draft.png',animations:'disabled'});
});

test('inline link inspector edits an existing link without losing the paragraph',async({page})=>{
  const state=await mockApp(page,{published:1});
  state.html='<section data-cms-section="test" data-cms-section-name="Teste"><p id="edited-text" data-cms-editable>Veja <a id="inline-link" href="https://example.com/old">este material</a> antes da aula.</p></section>';
  await open(page);
  await page.frameLocator('#page-frame').locator('#inline-link').click();
  await expect(page.locator('[data-cms-link-inspector]')).toBeVisible();
  await page.locator('#cms-link-href').fill('javascript:alert(1)');
  await page.locator('#cms-link-apply').click();
  await expect(page.locator('#cms-link-error')).toBeVisible();
  await expect(page.frameLocator('#page-frame').locator('#inline-link')).toHaveAttribute('href','https://example.com/old');
  await page.locator('#cms-link-href').fill('https://example.org/novo');
  await page.locator('#cms-link-text').fill('novo material');
  await page.locator('#cms-link-newtab').check();
  await page.locator('#cms-link-apply').click();
  await expect(page.frameLocator('#page-frame').locator('#inline-link')).toHaveAttribute('href','https://example.org/novo');
  await expect(page.frameLocator('#page-frame').locator('#inline-link')).toHaveAttribute('rel','noopener noreferrer');
  await page.evaluate(()=>window.CmsEditorDraft.save());
  expect(state.html).toContain('novo material</a> antes da aula.');
  expect(state.html).toContain('href="https://example.org/novo"');
});

test('image placeholder replacement preserves the existing figure wrapper',async({page})=>{
  const state=await mockApp(page,{published:null});
  state.html='<section data-cms-section="test" data-cms-section-name="Teste"><figure id="picture-wrap" data-cms-image-block><div id="picture-slot" class="cms-media-placeholder" data-cms-image-placeholder><span>Adicionar imagem</span></div><figcaption data-cms-editable>Legenda original</figcaption></figure></section>';
  await open(page);
  await page.evaluate(()=>{
    window.MediaLibrary.load=async()=>{window.MediaLibrary.images=[{id:731,src:'/assets/media/hero-medusa.webp',label:'Retrato',alt:'Retrato em filme',versionId:1}];return window.MediaLibrary.images;};
  });
  await page.frameLocator('#page-frame').locator('#picture-slot').click();
  await expect(page.locator('#i-choose')).toBeVisible();
  await page.locator('#i-choose').click();
  await expect(page.locator('#media-grid .media-item')).toHaveCount(1);
  await page.locator('#media-grid .media-item').click();
  await expect(page.frameLocator('#page-frame').locator('#picture-wrap')).toHaveCount(1);
  await expect(page.frameLocator('#page-frame').locator('#picture-wrap figure')).toHaveCount(0);
  await expect(page.frameLocator('#page-frame').locator('#picture-wrap img')).toHaveCount(1);
  await page.evaluate(()=>window.CmsEditorDraft.save());
  expect(state.html).toContain('Legenda original');
  expect(state.html).toContain('data-media-asset-id="731"');
});

test('link selected at 390px retains a usable inspector and feedback',async({page})=>{
  const state=await mockApp(page,{published:1});
  state.html='<section data-cms-section="test" data-cms-section-name="Teste"><p data-cms-editable>Leia <a id="inline-link" href="/bibliografia">a bibliografia</a>.</p></section>';
  await page.setViewportSize({width:390,height:844});
  await open(page);
  await page.frameLocator('#page-frame').locator('#inline-link').click();
  await expect(page.locator('[data-cms-link-inspector] #cms-link-href')).toBeVisible();
  await expect(page.locator('#save-status')).toBeVisible();
  require('fs').mkdirSync('test-results/editor-integrity',{recursive:true});
  await page.screenshot({path:'test-results/editor-integrity/mobile-link-selected.png',animations:'disabled'});
});


test('editor-only selection, drag hints and inline controls never enter the saved HTML',async({page})=>{
  const state=await mockApp(page,{published:null});
  await open(page);
  await page.frameLocator('#page-frame').locator('#edited-text').evaluate(el=>{
    el.classList.add('cms-structure-selected','cms-direct-drop-before','cms-pro-selected');
    el.setAttribute('title','Clique para editar');
    const ui=el.ownerDocument.createElement('span');
    ui.setAttribute('data-cms-editor-ui','1');ui.textContent='Botão de edição';
    el.append(ui);
    el.firstChild.textContent='Texto alterado';
    el.dispatchEvent(new Event('input',{bubbles:true}));
  });
  await page.evaluate(()=>window.CmsEditorDraft.save());
  expect(state.saved).toHaveLength(1);
  expect(state.html).toContain('Texto alterado');
  expect(state.html).not.toContain('cms-structure-selected');
  expect(state.html).not.toContain('cms-direct-drop-before');
  expect(state.html).not.toContain('cms-pro-selected');
  expect(state.html).not.toContain('data-cms-editor-ui');
  expect(state.html).not.toContain('Clique para editar');
});


test('mobile inspector collapses without destroying content and expands for a new selection',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await mockApp(page,{published:null});
  await open(page);
  await page.frameLocator('#page-frame').locator('#edited-text').click();
  const inspector=page.locator('#inspector');
  const toggle=inspector.locator('.cms-mobile-inspector-toggle');
  await expect(toggle).toBeVisible();
  await expect(toggle).toHaveAttribute('aria-expanded','true');
  await toggle.click();
  await expect(toggle).toHaveAttribute('aria-expanded','false');
  await expect(inspector).toHaveClass(/cms-mobile-collapsed/);
  const height=await inspector.evaluate(node=>node.getBoundingClientRect().height);
  expect(height).toBeLessThanOrEqual(52);
  await toggle.click();
  await expect(toggle).toHaveAttribute('aria-expanded','true');
  // A mobile sheet covers the lower canvas; collapse it before choosing another block.
  await toggle.click();
  await expect(toggle).toHaveAttribute('aria-expanded','false');
  await page.frameLocator('#page-frame').locator('#photo').click();
  await expect(inspector.locator('#i-alt')).toBeVisible();
  await expect(inspector.locator('.cms-mobile-inspector-name')).toHaveText('Imagem');
  await expect(toggle).toHaveAttribute('aria-expanded','true');
  await page.keyboard.press('Escape');
  await expect(toggle).toHaveAttribute('aria-expanded','false');
  await toggle.click();
  await expect(inspector.locator('#i-alt')).toBeVisible();
  require('fs').mkdirSync('test-results/editor-integrity',{recursive:true});
  await page.screenshot({path:'test-results/editor-integrity/mobile-inspector-expanded.png',animations:'disabled'});
});

test('page navigation checks canonical dirty state even when status label looks saved',async({page})=>{
  await mockApp(page,{published:1,latency:10000});
  await open(page);
  await page.frameLocator('#page-frame').locator('#edited-text').click();
  await page.frameLocator('#page-frame').locator('#edited-text').evaluate(el=>{
    el.textContent='Alteração ainda não salva';
    el.dispatchEvent(new Event('input',{bubbles:true}));
  });
  await expect.poll(()=>page.evaluate(()=>window.CmsEditorDraft.isDirty())).toBe(true);
  await page.locator('#save-status').evaluate(el=>{el.textContent='Publicado · atualizado';el.dataset.state='published'});
  const notices=[];
  page.on('dialog',async dialog=>{notices.push(dialog.message());await dialog.dismiss()});
  await page.locator('#editor-more').evaluate(el=>el.open=true);
  await page.locator('#editor-pages-link').click();
  expect(notices.some(v=>v.includes('alterações não salvas'))).toBe(true);
  expect(page.url()).toContain('/editor/index.html?page=301');
});

test('native inline formatting keeps the selected text and persists only semantic markup',async({page})=>{
  const state=await mockApp(page,{published:null});
  await open(page);
  const edited=page.frameLocator('#page-frame').locator('#edited-text');
  await edited.click();
  await edited.evaluate(el=>{
    const range=el.ownerDocument.createRange();
    range.setStart(el.firstChild,0);range.setEnd(el.firstChild,5);
    const selection=el.ownerDocument.defaultView.getSelection();
    selection.removeAllRanges();selection.addRange(range);
  });
  await page.locator('#text-toolbar [data-command="bold"]').click();
  await expect(edited.locator('strong')).toContainText('Texto');
  await edited.locator('strong').evaluate(el=>{
    const range=el.ownerDocument.createRange();range.selectNodeContents(el);
    const selection=el.ownerDocument.defaultView.getSelection();
    selection.removeAllRanges();selection.addRange(range);
  });
  await page.locator('#text-toolbar [data-command="removeFormat"]').click();
  await expect(edited.locator('strong')).toHaveCount(0);
  await expect(edited).toContainText('Texto inicial');
  await edited.evaluate(el=>{
    const text=[...el.childNodes].find(n=>n.nodeType===Node.TEXT_NODE&&n.textContent.includes('inicial'));
    const range=el.ownerDocument.createRange();range.setStart(text,text.textContent.indexOf('inicial'));range.setEnd(text,text.textContent.indexOf('inicial')+7);
    const selection=el.ownerDocument.defaultView.getSelection();selection.removeAllRanges();selection.addRange(range);
  });
  await page.locator('#text-toolbar [data-command="italic"]').click();
  await expect(edited.locator('em')).toContainText('inicial');
  await page.evaluate(()=>window.CmsEditorDraft.save());
  expect(state.html).toContain('<em>inicial</em>');
  expect(state.html).not.toContain('contenteditable');
});

test('clearing inline formatting preserves hyperlinks and their destination',async({page})=>{
  const state=await mockApp(page,{published:null});
  state.html='<section data-cms-section="intro" data-cms-section-name="Introdução"><div class="section-inner"><p data-cms-editable id="edited-text">Leia <strong><a id="keep-link" href="/bibliografia">Bibliografia</a></strong> antes da aula.</p></div></section>';
  await open(page);
  const edited=page.frameLocator('#page-frame').locator('#edited-text');
  await edited.click();
  await edited.locator('strong').evaluate(el=>{
    const range=el.ownerDocument.createRange();range.selectNodeContents(el);
    const selection=el.ownerDocument.defaultView.getSelection();selection.removeAllRanges();selection.addRange(range);
  });
  await page.locator('#text-toolbar [data-command="removeFormat"]').click();
  await expect(edited.locator('strong')).toHaveCount(0);
  await expect(edited.locator('a')).toHaveAttribute('href','/bibliografia');
  await page.evaluate(()=>window.CmsEditorDraft.save());
  expect(state.html).toContain('href="/bibliografia"');
  expect(state.html).not.toContain('<strong>');
});


test('clearing the middle of a bold run preserves formatting on both sides',async({page})=>{
  const state=await mockApp(page,{published:null});
  state.html='<section data-cms-section="intro" data-cms-section-name="Introdução"><p id="edited-text" data-cms-editable><strong>ABCDEFGHIJ</strong></p></section>';
  await open(page);
  const field=page.frameLocator('#page-frame').locator('#edited-text');
  await field.click();
  await field.locator('strong').evaluate(el=>{
    const text=el.firstChild,range=el.ownerDocument.createRange();
    range.setStart(text,2);range.setEnd(text,5);
    const selection=el.ownerDocument.defaultView.getSelection();selection.removeAllRanges();selection.addRange(range);
  });
  await page.locator('#text-toolbar [data-command="removeFormat"]').click();
  await expect(field.locator('strong')).toHaveCount(2);
  await expect(field.locator('strong').first()).toHaveText('AB');
  await expect(field.locator('strong').last()).toHaveText('FGHIJ');
  expect(await field.textContent()).toBe('ABCDEFGHIJ');
  await page.evaluate(()=>window.CmsEditorDraft.save());
  expect(state.html).toContain('<strong>AB</strong>CDE<strong>FGHIJ</strong>');
});

test('toggling bold off a substring preserves every segment of a nested hyperlink',async({page})=>{
  const state=await mockApp(page,{published:null});
  state.html='<section data-cms-section="intro" data-cms-section-name="Introdução"><p id="edited-text" data-cms-editable>Leia <strong><a href="/bibliografia" id="reference-link">ABCDEFG</a></strong> agora.</p></section>';
  await open(page);
  const field=page.frameLocator('#page-frame').locator('#edited-text');
  await field.click({position:{x:4,y:4}});
  await field.locator('#reference-link').evaluate(el=>{
    const text=el.firstChild,range=el.ownerDocument.createRange();
    range.setStart(text,1);range.setEnd(text,4);
    const selection=el.ownerDocument.defaultView.getSelection();selection.removeAllRanges();selection.addRange(range);
  });
  await page.locator('#text-toolbar [data-command="bold"]').click();
  await expect(field.locator('strong')).toHaveCount(2);
  await expect(field.locator('a')).toHaveCount(3);
  await expect(field.locator('#reference-link')).toHaveCount(1);
  for(const href of await field.locator('a').evaluateAll(nodes=>nodes.map(node=>node.getAttribute('href'))))
    expect(href).toBe('/bibliografia');
  expect(await field.textContent()).toBe('Leia ABCDEFG agora.');
  await page.evaluate(()=>window.CmsEditorDraft.save());
  expect(state.html).toContain('<a href="/bibliografia">BCD</a>');
  expect(state.html).toContain('<strong><a href="/bibliografia"');
});

test('selection across mixed markup never removes formatting outside a selected run',async({page})=>{
  const state=await mockApp(page,{published:null});
  state.html='<section data-cms-section="intro" data-cms-section-name="Introdução"><p id="edited-text" data-cms-editable><strong>Primeiro</strong> e <em>segundo</em>.</p></section>';
  await open(page);
  const field=page.frameLocator('#page-frame').locator('#edited-text');
  await field.click();
  await field.evaluate(el=>{
    const bold=el.querySelector('strong').firstChild,ital=el.querySelector('em').firstChild;
    const range=el.ownerDocument.createRange();range.setStart(bold,3);range.setEnd(ital,3);
    const selection=el.ownerDocument.defaultView.getSelection();selection.removeAllRanges();selection.addRange(range);
  });
  await page.locator('#text-toolbar [data-command="removeFormat"]').click();
  expect(await field.textContent()).toBe('Primeiro e segundo.');
  // Remaining unselected prefixes/suffixes keep their semantic wrappers.
  await expect(field.locator('strong')).toHaveText('Primeiro');
  await expect(field.locator('em')).toHaveText('segundo');
  await page.evaluate(()=>window.CmsEditorDraft.save());
  expect(state.html).toContain('<strong>Primeiro</strong>');
  expect(state.html).toContain('<em>segundo</em>');
});


test('history captures the initial iframe state before immediate text formatting',async({page})=>{
  const state=await mockApp(page,{published:null});
  await open(page);
  // No artificial delay: the very first edit must have a baseline to undo to.
  const initial=await page.evaluate(()=>window.CmsEditorHistory?.getState());
  expect(initial).toBeTruthy();
  expect(initial.length).toBe(1);
  expect(initial.index).toBe(0);
  const edited=page.frameLocator('#page-frame').locator('#edited-text');
  await edited.click();
  await edited.evaluate(el=>{
    const range=el.ownerDocument.createRange();
    range.setStart(el.firstChild,0);
    range.setEnd(el.firstChild,5);
    const selection=el.ownerDocument.defaultView.getSelection();
    selection.removeAllRanges();selection.addRange(range);
  });
  await page.locator('#text-toolbar [data-command="bold"]').click();
  await expect(edited.locator('strong')).toHaveText('Texto');
  await expect(page.locator('#undo')).toBeEnabled();
  expect(await page.evaluate(()=>window.CmsEditorHistory.getState().length)).toBeGreaterThan(1);
  await page.evaluate(()=>window.CmsEditorHistory.undo());
  await expect(edited.locator('strong')).toHaveCount(0);
  await expect(edited).toHaveText('Texto inicial');
  await expect(page.locator('#redo')).toBeEnabled();
  expect(state.html).toContain('Texto inicial');
});

test('stale revision opens recovery without overwriting server, local draft can be exported',async({page})=>{
  const state=await mockApp(page,{published:1});
  await open(page);
  state.revision=3; // Another authenticated session has saved revision 3.
  const field=page.frameLocator('#page-frame').locator('#edited-text');
  await field.click();
  await field.evaluate(el=>{
    el.textContent='Minha edição local não pode ser perdida';
    el.dispatchEvent(new Event('input',{bubbles:true}));
  });
  await page.evaluate(()=>window.CmsEditorDraft.save().catch(()=>{}));
  const dialog=page.locator('#cms-conflict-recovery');
  await expect(dialog).toBeVisible();
  await expect(dialog.locator('#cms-conflict-revisions')).toContainText('revisão 2');
  expect(state.revision).toBe(3);
  expect(state.html).not.toContain('Minha edição local');
  const fileEvent=page.waitForEvent('download');
  await dialog.locator('[data-conflict-download]').click();
  const download=await fileEvent;
  expect(download.suggestedFilename()).toContain('pagina-301-revisao-2');
  const backup=JSON.parse(require('fs').readFileSync(await download.path(),'utf8'));
  expect(backup.format).toBe('workshop-cms-editor-recovery-v1');
  expect(backup.document.html).toContain('Minha edição local não pode ser perdida');
  expect(backup.basedOnRevision).toBe(2);
  await dialog.locator('[data-conflict-check]').click();
  await expect(dialog.locator('#cms-conflict-feedback')).toContainText('revisão atual no servidor é 3');
  // Compare the actual modal at desktop and mobile viewports, not CSS alone.
  const fs=require('fs');
  fs.mkdirSync('test-results/editor-integrity',{recursive:true});
  await page.screenshot({path:'test-results/editor-integrity/conflict-recovery-desktop.png'});
  await page.setViewportSize({width:390,height:844});
  await expect(dialog.locator('[data-conflict-download]')).toBeVisible();
  await expect(dialog.locator('[data-conflict-copy]')).toBeVisible();
  await expect(dialog.locator('[data-conflict-check]')).toBeVisible();
  await expect(dialog.locator('[data-conflict-close]')).toBeVisible();
  await page.screenshot({path:'test-results/editor-integrity/conflict-recovery-mobile-390.png'});
  await dialog.locator('[data-conflict-close]').click();
  await expect(dialog).not.toBeVisible();
  await expect.poll(()=>page.evaluate(()=>window.CmsEditorDraft.isDirty())).toBe(true);
  await expect(field).toContainText('Minha edição local não pode ser perdida');
});
