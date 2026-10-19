const {test,expect}=require('@playwright/test');
const host='http://127.0.0.1:8099';
const fixture=p=>host+'/tools/browser-fixture/'+p;
const screens=[
  ['login','student-auth-operational.php?mode=login'],
  ['activation-password','student-auth-operational.php?mode=activation'],
  ['home','student-home.php?state=active'],
  ['home-feedback','student-home.php?state=feedback'],
  ['home-study','student-home.php?state=study'],
  ['home-multiple','student-home.php?state=multiple'],
  ['course-list','student-courses.php?state=multiple'],
  ['course-detail','student-courses.php?state=detail'],
  ['course-feedback','student-courses.php?state=attention'],
  ['course-empty','student-courses.php?state=no-material'],
  ['material','student-material-cms-operational.php?page=current&cohort=cohort-a'],
  ['questions-list','student-questions-operational.php?state=list'],
  ['questions-feedback','student-questions-operational.php?state=evaluations'],
  ['question-new','student-questions-operational.php?state=failed-create'],
  ['question-thread','student-questions-operational.php?state=thread&id=1'],
  ['notebook','student-notebook.php?state=mixed'],
  ['new-record','student-record-create-sheet.php'],
  ['exposure','student-record-workspace-operational.php?state=exposure'],
  ['process-choice','student-record-workspace-operational.php?state=process-choice'],
  ['process-plan','student-record-workspace-operational.php?state=process-plan'],
  ['process-partial','student-record-workspace-operational.php?state=process-partial'],
  ['result','student-record-workspace-operational.php?state=result'],
  ['result-waiting','student-record-workspace-operational.php?state=result-waiting'],
  ['result-revision','student-record-workspace-operational.php?state=result-revision'],
  ['result-reviewed','student-record-workspace-operational.php?state=result-reviewed'],
  ['step-editor','student-record-actions-operational.php?mode=step'],
  ['shared-record','student-shared-record-operational.php?mode=filled'],
  ['delete-record','student-record-actions-operational.php?mode=delete'],
  ['compare-select','student-comparison-operational.php?mode=select'],
  ['compare-records','student-comparison-operational.php?mode=analysis'],
  ['research-derived','student-notebook.php?state=derived'],
  ['process-library','student-process-saved-cards.php?mode=library'],
  ['process-editor','student-process-editor.php?mode=edit'],
  ['lab-runner','student-process-runner-operational.php?state=idle'],
  ['inventory','student-inventory-movement.php?mode=inventory'],
  ['inventory-empty','student-inventory-movement.php?mode=empty'],
  ['inventory-movement','student-inventory-movement.php'],
  ['inventory-item-editor','student-inventory-item-direct.php?mode=editor'],
  ['preparations','student-long-editors.php?kind=preparation&mode=list'],
  ['preparation-editor','student-long-editors.php?kind=preparation&mode=edit'],
  ['calibration-list','student-long-editors.php?kind=calibration&mode=list'],
  ['calibration-editor','student-long-editors.php?kind=calibration&mode=edit'],
  ['tools','student-laboratory.php?state=full'],
  ['toolbox','student-toolbox-real-audit.php'],
  ['profile','student-profile.php?mode=view'],
  ['password-change','student-auth-operational.php?mode=change'],
];
const viewports={desktop:{width:1440,height:1100},phone:{width:390,height:844}};
// Four screens share the operational PHP view and v2 shell; never patch their CSS in the browser.
const operationalScreens=new Set(['course-feedback','questions-feedback','process-library','process-editor','lab-runner','profile','password-change','preparations','calibration-list','step-editor','delete-record','compare-select','compare-records','exposure','process-choice','result','result-waiting','result-revision','result-reviewed','process-plan','process-partial','shared-record','research-derived']);
const anonymousAuthScreens=new Set(['login','activation-password']);
// The mobile-first geometry lives in student-area.css; feature assets remain downstream.
const shellStyles=['/assets/student-workbench.css','/assets/student-experience.css','/assets/student-academic.css','/assets/student-feedback.css','/assets/student-rendered-fixes.css','/assets/student-auth.css'];
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
      const pageErrors=[];page.on('pageerror',error=>pageErrors.push(error.message));
      await page.setViewportSize(viewport);const response=await page.goto(fixture(path),{waitUntil:'networkidle'});
      expect(response?.status(),`${name}: expected successful PHP fixture rendering`).toBe(200);
      if(name!=='material'&&!operationalScreens.has(name)&&!anonymousAuthScreens.has(name))await ensureCanonicalShellStyles(page);
      await page.evaluate(()=>document.documentElement.setAttribute('data-theme','dark'));
      if(anonymousAuthScreens.has(name)){
        const auth=await page.evaluate(()=>({
          app:document.body.classList.contains('student-app'),
          shell:!!document.querySelector('.student-shell'),
          css:[...document.querySelectorAll('link[rel="stylesheet"]')].some(n=>(n.getAttribute('href')||'').split('?')[0]==='/assets/student-area.css'),
          nav:document.querySelectorAll('.student-mobile-nav a').length
        }));
        expect(auth.app&&auth.shell&&auth.css,`${name}: render true anonymous shell`).toBe(true);
        expect(auth.nav,`${name}: anonymous access cannot expose authenticated nav`).toBe(0);
        await expect(page.locator('form [name="_csrf"]')).toHaveCount(name==='login'?2:1);
      }
      if(operationalScreens.has(name)){
        const contract=await page.evaluate(()=>({
          app:document.body.classList.contains('student-app'),
          shell:!!document.querySelector('.student-shell'),
          shellCss:[...document.querySelectorAll('link[rel="stylesheet"]')].some(n=>(n.getAttribute('href')||'').split('?')[0]==='/assets/student-area.css'),
          links:[...document.querySelectorAll('.student-mobile-nav a')].map(a=>a.getAttribute('href')),
          scrollGutter:document.querySelector('.student-main')?getComputedStyle(document.querySelector('.student-main')).scrollbarGutter:''
        }));
        expect(contract.app&&contract.shell&&contract.shellCss,`${name}: must use operational student shell and its stylesheet`).toBe(true);
        expect(contract.links,`${name}: global navigation destinations`).toEqual(['/aluno/','/aluno/agenda.php','/aluno/cursos.php','/aluno/caderno.php','/aluno/ferramentas.php']);
        expect(contract.scrollGutter,`${name}: real shell reserves scroll track`).toContain('stable');
      }
      if(name==='material'){
        const cms=await page.evaluate(()=>({
          page:document.body.classList.contains('cms-student-material'),
          root:!!document.querySelector('.cms-student-reading[data-student-scroll-root]'),
          main:!!document.querySelector('main[data-cms-page-main]'),
          nav:[...document.querySelectorAll('.student-mobile-nav a')].map(a=>a.getAttribute('href')),
          context:!!document.querySelector('.cms-student-study-context'),
          notes:!!document.querySelector('[data-student-notes-panel]'),
          hasBlocked:!!document.querySelector('[data-cms-section="aula-nao-liberada"]')
        }));
        expect(cms.page&&cms.root&&cms.main&&cms.context&&cms.notes, 'material must use enrolled canonical CMS reading shell').toBe(true);
        expect(cms.hasBlocked, 'material cannot render an unreleased section').toBe(false);
        expect(cms.nav, 'material must retain global five-destination navigation').toEqual(['/aluno/','/aluno/agenda.php','/aluno/cursos.php','/aluno/caderno.php','/aluno/ferramentas.php']);
      }
      if(name==='inventory-movement'){await page.getByRole('button',{name:'Movimentar estoque'}).click();}
      if(name==='new-record'||name==='toolbox'){
        const selector=name==='new-record'?'.student-create-dialog':'.student-toolbox';
        await page.evaluate(sel=>{const d=document.querySelector(sel);if(d){if(d.open)d.removeAttribute('open');if(typeof d.showModal==='function')d.showModal();else d.setAttribute('open','');}},selector);
      }
      if(name==='home-feedback'){await expect(page.locator('[data-home-message]')).toBeVisible();await expect(page.getByRole('link',{name:/Ler retorno e revisar/})).toBeVisible();}
      if(name==='course-feedback'){await expect(page.getByText('Há algo para retomar',{exact:true})).toBeVisible();await expect(page.getByText('Revisão solicitada',{exact:true})).toBeVisible();}
      if(name==='questions-feedback'){await expect(page.getByRole('heading',{name:'Conversas de avaliação'})).toBeVisible();await expect(page.getByRole('heading',{name:'Conversas',exact:true})).toBeVisible();}
      if(name==='questions-list')await expect(page.getByRole('heading',{name:'Dúvidas',exact:true,level:1})).toBeVisible();
      if(name==='question-new'){await expect(page.getByRole('heading',{name:'Dúvidas',exact:true,level:1})).toBeVisible();await expect(page.locator('[data-question-new-panel] form')).toBeVisible();}
      if(name==='question-thread'){await expect(page.getByRole('heading',{name:'Segunda revelação'})).toBeVisible();await expect(page.locator('[data-student-local-key="question-thread"] form textarea[name="body"]')).toBeVisible();}
      if(name==='preparations'){
        await expect(page.getByRole('heading',{name:'Predefinições de revelação'})).toBeVisible();
        await expect(page.locator('.student-preparation-card')).toHaveCount(1);
        await expect(page.locator('.student-preparation-card form [name="action"][value="delete"]')).toHaveCount(1);
        await expect(page.locator('.student-preparation-card form [name="_csrf"]')).toHaveCount(1);
        await expect(page.getByRole('link',{name:'Abrir inventário'})).toHaveAttribute('href','/aluno/inventario.php');
        await expect(page.locator('.student-preparation-card .student-preparation-note')).toHaveCount(0);
        await expect(page.locator('.student-preparation-guidance')).toContainText('Não representam soluções em estoque');
      }
      if(name==='calibration-list'){
        await expect(page.getByRole('heading',{name:'Referências de calibração'})).toBeVisible();
        await expect(page.locator('[data-student-local-key="calibrations-list"] .student-simple-row')).toHaveCount(1);
        await expect(page.locator('[data-student-local-key="calibrations-list"] .student-simple-row-actions strong')).toContainText('03:00');
      }
      if(name==='preparation-editor'||name==='calibration-editor'){
        await expect(page.locator('.student-main > [data-student-editor] form')).toBeVisible();
        await expect(page.locator('nav.student-mobile-nav a[href="/aluno/ferramentas.php"]')).toHaveAttribute('aria-current','page');
      }
      if(name==='result-waiting')await expect(page.locator('#resultado .student-review-state strong')).toHaveText('Enviado para avaliação');
      if(name==='result-revision'){await expect(page.locator('#resultado .student-review-state strong')).toHaveText('Revisão solicitada');await expect(page.getByRole('button',{name:'Enviar revisão para avaliação'})).toBeVisible();await expect(page.locator('#resultado .student-message-list strong')).toHaveText('Professor');}
      if(name==='result-reviewed'){await expect(page.locator('#resultado .student-review-state strong')).toHaveText('Avaliação concluída');await expect(page.locator('#resultado textarea[name="notes"]')).toBeDisabled();}
      if(name==='compare-select'){await expect(page.getByText('Escolha o segundo registro',{exact:true})).toBeVisible();await expect(page.getByText('EI 200 — FeCl₃',{exact:true})).toBeVisible();await expect(page.locator('.student-research-picker-form input[name="id[]"][value="10"]')).toHaveCount(1);await expect(page.locator('.student-research-picker-form select[name="id[]"]')).toHaveCount(1);}
      if(name==='compare-records'){await expect(page.getByText('Diferenças registradas',{exact:true})).toBeVisible();await expect(page.getByText('Resultado observado',{exact:true})).toBeVisible();await expect(page.getByText('Criar próxima variação',{exact:true})).toBeVisible();await expect(page.locator('.student-research-result-card')).toHaveCount(2);await expect(page.locator('form.student-research-next-form input[name="_csrf"]')).toHaveCount(1);await expect(page.getByText('Etapa 1 · Tempo',{exact:true})).toBeVisible();}
      if(name==='research-derived'){
        await expect(page.getByText('Continuação de',{exact:false})).toBeVisible();await expect(page.getByText('Manter EI 400 e voltar a primeira revelação para 7 minutos.',{exact:false})).toBeVisible();
        const card=page.locator('[data-record-id="14"]'),menu=card.locator('.student-record-menu'),compareOrigin=menu.getByRole('link',{name:'Comparar com origem'});await expect(menu).toBeVisible();await expect(compareOrigin).toBeHidden();await menu.locator('summary').click();await expect(compareOrigin).toHaveAttribute('href','/aluno/comparar-processos.php?id%5B%5D=12&id%5B%5D=14');await expect(compareOrigin).toBeVisible();await menu.locator('summary').click();await expect(compareOrigin).toBeHidden();
      }
      if(name==='process-choice'){
        await expect(page.getByText('Associar roteiro',{exact:true})).toBeVisible();await expect(page.getByText('Adicionar etapa',{exact:true}).first()).toBeVisible();await expect(page.getByText('Movimentar estoque',{exact:true})).toBeVisible();await expect(page.getByText('O processamento já aconteceu?',{exact:false})).toHaveCount(0);
      }
      if(name==='process-plan'){
        await expect(page.getByText('0 de 9 marcadas',{exact:true})).toBeVisible();await expect(page.locator('.student-notebook-route-step')).toHaveCount(9);await expect(page.getByText('Abrir roteiro',{exact:true})).toBeVisible();await expect(page.getByText('Trocar roteiro-base',{exact:true})).toBeVisible();await expect(page.getByText('Editar',{exact:true}).first()).toBeHidden();await expect(page.getByText('Timer',{exact:true}).first()).toBeHidden();const firstStep=page.locator('#processamento .student-notebook-route-step').first();await firstStep.locator('summary').click();await expect(firstStep.getByRole('link',{name:'Editar'})).toBeVisible();await expect(firstStep.getByRole('link',{name:'Timer'})).toBeVisible();await firstStep.locator('summary').click();await expect(page.getByText('Abrir laboratório',{exact:false})).toHaveCount(0);
        await expect(page.locator('#processamento [name="action"][value="toggle_plan_step"]')).toHaveCount(9);
        await expect(page.locator('#processamento .student-notebook-route-step-actions a[href^="/aluno/processar.php?test=7"]')).toHaveCount(9);
      }
      if(name==='process-partial'){
        await expect(page.getByText('5 de 9 marcadas',{exact:true})).toBeVisible();await expect(page.locator('#processamento .student-notebook-route-step button').filter({hasText:'Desmarcar'})).toHaveCount(5);await expect(page.locator('#processamento .student-notebook-route-step button').filter({hasText:'Marcar ✓'})).toHaveCount(4);await expect(page.locator('#processamento details.student-notebook-route-detail[open]')).toHaveCount(0);await expect(page.getByText('Completar registro',{exact:false})).toHaveCount(0);
        await expect(page.locator('#processamento .student-notebook-route-step.is-done')).toHaveCount(5);
        await expect(page.locator('#processamento .student-notebook-route-step.is-done:nth-child(2)')).toHaveCount(0);
        await expect(page.locator('#processamento .student-notebook-route-step.is-done:nth-child(3)')).toHaveCount(1);
      }
      if(name==='step-editor'){
        await expect(page.getByText('Nenhuma outra etapa é alterada por isso.',{exact:false})).toBeVisible();
        await expect(page.getByText('Corrigir a sequência do processo',{exact:false})).toHaveCount(0);
        await expect(page.getByText('Remover esta etapa',{exact:true})).toBeVisible();
        await expect(page.locator('.student-step-editor form [name="_csrf"]')).toHaveCount(2);
        await expect(page.locator('.student-step-editor form [name="action"][value="save"]')).toHaveCount(1);
        await expect(page.locator('.student-step-editor form [name="action"][value="delete"]')).toHaveCount(1);
        await expect(page.locator('.student-step-editor form [name="registro"][value="7"]')).toHaveCount(2);
        await expect(page.locator('a.student-back')).toHaveAttribute('href','/aluno/teste.php?id=7#processamento');
      }
      if(name==='shared-record'){
        await expect(page.getByRole('heading',{name:'Avaliação e conversa'})).toBeVisible();
        await expect(page.locator('.student-main .student-review-grid article')).toHaveCount(2);
        await expect(page.locator('.student-main .student-process-summary li')).toHaveCount(3);
        await expect(page.locator('.student-main .student-thread article')).toHaveCount(2);
        await expect(page.locator('.student-main form')).toHaveCount(0);
        await expect(page.locator('.student-main button')).toHaveCount(0);
        await expect(page.locator('.student-main a.student-back')).toHaveAttribute('href','/aluno/caderno.php');
      }
      if(name==='delete-record'){
        await expect(page.locator('.student-danger-zone form [name="_csrf"]')).toHaveCount(1);
        await expect(page.locator('.student-danger-zone form [name="id"][value="7"]')).toHaveCount(1);
        await expect(page.getByRole('button',{name:'Excluir registro definitivamente'})).toBeVisible();
        await expect(page.locator('a.student-back')).toHaveAttribute('href','/aluno/teste.php?id=7');
      }
      if(name==='process-library'){await expect(page.locator('[data-process-saved-card]')).toHaveCount(2);await expect(page.getByRole('link',{name:'Abrir no laboratório'}).first()).toBeVisible();await expect(page.getByRole('button',{name:'Excluir'})).toBeHidden();await expect(page.getByText('Iniciar no laboratório',{exact:true})).toHaveCount(0);}
      if(name==='login'){await expect(page.locator('form input[name="mode"][value="login"]')).toHaveCount(1);await expect(page.locator('form input[name="mode"][value="activate"]')).toHaveCount(1);}
      if(name==='activation-password'){await expect(page.locator('input[name="privacy_ack"][required]')).toHaveCount(1);await expect(page.getByRole('button',{name:'Ativar conta'})).toBeVisible();}
      if(name==='password-change'){await expect(page.locator('input[name="primeiro"][value="0"]')).toHaveCount(1);await expect(page.getByRole('button',{name:'Salvar senha'})).toBeVisible();}
      if(name==='profile'){
        await expect(page.locator('[data-student-profile-form]')).toHaveCount(1);
        await expect(page.locator('[data-profile-contact]')).not.toHaveAttribute('open','');
        await expect(page.locator('[data-profile-address]')).not.toHaveAttribute('open','');
      }
      if(name==='process-editor'){await expect(page.locator('[data-operational-template-editor]')).toHaveCount(1);await expect(page.locator('.student-process-step-card')).toHaveCount(9);await expect(page.getByText('Abrir no laboratório',{exact:true})).toBeVisible();await expect(page.getByText('Iniciar no laboratório',{exact:true})).toHaveCount(0);}
      if(name==='lab-runner'){
        await expect(page.locator('[data-operational-runner-view]')).toHaveCount(1);
        await expect(page.locator('[data-runner-endpoint="/aluno/processar.php?test=7"]')).toHaveCount(1);
        await expect(page.locator('.student-lab-stage-nav a')).toHaveCount(9);await expect(page.getByRole('button',{name:'Marcar como concluída',exact:true})).toBeVisible();await expect(page.getByText('Ir para próxima etapa',{exact:false})).toHaveCount(0);
      }
      if(device==='phone'&&name!=='login'&&name!=='activation-password'){
        const mobileChrome=await page.evaluate(()=>{const nav=document.querySelector('.student-mobile-nav'),top=document.querySelector('.student-topbar,.cms-topbar'),reserve=document.querySelector('.student-shell')||document.body,reading=document.querySelector('.student-main');if(!nav||!top||!reserve)return null;const ns=getComputedStyle(nav),ts=getComputedStyle(top),rs=getComputedStyle(reserve),nr=nav.getBoundingClientRect(),mr=reading?.getBoundingClientRect();return {navDisplay:ns.display,topDisplay:ts.display,navPosition:ns.position,navTop:nr.top,navBottom:nr.bottom,mainBottom:mr?.bottom??null,navHeight:nr.height,viewportHeight:innerHeight,reservedBottom:parseFloat(rs.paddingBottom||'0'),scrollPaddingBottom:parseFloat(getComputedStyle(document.documentElement).scrollPaddingBottom||'0')};});
        expect(mobileChrome,`${name}: authenticated mobile surface must expose global chrome`).not.toBeNull();
        expect(mobileChrome.navDisplay,`${name}: mobile navigation unexpectedly hidden`).not.toBe('none');
        expect(mobileChrome.topDisplay,`${name}: topbar unexpectedly hidden`).not.toBe('none');
        expect(Math.abs(mobileChrome.navBottom-mobileChrome.viewportHeight),`${name}: nav must touch bottom viewport edge`).toBeLessThanOrEqual(1);
        if(mobileChrome.navPosition==='fixed'){
          expect(mobileChrome.reservedBottom,`${name}: fixed navigation must reserve its footprint`).toBeGreaterThanOrEqual(mobileChrome.navHeight);
          expect(mobileChrome.scrollPaddingBottom,`${name}: fixed navigation must reserve scroll padding`).toBeGreaterThanOrEqual(mobileChrome.navHeight);
        }else{
          // The mobile-first shell uses a real grid row for the nav. The scroll
          // region must physically terminate above it; no fake padding is needed.
          expect(mobileChrome.mainBottom,`${name}: grid scroll region missing`).not.toBeNull();
          expect(mobileChrome.mainBottom,`${name}: content overlaps in-flow navigation`).toBeLessThanOrEqual(mobileChrome.navTop+1);
        }
      }
      if(path.startsWith('student-secondary-screens-audit.html')){
        expect(pageErrors,`${name}: JS failure inside real-layout fixture`).toEqual([]);
        if(!['question-new','question-thread'].includes(name))
          await expect(page.getByRole('alert').filter({hasText:'Não foi possível enviar a resposta.'})).toHaveCount(0);
      }
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth);expect(overflow,`${name} horizontal overflow on ${device}`).toBeLessThanOrEqual(1);
      await page.screenshot({path:`student-visual-audit/complete/${device}/${name}.png`,fullPage:true,animations:'disabled'});
      if(device==='phone'&&name!=='login'&&name!=='activation-password'){
        const root=page.locator(name==='material'?'.cms-student-reading':'.student-main');
        await expect(root,`${name}: mobile scroll region missing`).toBeVisible();
        const bounds=await page.evaluate(kind=>{
          const node=document.querySelector(kind==='material'?'.cms-student-reading':'.student-main');
          const nav=document.querySelector('.student-mobile-nav');
          if(!node||!nav)return null;
          const reading=node.getBoundingClientRect(),bar=nav.getBoundingClientRect();
          return{bottom:reading.bottom,navTop:bar.top,viewport:innerHeight,pageHeight:document.documentElement.scrollHeight,scrollHeight:node.scrollHeight,clientHeight:node.clientHeight};
        },name);
        expect(bounds,`${name}: missing global nav or scroll root`).not.toBeNull();
        expect(bounds.bottom,`${name}: content overlaps navigation`).toBeLessThanOrEqual(bounds.navTop+1);
        expect(bounds.pageHeight,`${name}: page must not scroll behind fixed nav`).toBeLessThanOrEqual(bounds.viewport+1);
        if(bounds.scrollHeight>bounds.clientHeight+8){
          await root.evaluate(el=>el.scrollTop=(el.scrollHeight-el.clientHeight)/2);
          await page.screenshot({path:`student-visual-audit/complete/phone/${name}-middle.png`,animations:'disabled'});
          await root.evaluate(el=>el.scrollTop=el.scrollHeight);
          await page.screenshot({path:`student-visual-audit/complete/phone/${name}-end.png`,animations:'disabled'});
          await root.evaluate(el=>el.scrollTop=0);
        }
      }
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
        // The CMS skip-link is intentionally offscreen until keyboard focus.
        // Pointer hit-testing it as a permanently visible button is invalid;
        // focus accessibility is verified independently below.
        if(item.skip||(name==='material'&&await locator.evaluate(el=>el.matches('a.skip-link')&&!el.matches(':focus'))))continue;
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

