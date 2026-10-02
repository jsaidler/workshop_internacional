const {test,expect}=require('@playwright/test');
const url='http://127.0.0.1:8099/tools/browser-fixture/student-recording-associated-audit.html';
for(const [device,viewport] of Object.entries({desktop:{width:1440,height:1100},phone:{width:390,height:844}})){
  test(`associated retroactive route stays direct on ${device}`,async({page})=>{
    await page.setViewportSize(viewport);
    await page.goto(url,{waitUntil:'networkidle'});
    await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
    await expect(page.getByText('Roteiro associado',{exact:true})).toBeVisible();
    await expect(page.getByRole('button',{name:'Registrar processo como realizado'})).toBeVisible();
    await expect(page.locator('.student-recording-context')).toHaveCount(0);
    await expect(page.getByLabel('Data do processamento')).toHaveCount(0);
    await expect(page.getByLabel('Observações gerais')).toHaveCount(0);
    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
    expect(overflow).toBeLessThanOrEqual(1);
    await page.screenshot({path:`student-visual-audit/${device}/recording-associated-real.png`,fullPage:true,animations:'disabled'});
  });
}
