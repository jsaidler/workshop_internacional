const {test,expect}=require('@playwright/test');
const {execFileSync,spawn}=require('node:child_process');
const fs=require('node:fs');
const path=require('node:path');
const os=require('node:os');
const net=require('node:net');

const sourceRoot=path.resolve(__dirname,'../..');
const seedScript=path.join(sourceRoot,'tools/browser-fixture/student-authenticated-material-question-seed.php');
let sandbox,server,origin,seedData,serverLog;
const password='fixture-password-123!';

async function freePort(){
  return new Promise((resolve,reject)=>{
    const listener=net.createServer();
    listener.once('error',reject);
    listener.listen(0,'127.0.0.1',()=>{
      const port=listener.address().port;
      listener.close(error=>error?reject(error):resolve(port));
    });
  });
}
async function awaitServer(url){
  for(let attempt=0;attempt<80;attempt++){
    try{const response=await fetch(url);if(response.status===200)return;}catch(_){}
    await new Promise(resolve=>setTimeout(resolve,150));
  }
  throw new Error('Disposable real-PHP application failed to start.');
}
function snapshot(){
  return JSON.parse(execFileSync('php',[seedScript,sandbox,'snapshot'],{encoding:'utf8',timeout:45000}));
}
async function signIn(page,email){
  await page.goto(origin+'/aluno/login.php',{waitUntil:'domcontentloaded'});
  const login=page.locator('form').filter({has:page.locator('input[name="mode"][value="login"]')});
  await expect(login).toBeVisible();
  await login.locator('input[name="email"]').fill(email);
  await login.locator('input[name="password"]').fill(password);
  await Promise.all([page.waitForURL(/\/aluno\/(?:\?|$)/),login.getByRole('button',{name:'Entrar'}).click()]);
}
function materialUrl(cohort){
  return origin+'/?page=material&lang=pt-br&cohort='+cohort;
}

