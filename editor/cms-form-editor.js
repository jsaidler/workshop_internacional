(()=>{
'use strict';

const frame=document.querySelector('#page-frame');
const inspector=document.querySelector('#inspector');
const mediaDialog=document.querySelector('#media-dialog');
const mediaGrid=document.querySelector('#media-grid');
if(!frame||!inspector)return;

const forms=new Map();
let selected=null;
let editing=null;
const esc=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
const normalizeFieldName=name=>String(name||'').replace(/\[\]$/,'');
const frameDoc=()=>frame.contentDocument||null;
const formBlockFrom=node=>node?.closest?.('[data-cms-form-block]')||null;

async function json(url,options={}){
  const response=await fetch(url,{credentials:'same-origin',...options});
  const body=await response.json().catch(()=>({}));
  if(!response.ok){const error=new Error(body?.error?.message||body?.error?.code||`HTTP ${response.status}`);error.code=body?.error?.code;throw error;}
  return body;
}
const post=(url,payload)=>json(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
function entryFor(formId){
  if(!forms.has(formId))forms.set(formId,{formId,data:null,promise:null,queue:Promise.resolve(),dirty:false,saving:false,changeSeq:0,error:''});
  return forms.get(formId);
}
function ensureForm(formId,refresh=false){
  const entry=entryFor(formId);
  if(refresh){entry.data=null;entry.promise=null;}
  if(entry.data)return Promise.resolve(entry);
  if(entry.promise)return entry.promise;
  entry.promise=json(`/admin/api/cms-form-load.php?form=${formId}`).then(data=>{entry.data=data;entry.error='';return entry;}).catch(error=>{entry.error=error.message;throw error;}).finally(()=>{entry.promise=null;});
  return entry.promise;
}
function fieldFor(entry,fieldId){return entry.data?.form?.schema?.fields?.find(field=>field.id===fieldId)||null;}
function optionFor(entry,fieldId,index){const field=fieldFor(entry,fieldId);return Array.isArray(field?.options)?field.options[index]||null:null;}
function contentBlocks(entry){
  const schema=entry.data?.form?.schema;
  if(!schema)return [];
  schema.settings=schema.settings&&typeof schema.settings==='object'?schema.settings:{};
  schema.settings.contentBlocks=Array.isArray(schema.settings.contentBlocks)?schema.settings.contentBlocks:[];
  return schema.settings.contentBlocks;
}
function contentFor(entry,contentId){return contentBlocks(entry).find(block=>block.id===contentId)||null;}
function blockIsPubliclyHidden(block){return /data-cms-public-hidden\s*=\s*["']1["']/i.test(String(block?.html||''));}
function statusText(entry){
  if(entry.saving)return 'Salvando rascunho…';
  if(entry.error)return entry.error;
  if(entry.dirty)return 'Alterações ainda não salvas.';
  const form=entry.data?.form;
  if(!form)return 'Carregando formulário…';
  return form.publishedRevision==null||Number(form.publishedRevision)!==Number(form.draftRevision)?'Rascunho salvo. Falta publicar.':'Formulário publicado e atualizado.';
}
function markChanged(entry){entry.dirty=true;entry.changeSeq++;renderIfCurrent(entry);}
function queueSave(entry){
  entry.queue=entry.queue.then(async()=>{
    if(!entry.data||!entry.dirty)return;
    const seq=entry.changeSeq,form=entry.data.form,schema=JSON.parse(JSON.stringify(form.schema));
    entry.saving=true;renderIfCurrent(entry);
    try{
      const saved=await post('/admin/api/cms-form-save.php',{csrf:entry.data.csrf,formId:form.id,revision:form.draftRevision,title:form.title,schema});
      entry.data.form=saved.form;
      if(entry.changeSeq===seq)entry.dirty=false;
      entry.error='';
    }catch(error){
      entry.error=error.code==='revision_conflict'?'O formulário foi alterado em outra sessão. Recarregue a página antes de continuar.':error.message;
      entry.dirty=true;
    }finally{entry.saving=false;renderIfCurrent(entry);}
  });
  return entry.queue;
}
async function publishEntry(entry){
  if(!entry.data)return;
  if(entry.dirty)await queueSave(entry);else await entry.queue;
  if(entry.dirty||entry.error)return;
  try{
    const result=await post('/admin/api/cms-form-publish.php',{csrf:entry.data.csrf,formId:entry.data.form.id});
    entry.data.form.publishedRevision=result.publishedRevision;
    entry.error='';
  }catch(error){entry.error=error.message;}
  renderIfCurrent(entry);
}
function renderIfCurrent(entry){if(selected?.formId===entry.formId)renderContext();}
function contextLabel(meta){
  if(meta.part==='field-label')return 'Rótulo do campo';
  if(meta.part==='option-label')return 'Opção';
  if(meta.part==='field-help')return 'Texto de ajuda';
  if(meta.part==='submit-label')return 'Texto do botão';
  if(meta.part==='field-control')return 'Campo do formulário';
  if(meta.part==='content-text')return 'Conteúdo editorial';
  if(meta.part==='content-image')return 'Imagem do conteúdo';
  if(meta.part==='content-block')return 'Bloco editorial';
  return 'Formulário';
}
function conditionSummary(block,entry){
  if(blockIsPubliclyHidden(block))return 'Oculto no site público. Continua visível aqui para edição.';
  const rule=block?.condition;
  if(!rule?.source)return 'Sempre visível no site público.';
  const field=fieldFor(entry,rule.source);
  const source=field?.label||rule.source;
  const labels={equals:'é igual a',not_equals:'é diferente de',contains:'contém',checked:'está marcado / preenchido',not_checked:'não está marcado / está vazio'};
  return `Exibição pública: ${source} ${labels[rule.operator]||rule.operator}${['checked','not_checked'].includes(rule.operator)?'':` “${rule.value||''}”`}. No editor, este bloco permanece visível.`;
}
function conditionControls(block,entry){
  const fields=entry.data?.form?.schema?.fields||[];
  const condition=block.condition||{source:'',operator:'equals',value:''};
  const visible=!blockIsPubliclyHidden(block);
  return `<hr><p class="eyebrow">Exibição pública</p>
    <label class="check-row"><input id="fc-visible" type="checkbox" ${visible?'checked':''}> Exibir este bloco no site público</label>
    <label>Mostrar quando<select id="fc-source"><option value="">Sempre mostrar</option>${fields.map(field=>`<option value="${esc(field.id)}" ${condition.source===field.id?'selected':''}>${esc(field.label)}</option>`).join('')}</select></label>
    <label>Operador<select id="fc-operator">${[['equals','é igual a'],['not_equals','é diferente de'],['contains','contém'],['checked','está marcado / preenchido'],['not_checked','não está marcado / está vazio']].map(([value,label])=>`<option value="${value}" ${condition.operator===value?'selected':''}>${label}</option>`).join('')}</select></label>
    <label>Valor<input id="fc-value" value="${esc(condition.value||'')}" ${['checked','not_checked'].includes(condition.operator)?'disabled':''}></label>
    <label>Posição no formulário<select id="fc-after"><option value="" ${!block.afterField?'selected':''}>Antes do primeiro campo</option>${fields.map(field=>`<option value="${esc(field.id)}" ${block.afterField===field.id?'selected':''}>Depois de: ${esc(field.label)}</option>`).join('')}</select></label>
    <p class="inspector-note">Blocos condicionais e blocos ocultos continuam visíveis no editor para que você possa alterá-los. Alterar a posição recarrega a prévia depois de salvar o rascunho.</p>`;
}
function renderContext(){
  if(!selected)return;
  const entry=entryFor(selected.formId);
  const form=entry.data?.form;
  const field=form?fieldFor(entry,selected.fieldId):null;
  const option=form&&selected.part==='option-label'?optionFor(entry,selected.fieldId,selected.optionIndex):null;
  const block=form&&selected.contentId?contentFor(entry,selected.contentId):null;
  const image=selected.part==='content-image'?selected.el:null;
  const link=selected.part==='content-text'&&selected.el?.tagName==='A'?selected.el:null;
  inspector.innerHTML=`<div class="inspector-section form-quick-editor" data-cms-form-unified>
    <header><p class="eyebrow">Formulário · edição visual</p><h2>${esc(contextLabel(selected))}</h2><p class="form-quick-status">${esc(statusText(entry))}</p></header>
    ${field?`<div class="form-quick-context"><span>Campo</span><strong>${esc(field.label)}</strong><small>${esc(field.type)}</small></div>`:''}
    ${block?`<div class="form-quick-context"><span>Bloco</span><strong>${esc(block.label||block.id)}</strong><small>${esc(conditionSummary(block,entry))}</small></div>`:''}
    ${selected.part==='field-control'?'<p class="form-quick-note">Este é o controle do campo. Para alterar o texto, clique diretamente no rótulo, ajuda ou opção visível.</p>':''}
    ${option?`<label>Valor interno da opção<input id="fq-option-value" value="${esc(option.value)}"></label><p class="form-quick-warning">O valor interno não aparece na página. Alterá-lo pode afetar regras condicionais.</p>`:''}
    ${image?`<label>Texto alternativo<input id="fc-image-alt" value="${esc(image.alt||'')}"></label><div class="button-row"><button type="button" id="fc-image-replace" class="primary">Trocar imagem</button></div>`:''}
    ${link?`<label>Destino do link<input id="fc-link-href" value="${esc(link.getAttribute('href')||'')}"></label>`:''}
    ${block?conditionControls(block,entry):''}
    <div class="button-row form-quick-actions"><button type="button" id="fq-publish" class="primary" ${!form?'disabled':''}>Publicar alterações do formulário</button></div>
    <a class="panel-button" href="${form?`/admin/forms.php?activity=${Number(form.activityId||0)}&form=${Number(form.id)}`:'#'}" target="_blank" rel="noopener">Configuração estrutural do formulário ↗</a>
  </div>`;
  inspector.querySelector('#fq-option-value')?.addEventListener('change',event=>{
    if(!option)return;const next=String(event.currentTarget.value||'').trim();if(!next){event.currentTarget.value=option.value;return;}option.value=next;const input=selected.el?.closest('label')?.querySelector('input');if(input)input.value=next;markChanged(entry);queueSave(entry);
  });
  inspector.querySelector('#fq-publish')?.addEventListener('click',()=>publishEntry(entry));
  inspector.querySelector('#fc-image-replace')?.addEventListener('click',()=>openImagePicker(entry,selected));
  inspector.querySelector('#fc-image-alt')?.addEventListener('change',event=>{
    if(!image||!block)return;image.alt=event.currentTarget.value;syncContentBlockFromDom(entry,block.id);markChanged(entry);queueSave(entry);
  });
  inspector.querySelector('#fc-link-href')?.addEventListener('change',event=>{
    if(!link||!block)return;link.setAttribute('href',String(event.currentTarget.value||'').trim()||'#');syncContentBlockFromDom(entry,block.id);markChanged(entry);queueSave(entry);
  });
  const visible=inspector.querySelector('#fc-visible'),source=inspector.querySelector('#fc-source'),operator=inspector.querySelector('#fc-operator'),value=inspector.querySelector('#fc-value'),after=inspector.querySelector('#fc-after');
  visible?.addEventListener('change',event=>{
    if(!block)return;
    const d=frameDoc(),content=d?.querySelector(`[data-cms-form-block="${entry.formId}"] [data-cms-form-content-id="${CSS.escape(block.id)}"]`),root=content?.firstElementChild;
    if(!root)return;
    if(event.currentTarget.checked){root.removeAttribute('hidden');delete root.dataset.cmsPublicHidden;content?.classList.remove('cms-form-content-public-hidden');}
    else{root.setAttribute('hidden','');root.dataset.cmsPublicHidden='1';content?.classList.add('cms-form-content-public-hidden');}
    syncContentBlockFromDom(entry,block.id);markChanged(entry);queueSave(entry);
  });
  const saveBlockSettings=async reload=>{
    if(!block)return;
    const src=source?.value||'',op=operator?.value||'equals';
    block.afterField=after?.value||'';
    if(src)block.condition={source:src,operator:op,value:['checked','not_checked'].includes(op)?'':(value?.value||'')};else delete block.condition;
    markChanged(entry);await queueSave(entry);if(reload&&!entry.dirty&&!entry.error)frame.contentWindow.location.reload();
  };
  source?.addEventListener('change',()=>saveBlockSettings(false));
  operator?.addEventListener('change',()=>{if(value)value.disabled=['checked','not_checked'].includes(operator.value);saveBlockSettings(false);});
  value?.addEventListener('change',()=>saveBlockSettings(false));
  after?.addEventListener('change',()=>saveBlockSettings(true));
}
function setMeta(el,meta){
  if(!el)return null;
  el.dataset.cmsFormInline=meta.part;
  if(meta.fieldId)el.dataset.cmsFormField=meta.fieldId;else delete el.dataset.cmsFormField;
  if(meta.contentId)el.dataset.cmsFormContentRef=meta.contentId;else delete el.dataset.cmsFormContentRef;
  if(Number.isInteger(meta.optionIndex))el.dataset.cmsFormOptionIndex=String(meta.optionIndex);else delete el.dataset.cmsFormOptionIndex;
  el.title=meta.part==='field-control'?'Clique para selecionar o campo':meta.part==='content-image'?'Clique para editar esta imagem':'Clique para editar';
  return el;
}
function ensureDirectTextTarget(host,meta){
  if(!host)return null;
  const existing=host.querySelector(':scope > [data-cms-form-inline]');if(existing)return existing;
  const nodes=[...host.childNodes].filter(node=>node.nodeType===3&&String(node.textContent||'').trim()!=='');if(!nodes.length)return null;
  const span=host.ownerDocument.createElement('span');span.textContent=nodes.map(node=>node.textContent||'').join('').trim();host.insertBefore(span,nodes[0]);nodes.forEach(node=>node.remove());return setMeta(span,meta);
}
function decorateBlock(block){
  const form=block.querySelector('form.cms-form');if(!form)return;
  form.querySelectorAll('.cms-field').forEach(container=>{
    const control=container.querySelector('input[name],select[name],textarea[name]'),fieldId=normalizeFieldName(control?.name);if(!fieldId)return;
    const label=container.querySelector(':scope > span');if(label)ensureDirectTextTarget(label,{part:'field-label',fieldId});
    const help=container.querySelector(':scope > small');if(help)setMeta(help,{part:'field-help',fieldId});
    if(control)setMeta(control,{part:'field-control',fieldId});
  });
  form.querySelectorAll('.cms-choice-group').forEach(group=>{
    const firstInput=group.querySelector('input[name]'),fieldId=normalizeFieldName(firstInput?.name);if(!fieldId)return;
    const legend=group.querySelector(':scope > legend');if(legend)ensureDirectTextTarget(legend,{part:'field-label',fieldId});
    [...group.querySelectorAll('.cms-choice-grid > label')].forEach((label,index)=>{
      const input=label.querySelector('input[name]'),text=label.querySelector(':scope > span');
      if(input)setMeta(input,{part:'field-control',fieldId,optionIndex:index});
      if(text)setMeta(text,{part:'option-label',fieldId,optionIndex:index});
    });
    const help=group.querySelector(':scope > small');if(help)setMeta(help,{part:'field-help',fieldId});
  });
  form.querySelectorAll('.cms-consent').forEach(container=>{
    const control=container.querySelector('input[name]'),fieldId=normalizeFieldName(control?.name);if(!fieldId)return;
    if(control)setMeta(control,{part:'field-control',fieldId});
    const text=container.querySelector(':scope > span');if(text)ensureDirectTextTarget(text,{part:'field-label',fieldId});
  });
  const button=form.querySelector(':scope > button.button');if(button)ensureDirectTextTarget(button,{part:'submit-label'});
  form.querySelectorAll('[data-cms-form-content-id]').forEach(content=>{
    const contentId=content.dataset.cmsFormContentId||'';
    setMeta(content,{part:'content-block',contentId});
    const root=content.firstElementChild;content.classList.toggle('cms-form-content-public-hidden',root?.dataset.cmsPublicHidden==='1');
    content.querySelectorAll('[data-cms-editable]').forEach(el=>setMeta(el,{part:'content-text',contentId}));
    content.querySelectorAll('[data-cms-image]').forEach(el=>setMeta(el,{part:'content-image',contentId}));
  });
}
function decorateAll(d){d.querySelectorAll('[data-cms-form-block]').forEach(decorateBlock);}
function metaFrom(el){
  const block=formBlockFrom(el);if(!block)return null;
  return {el,block,formId:Number(block.dataset.cmsFormBlock||0),part:el.dataset.cmsFormInline||'',fieldId:el.dataset.cmsFormField||'',contentId:el.dataset.cmsFormContentRef||'',optionIndex:Number(el.dataset.cmsFormOptionIndex||0)};
}
function resolveTarget(raw){
  if(!raw?.closest)return null;
  const decorated=raw.closest('[data-cms-form-inline]');if(decorated)return metaFrom(decorated);
  const block=formBlockFrom(raw);if(!block)return null;
  const content=raw.closest('[data-cms-form-content-id]');
  if(content){
    const contentId=content.dataset.cmsFormContentId||'';
    const image=raw.closest('[data-cms-image]');if(image)return metaFrom(setMeta(image,{part:'content-image',contentId}));
    const text=raw.closest('[data-cms-editable]');if(text)return metaFrom(setMeta(text,{part:'content-text',contentId}));
    return metaFrom(setMeta(content,{part:'content-block',contentId}));
  }
  const optionLabel=raw.closest('.cms-choice-grid > label');
  if(optionLabel){
    const grid=optionLabel.parentElement,labels=[...grid.children].filter(node=>node.matches?.('label')),index=Math.max(0,labels.indexOf(optionLabel));
    const input=optionLabel.querySelector('input[name]'),fieldId=normalizeFieldName(input?.name);
    if(raw.closest('input'))return metaFrom(setMeta(input,{part:'field-control',fieldId,optionIndex:index}));
    const text=optionLabel.querySelector(':scope > span')||ensureDirectTextTarget(optionLabel,{part:'option-label',fieldId,optionIndex:index});return text?metaFrom(setMeta(text,{part:'option-label',fieldId,optionIndex:index})):null;
  }
  const legend=raw.closest('.cms-choice-group > legend');
  if(legend){const group=legend.closest('.cms-choice-group'),fieldId=normalizeFieldName(group?.querySelector('input[name]')?.name),text=ensureDirectTextTarget(legend,{part:'field-label',fieldId});return text?metaFrom(text):null;}
  const field=raw.closest('.cms-field');
  if(field){
    const control=field.querySelector('input[name],select[name],textarea[name]'),fieldId=normalizeFieldName(control?.name);
    if(raw.closest('input,select,textarea'))return metaFrom(setMeta(control,{part:'field-control',fieldId}));
    const help=raw.closest('small');if(help)return metaFrom(setMeta(help,{part:'field-help',fieldId}));
    const label=field.querySelector(':scope > span'),text=ensureDirectTextTarget(label,{part:'field-label',fieldId});return text?metaFrom(text):null;
  }
  const consent=raw.closest('.cms-consent');
  if(consent){
    const control=consent.querySelector('input[name]'),fieldId=normalizeFieldName(control?.name);
    if(raw.closest('input'))return metaFrom(setMeta(control,{part:'field-control',fieldId}));
    const label=consent.querySelector(':scope > span'),text=ensureDirectTextTarget(label,{part:'field-label',fieldId});return text?metaFrom(text):null;
  }
  const button=raw.closest('form.cms-form > button.button');if(button){const text=ensureDirectTextTarget(button,{part:'submit-label'});return text?metaFrom(text):null;}
  return null;
}
function clearSelection(){
  const d=frameDoc();d?.querySelectorAll('.cms-form-inline-selected,.cms-form-inline-editing').forEach(el=>el.classList.remove('cms-form-inline-selected','cms-form-inline-editing'));
  selected=null;
}
function selectMeta(meta){
  if(!meta?.formId||!meta.el)return;
  if(selected?.el&&selected.el!==meta.el)selected.el.classList.remove('cms-form-inline-selected');
  selected=meta;meta.el.classList.add('cms-form-inline-selected');renderContext();
  ensureForm(meta.formId).then(()=>{if(selected?.el===meta.el)renderContext();}).catch(error=>{entryFor(meta.formId).error=error.message;if(selected?.el===meta.el)renderContext();});
}
function applyTextToSchema(entry,meta,text){
  const value=String(text||'').trim();
  if(meta.part==='submit-label'){if(value)entry.data.form.schema.submitLabel=value;return;}
  if(meta.part==='content-text'){
    syncContentBlockFromDom(entry,meta.contentId);return;
  }
  const field=fieldFor(entry,meta.fieldId);if(!field)return;
  if(meta.part==='field-label'){if(value)field.label=value;return;}
  if(meta.part==='field-help'){if(value)field.help=value;else delete field.help;return;}
  if(meta.part==='option-label'){const option=optionFor(entry,meta.fieldId,meta.optionIndex);if(option&&value)option.label=value;}
}
function cleanContentHtml(content){
  const clone=content.cloneNode(true);
  clone.querySelectorAll('[contenteditable]').forEach(el=>el.removeAttribute('contenteditable'));
  clone.querySelectorAll('[data-cms-form-inline]').forEach(el=>{
    el.removeAttribute('data-cms-form-inline');el.removeAttribute('data-cms-form-field');el.removeAttribute('data-cms-form-content-ref');el.removeAttribute('data-cms-form-option-index');el.removeAttribute('title');el.classList.remove('cms-form-inline-selected','cms-form-inline-editing');
  });
  clone.removeAttribute('data-cms-form-inline');clone.removeAttribute('data-cms-form-content-ref');clone.removeAttribute('title');clone.classList.remove('cms-form-inline-selected','cms-form-inline-editing','cms-form-content-public-hidden');
  return clone.innerHTML.trim();
}
function syncContentBlockFromDom(entry,contentId){
  const block=contentFor(entry,contentId),d=frameDoc(),content=d?.querySelector(`[data-cms-form-block="${entry.formId}"] [data-cms-form-content-id="${CSS.escape(contentId)}"]`);
  if(block&&content)block.html=cleanContentHtml(content);
}
function finishInline(cancel=false){
  if(!editing)return;
  const current=editing;editing=null;const el=current.el;
  if(cancel)el.innerHTML=current.originalHtml;
  el.removeAttribute('contenteditable');el.classList.remove('cms-form-inline-editing');
  if(cancel)return;
  const value=String(el.textContent||'').trim();if(!value&&current.part!=='field-help'){el.innerHTML=current.originalHtml;return;}
  ensureForm(current.formId).then(entry=>{applyTextToSchema(entry,current,el.textContent);markChanged(entry);queueSave(entry);}).catch(error=>{entryFor(current.formId).error=error.message;renderIfCurrent(entryFor(current.formId));});
}
function placeCaret(el,event){
  const d=el.ownerDocument;el.focus({preventScroll:true});let range=null;
  if(typeof d.caretPositionFromPoint==='function'){
    const position=d.caretPositionFromPoint(event.clientX,event.clientY);if(position&&el.contains(position.offsetNode)){range=d.createRange();range.setStart(position.offsetNode,position.offset);range.collapse(true);}
  }else if(typeof d.caretRangeFromPoint==='function'){
    const candidate=d.caretRangeFromPoint(event.clientX,event.clientY);if(candidate&&el.contains(candidate.startContainer))range=candidate;
  }
  if(range){const selection=d.getSelection();selection.removeAllRanges();selection.addRange(range);}
}
function startInline(meta,event){
  if(!meta?.formId||!meta.el)return;
  if(editing?.el===meta.el){placeCaret(meta.el,event);return;}
  if(editing)finishInline(false);selectMeta(meta);
  editing={...meta,originalHtml:meta.el.innerHTML};meta.el.setAttribute('contenteditable','true');meta.el.classList.add('cms-form-inline-editing');placeCaret(meta.el,event);
}
async function openImagePicker(entry,meta){
  if(!mediaDialog||!mediaGrid||!window.MediaLibrary)return;
  mediaDialog.showModal();mediaGrid.innerHTML='<p>Carregando…</p>';
  try{
    await MediaLibrary.load();
    mediaGrid.innerHTML=MediaLibrary.images.map((item,index)=>`<button type="button" class="media-item" data-form-media="${index}"><img src="${esc(item.src)}" alt=""><span>${esc(item.label)}</span></button>`).join('')||'<p>Nenhuma imagem.</p>';
    mediaGrid.querySelectorAll('[data-form-media]').forEach(button=>button.onclick=()=>applyContentImage(entry,meta,MediaLibrary.images[Number(button.dataset.formMedia)]));
  }catch(error){mediaGrid.innerHTML=`<p>${esc(error.message||'Não foi possível carregar a biblioteca.')}</p>`;}
}
function applyContentImage(entry,meta,item){
  const image=meta.el;if(!image)return;
  image.src=item.src;image.alt=item.alt||item.label||'';image.dataset.mediaAssetId=String(item.id||'');if(item.versionId)image.dataset.mediaVersionId=String(item.versionId);else delete image.dataset.mediaVersionId;
  if(item.focal_x!=null)image.dataset.focalX=String(item.focal_x);if(item.focal_y!=null)image.dataset.focalY=String(item.focal_y);
  syncContentBlockFromDom(entry,meta.contentId);markChanged(entry);queueSave(entry);mediaDialog?.close();renderContext();
}
function neutralizeLegacyWrapperSelection(d){
  d.querySelectorAll('[data-cms-form-block]').forEach(block=>{block.onclick=null;block.removeAttribute('title');block.dataset.cmsFormInlineOwned='1';});
}
function injectFrameStyle(d){
  if(d.getElementById('cms-form-unified-editor-style'))return;
  const style=d.createElement('style');style.id='cms-form-unified-editor-style';style.textContent=`
    [data-cms-form-inline]{cursor:text}
    input[data-cms-form-inline],select[data-cms-form-inline],textarea[data-cms-form-inline],[data-cms-form-inline="content-image"]{cursor:pointer}
    [data-cms-form-inline="content-block"]{cursor:default}
    .cms-form-inline-selected{outline:2px solid #186f4d!important;outline-offset:3px}
    .cms-form-inline-editing{outline:2px solid #186f4d!important;outline-offset:3px;background:rgba(255,255,255,.72)}
    [data-cms-form-content-id]{position:relative}
    .cms-editor-preview [data-cms-form-content-id]::before{content:attr(data-cms-form-content-label);display:none;position:absolute;right:0;top:0;z-index:4;padding:3px 6px;background:#111;color:#fff;font:10px/1.2 monospace}
    .cms-editor-preview [data-cms-form-content-id]:hover::before{display:block}
    .cms-editor-preview [data-cms-public-hidden="1"][hidden]{display:block!important;opacity:.5}
    .cms-editor-preview .cms-form-content-public-hidden::before{display:block;content:"OCULTO NO SITE · " attr(data-cms-form-content-label)}
  `;d.head.append(style);
}
function installFrameCapture(d){
  if(d.documentElement.dataset.cmsFormUnifiedInstalled==='1')return;
  d.documentElement.dataset.cmsFormUnifiedInstalled='1';injectFrameStyle(d);decorateAll(d);
  d.addEventListener('pointerdown',event=>{
    const meta=resolveTarget(event.target);if(!meta)return;
    if(['field-control','content-image','content-block'].includes(meta.part)){
      event.preventDefault();event.stopImmediatePropagation();selectMeta(meta);return;
    }
    if(['field-label','option-label','field-help','submit-label','content-text'].includes(meta.part)){
      event.preventDefault();event.stopImmediatePropagation();startInline(meta,event);
    }
  },true);
  d.addEventListener('click',event=>{
    const meta=resolveTarget(event.target);if(!meta)return;
    event.preventDefault();event.stopImmediatePropagation();
    if(['field-label','option-label','field-help','submit-label','content-text'].includes(meta.part))startInline(meta,event);else selectMeta(meta);
  },true);
  d.addEventListener('keydown',event=>{
    const target=event.target?.closest?.('[data-cms-form-inline]');if(!target||editing?.el!==target)return;
    if(event.key==='Escape'){event.preventDefault();finishInline(true);target.blur();return;}
    if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();target.blur();}
  },true);
  d.addEventListener('blur',event=>{const target=event.target?.closest?.('[data-cms-form-inline]');if(target&&editing?.el===target)finishInline(false);},true);
  neutralizeLegacyWrapperSelection(d);
}
function installCurrentDocument(){
  const d=frameDoc();if(!d)return;
  if(d.readyState==='loading')d.addEventListener('DOMContentLoaded',()=>installFrameCapture(d),{once:true});else installFrameCapture(d);
}
frame.addEventListener('load',()=>installFrameCapture(frameDoc()));
installCurrentDocument();
})();