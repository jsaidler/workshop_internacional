const {test,expect}=require('@playwright/test');
const fs=require('fs');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-resource-pages.php';
for(const target of ['materials','bibliography']){
 for(const [width,height] of [[320,720],[390,844],[1440,1100]]){
  test('real seeded CMS document visual '+target+' '+width,async({page})=>{
   const errors=[];page.on('pageerror',e=>errors.push(e.message));
   await page.setViewportSize({width,height});
   const response=await page.goto(base+'?page='+target,{waitUntil:'networkidle'});
   expect(response?.status()).toBe(200);
   await expect(page.locator('main section[data-cms-section]')).toHaveCount(target==='materials'?4:1);
   await expect(page.locator('main h1')).toBeVisible();
   await expect(page.locator('main a[href]')).not.toHaveCount(0);
   const size=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
   expect(size).toBeLessThanOrEqual(1);
   expect(errors).toEqual([]);
   if(target==='materials'){
    await expect(page.getByText('Termômetro culinário digital')).toBeVisible();
    await expect(page.getByText('Garrafas de cerveja de vidro âmbar, cerca de 300 ml')).toBeVisible();
   }else{
    await expect(page.getByText(/TAYLOR, J. Traill/)).toBeVisible();
    await expect(page.getByText(/CRAIG, C. C. Craig Film Holder/)).toBeVisible();
   }
   fs.mkdirSync('student-visual-audit/resource-pages',{recursive:true});
   await page.screenshot({path:'student-visual-audit/resource-pages/'+target+'-'+width+'-top.png',animations:'disabled'});
   await page.evaluate(()=>scrollTo(0,document.documentElement.scrollHeight));
   await page.screenshot({path:'student-visual-audit/resource-pages/'+target+'-'+width+'-bottom.png',animations:'disabled'});
  });
 }
}
