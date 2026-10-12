const {test,expect}=require('@playwright/test');
const root='http://127.0.0.1:8099',fixture=root+'/tools/browser-fixture/student-inventory-item-direct.php';
const html=async(page,mode)=>{const res=await page.request.get(fixture+'?mode='+mode);expect(res.ok()).toBeTruthy();return res.text();};
async function mockRoutes(page){
 const [list,updated,editor]=await Promise.all(['list','updated','editor'].map(mode=>html(page,mode)));
 const seen=[];
 await page.route('**/aluno/inventario.php**',route=>route.fulfill({status:200,contentType:'text/html',body:route.request().url().includes('saved=1')?updated:list}));
 await page.route('**/aluno/inventario-item.php**',async route=>{
   if(route.request().method()==='POST'){
     const values=Object.fromEntries(new URLSearchParams(route.request().postData()||''));
     const ajax=route.request().headers()['x-requested-with']==='XMLHttpRequest';
     seen.push({ajax,values});
     if(String(values.name).includes('Recusado')){
       const result=await page.request.post(fixture+'?mode=error',{form:values});
       return route.fulfill({status:200,contentType:'text/html',body:await result.text()});
     }
     if(ajax)return route.fulfill({status:200,contentType:'text/html',body:updated});
     // A Playwright-fulfilled 303 bypasses page.route for the redirected request.
     // Redirect to the real, accessible fixture URL; verify production's 303 destination in PHP regression.
     return route.fulfill({status:303,headers:{location:'/tools/browser-fixture/student-inventory-item-direct.php?mode=updated'},body:''});
   }
   return route.fulfill({status:200,contentType:'text/html',body:editor});
 });
 return seen;
}
for(const [device,width,height] of [['phone',390,844],['desktop',1440,1100]]){
 test(`direct metadata editor retains failing draft, then redirects after native save — ${device}`,async({page})=>{
   await page.setViewportSize({width,height});const posts=await mockRoutes(page);
   await page.goto(root+'/aluno/inventario-item.php?id=7',{waitUntil:'networkidle'});
   await expect(page.getByRole('heading',{name:'Editar item'})).toBeVisible();
   // Dedicated route must offer both return actions; no modal is active here.
   await expect(page.locator('dialog[data-student-editor-dialog]')).toHaveCount(0);
   for(const name of ['Fechar','Cancelar']){
     const link=page.getByRole('link',{name,exact:true});
     await expect(link).toBeVisible();
     await expect(link).toHaveAttribute('href','/aluno/inventario.php');
   }
   await expect(page.getByRole('link',{name:'← Inventário'})).toBeVisible();
   const form=page.locator('[data-student-editor] form');
   await form.locator('[name="name"]').fill('Recusado <lote>');
   await form.locator('[name="lot_code"]').fill('ETAPA-123');
   await form.locator('[name="notes"]').fill('Anotação em edição');
   await form.getByRole('button',{name:'Salvar alterações'}).click();
   await expect(page).toHaveURL(/aluno\/inventario-item.php/);
   await expect(page.locator('[data-inventory-item-error-focus]')).toBeVisible();
   await expect(page.locator('[data-inventory-item-error-focus]')).toBeFocused();
   await expect(page.locator('[name="name"]')).toHaveValue('Recusado <lote>');
   await expect(page.locator('[name="lot_code"]')).toHaveValue('ETAPA-123');
   await expect(page.locator('[name="notes"]')).toHaveValue('Anotação em edição');
   expect(posts[0].ajax).toBe(false);
   await page.screenshot({path:`student-visual-audit/inventory-item/${device}-direct-error.png`,animations:'disabled'});
   await page.locator('[name="name"]').fill('Corrigido');
   await page.getByRole('button',{name:'Salvar alterações'}).click();
   await expect(page).toHaveURL(/tools\/browser-fixture\/student-inventory-item-direct.php\?mode=updated/);
   await expect(page.getByText('Lote atualizado')).toBeVisible();
   expect(posts[1].ajax).toBe(false);
   await page.screenshot({path:`student-visual-audit/inventory-item/${device}-redirect-after-save.png`,animations:'disabled'});
 });
 test(`record list editor uses mobile navigation or desktop contextual modal — ${device}`,async({page})=>{
   await page.setViewportSize({width,height});const posts=await mockRoutes(page);
   await page.goto(root+'/aluno/inventario.php',{waitUntil:'networkidle'});
   const link=page.locator('[data-student-editor-link]');
   await link.click();
   if(device==='phone'){
     await expect(page).toHaveURL(/aluno\/inventario-item.php/);
     await expect(page.getByRole('heading',{name:'Editar item'})).toBeVisible();
     await expect(page.locator('.student-mobile-nav a')).toHaveCount(4);
     await page.screenshot({path:'student-visual-audit/inventory-item/phone-dedicated-page.png',animations:'disabled'});
     await page.getByRole('link',{name:'← Inventário'}).first().click();
     await expect(page).toHaveURL(/aluno\/inventario.php/);
   }else{
     await expect(page).toHaveURL(/aluno\/inventario.php/);
     const dialog=page.locator('[data-student-editor-dialog]');
     await expect(dialog).toBeVisible();
     await expect(dialog.getByRole('heading',{name:'Editar item'})).toBeVisible();
     await page.screenshot({path:'student-visual-audit/inventory-item/desktop-editor-modal.png',animations:'disabled'});
     const layout=await dialog.evaluate(el=>{
       const box=el.getBoundingClientRect(),inner=el.querySelector('.student-inventory-editor-body>.student-lead').getBoundingClientRect(),small=el.querySelector('.student-inventory-editor-balance>small').getBoundingClientRect(),input=el.querySelector('input[name="name"]').getBoundingClientRect();
       return{left:box.left,right:box.right,innerLeft:inner.left,innerRight:inner.right,smallRight:small.right,inputLeft:input.left,overflow:el.scrollWidth-el.clientWidth};
     });
     expect(layout.innerLeft-layout.left,'dialog body must have inset').toBeGreaterThanOrEqual(16);
     expect(layout.right-layout.innerRight,'dialog body needs right inset').toBeGreaterThanOrEqual(16);
     expect(layout.smallRight,'stock explanation must wrap inside modal').toBeLessThanOrEqual(layout.innerRight+1);
     expect(layout.inputLeft,'form fields must not touch left frame').toBeGreaterThanOrEqual(layout.innerLeft);
     expect(layout.overflow,'dialog must have no horizontal overflow').toBeLessThanOrEqual(1);
     
     await dialog.locator('input[name="lot_code"]').fill('Lote atualizado');
     await dialog.getByRole('button',{name:'Salvar alterações'}).click();
     await expect(dialog).toBeHidden();
     await expect(page.getByText('Lote atualizado')).toBeVisible();
     expect(posts[0].ajax).toBe(true);
   }
 });
}
