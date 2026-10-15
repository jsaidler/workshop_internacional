const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-process-editor.php';
const dimensions=[
  {label:'narrow',width:320,height:720},
  {label:'phone',width:390,height:844},
  {label:'desktop',width:1440,height:1100},
];
async function screenshotAt(page,label,part){
  const main=page.locator('.student-main');
  await main.evaluate((el,where)=>{el.scrollTop=where==='start'?0:where==='middle'?(el.scrollHeight-el.clientHeight)/2:el.scrollHeight;},part);
  await page.screenshot({path:'student-visual-audit/process-template-editor/'+label+'-'+part+'.png',animations:'disabled'});
}
for(const dim of dimensions){
  test('full operational processing template editor '+dim.label,async({page})=>{
    const errors=[];page.on('pageerror',error=>errors.push(error.message));
    await page.setViewportSize({width:dim.width,height:dim.height});
    const response=await page.goto(base+'?mode=edit',{waitUntil:'networkidle'});
    expect(response?.status()).toBe(200);
    const view=page.locator('[data-operational-template-editor]');
    await expect(view).toHaveCount(1);
    await expect(page.locator('.student-shell')).toHaveCount(1);
    await expect(page.locator('.student-mobile-nav a')).toHaveCount(5);
    await expect(page.locator('.student-process-step-card')).toHaveCount(9);
    await expect(view.locator('[data-stage-key="ferric"]')).toContainText('Cloreto férrico');
    await expect(view.locator('[data-stage-key="ammonia"]')).toContainText('amônia');
    await expect(view.getByText('Mesmo banho da 1ª revelação')).toHaveCount(1);
    await expect(view.getByRole('link',{name:'Abrir no laboratório'})).toHaveAttribute('href','/aluno/processar.php?template=14');
    await expect(view.getByText('Iniciar no laboratório',{exact:true})).toHaveCount(0);
    const current=view.locator('.student-process-step-card.is-editing');
    await expect(current).toHaveCount(1);
    const form=current.locator('[data-process-template-step-form]');
    await expect(form.locator('[name="stage_key"]')).toHaveValue('final_wash');
    await expect(form.locator('[name="step_id"]')).toHaveValue('38');
    await expect(form.locator('[name="_csrf"]')).toHaveValue('fixture-csrf-no-write');
    await expect(form.locator('[data-process-template-development]')).toBeHidden();
    await expect(form.locator('[data-process-template-reuse]')).toBeHidden();
    await expect(form.locator('input[name="duration"]')).toBeVisible();
    await expect(form.getByRole('button',{name:'Salvar etapa'})).toBeVisible();
    const operations=await view.locator('.student-process-step-actions form').evaluateAll(forms=>forms.map(f=>({action:f.querySelector('[name="action"]')?.value,csrf:f.querySelector('[name="_csrf"]')?.value,method:f.method})));
    expect(operations.filter(x=>x.action==='delete_step')).toHaveLength(9);
    expect(operations.filter(x=>x.action==='move_step')).toHaveLength(16);
    expect(operations.every(x=>x.csrf==='fixture-csrf-no-write'&&x.method==='post')).toBe(true);
    const metrics=await page.evaluate(()=>{
      const reading=document.querySelector('.student-main'),nav=document.querySelector('.student-mobile-nav');
      const content=document.querySelector('[data-operational-template-editor]');
      return {overflow:reading.scrollWidth-reading.clientWidth,gutter:getComputedStyle(reading).scrollbarGutter,contentWidth:content.getBoundingClientRect().width,readingWidth:reading.getBoundingClientRect().width,readingBottom:reading.getBoundingClientRect().bottom,navTop:nav.getBoundingClientRect().top,navVisible:getComputedStyle(nav).display!=='none'};
    });
    expect(metrics.overflow,'horizontal scroll cannot hide form actions').toBeLessThanOrEqual(1);
    expect(metrics.gutter).toContain('stable');
    expect(metrics.contentWidth).toBeLessThanOrEqual(metrics.readingWidth+1);
    if(dim.width<821&&metrics.navVisible)expect(metrics.readingBottom).toBeLessThanOrEqual(metrics.navTop+1);
    await screenshotAt(page,dim.label+'-edit','start');
    await screenshotAt(page,dim.label+'-edit','middle');
    await screenshotAt(page,dim.label+'-edit','end');
    await form.locator('[name="stage_key"]').selectOption('second_development');
    await expect(form.locator('[data-process-template-development]')).toBeVisible();
    await expect(form.locator('[data-process-template-reuse]')).toBeVisible();
    await form.locator('[name="developer_key"]').selectOption('other');
    await expect(form.locator('[data-process-template-other-developer]')).toBeVisible();
    await expect(form.locator('details').filter({hasText:'Composição da solução'})).toHaveAttribute('open','');
    await form.locator('[name="stage_key"]').selectOption('final_wash');
    await expect(form.locator('[data-process-template-development]')).toBeHidden();
    await expect(form.locator('[name="developer_key"]')).toBeDisabled();
    await form.locator('[name="stage_key"]').selectOption('second_development');
    await expect(form.locator('[name="developer_key"]')).toHaveValue('other');
    await expect(form.locator('[name="developer_key"]')).toBeEnabled();
    await screenshotAt(page,dim.label+'-modified','end');
    expect(errors).toEqual([]);
  });
}
test('empty template exposes adding, never running',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  const response=await page.goto(base+'?mode=empty',{waitUntil:'networkidle'});
  expect(response?.status()).toBe(200);
  await expect(page.locator('.student-process-step-card')).toHaveCount(0);
  await expect(page.getByText('Este roteiro ainda está vazio.')).toBeVisible();
  await expect(page.getByRole('link',{name:'Abrir no laboratório'})).toHaveCount(0);
  const add=page.locator('details.student-process-add');
  await expect(add).toHaveAttribute('open','');
  await expect(add.locator('[name="action"]')).toHaveValue('add_step');
  await screenshotAt(page,'phone-empty','start');
});
test('record association remains distinct from execution and personal duplication',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  const response=await page.goto(base+'?mode=association',{waitUntil:'networkidle'});
  expect(response?.status()).toBe(200);
  const association=page.locator('form').filter({has:page.locator('input[name="action"][value="apply_template"]')});
  await expect(association).toHaveCount(1);
  await expect(association.locator('input[name="test_id"]')).toHaveValue('7');
  await expect(association.getByRole('button',{name:'Associar ao registro'})).toBeVisible();
  await expect(page.getByRole('button',{name:'Duplicar processamento'})).toHaveCount(0);
  await expect(page.getByRole('link',{name:'Abrir no laboratório'})).toHaveCount(0);
  await expect(page.locator('.student-mobile-nav a[href="/aluno/caderno.php"]')).toHaveAttribute('aria-current','page');
  await screenshotAt(page,'phone-association','start');
});
test('unmodified template is a navigable reading surface without an auto-start',async({page})=>{
  await page.setViewportSize({width:320,height:720});
  await page.goto(base+'?mode=read',{waitUntil:'networkidle'});
  await expect(page.locator('.student-process-step-card.is-editing')).toHaveCount(0);
  await expect(page.locator('.student-process-step-card')).toHaveCount(9);
  await expect(page.locator('.student-process-step-actions a').first()).toHaveAttribute('href','/aluno/processamentos.php?id=14&step=31#editar-etapa');
  await screenshotAt(page,'narrow-read','start');
});
