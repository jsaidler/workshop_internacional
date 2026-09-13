const {test,expect}=require('@playwright/test');

const clone=value=>JSON.parse(JSON.stringify(value));
const esc=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));

function fixtureState(){
  return {
    csrf:'fixture-token',
    form:{
      id:1,
      activityId:1,
      locale:'pt-BR',
      title:'Inscrição — nova turma',
      draftRevision:1,
      publishedRevision:1,
      schema:{
        version:1,
        submitLabel:'Enviar inscrição',
        successTitle:'Inscrição recebida',
        successMessage:'OK',
        fields:[
          {id:'name',type:'text',label:'Nome Completo',required:true,width:'full'},
          {id:'payment_method',type:'radio',label:'Forma de pagamento',required:true,width:'full',options:[
            {value:'pix',label:'PIX - R$698,00'},
            {value:'card_cash',label:'Cartão à vista'},
            {value:'card_installments',label:'Cartão parcelado'}
          ]},
          {id:'terms',type:'consent',label:'Declaro que li as condições.',required:true,width:'full'}
        ],
        settings:{contentBlocks:[
          {id:'payment_pix',label:'Pagamento — Pix',afterField:'payment_method',condition:{source:'payment_method',operator:'equals',value:'pix'},html:'<div class="registration-payment-source"><section class="registration-payment-panel"><h3 data-cms-editable>R$ 698,00</h3><div class="registration-pix-layout"><img src="/assets/media/pix-workshop.svg" alt="QR Code Pix" data-cms-image><code data-pix-copy-value data-cms-editable>PIX-CODE</code><button type="button" data-copy-pix data-cms-editable>Copiar código Pix</button></div></section></div>'},
          {id:'payment_card',label:'Pagamento — cartão',afterField:'payment_method',condition:{source:'payment_method',operator:'contains',value:'card_'},html:'<div class="registration-payment-source"><section class="registration-payment-panel"><h3 data-cms-editable>Pagamento por cartão de crédito</h3><p data-cms-editable>Use o link do Mercado Pago.</p><a href="https://example.test/pay" data-cms-editable>Pagar com cartão</a></section></div>'}
        ]}
      }
    }
  };
}

function fieldHtml(field){
  const star=field.required?' <span aria-hidden="true">*</span>':'';
  if(field.type==='radio'||field.type==='checkbox-group'){
    const inputType=field.type==='radio'?'radio':'checkbox',name=field.type==='radio'?field.id:`${field.id}[]`;
    return `<fieldset class="cms-choice-group"><legend>${esc(field.label)}${star}</legend><div class="cms-choice-grid">${(field.options||[]).map(option=>`<label><input type="${inputType}" name="${esc(name)}" value="${esc(option.value)}"><span>${esc(option.label)}</span></label>`).join('')}</div>${field.help?`<small>${esc(field.help)}</small>`:''}</fieldset>`;
  }
  if(field.type==='consent')return `<label class="cms-consent"><input type="checkbox" name="${esc(field.id)}" value="1"><span>${esc(field.label)}${star}</span></label>`;
  return `<label class="cms-field"><span>${esc(field.label)}${star}</span><input type="text" name="${esc(field.id)}">${field.help?`<small>${esc(field.help)}</small>`:''}</label>`;
}

function renderedForm(form){
  const blocks=Array.isArray(form.schema?.settings?.contentBlocks)?form.schema.settings.contentBlocks:[];
  const byAfter={};for(const block of blocks)(byAfter[block.afterField||'']??=[]).push(block);
  const blockHtml=block=>`<div class="cms-form-content" data-cms-form-content-id="${esc(block.id)}" data-cms-form-content-label="${esc(block.label||block.id)}"${block.condition?` data-cms-content-condition-source="${esc(block.condition.source||'')}" data-cms-content-condition-operator="${esc(block.condition.operator||'equals')}" data-cms-content-condition-value="${esc(block.condition.value||'')}"`:''}>${block.html||''}</div>`;
  let content=(byAfter['']||[]).map(blockHtml).join('');
  for(const field of form.schema.fields||[]){content+=fieldHtml(field);content+=(byAfter[field.id]||[]).map(blockHtml).join('');}
  return `<div data-cms-form-key="registration" data-cms-form-block="${form.id}" class="cms-form-block"><form class="cms-form" data-cms-form-preview="1"><div class="cms-form-grid">${content}</div><button class="button" type="button">${esc(form.schema.submitLabel)} <span aria-hidden="true">↗</span></button></form></div>`;
}

