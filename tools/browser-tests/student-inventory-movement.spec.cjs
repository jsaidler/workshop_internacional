const {test,expect}=require('@playwright/test');
const fixture='http://127.0.0.1:8099/tools/browser-fixture/student-inventory-movement.php';
for(const [device,width,height] of [['phone',390,844],['desktop',1440,1100],['compact',320,720]]){
 test('inventory real movement dialog, draft, error and native POST — '+device,async({page})=>{
   const errors=[];page.on('pageerror',e=>errors.push(e.message));
   await page.setViewportSize({width,height});
   await page.goto(fixture,{waitUntil:'networkidle'});
   const trigger=page.getByRole('button',{name:'Movimentar estoque'});
   const dialog=page.locator('[data-inventory-move-dialog]');
   await expect(trigger).toBeVisible();await expect(dialog).toBeHidden();
   const bar=page.locator('.student-mobile-nav a');
   await trigger.click();
   await expect(dialog).toBeVisible();
   await expect(dialog).toHaveJSProperty('open',true);
   await expect(dialog.getByText('Saldo disponível: 180 ml')).toBeVisible();
   await expect(dialog.locator('[name="amount"]')).toBeFocused();
   const headerClose=dialog.getByRole('button',{name:'Fechar'});
   const closeGeometry=await headerClose.boundingBox();
   expect(closeGeometry.width,'header close must remain a compact contextual action').toBeLessThan(130);
   if(width>=390&&device==='phone'){
     // A two-line title is acceptable at 390 px. The regression was a full-width
     // Fechar control that collided with the heading, not the number of lines.
     const head=await dialog.locator('.student-inventory-move-head').evaluate(node=>{
       const a=node.getBoundingClientRect(),title=node.querySelector('h2').getBoundingClientRect();
       const close=node.querySelector('[data-inventory-move-close]').getBoundingClientRect();
       return{height:a.height,titleRight:title.right,closeLeft:close.left,closeRight:close.right,headRight:a.right};
     });
     expect(head.titleRight,'dialog title collides with Fechar').toBeLessThanOrEqual(head.closeLeft+1);
     expect(head.closeRight,'close control escapes the dialog header').toBeLessThanOrEqual(head.headRight+1);
     expect(head.height,'unexpectedly tall dialog header').toBeLessThan(150);
   }

   if(device!=='desktop'){
     await expect(bar).toHaveCount(5);
     await expect(page.locator('.student-mobile-nav a[href="/aluno/agenda.php"]')).toHaveCount(1);
     await expect(page.locator('.student-mobile-nav a[href="/aluno/ferramentas.php"]')).toHaveAttribute('aria-current','page');
     const geometry=await dialog.evaluate(node=>{
       const d=node.getBoundingClientRect(),f=node.querySelector('form').getBoundingClientRect();
       return {left:d.left,right:d.right,bottom:d.bottom,viewport:innerHeight,scrollWidth:node.scrollWidth,clientWidth:node.clientWidth,formBottom:f.bottom};
     });
     expect(geometry.bottom).toBeLessThanOrEqual(geometry.viewport+2);
     expect(geometry.right).toBeLessThanOrEqual(width+2);
     expect(geometry.left).toBeGreaterThanOrEqual(-1);
     expect(geometry.scrollWidth).toBeLessThanOrEqual(geometry.clientWidth+1);
   }
   await page.screenshot({path:`student-visual-audit/inventory-movement/${device}-open.png`,animations:'disabled'});
   await page.keyboard.press('Escape');await expect(dialog).toBeHidden();
   await expect(trigger).toBeFocused();
   await trigger.click();
   await dialog.locator('[name="direction"]').selectOption('adjust_down');
   await expect(dialog.locator('[name="note"]')).toHaveAttribute('required','');
   await dialog.locator('[name="amount"]').fill('25');
   await dialog.locator('[name="note"]').fill('Recontagem controlada');
   await page.screenshot({path:`student-visual-audit/inventory-movement/${device}-correction.png`,animations:'disabled'});
   await dialog.getByRole('button',{name:'Fechar'}).click();
   await expect(trigger).toBeFocused();
   await trigger.click();
   await expect(dialog.locator('[name="amount"]')).toHaveValue('25');
   await dialog.getByRole('button',{name:'Cancelar'}).click();
   await expect(trigger).toBeFocused();
   await page.goto(fixture+'?mode=server-error',{waitUntil:'networkidle'});
   const failing=page.locator('[data-inventory-move-dialog]');
   await page.getByRole('button',{name:'Movimentar estoque'}).click();
   const originalUuid=await failing.locator('[name="movement_uuid"]').inputValue();
   await failing.locator('[name="amount"]').fill('900');
   await failing.locator('[name="note"]').fill('Rascunho persistente');
   await failing.getByRole('button',{name:'Registrar movimentação'}).click();
   await expect(page.locator('[data-inventory-move-error-focus]')).toBeVisible();
   await expect(page.locator('[data-inventory-move-error-focus]')).toBeFocused();
   await expect(page.locator('[name="amount"]')).toHaveValue('900');
   await expect(page.locator('[name="note"]')).toHaveValue('Rascunho persistente');
   await expect(page.locator('[name="movement_uuid"]')).toHaveValue(originalUuid);
   await page.screenshot({path:`student-visual-audit/inventory-movement/${device}-server-error.png`,animations:'disabled'});
   await page.goto(fixture,{waitUntil:'networkidle'});
   await page.getByRole('button',{name:'Movimentar estoque'}).click();
   await page.locator('[name="amount"]').fill('200');
   await page.getByRole('button',{name:'Registrar movimentação'}).click();
   await expect(page.getByRole('alert')).toContainText('não é suficiente');
   await expect(page.locator('[name="amount"]')).toHaveValue('200');
   await page.screenshot({path:`student-visual-audit/inventory-movement/${device}-insufficient.png`,animations:'disabled'});
   await page.goto(fixture,{waitUntil:'networkidle'});
   await page.getByRole('button',{name:'Movimentar estoque'}).click();
   await page.locator('[name="amount"]').fill('10');
   await page.getByRole('button',{name:'Registrar movimentação'}).click();
   await expect(page).toHaveURL(/mode=success/);
   await expect(page.getByRole('status')).toHaveText('Movimentação registrada.');
   await page.screenshot({path:`student-visual-audit/inventory-movement/${device}-success.png`,animations:'disabled'});
   expect(errors).toEqual([]);
 });
}
test('inventory short sheet keeps last action scrollable with reduced mobile height',async({page})=>{
 await page.setViewportSize({width:320,height:500});
 await page.goto(fixture,{waitUntil:'networkidle'});
 await page.getByRole('button',{name:'Movimentar estoque'}).click();
 const dialog=page.locator('[data-inventory-move-dialog]');
 const save=dialog.getByRole('button',{name:'Registrar movimentação'});
 await save.scrollIntoViewIfNeeded();
 await expect(save).toBeInViewport();
 const box=await dialog.boundingBox();
 expect(box.y+box.height).toBeLessThanOrEqual(501);
 await page.screenshot({path:'student-visual-audit/inventory-movement/compact-reduced-height.png',animations:'disabled'});
});

test('inventory mobile prioritizes physical items over secondary presets',async({page})=>{
 await page.setViewportSize({width:390,height:844});
 await page.goto(fixture+'?mode=inventory',{waitUntil:'networkidle'});
 const item=page.locator('.student-inventory-item');
 const summary=page.locator('.student-stock-summary');
 await expect(item).toBeVisible();
 await expect(summary.locator('article')).toHaveCount(3);
 const b=await item.boundingBox(),link=await page.locator('.student-lab-crosslink').boundingBox();
 expect(b.y,'first stock entry should be discoverable in the first mobile screen').toBeLessThan(830);
 expect(link.y,'secondary presets must follow physical inventory').toBeGreaterThan(b.y);
 const height=await summary.evaluate(node=>node.getBoundingClientRect().height);
 expect(height,'three stacked stat cards bury physical inventory').toBeLessThan(145);
 await page.screenshot({path:'student-visual-audit/inventory-movement/phone-prioritized-stock.png',animations:'disabled'});
});
