(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const inspector=document.querySelector('#inspector');
if(!frame||!inspector)return;

const esc=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot',"'":'&#039;'}[char]));
const forms=new Map();
let selected=null;
let editing=null;

function frameDoc(){return frame.contentDocument||null}
function normalizeFieldName(name){return String(name||'').replace(/\[\]$/,'')}
function formBlockFrom(node){return node?.closest?.('[data-cms-form-block]')||null}
function entryFor(formId){
  if(!forms.has(formId))forms.set(formId,{formId,data:null,promise:null,queue:Promise.resolve(),dirty:false,saving:false,changeSeq:0,error:''});
  return forms.get(formId);
}
async function json(url,options={}){
  const response=await fetch(url,{credentials:'same-origin',...options});
  const body=await response.json().catch(()=>({}));
  if(!response.ok){const error=new Error(body?.error?.message||body?.error?.code||`HTTP ${response.status}`);error.code=body?.error?.code;throw error}
  return body;
}
async function post(url,payload){return json(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})}
function ensureForm(formId){
  const entry=entryFor(formId);
  if(entry.data)return Promise.resolve(entry);
  if(entry.promise)return entry.promise;
  entry.promise=json(`/admin/api/cms-form-load.php?form=${formId}`).then(data=>{entry.data=data;entry.error='';return entry}).catch(error=>{entry.error=error.message;throw error}).finally(()=>{entry.promise=null});
  return entry.promise;
}
function fieldFor(entry,fieldId){return entry.data?.form?.schema?.fields?.find(field=>field.id===fieldId)||null}
function optionFor(entry,fieldId,index){const field=fieldFor(entry,fieldId);return Array.isArray(field?.options)?field.options[index]||null:null}
function contextLabel(context){
  if(context.part==='field-label')return 'Rótulo do campo';
  if(context.part==='option-label')return 'Opção';
  if(context.part==='field-help')return 'Texto de ajuda';
  if(context.part==='submit-label')return 'Texto do botão';
  if(context.part==='field-control')return 'Campo do formulário';
  return 'Formulário';
}
function statusText(entry){
  if(entry.saving)return 'Salvando rascunho…';
  if(entry.error)return entry.error;
  if(entry.dirty)return 'Alterações ainda não salvas.';
  const form=entry.data?.form;
  if(!form)return 'Carregando formulário…';
  return form.publishedRevision==null||Number(form.publishedRevision)!==Number(form.draftRevision)?'Rascunho salvo. Falta publicar.':'Formulário publicado e atualizado.';
}
function renderContext(){
  if(!selected)return;
  const entry=entryFor(selected.formId);
  const form=entry.data?.form;
  const field=form?fieldFor(entry,selected.fieldId):null;
  const option=form&&selected.part==='option-label'?optionFor(entry,selected.fieldId,selected.optionIndex):null;
  inspector.innerHTML=`<div class="inspector-section form-quick-editor" data-cms-form-quick>
    <header><p class="eyebrow">Formulário · edição visual</p><h2>${esc(contextLabel(selected))}</h2><p class="form-quick-status">${esc(statusText(entry))}</p></header>
    ${selected.part==='field-control'?'<p class="form-quick-note">Este é o controle do campo. Para alterar o texto, clique diretamente no rótulo ou na opção visível na página.</p>':'<p class="form-quick-note">Edite o texto diretamente no ponto em que ele aparece na página.</p>'}
    ${field?`<div class="form-quick-context"><span>Campo</span><strong>${esc(field.label)}</strong><small>${esc(field.type)}</small></div>`:''}
    ${option?`<label>Valor interno da opção<input id="fq-option-value" value="${esc(option.value)}"></label><p class="form-quick-warning">O valor interno não aparece na página. Alterá-lo pode afetar regras condicionais que dependam dessa opção.</p>`:''}
    <div class="button-row form-quick-actions"><button type="button" id="fq-publish" class="primary" ${!form?'disabled':''}>Publicar alterações do formulário</button></div>
    <a class="panel-button" href="${form?`/admin/forms.php?activity=${Number(form.activityId||0)}&form=${Number(form.id)}`:'#'}" target="_blank" rel="noopener">Abrir editor completo ↗</a>
  </div>`;
  const valueInput=inspector.querySelector('#fq-option-value');
  if(valueInput&&option){
    valueInput.addEventListener('change',event=>{
      const next=String(event.currentTarget.value||'').trim();
      if(!next){event.currentTarget.value=option.value;return}
      option.value=next;
      const input=selected?.el?.closest('label')?.querySelector('input');if(input)input.value=next;
      markChanged(entry);queueSave(entry);
    });
  }
  inspector.querySelector('#fq-publish')?.addEventListener('click',()=>publishEntry(entry));
}
function renderIfCurrent(entry){if(selected?.formId===entry.formId)renderContext()}
function applyTextToSchema(entry,context,text){
  const value=String(text||'').trim();
  if(context.part==='submit-label'){if(value)entry.data.form.schema.submitLabel=value;return}
  const field=fieldFor(entry,context.fieldId);if(!field)return;
  if(context.part==='field-label'){if(value)field.label=value;return}
  if(context.part==='field-help'){if(value)field.help=value;else delete field.help;return}
  if(context.part==='option-label'){const option=optionFor(entry,context.fieldId,context.optionIndex);if(option&&value)option.label=value}
}
function markChanged(entry){entry.dirty=true;entry.changeSeq++;renderIfCurrent(entry)}
function queueSave(entry){
  entry.queue=entry.queue.then(async()=>{
    if(!entry.data||!entry.dirty)return;
    const seq=entry.changeSeq;
    const form=entry.data.form;
    const revision=form.draftRevision;
    const schema=JSON.parse(JSON.stringify(form.schema));
    entry.saving=true;renderIfCurrent(entry);
    try{
      const saved=await post('/admin/api/cms-form-save.php',{csrf:entry.data.csrf,formId:form.id,revision,title:form.title,schema});
      if(entry.changeSeq===seq){entry.data.form=saved.form;entry.dirty=false}
      else{
        entry.data.form.draftRevision=saved.form.draftRevision;
        entry.data.form.draftUpdatedAt=saved.form.draftUpdatedAt;
        entry.data.form.publishedRevision=saved.form.publishedRevision;
      }
      entry.error='';
    }catch(error){
      entry.error=error.code==='revision_conflict'?'O formulário foi alterado em outra sessão. Recarregue a página antes de continuar.':error.message;
      entry.dirty=true;
    }finally{entry.saving=false;renderIfCurrent(entry)}
  });
  return entry.queue;
}
async function publishEntry(entry){
  if(!entry.data)return;
  if(entry.dirty)await queueSave(entry);else await entry.queue;
  if(entry.dirty||entry.error)return;
  const button=inspector.querySelector('#fq-publish');if(button)button.disabled=true;
  try{
    const result=await post('/admin/api/cms-form-publish.php',{csrf:entry.data.csrf,formId:entry.data.form.id});
    entry.data.form.publishedRevision=result.publishedRevision;entry.error='';
  }catch(error){entry.error=error.message}
  renderIfCurrent(entry);
}
function setMeta(el,{part,fieldId='',optionIndex=0}){
  if(!el)return null;
  el.dataset.cmsFormInline=part;
  el.dataset.cmsFormField=fieldId;
  if(part==='option-label')el.dataset.cmsFormOptionIndex=String(optionIndex);else delete el.dataset.cmsFormOptionIndex;
  el.title=part==='field-control'?'Clique para selecionar o campo':'Clique para editar';
  return el;
}
function ensureDirectTextTarget(host,meta){
  if(!host)return null;
  const existing=host.querySelector(':scope > [data-cms-form-inline]');if(existing)return existing;
  const nodes=[...host.childNodes].filter(node=>node.nodeType===3&&String(node.textContent||'').trim()!=='');
  if(!nodes.length)return null;
  const first=nodes[0];
  const span=host.ownerDocument.createElement('span');
  span.textContent=nodes.map(node=>node.textContent||'').join('').trim();
  host.insertBefore(span,first);nodes.forEach(node=>node.remove());
  return setMeta(span,meta);
}
function metaFrom(el){
  const block=formBlockFrom(el);if(!block)return null;
  return {el,block,formId:Number(block.dataset.cmsFormBlock||0),part:el.dataset.cmsFormInline||'',fieldId:el.dataset.cmsFormField||'',optionIndex:Number(el.dataset.cmsFormOptionIndex||0)};
}
function decorateBlock(block){
  const form=block.querySelector('form.cms-form');if(!form)return;
  form.querySelectorAll('.cms-field').forEach(container=>{
    const control=container.querySelector('input[name],select[name],textarea[name]');
    const fieldId=normalizeFieldName(control?.name);if(!fieldId)return;
    const label=container.querySelector(':scope > span');if(label)ensureDirectTextTarget(label,{part:'field-label',fieldId});
    const help=container.querySelector(':scope > small');if(help)setMeta(help,{part:'field-help',fieldId});
    if(control)setMeta(control,{part:'field-control',fieldId});
  });
  form.querySelectorAll('.cms-choice-group').forEach(group=>{
    const firstInput=group.querySelector('input[name]');
    const fieldId=normalizeFieldName(firstInput?.name);if(!fieldId)return;
    const legend=group.querySelector(':scope > legend');if(legend)ensureDirectTextTarget(legend,{part:'field-label',fieldId});
    [...group.querySelectorAll('.cms-choice-grid > label')].forEach((label,index)=>{
      const input=label.querySelector('input[name]');const text=label.querySelector(':scope > span');
      if(input)setMeta(input,{part:'field-control',fieldId,optionIndex:index});
      if(text)setMeta(text,{part:'option-label',fieldId,optionIndex:index});
    });
    const help=group.querySelector(':scope > small');if(help)setMeta(help,{part:'field-help',fieldId});
  });
  form.querySelectorAll('.cms-consent').forEach(container=>{
    const control=container.querySelector('input[name]');
    const fieldId=normalizeFieldName(control?.name);if(!fieldId)return;
    if(control)setMeta(control,{part:'field-control',fieldId});
    const text=container.querySelector(':scope > span');if(text)ensureDirectTextTarget(text,{part:'field-label',fieldId});
  });
  const button=form.querySelector(':scope > button.button');if(button)ensureDirectTextTarget(button,{part:'submit-label'});
}
function decorateAll(d){d.querySelectorAll('[data-cms-form-block]').forEach(decorateBlock)}
function selectMeta(meta){
  if(!meta?.formId||!meta.el)return;
  if(selected?.el&&selected.el!==meta.el)selected.el.classList.remove('cms-form-inline-selected');
  selected=meta;meta.el.classList.add('cms-form-inline-selected');
  renderContext();
  ensureForm(meta.formId).then(()=>{if(selected?.el===meta.el)renderContext()}).catch(error=>{entryFor(meta.formId).error=error.message;if(selected?.el===meta.el)renderContext()});
}
function clearSelection(){if(selected?.el)selected.el.classList.remove('cms-form-inline-selected');selected=null}
function finishInline(cancel=false){
  if(!editing)return;
  const current=editing;editing=null;
  const el=current.el;
  if(cancel)el.textContent=current.originalText;
  el.removeAttribute('contenteditable');el.classList.remove('cms-form-inline-editing');
  if(cancel)return;
  const value=String(el.textContent||'').trim();
  if(!value&&current.part!=='field-help'){el.textContent=current.originalText;return}
  if(value===String(current.originalText||'').trim())return;
  ensureForm(current.formId).then(entry=>{applyTextToSchema(entry,current,el.textContent);markChanged(entry);queueSave(entry)}).catch(error=>{entryFor(current.formId).error=error.message;renderIfCurrent(entryFor(current.formId))});
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
function startInline(meta,event){
  if(!meta?.formId||!meta.el)return;
  if(editing?.el===meta.el){placeCaret(meta.el,event);return}
  if(editing)finishInline(false);
  selectMeta(meta);
  editing={...meta,originalText:meta.el.textContent};
  meta.el.setAttribute('contenteditable','true');meta.el.classList.add('cms-form-inline-editing');
  placeCaret(meta.el,event);
}
function injectFrameStyle(d){
  if(d.getElementById('cms-form-inline-editor-style'))return;
  const style=d.createElement('style');style.id='cms-form-inline-editor-style';
  style.textContent='[data-cms-form-inline="field-label"],[data-cms-form-inline="option-label"],[data-cms-form-inline="field-help"],[data-cms-form-inline="submit-label"]{cursor:text}[data-cms-form-inline="field-control"]{cursor:pointer}.cms-form-inline-selected{outline:1px solid currentColor!important;outline-offset:3px}.cms-form-inline-editing{outline:2px solid currentColor!important;outline-offset:3px;cursor:text!important}';
  d.head.append(style);
}
function installFrameCapture(d=frameDoc()){
  if(!d||d.__cmsFormVisualEdit)return;
  d.__cmsFormVisualEdit=true;injectFrameStyle(d);decorateAll(d);
  d.addEventListener('pointerdown',event=>{
    const target=event.target?.closest?.('[data-cms-form-inline]');
    if(!target){if(editing)finishInline(false);return}
    const meta=metaFrom(target);if(!meta?.formId)return;
    event.stopImmediatePropagation();
    if(meta.part==='field-control'){
      if(editing)finishInline(false);
      selectMeta(meta);
      return;
    }
    startInline(meta,event);
  },true);
  d.addEventListener('click',event=>{
    const target=event.target?.closest?.('[data-cms-form-inline]');if(!target)return;
    const meta=metaFrom(target);if(!meta?.formId)return;
    event.preventDefault();event.stopImmediatePropagation();
  },true);
  d.addEventListener('keydown',event=>{
    const target=event.target?.closest?.('[data-cms-form-inline]');if(!target||editing?.el!==target)return;
    if(event.key==='Escape'){event.preventDefault();finishInline(true);target.blur();return}
    if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();target.blur()}
  },true);
  d.addEventListener('blur',event=>{
    const target=event.target?.closest?.('[data-cms-form-inline]');if(target&&editing?.el===target)finishInline(false)
  },true);
}
function installCurrentDocument(){
  const d=frameDoc();if(!d)return;
  if(d.readyState==='loading')d.addEventListener('DOMContentLoaded',()=>installFrameCapture(d),{once:true});
  else installFrameCapture(d);
}
frame.addEventListener('load',()=>installCurrentDocument());
installCurrentDocument();
})();
