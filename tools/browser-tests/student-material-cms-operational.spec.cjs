const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-material-cms-operational.php';
const viewports=[
  {name:'narrow',width:320,height:720},
  {name:'phone',width:390,height:844},
  {name:'desktop',width:1440,height:1100},
];
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
// Material has a nested scroll region; Back/Forward must restore reading,
 // not just the window's zero-valued scroll position.
for(const device of viewports){
 test('Material preserves nested reading position across Back/Forward — '+device.name,async({page})=>{
   await page.setViewportSize({width:device.width,height:device.height});
   const start=base+'?page=current&cohort=cohort-a&long=1';
   expect((await page.goto(start,{waitUntil:'networkidle'}))?.status()).toBe(200);
   const reading=page.locator('.cms-student-reading');
   const before=await reading.evaluate(el=>{
     el.scrollTop=Math.round((el.scrollHeight-el.clientHeight)*.53);
     return {y:el.scrollTop,extent:el.scrollHeight-el.clientHeight};
   });
   expect(before.extent,'fixture must have enough reading text to scroll').toBeGreaterThan(250);
   expect(before.y).toBeGreaterThan(100);
   await expect.poll(async()=>page.evaluate(()=>history.state?.cmsStudentReading?.y??-1)).toBe(before.y);
   const entry=await page.evaluate(()=>({
     length:history.length,key:history.state?.cmsStudentReading?.key,
     windowScroll:window.scrollY
   }));
   expect(entry.key).toBe('/tools/browser-fixture/student-material-cms-operational.php?page=current&cohort=cohort-a&long=1');
   await page.screenshot({path:'student-visual-audit/material-continuity/'+device.name+'-read-before.png',animations:'disabled'});
   expect((await page.goto(base+'?page=next&cohort=cohort-a',{waitUntil:'networkidle'}))?.status()).toBe(200);
   await expect(page.locator('[data-cms-page-main]')).toContainText('Segunda revelação e acabamento');
   await page.goBack({waitUntil:'networkidle'});
   await expect(page.locator('body[data-cms-page-id="101"]')).toHaveCount(1);
   await expect.poll(async()=>reading.evaluate(el=>Math.abs(el.scrollTop-before.y))).toBeLessThanOrEqual(3);
   await expect(page.locator('.student-mobile-nav a[href="/aluno/cursos.php"]')).toHaveAttribute('aria-current','page');
   await expect(page.locator('.cms-student-study-context a[href="/aluno/cursos.php?cohort=cohort-a"]')).toHaveCount(1);
   await page.screenshot({path:'student-visual-audit/material-continuity/'+device.name+'-read-after-back.png',animations:'disabled'});
   await page.goForward({waitUntil:'networkidle'});
   await expect(page.locator('body[data-cms-page-id="102"]')).toHaveCount(1);
   await page.goBack({waitUntil:'networkidle'});
   await expect.poll(async()=>reading.evaluate(el=>Math.abs(el.scrollTop-before.y))).toBeLessThanOrEqual(3);
   expect(await page.evaluate(()=>history.length)).toBe(entry.length+1);
   const metrics=await reading.evaluate(el=>({horizontal:el.scrollWidth-el.clientWidth,bottom:el.getBoundingClientRect().bottom}));
   expect(metrics.horizontal).toBeLessThanOrEqual(1);
   if(device.width<821){
     const navTop=await page.locator('.student-mobile-nav').evaluate(el=>el.getBoundingClientRect().top);
     expect(metrics.bottom).toBeLessThanOrEqual(navTop+1);
   }
 });
 test('Material edit keeps independent reading scroll and saves inline — '+device.name,async({page})=>{
   await page.setViewportSize({width:device.width,height:device.height});
   let requests=0;
   await page.route('**/aluno/material-anotacao.php',async route=>{
     requests++;
     expect(route.request().method()).toBe('POST');
     expect(route.request().postData()).toContain('annotation_action');
     await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify({ok:true,action:'update',annotation_id:1})});
   });
   await page.goto(base+'?page=current&cohort=cohort-a&long=1',{waitUntil:'networkidle'});
   const reading=page.locator('.cms-student-reading');
   const before=await reading.evaluate(el=>{el.scrollTop=Math.round((el.scrollHeight-el.clientHeight)*.62);return el.scrollTop;});
   await expect.poll(async()=>page.evaluate(()=>history.state?.cmsStudentReading?.y??-1)).toBe(before);
   const panel=page.locator('[data-student-notes-panel]');
   // Open the existing disclosure without Playwright's auto-scroll to its
   // footer trigger: the reading position is deliberately held as the invariant.
   await panel.locator('summary').first().evaluate(el=>el.click());
   await panel.locator('[data-annotation-edit="1"]').click();
   const edit=panel.locator('[data-annotation-item="1"]');
   const changed='Comparar duas sensibilidades mantendo os demais parâmetros.';
   await edit.locator('textarea[name="body"]').fill(changed);
   await edit.locator('button[name="annotation_action"][value="update"]').click();
   await expect(edit.locator('[data-note-save-status]')).toHaveText('Salvo.');
   expect(requests).toBe(1);
   await expect(edit.locator('.student-note-body-preview')).toHaveText(changed);
   await panel.locator('summary').first().click();
   await expect(panel).not.toHaveAttribute('open','');
   await expect.poll(async()=>reading.evaluate(el=>Math.abs(el.scrollTop-before))).toBeLessThanOrEqual(3);
   expect(await page.evaluate(()=>location.pathname+location.search)).toBe('/tools/browser-fixture/student-material-cms-operational.php?page=current&cohort=cohort-a&long=1');
   await page.screenshot({path:'student-visual-audit/material-continuity/'+device.name+'-after-note-save.png',animations:'disabled'});
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
