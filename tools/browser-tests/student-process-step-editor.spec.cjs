const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-process-step-editor.php';

for(const width of [320,390,1440]){
 const device=width===1440?'desktop':width===320?'narrow':'phone';
 test('operational process editor task-first '+device,async({page})=>{
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.setViewportSize({width,height:width===320?720:width===390?844:1100});
  const response=await page.goto(base+'?mode=create',{waitUntil:'networkidle'});
  expect(response?.status()).toBe(200);
  const form=page.locator('[data-operational-process-editor] form[data-process-template-step-form]');
  await expect(form).toHaveCount(1);
  await expect(form.locator('select[name="stage_key"]')).toBeVisible();
  await expect(form.locator('select[name="developer_key"]')).toBeVisible();
  await expect(form.locator('input[name="duration"]')).toBeVisible();
  await expect(form.locator('input[name="temperature"]')).toBeVisible();
  await expect(form.locator('input[name="developer_name"]')).toBeHidden();
  const composition=form.locator('details').filter({hasText:'Composição da solução'}),extras=form.locator('details').filter({hasText:'Agitação e observações'});
  await expect(composition).not.toHaveAttribute('open','');
  await expect(extras).not.toHaveAttribute('open','');
  const order=await form.evaluate(form=>{
   const top=s=>form.querySelector(s).getBoundingClientRect().top;
   return {stage:top('[name="stage_key"]'),duration:top('[name="duration"]'),advanced:top('details.student-process-task-disclosure:last-of-type'),pane:document.querySelector('.student-main').getBoundingClientRect().right,formRight:form.getBoundingClientRect().right,
   overflow:document.querySelector('.student-main').scrollWidth-document.querySelector('.student-main').clientWidth};
  });
  expect(order.stage).toBeLessThan(order.duration);
  expect(order.duration,'time must be ahead of advanced detail groups').toBeLessThan(order.advanced);
  await page.locator('.student-main').evaluate(main=>{main.scrollTop=0;});
  await page.screenshot({path:'student-visual-audit/process-editor/'+device+'-first-fold.png',animations:'disabled'});
  expect(order.overflow).toBeLessThanOrEqual(1);
  expect(order.formRight).toBeLessThanOrEqual(order.pane-10);
  await form.locator('[name="duration"]').fill('09:30');
  await composition.locator('summary').click();
  await composition.locator('[name="developer_amount"]').fill('15');
  await composition.locator('[name="water_amount"]').fill('535');
  await composition.locator('summary').click();
  await expect(form.locator('[name="developer_amount"]')).toHaveValue('15');
  await form.locator('[name="developer_key"]').selectOption('other');
  await expect(composition).toHaveAttribute('open','');
  await expect(form.locator('[name="developer_name"]')).toBeVisible();
  await form.locator('[name="developer_name"]').fill('Revelador experimental');
  await form.locator('[name="developer_key"]').selectOption('parodinal');
  await expect(form.locator('[name="developer_name"]')).toBeHidden();
  await extras.locator('summary').click();
  await form.locator('[name="agitation_mode"]').selectOption('periodic');
  await expect(form.locator('[name="agitation_duration"]')).toBeVisible();
  await form.locator('[name="agitation_duration"]').fill('00:20');
  await form.locator('[name="agitation_mode"]').selectOption('none');
  await expect(form.locator('[name="agitation_duration"]')).toBeHidden();
  await extras.locator('[name="notes"]').fill('Manter o filme imóvel');
  await extras.locator('summary').click();
  await expect(form.locator('[name="notes"]')).toHaveValue('Manter o filme imóvel');
  await expect(form.locator('[name="duration"]')).toHaveValue('09:30');
  await form.locator('[name="stage_key"]').selectOption('wash_after_first');
  await expect(form.locator('[data-process-template-development]')).toBeHidden();
  await form.locator('[name="stage_key"]').selectOption('second_development');
  await expect(form.locator('[data-process-template-development]')).toBeVisible();
  await expect(form.locator('[data-process-template-reuse]')).toBeVisible();
  await page.screenshot({path:'student-visual-audit/process-editor/'+device+'-create-task.png',animations:'disabled'});
  await expect(page.locator('.student-mobile-nav a')).toHaveCount(5);
  if(width<821){
    const pos=await page.evaluate(()=>({main:document.querySelector('.student-main').getBoundingClientRect().bottom,nav:document.querySelector('.student-mobile-nav').getBoundingClientRect().top}));
    expect(pos.main).toBeLessThanOrEqual(pos.nav+1);
  }
  expect(errors).toEqual([]);
 });
 test('existing process step preserves expanded relevant details '+device,async({page})=>{
  await page.setViewportSize({width,height:width===320?720:width===390?844:1100});
  const response=await page.goto(base+'?mode=edit',{waitUntil:'networkidle'});
  expect(response?.status()).toBe(200);
  const form=page.locator('[data-process-template-step-form]');
  await expect(form.locator('[name="stage_key"]')).toHaveValue('second_development');
  await expect(form.locator('[name="reuse_source_stage_key"]')).toBeChecked();
  await expect(form.locator('[name="developer_amount"]')).toHaveValue('10');
  await expect(form.locator('details').filter({hasText:'Composição da solução'})).toHaveAttribute('open','');
  await expect(form.locator('details').filter({hasText:'Agitação e observações'})).toHaveAttribute('open','');
  await expect(form.locator('[name="agitation_interval"]')).toHaveValue('01:00');
  await expect(form.locator('[name="notes"]')).toHaveValue('Reutilização documentada');
  await page.locator('.student-main').evaluate(main=>{main.scrollTop=0;});
  await page.screenshot({path:'student-visual-audit/process-editor/'+device+'-edit-detail.png',animations:'disabled'});
 });
}
test('custom operation bypasses irrelevant developer settings without hiding valid freeform notes',async({page})=>{
 await page.setViewportSize({width:390,height:844});
 await page.goto(base+'?mode=custom',{waitUntil:'networkidle'});
 const form=page.locator('[data-process-template-step-form]');
 await expect(form.locator('[name="custom_label"]')).toHaveValue('Secagem ao ar');
 await expect(form.locator('[name="custom_label"]')).toBeVisible();
 await expect(form.locator('[data-process-template-development]')).toBeHidden();
 await expect(form.locator('details').filter({hasText:'Agitação e observações'})).toHaveAttribute('open','');
 await expect(form.locator('[name="notes"]')).toHaveValue('Ambiente ventilado');
});
