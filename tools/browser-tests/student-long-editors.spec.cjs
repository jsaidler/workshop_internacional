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
    await expect(page.locator('.student-mobile-nav a')).toHaveCount(4);
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
  await expect(save).toBeInViewport();await expect(page.locator('.student-mobile-nav a')).toHaveCount(4);
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
