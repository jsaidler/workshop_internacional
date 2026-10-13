const {test,expect}=require('@playwright/test');
const fs=require('fs');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-laboratory.php';
const viewports=[['narrow',320,720],['phone',390,844],['desktop',1440,1100]];
const states=['full','empty','limited','chemistry','operations'];
const shot=async(page,name)=>{fs.mkdirSync('student-visual-audit/laboratory',{recursive:true});await page.screenshot({path:'student-visual-audit/laboratory/'+name+'.png',animations:'disabled'});};
for(const [device,width,height] of viewports)for(const state of states){
  test('Laboratory released capabilities '+state+' '+device,async({page})=>{
    const errors=[];page.on('pageerror',e=>errors.push(e.message));
    await page.setViewportSize({width,height});
    const response=await page.goto(base+'?state='+state,{waitUntil:'networkidle'});
    expect(response?.status()).toBe(200);
    await expect(page.locator('[data-student-laboratory]')).toBeVisible();
    await expect(page.getByRole('heading',{name:'Laboratório',exact:true})).toBeVisible();
    const nav=page.locator('.student-mobile-nav');
    await expect(nav.locator('a')).toHaveCount(5);
    await expect(nav.locator('a[href="/aluno/ferramentas.php"]')).toHaveAttribute('aria-current','page');
    const links=page.locator('[data-lab-link]');
    const calculators=page.locator('[data-lab-calculator]');
    if(state==='empty'){
      await expect(page.locator('[data-lab-empty]')).toBeVisible();
      await expect(page.locator('[data-lab-operations]')).toHaveCount(0);
      await expect(calculators).toHaveCount(0);
    }else if(state==='limited'){
      await expect(links).toHaveCount(1);
      await expect(links.first()).toHaveAttribute('href','/aluno/preparos.php');
      await expect(calculators).toHaveCount(1);
      await expect(calculators.first()).toHaveAttribute('id','reciprocidade');
      await expect(page.locator('[data-solution-tool]')).toHaveCount(0);
      await expect(page.locator('[data-exposure-tool]')).toHaveCount(0);
    }else if(state==='chemistry'){
      await expect(links).toHaveCount(1);
      await expect(calculators).toHaveCount(1);
      await expect(calculators.first()).toHaveAttribute('id','receitas');
      await expect(page.locator('[data-quick-reciprocity]')).toHaveCount(0);
      await expect(page.locator('[data-exposure-tool]')).toHaveCount(0);
    }else if(state==='operations'){
      await expect(links).toHaveCount(4);
      await expect(calculators).toHaveCount(0);
      await expect(page.locator('[data-lab-calculations]')).toHaveCount(0);
    }else{
      await expect(links).toHaveCount(4);
      await expect(calculators).toHaveCount(3);
      await expect(page.locator('[data-lab-link="processamentos"]')).toHaveAttribute('href','/aluno/processamentos.php');
      await expect(page.locator('[data-lab-link="inventario"]')).toHaveAttribute('href','/aluno/inventario.php');
      await expect(page.locator('[data-lab-link="preparos"]')).toHaveAttribute('href','/aluno/preparos.php');
      await expect(page.locator('[data-lab-link="calibracao"]')).toHaveAttribute('href','/aluno/calibracao.php');
    }
    await expect(page.locator('[data-lab-timer]')).toHaveCount(0);
    for(const el of await calculators.all())await expect(el).not.toHaveAttribute('open','');
    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
    expect(overflow).toBeLessThanOrEqual(1);
    if(width<=820){
      const g=await page.evaluate(()=>{
        const nav=document.querySelector('.student-mobile-nav').getBoundingClientRect();
        const main=document.querySelector('.student-main').getBoundingClientRect();
        return{navTop:nav.top,mainBottom:main.bottom,navBottom:nav.bottom,viewport:innerHeight};
      });
      expect(g.mainBottom).toBeLessThanOrEqual(g.navTop+1);
      expect(Math.abs(g.navBottom-g.viewport)).toBeLessThanOrEqual(2);
    }
    await page.locator('main.student-main').evaluate(el=>el.scrollTop=0);
    await shot(page,state+'-'+device+'-start');
    if(state==='full'||state==='chemistry'){
      const recipe=page.locator('#receitas');
      await recipe.locator('summary').click();
      await expect(recipe.locator('[data-solution-tool]')).toBeVisible();
      await expect(recipe.locator('[data-solution-formula]')).toHaveCount(2);
      await expect(recipe.getByText('Modo de preparo',{exact:true})).toBeVisible();
      await expect(recipe.getByText('Segurança',{exact:true})).toBeVisible();
      const expandedOverflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);
      expect(expandedOverflow).toBeLessThanOrEqual(1);
      await recipe.locator('summary').click();
    }
    await page.locator('main.student-main').evaluate(el=>el.scrollTop=el.scrollHeight);
    await shot(page,state+'-'+device+'-end');
    expect(errors).toEqual([]);
  });
}
test('legacy anchors reveal real calculator and browser Back remains reversible',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(base+'?state=full#reciprocidade',{waitUntil:'networkidle'});
  await expect(page.locator('#reciprocidade')).toHaveAttribute('open','');
  await expect(page.locator('#exposicao')).not.toHaveAttribute('open','');
  await page.evaluate(()=>location.hash='#exposicao');
  await expect(page.locator('#exposicao')).toHaveAttribute('open','');
  await page.goBack();
  await expect(page).toHaveURL(/#reciprocidade$/);
  await expect(page.locator('#reciprocidade')).toHaveAttribute('open','');
});
test('exposure calculator remains bound to original mechanics inside disclosure',async({page})=>{
  await page.goto(base+'?state=full',{waitUntil:'networkidle'});
  await page.locator('#exposicao summary').click();
  const root=page.locator('#exposicao [data-exposure-tool]');
  await expect(root).toBeVisible();
  await expect(root).toHaveAttribute('data-mechanics-exposure','1',{timeout:10000});
  await root.locator('[data-exposure-f-target]').fill('11');
  await root.locator('[data-exposure-f-target]').dispatchEvent('input');
  await expect(root.locator('[data-exposure-result]')).not.toHaveText('1 s');
});
test('recipe quantity is recomputed by original mechanics and not static decoration',async({page})=>{
  await page.goto(base+'?state=chemistry',{waitUntil:'networkidle'});
  await page.locator('#receitas summary').click();
  const root=page.locator('#receitas [data-solution-tool]');
  await root.locator('[data-solution-select]').selectOption('parodinal');
  const amount=root.locator('[data-solution-formula="parodinal"] [data-solution-amount="10"]').first();
  await expect(amount).toBeVisible();
  await root.locator('[data-solution-volume]').fill('500');
  await root.locator('[data-solution-volume]').dispatchEvent('input');
  await expect(amount).not.toHaveText('10 ml');
});
for(const theme of ['dark','light'])test('laboratory preserves '+theme+' theme for expanded calculators',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto(base+'?state=full',{waitUntil:'networkidle'});
  await page.evaluate(t=>document.documentElement.setAttribute('data-theme',t),theme);
  await page.locator('#receitas summary').click();
  await shot(page,'full-phone-'+theme+'-recipe');
});
