const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-process-replanning-audit.html';
const views=['partial','choice','interrupted','recorded'];
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};
for(const [device,viewport] of Object.entries(viewports)){
  for(const view of views){
    test(`process replanning visual audit ${device} ${view}`,async({page})=>{
      await page.setViewportSize(viewport);await page.goto(`${base}?view=${view}`,{waitUntil:'networkidle'});
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
      expect(overflow,`${view} horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
      if(view==='partial'){
        await expect(page.getByText('3 / 9 etapas')).toBeVisible();
        await expect(page.getByText('Alterar próximas etapas')).toBeVisible();
      }
      if(view==='choice'||view==='interrupted'){
        await expect(page.getByRole('heading',{name:'Alterar próximas etapas'})).toBeVisible();
        await expect(page.getByText('3 etapas registradas',{exact:false})).toBeVisible();
        await expect(page.locator('.student-process-standard-card')).toHaveCount(6);
        await expect(page.getByRole('button',{name:'Usar nas próximas etapas'}).first()).toBeVisible();
        if(view==='interrupted')await expect(page.getByText('preservada como',{exact:false})).toBeVisible();
      }
      if(view==='recorded'){
        await expect(page.getByText('3 de 9 etapas registradas.')).toBeVisible();
        await expect(page.getByText('Trocar próximas etapas')).toBeVisible();
        await expect(page.locator('.student-process-history article')).toHaveCount(3);
      }
      await page.screenshot({path:`student-visual-audit/${device}/process-replanning-${view}.png`,fullPage:true,animations:'disabled'});
    });
  }
}
