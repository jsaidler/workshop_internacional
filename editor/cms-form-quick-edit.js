(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const inspector=document.querySelector('#inspector');
if(!frame||!inspector)return;

const esc=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
const forms=new Map();
let selected=null;
let editing=null;
let inspectorObserver=null;

function frameDoc(){return frame.contentDocument||null}
function normalizeFieldName(name){return String(name||'').replace(/\[\]$/,'')}
function selectedBlock(){return frameDoc()?.querySelector('[data-cms-form-block].cms-selection')||null}
function formBlockFrom(node){return node?.closest?.('[data-cms-form-block]')||null}
function statusText(entry){
  if(entry.saving)return 'Salvando rascunho…';
  if(entry.dirty)return 'Alterações ainda não salvas.';
  const form=entry.data?.form;
  if(!form)return 'Carregando formulário…';
  return form.publishedRevision==null||Number(form.publishedRevision)!==Number(form.draftRevision)?'Rascunho salvo. Falta publicar.':'Formulário publicado e atualizado.';
}
async function json(url,options={}){
  const response=await fetch(url,{credentials:'same-origin',...options});
  const body=await response.json().catch(()=>({}));
  if(!response.ok){const error=new Error(body?.error?.message||body?.error?.code||`HTTP ${response.status}`);error.code=body?.error?.code;throw error}
  return body;
}
async function post(url,payload){return json(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})}
function entryFor(formId){
  if(!forms.has(formId))forms.set(formId,{formId,data:null,promise:null,saveQueue:Promise.resolve(),dirty:false,saving:false,changeSeq:0,error:''});
  return forms.get(formId);
}
function ensureForm(formId){
  const entry=entryFor(formId);
  if(entry.data)return Promise.resolve(entry);
  if(entry.promise)return entry.promise;
  entry.promise=json(`/admin/api/cms-form-load.php?form=${formId}`).then(data=>{entry.data=data;entry.error='';return entry}).catch(error=>{entry.error=error.message;throw error}).finally(()=>{entry.promise=null});
  return entry.promise;
}
function fieldFor(entry,fieldId){return entry.data?.form?.schema?.fields?.find(field=>field.id===fieldId)||null}
function optionFor(entry,fieldId,index){const field=fieldFor(entry,fieldId);return Array.isArray(field?.options)?field.options[index]||null:null}
function markChanged(entry){entry.dirty=true;entry.changeSeq++;renderContext()}
function applyTextToSchema(entry,context,text){
  const value=String(text||'').trim();
  if(context.part==='submit-label'){if(value)entry.data.form.schema.submitLabel=value;return}
  const field=fieldFor(entry,context.fieldId);if(!field)return;
  if(context.part==='field-label'){if(value)field.label=value;return}
  if(context.part==='field-help'){if(value)field.help=value;else delete field.help;return}
  if(context.part==='option-label'){const option=optionFor(entry,context.fieldId,context.optionIndex);if(option&&value)option.label=value}
}
function contextLabel(context){
  if(context.part==='field-label')return 'Rótulo do campo';
  if(context.part==='option-label')return 'Opção';
  if(context.part==='field-help')return 'Texto de ajuda';
  if(context.part==='submit-label')return 'Texto do botão';
  return 'Texto do formulário';
}
function renderContext(){
  if(!selected)return;
  const entry=entryFor(selected.formId);
  const form=entry.data?.form;
  const field=form?fieldFor(entry,selected.fieldId):null;
  const option=form&&selected.part==='option-label'?optionFor(entry,selected.fieldId,selected.optionIndex):null;
  inspector.innerHTML=`<div class="inspector-section form-quick-editor" data-cms-form-quick>
    <header><p class="eyebrow">Formulário · edição visual</p><h2>${esc(contextLabel(selected))}</h2><p class="form-quick-status">${esc(entry.error||statusText(entry))}</p></header>
    <p class="form-quick-note">Edite o texto diretamente no lugar em que ele aparece na página.</p>
    ${field?`<div class="form-quick-context"><span>Campo</span><strong>${esc(field.label)}</strong><small>${esc(field.type)}</small></div>`:''}
    ${option?`<label>Valor interno da opção<input id="fq-option-value" value="${esc(option.value)}"></label><p class="form-quick-warning">O valor interno não aparece na página. Alterá-lo pode afetar regras condicionais que dependam dessa opção.</p>`:''}
    <div class="button-row form-quick-actions"><button type="button" id="fq-publish" class="primary" ${!form?'disabled':''}>Publicar alterações do formulário</button></div>
    <a class="panel-button" href="${form?`/admin/forms.php?activity=${Number(form.activityId||0)}&form=${Number(form.id)}`:'#'}" target="_blank" rel="noopener">Abrir editor completo ↗</a>
  </div>`;
  const valueInput=inspector.querySelector('#fq-option-value');
  if(valueInput&&option){
    valueInput.addEventListener('change',async event=>{
      const next=String(event.currentTarget.value||'').trim();
      if(!next){event.currentTarget.value=option.value;return}
      option.value=next;
      const input=selected.el?.closest('label')?.querySelector('input');if(input)input.value=next;
      markChanged(entry);await saveEntry(entry);
    });
  }
  inspector.querySelector('#fq-publish')?.addEventListener('click',()=>publishEntry(entry));
}
function renderFormHint(block){
  const formId=Number(block?.dataset.cmsFormBlock||0);if(!formId)return;
  const entry=entryFor(formId);
  inspector.innerHTML=`<div class="inspector-section form-quick-editor" data-cms-form-quick>
    <header><p class="eyebrow">Formulário</p><h2>Edição visual</h2><p class="form-quick-status">${esc(entry.error||statusText(entry))}</p></header>
    <p class="form-quick-note">O formulário não é um bloco de texto único. Clique diretamente em um rótulo, opção, texto de ajuda ou no texto do botão para editar naquele ponto.</p>
    <a class="panel-button" href="${entry.data?`/admin/forms.php?activity=${Number(entry.data.form.activityId||0)}&form=${Number(entry.data.form.id)}`:'#'}" target="_blank" rel="noopener">Abrir editor completo ↗</a>
  </div>`;
  ensureForm(formId).then(()=>{if(selectedBlock()===block&&!selected)renderFormHint(block)}).catch(()=>{});
}
function saveEntry(entry){
  if(!entry.data||!entry.dirty)return entry.saveQueue;
  entry.saveQueue=entry.saveQueue.then(async()=>{
    if(!entry.dirty||!entry.data)return;
    const seq=entry.changeSeq;
    const form=entry.data.form;
    const schema=JSON.parse(JSON.stringify(form.schema));
    const revision=form.draftRevision;
    entry.saving=true;renderContext();
    try{
      const saved=await post('/admin/api/cms-form-save.php',{csrf:entry.data.csrf,formId:form.id,revision,title:form.title,schema});
      if(entry.changeSeq===seq){entry.data.form=saved.form;entry.dirty=false}else{
        entry.data.form.draftRevision=saved.form.draftRevision;
        entry.data.form.draftUpdatedAt=saved.form.draftUpdatedAt;
        entry.data.form.publishedRevision=saved.form.publishedRevision;
      }
      entry.error='';
    }catch(error){
      entry.error=error.code==='revision_conflict'?'O formulário foi alterado em outra sessão. Recarregue a página antes de continuar.':error.message;
      entry.dirty=true;
    }finally{entry.saving=false;renderContext()}
    if(entry.dirty&&entry.changeSeq>seq)setTimeout(()=>saveEntry(entry),0);
  });
  return entry.saveQueue;
}
async function publishEntry(entry){
  if(!entry.data)return;
  await saveEntry(entry);
  if(entry.dirty||entry.error)return;
  const button=inspector.querySelector('#fq-publish');if(button)button.disabled=true;
  try{
    const result=await post('/admin/api/cms-form-publish.php',{csrf:entry.data.csrf,formId:entry.data.form.id});
    entry.data.form.publishedRevision=result.publishedRevision;entry.error='';
  }catch(error){entry.error=error.message}
  renderContext();
}
function metaFrom(el){
  const block=formBlockFrom(el);if(!block)return null;
  return {el,block,formId:Number(block.dataset.cmsFormBlock||0),part:el.dataset.cmsFormInline||'',fieldId:el.dataset.cmsFormField||'',optionIndex:Number(el.dataset.cmsFormOptionIndex||0)};
}
function clearInlineSelection(){
  if(editing)finishInline(false);
  if(selected?.el)selected.el.classList.remove('cms-form-inline-selected');
  selected=null;
}
function selectInline(el){
  const next=metaFrom(el);if(!next?.formId)return;
  if(selected?.el&&selected.el!==el)selected.el.classList.remove('cms-form-inline-selected');
  selected=next;el.classList.add('cms-form-inline-selected');
  ensureForm(next.formId).then(entry=>{if(selected?.el===el){applyTextToSchema(entry,next,el.textContent);renderContext()}}).catch(()=>renderContext());
  renderContext();
}
function finishInline(cancel=false){
  if(!editing)return;
  const current=editing;editing=null;
  const el=current.el;
  if(cancel)el.textContent=current.originalText;
  el.removeAttribute('contenteditable');el.classList.remove('cms-form-inline-editing');
  if(cancel)return;
  const value=String(el.textContent||'').trim();
  if(!value&&current.part!=='field-help'){el.textContent=current.originalText;return}
  ensureForm(current.formId).then(entry=>{applyTextToSchema(entry,current,el.textContent);markChanged(entry);saveEntry(entry)}).catch(()=>renderContext());
}
function placeCaret(el,event){
  const d=el.ownerDocument;el.focus({preventScroll:true});
  let range=null;
  if(typeof d.caretPositionFromPoint==='function'){
    const position=d.caretPositionFromPoint(event.clientX,event.clientY);
    if(position&&el.contains(position.offsetNode)){range=d.createRange();range.setStart(position.offsetNode,position.offset);range.collapse(true)}
  }else if(typeof d.caretRangeFromPoint==='function'){
    const candidate=d.caretRangeFromPoint(event.clientX,event.clientY);if(candidate&&el.contains(candidate.startContainer))range=candidate;
  }
  if(range){const selection=d.getSelection();selection.removeAllRanges();selection.addRange(range)}
}
function startInline(el,event){
  if(editing?.el===el){placeCaret(el,event);return}
  if(editing)finishInline(false);
  selectInline(el);
  const meta=metaFrom(el);if(!meta)return;
  editing={...meta,originalText:el.textContent};
  el.setAttribute('contenteditable','true');el.classList.add('cms-form-inline-editing');placeCaret(el,event);
}
function decorateDirectText(host,meta){
  if(!host)return null;
  const existing=host.querySelector(':scope > [data-cms-form-inline]');if(existing)return existing;
  const nodes=[...host.childNodes].filter(node=>node.nodeType===3&&String(node.textContent||'').trim()!=='');
  if(!nodes.length)return null;
  const text=nodes.map(node=>node.textContent||'').join('').trim();if(!text)return null;
  const span=host.ownerDocument.createElement('span');span.textContent=text;
  host.insertBefore(span,nodes[0]);nodes.forEach(node=>node.remove());
  if(host.querySelector(':scope > [aria-hidden="true"]'))span.after(host.ownerDocument.createTextNode(' '));
  decorateInline(span,meta);return span;
}
function decorateInline(el,{part,fieldId='',optionIndex=0}){
  if(!el)return;
  el.dataset.cmsFormInline=part;el.dataset.cmsFormField=fieldId;
  if(part==='option-label')el.dataset.cmsFormOptionIndex=String(optionIndex);
  el.title='Clique para editar';
}
function decorateBlock(block){
  const form=block.querySelector('form.cms-form');if(!form)return;
  form.querySelectorAll('.cms-field').forEach(container=>{
    const control=container.querySelector('input[name],select[name],textarea[name]');const fieldId=normalizeFieldName(control?.name);if(!fieldId)return;
    decorateDirectText(container.querySelector(':scope > span'),{part:'field-label',fieldId});
    const help=container.querySelector(':scope > small');if(help)decorateInline(help,{part:'field-help',fieldId});
  });
  form.querySelectorAll('.cms-choice-group').forEach(group=>{
    const control=group.querySelector('input[name]');const fieldId=normalizeFieldName(control?.name);if(!fieldId)return;
    decorateDirectText(group.querySelector(':scope > legend'),{part:'field-label',fieldId});
    [...group.querySelectorAll('.cms-choice-grid > label')].forEach((label,index)=>decorateInline(label.querySelector(':scope > span'),{part:'option-label',fieldId,optionIndex:index}));
    const help=group.querySelector(':scope > small');if(help)decorateInline(help,{part:'field-help',fieldId});
  });
  form.querySelectorAll('.cms-consent').forEach(container=>{
    const control=container.querySelector('input[name]');const fieldId=normalizeFieldName(control?.name);if(!fieldId)return;
    decorateDirectText(container.querySelector(':scope > span'),{part:'field-label',fieldId});
  });
  const button=form.querySelector(':scope > button.button');if(button)decorateDirectText(button,{part:'submit-label'});
}
function injectFrameStyle(d){
  if(d.getElementById('cms-form-inline-editor-style'))return;
  const style=d.createElement('style');style.id='cms-form-inline-editor-style';style.textContent='[data-cms-form-inline]{cursor:text;border-radius:2px}[data-cms-form-inline]:hover{outline:1px dashed currentColor;outline-offset:3px}.cms-form-inline-selected{outline:1px solid currentColor!important;outline-offset:3px}.cms-form-inline-editing{outline:2px solid currentColor!important;outline-offset:3px;cursor:text!important}';d.head.append(style);
}
function installFrameCapture(){
  const d=frameDoc();if(!d||d.__cmsFormVisualEdit)return;
  d.__cmsFormVisualEdit=true;injectFrameStyle(d);
  d.querySelectorAll('[data-cms-form-block]').forEach(block=>{decorateBlock(block);const id=Number(block.dataset.cmsFormBlock||0);if(id)ensureForm(id).catch(()=>{})});
  d.addEventListener('pointerdown',event=>{
    const target=event.target?.closest?.('[data-cms-form-inline]');
    if(!target){if(event.target?.closest?.('[data-cms-form-block]'))clearInlineSelection();return}
    event.stopImmediatePropagation();startInline(target,event);
  },true);
  d.addEventListener('click',event=>{
    const target=event.target?.closest?.('[data-cms-form-inline]');if(!target)return;
    event.preventDefault();event.stopImmediatePropagation();
  },true);
  d.addEventListener('input',event=>{
    const target=event.target?.closest?.('[data-cms-form-inline]');if(!target||editing?.el!==target)return;
    const meta=metaFrom(target);if(!meta)return;
    ensureForm(meta.formId).then(entry=>{applyTextToSchema(entry,meta,target.textContent);markChanged(entry)}).catch(()=>{});
  },true);
  d.addEventListener('keydown',event=>{
    const target=event.target?.closest?.('[data-cms-form-inline]');if(!target||editing?.el!==target)return;
    if(event.key==='Escape'){event.preventDefault();finishInline(true);target.blur();return}
    if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();target.blur()}
  },true);
  d.addEventListener('blur',event=>{const target=event.target?.closest?.('[data-cms-form-inline]');if(target&&editing?.el===target)finishInline(false)},true);
}
function maybeExplainSelectedForm(){
  if(selected)return;
  const block=selectedBlock();if(!block)return;
  if(inspector.querySelector('[data-cms-form-quick]'))return;
  renderFormHint(block);
}
frame.addEventListener('load',()=>setTimeout(installFrameCapture,0));
inspectorObserver=new MutationObserver(()=>setTimeout(maybeExplainSelectedForm,0));
inspectorObserver.observe(inspector,{childList:true,subtree:true});
})();