test.describe('authenticated Material → annotation → question → Material, real SQLite and routes',()=>{
  test.describe.configure({mode:'serial'});
  test.setTimeout(120000);
  test.beforeEach(async()=>{
    sandbox=fs.mkdtempSync(path.join(os.tmpdir(),'student-material-journey-'));
    try{
      // The real bootstrap resolves SQLite and config relative to its own
      // app directory; this is an isolated installation, not a mocked DB.
      for(const name of ['app','aluno','assets','data','migrations','template','index.php']){
        const from=path.join(sourceRoot,name);
        if(fs.existsSync(from))fs.cpSync(from,path.join(sandbox,name),{recursive:true});
      }
      fs.mkdirSync(path.join(sandbox,'config'),{recursive:true});
      fs.mkdirSync(path.join(sandbox,'storage'),{recursive:true});
      fs.writeFileSync(path.join(sandbox,'storage','installed.lock'),'CI isolated installation');
      fs.writeFileSync(path.join(sandbox,'storage','database.sqlite'),'');
      fs.writeFileSync(path.join(sandbox,'config','local.php'),"<?php return ['app_secret'=>'"+'a'.repeat(64)+"','ip_hash_secret'=>'"+'b'.repeat(64)+"','site_name'=>'CI isolated installation','timezone'=>'UTC','public_url'=>''];\n");
      seedData=JSON.parse(execFileSync('php',[seedScript,sandbox,'seed'],{encoding:'utf8',timeout:60000}));
      const port=await freePort();
      origin='http://127.0.0.1:'+port;
      serverLog=fs.openSync(path.join(sandbox,'php-server.log'),'w');
      server=spawn('php',['-S','127.0.0.1:'+port,'-t',sandbox],{cwd:sandbox,stdio:['ignore',serverLog,serverLog]});
      await awaitServer(origin+'/aluno/login.php');
      fs.mkdirSync('student-visual-audit/authenticated-material-question',{recursive:true});
    }catch(error){
      if(sandbox&&fs.existsSync(path.join(sandbox,'php-server.log'))){
        error.message+='\n'+fs.readFileSync(path.join(sandbox,'php-server.log'),'utf8').slice(-3000);
      }
      throw error;
    }
  });
  test.afterEach(async()=>{
    server?.kill('SIGTERM');
    if(serverLog!==undefined)fs.closeSync(serverLog);
    if(sandbox)fs.rmSync(sandbox,{recursive:true,force:true,maxRetries:8,retryDelay:100});
  });

  test('real login, save, cross-cohort navigation, private scope and revocation',async({browser})=>{
    const context=await browser.newContext({viewport:{width:390,height:844}});
    const page=await context.newPage();
    try{
      const beforeAuth=await page.goto(materialUrl('journey-b'),{waitUntil:'domcontentloaded'});
      expect(beforeAuth?.status()).toBe(200);
      await expect(page).toHaveURL(/\/aluno\/login.php/);
      await signIn(page,'journey-owner@example.invalid');
      const material=await page.goto(materialUrl('journey-b'),{waitUntil:'networkidle'});
      expect(material?.status()).toBe(200);
      await expect(page.locator('body')).toHaveClass(/cms-student-material/);
      const panel=page.locator('[data-student-notes-panel]');
      await expect(panel).toHaveCount(1);
      await panel.locator(':scope > summary').click();
      const general=panel.locator('[data-student-note-new]');
      await general.locator(':scope > summary').click();
      const form=general.locator('form');
      // Invalid question title must roll back the new annotation as well.
      const invalid=await form.evaluate(async element=>{
        const data=new FormData(element);
        data.set('annotation_action','create_page');
        data.set('body','Rascunho que não pode ficar parcialmente salvo');
        data.set('create_question','1');
        data.set('question_title','');
        const response=await fetch(element.action,{method:'POST',body:data,credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
        return {status:response.status,payload:await response.json()};
      });
      expect(invalid.status).toBe(400);
      expect(invalid.payload.ok).toBe(false);
      expect(snapshot().notes).toHaveLength(0);
      expect(snapshot().questions).toHaveLength(0);
      await form.locator('textarea[name="body"]').fill('Qual o efeito da exposição no positivo?');
      await form.locator('[data-note-question] > summary').click();
      await form.locator('input[name="question_title"]').fill('Relação entre exposição e positivo');
      await form.locator('input[name="question_visibility"][value="private"]').check();
      await form.getByRole('button',{name:'Salvar e publicar dúvida'}).click();

      const note=panel.locator('[data-annotation-item]');
      await expect(note).toHaveCount(1);
      await expect(note).toContainText('Qual o efeito da exposição no positivo?');
      let persisted=snapshot();
      expect(persisted.notes).toHaveLength(1);
      expect(persisted.questions).toHaveLength(1);
      expect(persisted.questions[0].cohort_id).toBe(seedData.cohorts['journey-b']);
      expect(persisted.questions[0].source_annotation_id).toBe(persisted.notes[0].id);
      const questionId=Number(persisted.questions[0].id);

      await note.locator('[data-annotation-edit]').click();
      const link=note.getByRole('link',{name:'Ver dúvida'});
      await expect(link).toHaveAttribute('href','/aluno/duvidas.php?cohort=journey-b&id='+questionId);
      await link.click();
      await expect(page).toHaveURL(new RegExp('duvidas.php\\?cohort=journey-b&id='+questionId));
      await expect(page.getByRole('heading',{name:'Relação entre exposição e positivo'})).toBeVisible();
      const backToPage=page.getByRole('link',{name:/Abrir página de origem no material/});
      await expect(backToPage).toBeVisible();
      await backToPage.click();
      await expect(page.locator('[data-annotation-item]')).toHaveCount(1);
      await expect(page.locator('[data-annotation-item]')).toContainText('Qual o efeito da exposição no positivo?');
      await page.locator('[data-student-notes-panel] > summary').click();
      await page.locator('[data-annotation-item] [data-annotation-edit]').click();
      await page.screenshot({path:'student-visual-audit/authenticated-material-question/phone-return.png',animations:'disabled'});

      // The note is personal to a CMS page; its private question belongs to B.
      await page.goto(materialUrl('journey-a'),{waitUntil:'networkidle'});
      await panel.locator(':scope > summary').click();
      await note.locator('[data-annotation-edit]').click();
      await expect(link).toHaveAttribute('href','/aluno/duvidas.php?cohort=journey-b&id='+questionId);

      // Peer is enrolled in A of the same course, but cannot read a private
      // note or a private question belonging to the owner.
      const peerContext=await browser.newContext({viewport:{width:390,height:844}});
      try{
        const peer=await peerContext.newPage();
        await signIn(peer,'journey-peer@example.invalid');
        await peer.goto(materialUrl('journey-a'),{waitUntil:'networkidle'});
        await expect(peer.locator('[data-annotation-item]')).toHaveCount(0);
        // Obtain a real CSRF token from the Material before leaving that
        // document. Dúvidas has no annotation panel or annotation CSRF input.
        const token=await peer.locator('[data-student-notes-panel] input[name="_csrf"]').first().inputValue();
        await peer.goto(origin+'/aluno/duvidas.php?cohort=journey-a&id='+questionId);
        await expect(peer.getByRole('heading',{name:'Relação entre exposição e positivo'})).toHaveCount(0);
        // Another authenticated student cannot mutate the author's note by ID.
        const forged=await peer.evaluate(async payload=>{
          const data=new FormData();
          for(const [key,value] of Object.entries(payload))data.set(key,String(value));
          const response=await fetch('/aluno/material-anotacao.php',{method:'POST',body:data,credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
          return {status:response.status,payload:await response.json()};
        },{_csrf:token,page_id:seedData.pageId,cohort:'journey-a',annotation_id:persisted.notes[0].id,annotation_action:'update',body:'Tentativa indevida'});
        expect(forged.status).toBe(400);
        expect(forged.payload.ok).toBe(false);
      }finally{await peerContext.close();}

      // Revoke B in the on-disk database without logging the owner out.
      execFileSync('php',[seedScript,sandbox,'revoke'],{encoding:'utf8',timeout:45000});
      await page.goto(materialUrl('journey-a'),{waitUntil:'networkidle'});
      await panel.locator(':scope > summary').click();
      await note.locator('[data-annotation-edit]').click();
      await expect(link).toHaveCount(0);
      await expect(note.locator('.student-note-linked-unavailable')).toContainText('acesso indisponível');
      await expect(note.locator('[data-note-question]')).toHaveCount(0);
      await page.screenshot({path:'student-visual-audit/authenticated-material-question/phone-revoked.png',animations:'disabled'});
      for(const width of [320,1440]){
        await page.setViewportSize({width,height:width===320?720:900});
        await expect(note.locator('.student-note-linked-unavailable')).toBeVisible();
        await page.screenshot({path:'student-visual-audit/authenticated-material-question/'+width+'-revoked.png',animations:'disabled'});
      }
      persisted=snapshot();
      expect(persisted.questions).toHaveLength(1);
      expect(persisted.notes).toHaveLength(1);
    }finally{await context.close();}
  });

  test('turning a linked selected note into a general note preserves question access or unavailability immediately',async({browser})=>{
    const context=await browser.newContext({viewport:{width:390,height:844}});
    const page=await context.newPage();
    try{
      await signIn(page,'journey-owner@example.invalid');
      await page.goto(materialUrl('journey-b'),{waitUntil:'networkidle'});
      const panel=page.locator('[data-student-notes-panel]');
      const token=await panel.locator('[data-inline-note-compose] input[name="_csrf"]').inputValue();
      const blockKey=await page.locator('[data-student-note-context="investigacao"] p[data-student-anchor-block]').getAttribute('data-student-anchor-block');
      expect(blockKey).toBeTruthy();
      const pageRevision=await panel.getAttribute('data-student-page-revision');

      // Create through the real authenticated endpoint, with genuine anchor
      // data and two questions originating in cohort B. The next interaction
      // is performed through the unmodified Material editor.
      const createSelected=async(suffix)=>{
        const created=await page.evaluate(async data=>{
          const body=new FormData();
          for(const [key,value] of Object.entries(data))body.set(key,String(value));
          const response=await fetch('/aluno/material-anotacao.php',{
            method:'POST',credentials:'same-origin',body,
            headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}
          });
          return {status:response.status,payload:await response.json()};
        },{
          _csrf:token,page_id:seedData.pageId,return_to:'/?page=material&lang=pt-br&cohort=journey-b',
          cohort:'journey-b',annotation_action:'create_selection',
          section_key:'investigacao',block_key:blockKey,
          quote_exact:'Exposição',quote_prefix:'',quote_suffix:' e revelação',
          start_offset:0,end_offset:9,source_block_hash:'',source_page_revision:pageRevision||'',
          body:'Nota vinculada '+suffix,create_question:1,
          question_title:'Dúvida vinculada '+suffix,question_visibility:'private'
        });
        expect(created.status).toBe(200);
        expect(created.payload.ok).toBe(true);
        expect(created.payload.annotation.anchorType).toBe('selection');
        expect(created.payload.question_url).toMatch(/^\/aluno\/duvidas.php\?cohort=journey-b&id=\d+$/);
        return {id:created.payload.annotation.id,questionId:created.payload.question.id};
      };
      const first=await createSelected('acessível');
      const second=await createSelected('revogada');
      expect(snapshot().notes).toHaveLength(2);
      expect(snapshot().questions).toHaveLength(2);

      // The same CMS page is available through A, while both questions
      // originated in B. Detaching must not manufacture a second question.
      await page.goto(materialUrl('journey-a'),{waitUntil:'networkidle'});
      await panel.locator(':scope > summary').click();
      const firstNote=panel.locator('[data-annotation-item="'+first.id+'"]');
      await firstNote.locator('[data-annotation-edit]').click();
      await expect(firstNote.getByRole('link',{name:'Ver dúvida'})).toHaveAttribute(
        'href','/aluno/duvidas.php?cohort=journey-b&id='+first.questionId
      );
      const firstResponse=page.waitForResponse(response=>
        response.url().includes('/aluno/material-anotacao.php')
        &&(response.request().postData()||'').includes('name="annotation_action"')
        &&(response.request().postData()||'').includes('detach')
      );
      await firstNote.getByRole('button',{name:'Tornar geral'}).click();
      const firstJson=await (await firstResponse).json();
      expect(firstJson.annotation.anchorType).toBe('page');
      expect(firstJson.question.id).toBe(first.questionId);
      expect(firstJson.question_url).toBe('/aluno/duvidas.php?cohort=journey-b&id='+first.questionId);
      await expect(firstNote).toHaveClass(/is-page/);
      await expect(firstNote.getByRole('link',{name:'Ver dúvida'})).toHaveAttribute('href',firstJson.question_url);
      await expect(firstNote.locator('[data-note-question]')).toHaveCount(0);
      await page.screenshot({path:'student-visual-audit/authenticated-material-question/phone-detached-linked.png',animations:'disabled'});
      await page.reload({waitUntil:'networkidle'});
      await panel.locator(':scope > summary').click();
      await firstNote.locator('[data-annotation-edit]').click();
      await expect(firstNote.getByRole('link',{name:'Ver dúvida'})).toHaveAttribute('href',firstJson.question_url);

      // With B revoked, the second note is still ours and the CMS remains
      // accessible through A, but its linked conversation is not.
      execFileSync('php',[seedScript,sandbox,'revoke'],{encoding:'utf8',timeout:45000});
      await page.goto(materialUrl('journey-a'),{waitUntil:'networkidle'});
      await panel.locator(':scope > summary').click();
      const secondNote=panel.locator('[data-annotation-item="'+second.id+'"]');
      await secondNote.locator('[data-annotation-edit]').click();
      await expect(secondNote.locator('.student-note-linked-unavailable')).toHaveCount(1);
      const secondResponse=page.waitForResponse(response=>
        response.url().includes('/aluno/material-anotacao.php')
        &&(response.request().postData()||'').includes('detach')
      );
      await secondNote.getByRole('button',{name:'Tornar geral'}).click();
      const secondJson=await (await secondResponse).json();
      expect(secondJson.annotation.anchorType).toBe('page');
      expect(secondJson.question.id).toBe(second.questionId);
      expect(secondJson.question_url).toBe('');
      await expect(secondNote).toHaveClass(/is-page/);
      await expect(secondNote.locator('.student-note-linked-unavailable')).toContainText('acesso indisponível');
      await expect(secondNote.locator('[data-note-question]')).toHaveCount(0);
      await expect(secondNote.getByRole('link',{name:'Ver dúvida'})).toHaveCount(0);
      await page.screenshot({path:'student-visual-audit/authenticated-material-question/phone-detached-revoked.png',animations:'disabled'});
      await page.setViewportSize({width:1440,height:900});
      await expect(secondNote.locator('.student-note-linked-unavailable')).toBeVisible();
      await page.screenshot({path:'student-visual-audit/authenticated-material-question/desktop-detached-revoked.png',animations:'disabled'});
      const persisted=snapshot();
      expect(persisted.notes).toHaveLength(2);
      expect(persisted.questions).toHaveLength(2);
      for(const note of persisted.notes){
        expect(note.anchor_type).toBe('page');
        expect(note.question_id).toBeTruthy();
      }
      await page.reload({waitUntil:'networkidle'});
      await panel.locator(':scope > summary').click();
      await secondNote.locator('[data-annotation-edit]').click();
      await expect(secondNote.locator('.student-note-linked-unavailable')).toHaveCount(1);
      await expect(secondNote.locator('[data-note-question]')).toHaveCount(0);
    }finally{await context.close();}
  });


  test('CMS publication changes an excerpt; reassociation updates visible title, quote and persisted question source',async({browser})=>{
    const context=await browser.newContext({viewport:{width:390,height:844}});
    const page=await context.newPage();
    try{
      await signIn(page,'journey-owner@example.invalid');
      await page.goto(materialUrl('journey-b'),{waitUntil:'networkidle'});
      const panel=page.locator('[data-student-notes-panel]');
      const token=await panel.locator('[data-inline-note-compose] input[name="_csrf"]').inputValue();
      const section=page.locator('[data-student-note-context="investigacao"]');
      const block=section.locator('p[data-student-anchor-block]');
      const originalBlock=await block.getAttribute('data-student-anchor-block');
      const pageRevision=await panel.getAttribute('data-student-page-revision');
      const created=await page.evaluate(async data=>{
        const form=new FormData();
        for(const [key,value] of Object.entries(data))form.set(key,String(value));
        const response=await fetch('/aluno/material-anotacao.php',{method:'POST',credentials:'same-origin',
          body:form,headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
        return {status:response.status,payload:await response.json()};
      },{
        _csrf:token,page_id:seedData.pageId,
        return_to:'/?page=material&lang=pt-br&cohort=journey-b',cohort:'journey-b',
        annotation_action:'create_selection',section_key:'investigacao',
        block_key:originalBlock,quote_exact:'Exposição',
        quote_prefix:'',quote_suffix:' e revelação',start_offset:0,end_offset:9,
        source_block_hash:'',source_page_revision:pageRevision||'',body:'Anotação de pesquisa preservada.',
        create_question:1,question_title:'Pergunta da primeira exposição',question_visibility:'private'
      });
      expect(created.status).toBe(200);
      expect(created.payload.ok).toBe(true);
      const id=created.payload.annotation.id,questionId=created.payload.question.id;
      expect(snapshot().notes[0].quote_exact).toBe('Exposição');
      expect(snapshot().questions).toHaveLength(1);

      // Publish a real second CMS revision, not an artificial DOM mutation.
      const revised=JSON.parse(execFileSync('php',[seedScript,sandbox,'revise'],{encoding:'utf8',timeout:45000}));
      expect(Number(revised.published_revision)).toBeGreaterThan(1);
      await page.goto(materialUrl('journey-b'),{waitUntil:'networkidle'});
      await expect(page.locator('[data-annotation-status="'+id+'"]')).toContainText('Trecho alterado');
      expect(snapshot().notes[0].quote_exact).toBe('Exposição');
      await panel.locator(':scope > summary').click();
      const note=panel.locator('[data-annotation-item="'+id+'"]');
      await note.locator('[data-annotation-edit]').click();
      await expect(note.locator('.student-note-title')).toHaveText('Exposição');
      await expect(note.getByRole('link',{name:'Ver dúvida'})).toHaveAttribute(
        'href','/aluno/duvidas.php?cohort=journey-b&id='+questionId
      );
      await note.getByRole('button',{name:'Reassociar'}).click();
      await expect(page.locator('.student-reanchor-hint')).toBeVisible();

      const updatedBlock=page.locator('[data-student-note-context="investigacao"] p[data-student-anchor-block]');
      await updatedBlock.evaluate(element=>{
        const needle='revelação continua decisiva';
        const walker=document.createTreeWalker(element,NodeFilter.SHOW_TEXT);
        let chosen=null,index=-1;
        while(walker.nextNode()){
          const current=walker.currentNode;
          const at=(current.nodeValue||'').indexOf(needle);
          if(at>=0){chosen=current;index=at;break;}
        }
        if(!chosen)throw new Error('Revised passage not present in CMS');
        const range=document.createRange();
        range.setStart(chosen,index);range.setEnd(chosen,index+needle.length);
        const selection=window.getSelection();selection.removeAllRanges();selection.addRange(range);
        element.dispatchEvent(new MouseEvent('mouseup',{bubbles:true,clientX:100,clientY:100}));
        document.dispatchEvent(new Event('selectionchange',{bubbles:true}));
      });
      const selectionAction=page.locator('.student-selection-note-action');
      await expect(selectionAction).toBeVisible();
      await expect(selectionAction).toHaveText('Reassociar seleção');
      await selectionAction.click();
      const compose=panel.locator('[data-inline-note-compose]');
      await expect(compose).toBeVisible();
      await expect(compose.locator('[data-anchor-preview]')).toHaveText('revelação continua decisiva');
      await expect(compose.locator('[data-annotation-action]')).toHaveValue('reanchor');
      const readerBefore=await page.evaluate(()=>({
        pathname:location.pathname,search:location.search,
        historyLength:history.length,scroll:document.querySelector('.cms-student-reading')?.scrollTop||0
      }));
      await compose.getByRole('button',{name:'Confirmar reassociação'}).click();
      await expect(compose).toBeHidden();
      await expect(page.locator('.student-reanchor-hint')).toBeHidden();
      expect(snapshot().notes[0].quote_exact).toBe('revelação continua decisiva');
      expect(snapshot().notes[0].body).toBe('Anotação de pesquisa preservada.');
      expect(snapshot().questions).toHaveLength(1);
      expect(snapshot().notes[0].question_id).toBe(questionId);
      const readerAfter=await page.evaluate(()=>({
        pathname:location.pathname,search:location.search,
        historyLength:history.length,scroll:document.querySelector('.cms-student-reading')?.scrollTop||0
      }));
      expect(readerAfter.pathname+readerAfter.search).toBe(readerBefore.pathname+readerBefore.search);
      expect(readerAfter.historyLength).toBe(readerBefore.historyLength);
      expect(Math.abs(readerAfter.scroll-readerBefore.scroll)).toBeLessThan(5);
      await expect(page.locator('mark.student-inline-note-mark[data-annotation-id="'+id+'"]')).toContainText('revelação continua decisiva');

      await panel.locator(':scope > summary').click();
      await note.locator('[data-annotation-edit]').click();
      await expect(note.locator('.student-note-quote')).toHaveText('revelação continua decisiva');
      await expect(note.locator('.student-note-title')).toHaveText('revelação continua decisiva');
      await expect(note.locator('textarea[name="body"]')).toHaveValue('Anotação de pesquisa preservada.');
      await expect(note.getByRole('link',{name:'Ver dúvida'})).toHaveAttribute(
        'href','/aluno/duvidas.php?cohort=journey-b&id='+questionId
      );
      await page.screenshot({path:'student-visual-audit/authenticated-material-question/phone-reanchored-after-cms-revision.png',animations:'disabled'});

      await page.reload({waitUntil:'networkidle'});
      await panel.locator(':scope > summary').click();
      await note.locator('[data-annotation-edit]').click();
      await expect(note.locator('.student-note-title')).toHaveText('revelação continua decisiva');
      await expect(note.locator('.student-note-quote')).toHaveText('revelação continua decisiva');
      await expect(note.locator('[data-note-question]')).toHaveCount(0);
      await expect(note.getByRole('link',{name:'Ver dúvida'})).toBeVisible();
      await page.setViewportSize({width:1440,height:900});
      await page.screenshot({path:'student-visual-audit/authenticated-material-question/desktop-reanchored-after-cms-revision.png',animations:'disabled'});
    }finally{await context.close();}
  });


  test('CMS cannot silently relink a short repeated excerpt to a replacement paragraph',async({browser})=>{
    const context=await browser.newContext({viewport:{width:390,height:844}});
    const page=await context.newPage();
    try{
      await signIn(page,'journey-owner@example.invalid');
      await page.goto(materialUrl('journey-b'),{waitUntil:'networkidle'});
      const panel=page.locator('[data-student-notes-panel]');
      const token=await panel.locator('[data-inline-note-compose] input[name="_csrf"]').inputValue();
      const first=page.locator('[data-student-note-context="investigacao"] p[data-student-anchor-block]').first();
      const anchor=await first.evaluate(block=>{
        const text=block.textContent||'';
        let hash=2166136261;
        for(let i=0;i<text.length;i++){hash^=text.charCodeAt(i);hash=Math.imul(hash,16777619);}
        const exact='Exposição',start=text.indexOf(exact);
        return{
          section_key:'investigacao',
          block_key:block.getAttribute('data-student-anchor-block'),
          quote_exact:exact,quote_prefix:text.slice(Math.max(0,start-120),start),
          quote_suffix:text.slice(start+exact.length,start+exact.length+120),
          start_offset:start,end_offset:start+exact.length,
          source_block_hash:(hash>>>0).toString(16).padStart(8,'0')
        };
      });
      expect(anchor.quote_exact).toBe('Exposição');
      expect(anchor.quote_suffix).toBe(' e revelação são decisões independentes.');
      const sourceRevision=await panel.getAttribute('data-student-page-revision');
      const create=async(input)=>{
        const res=await page.evaluate(async data=>{
          const form=new FormData();
          for(const [key,value] of Object.entries(data))form.set(key,String(value));
          const response=await fetch('/aluno/material-anotacao.php',{
            method:'POST',credentials:'same-origin',body:form,
            headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}
          });
          return{status:response.status,payload:await response.json()};
        },{
          ...anchor,...input,
          _csrf:token,page_id:seedData.pageId,
          return_to:'/?page=material&lang=pt-br&cohort=journey-b',
          cohort:'journey-b',annotation_action:'create_selection',
          source_page_revision:sourceRevision||''
        });
        expect(res.status).toBe(200);
        expect(res.payload.ok).toBe(true);
        return res.payload;
      };
      const linked=await create({
        body:'Registro pessoal do trecho antes da revisão',
        create_question:1,question_title:'Pergunta que não pode mudar de origem',
        question_visibility:'private'
      });
      const id=linked.annotation.id,qid=linked.question.id;
      const uncertain=await create({
        body:'Anotação legada sem impressão digital nem contexto',
        quote_prefix:'',quote_suffix:'',source_block_hash:''
      });
      const uncertainId=uncertain.annotation.id;
      expect(snapshot().questions).toHaveLength(1);
      await page.reload({waitUntil:'networkidle'});
      await expect(page.locator('[data-annotation-status="'+id+'"]')).toHaveText('Vinculada ao texto.');
      await expect(page.locator('mark.student-inline-note-mark[data-annotation-id="'+id+'"]')).toHaveCount(1);
      await expect(page.locator('[data-annotation-status="'+uncertainId+'"]')).toContainText('não identificado com segurança');
      await expect(page.locator('mark.student-inline-note-mark[data-annotation-id="'+uncertainId+'"]')).toHaveCount(0);

      // A new CMS revision puts the same short quote in the old ordinal,
      // but with different text, while the original block moves to p:2.
      const unique=JSON.parse(execFileSync('php',[seedScript,sandbox,'revise-duplicates'],{encoding:'utf8',timeout:45000}));
      expect(unique.mode).toBe('revise-duplicates');
      await page.reload({waitUntil:'networkidle'});
      const candidates=page.locator('[data-student-note-context="investigacao"] p[data-student-anchor-block]');
      await expect(candidates).toHaveCount(2);
      await expect(page.locator('[data-annotation-status="'+id+'"]')).toContainText('localizado após alteração');
      const found=page.locator('mark.student-inline-note-mark[data-annotation-id="'+id+'"]');
      await expect(found).toHaveCount(1);
      await expect(found).toContainText('Exposição');
      await expect(found.locator('xpath=ancestor::p')).toContainText('Exposição e revelação são decisões independentes.');
      await expect(candidates.first().locator('mark.student-inline-note-mark[data-annotation-id="'+id+'"]')).toHaveCount(0);
      await expect(page.locator('mark.student-inline-note-mark[data-annotation-id="'+uncertainId+'"]')).toHaveCount(0);
      await panel.locator(':scope > summary').click();
      let note=panel.locator('[data-annotation-item="'+id+'"]');
      await note.locator('[data-annotation-edit]').click();
      await expect(note.locator('textarea[name="body"]')).toHaveValue('Registro pessoal do trecho antes da revisão');
      await expect(note.getByRole('link',{name:'Ver dúvida'})).toHaveAttribute('href','/aluno/duvidas.php?cohort=journey-b&id='+qid);
      await page.screenshot({path:'student-visual-audit/authenticated-material-question/phone-duplicate-excerpt-recovered.png',animations:'disabled'});
      await panel.locator('[data-student-notes-back-button]').click();

      // A second copy with the same whole-block fingerprint and neighbours
      // removes the evidence of which occurrence was the original.
      const duplicate=JSON.parse(execFileSync('php',[seedScript,sandbox,'revise-ambiguous'],{encoding:'utf8',timeout:45000}));
      expect(duplicate.mode).toBe('revise-ambiguous');
      await page.reload({waitUntil:'networkidle'});
      await expect(page.locator('[data-annotation-status="'+id+'"]')).toContainText('não identificado com segurança');
      await expect(page.locator('mark.student-inline-note-mark[data-annotation-id="'+id+'"]')).toHaveCount(0);
      await panel.locator(':scope > summary').click();
      note=panel.locator('[data-annotation-item="'+id+'"]');
      await note.locator('[data-annotation-edit]').click();
      await expect(note.locator('.student-note-quote')).toHaveText('Exposição');
      await expect(note.locator('textarea[name="body"]')).toHaveValue('Registro pessoal do trecho antes da revisão');
      await expect(note.getByRole('button',{name:'Reassociar'})).toBeVisible();
      await expect(note.getByRole('link',{name:'Ver dúvida'})).toHaveAttribute('href','/aluno/duvidas.php?cohort=journey-b&id='+qid);
      await page.screenshot({path:'student-visual-audit/authenticated-material-question/phone-duplicate-excerpt-ambiguous.png',animations:'disabled'});
      await page.setViewportSize({width:1440,height:900});
      await page.screenshot({path:'student-visual-audit/authenticated-material-question/desktop-duplicate-excerpt-ambiguous.png',animations:'disabled'});
      const persisted=snapshot();
      expect(persisted.notes).toHaveLength(2);
      expect(persisted.questions).toHaveLength(1);
      const source=persisted.notes.find(n=>Number(n.id)===Number(id));
      expect(source).toMatchObject({
        quote_exact:'Exposição',
        block_key:anchor.block_key,
        source_block_hash:anchor.source_block_hash,
        body:'Registro pessoal do trecho antes da revisão',
        question_id:qid
      });
    }finally{await context.close();}
  });

});
