const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-process-ux.html?id=17';

test('process log is editable and branching requires an explicit decision',async({page})=>{
  await page.setViewportSize({width:1100,height:900});
  await page.goto(base,{waitUntil:'networkidle'});
  await expect(page.locator('.student-process-history')).toHaveAttribute('open','');
  await expect(page.locator('.student-process-step-edit')).toHaveCount(2);
  await expect(page.locator('.student-process-step-edit').first()).toHaveAttribute('href','/aluno/teste-etapa.php?registro=17&etapa=1');

  const primary=page.locator('.student-sticky-action .button-primary');
  await expect(primary).toBeDisabled();
  await expect(page.locator('.student-process-now h2')).toHaveText('Escolha como continuar');
  const guided=page.locator('.student-process-choice-button');
  await expect(guided).toHaveCount(2);
  await expect(guided.nth(0)).toHaveText('Lavagem com água');
  await expect(guided.nth(1)).toHaveText('Banho interruptor');

  await guided.nth(1).click();
  await expect(primary).toBeEnabled();
  await expect(page.locator('input[name="stage_key"][value="stop_after_first"]')).toBeChecked();
  await expect(page.locator('[data-stage-fields="simple"]')).toBeVisible();
  const inventory=page.locator('select[name="inventory_item_id_simple"]');
  const amount=page.locator('input[name="inventory_amount_simple"]');
  await expect(inventory).toBeVisible();
  await expect(amount).toBeHidden();
  await inventory.selectOption('4');
  await expect(amount).toBeVisible();

  await page.locator('.student-process-other-stage summary').click();
  await page.locator('.student-process-other-stage select').selectOption('dry');
  await expect(page.locator('input[name="stage_key"][value="dry"]')).toBeChecked();
  await expect(page.locator('.student-process-now h2')).toHaveText('Secagem');
  await expect(inventory).toBeHidden();
  await expect(amount).toBeHidden();
  await expect(page.locator('[data-lab-timer]')).toBeHidden();
  await expect(page.locator('input[name="duration_simple"]').locator('xpath=..')).toContainText('Tempo de secagem');
});

test('developer catalog keeps course developers prominent and explains custom developer input',async({page})=>{
  await page.goto(base,{waitUntil:'networkidle'});
  await page.locator('.student-process-other-stage summary').click();
  await page.locator('.student-process-other-stage select').selectOption('second_development');
  const select=page.locator('[data-developer-select]');
  await expect(select).toBeVisible();
  await expect(select).toBeEnabled();
  await expect(select.locator('optgroup')).toHaveCount(3);
  await expect(select.locator('optgroup').nth(0)).toHaveAttribute('label','Pesquisa / curso');
  await expect(select.locator('optgroup').nth(0).locator('option')).toHaveCount(2);
  await expect(select.locator('optgroup').nth(1)).toHaveAttribute('label','Outros reveladores');
  await expect(select.locator('option[value="d76"]')).toHaveCount(1);
  await expect(select.locator('optgroup').nth(2)).toHaveAttribute('label','Personalizado');
  await expect(select.locator('option[value="other"]')).toHaveCount(1);
  await select.selectOption('other');
  const custom=page.locator('[data-custom-developer]');
  await expect(custom).toBeVisible();
  await expect(custom).toContainText('Outro revelador');
  await expect(custom).toContainText('Preencha somente quando “Outro” estiver selecionado.');
});
