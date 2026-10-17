const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-material-cms-operational.php';
const viewports=[
  {name:'narrow',width:320,height:720},
  {name:'phone',width:390,height:844},
  {name:'desktop',width:1440,height:1100},
];

/* On mobile the CMS reading pane scrolls; on desktop the window owns scroll.
   Observe the actual owner instead of assuming it from the existence of the pane. */
const setReadingPosition=(page,fraction)=>page.evaluate(fraction=>{
 const reader=document.querySelector('.cms-student-reading[data-student-scroll-root]');
 const innerExtent=Math.max(0,reader.scrollHeight-reader.clientHeight);
 const owner=innerExtent>30?'reader':'window';
 const extent=owner==='reader'?innerExtent:Math.max(0,document.documentElement.scrollHeight-innerHeight);
 const target=Math.round(extent*fraction);
 if(owner==='reader')reader.scrollTop=target;else window.scrollTo({top:target,behavior:'instant'});
 return {owner,extent,top:owner==='reader'?reader.scrollTop:window.scrollY};
},fraction);
const readReadingPosition=(page,owner)=>page.evaluate(owner=>
 owner==='reader'?document.querySelector('.cms-student-reading[data-student-scroll-root]').scrollTop:window.scrollY,owner);

for(const device of viewports){
  test('real CMS renderer, enrolled course and released sections — '+device.name,async({page})=>{
    const errors=[];page.on('pageerror',error=>errors.push(error.message));
    await page.setViewportSize({width:device.width,height:device.height});
    const response=await page.goto(base+'?page=current&cohort=cohort-a',{waitUntil:'networkidle'});
    expect(response?.status()).toBe(200);
    await expect(page.locator('body.cms-student-material[data-cms-page-id="101"]')).toHaveCount(1);
    await expect(page.locator('.cms-student-reading[data-student-scroll-root]')).toHaveCount(1);
    await expect(page.locator('main[data-cms-page-main]')).toHaveCount(1);
    await expect(page.locator('.study-material[data-testid="editorial-material"]')).toHaveCount(1);
    const context=page.locator('.cms-student-study-context');
    await expect(context).toContainText('Positivo direto em filme de raio-X');
    await expect(context).toContainText('Turma Outubro 2026');
    await expect(context.locator('a[href="/aluno/cursos.php?cohort=cohort-a"]')).toHaveCount(1);
    await expect(context.locator('a[href="/aluno/duvidas.php?cohort=cohort-a"]')).toHaveCount(1);
    await expect(context.locator('a[rel="prev"]')).toContainText('Exposição pensando no positivo');
    await expect(context.locator('a[rel="prev"]')).toHaveAttribute('href',/page=exposicao.*cohort=cohort-a/);
    await expect(context.locator('a[rel="next"]')).toContainText('Segunda revelação e acabamento');
    await expect(context.locator('a[rel="next"]')).toHaveAttribute('href',/page=segunda-revelacao.*cohort=cohort-a/);
    await expect(context).not.toContainText('Conteúdo não liberado');
    const article=page.locator('.study-material');
    await expect(article.locator('[data-cms-section="primeiro-desenvolvimento"]')).toHaveCount(1);
    await expect(article.locator('[data-cms-section="branqueamento"]')).toHaveCount(1);
    await expect(article.locator('[data-cms-section="aula-nao-liberada"]')).toHaveCount(0);
    await expect(article).not.toContainText('Esta seção não pode ser enviada ao aluno.');
    const notes=page.locator('[data-student-notes-panel]');
    await expect(notes).toHaveCount(1);
    await expect(notes.locator('[data-annotation-item]')).toHaveCount(2);
    const notesData=notes.locator('script[type="application/json"][data-student-annotation-data]');
    await expect(notesData).toHaveCount(1);
    const annotations=await notesData.evaluate(el=>JSON.parse(el.textContent||'[]'));
    expect(annotations).toHaveLength(2);
    expect(annotations.map(x=>x.anchorType)).toEqual(['page','page']);
    await expect(notes.locator('form[action="/aluno/material-anotacao.php"] input[name="_csrf"]').first()).toHaveValue('fixture-cms-csrf-no-write');
    await expect(notes.locator('summary').first()).toContainText('Anotações');
    await expect(page.locator('header[data-cms-public-header] a.brand')).toHaveAttribute('href','/aluno/');
    await expect(page.locator('.student-desktop-nav a.student-sidebar-brand')).toHaveCount(1);
    await expect(page.locator('.student-desktop-nav a:not(.student-sidebar-brand)')).toHaveCount(5);
    await expect(page.locator('.student-mobile-nav a')).toHaveCount(5);
    await expect(page.locator('.student-mobile-nav a[href="/aluno/cursos.php"]')).toHaveAttribute('aria-current','page');
    await expect(page.locator('[data-cms-page-slug="processo-quimico-completo"]')).toHaveCount(1);
    const geometry=await page.evaluate(()=>{
      const root=document.querySelector('.cms-student-reading'),nav=document.querySelector('.student-mobile-nav');
      return{over:document.documentElement.scrollWidth-document.documentElement.clientWidth,
        readOver:root.scrollWidth-root.clientWidth,readBottom:root.getBoundingClientRect().bottom,
        navTop:nav.getBoundingClientRect().top,navDisplay:getComputedStyle(nav).display,
        pageHeight:document.documentElement.scrollHeight,viewport:innerHeight,readHeight:root.scrollHeight,clientHeight:root.clientHeight};
    });
    expect(geometry.over).toBeLessThanOrEqual(1);
    expect(geometry.readOver).toBeLessThanOrEqual(1);
    if(device.width<821&&geometry.navDisplay!=='none'){
       expect(geometry.readBottom).toBeLessThanOrEqual(geometry.navTop+1);
       expect(geometry.pageHeight).toBeLessThanOrEqual(geometry.viewport+1);
       expect(geometry.readHeight).toBeGreaterThan(geometry.clientHeight);
    }
    const root=page.locator('.cms-student-reading');
    for(const part of ['start','middle','end']){
      await root.evaluate((el,point)=>{el.scrollTop=point==='start'?0:point==='middle'?(el.scrollHeight-el.clientHeight)/2:el.scrollHeight;},part);
      await page.screenshot({path:'student-visual-audit/material-cms/'+device.name+'-'+part+'.png',animations:'disabled'});
    }
    await notes.locator('summary').first().click();
    await expect(notes).toHaveAttribute('open','');
    await expect(notes).toContainText('Comparar EI 200 e EI 400');
    await page.screenshot({path:'student-visual-audit/material-cms/'+device.name+'-annotations.png',animations:'disabled'});
    await notes.locator('[data-annotation-edit="1"]').click();
    const editing=notes.locator('[data-annotation-item="1"]');
    await expect(editing.locator('textarea[name="body"]')).toBeVisible();
    await expect(editing.locator('textarea[name="body"]')).toHaveValue(/Comparar EI 200 e EI 400/);
    await expect(editing.locator('button[name="annotation_action"][value="update"]')).toBeVisible();
    await page.screenshot({path:'student-visual-audit/material-cms/'+device.name+'-note-edit.png',animations:'disabled'});
    expect(errors).toEqual([]);
  });
}
// The reading owner differs by viewport; editing cannot move either owner or abandon focus.
for(const device of viewports){
 test('CMS notes retain reader scroll and restore list focus — '+device.name,async({page})=>{
  const errors=[];page.on('pageerror',error=>errors.push(error.message));
  await page.setViewportSize({width:device.width,height:device.height});
  await page.route('**/aluno/material-anotacao.php',async route=>{
   expect(route.request().method()).toBe('POST');
   const body=route.request().postData()||'';
   expect(body).toContain('name="annotation_action"');
   expect(body).toContain('update');
   await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify({ok:true,action:'update',annotation_id:1})});
  });
  await page.goto(base+'?page=current&cohort=cohort-a',{waitUntil:'networkidle'});
  const root=page.locator('.cms-student-reading[data-student-scroll-root]');
  const panel=page.locator('[data-student-notes-panel]');
  await panel.locator(':scope > summary').click();
  await expect(panel).toHaveAttribute('open','');
  const position=await setReadingPosition(page,.48);
  expect(position.extent,'material must have a scrollable reading region').toBeGreaterThan(30);
  const entry=panel.locator('[data-annotation-item="1"]');
  await entry.locator('[data-annotation-edit]').click();
  const textarea=entry.locator('textarea[name="body"]');
  await expect(textarea).toBeVisible();
  await expect(textarea).toBeFocused();
  await expect.poll(()=>readReadingPosition(page,position.owner)).toBe(position.top);
  await textarea.fill('Comparar resultados mantendo as condições constantes.');
  await entry.locator('button[name="annotation_action"][value="update"]').click();
  await expect(entry.locator('[data-note-save-status]')).toHaveText('Salvo.');
  await expect(textarea).toHaveValue('Comparar resultados mantendo as condições constantes.');
  await expect.poll(()=>readReadingPosition(page,position.owner)).toBe(position.top);
  await page.screenshot({path:'student-visual-audit/material-reading-continuity/'+device.name+'-editing.png',animations:'disabled'});
  await panel.locator('[data-student-notes-back-button]').click();
  const trigger=entry.locator('[data-annotation-edit]');
  await expect(trigger).toBeFocused();
  await expect(entry.locator('form')).toBeHidden();
  await expect.poll(()=>readReadingPosition(page,position.owner)).toBe(position.top);
  await page.screenshot({path:'student-visual-audit/material-reading-continuity/'+device.name+'-returned-to-list.png',animations:'disabled'});
  const geometry=await page.evaluate(()=>{
   const reader=document.querySelector('.cms-student-reading');
   const nav=document.querySelector('.student-mobile-nav');
   return {documentOver:document.documentElement.scrollWidth-document.documentElement.clientWidth,
    readingOver:reader.scrollWidth-reader.clientWidth,
    navCount:nav.querySelectorAll('a').length,
    readerBottom:reader.getBoundingClientRect().bottom,navTop:nav.getBoundingClientRect().top,
    navDisplay:getComputedStyle(nav).display};
  });
  expect(geometry.navCount).toBe(5);
  expect(geometry.documentOver).toBeLessThanOrEqual(1);
  expect(geometry.readingOver).toBeLessThanOrEqual(1);
  if(device.width<=820&&geometry.navDisplay!=='none')
   expect(geometry.readerBottom).toBeLessThanOrEqual(geometry.navTop+1);
  expect(errors).toEqual([]);
 });
}