function previewHtml(form){
  return `<!doctype html><html><head><meta charset="utf-8"><link rel="stylesheet" href="/assets/cms.css"><style>body{font-family:sans-serif;padding:24px}.cms-form-grid{display:grid;gap:18px}.cms-form-content{padding:12px;border:1px solid #aaa}.registration-pix-layout{display:grid;grid-template-columns:120px 1fr;gap:12px}.registration-pix-layout img{width:100px;height:auto}</style></head><body class="cms-public cms-editor-preview"><main data-cms-page-main><section data-cms-section="registration" data-cms-section-name="Inscrição"><p data-cms-editable>Texto comum da página</p>${renderedForm(form)}</section></main></body></html>`;
}

async function installFormRoutes(page,state){
  await page.route('**/admin/api/cms-form-load.php?form=1',async route=>{
    await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify({csrf:state.csrf,form:clone(state.form)})});
  });
  await page.route('**/admin/api/cms-form-save.php',async route=>{
    const payload=route.request().postDataJSON();
    expect(payload.csrf).toBe(state.csrf);
    expect(payload.formId).toBe(1);
    expect(payload.revision).toBe(state.form.draftRevision);
    state.form.schema=clone(payload.schema);
    state.form.title=payload.title;
    state.form.draftRevision+=1;
    await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify({form:clone(state.form)})});
  });
  await page.route('**/admin/api/cms-form-publish.php',async route=>{
    state.form.publishedRevision=state.form.draftRevision;
    await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify({publishedRevision:state.form.publishedRevision})});
  });
}

test('registration form content is edited directly and persists after reload',async({page})=>{
  const state=fixtureState();
  await installFormRoutes(page,state);
  await page.goto('http://127.0.0.1:8099/tools/browser-fixture/form-editor.html');
  const editor=page.frameLocator('#page-frame');
  const nameLabel=editor.locator('.cms-field > span [data-cms-form-inline="field-label"]');
  await expect(nameLabel).toBeVisible();
  await nameLabel.click();
  await expect(nameLabel).toHaveAttribute('contenteditable','true');
  await nameLabel.fill('Nome do participante');
  await nameLabel.press('Enter');
  await expect.poll(()=>state.form.schema.fields.find(field=>field.id==='name')?.label).toBe('Nome do participante');

  const pixOption=editor.locator('[data-cms-form-inline="option-label"][data-cms-form-field="payment_method"][data-cms-form-option-index="0"]');
  await pixOption.click();
  await pixOption.fill('PIX imediato');
  await pixOption.press('Enter');
  await expect.poll(()=>state.form.schema.fields.find(field=>field.id==='payment_method')?.options?.[0]?.label).toBe('PIX imediato');

  const pixBlock=editor.locator('[data-cms-form-content-id="payment_pix"]');
  const cardBlock=editor.locator('[data-cms-form-content-id="payment_card"]');
  await expect(pixBlock).toBeVisible();
  await expect(cardBlock).toBeVisible();

  const cardTitle=cardBlock.locator('h3');
  await cardTitle.click();
  await cardTitle.fill('Pagamento no cartão');
  await cardTitle.press('Enter');
  await expect.poll(()=>state.form.schema.settings.contentBlocks.find(block=>block.id==='payment_card')?.html).toContain('Pagamento no cartão');

  await pixBlock.locator('img').click();
  await expect(page.locator('#inspector')).toContainText('Imagem do conteúdo');
  await page.locator('#fc-image-replace').click();
  await page.locator('[data-form-media="0"]').click();
  await expect.poll(()=>state.form.schema.settings.contentBlocks.find(block=>block.id==='payment_pix')?.html).toContain('/uploads/qr-new.svg');

  await expect(page.locator('body')).not.toHaveAttribute('data-legacy','hit');

  await page.reload();
  const reloaded=page.frameLocator('#page-frame');
  await expect(reloaded.locator('.cms-field > span')).toContainText('Nome do participante');
  await expect(reloaded.locator('.cms-choice-grid label').first()).toContainText('PIX imediato');
  await expect(reloaded.locator('[data-cms-form-content-id="payment_card"] h3')).toHaveText('Pagamento no cartão');
  await expect(reloaded.locator('[data-cms-form-content-id="payment_pix"] img')).toHaveAttribute('src','/uploads/qr-new.svg');
});

