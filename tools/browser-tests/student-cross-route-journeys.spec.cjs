const {test,expect}=require('@playwright/test');
const fs=require('node:fs');
fs.mkdirSync('student-visual-audit/journeys',{recursive:true});

/**
 * Navigates canonical student URLs without an authenticated server.
 * Each HTTP response is the REAL operational PHP view rendered by a browser fixture.
 * This tests inter-route continuity, links, history, and global shell, not DB authorization.
 */
const origin='http://127.0.0.1:8099';
async function mountOperationalViews(page){
  const destinations={
    '/aluno/': '/tools/browser-fixture/student-home.php?state=active',
    '/aluno/agenda.php': '/tools/browser-fixture/student-agenda.php?state=regular',
    '/aluno/caderno.php': '/tools/browser-fixture/student-notebook.php?state=mixed',
    '/aluno/ferramentas.php': '/tools/browser-fixture/student-laboratory.php?state=full',
    '/aluno/inventario.php': '/tools/browser-fixture/student-inventory-movement.php?mode=inventory',
    '/aluno/inventario-item.php': '/tools/browser-fixture/student-inventory-item-direct.php?mode=editor',
    '/aluno/preparos.php': '/tools/browser-fixture/student-long-editors.php?kind=preparation&mode=list',
    '/aluno/calibracao.php': '/tools/browser-fixture/student-long-editors.php?kind=calibration&mode=list',
    '/aluno/duvidas.php': '/tools/browser-fixture/student-questions-operational.php',
    '/aluno/teste.php': '/tools/browser-fixture/student-caderno-product-audit.php?screen=record-plan',
  };
  await page.route('**/aluno/**',async route=>{
    const request=new URL(route.request().url());
    let mapped=destinations[request.pathname];
    if(request.pathname==='/aluno/cursos.php'){
      const cohort=request.searchParams.get('cohort');
      mapped='/tools/browser-fixture/student-courses.php?state=multiple'+(cohort?'&cohort='+encodeURIComponent(cohort):'');
    }
    if(!mapped)return route.continue();
    if(request.pathname==='/aluno/duvidas.php'){
      mapped='/tools/browser-fixture/student-questions-operational.php?cohort='+encodeURIComponent(request.searchParams.get('cohort')||'cohort-a')+(request.searchParams.get('id')?'&id='+encodeURIComponent(request.searchParams.get('id')):'');
    }
    if(request.pathname==='/aluno/preparos.php'||request.pathname==='/aluno/calibracao.php'){
      const kind=request.pathname.endsWith('preparos.php')?'preparation':'calibration';
      const mode=request.searchParams.has('novo')?'new':request.searchParams.has('editar')?'edit':'list';
      mapped='/tools/browser-fixture/student-long-editors.php?kind='+kind+'&mode='+mode;
    }
    if(request.pathname==='/aluno/inventario-item.php'){
      mapped='/tools/browser-fixture/student-inventory-item-direct.php?mode=editor';
    }
    if(request.pathname==='/aluno/agenda.php'&&request.searchParams.get('view')){
      mapped+='&view='+encodeURIComponent(request.searchParams.get('view'));
    }
    const response=await route.fetch({url:origin+mapped});
    await route.fulfill({response});
  });
  await page.route('**/material/**',async route=>{
    const response=await route.fetch({url:origin+'/tools/browser-fixture/student-material-context.html'});
    await route.fulfill({response});
  });
}
async function globalArea(page,destination,viewport){
  const nav=page.locator(viewport==='desktop'?'nav.student-desktop-nav':'nav.student-mobile-nav');
  await expect(nav).toBeVisible();
  const choices=nav.locator('a:not(.student-sidebar-brand)');
  await expect(choices).toHaveCount(5);
  const canonical=['/aluno/','/aluno/agenda.php','/aluno/cursos.php','/aluno/caderno.php','/aluno/ferramentas.php'];
  for(let i=0;i<canonical.length;i++)await expect(choices.nth(i)).toHaveAttribute('href',canonical[i]);
  await expect(page.locator('a.student-wordmark')).toHaveAttribute('href','/aluno/');
  const active=nav.locator('a[aria-current="page"]');
  await expect(active).toHaveCount(1);
  await expect(active).toHaveAttribute('href',destination);
  if(viewport!=='desktop'){
    const box=await page.evaluate(()=>{
      const main=document.querySelector('.student-main');
      const nav=document.querySelector('nav.student-mobile-nav');
      if(!main||!nav)return null;
      return{readingBottom:main.getBoundingClientRect().bottom,navTop:nav.getBoundingClientRect().top,navBottom:nav.getBoundingClientRect().bottom,viewport:innerHeight};
    });
    expect(box).not.toBeNull();
    expect(box.readingBottom).toBeLessThanOrEqual(box.navTop+1);
    expect(Math.abs(box.navBottom-box.viewport)).toBeLessThanOrEqual(2);
  }
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
}
const sizes=[['narrow',320,720],['phone',390,844],['desktop',1440,1100]];
for(const [device,width,height] of sizes){
  test('actual Home → course → material → course, Back and contextual return '+device,async({page})=>{
    await page.setViewportSize({width,height});
    await mountOperationalViews(page);
    await page.goto(origin+'/aluno/',{waitUntil:'networkidle'});
    await expect(page.locator('[data-student-home]')).toBeVisible();
    await globalArea(page,'/aluno/',device);
    await page.locator(device==='desktop'?'nav.student-desktop-nav a[href="/aluno/cursos.php"]':'nav.student-mobile-nav a[href="/aluno/cursos.php"]').click();
    await expect(page).toHaveURL(origin+'/aluno/cursos.php');
    await expect(page.locator('[data-courses-selection] a')).toHaveCount(2);
    await globalArea(page,'/aluno/cursos.php',device);
    await page.locator('[data-courses-selection] a').first().click();
    await expect(page).toHaveURL(origin+'/aluno/cursos.php?cohort=cohort-a');
    await expect(page.locator('[data-course-material]')).toBeVisible();
    await expect(page.getByRole('heading',{name:'Positivo direto em filme de raio-X'})).toBeVisible();
    await globalArea(page,'/aluno/cursos.php',device);
    await page.locator('.student-v2-course-page').first().click();
    await expect(page).toHaveURL(origin+'/material/exposicao?cohort=cohort-a');
    await expect(page.getByTestId('study-context')).toBeVisible();
    await expect(page.getByTestId('back-course')).toHaveAttribute('href','/aluno/cursos.php?cohort=cohort-a');
    const materialBar=page.locator('nav.student-mobile-nav');
    await expect(materialBar.locator('a')).toHaveCount(5);
    await expect(materialBar.locator('a[href="/aluno/cursos.php"]')).toHaveAttribute('aria-current','page');
    await page.getByTestId('questions').click();
    await expect(page).toHaveURL(origin+'/aluno/duvidas.php?cohort=cohort-a');
    await expect(page.getByRole('heading',{name:'Dúvidas',exact:true,level:1})).toBeVisible();
    await expect(page.locator('.student-course-context-nav a[aria-current="page"]')).toHaveText('Dúvidas');
    await expect(page.locator('nav.student-mobile-nav a[href="/aluno/cursos.php"]')).toHaveAttribute('aria-current','page');
    await expect(page.locator('.student-course-context-back')).toHaveAttribute('href','/aluno/cursos.php?cohort=cohort-a');
    await expect(page.locator('.student-course-context-nav a').first()).toHaveAttribute('href','/aluno/cursos.php?cohort=cohort-a');
    await page.screenshot({path:'student-visual-audit/journeys/questions-list-'+device+'.png'});
    await page.locator('a.student-test-row[href="/aluno/duvidas.php?cohort=cohort-a&id=1"]').click();
    await expect(page).toHaveURL(origin+'/aluno/duvidas.php?cohort=cohort-a&id=1');
    await expect(page.getByRole('heading',{name:'Segunda revelação'})).toBeVisible();
    await expect(page.locator('[data-student-local-key="question-thread"] form textarea[name="body"]')).toBeVisible();
    await expect(page.locator('nav.student-mobile-nav a[href="/aluno/cursos.php"]')).toHaveAttribute('aria-current','page');
    await expect(page.locator('.student-back')).toHaveAttribute('href','/aluno/duvidas.php?cohort=cohort-a');
    await page.screenshot({path:'student-visual-audit/journeys/question-thread-'+device+'.png'});
    await page.goBack();
    await expect(page.getByRole('heading',{name:'Dúvidas',exact:true,level:1})).toBeVisible();
    await page.goBack();
    await expect(page).toHaveURL(origin+'/material/exposicao?cohort=cohort-a');
    await page.getByTestId('back-course').click();
    await expect(page).toHaveURL(origin+'/aluno/cursos.php?cohort=cohort-a');
    await expect(page.locator('[data-course-material]')).toBeVisible();
    await page.goBack();
    await expect(page).toHaveURL(origin+'/material/exposicao?cohort=cohort-a');
    await page.goBack();
    await expect(page).toHaveURL(origin+'/aluno/cursos.php?cohort=cohort-a');
    await page.goBack();
    await expect(page).toHaveURL(origin+'/aluno/cursos.php');
    await expect(page.locator('[data-courses-selection] a')).toHaveCount(2);
    await page.goForward();
    await expect(page.locator('[data-course-material]')).toBeVisible();
    await expect(page).toHaveURL(origin+'/aluno/cursos.php?cohort=cohort-a');
  });
}
test('real sections preserve Agenda views and notebook creation across native browser navigation',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await mountOperationalViews(page);
  await page.goto(origin+'/aluno/',{waitUntil:'networkidle'});
  await page.locator('nav.student-mobile-nav a[href="/aluno/agenda.php"]').click();
  await expect(page.locator('[data-student-agenda]')).toHaveAttribute('data-agenda-view','encontros');
  await globalArea(page,'/aluno/agenda.php','phone');
  await page.locator('[data-agenda-view-link="ouvintes"]').click();
  await expect(page).toHaveURL(origin+'/aluno/agenda.php?view=ouvintes');
  await expect(page.locator('[data-student-agenda]')).toHaveAttribute('data-agenda-view','ouvintes');
  await page.goBack();
  await expect(page.locator('[data-student-agenda]')).toHaveAttribute('data-agenda-view','encontros');
  await page.locator('nav.student-mobile-nav a[href="/aluno/caderno.php"]').click();
  await globalArea(page,'/aluno/caderno.php','phone');
  await expect(page.locator('.student-v2-notebook-card')).toHaveCount(3);
  await page.locator('.student-v2-notebook-create').click();
  const modal=page.locator('[data-record-create-dialog]');
  await expect(modal).toBeVisible();
  const title=modal.locator('input[name="title"]');
  await title.fill('EI 400 — nova variação');
  await modal.locator('label.choice-option').filter({hasText:'Curso'}).click();
  await expect(modal.locator('input[name="context_scope"][value="course"]')).toBeChecked();
  await modal.locator('select[name="context_cohort_id"]').selectOption('101');
  const beforeClose=await page.evaluate(()=>({url:location.href,length:history.length}));
  await page.keyboard.press('Escape');
  await expect(modal).not.toBeVisible();
  await expect(page.locator('.student-v2-notebook-create')).toBeFocused();
  await page.locator('.student-v2-notebook-create').click();
  await expect(modal).toBeVisible();
  await expect(title).toHaveValue('EI 400 — nova variação');
  await expect(modal.locator('input[name="context_scope"][value="course"]')).toBeChecked();
  await expect(modal.locator('select[name="context_cohort_id"]')).toHaveValue('101');
  expect(await page.evaluate(()=>({url:location.href,length:history.length}))).toEqual(beforeClose);
  await page.screenshot({path:'student-visual-audit/journeys/notebook-reopen-draft-phone.png',animations:'disabled'});
  await modal.locator('[data-record-create-close]').click();
  await expect(modal).not.toBeVisible();
  await page.locator('.student-v2-notebook-card a.student-process-card-main').first().click();
  await expect(page).toHaveURL(origin+'/aluno/teste.php?id=12');
  await page.goBack();
  await globalArea(page,'/aluno/caderno.php','phone');
  await expect(page.locator('.student-v2-notebook-card')).toHaveCount(3);
  const first=page.locator('.student-v2-notebook-card[data-record-id="12"]');
  const menu=first.locator('details.student-record-menu');
  await menu.locator('summary').click();
  await expect(menu.getByRole('link',{name:'Comparar com outro registro'})).toHaveAttribute('href','/aluno/comparar-processos.php?id%5B%5D=12');
  const duplicate=menu.getByRole('button',{name:'Duplicar registro'}).locator('xpath=ancestor::form');
  await expect(duplicate.locator('[name="action"]')).toHaveValue('duplicate');
  await expect(duplicate.locator('[name="id"]')).toHaveValue('12');
  await expect(menu.locator('select[name="visibility"]')).toHaveValue('cohort');
  await expect(menu.getByRole('link',{name:'Excluir registro'})).toHaveAttribute('href','/aluno/excluir-teste.php?id=12');
  await menu.locator('summary').click();
  await expect(menu.getByRole('link',{name:'Excluir registro'})).toBeHidden();
});
test('laboratory deep-linked calculators remain contextual after navigating from notebook',async({page})=>{
  await page.setViewportSize({width:320,height:720});
  await mountOperationalViews(page);
  await page.goto(origin+'/aluno/caderno.php',{waitUntil:'networkidle'});
  await page.locator('nav.student-mobile-nav a[href="/aluno/ferramentas.php"]').click();
  await expect(page.locator('[data-student-laboratory]')).toBeVisible();
  await globalArea(page,'/aluno/ferramentas.php','narrow');
  await expect(page.locator('#receitas')).not.toHaveAttribute('open','');
  await page.evaluate(()=>{location.hash='#receitas';});
  await expect(page.locator('#receitas')).toHaveAttribute('open','');
  await expect(page.locator('#receitas [data-solution-tool]')).toBeVisible();
  await page.goBack();
  await expect(page).toHaveURL(origin+'/aluno/ferramentas.php');
  await expect(page.locator('[data-student-laboratory]')).toBeVisible();
  await page.goBack();
  await expect(page).toHaveURL(origin+'/aluno/caderno.php');
  await globalArea(page,'/aluno/caderno.php','narrow');
});

