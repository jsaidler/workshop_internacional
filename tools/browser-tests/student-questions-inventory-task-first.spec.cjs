const {test,expect}=require('@playwright/test');
const host='http://127.0.0.1:8099/tools/browser-fixture/';
for(const [device,width,height] of [['narrow',320,720],['phone',390,844],['desktop',1440,1100]]){
 test('Dúvidas mostra conversa antes de masthead administrativo e mantém contexto — '+device,async({page})=>{
  await page.setViewportSize({width,height});
  const res=await page.goto(host+'student-questions-operational.php?state=list',{waitUntil:'networkidle'});
  expect(res?.status()).toBe(200);
  const context=page.locator('.student-course-context-compact');
  await expect(context).toBeVisible();
  await expect(context.locator('h1')).toHaveCount(0);
  await expect(context.locator('strong.student-course-context-title')).toContainText('Positivo direto');
  await expect(context.locator('.student-course-context-back')).toHaveAttribute('href','/aluno/cursos.php?cohort=cohort-a');
  await expect(context.locator('nav a[aria-current=page]')).toHaveText('Dúvidas');
  const area=page.locator('[data-student-questions]');
  await expect(area.getByRole('heading',{name:'Dúvidas',exact:true,level:1})).toBeVisible();
  await expect(area.getByRole('button',{name:'Nova dúvida'})).toBeVisible();
  await expect(area.locator('.student-test-list a').first()).toBeVisible();
  const geometry=await page.evaluate(()=>{
   const main=document.querySelector('main.student-main');
   const first=document.querySelector('[data-student-questions] .student-test-list a');
   const h=document.querySelector('[data-student-questions] h1');
   const bar=document.querySelector('.student-mobile-nav');
   const m=main.getBoundingClientRect(),r=first.getBoundingClientRect(),t=h.getBoundingClientRect();
   return {titleTop:t.top,firstTop:r.top,viewportBottom:m.bottom,
      over:document.documentElement.scrollWidth-document.documentElement.clientWidth,
      navCount:bar?.querySelectorAll('a').length||0};
  });
  expect(geometry.over).toBeLessThanOrEqual(1);
  expect(geometry.navCount).toBe(5);
  expect(geometry.titleTop).toBeLessThan(geometry.firstTop);
  if(width<=820)expect(geometry.firstTop).toBeLessThan(geometry.viewportBottom);
 });
 test('Inventário prioriza o saldo e sua ação; administração fica progressiva — '+device,async({page})=>{
  await page.setViewportSize({width,height});
  const res=await page.goto(host+'student-inventory-movement.php?mode=inventory',{waitUntil:'networkidle'});
  expect(res?.status()).toBe(200);
  const item=page.locator('.student-inventory-item').first(),metrics=page.locator('[data-inventory-metrics]');
  await expect(item).toBeVisible();
  await expect(item.getByRole('button',{name:'Movimentar estoque'})).toBeVisible();
  await expect(metrics).toHaveCount(1);
  await expect(metrics).not.toHaveAttribute('open','');
  await expect(item.locator('.student-inventory-options')).toHaveCount(1);
  await expect(item.getByRole('button',{name:'Arquivar'})).toBeHidden();
  await expect(item.getByRole('link',{name:'Editar item'})).toBeHidden();
  const g=await page.evaluate(()=>{
    const rect=s=>document.querySelector(s).getBoundingClientRect();
    return {item:rect('.student-inventory-item').top,metrics:rect('[data-inventory-metrics]').top,
      over:document.documentElement.scrollWidth-document.documentElement.clientWidth,
      metricsTopBorder:getComputedStyle(document.querySelector('[data-inventory-metrics]')).borderTopWidth,
      itemKindBorder:getComputedStyle(document.querySelector('.student-inventory-kind')).borderTopWidth,
      unitFontSize:parseFloat(getComputedStyle(document.querySelector('.student-inventory-balance span')).fontSize),
      edges:[...document.querySelectorAll('.student-inventory-item')].map(el=>{
       const css=getComputedStyle(el);
       return [css.borderTopWidth,css.borderRightWidth,css.borderBottomWidth,css.borderLeftWidth]
      })};
  });
  expect(g.item).toBeLessThan(g.metrics);
  expect(g.over).toBeLessThanOrEqual(1);
  expect(g.edges[0].slice(0,3)).toEqual(['0px','0px','1px']);
  expect(g.metricsTopBorder,'adjacent item and metrics cannot draw two dividers').toBe('0px');
  expect(g.itemKindBorder,'stock category is metadata, not a nested badge').toBe('0px');
  expect(g.unitFontSize,'mobile quantity units must be legible').toBeGreaterThanOrEqual(12);
  await item.locator('.student-inventory-options > summary').click();
  await expect(item.getByRole('link',{name:'Editar item'})).toHaveAttribute('href',/inventario-item.php\?id=/);
  await expect(item.getByRole('button',{name:'Arquivar'})).toBeVisible();
  await metrics.locator('summary').click();
  await expect(metrics.locator('.student-stock-summary article')).toHaveCount(3);
 });
}
test('Dúvida individual conserva retorno da turma e título único',async({page})=>{
 await page.goto(host+'student-questions-operational.php?state=thread&id=1',{waitUntil:'networkidle'});
 await expect(page.locator('.student-course-context-compact')).toBeVisible();
 await expect(page.locator('[data-student-questions] h1')).toHaveCount(1);
 await expect(page.locator('.student-back')).toHaveAttribute('href','/aluno/duvidas.php?cohort=cohort-a');
 await expect(page.locator('nav.student-mobile-nav a')).toHaveCount(5);
});
