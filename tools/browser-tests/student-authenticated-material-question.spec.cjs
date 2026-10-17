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
  test.beforeAll(async()=>{
    sandbox=fs.mkdtempSync(path.join(os.tmpdir(),'student-material-journey-'));
    try{
      // The real bootstrap resolves SQLite and config relative to its own
      // app directory; this is an isolated installation, not a mocked DB.
      for(const name of ['app','aluno','assets','migrations','template','index.php']){
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
      fs.mkdirSync('test-results/authenticated-material-question',{recursive:true});
    }catch(error){
      if(sandbox&&fs.existsSync(path.join(sandbox,'php-server.log'))){
        error.message+='\n'+fs.readFileSync(path.join(sandbox,'php-server.log'),'utf8').slice(-3000);
      }
      throw error;
    }
  });
  test.afterAll(async()=>{
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
      await page.screenshot({path:'test-results/authenticated-material-question/phone-return.png',animations:'disabled'});

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
        await peer.goto(origin+'/aluno/duvidas.php?cohort=journey-a&id='+questionId);
        await expect(peer.getByRole('heading',{name:'Relação entre exposição e positivo'})).toHaveCount(0);
      }finally{await peerContext.close();}

      // Revoke B in the on-disk database without logging the owner out.
      execFileSync('php',[seedScript,sandbox,'revoke'],{encoding:'utf8',timeout:45000});
      await page.goto(materialUrl('journey-a'),{waitUntil:'networkidle'});
      await panel.locator(':scope > summary').click();
      await note.locator('[data-annotation-edit]').click();
      await expect(link).toHaveCount(0);
      await expect(note.locator('.student-note-linked-unavailable')).toContainText('acesso indisponível');
      await expect(note.locator('[data-note-question]')).toHaveCount(0);
      await page.screenshot({path:'test-results/authenticated-material-question/phone-revoked.png',animations:'disabled'});
      for(const width of [320,1440]){
        await page.setViewportSize({width,height:width===320?720:900});
        await expect(note.locator('.student-note-linked-unavailable')).toBeVisible();
        await page.screenshot({path:'test-results/authenticated-material-question/'+width+'-revoked.png',animations:'disabled'});
      }
      persisted=snapshot();
      expect(persisted.questions).toHaveLength(1);
      expect(persisted.notes).toHaveLength(1);
    }finally{await context.close();}
  });
});
