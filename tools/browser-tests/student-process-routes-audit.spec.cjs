const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-process-routes-audit.html';
const routes={
  'parodinal-ei200-ferric':{steps:9,developer:'Parodinal 10 ml + água até 550 ml',bleach:'cloreto férrico',clean:'amônia'},
  'parodinal-ei400-ferric':{steps:9,developer:'Parodinal 20 ml + água até 550 ml',bleach:'cloreto férrico',clean:'amônia'},
  'parodinal-ei200-peracetic':{steps:7,developer:'Parodinal 10 ml + água até 550 ml',bleach:'peracética'},
  'parodinal-ei400-peracetic':{steps:7,developer:'Parodinal 20 ml + água até 550 ml',bleach:'peracética'},
  'caffenol-ei400-ferric':{steps:9,developer:'Brewed Caffenol fresco',bleach:'cloreto férrico',clean:'amônia'},
  'caffenol-ei400-peracetic':{steps:7,developer:'Brewed Caffenol fresco',bleach:'peracética'}
};
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};
for(const [device,viewport] of Object.entries(viewports)){
  for(const [route,expected] of Object.entries(routes)){
    test(`workshop route visual audit ${device} ${route}`,async({page})=>{
      await page.setViewportSize(viewport);
      await page.goto(`${base}?route=${route}`,{waitUntil:'networkidle'});
      await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
      await expect(page.locator('.student-process-step-card')).toHaveCount(expected.steps);
      await expect(page.getByText(expected.developer,{exact:false}).first()).toBeVisible();
      await expect(page.getByText(expected.bleach,{exact:false}).first()).toBeVisible();
      if(expected.clean)await expect(page.getByText(expected.clean,{exact:false}).first()).toBeVisible();
      else await expect(page.getByText('Limpeza — amônia',{exact:true})).toHaveCount(0);
      await expect(page.getByText('Mesmo banho da 1ª revelação',{exact:true})).toBeVisible();
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
      expect(overflow,`${route} horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
      await page.screenshot({path:`student-visual-audit/routes/${device}/${route}.png`,fullPage:true,animations:'disabled'});
    });
  }
}