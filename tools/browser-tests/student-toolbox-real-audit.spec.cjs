const {test,expect}=require('@playwright/test');
const url='http://127.0.0.1:8099/tools/browser-fixture/student-toolbox-real-audit.php';
for(const [device,width,height] of [['phone-compact',320,720],['phone',390,844],['desktop',1440,1100]]){
  test(`real toolbox has quick links, progressive calculators, focus and safe scrolling — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});await page.goto(url,{waitUntil:'networkidle'});
    const trigger=page.getByRole('button',{name:'Atalhos'});
    const dialog=page.locator('dialog[data-student-toolbox]');
    await expect(dialog).not.toBeVisible();
    await expect(trigger,'toolbox must be operable directly from the mobile header').toBeVisible();
    const header=await page.evaluate(()=>{const brand=document.querySelector('.student-wordmark').getBoundingClientRect(),tools=document.querySelector('[data-toolbox-open]').getBoundingClientRect(),account=document.querySelector('.student-user-menu>summary').getBoundingClientRect();return{brandRight:brand.right,toolsLeft:tools.left,toolsRight:tools.right,accountLeft:account.left,accountRight:account.right,viewport:innerWidth,scrollWidth:document.documentElement.scrollWidth};});
    expect(header.toolsLeft,'Atalhos must not cover the brand').toBeGreaterThanOrEqual(header.brandRight-1);
    expect(header.toolsRight,'Atalhos must not overlap Conta').toBeLessThanOrEqual(header.accountLeft+1);
    expect(header.accountRight,'Conta must fit in viewport').toBeLessThanOrEqual(header.viewport+1);
    expect(header.scrollWidth,'Atalhos must not create horizontal overflow').toBeLessThanOrEqual(header.viewport+1);
    await page.screenshot({path:`student-visual-audit/toolbox-real/${device}-closed.png`,animations:'disabled'});
    await trigger.click();
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole('heading',{name:'Ferramentas'})).toBeVisible();
    await expect(dialog.locator('details.student-toolbox-tool')).toHaveCount(2);
    await expect(dialog.locator('details[open]')).toHaveCount(0);
    await expect(dialog.getByRole('link',{name:/Processamentos/})).toHaveCount(1);
    await expect(dialog.getByRole('link',{name:/Inventário/})).toHaveCount(1);
    await expect(dialog.getByText('Temporizador',{exact:true})).toHaveCount(0);
    const summary=dialog.locator('details.student-toolbox-tool summary');
    await expect(summary.first()).toHaveText('Reciprocidade');
    await expect(summary.last()).toHaveText('Exposição equivalente');
    const touch=await summary.first().evaluate(el=>el.getBoundingClientRect().height);
    expect(touch).toBeGreaterThanOrEqual(48);
    if(device!=='desktop'){
      const box=await dialog.evaluate(el=>({bottom:el.getBoundingClientRect().bottom,height:el.getBoundingClientRect().height,viewport:innerHeight}));
      expect(Math.abs(box.bottom-box.viewport)).toBeLessThanOrEqual(2);
      expect(box.height).toBeLessThanOrEqual(box.viewport);
      await expect(page.locator('.student-mobile-nav a')).toHaveCount(4);
    }
    await page.screenshot({path:`student-visual-audit/toolbox-real/${device}-menu.png`,animations:'disabled'});
    await summary.first().click();
    await expect(dialog.locator('details').first()).toHaveAttribute('open','');
    await dialog.locator('[data-quick-reciprocity-source]').fill('4 s');
    await expect.poll(async()=>dialog.locator('[data-quick-reciprocity-target]').inputValue()).not.toBe('');
    await page.screenshot({path:`student-visual-audit/toolbox-real/${device}-reciprocity.png`,animations:'disabled'});
    await summary.last().click();
    await expect(dialog.locator('details').last()).toHaveAttribute('open','');
    await expect(dialog.locator('details').first()).not.toHaveAttribute('open');
    await dialog.locator('[data-exposure-time]').fill('2');
    await expect(dialog.locator('[data-exposure-result]')).toBeVisible();
    await page.screenshot({path:`student-visual-audit/toolbox-real/${device}-exposure.png`,animations:'disabled'});
    await dialog.evaluate(el=>{el.scrollTop=el.scrollHeight;});
    await expect(dialog.getByRole('link',{name:/Ver bancada completa/})).toBeVisible();
    await page.screenshot({path:`student-visual-audit/toolbox-real/${device}-end.png`,animations:'disabled'});
    await page.keyboard.press('Escape');
    await expect(dialog).not.toBeVisible();
    await expect(trigger).toBeFocused();
    await trigger.click();
    await expect(dialog).toBeVisible();
    await dialog.getByRole('button',{name:'Fechar'}).click();
    await expect(dialog).not.toBeVisible();
    await expect(trigger).toBeFocused();
  });
  test(`restricted toolbox omits unauthorized actions — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});await page.goto(url+'?mode=restricted',{waitUntil:'networkidle'});
    await page.getByRole('button',{name:'Atalhos'}).click();
    const dialog=page.locator('dialog[data-student-toolbox]');
    await expect(dialog.locator('[data-quick-reciprocity]')).toHaveCount(1);
    await expect(dialog.locator('[data-exposure-tool]')).toHaveCount(0);
    await expect(dialog.getByRole('link',{name:/Processamentos/})).toHaveCount(0);
    await expect(dialog.getByRole('link',{name:/Receitas e preparo/})).toHaveCount(0);
    await page.screenshot({path:`student-visual-audit/toolbox-real/${device}-restricted.png`,animations:'disabled'});
  });
}