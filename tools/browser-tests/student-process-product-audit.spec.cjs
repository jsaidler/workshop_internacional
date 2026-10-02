const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-process-product-audit.html';
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
      }
      if(screen==='editor'){
        await expect(page.getByText('Mesmo banho da 1ª revelação')).toBeVisible();
        await expect(page.getByRole('button',{name:'Salvar etapa'})).toBeVisible();
      }
      if(screen==='runner'){
        await expect(page.getByText('Reutilize o banho da primeira revelação')).toBeVisible();
        await expect(page.getByText('07:00').first()).toBeVisible();
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
}
