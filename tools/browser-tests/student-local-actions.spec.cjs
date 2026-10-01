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
