const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-caderno-product-audit.html';
const screens=['notebook','record-empty','record-plan','record-partial','route-picker','record-result'];
const recordScreens=new Set(['record-empty','record-plan','record-partial','record-result']);
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
          const shell=document.querySelector('.student-shell');
          const ns=nav?getComputedStyle(nav):null;
          const ts=top?getComputedStyle(top):null;
          const shs=shell?getComputedStyle(shell):null;
          const nr=nav?.getBoundingClientRect();
          return {navPosition:ns?.position,topPosition:ts?.position,navBottom:nr?.bottom||0,navHeight:nr?.height||0,viewportHeight:innerHeight,shellPaddingBottom:parseFloat(shs?.paddingBottom||'0')};
        });
        expect(chrome.navPosition,'mobile nav must stay anchored to the viewport').toBe('fixed');
        expect(Math.abs(chrome.navBottom-chrome.viewportHeight),'mobile nav must touch the bottom viewport edge').toBeLessThanOrEqual(1);
        expect(chrome.shellPaddingBottom,'content must reserve the fixed navigation footprint').toBeGreaterThanOrEqual(chrome.navHeight);
        expect(chrome.topPosition,'mobile topbar must not occupy viewport while scrolling').toBe('static');
      }
      if(screen==='notebook'){
        if(device==='phone')await expect(page.locator('.student-notebook-intro')).toBeHidden();
        else await expect(page.getByText('preenchidas em qualquer ordem',{exact:false})).toBeVisible();
        await expect(page.getByText('3 registros',{exact:true})).toBeVisible();
        await expect(page.locator('.student-notebook-card')).toHaveCount(3);
        await expect(page.locator('.student-record-progress')).toHaveCount(0);
        await expect(page.getByText('Abrir registro →',{exact:true}).first()).toBeVisible();
        await expect(page.getByRole('button',{name:'Novo registro'})).toBeVisible();
      }
      if(recordScreens.has(screen)){
        await expect(page.locator('.student-step-nav')).toHaveCount(0);
        await expect(page.locator('#exposicao')).toBeVisible();
        await expect(page.locator('#processamento')).toBeVisible();
        await expect(page.locator('#resultado')).toBeVisible();
        await expect(page.getByRole('link',{name:'Exposição',exact:true})).toBeVisible();
        await expect(page.getByRole('link',{name:'Processamento',exact:true})).toBeVisible();
        await expect(page.getByRole('link',{name:'Resultado',exact:true})).toBeVisible();
        await expect(page.getByRole('button',{name:'Salvar exposição'})).toBeVisible();
        await expect(page.getByRole('button',{name:'Salvar resultado'})).toBeVisible();
      }
      if(screen==='record-empty'){
        await expect(page.getByRole('heading',{name:'Como quer registrar o processamento?'})).toBeVisible();
        await expect(page.getByText('Usar um roteiro',{exact:true})).toBeVisible();
        await expect(page.getByText('Registrar manualmente',{exact:true})).toBeVisible();
        await expect(page.getByText('O processamento já aconteceu?')).toHaveCount(0);
      }
      if(screen==='record-plan'){
        await expect(page.getByText('0 / 9 etapas')).toBeVisible();
        await expect(page.getByText('Abrir laboratório',{exact:true})).toBeVisible();
        await expect(page.getByText('Alterar roteiro',{exact:true})).toBeVisible();
        await expect(page.getByText('Registrar manualmente',{exact:true})).toBeVisible();
        await expect(page.getByText('Agora',{exact:true})).toHaveCount(0);
        await expect(page.getByText('Já realizado',{exact:true})).toHaveCount(0);
      }
      if(screen==='record-partial'){
        await expect(page.getByText('5 / 9 etapas')).toBeVisible();
        await expect(page.getByText('Lavagem após branqueamento')).toBeVisible();
        await expect(page.getByText('Continuar laboratório',{exact:true})).toBeVisible();
        await expect(page.getByText('Alterar roteiro',{exact:true})).toBeVisible();
        await expect(page.getByText('Etapas registradas · 5 etapas',{exact:true})).toBeVisible();
      }
      if(screen==='route-picker'){
        await expect(page.getByRole('heading',{name:'Alterar roteiro'})).toBeVisible();
        await expect(page.getByText('permanece no histórico',{exact:false})).toBeVisible();
        await expect(page.getByText('Usar daqui em diante',{exact:true}).first()).toBeVisible();
        await expect(page.getByText('Cancelar e voltar ao registro',{exact:true})).toBeVisible();
      }
      if(screen==='record-result'){
        await expect(page.getByRole('heading',{name:'O que obtive'})).toBeVisible();
        await expect(page.getByRole('heading',{name:'Exposição e processamento'})).toBeVisible();
        await expect(page.getByText('Anote o que observou no positivo',{exact:false})).toBeVisible();
        await expect(page.locator('textarea')).toContainText('Sombras agrupadas');
      }
      await page.screenshot({path:`student-visual-audit/${device}/caderno-${screen}.png`,fullPage:true,animations:'disabled'});
    });
  }
}
