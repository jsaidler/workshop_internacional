const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-process-product-audit.html';
const intentBase='http://127.0.0.1:8099/tools/browser-fixture/student-process-intent-audit.html';
const screens=['library','editor','runner'];
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};
for(const [device,viewport] of Object.entries(viewports)){
  for(const screen of screens){
    test(`process product visual audit ${device} ${screen}`,async({page})=>{
      await page.setViewportSize(viewport);
      await page.goto(`${base}?screen=${screen}`,{waitUntil:'networkidle'});
      await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
      expect(overflow,`${screen} horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
      if(screen==='library'){
        await expect(page.getByText('Meus processamentos')).toBeVisible();
        await expect(page.getByRole('button',{name:'Excluir'}).first()).toBeVisible();
        await expect(page.locator('.student-process-standard-card')).toHaveCount(6);
        await expect(page.locator('.student-process-standard-card[data-developer="Brewed Caffenol"]')).toHaveCount(2);
        await expect(page.getByText('Positivo direto — Brewed Caffenol EI 400 — FeCl₃ + amônia',{exact:true})).toBeVisible();
      }
      if(screen==='editor'){
        await expect(page.getByText('Mesmo banho da 1ª revelação')).toBeVisible();
        await expect(page.getByRole('button',{name:'Salvar etapa'})).toBeVisible();
        await expect(page.locator('.student-process-step-card')).toHaveCount(9);
        await expect(page.locator('.student-process-step-card[data-stage="03"]')).toContainText('cloreto férrico');
        await expect(page.locator('.student-process-step-card[data-stage="05"]')).toContainText('amônia');
      }
      if(screen==='runner'){
        await expect(page.getByText('Reutilize o banho da primeira revelação')).toBeVisible();
        await expect(page.getByText('07:00').first()).toBeVisible();
        await expect(page.locator('.student-process-runner-timeline li')).toHaveCount(9);
        await expect(page.locator('.student-topbar')).toBeHidden();
        await expect(page.locator('.student-mobile-nav')).toBeHidden();
        const start=page.getByRole('button',{name:'Iniciar',exact:true});
        await expect(start).toBeVisible();
        const receivesPointer=await start.evaluate(el=>{
          const box=el.getBoundingClientRect();
          const hit=document.elementFromPoint(box.left+box.width/2,box.top+box.height/2);
          return hit===el||el.contains(hit);
        });
        expect(receivesPointer,'runner primary control must not be covered by navigation chrome').toBe(true);
      }
      await page.screenshot({path:`student-visual-audit/${device}/process-${screen}.png`,fullPage:true,animations:'disabled'});
    });
  }
  test(`process intent visual audit ${device}`,async({page})=>{
    await page.setViewportSize(viewport);
    await page.goto(intentBase,{waitUntil:'networkidle'});
    await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
    await expect(page.getByText('Nenhuma execução iniciada',{exact:true})).toBeVisible();
    await expect(page.getByRole('heading',{name:'Usar o modo laboratório'})).toBeVisible();
    await expect(page.getByRole('heading',{name:'Registrar o processamento realizado'})).toBeVisible();
    await expect(page.getByText('Trocar roteiro',{exact:true})).toBeVisible();
    await expect(page.locator('.student-topbar')).toBeVisible();
    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
    expect(overflow,`intent horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
    await page.screenshot({path:`student-visual-audit/${device}/process-intent.png`,fullPage:true,animations:'disabled'});
  });
}