for(const [device,width,height] of [['phone',390,844],['desktop',1280,820]]){
  test(`converting a saved selection note to a page note preserves its text — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});
    await page.route('**/aluno/material-anotacao.php',route=>{
      const form=route.request().postData()||'';
      expect(form).toContain('name="annotation_action"');
      expect(form).toContain('detach');
      return route.fulfill({status:200,contentType:'application/json',body:JSON.stringify({
        ok:true,action:'detach',
        annotation:{id:1,anchorType:'page',sectionKey:'introducao',body:'Nota vinculada ao processo e à exposição.',
          exact:'',prefix:'',suffix:'',blockKey:'',start:null,end:null,sourceBlockHash:''}
      })});
    });
    await page.goto(fixture('student-inline-annotations.html'),{waitUntil:'networkidle'});
    const panel=page.locator('[data-student-notes-panel]');
    await panel.locator('summary').first().click();
    await panel.locator('[data-annotation-edit="1"]').click();
    const original=panel.locator('[data-annotation-item="1"]');
    await expect(original).toHaveClass(/is-selection/);
    await expect(panel.locator(':scope > summary b')).toHaveText('3');
    await original.getByRole('button',{name:'Tornar geral'}).click();
    await expect(panel.locator(':scope > summary b')).toHaveText('3');
    const converted=panel.locator('[data-annotation-item="1"]');
    await expect(converted).toHaveCount(1);
    await expect(converted).toHaveClass(/is-page/);
    await expect(converted).toHaveClass(/is-active/);
    await expect(converted.locator('textarea[name="body"]')).toHaveValue('Nota vinculada ao processo e à exposição.');
    await expect(converted.locator('.student-note-quote')).toHaveCount(0);
    await expect(converted.getByRole('button',{name:'Tornar geral'})).toHaveCount(0);
    await panel.screenshot({path:`student-visual-audit/notes/${device}-detached-to-page.png`,animations:'disabled'});
    await panel.locator('[data-student-notes-back-button]').click();
    await expect(converted.locator('.student-note-body-preview')).toContainText('Nota vinculada ao processo');
    await expect(converted.locator('.student-note-edit-trigger')).toBeVisible();
  });
}

for(const [device,width,height] of [['phone',390,844],['desktop',1440,1100]]){
  test(`real new-question form: disclosure, validation, all visibility scopes and keyboard focus — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});
    await page.goto(fixture('student-questions-operational.php?state=list'),{waitUntil:'networkidle'});
    await ensureCanonicalShellStyles(page);
    // The real student panel controller must be used rather than a fixture-only imitation.
    const trigger=page.locator('[data-question-new-toggle]');
    const panel=page.locator('[data-question-new-panel]');
    const form=panel.locator('form');
    await expect(panel).toBeHidden();
    await trigger.click();
    await expect(panel).toBeVisible();
    await expect(form.locator('input[name="visibility"]')).toHaveCount(3);
    await expect(form.locator('input[name="visibility"]:checked')).toHaveValue('private');
    await expect(form.locator('[name="title"]')).toHaveAttribute('required','');
    await expect(form.locator('[name="title"]')).toHaveAttribute('maxlength','180');
    await expect(form.locator('[name="body"]')).toHaveAttribute('required','');
    await form.locator('[data-question-new-cancel]').click();
    await expect(panel).toBeHidden();
    await trigger.click();
    await expect(panel).toBeVisible();
    await expect(trigger).toHaveAttribute('aria-expanded','true');
    await expect(form.locator('select[name="topic"]')).toBeFocused();

    await form.locator('[name="title"]').fill('');
    await form.locator('[name="body"]').fill('');
    expect(await form.evaluate(el=>el.checkValidity())).toBe(false);
    await form.locator('[name="title"]').fill('Pergunta sobre a segunda revelação');
    await form.locator('[name="body"]').fill('Posso reutilizar o banho da primeira revelação?');
    expect(await form.evaluate(el=>el.checkValidity())).toBe(true);

    for(const [scope,label] of [
      ['private','Somente eu e o professor'],
      ['cohort','Minha turma'],
      ['course','Todas as turmas deste curso']
    ]){
      const radio=form.locator(`input[name="visibility"][value="${scope}"]`);
      await expect(form.getByText(label,{exact:true})).toBeVisible();
      await radio.scrollIntoViewIfNeeded();
      await radio.locator('xpath=..').click();
      await expect(radio).toBeChecked();
      await expect(form.locator('input[name="visibility"]:checked')).toHaveCount(1);
      await page.screenshot({path:`student-visual-audit/questions/${device}-new-question-${scope}.png`,animations:'disabled'});
    }
    await expect(form.getByRole('button',{name:'Publicar dúvida'})).toBeVisible();
    await form.locator('[data-question-new-cancel]').click();
    await expect(panel).toBeHidden();
    await expect(trigger).toHaveAttribute('aria-expanded','false');
  });
}

