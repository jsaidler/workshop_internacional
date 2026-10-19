const {test,expect}=require('@playwright/test');
const fs=require('fs');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-home.php';
for(const [state,width,height] of [
 ['active',320,720],['active',390,844],['active',1440,1100],
 ['multiple',390,844],['feedback',390,844],['study',390,844],
 ['empty',390,844],['restricted',390,844],['long',320,720]
]){
 test('live authenticated Home PHP renderer '+state+' '+width,async({page})=>{
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  const res=await page.goto(base+'?state='+state,{waitUntil:'domcontentloaded'});
  expect(res?.status()).toBe(200);await expect(page.locator('[data-home-live]')).toBeVisible();
  await expect(page.locator('nav.student-mobile-nav a')).toHaveCount(5);
  await expect(page.getByRole('heading',{name:'Meus cursos'})).toBeVisible();
  await expect(page.locator('.student-v2-next')).toHaveCount(0);
  expect(await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
  expect(errors).toEqual([]);
  if(width<=760){
   const boxes=await page.evaluate(()=>{const main=document.querySelector('main.student-main').getBoundingClientRect(),nav=document.querySelector('nav.student-mobile-nav').getBoundingClientRect();return [main.bottom,nav.top]});
   expect(boxes[0]).toBeLessThanOrEqual(boxes[1]+1);
  }
  fs.mkdirSync('student-visual-audit/home-live',{recursive:true});
  await page.screenshot({path:'student-visual-audit/home-live/'+state+'-'+width+'-start.png',animations:'disabled'});
  const main=page.locator('main.student-main');
  await main.evaluate(el=>el.scrollTop=Math.round((el.scrollHeight-el.clientHeight)/2));
  await page.screenshot({path:'student-visual-audit/home-live/'+state+'-'+width+'-middle.png',animations:'disabled'});
  await main.evaluate(el=>el.scrollTop=el.scrollHeight);
  await page.screenshot({path:'student-visual-audit/home-live/'+state+'-'+width+'-end.png',animations:'disabled'});
 });
}
test('Home always contains authorized course Material/Dúvidas context without duplicating Notebook',async({page})=>{
 await page.goto(base+'?state=multiple');
 const courses=page.locator('.student-home-course');await expect(courses).toHaveCount(2);
 await expect(courses.nth(0).getByRole('link',{name:'Material'})).toHaveAttribute('href',/cohort=cohort-a/);
 await expect(courses.nth(1).getByRole('link',{name:'Material'})).toHaveAttribute('href',/cohort=cohort-b/);
 await expect(courses.nth(0).getByRole('link',{name:'Dúvidas'})).toHaveAttribute('href',/cohort=cohort-a/);
 await expect(courses.nth(1).getByRole('link',{name:'Dúvidas'})).toHaveAttribute('href',/cohort=cohort-b/);
 await expect(page.locator('[data-record-create-open]')).toHaveCount(0);
 await expect(page.locator('[data-record-create-dialog]')).toHaveCount(0);
});
test('Home exposes notice link and consistent unread number without fake conversation text',async({page})=>{
 await page.goto(base+'?state=active');
 await expect(page.locator('.student-home-bell-count')).toHaveText('1');
 await expect(page.locator('main.student-main h1')).toHaveClass(/student-home-live-accessible-title/);
 await expect(page.locator('.student-home-live-header')).toHaveCount(0);
 expect(await page.locator('[data-home-meeting]').evaluate(n=>getComputedStyle(n).borderLeftWidth)).toBe('0px');
 await expect(page.getByRole('link',{name:/Segunda revelação/})).toHaveAttribute('href',/cohort=cohort-a&id=42/);
 await expect(page.locator('[data-home-meeting]')).toBeVisible();
 await expect(page.locator('[data-home-updates]')).toBeVisible();
});
test('Home does not invent urgency when there are no upcoming events',async({page})=>{
 await page.goto(base+'?state=study');
 await expect(page.locator('[data-home-meeting]')).toHaveCount(0);
 await expect(page.getByRole('link',{name:/Formação com página pública publicada/})).toHaveAttribute('href','/curso-publico');
 await page.goto(base+'?state=empty');
 await expect(page.locator('.student-home-bell-count')).toHaveCount(0);
 await expect(page.getByText('Não há matrículas ativas.')).toBeVisible();
});
for(const theme of ['light','dark']){
 test('Live Home inherits CMS typography and theme '+theme,async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(base+'?state=active',{waitUntil:'domcontentloaded'});
  await page.evaluate(theme=>document.documentElement.setAttribute('data-theme',theme),theme);
  const style=await page.evaluate(()=>{
   const h=document.querySelector('.student-home-live-section-head h2');
   const body=document.querySelector('.student-home-course-cohort');
   const c=getComputedStyle(document.documentElement);
   return {title:getComputedStyle(h).fontFamily,body:getComputedStyle(body).fontFamily,focus:c.getPropertyValue('--focus'),bg:c.getPropertyValue('--bg')};
  });
  expect(style.title).toContain('Saira Extra Condensed');
  expect(style.body).toContain('IBM Plex Sans');
  expect(style.bg.trim()).toBe(theme==='dark'?'#0c0d0e':'#f2f2ef');
  fs.mkdirSync('student-visual-audit/home-live',{recursive:true});
  await page.screenshot({path:'student-visual-audit/home-live/theme-'+theme+'.png',animations:'disabled'});
 });
}

test('Notifications render through the same operational PHP projection and do not auto-mark read',async({page})=>{
 await page.setViewportSize({width:390,height:844});
 await page.goto(base+'?state=active&screen=notifications',{waitUntil:'domcontentloaded'});
 await expect(page.getByRole('heading',{name:'Notificações'})).toBeVisible();
 await expect(page.getByRole('link',{name:/Segunda revelação/})).toHaveAttribute('href',/cohort=cohort-a&id=42/);
 await expect(page.locator('.student-home-notice.is-unread')).toHaveCount(1);
 await expect(page.locator('nav.student-mobile-nav a')).toHaveCount(5);
 fs.mkdirSync('student-visual-audit/home-live',{recursive:true});
 await page.screenshot({path:'student-visual-audit/home-live/notifications-390.png',animations:'disabled'});
 await page.setViewportSize({width:320,height:720});
 expect(await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
 await page.screenshot({path:'student-visual-audit/home-live/notifications-320.png',animations:'disabled'});
});
