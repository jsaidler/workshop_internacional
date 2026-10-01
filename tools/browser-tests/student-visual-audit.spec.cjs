const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-visual-audit.html';
const screens=['home','course','notebook','new-record','exposure','process','result','tools','inventory','toolbox'];
const viewports={desktop:{width:1440,height:1000},phone:{width:390,height:844}};
for(const [device,viewport] of Object.entries(viewports)){
  for(const screen of screens){
    test(`visual audit ${device} ${screen}`,async({page})=>{
      await page.setViewportSize(viewport);
      await page.goto(`${base}?screen=${encodeURIComponent(screen)}`,{waitUntil:'networkidle'});
      await page.addStyleTag({url:'/assets/student-rendered-fixes.css'});
      await page.evaluate(screenName=>{
        document.documentElement.setAttribute('data-theme','dark');
        const selector=screenName==='new-record'?'.student-create-dialog':screenName==='toolbox'?'.student-toolbox':'';
        if(selector){const dialog=document.querySelector(selector);if(dialog){if(dialog.open)dialog.removeAttribute('open');if(typeof dialog.showModal==='function')dialog.showModal();else dialog.setAttribute('open','');}}
      },screen);
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
      expect(overflow,`${screen} horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
      await page.screenshot({path:`student-visual-audit/${device}/${screen}.png`,fullPage:true,animations:'disabled'});
    });
  }
}

const authBase='http://127.0.0.1:8099/tools/browser-fixture/student-auth-visual-audit.html';
const authScreens=['login','password'];
const authViewports={desktop:{width:1440,height:1000},compact:{width:560,height:900},phone:{width:390,height:844}};
for(const [device,viewport] of Object.entries(authViewports)){
  for(const screen of authScreens){
    test(`auth visual audit ${device} ${screen}`,async({page})=>{
      await page.setViewportSize(viewport);
      await page.goto(`${authBase}?screen=${encodeURIComponent(screen)}`,{waitUntil:'networkidle'});
      await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
      expect(overflow,`${screen} horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
      await page.screenshot({path:`student-visual-audit/${device}/auth-${screen}.png`,fullPage:true,animations:'disabled'});
    });
  }
}
