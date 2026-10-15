const {test,expect}=require('@playwright/test');
const fs=require('fs');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-home.php';

test('all independent sources are visible together; private links belong only to authorized scheduled future meeting',async({page})=>{
  await page.goto(base+'?state=active');
  await expect(page.locator('[data-home-meeting]')).toBeVisible();
  await expect(page.locator('[data-home-feedback]')).toBeVisible();
  await expect(page.getByRole('heading',{name:'Continuar'})).toBeVisible();
  await expect(page.getByRole('heading',{name:'Meus cursos'})).toBeVisible();
  await expect(page.getByRole('heading',{name:'Caderno recente'})).toBeVisible();
  await expect(page.getByRole('link',{name:'Acessar encontro ↗'})).toHaveAttribute('href','https://example.org/private-meeting');
  await expect(page.locator('.student-home-course-actions a[href*="duvidas.php?cohort=cohort-a"]')).toHaveCount(1);
  await expect(page.locator('.student-home-course-actions a[href*="material-do-curso?cohort=cohort-a"]')).toHaveCount(1);
  await expect(page.locator('nav.student-mobile-nav a[href="/aluno/agenda.php"]')).toHaveCount(1);
  await page.locator('.student-tools-trigger').click();
  const toolbox=page.locator('[data-student-toolbox]');
  await expect(toolbox).toBeVisible();
  await expect(toolbox.locator('.student-toolbox-links a').first()).toHaveAttribute('href','/aluno/agenda.php');
  await expect(page.locator('.student-v2-footer-actions a[href="/aluno/processamentos.php"]')).toHaveAttribute('href','/aluno/processamentos.php');
  await expect(page.locator('body')).not.toContainText('https://example.org/completed');
});
test('Home retains access during the documented time slot, without asserting attendance',async({page})=>{
  await page.goto(base+'?state=ongoing');
  await expect(page.locator('[data-home-meeting]')).toBeVisible();
  await expect(page.getByText('Horário previsto do encontro')).toBeVisible();
  await expect(page.getByRole('link',{name:'Acessar encontro ↗'})).toHaveAttribute('href','https://example.org/private-meeting');
});
test('Home surfaces the Caderno task when there is no meeting or feedback',async({page})=>{
  await page.goto(base+'?state=record');
  await expect(page.locator('[data-home-record-feature]')).toBeVisible();
  await expect(page.locator('[data-home-record-feature] a')).toHaveAttribute('href','/aluno/teste.php?id=22&view=process');
  await expect(page.getByRole('heading',{name:'Meus cursos'})).toBeVisible();
});
test('Home never exposes non-http meeting URL and keeps Agenda discoverable',async({page})=>{
  await page.goto(base+'?state=no-link');
  await expect(page.locator('[data-home-meeting]')).toBeVisible();
  await expect(page.getByText('Link ainda não informado')).toBeVisible();
  await expect(page.getByRole('link',{name:'Acessar encontro ↗'})).toHaveCount(0);
  await expect(page.locator('[data-home-meeting] a[href="/aluno/agenda.php"]')).toBeVisible();
});
test('no enrollment and restricted tools stay functional without fabricated metrics',async({page})=>{
  await page.goto(base+'?state=empty');
  await expect(page.getByText('Nenhuma matrícula ativa')).toBeVisible();
  await expect(page.locator('[data-home-empty-feature]')).toBeVisible();
  await expect(page.locator('[data-record-create-open]')).toHaveCount(1);
  await page.goto(base+'?state=restricted');
  await expect(page.getByText('Material aguardando liberação')).toBeVisible();
  await expect(page.getByRole('link',{name:'Processamentos'})).toHaveCount(0);
});
test('multiple courses expose scoped materials and questions without mandatory course selection',async({page})=>{
  await page.goto(base+'?state=multiple');
  const courses=page.locator('.student-v2-course');
  await expect(courses).toHaveCount(2);
  await expect(courses.nth(0).getByRole('link',{name:'Abrir material'})).toHaveAttribute('href',/cohort=cohort-a/);
  await expect(courses.nth(1).getByRole('link',{name:'Abrir material'})).toHaveAttribute('href',/cohort=cohort-b/);
  await expect(page.getByText('Escolha o curso')).toHaveCount(0);
  const continueMaterial=page.locator('.student-v2-section').filter({has:page.getByRole('heading',{name:'Continuar'})}).locator('.student-v2-row').first();
  await expect(continueMaterial).toContainText('Positivo direto em filme de raio-X');
  await expect(continueMaterial).toContainText('Turma Outubro 2026');
});
test('New record opens one operational sheet in Home and preserves native POST destination, Escape and focus',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(base+'?state=active');
  const opener=page.locator('.student-v2-footer-actions [data-record-create-open]');
  await opener.click();
  const dialog=page.locator('[data-record-create-dialog]');
  await expect(dialog).toBeVisible();
  await expect(dialog.locator('form')).toHaveAttribute('action','/aluno/');
  await expect(dialog.getByRole('textbox',{name:'Título'})).toBeVisible();
  await expect(dialog.locator('form [name="_csrf"]')).toHaveValue('fixture-csrf');
  await dialog.getByRole('textbox',{name:'Título'}).fill('Experimento não salvo');
  await page.keyboard.press('Escape');
  await expect(dialog).not.toBeVisible();
  await expect(opener).toBeFocused();
  await opener.click();
  await expect(dialog.getByRole('textbox',{name:'Título'})).toHaveValue('Experimento não salvo');
  await dialog.getByRole('button',{name:'Fechar'}).click();
  await expect(opener).toBeFocused();
  fs.mkdirSync('student-visual-audit/home',{recursive:true});
});
for(const [device,width,height] of [['phone',390,844],['narrow',320,720],['desktop',1440,1100]]){
 test('shared creation drawer toggles personal/course fields '+device,async({page})=>{
   await page.setViewportSize({width,height});
   await page.goto(base+'?state=active');
   await page.locator('.student-v2-footer-actions [data-record-create-open]').click();
   const dialog=page.locator('[data-record-create-dialog]');
   const courseFields=dialog.locator('[data-process-course-context]');
   await expect(dialog.locator('input[name="context_scope"][value="personal"]')).toBeChecked();
   await expect(courseFields).toBeHidden();
   await expect(courseFields.locator('select')).not.toHaveAttribute('required','');
   await dialog.locator('label.choice-option:has(input[value="course"])').click();
   await expect(courseFields).toBeVisible();
   await expect(courseFields.locator('select')).toHaveAttribute('required','');
   fs.mkdirSync('student-visual-audit/home',{recursive:true});
   await page.screenshot({path:'student-visual-audit/home/context-course-'+device+'.png',animations:'disabled'});
   if(width===320){
     const submit=dialog.getByRole('button',{name:'Criar registro'});
     // A partially visible submit control does not pass the mobile gate.
     // Scroll to the real end of the dialog, not only until one pixel enters view.
     await dialog.evaluate(el=>{el.scrollTop=el.scrollHeight;});
     await expect(submit).toBeInViewport({ratio:1});
     const bottomGap=await submit.evaluate(el=>window.innerHeight-el.getBoundingClientRect().bottom);
     expect(bottomGap).toBeGreaterThanOrEqual(12);
     await page.screenshot({path:'student-visual-audit/home/context-course-narrow-submit.png',animations:'disabled'});
   }
   await dialog.locator('label.choice-option:has(input[value="personal"])').click();
   await expect(courseFields).toBeHidden();
   await expect(courseFields.locator('select')).not.toHaveAttribute('required','');
   await page.screenshot({path:'student-visual-audit/home/context-personal-'+device+'.png',animations:'disabled'});
 });
}
test('failed Home creation restores saved draft and sheet focus instead of sending user to Caderno',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(base+'?state=error');
  const dialog=page.locator('[data-record-create-dialog]');
  await expect(dialog).toBeVisible();
  await expect(dialog.locator('[role="alert"]')).toBeVisible();
  await expect(dialog.locator('[role="alert"]')).toBeFocused();
  await expect(dialog.getByRole('textbox',{name:'Título'})).toHaveValue('EI 400 — tratamento alterado');
  await expect(dialog.locator('select[name="context_cohort_id"]')).toHaveValue('12');
  await expect(dialog.locator('form')).toHaveAttribute('action','/aluno/');
  fs.mkdirSync('student-visual-audit/home',{recursive:true});
  await page.screenshot({path:'student-visual-audit/home/error-phone-drawer.png',animations:'disabled'});
});
for(const [device,width,height] of [['narrow',320,720],['phone',390,844],['desktop',1440,1100]]){
  for(const state of ['active','study','multiple','empty','feedback','record','no-link','long','restricted','ongoing']){
    test('Home operational renderer '+state+' '+device,async({page})=>{
      const errors=[];page.on('pageerror',e=>errors.push(e.message));
      await page.setViewportSize({width,height});
      const response=await page.goto(base+'?state='+state,{waitUntil:'networkidle'});
      expect(response?.status()).toBe(200);
      await expect(page.locator('[data-student-home]')).toBeVisible();
      await expect(page.locator('nav.student-mobile-nav a')).toHaveCount(5);
      await expect(page.locator('nav.student-mobile-nav a[href="/aluno/"]')).toHaveAttribute('aria-current','page');
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
      expect(overflow).toBeLessThanOrEqual(1);
      expect(errors).toEqual([]);
      if(width<=760){
        const boxes=await page.evaluate(()=>{const n=document.querySelector('.student-mobile-nav').getBoundingClientRect(),m=document.querySelector('main.student-main').getBoundingClientRect();return {navTop:n.top,mainBottom:m.bottom}});
        expect(boxes.mainBottom).toBeLessThanOrEqual(boxes.navTop+1);
      }
      fs.mkdirSync('student-visual-audit/home',{recursive:true});
      await page.screenshot({path:'student-visual-audit/home/'+state+'-'+device+'-start.png',animations:'disabled'});
      const main=page.locator('main.student-main');
      await main.evaluate(el=>{el.scrollTop=Math.round((el.scrollHeight-el.clientHeight)/2);});
      await page.screenshot({path:'student-visual-audit/home/'+state+'-'+device+'-middle.png',animations:'disabled'});
      await main.evaluate(el=>{el.scrollTop=el.scrollHeight;});
      await page.screenshot({path:'student-visual-audit/home/'+state+'-'+device+'-end.png',animations:'disabled'});
      if(state==='active'){
        const theme=await page.evaluate(()=>getComputedStyle(document.body).backgroundColor);
        expect(theme).toBeTruthy();
      }
    });
  }
}
for(const theme of ['light','dark']){
  test('Home real renderer color contrast and sheet state '+theme,async({page})=>{
    await page.setViewportSize({width:390,height:844});
    await page.goto(base+'?state=long',{waitUntil:'networkidle'});
    await page.evaluate(t=>document.documentElement.setAttribute('data-theme',t),theme);
    fs.mkdirSync('student-visual-audit/home',{recursive:true});
    await page.screenshot({path:'student-visual-audit/home/long-phone-'+theme+'-start.png',animations:'disabled'});
    await page.locator('.student-v2-footer-actions [data-record-create-open]').click();
    await expect(page.locator('[data-record-create-dialog]')).toBeVisible();
    await page.screenshot({path:'student-visual-audit/home/long-phone-'+theme+'-drawer.png',animations:'disabled'});
    const doc=await page.evaluate(()=>({scroll:document.documentElement.scrollWidth,client:document.documentElement.clientWidth}));
    expect(doc.scroll-doc.client).toBeLessThanOrEqual(1);
  });
}

