const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-visual-audit.html';
const screens=['home','course','notebook','notebook-actions','new-record','process','exposure','result','tools','inventory','toolbox'];
const viewports={desktop:{width:1440,height:1000},phone:{width:390,height:844}};
for(const [device,viewport] of Object.entries(viewports)){
  for(const screen of screens){
    test(`visual audit ${device} ${screen}`,async({page})=>{
      await page.setViewportSize(viewport);
      await page.goto(`${base}?screen=${encodeURIComponent(screen)}`,{waitUntil:'networkidle'});
      await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
      expect(overflow,`${screen} horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
      await page.screenshot({path:`student-visual-audit/${device}/${screen}.png`,fullPage:true,animations:'disabled'});
    });
  }
}
