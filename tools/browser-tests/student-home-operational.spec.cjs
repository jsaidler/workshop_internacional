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
  await expect(page.locator('body')).not.toContainText('https://example.org/completed');
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
  await expect(page.getByRole('link',{name:'Temporizador'})).toHaveCount(0);
});
test('multiple courses expose scoped materials and questions without mandatory course selection',async({page})=>{
  await page.goto(base+'?state=multiple');
  const courses=page.locator('.student-home-course');
  await expect(courses).toHaveCount(2);
  await expect(courses.nth(0).getByRole('link',{name:'Abrir material'})).toHaveAttribute('href',/cohort=cohort-a/);
  await expect(courses.nth(1).getByRole('link',{name:'Abrir material'})).toHaveAttribute('href',/cohort=cohort-b/);
  await expect(page.getByText('Escolha o curso')).toHaveCount(0);
});
test('New record opens one operational sheet in Home and preserves native POST destination, Escape and focus',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(base+'?state=active');
  const opener=page.locator('.student-home-quick [data-record-create-open]');
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
  await page.screenshot({path:'student-visual-audit/home/error-phone-drawer.png'});
});
for(const [device,width,height] of [['narrow',320,720],['phone',390,844],['desktop',1440,1100]]){
  for(const state of ['active','study','multiple','empty','feedback','no-link','long','restricted']){
    test('Home operational renderer '+state+' '+device,async({page})=>{
      const errors=[];page.on('pageerror',e=>errors.push(e.message));
      await page.setViewportSize({width,height});
      const response=await page.goto(base+'?state='+state,{waitUntil:'networkidle'});
      expect(response?.status()).toBe(200);
      await expect(page.locator('[data-student-home]')).toBeVisible();
      await expect(page.locator('nav.student-mobile-nav a')).toHaveCount(4);
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
      if(width<=760)await main.evaluate(el=>{el.scrollTop=Math.round((el.scrollHeight-el.clientHeight)/2);});
      else await page.evaluate(()=>scrollTo(0,Math.round((document.documentElement.scrollHeight-innerHeight)/2)));
      await page.screenshot({path:'student-visual-audit/home/'+state+'-'+device+'-middle.png',animations:'disabled'});
      if(width<=760)await main.evaluate(el=>{el.scrollTop=el.scrollHeight;});
      else await page.evaluate(()=>scrollTo(0,document.documentElement.scrollHeight));
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
    await page.screenshot({path:'student-visual-audit/home/long-phone-'+theme+'-start.png'});
    await page.locator('.student-home-quick [data-record-create-open]').click();
    await expect(page.locator('[data-record-create-dialog]')).toBeVisible();
    await page.screenshot({path:'student-visual-audit/home/long-phone-'+theme+'-drawer.png'});
    const doc=await page.evaluate(()=>({scroll:document.documentElement.scrollWidth,client:document.documentElement.clientWidth}));
    expect(doc.scroll-doc.client).toBeLessThanOrEqual(1);
  });
}
