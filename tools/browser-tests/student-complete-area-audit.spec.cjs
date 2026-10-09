const {test,expect}=require('@playwright/test');
const host='http://127.0.0.1:8099';
const fixture=p=>host+'/tools/browser-fixture/'+p;
const screens=[
  ['login','student-auth-visual-audit.html?screen=login'],
  ['activation-password','student-auth-visual-audit.html?screen=password'],
  ['home','student-dashboard-truth.html?state=active'],
  ['home-feedback','student-pedagogical-feedback-audit.html?screen=home-feedback'],
  ['home-study','student-dashboard-truth.html?state=study'],
  ['home-multiple','student-dashboard-truth.html?state=multiple'],
  ['course-list','student-course-context.html'],
  ['course-detail','student-course-context.html?cohort=cohort-a'],
  ['course-feedback','student-pedagogical-feedback-audit.html?screen=course-feedback'],
  ['course-empty','student-course-context.html?cohort=cohort-b&state=empty'],
  ['material','student-material-context.html'],
  ['questions-list','student-secondary-screens-audit.html?screen=questions-list'],
  ['questions-feedback','student-pedagogical-feedback-audit.html?screen=questions-feedback'],
  ['question-new','student-secondary-screens-audit.html?screen=question-new'],
  ['question-thread','student-secondary-screens-audit.html?screen=question-thread'],
  ['notebook','student-caderno-product-audit.html?screen=notebook'],
  ['new-record','student-visual-audit.html?screen=new-record'],
  ['exposure','student-caderno-product-audit.html?screen=record-empty'],
  ['process-choice','student-caderno-product-audit.html?screen=record-empty'],
  ['process-plan','student-caderno-product-audit.html?screen=record-plan'],
  ['process-partial','student-caderno-product-audit.html?screen=record-partial'],
  ['result','student-caderno-product-audit.html?screen=record-result'],
  ['result-waiting','student-pedagogical-feedback-audit.html?screen=result-waiting'],
  ['result-revision','student-pedagogical-feedback-audit.html?screen=result-revision'],
  ['result-reviewed','student-pedagogical-feedback-audit.html?screen=result-reviewed'],
  ['step-editor','student-secondary-screens-audit.html?screen=step-editor'],
  ['shared-record','student-secondary-screens-audit.html?screen=shared-record'],
  ['delete-record','student-secondary-screens-audit.html?screen=delete-record'],
  ['compare-select','student-notebook-compare.html?screen=select'],
  ['compare-records','student-notebook-compare.html?screen=analysis'],
  ['research-derived','student-notebook-compare.html?screen=derived'],
  ['process-library','student-process-product-audit.html?screen=library'],
  ['process-editor','student-process-product-audit.html?screen=editor'],
  ['lab-runner','student-process-execution-state-audit.html?state=idle'],
  ['inventory','student-lab-stock-product-audit.html?screen=inventory'],
  ['inventory-empty','student-lab-stock-product-audit.html?screen=empty'],
  ['inventory-movement','student-lab-stock-product-audit.html?screen=movement'],
  ['inventory-item-editor','student-secondary-screens-audit.html?screen=inventory-item'],
  ['preparations','student-lab-stock-product-audit.html?screen=preparations'],
  ['preparation-editor','student-lab-stock-product-audit.html?screen=editor'],
  ['calibration-list','student-secondary-screens-audit.html?screen=calibration-list'],
  ['calibration-editor','student-secondary-screens-audit.html?screen=calibration-editor'],
  ['tools','student-visual-audit.html?screen=tools'],
  ['toolbox','student-visual-audit.html?screen=toolbox'],
  ['profile','student-secondary-screens-audit.html?screen=profile'],
  ['password-change','student-secondary-screens-audit.html?screen=password-change'],
];
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};
const shellStyles=['/assets/student-workbench.css','/assets/student-experience.css','/assets/student-academic.css','/assets/student-feedback.css','/assets/student-rendered-fixes.css','/assets/student-auth.css','/assets/student-field-language.css'];
async function ensureCanonicalShellStyles(page){
  await page.evaluate(async styles=>{
    const featurePattern=/\/assets\/student-(?:auth|caderno|processes|lab-stock|process-recording)\.css(?:\?|$)/;
    const head=document.head;
    let anchor=[...head.querySelectorAll('link[rel="stylesheet"]')].find(link=>featurePattern.test(link.getAttribute('href')||''))||null;
    for(const href of styles){
      if([...head.querySelectorAll('link[rel="stylesheet"]')].some(link=>(link.getAttribute('href')||'').split('?')[0]===href))continue;
      await new Promise((resolve,reject)=>{const link=document.createElement('link');link.rel='stylesheet';link.href=href;link.onload=resolve;link.onerror=reject;head.insertBefore(link,anchor);});
    }
    if(![...head.querySelectorAll('link[rel="stylesheet"]')].some(link=>(link.getAttribute('href')||'').split('?')[0]==='/assets/student-quality-pass.css')){
      await new Promise((resolve,reject)=>{const link=document.createElement('link');link.rel='stylesheet';link.href='/assets/student-quality-pass.css';link.onload=resolve;link.onerror=reject;head.appendChild(link);});
    }
  },shellStyles);
}
for(const [device,viewport] of Object.entries(viewports)){
  for(const [name,path] of screens){
    test(`complete student area visual audit ${device} ${name}`,async({page})=>{
      await page.setViewportSize(viewport);await page.goto(fixture(path),{waitUntil:'networkidle'});
      if(name!=='material')await ensureCanonicalShellStyles(page);
      await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
      if(name==='new-record'||name==='toolbox'){
        const selector=name==='new-record'?'.student-create-dialog':'.student-toolbox';
        await page.evaluate(sel=>{const d=document.querySelector(sel);if(d){if(d.open)d.removeAttribute('open');if(typeof d.showModal==='function')d.showModal();else d.setAttribute('open','');}},selector);
      }
      if(name==='home-feedback'){await expect(page.getByText('Retorno do professor',{exact:true})).toBeVisible();await expect(page.getByText('Ler retorno e revisar →',{exact:true})).toBeVisible();}
      if(name==='course-feedback'){await expect(page.getByText('Há algo para retomar',{exact:true})).toBeVisible();await expect(page.getByText('Revisão solicitada',{exact:true})).toBeVisible();}
      if(name==='questions-feedback'){await expect(page.getByText('Conversas de avaliação',{exact:true})).toBeVisible();await expect(page.getByText('Dúvidas da turma',{exact:true})).toBeVisible();}
      if(name==='result-waiting')await expect(page.getByText('Enviado para avaliação',{exact:true})).toBeVisible();
      if(name==='result-revision'){await expect(page.getByText('Revisão solicitada',{exact:true})).toBeVisible();await expect(page.getByText('Enviar revisão para avaliação',{exact:true})).toBeVisible();await expect(page.getByText('Professor',{exact:true})).toBeVisible();}
      if(name==='result-reviewed')await expect(page.getByText('Avaliação concluída',{exact:true})).toBeVisible();
      if(name==='compare-select'){await expect(page.getByText('Escolha o segundo registro',{exact:true})).toBeVisible();await expect(page.getByText('EI 200 — FeCl₃',{exact:true})).toBeVisible();}
      if(name==='compare-records'){await expect(page.getByText('Diferenças registradas',{exact:true})).toBeVisible();await expect(page.getByText('Resultado observado',{exact:true})).toBeVisible();await expect(page.getByText('Criar próxima variação',{exact:true})).toBeVisible();}
      if(name==='research-derived'){
        await expect(page.getByText('Continuação de',{exact:false})).toBeVisible();await expect(page.getByText('Manter EI 400 e voltar a primeira revelação para 7 minutos.',{exact:false})).toBeVisible();
        const menu=page.locator('.student-record-menu'),compareOrigin=menu.getByRole('link',{name:'Comparar com origem'});await expect(menu).toBeVisible();await expect(compareOrigin).toBeHidden();await menu.locator('summary').click();await expect(compareOrigin).toBeVisible();await menu.locator('summary').click();await expect(compareOrigin).toBeHidden();
      }
      if(name==='process-choice'){
        await expect(page.getByText('Associar roteiro',{exact:true})).toBeVisible();await expect(page.getByText('Adicionar etapa',{exact:true}).first()).toBeVisible();await expect(page.getByText('Movimentar estoque',{exact:true})).toBeVisible();await expect(page.getByText('O processamento já aconteceu?',{exact:false})).toHaveCount(0);
      }
      if(name==='process-plan'){
        await expect(page.getByText('0 de 9 marcadas',{exact:true})).toBeVisible();await expect(page.locator('.student-notebook-route-step')).toHaveCount(9);await expect(page.getByText('Abrir roteiro',{exact:true})).toBeVisible();await expect(page.getByText('Trocar roteiro-base',{exact:true})).toBeVisible();await expect(page.getByText('Editar',{exact:true}).first()).toBeVisible();await expect(page.getByText('Timer',{exact:true}).first()).toBeVisible();await expect(page.getByText('Abrir laboratório',{exact:false})).toHaveCount(0);
      }
      if(name==='process-partial'){
        await expect(page.getByText('5 de 9 marcadas',{exact:true})).toBeVisible();await expect(page.getByRole('button',{name:'Desmarcar'})).toHaveCount(5);await expect(page.getByRole('button',{name:'Marcar ✓'})).toHaveCount(4);await expect(page.getByText('Completar registro',{exact:false})).toHaveCount(0);
      }
      if(name==='step-editor'){await expect(page.getByText('Nenhuma outra etapa é alterada por isso.',{exact:false})).toBeVisible();await expect(page.getByText('Corrigir a sequência do processo',{exact:false})).toHaveCount(0);await expect(page.getByText('Remover esta etapa',{exact:true})).toBeVisible();}
      if(name==='process-library'){await expect(page.locator('.student-process-standard-card')).toHaveCount(6);await expect(page.getByText('Abrir no laboratório',{exact:true}).first()).toBeVisible();await expect(page.getByText('Iniciar no laboratório',{exact:true})).toHaveCount(0);}
      if(name==='process-editor'){await expect(page.locator('.student-process-step-card')).toHaveCount(9);await expect(page.getByText('Abrir no laboratório',{exact:true})).toBeVisible();await expect(page.getByText('Iniciar no laboratório',{exact:true})).toHaveCount(0);}
      if(name==='lab-runner'){
        await expect(page.locator('.student-lab-stage-nav a')).toHaveCount(9);await expect(page.getByRole('button',{name:'Marcar como concluída',exact:true})).toBeVisible();await expect(page.getByText('Ir para próxima etapa',{exact:false})).toHaveCount(0);
      }
      if(device==='phone'&&name!=='login'&&name!=='activation-password'){
        const mobileChrome=await page.evaluate(()=>{const nav=document.querySelector('.student-mobile-nav'),top=document.querySelector('.student-topbar,.cms-topbar'),reserve=document.querySelector('.student-shell')||document.body;if(!nav||!top||!reserve)return null;const ns=getComputedStyle(nav),ts=getComputedStyle(top),rs=getComputedStyle(reserve),nr=nav.getBoundingClientRect();return {navDisplay:ns.display,topDisplay:ts.display,navPosition:ns.position,navBottom:nr.bottom,navHeight:nr.height,viewportHeight:innerHeight,reservedBottom:parseFloat(rs.paddingBottom||'0'),scrollPaddingBottom:parseFloat(getComputedStyle(document.documentElement).scrollPaddingBottom||'0')};});
        expect(mobileChrome,`${name}: authenticated mobile surface must expose global chrome`).not.toBeNull();
        expect(mobileChrome.navDisplay,`${name}: mobile navigation unexpectedly hidden`).not.toBe('none');expect(mobileChrome.topDisplay,`${name}: topbar unexpectedly hidden`).not.toBe('none');expect(mobileChrome.navPosition,`${name}: mobile nav must stay anchored to viewport`).toBe('fixed');expect(Math.abs(mobileChrome.navBottom-mobileChrome.viewportHeight),`${name}: mobile nav must touch bottom viewport edge`).toBeLessThanOrEqual(1);expect(mobileChrome.reservedBottom,`${name}: surface must reserve the mobile nav footprint`).toBeGreaterThanOrEqual(mobileChrome.navHeight);expect(mobileChrome.scrollPaddingBottom,`${name}: scrolling must account for the fixed mobile navigation`).toBeGreaterThanOrEqual(mobileChrome.navHeight);
      }
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);expect(overflow,`${name} horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
      await page.screenshot({path:`student-visual-audit/complete/${device}/${name}.png`,fullPage:true,animations:'disabled'});
    });
  }
}

test('complete student visual audit declares all rendered student route families',()=>{
  const names=new Set(screens.map(([name])=>name));
  for(const required of ['login','home','home-feedback','home-study','home-multiple','course-list','course-detail','course-feedback','course-empty','material','questions-list','questions-feedback','question-thread','notebook','new-record','exposure','process-choice','process-plan','process-partial','result','result-waiting','result-revision','result-reviewed','step-editor','shared-record','delete-record','compare-select','compare-records','research-derived','process-library','process-editor','lab-runner','inventory','inventory-item-editor','preparations','calibration-list','tools','toolbox','profile','password-change'])expect(names.has(required),`missing visual surface ${required}`).toBe(true);
  for(const obsolete of ['process-intent','recording-start','recording-associated','recording-partial','recording-complete'])expect(names.has(obsolete),`obsolete temporal processing surface ${obsolete}`).toBe(false);
  expect(screens.length,'full student audit surface count').toBe(46);
});

for(const [device,width,height] of [['phone',390,844],['desktop',1280,820]]){
  test(`student material annotation and question composer — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});
    await page.goto(fixture('student-inline-annotations.html'),{waitUntil:'networkidle'});
    const textBlock=page.locator('[data-student-anchor-block="processo:p:1"]');
    await textBlock.scrollIntoViewIfNeeded();
    await textBlock.evaluate(element=>{
      const text=element.firstChild;
      const needle='parágrafo foi alterado',start=text.nodeValue.indexOf(needle);
      if(start<0)throw new Error('Fixture source excerpt missing');
      const range=document.createRange();range.setStart(text,start);range.setEnd(text,start+needle.length);
      window.getSelection().removeAllRanges();window.getSelection().addRange(range);
      element.dispatchEvent(new MouseEvent('mouseup',{bubbles:true}));
    });
    const annotate=page.locator('.student-selection-note-action');
    await expect(annotate).toBeVisible({timeout:2500});
    await annotate.click();
    const panel=page.locator('[data-student-notes-panel]');
    const composer=panel.locator('[data-inline-note-compose]');
    await expect(panel.locator('.student-notes-head')).toBeHidden();
    await expect(composer.locator('[data-inline-note-body]')).toBeVisible();
    await expect(composer.locator('.student-inline-note-primary-actions')).toBeVisible();
    await panel.screenshot({path:`student-visual-audit/notes/${device}-annotation.png`,animations:'disabled'});
    await composer.locator('[data-note-question]>summary').click();
    await expect(panel).toHaveClass(/is-questioning/);
    await expect(composer.locator('[data-inline-note-body]')).toBeHidden();
    await expect(composer.locator('.student-inline-note-primary-actions')).toBeHidden();
    await expect(composer.locator('input[name="question_visibility"]')).toHaveCount(3);
    await expect(composer.locator('input[value="course"]')).toBeVisible();
    await panel.screenshot({path:`student-visual-audit/notes/${device}-question.png`,animations:'disabled'});
  });
}

