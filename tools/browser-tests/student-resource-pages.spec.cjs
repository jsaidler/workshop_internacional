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
    await expect(page.locator('[data-cms-image-placeholder]')).toHaveCount(5);
    await expect(page.getByRole('heading',{name:'04 — Cuidados no laboratório'})).toBeVisible();
    await expect(page.getByText('Termômetro digital culinário')).toBeVisible();
    await expect(page.getByText('Garrafa de cerveja âmbar de aproximadamente 300 ml')).toBeVisible();
   }else{
    await expect(page.getByText(/TAYLOR, J. Traill/)).toBeVisible();
    await expect(page.getByText(/CRAIG, C. C. Craig Film Holder/)).toBeVisible();
   }
   fs.mkdirSync('student-visual-audit/resource-pages',{recursive:true});
   const top=await page.screenshot({path:'student-visual-audit/resource-pages/'+target+'-'+width+'-top.png',animations:'disabled'});
   // Canonical CSS sets html scroll-behavior:smooth; an immediate screenshot
   // after scrollTo() captures the TOP again. Force instant scrolling.
   const scroll=await page.evaluate(()=>{
    const root=document.scrollingElement;
    const full=root.scrollHeight;
    window.scrollTo({top:full,behavior:'instant'});
    return {full,viewport:innerHeight,y:scrollY};
   });
   expect(scroll.full, 'resource document must extend beyond the viewport').toBeGreaterThan(scroll.viewport+100);
   expect(scroll.y, 'bottom screenshot must actually reach the bottom').toBeGreaterThan(0);
   expect(scroll.y+scroll.viewport).toBeGreaterThanOrEqual(scroll.full-2);
   const bottom=await page.screenshot({path:'student-visual-audit/resource-pages/'+target+'-'+width+'-bottom.png',animations:'disabled'});
   expect(Buffer.compare(top,bottom),'top and bottom screenshots must differ').not.toBe(0);
  });
 }
}
