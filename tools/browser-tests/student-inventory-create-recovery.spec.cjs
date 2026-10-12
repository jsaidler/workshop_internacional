const {test,expect}=require('@playwright/test');
const url='http://127.0.0.1:8099/tools/browser-fixture/student-inventory-create-recovery.php';
for(const [device,width,height] of [['phone',390,844],['desktop',1440,1100]]){
 test(`inventory creation has both triggers and retains its inline draft — ${device}`,async({page})=>{
  await page.setViewportSize({width,height});await page.goto(url,{waitUntil:'networkidle'});
  const panel=page.locator('[data-inventory-new-panel]'),openers=page.locator('[data-inventory-new-toggle]');
  await expect(openers).toHaveCount(2);await expect(panel).toBeHidden();
  await openers.last().click();await expect(panel).toBeVisible();
  await expect(openers.first()).toHaveAttribute('aria-expanded','true');await expect(openers.last()).toHaveAttribute('aria-expanded','true');
  const name=panel.locator('input[name="name"]');await name.fill('Solução teste');
  await panel.locator('input[name="quantity"]').fill('55');
  await panel.locator('input[name="location"]').fill('Frasco âmbar');
  await panel.locator('[data-inventory-new-cancel]').click();
  await expect(panel).toBeHidden();await expect(openers.first()).toHaveAttribute('aria-expanded','false');await expect(openers.last()).toHaveAttribute('aria-expanded','false');
  await expect(openers.first()).toBeFocused();
  await openers.first().click();await expect(name).toHaveValue('Solução teste');await expect(panel.locator('input[name="quantity"]')).toHaveValue('55');
  await panel.scrollIntoViewIfNeeded();
  await page.screenshot({path:`student-visual-audit/inventory-create/${device}-draft.png`,animations:'disabled'});
 });
 test(`inventory server POST failure preserves all fields and reveals the error — ${device}`,async({page})=>{
  await page.setViewportSize({width,height});await page.goto(url+'?mode=error',{waitUntil:'networkidle'});
  const panel=page.locator('[data-inventory-new-panel]');
  await expect(panel).toBeVisible();await expect(panel.getByRole('alert')).toContainText('Não foi possível cadastrar');
  await expect(panel.locator('[data-inventory-error-focus]')).toBeFocused();
  await expect(panel.locator('input[name="name"]')).toHaveValue('Parodinal <lote>');
  await expect(panel.locator('input[name="quantity"]')).toHaveValue('125.5');
  await expect(panel.locator('input[name="item_kind"][value="solution"]')).toBeChecked();
  await expect(panel.locator('select[name="unit"]')).toHaveValue('g');
  await expect(panel.locator('details.student-optional-fields')).toHaveAttribute('open','');
  await expect(panel.locator('textarea[name="notes"]')).toHaveValue('Anotação importante');
  await page.screenshot({path:`student-visual-audit/inventory-create/${device}-server-error.png`,animations:'disabled'});
  const response=await page.request.post(url,{form:{action:'create',name:'Lote "X" <unsafe>',item_kind:'raw',quantity:'20',unit:'ml',notes:'Meu registro'}});
  expect(response.ok()).toBeTruthy();
  const source=await response.text();
  expect(source).toContain('value="Lote &quot;X&quot; &lt;unsafe&gt;"');
  expect(source).toContain('Meu registro');expect(source).toContain('data-inventory-error-focus');
 });
 test(`inventory AJAX failure keeps draft and form expanded — ${device}`,async({page})=>{
  await page.setViewportSize({width,height});await page.goto(url,{waitUntil:'networkidle'});
  await page.locator('[data-inventory-new-toggle]').first().click();
  const panel=page.locator('[data-inventory-new-panel]');
  await panel.locator('input[name="name"]').fill('Teste de falha AJAX');
  await panel.locator('input[name="quantity"]').fill('22');
  await panel.getByRole('button',{name:'Adicionar ao inventário'}).click();
  await expect(panel).toBeVisible();
  await expect(panel.getByRole('alert')).toContainText('Não foi possível cadastrar');
  await expect(panel.locator('input[name="name"]')).toHaveValue('Teste de falha AJAX');
  await expect(panel.locator('input[name="quantity"]')).toHaveValue('22');
  await page.screenshot({path:`student-visual-audit/inventory-create/${device}-ajax-error.png`,animations:'disabled'});
 });
}
