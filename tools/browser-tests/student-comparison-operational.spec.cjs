const {test,expect}=require('@playwright/test');
const fixture='http://127.0.0.1:8099/tools/browser-fixture/student-comparison-operational.php';
const canonical=['/aluno/','/aluno/agenda.php','/aluno/cursos.php','/aluno/caderno.php','/aluno/ferramentas.php'];
for(const [label,width,height] of [['narrow',320,720],['phone',390,844],['desktop',1440,1000]]){
 for(const mode of ['select','analysis','no-difference','error','insufficient']){
  test('operational comparison '+label+' '+mode,async({page})=>{
   await page.setViewportSize({width,height});
   const errors=[];page.on('pageerror',err=>errors.push(err.message));
   const response=await page.goto(fixture+'?mode='+mode,{waitUntil:'networkidle'});
   expect(response?.status()).toBe(200);
   await expect(page.locator('body.student-app .student-shell')).toHaveCount(1);
   await expect(page.locator('link[href^="/assets/student-app-shell.css"]')).toHaveCount(1);
   const links=await page.locator('.student-mobile-nav a').evaluateAll(nodes=>nodes.map(n=>n.getAttribute('href')));
   expect(links).toEqual(canonical);
   await expect(page.locator('.student-mobile-nav a[aria-current=page]')).toHaveAttribute('href','/aluno/caderno.php');
   await expect(page.locator('a.student-back')).toHaveAttribute('href','/aluno/caderno.php');
   if(mode==='select'){
    await expect(page.getByRole('heading',{name:'Escolha o segundo registro'})).toBeVisible();
    await expect(page.locator('.student-research-picker-form input[name="id[]"][value="10"]')).toHaveCount(1);
    await expect(page.locator('.student-research-picker-form select[name="id[]"] option[value="11"]')).toHaveCount(1);
    await expect(page.getByRole('button',{name:'Comparar registros'})).toBeVisible();
   }else if(mode==='insufficient'){
    await expect(page.getByText('São necessários dois registros.')).toBeVisible();
    await expect(page.locator('.student-research-picker-form')).toHaveCount(0);
   }else{
    await expect(page.getByRole('heading',{name:'Diferenças registradas'})).toBeVisible();
    await expect(page.getByRole('heading',{name:'Leia os resultados lado a lado'})).toBeVisible();
    await expect(page.getByRole('heading',{name:'Conferir exposição e processamento'})).toBeVisible();
    await expect(page.locator('.student-research-result-card')).toHaveCount(2);
    const next=page.locator('form.student-research-next-form');
    await expect(next.locator('input[name="compare_id[]"]')).toHaveCount(2);
    await expect(next.locator('input[name="_csrf"]')).toHaveCount(1);
    await expect(next.locator('input[name="action"][value="fork"]')).toHaveCount(1);
    await expect(page.getByRole('button',{name:'Criar próxima variação'})).toBeVisible();
    if(mode==='no-difference'){
      await expect(page.getByText('Nenhuma diferença técnica detectada.')).toBeVisible();
      await expect(page.locator('.student-research-difference-row')).toHaveCount(0);
    }else{
      await expect(page.locator('.student-research-difference-row').first()).toBeVisible();
      await expect(page.getByText('Etapa 1 · Tempo',{exact:true})).toBeVisible();
    }
    if(mode==='error'){
      await expect(page.locator('[role=alert]')).toContainText('Não foi possível criar variação');
      await expect(next.locator('textarea[name="research_intent"]')).toHaveValue('Testar EI <400> sem alterar o tempo.');
      await expect(next.locator('input[name="source_test_id"][value="10"]')).toBeChecked();
    }else{
      await expect(next.locator('textarea[name="research_intent"]')).toHaveValue('');
      await expect(next.locator('input[name="source_test_id"]:checked')).toHaveCount(0);
    }
   }
   const geometry=await page.evaluate(()=>{
    const main=document.querySelector('.student-main'),nav=document.querySelector('.student-mobile-nav');
    return{docOverflow:document.documentElement.scrollWidth-innerWidth,mainOverflow:main.scrollWidth-main.clientWidth,
      gutter:getComputedStyle(main).scrollbarGutter,mainBottom:main.getBoundingClientRect().bottom,navTop:nav.getBoundingClientRect().top};
   });
   expect(geometry.docOverflow).toBeLessThanOrEqual(1);
   expect(geometry.mainOverflow).toBeLessThanOrEqual(1);
   expect(geometry.gutter).toContain('stable');
   if(width<821)expect(geometry.mainBottom).toBeLessThanOrEqual(geometry.navTop+1);
   await page.screenshot({path:'student-visual-audit/comparison/'+label+'-'+mode+'-start.png',animations:'disabled'});
   if(mode==='analysis'){
     const panel=page.locator('.student-main');
     await panel.evaluate(el=>{el.scrollTop=el.scrollHeight/2;});
     await page.screenshot({path:'student-visual-audit/comparison/'+label+'-analysis-middle.png',animations:'disabled'});
     await page.getByRole('button',{name:'Criar próxima variação'}).scrollIntoViewIfNeeded();
     await page.screenshot({path:'student-visual-audit/comparison/'+label+'-analysis-end.png',animations:'disabled'});
   }
   expect(errors).toEqual([]);
  });
 }
}
