const {test,expect}=require('@playwright/test');
const host='http://127.0.0.1:8099';
const fixture=screen=>`${host}/tools/browser-fixture/student-tools-current-audit.html?screen=${screen}`;
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};

for(const [device,viewport] of Object.entries(viewports)){
  for(const screen of ['tools','toolbox']){
    test(`current student tools audit ${device} ${screen}`,async({page})=>{
      await page.setViewportSize(viewport);
      await page.goto(fixture(screen),{waitUntil:'networkidle'});
      await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
      if(screen==='tools'){
        await expect(page.getByRole('heading',{name:'Reciprocidade'})).toBeVisible();
        await expect(page.getByRole('heading',{name:'Exposição equivalente'})).toBeVisible();
        await expect(page.getByLabel('EI desejado')).toBeVisible();
        await expect(page.getByLabel('Compensação (EV)')).toBeVisible();
        await expect(page.getByRole('heading',{name:'Processamentos'})).toBeVisible();
        await expect(page.getByRole('heading',{name:'Receitas'})).toBeVisible();
        await expect(page.getByRole('heading',{name:'Organização do laboratório'})).toBeVisible();
        await expect(page.getByRole('heading',{name:'Temporizador'})).toHaveCount(0);
      }else{
        const dialog=page.locator('.student-toolbox');
        await expect(dialog).toBeVisible();
        await expect(dialog.getByLabel('EI desejado')).toBeVisible();
        await expect(dialog.getByLabel('Compensação (EV)')).toBeVisible();
        await expect(dialog.getByText('Receitas e preparo',{exact:true})).toBeVisible();
        await expect(dialog.getByText('Ver bancada completa',{exact:true})).toBeVisible();
      }
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
      expect(overflow,`${screen} horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
      await page.screenshot({path:`student-visual-audit/aula3/${device}/${screen}.png`,fullPage:true,animations:'disabled'});
    });
  }
}
