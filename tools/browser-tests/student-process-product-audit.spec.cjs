const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-process-product-audit.html';
const screens=['library','editor'];
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
      await page.screenshot({path:`student-visual-audit/${device}/process-${screen}.png`,fullPage:true,animations:'disabled'});
    });
  }
}
