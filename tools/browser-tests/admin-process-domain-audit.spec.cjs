const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/admin-process-domain-audit.html';
const views=['list','draft','published','stages','developers'];
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};
for(const [device,viewport] of Object.entries(viewports)){
  for(const view of views){
    test(`admin process domain visual audit ${device} ${view}`,async({page})=>{
      await page.setViewportSize(viewport);
      await page.goto(`${base}?view=${view}`,{waitUntil:'networkidle'});
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
      expect(overflow,`${view} horizontal document overflow on ${device}`).toBeLessThanOrEqual(1);
      if(view==='list'){
        await expect(page.getByRole('heading',{name:'Processos globais',level:1})).toBeVisible();
        await expect(page.locator('tbody tr')).toHaveCount(6);
        await expect(page.getByText('Receitas publicadas são versionadas; uma alteração nunca modifica o histórico do aluno.')).toBeVisible();
      }
      if(view==='draft'){
        await expect(page.getByRole('heading',{name:'Editar próxima versão'})).toBeVisible();
        await expect(page.locator('.admin-process-step')).toHaveCount(9);
        await expect(page.getByText('Versão publicada v1')).toBeVisible();
        await expect(page.getByRole('button',{name:'Publicar nova versão'})).toBeVisible();
      }
      if(view==='published'){
        await expect(page.getByRole('heading',{name:'Criar uma nova versão'})).toBeVisible();
        await expect(page.getByRole('heading',{name:'Roteiro publicado'})).toBeVisible();
        await expect(page.getByRole('heading',{name:'Versões'})).toBeVisible();
        await expect(page.locator('.admin-process-history tbody tr')).toHaveCount(3);
      }
      if(view==='stages'){
        await expect(page.getByRole('heading',{name:'Catálogos do laboratório',level:1})).toBeVisible();
        await expect(page.getByText('17 definições')).toBeVisible();
        await expect(page.getByText('stop_after_first')).toBeVisible();
        await expect(page.getByText('Desativado').first()).toBeVisible();
      }
      if(view==='developers'){
        await expect(page.getByText('13 definições')).toBeVisible();
        await expect(page.getByText('Brewed Caffenol',{exact:true}).first()).toBeVisible();
        await expect(page.getByText('fresh',{exact:true}).first()).toBeVisible();
      }
      await page.screenshot({path:`student-visual-audit/${device}/admin-process-${view}.png`,fullPage:true,animations:'disabled'});
    });
  }
}