test('expanded forms stay owned by the visual form editor when the full page editor is loaded',async({page})=>{
  const state=fixtureState();
  await installFormRoutes(page,state);
  await page.route('**/admin/api/cms-page-load.php?page=1',async route=>{
    await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify({
      csrf:'page-token',
      page:{id:1,uuid:'page-1',activityId:1,locale:'pt-BR',slug:'inscricao',title:'Inscrição',navTitle:'Inscrição',isHome:false,showInNav:true,draftRevision:1,publishedRevision:1,document:{version:2,theme:'auto',meta:{title:'',description:''},html:'<section data-cms-section="registration"><div data-cms-form-key="registration"></div></section>'}},
      activity:{id:1,slug:'workshop',title:'Workshop'},
      forms:[{id:1,key:'registration',title:'Inscrição — nova turma',locale:'pt-BR',draftRevision:1,publishedRevision:1}]
    })});
  });
  await page.route('**/preview/?page=1*',async route=>{
    await route.fulfill({status:200,contentType:'text/html',body:previewHtml(state.form)});
  });

  await page.goto('http://127.0.0.1:8099/tools/browser-fixture/form-editor-integrated.html?page=1');
  const editor=page.frameLocator('#page-frame');
  const nameLabel=editor.locator('.cms-field > span [data-cms-form-inline="field-label"]');
  const nameInput=editor.locator('.cms-field input[name="name"]');
  await expect(nameLabel).toBeVisible();
  await expect.poll(()=>nameLabel.evaluate(el=>getComputedStyle(el).pointerEvents)).toBe('auto');
  await expect.poll(()=>nameInput.evaluate(el=>getComputedStyle(el).pointerEvents)).toBe('auto');
  await nameLabel.click();
  await expect(nameLabel).toHaveAttribute('contenteditable','true');
  await expect(page.locator('#inspector')).toContainText('Rótulo do campo');
  await expect(page.locator('#inspector')).not.toContainText('Formulário usado');
  await nameLabel.fill('Nome visual');
  await nameLabel.press('Enter');
  await expect.poll(()=>state.form.schema.fields.find(field=>field.id==='name')?.label).toBe('Nome visual');

  const pixTitle=editor.locator('[data-cms-form-content-id="payment_pix"] h3');
  await pixTitle.click();
  await expect(page.locator('#fc-visible')).toBeChecked();
  await page.locator('#fc-visible').uncheck();
  await expect.poll(()=>state.form.schema.settings.contentBlocks.find(block=>block.id==='payment_pix')?.html).toContain('data-cms-public-hidden="1"');
  await expect(editor.locator('[data-cms-form-content-id="payment_pix"]')).toBeVisible();
  await expect(page.locator('#inspector')).toContainText('Oculto no site público');

  await editor.locator('[data-cms-form-content-id="payment_pix"] img').click();
  await expect(page.locator('#inspector')).toContainText('Imagem do conteúdo');
  await page.locator('#fc-image-replace').click();
  await page.locator('[data-form-media="0"]').click();
  await expect.poll(()=>state.form.schema.settings.contentBlocks.find(block=>block.id==='payment_pix')?.html).toContain('/uploads/qr-new.svg');
});