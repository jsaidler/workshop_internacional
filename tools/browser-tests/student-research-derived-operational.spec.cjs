const {test,expect}=require('@playwright/test');
const fixture='http://127.0.0.1:8099/tools/browser-fixture/student-notebook.php';
const destinations=['/aluno/','/aluno/agenda.php','/aluno/cursos.php','/aluno/caderno.php','/aluno/ferramentas.php'];
for(const [viewport,width,height] of [['narrow',320,720],['phone',390,844],['desktop',1440,1000]]){
 for(const state of ['derived','derived-no-source','derived-no-intent']){
  test('operational research lineage '+viewport+' '+state,async({page})=>{
   await page.setViewportSize({width,height});
   const errors=[];page.on('pageerror',e=>errors.push(e.message));
   const response=await page.goto(fixture+'?state='+state,{waitUntil:'networkidle'});
   expect(response?.status()).toBe(200);
   await expect(page.locator('body.student-app .student-shell')).toHaveCount(1);
   await expect(page.locator('link[href="/assets/student-area.css"]')).toHaveCount(1);
   const nav=await page.locator('nav.student-mobile-nav a').evaluateAll(nodes=>nodes.map(n=>n.getAttribute('href')));
   expect(nav).toEqual(destinations);
   await expect(page.locator('nav.student-mobile-nav a[href="/aluno/caderno.php"]')).toHaveAttribute('aria-current','page');
   const derived=page.locator('[data-record-id="14"]');
   await expect(derived).toHaveCount(1);
   await expect(derived.locator('h3')).toHaveText('Variação de EI 400 — FeCl₃');
   await expect(derived.locator('.student-process-card-main')).toHaveAttribute('href','/aluno/teste.php?id=14');
   await expect(page.locator('.student-process-card')).toHaveCount(2);
   // Contextual menu is a disclosure, not a permanent multi-record selection.
   await expect(page.locator('.student-process-compare,.student-process-select input[type=checkbox]')).toHaveCount(0);
   const menu=derived.locator('details.student-record-menu');
   await expect(menu).not.toHaveAttribute('open','');
   const compareOrigin=menu.getByRole('link',{name:'Comparar com origem'});
   const compareAnother=menu.getByRole('link',{name:'Comparar com outro registro'});
   if(state==='derived-no-source'){
     await expect(derived.locator('.student-research-card-lineage')).toHaveCount(0);
     await expect(compareOrigin).toHaveCount(0);
     await menu.locator('summary').click();
     await expect(compareAnother).toHaveAttribute('href','/aluno/comparar-processos.php?id%5B%5D=14');
   }else{
     await expect(derived.locator('.student-research-card-lineage')).toContainText('Continuação de EI 400 — FeCl₃');
     if(state==='derived'){
       await expect(derived.locator('.student-research-card-lineage')).toContainText('Manter EI 400 e voltar a primeira revelação para 7 minutos.');
     }else{
       await expect(derived.locator('.student-research-card-lineage')).not.toContainText('Manter EI 400');
     }
     await expect(compareOrigin).toBeHidden();
     await menu.locator('summary').click();
     await expect(compareOrigin).toHaveAttribute('href','/aluno/comparar-processos.php?id%5B%5D=12&id%5B%5D=14');
     await expect(compareOrigin).toBeVisible();
   }
   await menu.locator('summary').click();
   await expect(menu).not.toHaveAttribute('open','');
   await expect(derived.getByText('Processamento ainda não registrado')).toBeVisible();
   await expect(derived.getByText('Resultado ainda não registrado')).toBeVisible();
   const geometry=await page.evaluate(()=>{
     const main=document.querySelector('.student-main'),bottom=document.querySelector('.student-mobile-nav');
     return{docOverflow:document.documentElement.scrollWidth-innerWidth,
       mainOverflow:main.scrollWidth-main.clientWidth,scrollGutter:getComputedStyle(main).scrollbarGutter,
       mainBottom:main.getBoundingClientRect().bottom,bottomTop:bottom.getBoundingClientRect().top};
   });
   expect(geometry.docOverflow).toBeLessThanOrEqual(1);
   expect(geometry.mainOverflow).toBeLessThanOrEqual(1);
   expect(geometry.scrollGutter).toContain('stable');
   if(width<821)expect(geometry.mainBottom).toBeLessThanOrEqual(geometry.bottomTop+1);
   await page.screenshot({path:'student-visual-audit/lineage/'+viewport+'-'+state+'.png',animations:'disabled'});
   expect(errors).toEqual([]);
  });
 }
}
