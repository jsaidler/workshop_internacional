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
  await expect(page.frameLocator('#page-frame').locator('#edited-text')).toBeVisible();
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
  await expect(page.locator('#save-page')).toBeVisible();
  await expect(page.locator('#publish-page')).toBeVisible();
  require('fs').mkdirSync('test-results/editor-integrity',{recursive:true});
  await page.screenshot({path:'test-results/editor-integrity/mobile-draft.png',animations:'disabled'});
});