for(const [device,width,height] of [['phone',390,844],['desktop',1280,820]]){
  test(`student saved annotations — list, edit, question and general note — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});
    await page.goto(fixture('student-inline-annotations.html'),{waitUntil:'networkidle'});
    const panel=page.locator('[data-student-notes-panel]');
    await panel.locator('summary').first().click();
    await expect(panel.locator('[data-annotation-item="2"] .student-note-body-preview')).toBeVisible();
    await expect(panel.locator('[data-annotation-item="2"] textarea')).toBeHidden();
    await panel.screenshot({path:`student-visual-audit/notes/${device}-saved-list.png`,animations:'disabled'});
    await panel.locator('[data-annotation-edit="2"]').click();
    await expect(panel.locator('[data-student-notes-back-button]')).toBeVisible();
    await expect(panel.locator('[data-annotation-item="2"] textarea')).toBeVisible();
    await expect(panel.locator('[data-student-note-new]')).toBeHidden();
    await panel.screenshot({path:`student-visual-audit/notes/${device}-saved-edit.png`,animations:'disabled'});
    await panel.locator('[data-annotation-item="2"] [data-note-question]>summary').click();
    await expect(panel.locator('[data-annotation-item="2"] .student-note-actions-group')).toBeHidden();
    await panel.screenshot({path:`student-visual-audit/notes/${device}-saved-question.png`,animations:'disabled'});
    await panel.locator('[data-annotation-item="2"] [data-note-question]>summary').click();
    await expect(panel.locator('[data-student-notes-back-button]')).toBeVisible();
    await panel.locator('[data-student-notes-back-button]').click();
    await panel.locator('[data-student-note-new]>summary').click();
    await expect(panel).toHaveClass(/is-general-composing/);
    await expect(panel.locator('.student-notes-list')).toBeHidden();
    await expect(panel.locator('.student-notes-head')).toBeHidden();
    await expect(panel.locator('[data-student-note-new]>summary')).toHaveText('← Todas as anotações');
    await expect(panel.locator('[data-student-note-new] form')).toBeVisible();
    await panel.screenshot({path:`student-visual-audit/notes/${device}-general-note.png`,animations:'disabled'});
    await panel.locator('[data-student-note-new] [data-note-question]>summary').click();
    await expect(panel.locator('[data-student-note-new]>summary')).toBeHidden();
    await expect(panel.locator('[data-student-note-new] [data-note-question]>summary')).toBeVisible();
    await panel.screenshot({path:`student-visual-audit/notes/${device}-general-question.png`,animations:'disabled'});
  });
}

for(const [device,width,height] of [['phone',390,844],['desktop',1280,820]]){
  test(`saved annotation deletion confirmation and cancellation — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});
    await page.goto(fixture('student-inline-annotations.html'),{waitUntil:'networkidle'});
    const panel=page.locator('[data-student-notes-panel]');
    await panel.locator('summary').first().click();
    await panel.locator('[data-annotation-edit="1"]').click();
    await panel.locator('[data-annotation-item="1"] button[value="remove"]').click();
    const dialog=panel.locator('[data-note-remove-confirm]');
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole('button',{name:'Cancelar'})).toBeVisible();
    await expect(dialog.getByRole('button',{name:'Remover anotação'})).toBeVisible();
    await page.screenshot({path:`student-visual-audit/notes/${device}-remove-confirmation.png`,animations:'disabled'});
    await dialog.getByRole('button',{name:'Cancelar'}).click();
    await expect(dialog).toBeHidden();
    await expect(panel.locator('[data-annotation-item="1"] textarea')).toBeVisible();
  });
}

