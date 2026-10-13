const {test,expect}=require('@playwright/test');
const fs=require('fs');
const url='http://127.0.0.1:8099/tools/browser-fixture/student-courses.php';
const sizes=[['narrow',320,720],['phone',390,844],['desktop',1440,1100]];
const states=['empty','single','multiple','detail','no-material','partial','attention'];
async function shot(page,name){fs.mkdirSync('student-visual-audit/courses',{recursive:true});await page.screenshot({path:'student-visual-audit/courses/'+name+'.png',animations:'disabled'});}
for(const [device,width,height] of sizes){
  for(const state of states){
    test('Courses operational renderer '+state+' '+device,async({page})=>{
      const errors=[];page.on('pageerror',e=>errors.push(e.message));
      await page.setViewportSize({width,height});
      const result=await page.goto(url+'?state='+state,{waitUntil:'networkidle'});
      expect(result?.status()).toBe(200);
      await expect(page.locator('[data-student-courses]')).toBeVisible();
      const nav=page.locator('nav.student-mobile-nav');
      await expect(nav.locator('a')).toHaveCount(5);
      await expect(nav.locator('a[href="/aluno/cursos.php"]')).toHaveAttribute('aria-current','page');
      if(state==='empty'){
        await expect(page.getByRole('heading',{name:'Seus cursos'})).toBeVisible();
        await expect(page.getByText('Nenhuma matrícula ativa')).toBeVisible();
        await expect(page.locator('[data-course-material]')).toHaveCount(0);
      }else if(state==='multiple'){
        await expect(page.getByRole('heading',{name:'Seus cursos'})).toBeVisible();
        await expect(page.locator('[data-courses-selection] a')).toHaveCount(2);
        await expect(page.locator('[data-courses-selection] a').first()).toHaveAttribute('href','/aluno/cursos.php?cohort=cohort-a');
        await expect(page.locator('[data-courses-selection] a').last()).toHaveAttribute('href','/aluno/cursos.php?cohort=cohort-b');
      }else{
        await expect(page.getByRole('heading',{name:'Positivo direto em filme de raio-X'})).toBeVisible();
        await expect(page.locator('[data-course-material]')).toBeVisible();
        await expect(page.locator('.student-v2-course-local-nav a')).toHaveCount(2);
        await expect(page.locator('.student-v2-course-local-nav a[href="/aluno/duvidas.php?cohort=cohort-a"]')).toBeVisible();
        if(state==='single')await expect(page.getByRole('link',{name:'Todos os cursos'})).toHaveCount(0);
        else await expect(page.getByRole('link',{name:/Todos os cursos/})).toBeVisible();
        if(state==='no-material'){
          await expect(page.locator('[data-course-material-empty]')).toBeVisible();
          await expect(page.getByText('As aulas aparecerão conforme forem cadastradas.')).toBeVisible();
        }else{
          await expect(page.locator('.student-v2-course-page')).toHaveCount(3);
          const rows=page.locator('.student-v2-course-page');
          await expect(rows.nth(0).locator('[data-state]')).toHaveAttribute('data-state','available');
          await expect(rows.nth(1).locator('[data-state]')).toHaveAttribute('data-state','partial');
          await expect(rows.nth(2).locator('[data-state]')).toHaveAttribute('data-state','scheduled');
          await expect(rows.nth(0)).toHaveAttribute('href','/material/exposicao?cohort=cohort-a');
          await expect(rows.nth(1)).toHaveAttribute('href','/material/processo?cohort=cohort-a');
          const details=page.locator('[data-course-releases]');
          await expect(details).toHaveCount(1);
          await expect(details).not.toHaveAttribute('open','');
          await details.locator('summary').click();
          await expect(details.locator('.student-v2-course-release-row')).toHaveCount(2);
          await details.locator('summary').click();
        }
        if(state==='attention'){
          await expect(page.locator('[data-course-attention]')).toBeVisible();
          await expect(page.locator('[data-course-attention] a[href="/aluno/teste.php?id=17&view=review"]')).toHaveCount(1);
          await expect(page.locator('[data-course-attention] a[href="/aluno/duvidas.php?cohort=cohort-a"]')).toHaveCount(1);
        }
      }
      expect(errors).toEqual([]);
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
      expect(overflow).toBeLessThanOrEqual(1);
      if(width<=820){
        const geometry=await page.evaluate(()=>{
          const n=document.querySelector('.student-mobile-nav').getBoundingClientRect(),m=document.querySelector('.student-main').getBoundingClientRect();
          return{bottom:m.bottom,nav:n.top,viewport:innerHeight,navBottom:n.bottom};
        });
        expect(geometry.bottom).toBeLessThanOrEqual(geometry.nav+1);
        expect(Math.abs(geometry.navBottom-geometry.viewport)).toBeLessThanOrEqual(2);
      }
      await page.locator('main.student-main').evaluate(el=>el.scrollTop=0);
      await shot(page,state+'-'+device+'-start');
      await page.locator('main.student-main').evaluate(el=>el.scrollTop=el.scrollHeight);
      await shot(page,state+'-'+device+'-end');
    });
  }
}
test('multiple courses preserve explicit scope in direct links and browser Back',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(url+'?state=multiple',{waitUntil:'networkidle'});
  await page.locator('[data-courses-selection] a').first().click();
  await expect(page).toHaveURL(/cohort=cohort-a/);
  await expect(page.getByRole('heading',{name:'Positivo direto em filme de raio-X'})).toBeVisible();
  await page.goBack();
  await expect(page.locator('[data-courses-selection] a')).toHaveCount(2);
  await page.goForward();
  await expect(page.getByRole('heading',{name:'Positivo direto em filme de raio-X'})).toBeVisible();
});
for(const theme of ['dark','light']){
  test('Course detailed view uses '+theme+' palette and keeps material readable',async({page})=>{
    await page.setViewportSize({width:390,height:844});
    await page.goto(url+'?state=attention',{waitUntil:'networkidle'});
    await page.evaluate(t=>document.documentElement.setAttribute('data-theme',t),theme);
    await shot(page,'attention-phone-'+theme);
    await expect(page.locator('[data-course-attention]')).toBeVisible();
  });
}
