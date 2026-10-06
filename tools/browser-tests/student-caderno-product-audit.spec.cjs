const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-caderno-product-audit.html';
const screens=['notebook','record-empty','record-plan','record-partial','route-picker','record-result'];
const recordScreens=new Set(['record-empty','record-plan','record-partial','record-result']);
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};
for(const [device,viewport] of Object.entries(viewports)){
  for(const screen of screens){
    test(`caderno product visual audit ${device} ${screen}`,async({page})=>{
      await page.setViewportSize(viewport);await page.goto(`${base}?screen=${screen}`,{waitUntil:'networkidle'});await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);expect(overflow,`${screen} horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
      if(device==='phone'){
        const chrome=await page.evaluate(()=>{const nav=document.querySelector('.student-mobile-nav'),top=document.querySelector('.student-topbar'),shell=document.querySelector('.student-shell'),ns=nav?getComputedStyle(nav):null,ts=top?getComputedStyle(top):null,shs=shell?getComputedStyle(shell):null,nr=nav?.getBoundingClientRect();return {navPosition:ns?.position,topPosition:ts?.position,navBottom:nr?.bottom||0,navHeight:nr?.height||0,viewportHeight:innerHeight,shellPaddingBottom:parseFloat(shs?.paddingBottom||'0')};});
        expect(chrome.navPosition).toBe('fixed');expect(Math.abs(chrome.navBottom-chrome.viewportHeight)).toBeLessThanOrEqual(1);expect(chrome.shellPaddingBottom).toBeGreaterThanOrEqual(chrome.navHeight);expect(chrome.topPosition).toBe('static');
      }
      if(screen==='notebook'){
        if(device==='phone')await expect(page.locator('.student-notebook-intro')).toBeHidden();else await expect(page.getByText('qualquer ordem',{exact:false})).toBeVisible();
        await expect(page.getByText('3 registros',{exact:true})).toBeVisible();await expect(page.locator('.student-notebook-card')).toHaveCount(3);await expect(page.locator('.student-record-progress')).toHaveCount(0);await expect(page.getByText('Abrir registro →',{exact:true}).first()).toBeVisible();await expect(page.getByRole('button',{name:'Novo registro'})).toBeVisible();
      }
      if(recordScreens.has(screen)){
        await expect(page.locator('.student-step-nav')).toHaveCount(0);await expect(page.locator('#exposicao')).toBeVisible();await expect(page.locator('#processamento')).toBeVisible();await expect(page.locator('#resultado')).toBeVisible();await expect(page.getByRole('link',{name:'Exposição',exact:true})).toBeVisible();await expect(page.getByRole('link',{name:'Processamento',exact:true})).toBeVisible();await expect(page.getByRole('link',{name:'Resultado',exact:true})).toBeVisible();await expect(page.getByRole('button',{name:'Salvar exposição'})).toBeVisible();await expect(page.getByRole('button',{name:'Salvar resultado'})).toBeVisible();
      }
      if(screen==='record-empty'){
        await expect(page.getByText('Associar roteiro',{exact:true})).toBeVisible();await expect(page.getByText('Adicionar etapa',{exact:true}).first()).toBeVisible();await expect(page.getByText('Movimentar estoque',{exact:true})).toBeVisible();
        for(const obsolete of ['O processamento já aconteceu?','Vou revelar agora','Já revelei','Registrar manualmente'])await expect(page.getByText(obsolete,{exact:false})).toHaveCount(0);
      }
      if(screen==='record-plan'){
        await expect(page.getByText('0 de 9 marcadas',{exact:true})).toBeVisible();await expect(page.locator('.student-notebook-route-step')).toHaveCount(9);await expect(page.getByRole('button',{name:'Marcar ✓'}).first()).toBeVisible();await expect(page.getByText('Abrir timer',{exact:true}).first()).toBeVisible();await expect(page.getByText('Alterar roteiro',{exact:true})).toBeVisible();await expect(page.getByText('Movimentar estoque',{exact:true})).toBeVisible();
        for(const obsolete of ['Próxima etapa','Abrir laboratório','Continuar laboratório','Em andamento'])await expect(page.getByText(obsolete,{exact:false})).toHaveCount(0);
      }
      if(screen==='record-partial'){
        await expect(page.getByText('5 de 9 marcadas',{exact:true})).toBeVisible();await expect(page.getByRole('button',{name:'Desmarcar'})).toHaveCount(5);await expect(page.getByRole('button',{name:'Marcar ✓'})).toHaveCount(4);await expect(page.getByText('Lavagem após branqueamento')).toBeVisible();
      }
      if(screen==='route-picker'){
        await expect(page.getByRole('heading',{name:'Alterar roteiro'})).toBeVisible();
        const neutralCopy=page.getByText('não inicia processamento, não impõe ordem e não movimenta estoque',{exact:false});if(device==='desktop')await expect(neutralCopy).toBeVisible();else await expect(neutralCopy).toHaveCount(1);
        await expect(page.getByText('Associar este roteiro',{exact:true}).first()).toBeVisible();await expect(page.getByText('Usar daqui em diante',{exact:false})).toHaveCount(0);await expect(page.getByText('Cancelar e voltar ao registro',{exact:true})).toBeVisible();
      }
      if(screen==='record-result'){
        await expect(page.getByRole('heading',{name:'O que obtive'})).toBeVisible();await expect(page.getByRole('heading',{name:'Exposição e processamento'})).toBeVisible();
        const independentCopy=page.getByText('não depende de nenhuma etapa anterior',{exact:false});if(device==='desktop')await expect(independentCopy).toBeVisible();else await expect(independentCopy).toHaveCount(1);
        await expect(page.getByRole('textbox',{name:'Anotações sobre o resultado'})).toHaveValue(/tentativa encerrada/);
      }
      await page.screenshot({path:`student-visual-audit/${device}/caderno-${screen}.png`,fullPage:true,animations:'disabled'});
    });
  }
}