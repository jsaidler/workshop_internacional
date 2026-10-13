const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099',fixture=base+'/tools/browser-fixture/student-long-editors.php';
const families=[
 {kind:'preparation',route:'/aluno/preparos.php',name:'Predefinições de revelação',title:'Criar predefinição',editTitle:'Editar predefinição',region:'preparations-list'},
 {kind:'calibration',route:'/aluno/calibracao.php',name:'Referências de calibração',title:'Registrar calibração',editTitle:'Editar calibração',region:'calibrations-list'}
];
async function mock(page,f){
 const modes=['list','create','edit','updated'];
 const html=Object.fromEntries(await Promise.all(modes.map(async mode=>{
  const res=await page.request.get(fixture+'?kind='+f.kind+'&mode='+mode);
  expect(res.ok()).toBeTruthy();return [mode,await res.text()];
 })));
 const posts=[];
 await page.route('**'+f.route+'**',async route=>{
  const req=route.request(),url=new URL(req.url());
  if(req.method()==='POST'){
   const data=Object.fromEntries(new URLSearchParams(req.postData()||''));
   const ajax=req.headers()['x-requested-with']==='XMLHttpRequest';
   posts.push({data,ajax,url:url.pathname+url.search});
   if((req.postData()||'').includes('Recusado')){
    const invalid=await page.request.post(fixture+'?kind='+f.kind+'&mode=error',{form:data});
    return route.fulfill({status:200,contentType:'text/html',body:await invalid.text()});
   }
   return route.fulfill({status:303,headers:{location:'/tools/browser-fixture/student-long-editors.php?kind='+f.kind+'&mode=updated'}});
  }
  const mode=url.searchParams.has('novo')?'create':url.searchParams.has('editar')?'edit':'list';
  return route.fulfill({status:200,contentType:'text/html',body:html[mode]});
 });
 return posts;
}
for(const f of families){
 for(const [device,width,height] of [['phone',390,844],['desktop',1440,1100]]){
  test(f.kind+' long editor native route, failed draft and successful redirect '+device,async({page})=>{
   await page.setViewportSize({width,height});const posts=await mock(page,f);
   await page.goto(base+f.route+'?editar=7',{waitUntil:'networkidle'});
   const editor=page.locator('.student-main > [data-student-editor]');
   await expect(editor).toBeVisible();
   await expect(page.locator('[data-student-editor-dialog]')).toHaveCount(0);
   await expect(page.getByRole('link',{name:new RegExp('^← '+(f.kind==='preparation'?'Predefinições':'Referências de calibração'))})).toBeVisible();
   await expect(editor.getByRole('heading',{name:f.editTitle})).toBeVisible();
   await expect(editor.locator('.student-create-dialog-head [data-student-editor-close]')).toBeHidden();
   await expect(editor.locator('.student-actions [data-student-editor-close]')).toBeVisible();
   const form=editor.locator('form');
   await form.locator('[name="label"]').fill('Recusado <teste>');
   await form.locator('[name="label"]').blur();
   if(f.kind==='calibration')await form.locator('[name="white_time"]').fill('04:40');
   else await form.locator('[name="temperature"]').fill('28 °C');
   await form.getByRole('button',{name:'Salvar alterações'}).click();
   await expect(page).toHaveURL(new RegExp(f.route.replaceAll('/','\\/')+'\\?editar=7'));
   await expect(page.locator('[data-student-editor-error-focus]')).toBeVisible();
   await expect(page.locator('[data-student-editor-error-focus]')).toBeFocused();
   await expect(page.locator('.student-main [name="label"]')).toHaveValue('Recusado <teste>');
   expect(posts[0].ajax).toBe(false);
   await page.screenshot({path:`student-visual-audit/long-editors/${f.kind}-${device}-error.png`,animations:'disabled'});
   await page.locator('.student-main [name="label"]').fill('Revisado');
   await page.getByRole('button',{name:'Salvar alterações'}).click();
   await expect(page).toHaveURL(/student-long-editors.php.*mode=updated/);
   await expect(page.getByText('Registro atualizado')).toBeVisible();
   expect(posts[1].ajax).toBe(false);
   await page.screenshot({path:`student-visual-audit/long-editors/${f.kind}-${device}-saved.png`,animations:'disabled'});
  });
  test(f.kind+' parent list opens native phone page or desktop modal '+device,async({page})=>{
   await page.setViewportSize({width,height});const posts=await mock(page,f);
   await page.goto(base+f.route,{waitUntil:'networkidle'});
   const create=page.locator('.student-page-heading [data-student-editor-link]');
   await create.click();
   if(device==='phone'){
    await expect(page).toHaveURL(new RegExp(f.route.replaceAll('/','\\/')+'\\?novo=1'));
    await expect(page.locator('.student-main > [data-student-editor]')).toBeVisible();
    await expect(page.locator('[data-student-editor-dialog]')).toHaveCount(0);
    await expect(page.locator('.student-mobile-nav a')).toHaveCount(5);
    await expect(page.locator('.student-main [name="label"]')).toBeVisible();
    await page.screenshot({path:`student-visual-audit/long-editors/${f.kind}-phone-dedicated.png`,animations:'disabled'});
    await page.goBack();await expect(page).toHaveURL(new RegExp(f.route.replaceAll('/','\\/')+'$'));
    await page.goForward();await expect(page).toHaveURL(/novo=1/);
    await page.getByRole('link',{name:/^← /}).last().click();
    await expect(page).toHaveURL(new RegExp(f.route.replaceAll('/','\\/')+'$'));
   }else{
    await expect(page).toHaveURL(new RegExp(f.route.replaceAll('/','\\/')+'$'));
    const modal=page.locator('[data-student-editor-dialog]');
    await expect(modal).toBeVisible();
    await expect(modal.getByRole('heading',{name:f.title})).toBeVisible();
    await expect(modal.locator('[data-student-editor-close]').first()).toBeVisible();
    const layout=await modal.evaluate(node=>{
      const a=node.getBoundingClientRect(),inner=node.querySelector('form label.form-field').getBoundingClientRect();
      return{left:inner.left-a.left,right:a.right-inner.right,overflow:node.scrollWidth-node.clientWidth};
    });
    expect(layout.left,'modal form requires left inset').toBeGreaterThanOrEqual(16);
    expect(layout.right,'modal form requires right inset').toBeGreaterThanOrEqual(16);
    expect(layout.overflow,'modal has horizontal overflow').toBeLessThanOrEqual(1);
    if(f.kind==='preparation'){
      const custom=modal.locator('[data-custom-developer]');
      await expect(custom).toBeHidden();
      await modal.locator('[name="developer_key"]').selectOption('other');
      await expect(custom).toBeVisible();
      await modal.locator('[name="developer_key"]').selectOption('parodinal');
      await expect(custom).toBeHidden();
    }
    await page.screenshot({path:`student-visual-audit/long-editors/${f.kind}-desktop-modal.png`,animations:'disabled'});
    await modal.locator('[name="label"]').fill('Recusado teste');
    if(f.kind==='calibration')await modal.locator('[name="white_time"]').fill('04:30');
    await modal.getByRole('button',{name:/Salvar /}).click();
    await expect(modal.getByRole('alert')).toContainText('Não foi possível salvar');
    await expect(modal.locator('[name="label"]')).toHaveValue('Recusado teste');
    expect(posts[0].ajax).toBe(true);
    await page.screenshot({path:`student-visual-audit/long-editors/${f.kind}-desktop-modal-error.png`,animations:'disabled'});
    await modal.locator('[name="label"]').fill('Revisado');
    await modal.getByRole('button',{name:/Salvar /}).click();
    await expect(modal).toBeHidden();
    await expect(page.getByText('Registro atualizado')).toBeVisible();
    await expect(create).toBeFocused();
    expect(posts[1].ajax).toBe(true);
    await create.click();
    await expect(modal).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(modal).toBeHidden();
    await expect(create).toBeFocused();
   }
  });
 }
 test(f.kind+' compact phone long form last action above nav',async({page})=>{
  await page.setViewportSize({width:320,height:720});await mock(page,f);
  await page.goto(base+f.route+'?novo=1',{waitUntil:'networkidle'});
  const save=page.getByRole('button',{name:/Salvar /});await save.scrollIntoViewIfNeeded();
  await expect(save).toBeInViewport();await expect(page.locator('.student-mobile-nav a')).toHaveCount(5);
  const geometry=await page.evaluate(()=>{
   const main=document.querySelector('.student-main').getBoundingClientRect();
   const nav=document.querySelector('.student-mobile-nav').getBoundingClientRect();
   const action=[...document.querySelectorAll('button')].find(b=>b.textContent.includes('Salvar')).getBoundingClientRect();
   return{main:main.bottom,nav:nav.top,action:action.bottom,overflow:document.documentElement.scrollWidth-document.documentElement.clientWidth};
  });
  expect(geometry.main).toBeLessThanOrEqual(geometry.nav+1);
  expect(geometry.action).toBeLessThanOrEqual(geometry.nav+1);
  expect(geometry.overflow).toBeLessThanOrEqual(1);
  await page.screenshot({path:`student-visual-audit/long-editors/${f.kind}-compact-last-action.png`,animations:'disabled'});
 });
}