for(const [device,width,height] of [['phone',390,844],['desktop',1440,1100]]){
  test(`question list distinguishes private, cohort and course-shared conversations — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});
    await page.goto(fixture('student-questions-operational.php?state=list'),{waitUntil:'networkidle'});
    await ensureCanonicalShellStyles(page);
    await expect(page.locator('nav.student-mobile-nav a[href="/aluno/cursos.php"]')).toHaveAttribute('aria-current','page');
    const items=page.locator('.student-test-row');
    await expect(items).toHaveCount(3);
    for(const [name,title] of [
      ['Turma','Como avaliar as sombras no positivo?'],
      ['Privada','Dúvida sobre o banho de amônia'],
      ['Curso','Exposição com filme ortocromático']
    ]){
      const row=items.filter({hasText:title});
      await expect(row).toHaveCount(1);
      await expect(row.locator('.student-status',{hasText:name})).toBeVisible();
    }
    await page.screenshot({path:`student-visual-audit/questions/${device}-questions-three-scopes.png`,fullPage:true,animations:'disabled'});
  });
}

for(const [device,width,height] of [['phone',390,844],['desktop',1440,1100]]){
  test(`failed question publication retains the complete draft — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});
    await page.goto(fixture('student-questions-operational.php?state=failed-create'),{waitUntil:'networkidle'});
    await ensureCanonicalShellStyles(page);
    const alert=page.getByRole('alert');
    await expect(alert).toContainText('Não foi possível publicar');
    await expect(alert).toBeFocused();
    await expect(alert).toBeInViewport();
    expect(await alert.evaluate(el=>el.closest('[data-question-new-panel]')!==null)).toBe(true);
    expect(await alert.evaluate(el=>el.nextElementSibling?.tagName)).toBe('FORM');
    expect(await alert.evaluate(el=>el.nextElementSibling.getBoundingClientRect().top-el.getBoundingClientRect().bottom)).toBeGreaterThanOrEqual(12);
    const open=page.locator('[data-question-new-toggle]'),panel=page.locator('[data-question-new-panel]'),form=panel.locator('form');
    await expect(open).toHaveAttribute('aria-expanded','true');
    await expect(panel).toBeVisible();
    await expect(form.locator('[name="topic"]')).toHaveValue('Química');
    await expect(form.locator('[name="test_id"]')).toHaveValue('23');
    await expect(form.locator('[name="title"]')).toHaveValue('Dúvida sobre o banho');
    await expect(form.locator('[name="body"]')).toHaveValue('Meu rascunho sobre diluição e amônia precisa permanecer no formulário.');
    await expect(form.locator('[name="visibility"]:checked')).toHaveValue('course');
    await expect(form.getByRole('button',{name:'Publicar dúvida'})).toBeVisible();
    await page.screenshot({path:`student-visual-audit/questions/${device}-create-error-draft.png`,animations:'disabled'});
  });
  test(`failed reply stays in its conversation and preserves text — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});
    await page.goto(fixture('student-questions-operational.php?state=failed-reply&id=31'),{waitUntil:'networkidle'});
    await ensureCanonicalShellStyles(page);
    const alert=page.getByRole('alert');
    await expect(alert).toContainText('Não foi possível enviar a resposta');
    await expect(alert).toBeFocused();
    await expect(alert).toBeInViewport();
    expect(await alert.evaluate(el=>el.parentElement?.getAttribute('data-student-local-key'))).toBe('question-thread');
    expect(await alert.evaluate(el=>el.nextElementSibling?.getAttribute('data-local-success'))).toBe('reset');
    expect(await alert.evaluate(el=>el.nextElementSibling.getBoundingClientRect().top-el.getBoundingClientRect().bottom)).toBeGreaterThanOrEqual(12);
    await expect(page.getByRole('heading',{name:'Segunda revelação'})).toBeVisible();
    const form=page.locator('form[data-local-refresh="question-thread"][data-local-success="reset"]');
    await expect(form.locator('[name="question_id"]')).toHaveValue('31');
    await expect(form.locator('[name="body"]')).toHaveValue('Minha resposta sobre segunda revelação continua aqui após a falha.');
    await expect(form.getByRole('button',{name:'Enviar resposta'})).toBeVisible();
    await page.screenshot({path:`student-visual-audit/questions/${device}-reply-error-draft.png`,animations:'disabled'});
  });
}


for(const [device,width,height] of [['phone',390,844],['desktop',1440,1100]]){
  test(`question list empty, with operational new-question composer — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});
    await page.goto(fixture('student-questions-operational.php?state=empty'),{waitUntil:'networkidle'});
    await ensureCanonicalShellStyles(page);
    await expect(page.getByText('Ainda não há dúvidas nesta turma ou curso.')).toBeVisible();
    const trigger=page.locator('[data-question-new-toggle]'),panel=page.locator('[data-question-new-panel]');
    await expect(panel).toBeHidden();
    await trigger.click();
    await expect(panel).toBeVisible();
    await expect(panel.locator('[name="title"]')).toHaveAttribute('required','');
    await expect(panel.locator('[name="body"]')).toHaveAttribute('required','');
    await expect(panel.locator('[name="visibility"]')).toHaveCount(3);
    await page.screenshot({path:`student-visual-audit/questions/${device}-empty-new-question-open.png`,animations:'disabled'});
  });
  test(`question list large, scroll beginning middle and final controls — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});
    await page.goto(fixture('student-questions-operational.php?state=many'),{waitUntil:'networkidle'});
    await ensureCanonicalShellStyles(page);
    const items=page.locator('.student-test-list .student-test-row');
    await expect(items).toHaveCount(31);
    await page.screenshot({path:`student-visual-audit/questions/${device}-many-start.png`,animations:'disabled'});
    await items.nth(15).scrollIntoViewIfNeeded();
    await expect(items.nth(15)).toBeInViewport();
    await page.screenshot({path:`student-visual-audit/questions/${device}-many-middle.png`,animations:'disabled'});
    await items.last().scrollIntoViewIfNeeded();
    await expect(items.last()).toBeInViewport();
    const finalBounds=await items.last().boundingBox();
    if(!finalBounds)throw new Error('Last question cannot be reached');
    if(device==='phone'){
      const bottomBar=await page.locator('.student-mobile-nav').boundingBox();
      if(bottomBar)expect(finalBounds.y+finalBounds.height).toBeLessThanOrEqual(bottomBar.y+1);
    }
    await page.screenshot({path:`student-visual-audit/questions/${device}-many-end.png`,animations:'disabled'});
  });
  test(`question list includes conditional evaluation discussions — ${device}`,async({page})=>{
    await page.setViewportSize({width,height});
    await page.goto(fixture('student-questions-operational.php?state=evaluations'),{waitUntil:'networkidle'});
    await ensureCanonicalShellStyles(page);
    await expect(page.getByRole('heading',{name:'Conversas de avaliação'})).toBeVisible();
    const evaluation=page.locator('.student-evaluation-conversations .student-test-row');
    await expect(evaluation).toHaveAttribute('href','/aluno/teste.php?id=17&view=review');
    await evaluation.scrollIntoViewIfNeeded();
    await page.screenshot({path:`student-visual-audit/questions/${device}-evaluation-conversations.png`,animations:'disabled'});
  });
  for(const state of ['linked-shared','linked-private']){
    test(`question linked record is ${state} — ${device}`,async({page})=>{
      await page.setViewportSize({width,height});
      await page.goto(fixture(`student-questions-operational.php?state=${state}&id=1`),{waitUntil:'networkidle'});
      await ensureCanonicalShellStyles(page);
      const link=page.getByRole('link',{name:'Abrir registro relacionado'});
      if(state==='linked-private')await expect(link).toHaveCount(0);
      else await expect(link).toHaveAttribute('href','/aluno/teste-compartilhado.php?id=103');
      await page.screenshot({path:`student-visual-audit/questions/${device}-${state}.png`,animations:'disabled'});
    });
  }
}


for(const width of [320,390]){
  for(const [surface,url] of [
    ['student-shell','student-secondary-screens-audit.html?screen=profile'],
    ['material-reader','student-material-context.html'],
  ]){
    test(`mobile navigation is legible, separated and reachable at ${width}px — ${surface}`,async({page})=>{
      await page.setViewportSize({width,height:720});
      await page.goto(fixture(url),{waitUntil:'networkidle'});
      if(surface==='student-shell')await ensureCanonicalShellStyles(page);
      const nav=page.locator('.student-mobile-nav'),links=nav.locator('a');
      await expect(nav).toBeVisible();
      await expect(links).toHaveCount(5);
      const items=await links.evaluateAll(nodes=>nodes.map(node=>{
        const box=node.getBoundingClientRect(),style=getComputedStyle(node),x=box.left+box.width/2,y=box.top+box.height/2;
        return {label:node.textContent.trim(),fontSize:parseFloat(style.fontSize),width:box.width,height:box.height,left:box.left,right:box.right,bottom:box.bottom,contentWidth:node.clientWidth,scrollWidth:node.scrollWidth,hittable:node===document.elementFromPoint(x,y)||node.contains(document.elementFromPoint(x,y))};
      }));
      for(const item of items){
        expect(item.fontSize,`${surface}: ${item.label} has tiny text`).toBeGreaterThanOrEqual(11);
        expect(item.height,`${surface}: ${item.label} has an undersized touch target`).toBeGreaterThanOrEqual(48);
        expect(item.scrollWidth,`${surface}: ${item.label} is clipped horizontally`).toBeLessThanOrEqual(item.contentWidth+1);
        expect(item.hittable,`${surface}: ${item.label} is not tappable`).toBe(true);
        expect(item.left).toBeGreaterThanOrEqual(0);
        expect(item.right).toBeLessThanOrEqual(width+1);
      }
      for(let i=1;i<items.length;i++)expect(items[i].left).toBeGreaterThanOrEqual(items[i-1].right-1);
      expect(Math.abs(items[items.length-1].bottom-720)).toBeLessThanOrEqual(2);
      await page.screenshot({path:`student-visual-audit/navigation/phone-${width}-${surface}.png`,animations:'disabled'});
    });
  }
}