test('direct link to a question keeps the chosen course and logical return',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await mountOperationalViews(page);
  await page.goto(origin+'/aluno/duvidas.php?cohort=cohort-b&id=2',{waitUntil:'networkidle'});
  await expect(page.getByRole('heading',{name:'Segunda revelação'})).toBeVisible();
  await globalArea(page,'/aluno/cursos.php','phone');
  await expect(page.locator('.student-back')).toHaveAttribute('href','/aluno/duvidas.php?cohort=cohort-b');
  await page.locator('.student-back').click();
  await expect(page).toHaveURL(origin+'/aluno/duvidas.php?cohort=cohort-b');
  await expect(page.getByRole('heading',{name:'Dúvidas',exact:true,level:1})).toBeVisible();
  await expect(page.locator('.student-course-context-nav a').first()).toHaveAttribute('href','/aluno/cursos.php?cohort=cohort-b');
});

for(const [device,width,height] of sizes){
  test('Laboratório → Inventário → histórico e retorno contextual, Back/Forward '+device,async({page})=>{
    await page.setViewportSize({width,height});
    await mountOperationalViews(page);
    await page.goto(origin+'/aluno/ferramentas.php',{waitUntil:'networkidle'});
    await expect(page.locator('[data-student-laboratory]')).toBeVisible();
    await page.locator('[data-lab-link="inventario"]').click();
    await expect(page).toHaveURL(origin+'/aluno/inventario.php');
    await expect(page.getByRole('heading',{name:'Inventário',exact:true})).toBeVisible();
    await expect(page.locator('[data-student-local-key="inventory-stock"] .student-inventory-item')).toHaveCount(1);
    await expect(page.locator('[data-student-local-key="inventory-history"]')).toBeVisible();
    await globalArea(page,'/aluno/ferramentas.php',device);
    await expect(page.locator('.student-appbar a.student-back').first()).toHaveAttribute('href','/aluno/ferramentas.php');
    await page.screenshot({path:'student-visual-audit/journeys/lab-inventory-'+device+'.png',animations:'disabled'});
    await page.goBack();
    await expect(page).toHaveURL(origin+'/aluno/ferramentas.php');
    await expect(page.locator('[data-student-laboratory]')).toBeVisible();
    await page.goForward();
    await expect(page.getByRole('heading',{name:'Inventário',exact:true})).toBeVisible();
    await page.locator('.student-appbar a.student-back').first().click();
    await expect(page).toHaveURL(origin+'/aluno/ferramentas.php');
    await expect(page.locator('[data-student-laboratory]')).toBeVisible();
    await page.locator('[data-lab-link="inventario"]').click();
    const item=page.getByRole('link',{name:'Editar item'});
    const beforeEditor=await page.evaluate(()=>({url:location.href,length:history.length}));
    await item.click();
    if(device==='desktop'){
      const dialog=page.locator('[data-student-editor-dialog]');
      await expect(dialog).toBeVisible();
      await expect(dialog.locator('[data-student-editor] form')).toBeVisible();
      await expect(page).toHaveURL(origin+'/aluno/inventario.php');
      await globalArea(page,'/aluno/ferramentas.php',device);
      await page.screenshot({path:'student-visual-audit/journeys/lab-inventory-item-desktop-modal.png',animations:'disabled'});
      await dialog.locator('.student-create-dialog-head [data-student-editor-close]').click();
      await expect(dialog).toBeHidden();
      await expect(item).toBeFocused();
      expect(await page.evaluate(()=>({url:location.href,length:history.length}))).toEqual(beforeEditor);
    }else{
      await expect(page).toHaveURL(origin+'/aluno/inventario-item.php?id=7');
      await expect(page.locator('.student-main > [data-student-editor] form')).toBeVisible();
      await globalArea(page,'/aluno/ferramentas.php',device);
      await expect(page.locator('.student-appbar a.student-back')).toHaveAttribute('href','/aluno/inventario.php');
      await page.screenshot({path:'student-visual-audit/journeys/lab-inventory-item-'+device+'.png',animations:'disabled'});
      await page.locator('.student-appbar a.student-back').click();
      await expect(page).toHaveURL(origin+'/aluno/inventario.php');
    }
    await expect(page.locator('.student-inventory-item')).toBeVisible();
  });
}

