const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-record-workspace-operational.php';
const states=['exposure','process-choice','process-plan','process-partial','process-plan-edit','result','result-waiting','result-revision','result-reviewed'];
for(const [device,width,height] of [['narrow',320,720],['phone',390,844],['desktop',1440,1000]]){
 for(const state of states){
  test('operational notebook '+device+' '+state,async({page})=>{
   await page.setViewportSize({width,height});
   const errors=[];page.on('pageerror',e=>errors.push(e.message));
   const response=await page.goto(base+'?state='+state,{waitUntil:'networkidle'});
   expect(response?.status()).toBe(200);
   await expect(page.locator('body.student-app .student-shell')).toHaveCount(1);
   await expect(page.locator('link[href^="/assets/student-app-shell.css"]')).toHaveCount(1);
   await expect(page.locator('.student-mobile-nav a')).toHaveCount(5);
   const urls=await page.locator('.student-mobile-nav a').evaluateAll(nodes=>nodes.map(n=>n.getAttribute('href')));
   expect(urls).toEqual(['/aluno/','/aluno/agenda.php','/aluno/cursos.php','/aluno/caderno.php','/aluno/ferramentas.php']);
   await expect(page.locator('.student-mobile-nav a[aria-current="page"]')).toHaveAttribute('href','/aluno/caderno.php');
   await expect(page.locator('[data-record-workspace-nav] a')).toHaveCount(3);
   await expect(page.locator('[data-record-workspace-panel]')).toHaveCount(3);
   const chosen=state==='exposure'?'exposicao':(state.startsWith('process-')?'processamento':'resultado');
   await expect(page.locator('[data-record-workspace-default]')).toHaveAttribute('data-record-workspace-default',chosen);
   await page.locator('[data-record-workspace-nav] a[href="#'+chosen+'"]').click();
   await expect(page.locator('#'+chosen)).toBeVisible();
   if(state==='exposure'){
    await expect(page.locator('#exposicao form input[name="action"][value="save_exposure"]')).toHaveCount(1);
    await expect(page.locator('#exposicao input[name="film"]')).toHaveValue('Fuji Super HR-U');
    await expect(page.locator('#exposicao [data-student-capture-controls]')).toHaveCount(1);
    await expect(page.locator('#exposicao .student-optional-fields')).not.toHaveAttribute('open','');
   }
   if(['process-plan','process-partial','process-plan-edit'].includes(state)){
    await expect(page.locator('#processamento .student-notebook-route-step')).toHaveCount(9);
    await expect(page.locator('#processamento a[href="/aluno/processar.php?test=7"]')).toBeVisible();
    await expect(page.locator('#processamento a[href="/aluno/registro-roteiro.php?test=7"]')).toBeVisible();
    await expect(page.getByText('O processamento já aconteceu?',{exact:false})).toHaveCount(0);
    await expect(page.getByText('Em andamento',{exact:true})).toHaveCount(0);
    if(state==='process-partial'){
      await expect(page.getByText('5 de 9 marcadas',{exact:true})).toBeVisible();
      await expect(page.getByRole('button',{name:'Desmarcar'})).toHaveCount(5);
      await expect(page.getByRole('button',{name:'Marcar ✓'})).toHaveCount(4);
      await expect(page.locator('#processamento .student-notebook-route-step.is-done:nth-child(2)')).toHaveCount(0);
      await expect(page.locator('#processamento .student-notebook-route-step.is-done:nth-child(3)')).toHaveCount(1);
    }else{
      await expect(page.getByText('0 de 9 marcadas',{exact:true})).toBeVisible();
      await expect(page.getByRole('button',{name:'Desmarcar'})).toHaveCount(0);
    }
    const first=page.locator('#processamento .student-notebook-route-step').first();
    await expect(first.locator('input[name="action"][value="toggle_plan_step"]')).toHaveCount(1);
    await expect(first.getByRole('link',{name:'Editar'})).toHaveAttribute('href','/aluno/teste.php?id=7&edit_plan_step=21#processamento');
    await expect(first.getByRole('link',{name:'Timer'})).toHaveAttribute('href','/aluno/processar.php?test=7&step=21');
    await expect(page.locator('#processamento details.student-notebook-route-add')).not.toHaveAttribute('open','');
    const controls=await first.locator('.student-notebook-route-step-actions .button').evaluateAll(nodes=>nodes.map(el=>{
      const css=getComputedStyle(el),box=el.getBoundingClientRect();
      return{label:el.textContent.trim(),font:parseFloat(css.fontSize),height:box.height,width:box.width,scroll:el.scrollWidth,visible:box.width>0};
    }));
    expect(controls).toHaveLength(3);
    for(const c of controls){
      expect(c.font,state+' '+device+' '+c.label+' readable action').toBeGreaterThanOrEqual(12);
      expect(c.height,state+' '+device+' '+c.label+' touch target').toBeGreaterThanOrEqual(44);
      expect(c.width,state+' '+device+' '+c.label+' usable width').toBeGreaterThanOrEqual(62);
      expect(c.scroll-c.width,state+' '+device+' '+c.label+' no clipped text').toBeLessThanOrEqual(1);
    }
    if(state==='process-plan-edit'){
      const editor=page.locator('#processamento .student-notebook-route-editor');
      await expect(editor).toHaveCount(1);
      await expect(editor.locator('input[name="action"][value="save_plan_step"]')).toHaveCount(1);
      await expect(editor.getByRole('button',{name:'Salvar etapa'})).toBeVisible();
      await expect(editor.locator('form [name="_csrf"]')).not.toHaveCount(0);
      await expect(editor.getByRole('button',{name:'↑ Subir'})).toBeVisible();
      await expect(editor.getByRole('button',{name:'↓ Descer'})).toBeVisible();
    }
   }
   if(state==='process-choice'){
    await expect(page.locator('#processamento a[href="/aluno/registro-roteiro.php?test=7"]')).toBeVisible();
    await expect(page.locator('#processamento details.student-manual-process')).not.toHaveAttribute('open','');
    await expect(page.locator('#processamento [name="action"][value="add_free_step"]')).toHaveCount(1);
    await expect(page.getByText('O processamento já aconteceu?',{exact:false})).toHaveCount(0);
    await expect(page.getByText('Em andamento',{exact:true})).toHaveCount(0);
    await page.locator('#processamento details.student-manual-process summary').click();
    await expect(page.locator('#processamento details.student-manual-process form')).toBeVisible();
    await expect(page.locator('#processamento select[name="stage_key"] option')).not.toHaveCount(0);
   }
   if(state.startsWith('result')){
    await expect(page.locator('#resultado .student-result-form textarea[name="notes"]')).toHaveValue('O positivo apresentou contraste diferente do teste anterior.');
    await expect(page.locator('#resultado details.student-result-context')).not.toHaveAttribute('open','');
    await expect(page.locator('#resultado input[name="action"][value="save_result"]')).toHaveCount(1);
    if(state==='result')await expect(page.locator('#resultado button',{hasText:'Salvar resultado'})).toBeVisible();
    if(state==='result-waiting')await expect(page.locator('#resultado .student-review-state strong')).toHaveText('Enviado para avaliação');
    if(state==='result-revision'){
      await expect(page.locator('#resultado .student-review-state strong')).toHaveText('Revisão solicitada');
      await expect(page.getByRole('button',{name:'Enviar revisão para avaliação'})).toBeVisible();
      await expect(page.locator('#resultado .student-message-list strong')).toHaveText('Professor');
    }
    if(state==='result-reviewed'){
      await expect(page.locator('#resultado .student-review-state strong')).toHaveText('Avaliação concluída');
      await expect(page.locator('#resultado .student-result-form textarea[name="notes"]')).toBeDisabled();
      await expect(page.getByRole('button',{name:'Salvar resultado'})).toHaveCount(0);
      await expect(page.locator('#resultado [data-student-capture-controls]')).toHaveCount(0);
    }
   }
   const geometry=await page.evaluate(()=>{
     const main=document.querySelector('.student-main'),nav=document.querySelector('.student-mobile-nav');
     return{scrollbarGutter:getComputedStyle(main).scrollbarGutter,mainOverflow:main.scrollWidth-main.clientWidth,docOverflow:document.documentElement.scrollWidth-innerWidth,mainBottom:main.getBoundingClientRect().bottom,navTop:nav.getBoundingClientRect().top};
   });
   expect(geometry.scrollbarGutter).toContain('stable');
   expect(geometry.mainOverflow).toBeLessThanOrEqual(1);
   expect(geometry.docOverflow).toBeLessThanOrEqual(1);
   if(width<821)expect(geometry.mainBottom).toBeLessThanOrEqual(geometry.navTop+1);
   await page.screenshot({path:'student-visual-audit/record-operational/'+device+'-'+state+'-start.png',animations:'disabled'});
   if(state.startsWith('process-')||state.startsWith('result')){
      await page.locator('.student-main').evaluate(node=>{node.scrollTop=node.scrollHeight/2;});
      await page.screenshot({path:'student-visual-audit/record-operational/'+device+'-'+state+'-middle.png',animations:'disabled'});
      await page.locator('.student-main').evaluate(node=>{node.scrollTop=node.scrollHeight;});
      await page.screenshot({path:'student-visual-audit/record-operational/'+device+'-'+state+'-end.png',animations:'disabled'});
   }
   expect(errors).toEqual([]);
  });
 }
}
