const {test,expect}=require('@playwright/test');
const host='http://127.0.0.1:8099';
const fixture=p=>host+'/tools/browser-fixture/'+p;
const screens=[
  ['login','student-auth-visual-audit.html?screen=login'],
  ['activation-password','student-auth-visual-audit.html?screen=password'],
  ['home','student-visual-audit.html?screen=home'],
  ['course-list','student-secondary-screens-audit.html?screen=course-list'],
  ['course-detail','student-visual-audit.html?screen=course'],
  ['material','student-material-notes.html'],
  ['questions-list','student-secondary-screens-audit.html?screen=questions-list'],
  ['question-new','student-secondary-screens-audit.html?screen=question-new'],
  ['question-thread','student-secondary-screens-audit.html?screen=question-thread'],
  ['notebook','student-caderno-product-audit.html?screen=notebook'],
  ['new-record','student-visual-audit.html?screen=new-record'],
  ['exposure','student-caderno-product-audit.html?screen=exposure'],
  ['process-choice','student-caderno-product-audit.html?screen=process-choice'],
  ['process-plan','student-caderno-product-audit.html?screen=process-plan'],
  ['process-partial','student-caderno-product-audit.html?screen=process-partial'],
  ['process-intent','student-process-intent-audit.html'],
  ['recording-start','student-secondary-screens-audit.html?screen=recording-start'],
  ['recording-associated','student-recording-associated-audit.html'],
  ['recording-partial','student-secondary-screens-audit.html?screen=recording-partial'],
  ['recording-complete','student-secondary-screens-audit.html?screen=recording-complete'],
  ['result','student-caderno-product-audit.html?screen=result'],
  ['step-editor','student-secondary-screens-audit.html?screen=step-editor'],
  ['shared-record','student-secondary-screens-audit.html?screen=shared-record'],
  ['delete-record','student-secondary-screens-audit.html?screen=delete-record'],
  ['compare-records','student-notebook-compare.html'],
  ['process-library','student-process-product-audit.html?screen=library'],
  ['process-editor','student-process-product-audit.html?screen=editor'],
  ['lab-runner','student-process-product-audit.html?screen=runner'],
  ['inventory','student-lab-stock-product-audit.html?screen=inventory'],
  ['inventory-empty','student-lab-stock-product-audit.html?screen=empty'],
  ['inventory-movement','student-lab-stock-product-audit.html?screen=movement'],
  ['inventory-item-editor','student-secondary-screens-audit.html?screen=inventory-item'],
  ['preparations','student-lab-stock-product-audit.html?screen=preparations'],
  ['preparation-editor','student-lab-stock-product-audit.html?screen=editor'],
  ['calibration-list','student-secondary-screens-audit.html?screen=calibration-list'],
  ['calibration-editor','student-secondary-screens-audit.html?screen=calibration-editor'],
  ['tools','student-visual-audit.html?screen=tools'],
  ['toolbox','student-visual-audit.html?screen=toolbox'],
  ['profile','student-secondary-screens-audit.html?screen=profile'],
  ['password-change','student-secondary-screens-audit.html?screen=password-change'],
];
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};
const shellStyles=['/assets/student-workbench.css','/assets/student-experience.css','/assets/student-rendered-fixes.css','/assets/student-auth.css','/assets/student-field-language.css'];
async function ensureCanonicalShellStyles(page){
  await page.evaluate(async styles=>{
    const featurePattern=/\/assets\/student-(?:auth|caderno|processes|lab-stock|process-recording)\.css(?:\?|$)/;
    const head=document.head;
    let anchor=[...head.querySelectorAll('link[rel="stylesheet"]')].find(link=>featurePattern.test(link.getAttribute('href')||''))||null;
    for(const href of styles){
      if([...head.querySelectorAll('link[rel="stylesheet"]')].some(link=>(link.getAttribute('href')||'').split('?')[0]===href))continue;
      await new Promise((resolve,reject)=>{
        const link=document.createElement('link');link.rel='stylesheet';link.href=href;link.onload=resolve;link.onerror=reject;
        head.insertBefore(link,anchor);
      });
    }
  },shellStyles);
}
for(const [device,viewport] of Object.entries(viewports)){
  for(const [name,path] of screens){
    test(`complete student area visual audit ${device} ${name}`,async({page})=>{
      await page.setViewportSize(viewport);
      await page.goto(fixture(path),{waitUntil:'networkidle'});
      if(name!=='material')await ensureCanonicalShellStyles(page);
      await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
      if(name==='new-record'||name==='toolbox'){
        const selector=name==='new-record'?'.student-create-dialog':'.student-toolbox';
        await page.evaluate(sel=>{const d=document.querySelector(sel);if(d){if(d.open)d.removeAttribute('open');if(typeof d.showModal==='function')d.showModal();else d.setAttribute('open','');}},selector);
      }
      if(name==='process-choice'){
        await expect(page.getByText('Registrar um processamento já realizado',{exact:true})).toBeVisible();
        await expect(page.locator('.student-process-path-options article')).toHaveCount(3);
      }
      if(name==='process-plan'){
        await expect(page.getByText('0 / 9 etapas')).toBeVisible();
        await expect(page.getByText('Registrar o que já foi feito',{exact:true})).toBeVisible();
      }
      if(name==='process-partial'){
        await expect(page.getByText('5 / 9 etapas')).toBeVisible();
        await expect(page.getByText('Registrar etapas já realizadas',{exact:true})).toBeVisible();
      }
      if(name==='process-intent'){
        await expect(page.getByText('Nenhuma execução iniciada',{exact:true})).toBeVisible();
        await expect(page.getByText('Registrar o processamento realizado',{exact:true})).toBeVisible();
      }
      if(name==='recording-start'){
        await expect(page.getByText('Brewed Caffenol EI 400 — FeCl₃ + amônia',{exact:false})).toBeVisible();
        await expect(page.getByText('não movimenta o inventário automaticamente',{exact:false})).toBeVisible();
      }
      if(name==='recording-associated'){
        await expect(page.getByText('Roteiro associado',{exact:true})).toBeVisible();
        await expect(page.getByRole('button',{name:'Registrar todo o processamento como realizado'})).toBeVisible();
        await expect(page.getByText('ainda estão apenas planejadas',{exact:false})).toBeVisible();
      }
      if(name==='recording-partial')await expect(page.getByRole('button',{name:'Registrar etapas restantes como realizadas'})).toBeVisible();
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
      expect(overflow,`${name} horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
      await page.screenshot({path:`student-visual-audit/complete/${device}/${name}.png`,fullPage:true,animations:'disabled'});
    });
  }
}

test('complete student visual audit declares all rendered student route families',()=>{
  const names=new Set(screens.map(([name])=>name));
  for(const required of ['login','home','course-list','course-detail','material','questions-list','question-thread','notebook','new-record','exposure','process-choice','process-plan','process-partial','process-intent','recording-start','recording-associated','recording-partial','recording-complete','result','step-editor','shared-record','delete-record','compare-records','process-library','process-editor','lab-runner','inventory','inventory-item-editor','preparations','calibration-list','tools','toolbox','profile','password-change'])expect(names.has(required),`missing visual surface ${required}`).toBe(true);
  expect(screens.length,'full student audit surface count').toBe(40);
});
