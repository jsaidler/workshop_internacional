const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-process-execution-state-audit.html';
const states=['idle','running','paused','elapsed'];
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};
for(const [device,viewport] of Object.entries(viewports)){
  for(const state of states){
    test(`process execution visual audit ${device} ${state}`,async({page})=>{
      await page.setViewportSize(viewport);await page.goto(`${base}?state=${state}`,{waitUntil:'networkidle'});
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);expect(overflow,`${state} horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
      await expect(page.locator('.student-lab-stage-nav a')).toHaveCount(9);await expect(page.getByText('Reutilize o banho da primeira revelação')).toBeVisible();
      await expect(page.getByRole('button',{name:'Ir para próxima etapa',exact:true})).toBeEnabled();
      const status=page.locator('[data-runner-state-status]'),start=page.locator('[data-runner-start]'),pause=page.locator('[data-runner-pause]'),reset=page.locator('[data-runner-reset]');
      if(state==='idle'){
        await expect(status).toHaveText('Pronto para iniciar');await expect(start).toBeVisible();await expect(start).toBeEnabled();await expect(pause).toBeHidden();await expect(reset).toBeHidden();
      }else if(state==='running'){
        await expect(status).toHaveText('Cronômetro em andamento');await expect(start).toBeHidden();await expect(pause).toBeVisible();await expect(pause).toBeEnabled();await expect(reset).toBeVisible();await expect(page.locator('[data-runner-agitation]')).toContainText(/AGITAR|Próxima agitação/);
      }else if(state==='paused'){
        await expect(status).toHaveText('Cronômetro pausado');await expect(start).toBeVisible();await expect(start).toHaveText('Retomar');await expect(pause).toBeHidden();await expect(reset).toBeVisible();await expect(page.locator('[data-runner-agitation]')).toHaveText('Agitação pausada');
      }else{
        await expect(status).toHaveText('Tempo concluído');await expect(start).toBeHidden();await expect(pause).toBeHidden();await expect(reset).toBeVisible();await expect(page.locator('[data-runner-agitation]')).toHaveText('Agitação encerrada');
      }
      await page.screenshot({path:`student-visual-audit/${device}/process-execution-${state}.png`,fullPage:true,animations:'disabled'});
    });
  }
  test(`continuous agitation visual audit ${device}`,async({page})=>{
    await page.setViewportSize(viewport);await page.goto(`${base}?state=running&agitation=continuous`,{waitUntil:'networkidle'});
    await expect(page.locator('[data-runner-agitation]')).toHaveText('AGITAÇÃO CONTÍNUA');await expect(page.locator('.student-process-runner-details')).toContainText('Agitação contínua');await expect(page.locator('.student-process-runner-details')).not.toContainText('a cada');
    await page.screenshot({path:`student-visual-audit/${device}/process-execution-continuous.png`,fullPage:true,animations:'disabled'});
  });
  test(`timer settings visual audit ${device}`,async({page})=>{
    await page.setViewportSize(viewport);await page.goto(`${base}?state=idle&settings=open`,{waitUntil:'networkidle'});
    await expect(page.locator('.student-lab-timer-settings')).toHaveAttribute('open','');
    await expect(page.locator('input[name="duration"]')).toHaveValue('07:00');
    await expect(page.locator('select[name="agitation_mode"]')).toHaveValue('periodic');
    await expect(page.locator('input[name="agitation_duration"]')).toHaveValue('00:10');
    await expect(page.locator('input[name="agitation_interval"]')).toHaveValue('01:00');
    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);expect(overflow,'settings horizontal overflow').toBeLessThanOrEqual(1);
    await page.screenshot({path:`student-visual-audit/${device}/process-execution-settings.png`,fullPage:true,animations:'disabled'});
  });
  test(`per-photo route adjustments visual audit ${device}`,async({page})=>{
    await page.setViewportSize(viewport);await page.goto(`${base}?state=idle&route=open`,{waitUntil:'networkidle'});
    await expect(page.locator('.student-lab-route-adjustments')).toHaveAttribute('open','');await expect(page.getByRole('button',{name:'Repetir esta etapa',exact:true})).toBeVisible();await expect(page.getByRole('button',{name:'Remover esta etapa',exact:true})).toBeVisible();
    await expect(page.locator('[data-route-insert]')).toHaveAttribute('open','');const stage=page.locator('[data-lab-stage]');await stage.selectOption('first_development');await expect(page.locator('[data-lab-field="development"]')).toBeVisible();
    const mode=page.locator('[data-lab-agitation-mode]');await mode.selectOption('periodic');await expect(page.locator('[data-lab-field="agitation-periodic"]')).toBeVisible();
    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);expect(overflow,'route adjustments horizontal overflow').toBeLessThanOrEqual(1);
    await page.screenshot({path:`student-visual-audit/${device}/process-execution-route-adjustments.png`,fullPage:true,animations:'disabled'});
  });
}

test('timer remains a tool while progression stays available',async({page})=>{
  await page.setViewportSize({width:390,height:844});await page.goto(`${base}?state=idle`,{waitUntil:'networkidle'});
  await expect(page.getByRole('button',{name:'Ir para próxima etapa',exact:true})).toBeEnabled();await page.getByRole('button',{name:'Iniciar',exact:true}).click();await expect(page.locator('[data-runner-state-status]')).toHaveText('Cronômetro em andamento');
  await expect.poll(async()=>await page.locator('[data-runner-clock]').textContent(),{timeout:2500}).not.toBe('07:00');await expect(page.getByRole('button',{name:'Ir para próxima etapa',exact:true})).toBeEnabled();
  await page.getByRole('button',{name:'Pausar',exact:true}).click();await expect(page.locator('[data-runner-state-status]')).toHaveText('Cronômetro pausado');const paused=await page.locator('[data-runner-clock]').textContent();await page.waitForTimeout(1100);await expect(page.locator('[data-runner-clock]')).toHaveText(paused);
  await page.getByRole('button',{name:'Retomar',exact:true}).click();await expect(page.locator('[data-runner-state-status]')).toHaveText('Cronômetro em andamento');await page.getByRole('button',{name:'Reiniciar',exact:true}).click();await expect(page.locator('[data-runner-state-status]')).toHaveText('Pronto para iniciar');await expect(page.locator('[data-runner-clock]')).toHaveText('07:00');
});
