const {test,expect}=require('@playwright/test');

test('registration form content is edited directly and persists after reload',async({page})=>{
  const state={
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
          {id:'payment_pix',label:'Pagamento — Pix',afterField:'payment_method',condition:{source:'payment_method',operator:'equals',value:'pix'},html:'<section class="registration-payment-panel"><h3 data-cms-editable>R$ 698,00</h3><div class="registration-pix-layout"><img src="/assets/media/pix-workshop.svg" alt="QR Code Pix" data-cms-image><code data-pix-copy-value data-cms-editable>PIX-CODE</code><button type="button" data-copy-pix data-cms-editable>Copiar código Pix</button></div></section>'},
          {id:'payment_card',label:'Pagamento — cartão',afterField:'payment_method',condition:{source:'payment_method',operator:'contains',value:'card_'},html:'<section class="registration-payment-panel"><h3 data-cms-editable>Pagamento por cartão de crédito</h3><p data-cms-editable>Use o link do Mercado Pago.</p><a href="https://example.test/pay" data-cms-editable>Pagar com cartão</a></section>'}
        ]}
      }
    }
  };

  const clone=value=>JSON.parse(JSON.stringify(value));
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
  await expect(reloaded.locator('[data-cms-form-content-id="payment_pix"]')).toBeVisible();
  await expect(reloaded.locator('[data-cms-form-content-id="payment_card"]')).toBeVisible();
});
