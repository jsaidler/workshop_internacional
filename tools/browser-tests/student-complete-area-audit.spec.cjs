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
      if(device==='phone'&&name!=='login'&&name!=='activation-password'&&name!=='material'){
        const mobileChrome=await page.evaluate(()=>{const nav=document.querySelector('.student-mobile-nav'),top=document.querySelector('.student-topbar'),shell=document.querySelector('.student-shell');if(!nav||!top||!shell)return null;const ns=getComputedStyle(nav),ts=getComputedStyle(top),shs=getComputedStyle(shell),nr=nav.getBoundingClientRect();return {navDisplay:ns.display,topDisplay:ts.display,navPosition:ns.position,topPosition:ts.position,navBottom:nr.bottom,navHeight:nr.height,viewportHeight:innerHeight,shellPaddingBottom:parseFloat(shs.paddingBottom||'0')};});
        if(mobileChrome){expect(mobileChrome.navDisplay,`${name}: mobile navigation unexpectedly hidden`).not.toBe('none');expect(mobileChrome.topDisplay,`${name}: topbar unexpectedly hidden`).not.toBe('none');expect(mobileChrome.navPosition,`${name}: mobile nav must stay anchored to viewport`).toBe('fixed');expect(Math.abs(mobileChrome.navBottom-mobileChrome.viewportHeight),`${name}: mobile nav must touch bottom viewport edge`).toBeLessThanOrEqual(1);expect(mobileChrome.shellPaddingBottom,`${name}: shell must reserve the mobile nav footprint`).toBeGreaterThanOrEqual(mobileChrome.navHeight);expect(mobileChrome.topPosition,`${name}: topbar remains sticky`).toBe('static');}
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
