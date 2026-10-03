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
      const status=page.locator('[data-runner-state-status]');
      if(state==='idle'){await expect(status).toHaveText('Pronto para iniciar');await expect(page.getByRole('button',{name:'Iniciar',exact:true})).toBeEnabled();}
      else if(state==='running'){await expect(status).toHaveText('Cronômetro em andamento');await expect(page.getByRole('button',{name:'Pausar',exact:true})).toBeEnabled();await expect(page.locator('[data-runner-agitation]')).toContainText(/AGITAR|Próxima agitação/);}
      else if(state==='paused'){await expect(status).toHaveText('Cronômetro pausado');await expect(page.getByRole('button',{name:'Retomar',exact:true})).toBeEnabled();await expect(page.locator('[data-runner-agitation]')).toHaveText('Agitação pausada');}
      else{await expect(status).toHaveText('Tempo concluído');await expect(page.getByRole('button',{name:'Iniciar',exact:true})).toBeDisabled();await expect(page.locator('[data-runner-agitation]')).toHaveText('Agitação encerrada');}
      await page.screenshot({path:`student-visual-audit/${device}/process-execution-${state}.png`,fullPage:true,animations:'disabled'});
    });
  }
  test(`continuous agitation visual audit ${device}`,async({page})=>{
    await page.setViewportSize(viewport);await page.goto(`${base}?state=running&agitation=continuous`,{waitUntil:'networkidle'});await expect(page.locator('[data-runner-agitation]')).toHaveText('AGITAÇÃO CONTÍNUA');await page.screenshot({path:`student-visual-audit/${device}/process-execution-continuous.png`,fullPage:true,animations:'disabled'});
  });
}

test('timer remains a tool while progression stays available',async({page})=>{
  await page.setViewportSize({width:390,height:844});await page.goto(`${base}?state=idle`,{waitUntil:'networkidle'});
  await expect(page.getByRole('button',{name:'Ir para próxima etapa',exact:true})).toBeEnabled();await page.getByRole('button',{name:'Iniciar',exact:true}).click();await expect(page.locator('[data-runner-state-status]')).toHaveText('Cronômetro em andamento');
  await expect.poll(async()=>await page.locator('[data-runner-clock]').textContent(),{timeout:2500}).not.toBe('07:00');await expect(page.getByRole('button',{name:'Ir para próxima etapa',exact:true})).toBeEnabled();
  await page.getByRole('button',{name:'Pausar',exact:true}).click();await expect(page.locator('[data-runner-state-status]')).toHaveText('Cronômetro pausado');const paused=await page.locator('[data-runner-clock]').textContent();await page.waitForTimeout(1100);await expect(page.locator('[data-runner-clock]')).toHaveText(paused);
  await page.getByRole('button',{name:'Retomar',exact:true}).click();await expect(page.locator('[data-runner-state-status]')).toHaveText('Cronômetro em andamento');await page.getByRole('button',{name:'Reiniciar',exact:true}).click();await expect(page.locator('[data-runner-state-status]')).toHaveText('Pronto para iniciar');await expect(page.locator('[data-runner-clock]')).toHaveText('07:00');
});
