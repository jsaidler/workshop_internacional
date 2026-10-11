const {test,expect}=require('@playwright/test');
const url='http://127.0.0.1:8099/tools/browser-fixture/student-record-create-sheet.php';
for(const [device,width,height] of [['phone',390,844],['desktop',1440,1100]]){
test(`new record: opens, draft stays, closes and restores focus — ${device}`,async({page})=>{
  await page.setViewportSize({width,height});
  await page.goto(url,{waitUntil:'networkidle'});
  const dialog=page.locator('dialog[data-record-create-dialog]');
  const triggers=page.locator('[data-record-create-open]');
  await expect(triggers).toHaveCount(2);
  await triggers.first().click();
  await expect(dialog).toBeVisible();
  await expect(dialog.getByRole('heading',{name:'Novo registro'})).toBeFocused();
  await expect(page.locator('.student-mobile-nav a')).toHaveCount(4);
  if(device==='phone'){
    const position=await dialog.evaluate(el=>({bottom:el.getBoundingClientRect().bottom,viewport:innerHeight,width:el.getBoundingClientRect().width,height:el.getBoundingClientRect().height}));
    expect(Math.abs(position.bottom-position.viewport)).toBeLessThanOrEqual(2);
    expect(position.width).toBeGreaterThanOrEqual(width-2);
    expect(position.height).toBeLessThanOrEqual(height);
  }
  await expect(dialog.getByRole('textbox',{name:'Título'})).toBeVisible();
  await expect(dialog.getByLabel('Data')).toBeVisible();
  await dialog.getByRole('textbox',{name:'Título'}).fill('Teste com rascunho');
  await dialog.getByText('Curso',{exact:true}).click();
  await expect(dialog.getByRole('radio',{name:'Curso'})).toBeChecked();
  await dialog.getByLabel('Curso e turma').selectOption('17');
  await page.screenshot({path:`student-visual-audit/record-create/${device}-course-filled.png`,animations:'disabled'});
  await page.keyboard.press('Escape');
  await expect(dialog).not.toBeVisible();
  await expect(triggers.first()).toBeFocused();
  await triggers.last().click();
  await expect(dialog).toBeVisible();
  await expect(dialog.getByRole('textbox',{name:'Título'})).toHaveValue('Teste com rascunho');
  await expect(dialog.getByLabel('Curso e turma')).toHaveValue('17');
  await dialog.getByRole('button',{name:'Fechar'}).click();
  await expect(dialog).not.toBeVisible();
  await expect(triggers.last()).toBeFocused();
});
test(`new record: server error reopens sheet with fields and accessible alert — ${device}`,async({page})=>{
  await page.setViewportSize({width,height});
  await page.goto(url+'?mode=error',{waitUntil:'networkidle'});
  const dialog=page.locator('dialog[data-record-create-dialog]');
  await expect(dialog).toBeVisible();
  await expect(dialog.getByRole('alert')).toContainText('Não foi possível criar o registro.');
  await expect(dialog.getByRole('alert')).toBeFocused();
  await expect(dialog.getByRole('textbox',{name:'Título'})).toHaveValue('EI 200 — teste de película');
  await expect(dialog.getByLabel('Data')).toHaveValue('2026-10-09');
  await expect(dialog.getByRole('radio',{name:'Curso'})).toBeChecked();
  await expect(dialog.getByLabel('Curso e turma')).toHaveValue('17');
  await expect(dialog.getByRole('button',{name:'Criar registro'})).toBeVisible();
  await page.screenshot({path:`student-visual-audit/record-create/${device}-server-error.png`,animations:'disabled'});
  await dialog.getByRole('button',{name:'Fechar'}).click();
  await expect(dialog).not.toBeVisible();
});
test(`new record: no course context for un-enrolled student — ${device}`,async({page})=>{
  await page.setViewportSize({width,height});
  await page.goto(url+'?mode=personal',{waitUntil:'networkidle'});
  await page.getByRole('button',{name:'Novo registro',exact:true}).click();
  const dialog=page.locator('dialog[data-record-create-dialog]');
  await expect(dialog.getByRole('radio',{name:'Pessoal'})).toBeChecked();
  await expect(dialog.getByRole('radio',{name:'Curso'})).toHaveCount(0);
  await expect(dialog.getByLabel('Curso e turma')).toHaveCount(0);
  await page.screenshot({path:`student-visual-audit/record-create/${device}-personal.png`,animations:'disabled'});
});
}