// A failed AJAX save must leave the draft editable and announce the error at the
// form, with keyboard focus on the message (not the previous submit control).
for(const device of viewports){
 test('CMS notes show actionable save failures without losing draft — '+device.name,async({page})=>{
  const errors=[];page.on('pageerror',error=>errors.push(error.message));
  await page.setViewportSize({width:device.width,height:device.height});
  await page.route('**/aluno/material-anotacao.php',route=>route.fulfill({
   status:422,contentType:'application/json',
   body:JSON.stringify({ok:false,error:'Não foi possível salvar esta anotação.'})
  }));
  await page.goto(base+'?page=current&cohort=cohort-a',{waitUntil:'networkidle'});
  const panel=page.locator('[data-student-notes-panel]');
  await panel.locator(':scope > summary').click();
  // Opening the independent sheet can change the viewport scroll owner.
  // Measure the reading position only after the sheet reaches its open state.
  const before=await setReadingPosition(page,.48);
  const article=panel.locator('[data-annotation-item="1"]');
  await article.locator('[data-annotation-edit]').click();
  const textarea=article.locator('textarea[name="body"]');
  const draft='Rascunho mantido após falha de servidor.';
  await textarea.fill(draft);
  await article.locator('button[name="annotation_action"][value="update"]').click();
  const error=article.locator('[data-note-save-status][role="alert"]');
  await expect(error).toHaveText('Não foi possível salvar esta anotação.');
  await expect(error).toBeVisible();
  await expect(error).toBeFocused();
  // Check the actual rendered contrast; a focusable warning with pale text
  // on a pale CMS surface is still unusable.
  const contrast=await error.evaluate(el=>{
    const rgb=value=>(value.match(/[\\d.]+/g)||[]).slice(0,3).map(Number);
    const luminance=value=>{
      const channels=rgb(value).map(c=>{
        const v=c/255;return v<=0.04045?v/12.92:((v+0.055)/1.055)**2.4;
      });
      return channels[0]*0.2126+channels[1]*0.7152+channels[2]*0.0722;
    };
    const style=getComputedStyle(el),a=luminance(style.color),b=luminance(style.backgroundColor);
    return (Math.max(a,b)+0.05)/(Math.min(a,b)+0.05);
  });
  expect(contrast,'message must remain readable in CMS themes').toBeGreaterThanOrEqual(4.5);
  await expect(textarea).toHaveValue(draft);
  await expect(article.locator('button[name="annotation_action"][value="update"]')).toBeEnabled();
  await expect.poll(async()=>Math.abs((await readReadingPosition(page,before.owner))-before.top)).toBeLessThanOrEqual(2);
  await page.screenshot({path:'student-visual-audit/material-reading-continuity/'+device.name+'-save-error.png',animations:'disabled'});
  expect(errors).toEqual([]);
 });
}