for(const [kind,label,route] of [
  ['preparation','Predefinições de revelação','/aluno/preparos.php'],
  ['calibration','Referências de calibração','/aluno/calibracao.php']
]){
  test('Laboratório → '+label+' → editor contextual e retorno mobile',async({page})=>{
    await page.setViewportSize({width:390,height:844});
    await mountOperationalViews(page);
    await page.goto(origin+'/aluno/ferramentas.php',{waitUntil:'networkidle'});
    await page.locator('[data-lab-link="'+(kind==='preparation'?'preparos':'calibracao')+'"]').click();
    await expect(page).toHaveURL(origin+route);
    await expect(page.getByRole('heading',{name:label,exact:true})).toBeVisible();
    await globalArea(page,'/aluno/ferramentas.php','phone');
    await page.getByRole('link',{name:kind==='preparation'?'Nova predefinição':'Nova referência'}).click();
    await expect(page).toHaveURL(origin+route+'?novo=1');
    await expect(page.locator('.student-main > [data-student-editor] form')).toBeVisible();
    await expect(page.locator('.student-appbar a.student-back')).toHaveAttribute('href',route);
    await globalArea(page,'/aluno/ferramentas.php','phone');
    await page.screenshot({path:'student-visual-audit/journeys/lab-'+kind+'-editor-phone.png',animations:'disabled'});
    await page.goBack();
    await expect(page).toHaveURL(origin+route);
    await expect(page.getByRole('heading',{name:label,exact:true})).toBeVisible();
    await page.goForward();
    await expect(page.locator('.student-main > [data-student-editor] form')).toBeVisible();
    await page.locator('.student-appbar a.student-back').click();
    await expect(page).toHaveURL(origin+route);
    await page.locator('.student-appbar a.student-back').click();
    await expect(page).toHaveURL(origin+'/aluno/ferramentas.php');
    await expect(page.locator('[data-student-laboratory]')).toBeVisible();
  });
}
