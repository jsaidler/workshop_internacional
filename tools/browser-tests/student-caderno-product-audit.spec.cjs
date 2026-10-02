const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-caderno-product-audit.html';
const screens=['notebook','exposure','process-choice','process-plan','process-partial','result'];
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};
for(const [device,viewport] of Object.entries(viewports)){
  for(const screen of screens){
    test(`caderno product visual audit ${device} ${screen}`,async({page})=>{
      await page.setViewportSize(viewport);
      await page.goto(`${base}?screen=${screen}`,{waitUntil:'networkidle'});
      await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
      expect(overflow,`${screen} horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
      if(device==='phone'){
        const chrome=await page.evaluate(()=>{
          const nav=document.querySelector('.student-mobile-nav');
          const top=document.querySelector('.student-topbar');
          const step=document.querySelector('.student-step-nav');
          const main=document.querySelector('.student-main');
          const ns=nav?getComputedStyle(nav):null;
          const ts=top?getComputedStyle(top):null;
          const ss=step?getComputedStyle(step):null;
          const nr=nav?.getBoundingClientRect();
          const mr=main?.getBoundingClientRect();
          return {navPosition:ns?.position,navBackdrop:ns?.backdropFilter||ns?.webkitBackdropFilter||'none',topPosition:ts?.position,stepPosition:ss?.position||null,navTop:nr?.top||0,mainBottom:mr?.bottom||0};
        });
        expect(chrome.navPosition,'mobile nav must participate in normal document flow').toBe('static');
        expect(['none',''].includes(chrome.navBackdrop),'mobile nav must not blur content underneath').toBe(true);
        expect(chrome.topPosition,'mobile topbar must not occupy viewport while scrolling').toBe('static');
        if(screen!=='notebook')expect(chrome.stepPosition,'record step navigation must not stick over content').toBe('static');
        expect(chrome.navTop,'mobile nav must begin after main content').toBeGreaterThanOrEqual(chrome.mainBottom-1);
      }
      if(screen==='notebook'){
        if(device==='phone')await expect(page.locator('.student-notebook-intro')).toBeHidden();
        else await expect(page.getByText('como você expôs',{exact:false}).first()).toBeVisible();
        await expect(page.getByText('3 registros',{exact:true})).toBeVisible();
        await expect(page.locator('.student-notebook-card')).toHaveCount(3);
        await expect(page.locator('.student-record-progress').first()).toBeVisible();
        await expect(page.getByText('Definir como registrar →',{exact:true})).toBeVisible();
        await expect(page.getByRole('button',{name:'Novo registro'})).toBeVisible();
      }
      if(screen==='exposure'){
        if(device==='phone')await expect(page.locator('.student-workflow-heading')).toBeHidden();
        else await expect(page.getByRole('heading',{name:'Como expus'})).toBeVisible();
        await expect(page.getByRole('button',{name:'Salvar exposição e continuar →'})).toBeVisible();
      }
      if(screen==='process-choice'){
        await expect(page.getByRole('heading',{name:'O processamento já aconteceu?'})).toBeVisible();
        await expect(page.getByRole('heading',{name:'Vou revelar agora'})).toBeVisible();
        await expect(page.getByRole('heading',{name:'Já revelei'})).toBeVisible();
        await expect(page.getByText('Registrar etapa por etapa, sem roteiro',{exact:true})).toBeVisible();
        await expect(page.locator('.student-process-path-options article')).toHaveCount(2);
      }
      if(screen==='process-plan'){
        await expect(page.getByText('0 / 9 etapas')).toBeVisible();
        await expect(page.getByText('Abrir laboratório',{exact:true})).toBeVisible();
        await expect(page.getByText('Registrar',{exact:true})).toBeVisible();
        await expect(page.getByText('Trocar roteiro',{exact:true})).toBeVisible();
      }
      if(screen==='process-partial'){
        await expect(page.getByText('5 / 9 etapas')).toBeVisible();
        await expect(page.getByText('Lavagem após branqueamento')).toBeVisible();
        await expect(page.getByText('Continuar laboratório',{exact:true})).toBeVisible();
        await expect(page.getByText('Completar registro',{exact:true})).toBeVisible();
      }
      if(screen==='result'){
        if(device==='phone')await expect(page.locator('.student-workflow-heading')).toBeHidden();
        else await expect(page.getByRole('heading',{name:'O que obtive'})).toBeVisible();
        await expect(page.getByRole('heading',{name:'Exposição e processamento, lado a lado'})).toBeVisible();
        await expect(page.getByText('Anote o que observou no positivo',{exact:false})).toBeVisible();
      }
      await page.screenshot({path:`student-visual-audit/${device}/caderno-${screen}.png`,fullPage:true,animations:'disabled'});
    });
  }
}