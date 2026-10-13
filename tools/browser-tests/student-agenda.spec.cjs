const {test,expect}=require('@playwright/test');
const fs=require('fs');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-agenda.php';
const states=['empty','regular','listener','waiting','full','mixed','history','unsafe','two'];
const sizes=[['phone',390,844],['narrow',320,720],['desktop',1440,1100]];
const screenshot=async(page,name)=>{
  fs.mkdirSync('student-visual-audit/agenda',{recursive:true});
  await page.screenshot({path:'student-visual-audit/agenda/'+name+'.png',animations:'disabled'});
};
for(const [device,width,height] of sizes){
  for(const state of states){
    test('Agenda v2 encounters '+state+' '+device,async({page})=>{
      const errors=[];page.on('pageerror',e=>errors.push(e.message));
      await page.setViewportSize({width,height});
      const response=await page.goto(base+'?state='+state+'&view=encontros',{waitUntil:'networkidle'});
      expect(response?.status()).toBe(200);
      const agenda=page.locator('[data-student-agenda]');
      await expect(agenda).toHaveAttribute('data-agenda-view','encontros');
      await expect(page.getByRole('heading',{name:'Agenda',exact:true})).toBeVisible();
      await expect(page.getByRole('heading',{name:'Meus encontros'})).toBeVisible();
      await expect(page.getByRole('heading',{name:'Próximas turmas'})).toHaveCount(0);
      await expect(page.locator('[data-agenda-view-link="encontros"]')).toHaveAttribute('aria-current','page');
      await expect(page.locator('[data-agenda-view-link="ouvintes"]')).toHaveAttribute('href','?view=ouvintes');
      const nav=page.locator('nav.student-mobile-nav');
      await expect(nav.locator('a')).toHaveCount(5);
      await expect(nav.locator('a[href="/aluno/agenda.php"]')).toHaveAttribute('aria-current','page');
      if(['regular','listener','mixed','two'].includes(state)){
        await expect(page.locator('[data-agenda-upcoming] .agenda-meeting')).toHaveCount(state==='two'?2:1);
        await expect(page.locator('.student-v2-agenda-meeting-date time')).toHaveAttribute('datetime','2030-10-12T22:00:00Z');
        await expect(page.getByRole('link',{name:'Acessar encontro ↗'}).first()).toHaveAttribute('href','https://example.org/private-meeting');
        await expect(page.locator('[data-agenda-upcoming] .agenda-meeting .student-status').first()).toHaveText(state==='listener'?'Ouvinte':'Matriculado');
        if(state==='two'){
          await expect(page.locator('.student-v2-agenda-meeting.is-next')).toHaveCount(1);
          await expect(page.locator('.student-v2-agenda-meeting.is-later')).toHaveCount(1);
          await expect(page.getByText('Depois deste encontro')).toBeVisible();
        }
      }else{
        await expect(page.getByRole('link',{name:'Acessar encontro ↗'})).toHaveCount(0);
        await expect(page.locator('body')).not.toContainText('https://example.org/private-meeting');
      }
      if(state==='unsafe')await expect(page.getByText('Link de acesso ainda não informado.')).toBeVisible();
      if(state==='empty')await expect(page.getByText('Ainda não há encontros com data definida')).toBeVisible();
      if(state==='history')await expect(page.getByText('Nenhum próximo encontro agendado')).toBeVisible();
      if(['mixed','history'].includes(state)){
        const history=page.locator('[data-agenda-history]');
        await expect(history).toHaveCount(1);
        await expect(history).not.toHaveAttribute('open','');
        await history.locator('summary').click();
        await expect(history.locator('.agenda-meeting')).toBeVisible();
        await expect(history.locator('a[href*="previous-meeting"]')).toHaveCount(0);
        await history.locator('summary').click();
      }
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-window.innerWidth);
      expect(overflow).toBeLessThanOrEqual(1);
      expect(errors).toEqual([]);
      await page.locator('main.student-main').evaluate(el=>{el.scrollTop=0;});
      await screenshot(page,'v2-'+state+'-'+device+'-encounters-start');
      await page.locator('main.student-main').evaluate(el=>{el.scrollTop=el.scrollHeight;});
      await screenshot(page,'v2-'+state+'-'+device+'-encounters-end');
      if(width<=820){
        const bounds=await page.evaluate(()=>{
          const n=document.querySelector('nav.student-mobile-nav').getBoundingClientRect();
          const m=document.querySelector('main.student-main').getBoundingClientRect();
          return {navTop:n.top,mainBottom:m.bottom,navBottom:n.bottom,viewport:innerHeight};
        });
        expect(bounds.mainBottom).toBeLessThanOrEqual(bounds.navTop+1);
        expect(Math.abs(bounds.navBottom-bounds.viewport)).toBeLessThanOrEqual(2);
      }
    });
  }
  for(const state of states){
    test('Agenda v2 listener offers '+state+' '+device,async({page})=>{
      await page.setViewportSize({width,height});
      await page.goto(base+'?state='+state+'&view=ouvintes',{waitUntil:'networkidle'});
      const agenda=page.locator('[data-student-agenda]');
      await expect(agenda).toHaveAttribute('data-agenda-view','ouvintes');
      await expect(page.getByRole('heading',{name:'Próximas turmas'})).toBeVisible();
      await expect(page.getByRole('heading',{name:'Meus encontros'})).toHaveCount(0);
      await expect(page.locator('[data-agenda-view-link="ouvintes"]')).toHaveAttribute('aria-current','page');
      await expect(page.locator('[data-agenda-upcoming]')).toHaveCount(0);
      if(state==='empty'){
        await expect(page.locator('[data-agenda-offers-empty]')).toBeVisible();
      }else{
        const offer=page.locator('.agenda-offer');
        await expect(offer).toHaveCount(1);
        await expect(offer.locator('.student-academic-release-line')).toHaveCount(3);
        await expect(offer.locator('form')).toHaveAttribute('action','?view=ouvintes');
        await expect(offer.locator('form [name="_csrf"]')).toHaveValue('fixture-csrf');
        await expect(offer.locator('form [name="cohort_id"]')).toHaveValue('3');
        if(state==='waiting'){
          await expect(offer.getByText('Na lista de espera')).toBeVisible();
          await expect(offer.getByRole('button',{name:'Cancelar solicitação'})).toBeVisible();
        }else{
          await expect(offer.getByRole('button',{name:/Solicitar vaga|Entrar na lista de espera/})).toBeVisible();
        }
        if(state==='full'){
          await expect(offer.getByText('Lotada (lista de espera)')).toBeVisible();
          await expect(offer.getByRole('button',{name:'Entrar na lista de espera'})).toBeVisible();
        }
        await offer.locator('.student-agenda-offer-schedule summary').click();
        await expect(offer.locator('.student-v2-agenda-schedule-row').first()).toBeVisible();
      }
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-window.innerWidth);
      expect(overflow).toBeLessThanOrEqual(1);
      await page.locator('main.student-main').evaluate(el=>{el.scrollTop=0;});
      await screenshot(page,'v2-'+state+'-'+device+'-listeners-start');
      await page.locator('main.student-main').evaluate(el=>{el.scrollTop=el.scrollHeight;});
      await screenshot(page,'v2-'+state+'-'+device+'-listeners-end');
    });
  }
}
test('Agenda views are real navigations and Back restores the previous view',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(base+'?view=encontros',{waitUntil:'networkidle'});
  await page.locator('[data-agenda-view-link="ouvintes"]').click();
  await expect(page.locator('[data-student-agenda]')).toHaveAttribute('data-agenda-view','ouvintes');
  await expect(page).toHaveURL(/\?view=ouvintes$/);
  await page.goBack();
  await expect(page.locator('[data-student-agenda]')).toHaveAttribute('data-agenda-view','encontros');
  await page.goForward();
  await expect(page.locator('[data-student-agenda]')).toHaveAttribute('data-agenda-view','ouvintes');
});
test('Agenda link safety, primary booking logic and account toolbox survive the new UI',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(base+'?state=unsafe',{waitUntil:'networkidle'});
  await expect(page.locator('a[href^="javascript:"]')).toHaveCount(0);
  await expect(page.locator('a[target="_blank"]')).toHaveCount(0);
  await page.locator('.student-user-menu summary').click();
  await page.locator('.student-tools-menu-trigger').click();
  await expect(page.locator('[data-student-toolbox]')).toBeVisible();
  await page.locator('[data-toolbox-close]').click();
});
for(const theme of ['light','dark']){
  test('Agenda v2 both views with '+theme+' theme',async({page})=>{
    await page.setViewportSize({width:390,height:844});
    await page.goto(base+'?state=mixed&view=encontros',{waitUntil:'networkidle'});
    await page.evaluate(theme=>document.documentElement.setAttribute('data-theme',theme),theme);
    await screenshot(page,'v2-mixed-'+theme+'-encounters');
    await page.goto(base+'?state=waiting&view=ouvintes',{waitUntil:'networkidle'});
    await page.evaluate(theme=>document.documentElement.setAttribute('data-theme',theme),theme);
    await screenshot(page,'v2-waiting-'+theme+'-listeners');
    await expect(page.locator('.student-v2-agenda-reservation-state')).toContainText('Na lista de espera');
  });
}
