const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-process-runner-operational.php';
const sizes=[
  {label:'narrow',width:320,height:720},
  {label:'phone',width:390,height:844},
  {label:'desktop',width:1440,height:1100},
];
const initialSeconds={idle:420,running:300,paused:233,elapsed:0};
async function syntheticServer(page,initial='idle'){
 let state=initial,remaining=initialSeconds[initial],revision=4,events=[];
 await page.route('**/aluno/processar.php?test=7',async route=>{
   const req=route.request();
   if(req.method()!=='POST'){await route.fulfill({status:405,body:'fixture cannot load operational student data'});return;}
   const values=new URLSearchParams(req.postData()||'');const action=values.get('action');events.push(action);
   const now=Date.now();
   if(action==='timer_start'){state='running';remaining=remaining<=0?420:remaining;revision++;}
   else if(action==='timer_pause'){state='paused';remaining=Math.max(1,remaining-1);revision++;}
   else if(action==='timer_reset'){state='idle';remaining=420;revision++;}
   else if(action!=='timer_state'){await route.fulfill({status:422,contentType:'application/json',body:JSON.stringify({error:'not simulated'})});return;}
   const payload={state:{state,remaining_seconds:remaining,revision,plan_id:91,plan_step_id:307,duration_seconds:420,
       server_now:new Date(now).toISOString(),timer_ends_at:state==='running'?new Date(now+remaining*1000).toISOString():null,
       timer_started_at:null,paused_at:null},completed:false};
   await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify(payload)});
 });
 return ()=>events;
}
async function captureAt(page,label,point){
 await page.locator('.student-main').evaluate((main,p)=>{main.scrollTop=p==='start'?0:p==='middle'?(main.scrollHeight-main.clientHeight)/2:main.scrollHeight;},point);
 await page.screenshot({path:'student-visual-audit/process-runner/'+label+'-'+point+'.png',animations:'disabled'});
}
for(const size of sizes){
 test('operational laboratory idle and independent actions '+size.label,async({page})=>{
   const errors=[];page.on('pageerror',error=>errors.push(error.message));
   await page.setViewportSize({width:size.width,height:size.height});
   await syntheticServer(page);
   const res=await page.goto(base+'?state=idle',{waitUntil:'networkidle'});
   expect(res?.status()).toBe(200);
   const runner=page.locator('[data-operational-runner-view]');
   await expect(runner).toHaveCount(1);
   await expect(page.locator('.student-mobile-nav a')).toHaveCount(5);
   await expect(runner.locator('.student-lab-stage-nav a')).toHaveCount(9);
   await expect(runner.locator('.student-lab-stage-nav a[aria-current="step"]')).toHaveAttribute('href','/aluno/processar.php?test=7&step=307');
   await expect(runner.getByText('Reutilizar o banho da primeira revelação')).toBeVisible();
   await expect(runner.locator('[data-runner-state-status]')).toHaveText('Pronto para iniciar');
   await expect(runner.locator('output[data-runner-clock]')).toHaveText('07:00');
   await expect(runner.getByRole('button',{name:'Marcar como concluída'})).toBeVisible();
   await expect(runner.getByRole('link',{name:'Movimentar estoque'})).toHaveAttribute('href','/aluno/inventario.php');
   await expect(runner.locator('form input[name="_csrf"]')).toHaveCount(2);
   await expect(runner.locator('form input[name="action"][value="toggle_complete"]')).toHaveCount(1);
   await expect(runner.locator('form input[name="action"][value="save_step"]')).toHaveCount(1);
   const geometry=await page.evaluate(()=>{const main=document.querySelector('.student-main'),nav=document.querySelector('.student-mobile-nav');return {overflow:main.scrollWidth-main.clientWidth,gutter:getComputedStyle(main).scrollbarGutter,mainBottom:main.getBoundingClientRect().bottom,navTop:nav.getBoundingClientRect().top,navVisible:getComputedStyle(nav).display!=='none'};});
   expect(geometry.overflow,'laboratory must not horizontally overflow').toBeLessThanOrEqual(1);
   expect(geometry.gutter).toContain('stable');
   if(size.width<821&&geometry.navVisible)expect(geometry.mainBottom).toBeLessThanOrEqual(geometry.navTop+1);
   for(const part of ['start','middle','end'])await captureAt(page,size.label+'-idle',part);
   await expect(runner.locator('details.student-lab-timer-settings')).toHaveAttribute('open','');
   await expect(runner.locator('input[name="duration"]')).toHaveValue('07:00');
   await expect(runner.locator('input[name="agitation_duration"]')).toHaveValue('00:10');
   await expect(runner.locator('input[name="agitation_interval"]')).toHaveValue('01:00');
   await captureAt(page,size.label+'-settings','end');
   expect(errors).toEqual([]);
 });
 for(const state of ['running','paused','elapsed']){
  test('operational laboratory timer '+state+' '+size.label,async({page})=>{
   await page.setViewportSize({width:size.width,height:size.height});
   await syntheticServer(page,state);
   const res=await page.goto(base+'?state='+state,{waitUntil:'networkidle'});
   expect(res?.status()).toBe(200);
   const runner=page.locator('[data-operational-runner-view]');
   await expect(runner.locator('[data-runner-state-status]')).toHaveText({running:'Cronômetro em andamento',paused:'Cronômetro pausado',elapsed:'Tempo concluído'}[state]);
   await expect(runner.locator('[data-step-completion]')).toContainText('Etapa não marcada');
   await expect(runner.getByRole('button',{name:'Marcar como concluída'})).toBeVisible();
   await captureAt(page,size.label+'-'+state,'start');
  });
 }
}
test('timer start, pause, resume and reset never confirm a completed step',async({page})=>{
 await page.setViewportSize({width:390,height:844});
 const events=await syntheticServer(page);
 await page.goto(base+'?state=idle',{waitUntil:'networkidle'});
 await page.getByRole('button',{name:'Iniciar',exact:true}).click();
 await expect(page.locator('[data-runner-state-status]')).toHaveText('Cronômetro em andamento');
 await page.getByRole('button',{name:'Pausar',exact:true}).click();
 await expect(page.locator('[data-runner-state-status]')).toHaveText('Cronômetro pausado');
 await page.getByRole('button',{name:'Retomar',exact:true}).click();
 await expect(page.locator('[data-runner-state-status]')).toHaveText('Cronômetro em andamento');
 await page.getByRole('button',{name:'Reiniciar',exact:true}).click();
 await expect(page.locator('[data-runner-state-status]')).toHaveText('Pronto para iniciar');
 await expect(page.locator('[data-runner-clock]')).toHaveText('07:00');
 await expect(page.locator('[data-step-completion]')).toContainText('Etapa não marcada');
 expect(events().filter(e=>e==='toggle_complete')).toHaveLength(0);
 expect(events().filter(e=>e!=='timer_state')).toEqual(['timer_start','timer_pause','timer_start','timer_reset']);
});
test('continuous agitation is a separate timed indication, not a fact',async({page})=>{
 await page.setViewportSize({width:390,height:844});
 await syntheticServer(page,'running');
 await page.goto(base+'?state=running&agitation=continuous',{waitUntil:'networkidle'});
 await expect(page.locator('[data-runner-agitation]')).toHaveText('AGITAÇÃO CONTÍNUA');
 await expect(page.locator('[data-step-completion]')).toContainText('Etapa não marcada');
 await captureAt(page,'phone-continuous','start');
});
test('completed step remains reversible after timer changes',async({page})=>{
 await page.setViewportSize({width:390,height:844});
 await syntheticServer(page,'elapsed');
 await page.goto(base+'?state=elapsed&done=1',{waitUntil:'networkidle'});
 await expect(page.locator('[data-runner-state-status]')).toHaveText('Tempo concluído');
 await expect(page.locator('[data-step-completion]')).toContainText('Etapa marcada como concluída');
 await expect(page.getByRole('button',{name:'Desmarcar etapa'})).toBeVisible();
 await expect(page.locator('form input[name="completed"]')).toHaveValue('0');
 await captureAt(page,'phone-completed','start');
});
