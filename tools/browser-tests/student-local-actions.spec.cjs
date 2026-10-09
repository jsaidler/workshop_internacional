const {test,expect}=require('@playwright/test');
const url='http://127.0.0.1:8099/tools/browser-fixture/student-local-actions.html';

const pageHtml=value=>`<!doctype html><html><body><section class="region" data-student-local-key="fixture-list"><h2>Lista</h2><p data-list-value>${value}</p><a href="/fixture/editor" data-student-editor-link>Editar</a></section></body></html>`;

test('local mutation updates only its region and preserves reading position',async({page})=>{
  await page.route('**/fixture/save',route=>route.fulfill({status:200,contentType:'text/html',body:pageHtml('Valor atualizado')}));
  await page.goto(url,{waitUntil:'networkidle'});
  const region=page.locator('[data-student-local-key="fixture-list"]');
  await region.evaluate(el=>el.scrollIntoView({block:'center'}));
  const before=await region.evaluate(el=>({top:el.getBoundingClientRect().top,y:scrollY,url:location.href}));
  await page.locator('form[data-student-local-form] input[name="value"]').fill('novo');
  await page.locator('form[data-student-local-form] button[type="submit"]').click();
  await expect(page.locator('[data-list-value]')).toHaveText('Valor atualizado');
  const after=await region.evaluate(el=>({top:el.getBoundingClientRect().top,y:scrollY,url:location.href}));
  expect(after.url).toBe(before.url);
  expect(Math.abs(after.top-before.top)).toBeLessThan(4);
  expect(Math.abs(after.y-before.y)).toBeLessThan(4);
});

test('contextual editor opens, saves locally and closes without navigating',async({page})=>{
  await page.route('**/fixture/editor',route=>route.fulfill({status:200,contentType:'text/html',body:'<!doctype html><html><body><div data-student-editor><h2>Editar</h2><form action="/fixture/editor-save" method="post" data-student-local-form data-local-refresh="fixture-list" data-local-success="close-dialog"><label>Nome<input name="name" value="Antigo"></label><button type="submit">Salvar</button><button type="button" data-student-editor-close>Cancelar</button></form></div></body></html>'}));
  await page.route('**/fixture/editor-save',route=>route.fulfill({status:200,contentType:'text/html',body:pageHtml('Editado no diálogo')}));
  await page.goto(url,{waitUntil:'networkidle'});
  const original=page.url();
  await page.locator('[data-student-editor-link]').click();
  const dialog=page.locator('[data-student-editor-dialog]');
  await expect(dialog).toHaveAttribute('open','');
  await dialog.locator('input[name="name"]').fill('Novo nome');
  await dialog.locator('button[type="submit"]').click();
  await expect(dialog).not.toHaveAttribute('open','');
  await expect(page.locator('[data-list-value]')).toHaveText('Editado no diálogo');
  expect(page.url()).toBe(original);
});

test('validation error keeps contextual editor and its unsaved value',async({page})=>{
  await page.route('**/fixture/editor',route=>route.fulfill({status:200,contentType:'text/html',body:'<!doctype html><html><body><div data-student-editor><form action="/fixture/editor-save" method="post" data-student-local-form data-local-refresh="fixture-list" data-local-success="close-dialog"><input name="name" value="Antigo"><button type="submit">Salvar</button></form></div></body></html>'}));
  await page.route('**/fixture/editor-save',route=>route.fulfill({status:200,contentType:'text/html',body:'<!doctype html><html><body><p class="ui-alert ui-alert-error">Nome inválido.</p></body></html>'}));
  await page.goto(url,{waitUntil:'networkidle'});
  await page.locator('[data-student-editor-link]').click();
  const dialog=page.locator('[data-student-editor-dialog]');
  const input=dialog.locator('input[name="name"]');
  await input.fill('Valor ainda não salvo');
  await dialog.locator('button[type="submit"]').click();
  await expect(dialog).toHaveAttribute('open','');
  await expect(input).toHaveValue('Valor ainda não salvo');
  await expect(dialog.locator('[data-local-status]')).toContainText('Nome inválido.');
});

test('expired session is reported explicitly and preserves the unsaved local value',async({page})=>{
  await page.route('**/fixture/save',route=>route.fulfill({status:401,contentType:'text/html',body:'Autenticação necessária.'}));
  await page.goto(url,{waitUntil:'networkidle'});
  const input=page.locator('form[data-student-local-form] input[name="value"]');
  await input.fill('ainda não salvo');
  await page.locator('form[data-student-local-form] button[type="submit"]').click();
  await expect(page.locator('form[data-student-local-form] [data-local-status]')).toHaveText('Sua sessão expirou. Entre novamente para continuar.');
  await expect(input).toHaveValue('ainda não salvo');
});

test('mobile AJAX update preserves inner scroll and keeps content above persistent navigation',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.route('**/fixture/save',route=>route.fulfill({status:200,contentType:'text/html',body:pageHtml('Atualizado no celular')}));
  await page.goto(url,{waitUntil:'networkidle'});
  const main=page.locator('.student-main'),nav=page.locator('.student-mobile-nav');
  await expect(nav).toBeVisible();
  const beforeGeometry=await page.evaluate(()=>{
    const main=document.querySelector('.student-main'),nav=document.querySelector('.student-mobile-nav');
    return{bottom:main.getBoundingClientRect().bottom,navTop:nav.getBoundingClientRect().top,height:main.clientHeight,total:main.scrollHeight,rootHeight:document.documentElement.scrollHeight,viewport:innerHeight};
  });
  expect(beforeGeometry.bottom).toBeLessThanOrEqual(beforeGeometry.navTop+1);
  expect(beforeGeometry.total).toBeGreaterThan(beforeGeometry.height);
  expect(beforeGeometry.rootHeight).toBeLessThanOrEqual(beforeGeometry.viewport+1);
  await main.evaluate(el=>el.scrollTop=el.scrollHeight);
  const record=page.locator('[data-student-local-key="fixture-list"]');
  const before=await record.evaluate(el=>({top:el.getBoundingClientRect().top,y:document.querySelector('.student-main').scrollTop}));
  await page.locator('form[data-student-local-form] input[name="value"]').fill('nova observação');
  await page.locator('form[data-student-local-form] button[type="submit"]').click();
  await expect(page.locator('[data-list-value]')).toHaveText('Atualizado no celular');
  const after=await record.evaluate(el=>({top:el.getBoundingClientRect().top,y:document.querySelector('.student-main').scrollTop}));
  expect(Math.abs(after.top-before.top)).toBeLessThan(4);
  expect(Math.abs(after.y-before.y)).toBeLessThan(4);
  await page.screenshot({path:'student-visual-audit/shell/phone-after-ajax.png',animations:'disabled'});
});