for(const [device,viewport] of Object.entries(viewports)){
  for(const [name,path] of screens){
    test(`student visible controls are reachable and unobstructed — ${device} ${name}`,async({page})=>{
      test.setTimeout(90000);
      await page.setViewportSize(viewport);
      await page.goto(fixture(path),{waitUntil:'networkidle'});
      if(name!=='material')await ensureCanonicalShellStyles(page);
      await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
      if(name==='new-record'||name==='toolbox'){
        await page.evaluate(selector=>{
          const d=document.querySelector(selector);
          if(d){if(d.open)d.removeAttribute('open');if(typeof d.showModal==='function')d.showModal();else d.setAttribute('open','');}
        },name==='new-record'?'.student-create-dialog':'.student-toolbox');
      }
      const controls=page.locator('button:visible,a[href]:visible,summary:visible,[role="button"]:visible');
      const count=await controls.count();
      expect(count,`${name} must contain visible interactive controls`).toBeGreaterThan(0);
      for(let i=0;i<count;i++){
        const locator=controls.nth(i);
        if(!await locator.isVisible())continue;
        const item=await locator.evaluate(el=>{
          const modal=document.querySelector('dialog[open]:modal');
          if(modal&&!modal.contains(el))return{skip:true};
          const style=getComputedStyle(el);
          if(style.pointerEvents==='none')return{skip:true};
          return {skip:false,tag:el.tagName,label:(el.getAttribute('aria-label')||el.textContent||'').trim().slice(0,80)};
        });
        if(item.skip)continue;
        await locator.scrollIntoViewIfNeeded({timeout:3000});
        const measure=()=>locator.evaluate(el=>{
          const r=el.getBoundingClientRect(),x=r.left+r.width/2,y=r.top+r.height/2;
          const hit=document.elementFromPoint(x,y);
          return {top:r.top,bottom:r.bottom,left:r.left,right:r.right,width:r.width,height:r.height,
            viewport:[innerWidth,innerHeight],hit:!!hit&&(hit===el||el.contains(hit)),
            actual:hit?.tagName||null,actualClass:typeof hit?.className==='string'?hit.className.slice(0,120):'',
            actualHref:hit?.getAttribute('href')||'',x,y};
        });
        let geometry=await measure();
        // Browsers do not account for the fixed bottom navigation when deciding
        // whether an element is "already in view". Try actual user-equivalent
        // scrolling before declaring it blocked; never force-click the target.
        for(let n=0;!geometry.hit&&n<5;n++){
          await page.mouse.move(Math.round(viewport.width/2),Math.round(viewport.height*0.5));
          await page.mouse.wheel(0,Math.round(viewport.height*0.25));
          await page.waitForTimeout(60);
          geometry=await measure();
        }
        expect(geometry.width,`${device} ${name} control "${item.label}" has no width`).toBeGreaterThan(0);
        expect(geometry.height,`${device} ${name} control "${item.label}" has no height`).toBeGreaterThan(0);
        expect(geometry.left,`${device} ${name} control "${item.label}" starts outside screen`).toBeGreaterThanOrEqual(-1);
        expect(geometry.right,`${device} ${name} control "${item.label}" exceeds screen`).toBeLessThanOrEqual(viewport.width+1);
        expect(geometry.top,`${device} ${name} control "${item.label}" is above visible viewport`).toBeGreaterThanOrEqual(-1);
        expect(geometry.bottom,`${device} ${name} control "${item.label}" is below visible viewport`).toBeLessThanOrEqual(viewport.height+1);
        expect(geometry.hit,`${device} ${name} control "${item.label}" blocked by ${geometry.actual}.${geometry.actualClass} href=${geometry.actualHref} at ${geometry.x},${geometry.y} after scroll`).toBe(true);
      }
    });
  }
}

