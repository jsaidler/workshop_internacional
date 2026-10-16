const {test,expect}=require('@playwright/test');
const fixture='http://127.0.0.1:8099/tools/browser-fixture/student-auth-operational.php';
for(const [width,height,label] of [[320,720,'narrow'],[390,844,'phone'],[1440,900,'desktop']]){
 for(const [mode,privileged] of [['login',false],['activation',false],['change',true]]){
  test('operational authentication '+label+' '+mode,async({page})=>{
   const errors=[];page.on('pageerror',error=>errors.push(error.message));
   await page.setViewportSize({width,height});
   const response=await page.goto(fixture+'?mode='+mode,{waitUntil:'networkidle'});
   expect(response?.status()).toBe(200);
   await expect(page.locator('body.student-app .student-shell')).toHaveCount(1);
   await expect(page.locator('link[href^="/assets/student-area.css"]')).toHaveCount(1);
   await expect(page.locator(mode==='login'?'.student-auth-grid form [name="_csrf"]':'.student-login-card form [name="_csrf"]')).toHaveCount(mode==='login'?2:1);
   await expect(page.locator('.student-mobile-nav a')).toHaveCount(privileged?5:0);
   if(mode==='login')await expect(page.locator('form [name="mode"][value="activate"]')).toHaveCount(1);
   if(mode==='activation')await expect(page.locator('[name="privacy_ack"][required]')).toHaveCount(1);
   if(mode==='change'){await expect(page.locator('[name="primeiro"][value="0"]')).toHaveCount(1);await expect(page.getByRole('link',{name:/Conta/})).toHaveAttribute('href','/aluno/perfil.php');}
   const scroll=await page.evaluate(()=>{
    const main=document.querySelector('.student-main'),nav=document.querySelector('.student-mobile-nav');
    return{documentOverflow:document.documentElement.scrollWidth-innerWidth,mainOverflow:main.scrollWidth-main.clientWidth,gutter:getComputedStyle(main).scrollbarGutter,mainBottom:main.getBoundingClientRect().bottom,navTop:nav?.getBoundingClientRect().top??0};
   });
   expect(scroll.documentOverflow).toBeLessThanOrEqual(1);
   expect(scroll.mainOverflow).toBeLessThanOrEqual(1);
   expect(scroll.gutter).toContain('stable');
   if(privileged&&width<821)expect(scroll.mainBottom).toBeLessThanOrEqual(scroll.navTop+1);
   await page.screenshot({path:'student-visual-audit/auth-operational/'+label+'-'+mode+'.png',animations:'disabled'});
   expect(errors).toEqual([]);
  });
 }
}
for(const mode of ['login-error','activation-error','password-error']){
 test('operational authentication contextual error '+mode,async({page})=>{
  await page.setViewportSize({width:390,height:844});
  const response=await page.goto(fixture+'?mode='+mode);
  expect(response?.status()).toBe(200);
  await expect(page.getByRole('alert')).toHaveCount(1);
  await page.screenshot({path:'student-visual-audit/auth-operational/phone-'+mode+'.png',animations:'disabled'});
 });
}
