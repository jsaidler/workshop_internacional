const {test,expect}=require('@playwright/test');
const fs=require('fs');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-notebook.php';
const devices=[['narrow',320,720],['phone',390,844],['desktop',1440,1100]];
const states=['empty','personal','mixed','shared','many','error'];
function name(s,d,rest='start'){return 'student-visual-audit/notebook/v2-'+s+'-'+d+'-'+rest+'.png';}
for(const [device,width,height] of devices)for(const state of states){
  test('notebook v2 actual renderer '+device+' '+state,async({page})=>{
    fs.mkdirSync('student-visual-audit/notebook',{recursive:true});
    const errors=[];page.on('pageerror',e=>errors.push(e.message));
    await page.setViewportSize({width,height});
    const response=await page.goto(base+'?state='+state,{waitUntil:'networkidle'});
    expect(response?.status()).toBe(200);
    const notebook=page.locator('[data-student-notebook]');
    await expect(notebook).toBeVisible();
    await expect(page.getByRole('heading',{name:'Caderno',exact:true})).toBeVisible();
    await expect(page.locator('[data-record-create-dialog]')).toHaveCount(1);
    await expect(page.getByRole('button',{name:/Novo registro/})).toBeVisible();
    const nav=page.locator('nav.student-mobile-nav');
    await expect(nav.locator('a')).toHaveCount(5);
    await expect(nav.locator('a[href="/aluno/caderno.php"]')).toHaveAttribute('aria-current','page');
    if(state==='empty'){
      await expect(page.locator('[data-notebook-empty]')).toBeVisible();
      await expect(page.locator('[data-notebook-records]')).toHaveCount(0);
      await expect(page.getByRole('button',{name:'Criar primeiro registro'})).toBeVisible();
    }else{
      const cards=page.locator('.student-v2-notebook-card');
      await expect(cards).toHaveCount(state==='personal'?1:state==='many'?12:3);
      await expect(cards.first().locator('a.student-process-card-main')).toHaveAttribute('href',/\/aluno\/teste\.php\?id=\d+/);
      await expect(cards.first().locator('.student-record-summary span')).toHaveCount(3);
      await expect(cards.first().locator('details.student-record-menu')).toHaveCount(1);
      await cards.first().locator('details.student-record-menu summary').click();
      const menu=cards.first().locator('.student-record-menu-panel');
      await expect(menu.getByRole('button',{name:'Duplicar registro'})).toBeVisible();
      await expect(menu.getByRole('link',{name:/Comparar/})).toBeVisible();
      await expect(menu.getByRole('link',{name:'Excluir registro'})).toHaveAttribute('href',/\/aluno\/excluir-teste\.php\?id=/);
      if(state==='personal')await expect(menu.getByText('Visibilidade')).toHaveCount(0);
      else await expect(menu.getByLabel('Visibilidade')).toHaveCount(1);
      await cards.first().locator('details.student-record-menu summary').click();
    }
    if(state==='shared'){
      await expect(page.locator('.student-v2-notebook-shared a')).toHaveAttribute('href','/aluno/teste-compartilhado.php?id=44');
    }else await expect(page.locator('.student-v2-notebook-shared')).toHaveCount(0);
    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
    expect(overflow).toBeLessThanOrEqual(1);
    if(width<=820){
      const g=await page.evaluate(()=>{
        const nav=document.querySelector('.student-mobile-nav').getBoundingClientRect();
        const main=document.querySelector('.student-main').getBoundingClientRect();
        return {navTop:nav.top,mainBottom:main.bottom,navBottom:nav.bottom,viewport:innerHeight};
      });
      expect(g.mainBottom).toBeLessThanOrEqual(g.navTop+1);
      expect(Math.abs(g.navBottom-g.viewport)).toBeLessThanOrEqual(1);
    }
    expect(errors).toEqual([]);
    await page.locator('main.student-main').evaluate(el=>{el.scrollTop=0;});
    await page.screenshot({path:name(state,device),animations:'disabled'});
    await page.locator('main.student-main').evaluate(el=>{el.scrollTop=el.scrollHeight;});
    await page.screenshot({path:name(state,device,'end'),animations:'disabled'});
  });
}
test('notebook uses the real shared creation form without saving synthetic data',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(base+'?state=mixed',{waitUntil:'networkidle'});
  await page.locator('.student-v2-notebook-create').click();
  await expect(page.locator('[data-record-create-dialog]')).toBeVisible();
  await expect(page.locator('[data-record-create-dialog] [name="_csrf"]')).toHaveValue('fixture-csrf');
  await expect(page.locator('[data-record-create-dialog] [name="context_scope"]')).toHaveCount(2);
  await page.locator('[data-record-create-dialog] [name="title"]').fill('Teste novo');
  await page.locator('[data-record-create-close]').click();
  await expect(page.locator('[data-record-create-dialog]')).not.toBeVisible();
});
test('Caderno shows validation feedback in the existing create dialog without losing draft',async({page})=>{
  await page.setViewportSize({width:320,height:720});
  await page.goto(base+'?state=error',{waitUntil:'networkidle'});
  const dlg=page.locator('[data-record-create-dialog]');
  await expect(dlg).toHaveAttribute('data-record-create-error','');
  await expect(dlg.locator('[role="alert"]')).toHaveText('Informe um título para continuar.');
});
for(const theme of ['dark','light'])test('notebook v2 '+theme+' theme',async({page})=>{
  fs.mkdirSync('student-visual-audit/notebook',{recursive:true});
  await page.setViewportSize({width:390,height:844});
  await page.goto(base+'?state=mixed',{waitUntil:'networkidle'});
  await page.evaluate(t=>document.documentElement.setAttribute('data-theme',t),theme);
  await page.screenshot({path:'student-visual-audit/notebook/v2-'+theme+'-phone.png',animations:'disabled'});
});
test('notebook keyboard and visual action order puts creation before comparison',async({page})=>{
  await page.setViewportSize({width:320,height:720});
  await page.goto(base+'?state=mixed',{waitUntil:'networkidle'});
  const actions=await page.locator('.student-v2-notebook-header > button').evaluateAll(nodes=>nodes.map(n=>({text:n.textContent.trim(),top:n.getBoundingClientRect().top})));
  expect(actions).toHaveLength(2);
  expect(actions[0].text).toContain('Novo registro');
  expect(actions[1].text).toContain('Comparar registros');
  expect(actions[0].top).toBeLessThan(actions[1].top);
  await page.getByRole('button',{name:'Comparar registros'}).click();
  await expect(page.locator('.student-compare-selection')).toBeVisible();
  await expect(page.locator('.student-compare-pick:not([hidden])')).toHaveCount(3);
  await page.getByRole('button',{name:'Cancelar'}).click();
  await expect(page.locator('.student-compare-selection')).not.toBeVisible();
});