/* Task-first acceptance: measuring comes before describing; secondary metadata is
   still sent with the same form and can be edited without a new network contract. */
for(const width of [320,390]){
 test('calibration mobile prioritizes observed time, optional conditions and preserved values at '+width+'px',async({page})=>{
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.setViewportSize({width,height:width===320?720:844});
  const response=await page.goto(fixture+'?kind=calibration&mode=create',{waitUntil:'networkidle'});
  expect(response?.status()).toBe(200);
  const editor=page.locator('.student-main>.student-calibration-editor');
  await expect(editor).toBeVisible();
  const mainTime=editor.locator('[name="white_time"]');
  const name=editor.locator('[name="label"]');
  const conditions=editor.locator('[data-calibration-conditions]');
  await expect(mainTime).toBeVisible();
  await expect(conditions).not.toHaveAttribute('open','');
  const priority=await page.evaluate(()=>{
   const time=document.querySelector('.student-calibration-editor [name="white_time"]').getBoundingClientRect();
   const name=document.querySelector('.student-calibration-editor [name="label"]').getBoundingClientRect();
   const pane=document.querySelector('.student-main').getBoundingClientRect();
   return{time:time.top,name:name.top,paneRight:pane.right,timeRight:time.right};
  });
  expect(priority.time,'central measurement must precede reference metadata').toBeLessThan(priority.name);
  expect(priority.timeRight,'measurement must stay inside the scroll pane').toBeLessThanOrEqual(priority.paneRight-12);
  await mainTime.fill('03:20');await name.fill('HR-U · branco');
  await conditions.locator('summary').focus();
  await page.keyboard.press('Enter');
  await expect(conditions).toHaveAttribute('open','');
  await conditions.locator('[name="film"]').fill('Fuji HR-U');
  await conditions.locator('[name="temperature"]').fill('26 °C');
  await conditions.locator('summary').click();
  await expect(conditions).not.toHaveAttribute('open','');
  await expect(mainTime).toHaveValue('03:20');
  await conditions.locator('summary').click();
  await expect(conditions.locator('[name="film"]')).toHaveValue('Fuji HR-U');
  await page.screenshot({path:'student-visual-audit/long-editors/calibration-'+width+'-task-first.png',animations:'disabled'});
  const geometry=await page.evaluate(()=>{
   const main=document.querySelector('.student-main'),form=document.querySelector('.student-task-form');
   return{overflow:main.scrollWidth-main.clientWidth,rightInset:main.getBoundingClientRect().right-form.getBoundingClientRect().right};
  });
  expect(geometry.overflow,'no horizontal overflow at '+width).toBeLessThanOrEqual(1);
  expect(geometry.rightInset,'leave breathing room beside scroll track').toBeGreaterThanOrEqual(12);
  expect(errors).toEqual([]);
 });
 test('preparation mobile separates composition and use without losing working controls at '+width+'px',async({page})=>{
  await page.setViewportSize({width,height:width===320?720:844});
  const response=await page.goto(fixture+'?kind=preparation&mode=create',{waitUntil:'networkidle'});
  expect(response?.status()).toBe(200);
  const form=page.locator('.student-main .student-preparation-editor form');
  await expect(form).toBeVisible();
  await expect(form.locator('[name="label"]')).toBeVisible();
  await expect(form.locator('[name="developer_key"]')).toBeVisible();
  await expect(form.locator('[name="developer_key"]')).toHaveValue('parodinal');
  await expect(form.locator('[data-custom-developer]')).toBeHidden();
  await expect(form.locator('[data-developer-mix] [name="water_amount"]')).toBeVisible();
  await form.locator('[name="developer_key"]').selectOption('other');
  await expect(form.locator('[data-custom-developer]')).toBeVisible();
  await form.locator('[name="developer_key"]').selectOption('parodinal');
  await expect(form.locator('[data-custom-developer]')).toBeHidden();
  await expect(form.locator('[name="temperature"]')).toBeVisible();
  const agitation=form.locator('.student-parameter-disclosure');
  await expect(agitation).not.toHaveAttribute('open','');
  await agitation.locator('summary').click();
  await agitation.locator('[name="agitation"]').fill('Agitação leve');
  await expect(agitation.locator('[name="agitation"]')).toHaveValue('Agitação leve');
  await page.screenshot({path:'student-visual-audit/long-editors/preparation-'+width+'-task-first.png',animations:'disabled'});
  const inset=await form.evaluate(node=>{
    const pane=document.querySelector('.student-main');
    return{right:pane.getBoundingClientRect().right-node.getBoundingClientRect().right,overflow:pane.scrollWidth-pane.clientWidth};
  });
  expect(inset.right).toBeGreaterThanOrEqual(12);
  expect(inset.overflow).toBeLessThanOrEqual(1);
 });
}
for(const kind of ['calibration','preparation']){
 test(kind+' failed POST retains expanded contextual values in the operational PHP renderer',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  const response=await page.goto(fixture+'?kind='+kind+'&mode=error',{waitUntil:'networkidle'});
  expect(response?.status()).toBe(200);
  const editor=page.locator('.student-main .student-task-editor');
  await expect(editor.getByRole('alert')).toBeVisible();
  await expect(editor.locator('[name="label"]')).toHaveValue('Teste <script>');
  if(kind==='calibration'){
   const details=editor.locator('[data-calibration-conditions]');
   await expect(details).toHaveAttribute('open','');
   await expect(details.locator('[name="temperature"]')).toHaveValue('30 °C');
   await expect(editor.locator('[name="white_time"]')).toHaveValue('04:30');
  }else{
   await expect(editor.locator('[name="developer_key"]')).toHaveValue('other');
   await expect(editor.locator('[data-custom-developer]')).toBeVisible();
   await expect(editor.locator('[name="temperature"]')).toHaveValue('30 °C');
  }
 });
}
