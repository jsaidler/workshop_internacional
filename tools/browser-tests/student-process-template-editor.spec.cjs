const {test,expect}=require('@playwright/test');
const base='http://127.0.0.1:8099/tools/browser-fixture/student-process-editor.php';
const dimensions=[
  {label:'narrow',width:320,height:720},
  {label:'phone',width:390,height:844},
  {label:'desktop',width:1440,height:1100},
];
async function screenshotAt(page,label,part){
  const main=page.locator('.student-main');
  await main.evaluate((el,where)=>{el.scrollTop=where==='start'?0:where==='middle'?(el.scrollHeight-el.clientHeight)/2:el.scrollHeight;},part);
  await page.screenshot({path:'student-visual-audit/process-template-editor/'+label+'-'+part+'.png',animations:'disabled'});
}
for(const dim of dimensions){
  test('full operational processing template editor '+dim.label,async({page})=>{
    const errors=[];page.on('pageerror',error=>errors.push(error.message));
    await page.setViewportSize({width:dim.width,height:dim.height});
    const response=await page.goto(base+'?mode=edit',{waitUntil:'networkidle'});
    expect(response?.status()).toBe(200);
    const view=page.locator('[data-operational-template-editor]');
    await expect(view).toHaveCount(1);
    await expect(page.locator('.student-shell')).toHaveCount(1);
    await expect(page.locator('.student-mobile-nav a')).toHaveCount(5);
    await expect(page.locator('.student-process-step-card')).toHaveCount(9);
    await expect(view.locator('[data-stage-key="ferric"]')).toContainText('Cloreto férrico');
    await expect(view.locator('[data-stage-key="ammonia"]')).toContainText('amônia');
    await expect(view.getByText('Mesmo banho da 1ª revelação')).toHaveCount(1);
    await expect(view.getByRole('link',{name:'Abrir no laboratório'})).toHaveAttribute('href','/aluno/processar.php?template=14');
    await expect(view.getByText('Iniciar no laboratório',{exact:true})).toHaveCount(0);
    const current=view.locator('.student-process-step-card.is-editing');
    await expect(current).toHaveCount(1);
    const form=current.locator('[data-process-template-step-form]');
    await expect(form.locator('[name="stage_key"]')).toHaveValue('final_wash');
    await expect(form.locator('[name="step_id"]')).toHaveValue('38');
    await expect(form.locator('[name="_csrf"]')).toHaveValue('fixture-csrf-no-write');
    await expect(form.locator('[data-process-template-development]')).toBeHidden();
    await expect(form.locator('[data-process-template-reuse]')).toBeHidden();
    await expect(form.locator('input[name="duration"]')).toBeVisible();
    await expect(form.getByRole('button',{name:'Salvar etapa'})).toBeVisible();
    const operations=await view.locator('.student-process-step-actions form').evaluateAll(forms=>forms.map(f=>({action:f.querySelector('[name="action"]')?.value,csrf:f.querySelector('[name="_csrf"]')?.value,method:f.method})));
    expect(operations.filter(x=>x.action==='delete_step')).toHaveLength(9);
    expect(operations.filter(x=>x.action==='move_step')).toHaveLength(16);
    expect(operations.every(x=>x.csrf==='fixture-csrf-no-write'&&x.method==='post')).toBe(true);
    // Editing is discoverable; destructive/reordering commands are not repeated in every row.
    const stageOptions=view.locator('.student-process-step-options');
    await expect(stageOptions).toHaveCount(9);
    await expect(view.locator('.student-process-step-actions > a')).toHaveCount(9);
    await expect(stageOptions.filter({has:page.locator('[open]')})).toHaveCount(0);
    await expect(stageOptions.first().getByRole('button',{name:'Descer'})).toBeHidden();
    await expect(stageOptions.first().getByRole('button',{name:'Remover'})).toBeHidden();
    const targets=await view.locator('.student-process-step-actions a, .student-process-step-actions button, .student-process-step-options > summary').evaluateAll(nodes=>nodes.filter(el=>el.getClientRects().length>0).map(el=>{
      const box=el.getBoundingClientRect(),style=getComputedStyle(el);
      return {label:el.textContent.trim(),height:box.height,fontSize:parseFloat(style.fontSize),left:box.left,right:box.right};
    }));
    const metadataTypography=await view.locator('.student-process-step-body > p').first().evaluate(el=>{
      const style=getComputedStyle(el);
      return {size:parseFloat(style.fontSize),family:style.fontFamily,body:getComputedStyle(document.querySelector('.student-shell')).getPropertyValue('--body').trim()};
    });
    expect(metadataTypography.size).toBeGreaterThanOrEqual(12);
    expect(metadataTypography.family).toContain(metadataTypography.body.split(',')[0].replaceAll('"','').replaceAll("'","").trim());
    const retire=view.locator('.student-process-retire');
    await expect(retire).toHaveCount(1);
    await expect(retire).not.toHaveAttribute('open','');
    await expect(retire.getByRole('button',{name:'Excluir processamento'})).toBeHidden();
    const dangerContrast=await page.evaluate(()=>{
      const dangerous=document.querySelector('.student-process-step-actions .student-danger-action');
      const root=document.documentElement,shell=document.querySelector('.student-shell');
      const previousTheme=root.getAttribute('data-theme');
      const ctx=document.createElement('canvas').getContext('2d',{willReadFrequently:true});
      const rgb=css=>{
        ctx.canvas.width=1;ctx.canvas.height=1;
        ctx.clearRect(0,0,1,1);ctx.fillStyle=css;ctx.fillRect(0,0,1,1);
        return [...ctx.getImageData(0,0,1,1).data].slice(0,3);
      };
      const luminance=values=>values.map(v=>{v/=255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4}).reduce((sum,v,i)=>sum+v*[.2126,.7152,.0722][i],0);
      const out={};
      try{
        for(const mode of ['light','dark']){
          // Theme tokens live in .student-page.student-app and are remapped by .student-shell;
          // setting --text on :root does not override that real scoped ownership.
          root.setAttribute('data-theme',mode);
          const textColor=getComputedStyle(dangerous).color;
          const background=getComputedStyle(shell).backgroundColor;
          const a=luminance(rgb(textColor)),b=luminance(rgb(background));
          out[mode]={ratio:(Math.max(a,b)+.05)/(Math.min(a,b)+.05),textColor,background};
        }
      }finally{
        if(previousTheme===null)root.removeAttribute('data-theme');
        else root.setAttribute('data-theme',previousTheme);
      }
      return out;
    });
    expect(dangerContrast.light.ratio,JSON.stringify(dangerContrast.light)+' — destructive action light theme AA').toBeGreaterThanOrEqual(4.5);
    expect(dangerContrast.dark.ratio,JSON.stringify(dangerContrast.dark)+' — destructive action dark theme AA').toBeGreaterThanOrEqual(4.5);
    for(const target of targets){
      expect(target.height,'step action '+target.label+' must be a full touch target').toBeGreaterThanOrEqual(44);
      expect(target.fontSize,'step action '+target.label+' must be legible').toBeGreaterThanOrEqual(11);
      expect(target.left).toBeGreaterThanOrEqual(0);
      expect(target.right).toBeLessThanOrEqual(dim.width+1);
    }
    const metrics=await page.evaluate(()=>{
      const reading=document.querySelector('.student-main'),nav=document.querySelector('.student-mobile-nav');
      const content=document.querySelector('[data-operational-template-editor]');
      return {overflow:reading.scrollWidth-reading.clientWidth,gutter:getComputedStyle(reading).scrollbarGutter,contentWidth:content.getBoundingClientRect().width,readingWidth:reading.getBoundingClientRect().width,readingBottom:reading.getBoundingClientRect().bottom,navTop:nav.getBoundingClientRect().top,navVisible:getComputedStyle(nav).display!=='none'};
    });
    expect(metrics.overflow,'horizontal scroll cannot hide form actions').toBeLessThanOrEqual(1);
    expect(metrics.gutter).toContain('stable');
    expect(metrics.contentWidth).toBeLessThanOrEqual(metrics.readingWidth+1);
    if(dim.width<821&&metrics.navVisible)expect(metrics.readingBottom).toBeLessThanOrEqual(metrics.navTop+1);
    await screenshotAt(page,dim.label+'-edit','start');
    await screenshotAt(page,dim.label+'-edit','middle');
    await screenshotAt(page,dim.label+'-edit','end');
    await stageOptions.nth(6).locator('summary').click();
    await expect(stageOptions.nth(6)).toHaveAttribute('open','');
    await expect(stageOptions.nth(6).getByRole('button',{name:'Subir'})).toBeVisible();
    await expect(stageOptions.nth(6).getByRole('button',{name:'Descer'})).toBeVisible();
    await expect(stageOptions.nth(6).getByRole('button',{name:'Remover'})).toBeVisible();
    const openedMetrics=await stageOptions.nth(6).locator('summary, button').evaluateAll(nodes=>nodes.map(el=>{
      const r=el.getBoundingClientRect();return {label:el.textContent.trim(),height:r.height,left:r.left,right:r.right};
    }));
    const expandedAlignment=await stageOptions.nth(6).evaluate(details=>{
      const summary=details.querySelector('summary').getBoundingClientRect();
      const edit=details.parentElement.querySelector(':scope > a').getBoundingClientRect();
      return {summaryLeft:summary.left,editLeft:edit.left,summaryTop:summary.top,editBottom:edit.bottom};
    });
    expect(Math.abs(expandedAlignment.summaryLeft-expandedAlignment.editLeft),'expanded options must align with the step action').toBeLessThanOrEqual(12);
    expect(expandedAlignment.summaryTop,'options should follow the edit action rather than float alongside it').toBeGreaterThanOrEqual(expandedAlignment.editBottom-1);
    for(const m of openedMetrics){
      expect(m.height,m.label+' expanded target height').toBeGreaterThanOrEqual(44);
      expect(m.left,m.label+' expanded left boundary').toBeGreaterThanOrEqual(0);
      expect(m.right,m.label+' expanded right boundary').toBeLessThanOrEqual(dim.width+1);
    }
    await page.screenshot({path:'student-visual-audit/process-template-editor/'+dim.label+'-options-expanded.png',animations:'disabled'});
    await stageOptions.nth(6).locator('summary').click();
    await expect(stageOptions.nth(6).getByRole('button',{name:'Remover'})).toBeHidden();
    await retire.locator('summary').click();
    await expect(retire.getByRole('button',{name:'Excluir processamento'})).toBeVisible();
    await retire.getByRole('button',{name:'Excluir processamento'}).scrollIntoViewIfNeeded();
    await page.screenshot({path:'student-visual-audit/process-template-editor/'+dim.label+'-retire-expanded.png',animations:'disabled'});
    await retire.locator('summary').click();
    await form.locator('[name="stage_key"]').selectOption('second_development');
    await expect(form.locator('[data-process-template-development]')).toBeVisible();
    await expect(form.locator('[data-process-template-reuse]')).toBeVisible();
    await form.locator('[name="developer_key"]').selectOption('other');
    await expect(form.locator('[data-process-template-other-developer]')).toBeVisible();
    await expect(form.locator('details').filter({hasText:'Composição da solução'})).toHaveAttribute('open','');
    await form.locator('[name="stage_key"]').selectOption('final_wash');
    await expect(form.locator('[data-process-template-development]')).toBeHidden();
    await expect(form.locator('[name="developer_key"]')).toBeDisabled();
    await form.locator('[name="stage_key"]').selectOption('second_development');
    await expect(form.locator('[name="developer_key"]')).toHaveValue('other');
    await expect(form.locator('[name="developer_key"]')).toBeEnabled();
    await screenshotAt(page,dim.label+'-modified','end');
    expect(errors).toEqual([]);
  });
}
test('empty template exposes adding, never running',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  const response=await page.goto(base+'?mode=empty',{waitUntil:'networkidle'});
  expect(response?.status()).toBe(200);
  await expect(page.locator('.student-process-step-card')).toHaveCount(0);
  await expect(page.getByText('Este roteiro ainda está vazio.')).toBeVisible();
  await expect(page.getByRole('link',{name:'Abrir no laboratório'})).toHaveCount(0);
  const add=page.locator('details.student-process-add');
  await expect(add).toHaveAttribute('open','');
  await expect(add.locator('[name="action"]')).toHaveValue('add_step');
  await screenshotAt(page,'phone-empty','start');
});
test('record association remains distinct from execution and personal duplication',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  const response=await page.goto(base+'?mode=association',{waitUntil:'networkidle'});
  expect(response?.status()).toBe(200);
  const association=page.locator('form').filter({has:page.locator('input[name="action"][value="apply_template"]')});
  await expect(association).toHaveCount(1);
  await expect(association.locator('input[name="test_id"]')).toHaveValue('7');
  await expect(association.getByRole('button',{name:'Associar ao registro'})).toBeVisible();
  await expect(page.getByRole('button',{name:'Duplicar processamento'})).toHaveCount(0);
  await expect(page.getByRole('link',{name:'Abrir no laboratório'})).toHaveCount(0);
  await expect(page.locator('.student-mobile-nav a[href="/aluno/caderno.php"]')).toHaveAttribute('aria-current','page');
  await screenshotAt(page,'phone-association','start');
});
test('unmodified template is a navigable reading surface without an auto-start',async({page})=>{
  await page.setViewportSize({width:320,height:720});
  await page.goto(base+'?mode=read',{waitUntil:'networkidle'});
  await expect(page.locator('.student-process-step-card.is-editing')).toHaveCount(0);
  await expect(page.locator('.student-process-step-card')).toHaveCount(9);
  await expect(page.locator('.student-process-step-actions a').first()).toHaveAttribute('href','/aluno/processamentos.php?id=14&step=31#editar-etapa');
  await screenshotAt(page,'narrow-read','start');
});