test('new app shell keeps five named destinations, correct active state and usable mobile account actions',async({page})=>{
  await page.setViewportSize({width:320,height:720});
  await page.goto(base+'?state=active',{waitUntil:'networkidle'});
  const nav=page.locator('nav.student-mobile-nav');
  await expect(nav.locator('a')).toHaveCount(5);
  expect(await nav.locator('a .student-nav-label').allTextContents()).toEqual(['Início','Agenda','Cursos','Caderno','Laboratório']);
  const boxes=await nav.locator('a').evaluateAll(nodes=>nodes.map(n=>({width:n.getBoundingClientRect().width,height:n.getBoundingClientRect().height})));
  expect(boxes.every(b=>b.width>=44&&b.height>=44)).toBeTruthy();
  await page.locator('.student-user-menu summary').click();
  await expect(page.locator('.student-tools-menu-trigger')).toBeVisible();
  await page.locator('.student-tools-menu-trigger').click();
  await expect(page.locator('[data-student-toolbox]')).toBeVisible();
  await page.locator('[data-toolbox-close]').click();
});

test('secondary continuation never duplicates the prominent Home action',async({page})=>{
  await page.goto(base+'?state=feedback');
  await expect(page.locator('[data-home-feedback-feature]')).toBeVisible();
  await expect(page.locator('[data-home-feedback]')).toHaveCount(0);
  await expect(page.locator('[data-home-feedback-feature] .student-v2-cta')).toHaveAttribute('href','/aluno/teste.php?id=17&view=review');
  await page.goto(base+'?state=record');
  await expect(page.locator('[data-home-record-feature]')).toBeVisible();
  await expect(page.locator('.student-v2-row[href="/aluno/teste.php?id=22&view=process"]')).toHaveCount(0);
  await page.goto(base+'?state=study');
  await expect(page.locator('[data-home-study-feature]')).toBeVisible();
  await expect(page.locator('.student-v2-row[href*="/material-do-curso"]')).toHaveCount(0);
});

test('Home offers direct record and processing actions before the long course lists',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(base+'?state=active',{waitUntil:'networkidle'});
  const order=await page.locator('main.student-main').evaluate(main=>{
    const tile=main.querySelector('.student-v2-shortcuts'),courses=main.querySelector('#student-v2-courses-title'),notebook=main.querySelector('#student-v2-notebook-title');
    return [Boolean(tile),Boolean(courses),Boolean(notebook),tile?.compareDocumentPosition(courses)&Node.DOCUMENT_POSITION_FOLLOWING,tile?.compareDocumentPosition(notebook)&Node.DOCUMENT_POSITION_FOLLOWING];
  });
  expect(order).toEqual([true,true,true,4,4]);
  const tools=page.locator('.student-v2-footer-actions');
  await expect(tools.locator('[data-record-create-open]')).toHaveCount(1);
  await expect(tools.locator('a[href="/aluno/processamentos.php"]')).toHaveCount(1);
});
