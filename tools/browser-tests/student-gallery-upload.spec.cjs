const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099',photo=n=>({name:`foto-${n}.jpg`,mimeType:'image/jpeg',buffer:Buffer.from([255,216,255,217])});
const fixture=async page=>{const response=await page.request.get(base+'/tools/browser-fixture/student-record-gallery.php');expect(response.ok()).toBeTruthy();return response.text();};
const show=async region=>{
  const trigger=region.getByRole('button',{name:'Adicionar imagens'});await trigger.click();
  const sheet=region.locator('dialog[data-student-action-sheet]');
  await expect(sheet).toBeVisible();
  await expect(sheet.getByText('Escolher da galeria',{exact:true})).toBeVisible();
  await expect(sheet.getByText('Fotografar',{exact:true})).toBeVisible();
  await expect(sheet.locator('input[name="images[]"]')).toHaveAttribute('multiple','');
  await expect(sheet.locator('input[name="images[]"]')).not.toHaveAttribute('capture');
  await expect(sheet.locator('input[name="image"]')).toHaveAttribute('capture','environment');
  for(const option of await sheet.locator('.student-action-sheet-choice').all()){
    const size=await option.evaluate(el=>({h:el.getBoundingClientRect().height,w:el.getBoundingClientRect().width}));
    expect(size.h).toBeGreaterThanOrEqual(60);expect(size.w).toBeGreaterThanOrEqual(240);
  }
  return{sheet,trigger};
};
for(const [device,width,height] of [['phone',390,844],['desktop',1440,1100]]){
test(`image action sheet with multi-gallery, camera, closing and local update — ${device}`,async({page})=>{
  await page.setViewportSize({width,height});const html=await fixture(page),posts=[];
  await page.route('**/aluno/teste.php**',route=>{if(route.request().method()==='POST')posts.push(route.request().postDataBuffer()?.toString('latin1')||'');return route.fulfill({status:200,contentType:'text/html',body:html});});
  await page.goto(base+'/aluno/teste.php?fixture=gallery',{waitUntil:'networkidle'});
  const scene=page.locator('#exposicao'),result=page.locator('#resultado');
  await expect(scene.getByRole('button',{name:'Adicionar imagens'})).toBeVisible();
  await expect(scene.getByText('Fotografar',{exact:true})).not.toBeVisible();
  let{sheet,trigger}=await show(scene);
  if(device==='phone'){const g=await sheet.evaluate(el=>({bottom:el.getBoundingClientRect().bottom,viewport:innerHeight}));expect(Math.abs(g.bottom-g.viewport)).toBeLessThanOrEqual(1);}
  await page.keyboard.press('Escape');await expect(sheet).not.toBeVisible();await expect(trigger).toBeFocused();
  ({sheet,trigger}=await show(scene));await sheet.getByRole('button',{name:'Fechar opções de imagem'}).click();await expect(sheet).not.toBeVisible();await expect(trigger).toBeFocused();
  ({sheet}=await show(scene));await page.screenshot({path:`student-visual-audit/sheets/${device}-scene-open.png`,animations:'disabled'});
  await sheet.locator('input[name="images[]"]').setInputFiles([photo(1),photo(2)]);
  await expect.poll(()=>posts.length).toBe(1);expect(posts[0]).toContain('filename="foto-1.jpg"');expect(posts[0]).toContain('filename="foto-2.jpg"');expect(posts[0]).toContain('scene');expect(posts[0]).toContain('upload');
  await expect(scene.locator('dialog')).not.toBeVisible();await expect(scene.getByRole('status')).toContainText('2 imagens adicionadas.');
  ({sheet}=await show(result));await page.screenshot({path:`student-visual-audit/sheets/${device}-result-open.png`,animations:'disabled'});
  await sheet.locator('input[name="images[]"]').setInputFiles([photo(3),photo(4)]);await expect.poll(()=>posts.length).toBe(2);expect(posts[1]).toContain('result');expect(posts[1]).toContain('filename="foto-3.jpg"');
  ({sheet}=await show(scene));await sheet.locator('input[name="image"]').setInputFiles(photo(5));await expect.poll(()=>posts.length).toBe(3);expect(posts[2]).toContain('filename="foto-5.jpg"');expect(posts[2]).toContain('scene');
});
test(`failed gallery upload stays in sheet and can retry — ${device}`,async({page})=>{
  await page.setViewportSize({width,height});const html=await fixture(page);let count=0;
  await page.route('**/aluno/teste.php**',route=>{if(route.request().method()==='POST'){count++;return route.fulfill({status:200,contentType:'text/html',body:'<p class="ui-alert ui-alert-error">Falha no envio.</p>'});}return route.fulfill({status:200,contentType:'text/html',body:html});});
  await page.goto(base+'/aluno/teste.php?fixture=gallery',{waitUntil:'networkidle'});
  const{sheet,trigger}=await show(page.locator('#resultado'));
  await sheet.locator('input[name="images[]"]').setInputFiles([photo(9),photo(10)]);await expect.poll(()=>count).toBe(1);
  await expect(sheet).toBeVisible();await expect(sheet.getByRole('alert')).toContainText('Falha no envio.');
  await sheet.locator('input[name="images[]"]').setInputFiles([photo(9),photo(10)]);await expect.poll(()=>count).toBe(2);
  await page.screenshot({path:`student-visual-audit/sheets/${device}-error.png`,animations:'disabled'});
  await page.keyboard.press('Escape');await expect(sheet).not.toBeVisible();await expect(trigger).toBeFocused();
});
}
