const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-tools-mechanics-audit.html';
const waitMechanics=async page=>{await page.waitForFunction(()=>document.querySelector('script[data-student-mechanics]')&&document.querySelector('link[data-student-mechanics-style]'));await page.waitForTimeout(150);};

test('exposure equivalent follows photographic nominal stops',async({page})=>{
  await page.setViewportSize({width:900,height:720});
  await page.goto(base+'?screen=exposure',{waitUntil:'networkidle'});await waitMechanics(page);
  const result=page.locator('[data-exposure-result]');
  await expect(result).toHaveText('0,5 s');
  const target=page.locator('[data-exposure-f-target]');await target.fill('11');
  await expect(result).toHaveText('2 s');
  await expect(target).toHaveAttribute('type','text');
  await page.screenshot({path:'student-visual-audit/mechanics/exposure-equivalent.png',fullPage:true,animations:'disabled'});
});

test('timer uses elapsed-time agitation cue and stable controls',async({page})=>{
  await page.setViewportSize({width:1100,height:720});
  await page.goto(base+'?screen=timer',{waitUntil:'networkidle'});await waitMechanics(page);
  await expect(page.locator('[data-timer-interval]')).toHaveValue('');
  await page.locator('[data-timer-interval]').fill('0:02');
  await page.locator('[data-timer-start]').click();
  await expect(page.locator('[data-timer-start]')).toBeDisabled();
  await expect(page.locator('.student-timer-state')).toContainText('Próxima agitação');
  await page.waitForTimeout(1150);
  await expect(page.locator('[data-timer-display]')).not.toHaveText('00:05');
  await page.screenshot({path:'student-visual-audit/mechanics/timer-running.png',fullPage:true,animations:'disabled'});
  await page.locator('[data-timer-pause]').click();
  await expect(page.locator('[data-timer-start]')).toContainText('Continuar');
});

test('recipe hides internal provenance and offers inventory handoff',async({page})=>{
  await page.setViewportSize({width:1000,height:800});
  await page.goto(base+'?screen=recipe',{waitUntil:'networkidle'});await waitMechanics(page);
  await expect(page.getByText('Fonte: Pesquisa João Saidler · Químicos - Receitas')).toHaveCount(0);
  await expect(page.getByRole('link',{name:'Guardar preparo no inventário'})).toBeVisible();
  await page.screenshot({path:'student-visual-audit/mechanics/recipe.png',fullPage:true,animations:'disabled'});
});

test('inventory editor exposes essentials and collapses metadata',async({page})=>{
  await page.setViewportSize({width:1180,height:820});
  await page.goto(base+'?screen=inventory&novo=1&tipo=solution&nome=Parodinal%20%C2%B7%20concentrado&quantidade=250&unidade=ml',{waitUntil:'networkidle'});await waitMechanics(page);
  const panel=page.locator('[data-inventory-new-panel]');await expect(panel).toBeVisible();
  await expect(page.locator('input[name="item_kind"][value="solution"]')).toBeChecked();
  await expect(page.locator('input[name="name"]')).toHaveValue('Parodinal · concentrado');
  await expect(page.locator('input[name="quantity"]')).toHaveValue('250');
  await expect(page.getByText('Detalhes do item',{exact:true})).toBeVisible();
  await expect(page.locator('input[name="lot_code"]')).not.toBeVisible();
  await page.screenshot({path:'student-visual-audit/mechanics/inventory-new.png',fullPage:true,animations:'disabled'});
});

for(const screen of ['exposure','timer','recipe','inventory']){
  test(`mechanics phone ${screen}`,async({page})=>{
    await page.setViewportSize({width:390,height:844});
    const extra=screen==='inventory'?'&novo=1':'';
    await page.goto(`${base}?screen=${screen}${extra}`,{waitUntil:'networkidle'});await waitMechanics(page);
    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
    expect(overflow).toBeLessThanOrEqual(1);
    await page.screenshot({path:`student-visual-audit/mechanics/phone-${screen}.png`,fullPage:true,animations:'disabled'});
  });
}
