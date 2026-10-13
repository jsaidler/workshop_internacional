const {test,expect}=require('@playwright/test');

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
    '/aluno/duvidas.php': '/tools/browser-fixture/student-secondary-screens-audit.html?screen=questions-list',
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
  await expect(choices).toHaveAttribute('href',[
    '/aluno/','/aluno/agenda.php','/aluno/cursos.php','/aluno/caderno.php','/aluno/ferramentas.php'
  ]);
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
  await page.keyboard.press('Escape');
  await expect(modal).not.toBeVisible();
  await page.locator('.student-v2-notebook-card a.student-process-card-main').first().click();
  await expect(page).toHaveURL(origin+'/aluno/teste.php?id=12');
  await page.goBack();
  await globalArea(page,'/aluno/caderno.php','phone');
  await expect(page.locator('.student-v2-notebook-card')).toHaveCount(3);
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