for(const [device,width,height] of [['phone',390,844],['desktop',1280,820]]){
  test(`legacy saved note and validation error look correct — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});
    await page.route('**/aluno/material-anotacao.php',route=>route.fulfill({
      status:422,contentType:'application/json',
      body:JSON.stringify({ok:false,error:'Não foi possível salvar. A anotação continua disponível para edição.'})
    }));
    await page.goto(fixture('student-inline-annotations.html'),{waitUntil:'networkidle'});
    const panel=page.locator('[data-student-notes-panel]');
    await panel.locator('summary').first().click();
    await page.evaluate(()=>{
      const template=document.getElementById('fixture-legacy-note');
      const list=document.querySelector('.student-notes-list');
      if(!template||!list)throw new Error('Missing real legacy-note markup');
      list.append(template.content.firstElementChild.cloneNode(true));
    });
    const legacy=panel.locator('[data-legacy-note]');
    await expect(legacy.locator('.student-note-body-preview')).toBeVisible();
    await expect(legacy.locator('form')).toBeHidden();
    await legacy.locator('[data-legacy-edit]').scrollIntoViewIfNeeded();
    const inViewport=await legacy.locator('[data-legacy-edit]').evaluate(el=>{
      const box=el.getBoundingClientRect();
      const sheet=el.closest('.student-notes-sheet')?.getBoundingClientRect();
      return !!sheet&&box.top>=sheet.top&&box.bottom<=sheet.bottom&&box.left>=sheet.left&&box.right<=sheet.right;
    });
    expect(inViewport,`${device}: legacy editor action must be visible in the actual notes sheet`).toBe(true);
    await panel.screenshot({path:`student-visual-audit/notes/${device}-legacy-list.png`,animations:'disabled'});
    await legacy.locator('[data-legacy-edit]').click();
    await expect(legacy.locator('textarea')).toBeVisible();
    await panel.screenshot({path:`student-visual-audit/notes/${device}-legacy-edit.png`,animations:'disabled'});
    await legacy.locator('textarea').fill('Meu rascunho não pode ser perdido na falha de gravação.');
    await legacy.getByRole('button',{name:'Salvar alterações'}).click();
    const warning=legacy.locator('[data-note-save-status]');
    await expect(warning).toHaveAttribute('role','alert');
    await expect(warning).toContainText('Não foi possível salvar.');
    await expect(legacy.locator('textarea')).toHaveValue('Meu rascunho não pode ser perdido na falha de gravação.');
    await panel.screenshot({path:`student-visual-audit/notes/${device}-legacy-save-error.png`,animations:'disabled'});
  });
}

for(const [device,width,height] of [['phone',390,844],['desktop',1280,820]]){
  test(`student notes empty state retains an accessible create action — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});
    await page.goto(fixture('student-inline-annotations.html?state=empty'),{waitUntil:'networkidle'});
    const panel=page.locator('[data-student-notes-panel]');
    await panel.locator('summary').first().click();
    await expect(panel.locator('.student-notes-empty')).toHaveText('Você ainda não fez anotações nesta página.');
    await expect(panel.locator('[data-annotation-item]')).toHaveCount(0);
    await expect(panel.locator('[data-student-note-new]>summary')).toBeVisible();
    await panel.screenshot({path:`student-visual-audit/notes/${device}-empty-list.png`,animations:'disabled'});
    await panel.locator('[data-student-note-new]>summary').click();
    await expect(panel.locator('[data-student-note-new] form')).toBeVisible();
    await expect(panel.locator('.student-notes-empty')).toBeHidden();
    await panel.screenshot({path:`student-visual-audit/notes/${device}-empty-new-general.png`,animations:'disabled'});
  });
}
