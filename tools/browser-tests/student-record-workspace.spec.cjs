const {test,expect}=require('@playwright/test');

const base='http://127.0.0.1:8099/tools/browser-fixture/student-record-workspace.php';
const parts=['exposicao','processamento','resultado'];

for(const width of [320,390,1440]){
 const device=width>=820?'desktop':width===320?'narrow':'phone';
 test('record workspace native history, typed drafts and independent contexts '+device,async({page})=>{
  const errors=[];
  page.on('pageerror',error=>errors.push(error.message));
  await page.setViewportSize({width,height:width===320?720:width===390?844:1100});
  const result=await page.goto(base,{waitUntil:'networkidle'});
  expect(result?.status()).toBe(200);
  const nav=page.locator('[data-record-workspace-nav]');
  await expect(nav.locator('a')).toHaveCount(3);
  await expect(page.locator('.student-mobile-nav a')).toHaveCount(5);
  const panels=Object.fromEntries(parts.map(id=>[id,page.locator('#'+id+'[data-record-workspace-panel]')]));
  await expect(panels.exposicao).toBeVisible();
  await expect(panels.processamento).toBeHidden();
  await expect(panels.resultado).toBeHidden();
  await expect(nav.locator('[aria-current="location"]')).toHaveAttribute('href','#exposicao');
  const title=panels.exposicao.locator('input[name="title"]');
  await title.fill('Ensaio em andamento · anotação sem salvar');
  await expect(page.locator('.student-record-media-disclosure')).not.toHaveAttribute('open','');
  await expect(panels.exposicao.locator('input[name="iso_reference"]')).toBeVisible();
  await nav.locator('a[href="#resultado"]').click();
  await expect(page).toHaveURL(/#resultado$/);
  await expect(panels.resultado).toBeVisible();
  await expect(panels.exposicao).toBeHidden();
  await panels.resultado.locator('textarea[name="notes"]').fill('Resultado anotado e ainda não salvo');
  await nav.locator('a[href="#processamento"]').click();
  await expect(panels.processamento).toBeVisible();
  await expect(panels.resultado).toBeHidden();
  await page.goBack();
  await expect(page).toHaveURL(/#resultado$/);
  await expect(panels.resultado).toBeVisible();
  await expect(panels.resultado.locator('textarea[name="notes"]')).toHaveValue('Resultado anotado e ainda não salvo');
  await page.goBack();
  await expect(panels.exposicao).toBeVisible();
  await expect(title).toHaveValue('Ensaio em andamento · anotação sem salvar');
  await page.goForward();
  await expect(panels.resultado).toBeVisible();
  expect(errors).toEqual([]);
  const layout=await page.evaluate(()=>{
   const main=document.querySelector('.student-main');
   const nav=document.querySelector('[data-record-workspace-nav]');
   const global=document.querySelector('.student-mobile-nav');
   const rMain=main.getBoundingClientRect(),rNav=nav.getBoundingClientRect();
   return{overflow:main.scrollWidth-main.clientWidth,gutter:getComputedStyle(main).scrollbarGutter,navRight:rNav.right,mainRight:rMain.right,globalTop:global.getBoundingClientRect().top,mainBottom:rMain.bottom};
  });
  expect(layout.overflow).toBeLessThanOrEqual(1);
  expect(layout.gutter).toContain('stable');
  expect(layout.navRight).toBeLessThanOrEqual(layout.mainRight+1);
  if(width<820)expect(layout.mainBottom).toBeLessThanOrEqual(layout.globalTop+1);
  await page.screenshot({path:'student-visual-audit/record-workspace/'+device+'-result.png',animations:'disabled'});
  await nav.locator('a[href="#exposicao"]').click();
  await expect(panels.exposicao).toBeVisible();
  if(width<820){
   await expect.poll(async()=>page.evaluate(()=>{
     const tab=document.querySelector('[data-record-workspace-nav]').getBoundingClientRect();
     const kicker=document.querySelector('#exposicao .student-workflow-heading .student-kicker').getBoundingClientRect();
     return kicker.top-tab.bottom;
   })).toBeGreaterThanOrEqual(6);
   const action=await panels.exposicao.locator('.student-record-save-actions').evaluate(node=>getComputedStyle(node).position);
   expect(action,'save must follow document flow without covering exposure fields').toBe('static');
   await panels.exposicao.locator('input[name="calculated_time"]').scrollIntoViewIfNeeded();
   const overlap=await panels.exposicao.evaluate(node=>{
     const a=node.querySelector('.student-record-save-actions').getBoundingClientRect();
     const b=node.querySelector('input[name="calculated_time"]').getBoundingClientRect();
     return Math.min(a.right,b.right)>Math.max(a.left,b.left)&&Math.min(a.bottom,b.bottom)>Math.max(a.top,b.top);
   });
   expect(overlap,'save control cannot invade an input rectangle').toBe(false);
  }
  await page.screenshot({path:'student-visual-audit/record-workspace/'+device+'-exposure.png',animations:'disabled'});
  await nav.locator('a[href="#processamento"]').click();
  await page.screenshot({path:'student-visual-audit/record-workspace/'+device+'-process.png',animations:'disabled'});
 });
}

test('deep link to a nested processing disclosure works on the first load and remains in native history',async({page})=>{
 await page.setViewportSize({width:390,height:844});
 await page.goto(base+'#adicionar-etapa',{waitUntil:'networkidle'});
 const details=page.locator('#adicionar-etapa');
 await expect(page.locator('#processamento')).toBeVisible();
 await expect(details).toHaveAttribute('open','');
 await expect(page.locator('[data-record-workspace-nav] [aria-current="location"]')).toHaveAttribute('href','#processamento');
 await expect(details.locator('input[name="duration"]')).toBeVisible();
 await page.reload({waitUntil:'networkidle'});
 await expect(details).toHaveAttribute('open','');
 await expect(page.locator('#exposicao')).toBeHidden();
 await page.screenshot({path:'student-visual-audit/record-workspace/phone-nested-deeplink.png',animations:'disabled'});
});

test('failed result POST reopens the result context without inventing a URL state',async({page})=>{
 await page.setViewportSize({width:390,height:844});
 await page.goto(base+'?failed=result',{waitUntil:'networkidle'});
 await expect(page.locator('#resultado')).toBeVisible();
 await expect(page.locator('#exposicao')).toBeHidden();
 await expect(page.locator('[data-record-workspace-nav] [aria-current="location"]')).toHaveAttribute('href','#resultado');
 expect(new URL(page.url()).hash).toBe('');
});

test('the record stays readable without enhancement',async({page})=>{
 await page.route('**/assets/student-record-workspace.js',route=>route.abort());
 await page.setViewportSize({width:390,height:844});
 await page.goto(base,{waitUntil:'networkidle'});
 for(const id of parts)await expect(page.locator('#'+id)).toBeVisible();
 await expect(page.locator('a[href="#resultado"]')).toBeVisible();
});
