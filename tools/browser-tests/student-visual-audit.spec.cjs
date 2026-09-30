const {test,expect}=require('@playwright/test');
const root='http://127.0.0.1:8099/tools/browser-fixture';
const screens={
  home:'student-dashboard-truth.html',
  course:'student-course-context.html',
  notebook:'student-tests-context.html',
  process:'student-test-workflow.html',
  form:'student-premium-ui.html',
  material:'student-material-context.html',
  account:'student-account-experience.html',
  login:'student-login-hierarchy.html',
  reciprocity:'student-reciprocity.html',
  study:'study-material.html'
};
const viewports={desktop:{width:1440,height:1000},phone:{width:390,height:844}};
for(const [device,viewport] of Object.entries(viewports)){
  for(const [screen,fixture] of Object.entries(screens)){
    test(`visual audit ${device} ${screen}`,async({page})=>{
      await page.setViewportSize(viewport);
      await page.goto(`${root}/${fixture}`,{waitUntil:'networkidle'});
      await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
      expect(overflow,`${screen} horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
      await page.screenshot({path:`student-visual-audit/${device}/${screen}.png`,fullPage:true,animations:'disabled'});
    });
  }
}