// Browser history must restore the active reading scroll owner after navigation.
for(const device of viewports){
 test('CMS material Back/Forward retains previous reading position — '+device.name,async({page})=>{
  await page.setViewportSize({width:device.width,height:device.height});
  const first=base+'?page=current&cohort=cohort-a';
  const next=base+'?page=next&cohort=cohort-a';
  const open=await page.goto(first,{waitUntil:'networkidle'});
  expect(open?.status()).toBe(200);
  const root=page.locator('.cms-student-reading[data-student-scroll-root]');
  const before=await setReadingPosition(page,.65);
  expect(before.extent,'long material needs meaningful scrolling').toBeGreaterThan(30);
  if(before.owner==='reader'){
   await expect.poll(()=>page.evaluate(()=>history.state?.__cmsStudentReaderScrollV1)).toBe(before.top);
  }
  await page.goto(next,{waitUntil:'networkidle'});
  await expect(page.locator('body.cms-student-material[data-cms-page-id="102"]')).toHaveCount(1);
  await page.goBack({waitUntil:'domcontentloaded'});
  await expect(page.locator('body.cms-student-material[data-cms-page-id="101"]')).toHaveCount(1);
  await expect.poll(async()=>Math.abs((await readReadingPosition(page,before.owner))-before.top),{timeout:7000}).toBeLessThanOrEqual(5);
  await page.screenshot({path:'student-visual-audit/material-reading-continuity/'+device.name+'-after-browser-back.png',animations:'disabled'});
  await page.goForward({waitUntil:'domcontentloaded'});
  await expect(page.locator('body.cms-student-material[data-cms-page-id="102"]')).toHaveCount(1);
  await page.goBack({waitUntil:'domcontentloaded'});
  await expect.poll(async()=>Math.abs((await readReadingPosition(page,before.owner))-before.top),{timeout:7000}).toBeLessThanOrEqual(5);
  await expect(page.locator('.student-mobile-nav a[href="/aluno/cursos.php"]')).toHaveAttribute('aria-current','page');
 });
}

