const {test,expect}=require('@playwright/test');

const host='http://127.0.0.1:8099';
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};
// These assertions prevent another low-resolution regression. They do not replace
// human inspection of the full-page desktop and phone renders uploaded by this test.

for(const [device,viewport] of Object.entries(viewports)){
  test(`Aula 2/Aula 3 final material visual audit ${device}`,async({page})=>{
    await page.setViewportSize(viewport);
    await page.goto(host+'/tools/browser-fixture/student-aula3-material-audit.php',{waitUntil:'networkidle'});
    await expect(page.getByTestId('aula3-material-audit')).toBeVisible();
    await expect(page.locator('[data-aula3-screenshot]')).toHaveCount(9);
    await expect(page.locator('[data-aula3-screenshot] .cms-media-placeholder')).toHaveCount(0);
    const images=page.locator('[data-aula3-screenshot] img');
    await expect(images).toHaveCount(9);
    const metrics=await images.evaluateAll(nodes=>nodes.map(img=>({naturalWidth:img.naturalWidth,naturalHeight:img.naturalHeight,width:img.getBoundingClientRect().width,height:img.getBoundingClientRect().height})));
    for(const [index,metric] of metrics.entries()){
      expect(metric.naturalWidth,`screenshot ${index+1} source is too narrow`).toBeGreaterThanOrEqual(1000);
      expect(metric.naturalHeight,`screenshot ${index+1} source is unexpectedly shallow`).toBeGreaterThanOrEqual(300);
      expect(metric.width,`screenshot ${index+1} is rendered too small on ${device}`).toBeGreaterThanOrEqual(device==='desktop'?700:300);
    }
    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
    expect(overflow,`Aula 2/Aula 3 horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
    await page.screenshot({path:`student-visual-audit/material/${device}/aula2-aula3-final.png`,fullPage:true,animations:'disabled'});
  });
}
