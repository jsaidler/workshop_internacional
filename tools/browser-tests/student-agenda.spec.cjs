const {test,expect}=require('@playwright/test');
const fs=require('fs');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-agenda.php';
const states=['empty','regular','listener','waiting','full','mixed','history','unsafe'];
for(const [device,width,height] of [['phone',390,844],['narrow',320,720],['desktop',1440,1100]]){
  for(const state of states){
    test('Agenda operational renderer '+state+' '+device,async({page})=>{
      await page.setViewportSize({width,height});
      const response=await page.goto(base+'?state='+state,{waitUntil:'networkidle'});
      expect(response?.status()).toBe(200);
      await expect(page.getByRole('heading',{name:'Agenda',exact:true})).toBeVisible();
      await expect(page.getByRole('heading',{name:'Meus encontros'})).toBeVisible();
      await expect(page.getByRole('heading',{name:'Próximas turmas'})).toBeVisible();
      await expect(page.getByRole('link',{name:'Voltar aos cursos'})).toHaveCount(0);
      const nav=page.locator('nav.student-mobile-nav');
      await expect(nav.locator('a[href="/aluno/cursos.php"]')).toHaveAttribute('aria-current','page');
      await expect(nav.locator('a')).toHaveCount(4);
      if(['regular','listener','mixed'].includes(state)){
        await expect(page.locator('[data-agenda-upcoming] .agenda-meeting')).toHaveCount(1);
        await expect(page.getByRole('link',{name:'Acessar encontro ↗'})).toHaveAttribute('href','https://example.org/private-meeting');
        await expect(page.locator('[data-agenda-upcoming] .agenda-meeting .student-status')).toHaveText(state==='listener'?'Ouvinte':'Matriculado');
      }else{
        await expect(page.getByRole('link',{name:'Acessar encontro ↗'})).toHaveCount(0);
        await expect(page.locator('body')).not.toContainText('https://example.org/private-meeting');
      }
      if(state!=='empty')await expect(page.locator('.agenda-offer .student-academic-release-line')).toHaveCount(3);
      if(state==='mixed'){
        await expect(page.locator('[data-agenda-history]')).toHaveCount(1);
        await expect(page.locator('[data-agenda-history]')).not.toHaveAttribute('open','');
        await page.locator('[data-agenda-history] summary').click();
        await expect(page.locator('[data-agenda-history] .agenda-meeting')).toBeVisible();
        await expect(page.locator('[data-agenda-history] a[href*="previous-meeting"]')).toHaveCount(0);
      }
      if(state==='history'){
        await expect(page.getByText('Nenhum próximo encontro agendado')).toBeVisible();
        await expect(page.locator('[data-agenda-history] .agenda-meeting')).toHaveCount(1);
      }
      if(state==='unsafe')await expect(page.getByText('Link de acesso ainda não informado.')).toBeVisible();
      if(state==='regular'){
        await expect(page.locator('[data-student-toolbox]')).toBeHidden();
        await page.locator('[data-toolbox-open]').click();
        await expect(page.locator('[data-student-toolbox]')).toBeVisible();
        await expect(page.locator('[data-student-toolbox] .student-toolbox-links a').first()).toHaveAttribute('href','/aluno/agenda.php');
        await page.locator('[data-toolbox-close]').click();
        await page.locator('.student-agenda-offer-schedule summary').click();
        await expect(page.locator('.student-agenda-offer-schedule .student-academic-release-line').first()).toBeVisible();
      }
      if(state==='waiting')await expect(page.getByText('Na lista de espera')).toBeVisible();
      if(state==='full')await expect(page.getByText('Lotada (lista de espera)')).toBeVisible();
      if(state==='empty')await expect(page.getByText('Ainda não há encontros com data definida')).toBeVisible();
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-window.innerWidth);
      expect(overflow).toBeLessThanOrEqual(1);
      fs.mkdirSync('student-visual-audit/agenda',{recursive:true});
      await page.screenshot({path:'student-visual-audit/agenda/'+state+'-'+device+'-start.png',animations:'disabled'});
      await page.locator('main.student-main').evaluate(el=>{el.scrollTop=el.scrollHeight;});
      await page.screenshot({path:'student-visual-audit/agenda/'+state+'-'+device+'-end.png',animations:'disabled'});
      if(width<=760){
        const regions=await page.evaluate(()=>{
          const nav=document.querySelector('nav.student-mobile-nav'),main=document.querySelector('main.student-main');
          const n=nav.getBoundingClientRect(),m=main.getBoundingClientRect();
          return {navTop:n.top,mainBottom:m.bottom,viewport:window.innerHeight};
        });
        expect(regions.mainBottom).toBeLessThanOrEqual(regions.navTop+1);
        expect(regions.navTop).toBeLessThan(regions.viewport);
      }
    });
  }
}
