const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-process-saved-cards.php';
for(const width of [320,390,1440]){
 const device=width===1440?'desktop':width===390?'phone':'narrow';
 test('saved process cards separate operational and maintenance actions '+device,async({page})=>{
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.setViewportSize({width,height:width===320?720:width===390?844:1100});
  const response=await page.goto(base,{waitUntil:'networkidle'});
  expect(response?.status()).toBe(200);
  const cards=page.locator('[data-process-saved-card]');
  await expect(cards).toHaveCount(2);
  const first=cards.first();
  await expect(first.getByRole('link',{name:'Abrir no laboratório'})).toHaveAttribute('href','/aluno/processar.php?template=14');
  await expect(first.locator('summary')).toBeVisible();
  await expect(first.getByRole('button',{name:'Excluir'})).toBeHidden();
  await page.locator('.student-main').evaluate(n=>{n.scrollTop=0;});
  await page.screenshot({path:'student-visual-audit/process-library/'+device+'-initial.png',animations:'disabled'});
  await first.locator('summary').focus();await page.keyboard.press('Enter');
  const options=first.locator('[data-process-card-options]');
  await expect(options).toHaveAttribute('open','');
  await expect(first.getByRole('link',{name:'Editar roteiro'})).toHaveAttribute('href','/aluno/processamentos.php?id=14');
  await expect(first.getByRole('button',{name:'Duplicar'})).toBeVisible();
  await expect(first.getByRole('button',{name:'Excluir'})).toBeVisible();
  await expect(first.locator('form input[name="_csrf"]')).toHaveCount(2);
  const geometry=await page.evaluate(()=>{
   const main=document.querySelector('.student-main');
   const card=document.querySelector('[data-process-saved-card]');
   const r=card.getBoundingClientRect(),m=main.getBoundingClientRect();
   return{overflow:main.scrollWidth-main.clientWidth,left:r.left-main.getBoundingClientRect().left,right:m.right-r.right,
      gutter:getComputedStyle(main).scrollbarGutter,navCount:document.querySelectorAll('.student-mobile-nav a').length,
      mainBottom:m.bottom,navTop:document.querySelector('.student-mobile-nav').getBoundingClientRect().top};
  });
  expect(geometry.overflow,'no horizontal scroll for expanded management').toBeLessThanOrEqual(1);
  expect(geometry.gutter).toContain('stable');
  expect(geometry.right).toBeGreaterThanOrEqual(8);
  expect(geometry.navCount).toBe(5);
  if(width<=820)expect(geometry.mainBottom).toBeLessThanOrEqual(geometry.navTop+1);
  await page.screenshot({path:'student-visual-audit/process-library/'+device+'-options-open.png',animations:'disabled'});
  await first.locator('summary').click();await expect(options).not.toHaveAttribute('open','');
  expect(errors).toEqual([]);
 });
}
test('association route only exposes association, not destructive saved-template management',async({page})=>{
 await page.setViewportSize({width:390,height:844});
 const response=await page.goto(base+'?mode=association',{waitUntil:'networkidle'});
 expect(response?.status()).toBe(200);
 const card=page.locator('[data-process-saved-card]');
 await expect(card).toHaveCount(1);
 await expect(card.getByRole('button',{name:'Associar ao registro'})).toBeVisible();
 await expect(card.locator('[data-process-card-options]')).toHaveCount(0);
 await expect(card.locator('form input[name="action"][value="apply_template"]')).toHaveCount(1);
});
test('empty route refuses lab runner without suppressing edit and duplication',async({page})=>{
 await page.setViewportSize({width:390,height:844});
 await page.goto(base+'?mode=empty',{waitUntil:'networkidle'});
 const card=page.locator('[data-process-saved-card]').last();
 await expect(card.getByText('Adicione uma etapa')).toBeVisible();
 await expect(card.getByRole('link',{name:'Abrir no laboratório'})).toHaveCount(0);
 await card.locator('summary').click();
 await expect(card.getByRole('link',{name:'Editar roteiro'})).toBeVisible();
 await expect(card.getByRole('button',{name:'Duplicar'})).toBeVisible();
});
