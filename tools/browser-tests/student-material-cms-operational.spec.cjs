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
    await expect(notes.locator('form[action="/aluno/material-anotacao.php"] input[name="_csrf"]').first()).toHaveValue('fixture-cms-csrf-no-write');
    await expect(notes.locator('summary').first()).toContainText('Anotações');
    await expect(page.locator('header[data-cms-public-header] a.brand')).toHaveAttribute('href','/aluno/');
    await expect(page.locator('.student-desktop-nav a')).toHaveCount(5);
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
    expect(errors).toEqual([]);
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
test('material last released page does not list blocked next material',async({page})=>{
 const res=await page.goto(base+'?page=next&cohort=cohort-a',{waitUntil:'networkidle'});
 expect(res?.status()).toBe(200);
 await expect(page.locator('.cms-student-study-context a[rel="prev"]')).toContainText('Processo químico completo');
 await expect(page.locator('.cms-student-study-context a[rel="next"]')).toHaveCount(0);
 await expect(page.locator('body')).not.toContainText('Somente depois da liberação');
});