test('CMS route rejects absent enrollment, wrong student and wrong cohort before rendering',async({page})=>{
 for(const q of ['actor=none','actor=other','cohort=cohort-b']){
   const res=await page.goto(base+'?page=current&'+q,{waitUntil:'domcontentloaded'});
   expect(res?.status(),q).toBe(403);
   await expect(page.getByText('Acesso não autorizado à página do curso.')).toBeVisible();
   await expect(page.locator('[data-cms-page-main]')).toHaveCount(0);
   await expect(page.getByText('Primeiro desenvolvimento')).toHaveCount(0);
 }
});
test('CMS skip link is keyboard-accessible without being a persistent pointer control',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(base+'?page=current&cohort=cohort-a',{waitUntil:'networkidle'});
  const skip=page.locator('a.skip-link[href="#main"]');
  await expect(skip).toHaveCount(1);
  await page.keyboard.press('Tab');
  await expect(skip).toBeFocused();
  const reach=await skip.evaluate(el=>{
    const b=el.getBoundingClientRect(),style=getComputedStyle(el);
    return{left:b.left,top:b.top,right:b.right,bottom:b.bottom,visibility:style.visibility};
  });
  expect(reach.left).toBeGreaterThanOrEqual(0);
  expect(reach.top).toBeGreaterThanOrEqual(0);
  expect(reach.right).toBeLessThanOrEqual(390);
  expect(reach.bottom).toBeLessThanOrEqual(844);
  await page.keyboard.press('Enter');
  await expect(page).toHaveURL(/#main$/);
});

test('material last released page does not list blocked next material',async({page})=>{
 const res=await page.goto(base+'?page=next&cohort=cohort-a',{waitUntil:'networkidle'});
 expect(res?.status()).toBe(200);
 await expect(page.locator('.cms-student-study-context a[rel="prev"]')).toContainText('Processo químico completo');
 await expect(page.locator('.cms-student-study-context a[rel="next"]')).toHaveCount(0);
 await expect(page.locator('body')).not.toContainText('Somente depois da liberação');
});

for(const [state,expected] of [
  ['default',{display:'Saira Extra Condensed',body:'IBM Plex Sans',mono:'IBM Plex Mono'}],
  ['custom',{display:'Oswald',body:'Inter',mono:'JetBrains Mono'}]
]){
  test('reader respects persisted CMS Design typography — '+state,async({page})=>{
    const res=await page.goto(base+'?page=current&cohort=cohort-a'+(state==='custom'?'&design=custom':''),{waitUntil:'networkidle'});
    expect(res?.status()).toBe(200);
    const actual=await page.evaluate(()=>{
      const font=selector=>getComputedStyle(document.querySelector(selector)).fontFamily;
      return {
        display:font('.study-cover h1'),
        body:font('.study-cover .study-subtitle'),
        mono:font('.study-cover .section-label'),
        rootDisplay:getComputedStyle(document.documentElement).getPropertyValue('--title'),
        rootBody:getComputedStyle(document.documentElement).getPropertyValue('--body'),
        rootMono:getComputedStyle(document.documentElement).getPropertyValue('--mono')
      };
    });
    for(const [role,family] of Object.entries(expected)){
      expect(actual[role],role+' effective font').toContain(family);
    }
    expect(actual.rootDisplay).toContain(expected.display);
    expect(actual.rootBody).toContain(expected.body);
    expect(actual.rootMono).toContain(expected.mono);
    expect(actual.display).not.toContain('Georgia');
    expect(actual.body).not.toContain('Times New Roman');
  });
}
