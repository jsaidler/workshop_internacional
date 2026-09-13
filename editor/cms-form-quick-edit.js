(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const inspector=document.querySelector('#inspector');
if(!frame||!inspector)return;

const esc=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
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
  return 'Texto do formulário';
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
    <p class="form-quick-note">O texto visível é editado diretamente na página. Este painel mostra apenas propriedades que não aparecem no layout.</p>
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
  el.dataset.cmsFormInline=part;
  el.dataset.cmsFormField=fieldId;
  if(part==='option-label')el.dataset.cmsFormOptionIndex=String(optionIndex);else delete el.dataset.cmsFormOptionIndex;
  el.title='Clique para editar';
  return el;
}
function ensureDirectTextTarget(host,meta){
  if(!host)return null;
  const existing=host.querySelector(':scope > [data-cms-form-inline]');if(existing)return existing;
  const nodes=[...host.childNodes].filter(node=>node.nodeType===3&&String(node.textContent||'').trim()!=='');
  if(!nodes.length)return null;
  const text=nodes.map(node=>node.textContent||'').join('').trim();if(!text)return null;
  const span=host.ownerDocument.createElement('span');span.textContent=text;
  host.insertBefore(span,nodes[0]);nodes.forEach(node=>node.remove());
  return setMeta(span,meta);
}
function metaFromDecorated(el){
  const block=formBlockFrom(el);if(!block)return null;
  return {el,block,formId:Number(block.dataset.cmsFormBlock||0),part:el.dataset.cmsFormInline||'',fieldId:el.dataset.cmsFormField||'',optionIndex:Number(el.dataset.cmsFormOptionIndex||0)};
}
function resolveEditableTarget(raw){
  if(!raw?.closest)return null;
  const decorated=raw.closest('[data-cms-form-inline]');if(decorated)return metaFromDecorated(decorated);
  const block=formBlockFrom(raw);if(!block)return null;
  const form=raw.closest('form.cms-form')||block.querySelector('form.cms-form');if(!form)return null;

  const optionSpan=raw.closest('.cms-choice-grid > label > span');
  if(optionSpan){
    const label=optionSpan.closest('label'),grid=label?.parentElement;
    const input=label?.querySelector('input[name]');const fieldId=normalizeFieldName(input?.name);
    const labels=grid?[...grid.children].filter(node=>node.matches?.('label')):[];
    const optionIndex=Math.max(0,labels.indexOf(label));
    return metaFromDecorated(setMeta(optionSpan,{part:'option-label',fieldId,optionIndex}));
  }
  const legend=raw.closest('.cms-choice-group > legend');
  if(legend){
    const group=legend.closest('.cms-choice-group');const input=group?.querySelector('input[name]');const fieldId=normalizeFieldName(input?.name);
    const el=ensureDirectTextTarget(legend,{part:'field-label',fieldId});return el?metaFromDecorated(el):null;
  }
  const fieldLabel=raw.closest('.cms-field > span');
  if(fieldLabel){
    const container=fieldLabel.closest('.cms-field');const control=container?.querySelector('input[name],select[name],textarea[name]');const fieldId=normalizeFieldName(control?.name);
    const el=ensureDirectTextTarget(fieldLabel,{part:'field-label',fieldId});return el?metaFromDecorated(el):null;
  }
  const consentLabel=raw.closest('.cms-consent > span');
  if(consentLabel){
    const container=consentLabel.closest('.cms-consent');const input=container?.querySelector('input[name]');const fieldId=normalizeFieldName(input?.name);
    const el=ensureDirectTextTarget(consentLabel,{part:'field-label',fieldId});return el?metaFromDecorated(el):null;
  }
  const help=raw.closest('.cms-field > small,.cms-choice-group > small');
  if(help){
    const container=help.parentElement;const control=container?.querySelector('input[name],select[name],textarea[name]');const fieldId=normalizeFieldName(control?.name);
    return metaFromDecorated(setMeta(help,{part:'field-help',fieldId}));
  }
  const button=raw.closest('form.cms-form > button.button');
  if(button){const el=ensureDirectTextTarget(button,{part:'submit-label'});return el?metaFromDecorated(el):null}
  return null;
}
function clearSelection(){
  if(selected?.el)selected.el.classList.remove('cms-form-inline-selected');
  selected=null;
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
  if(selected?.el&&selected.el!==meta.el)selected.el.classList.remove('cms-form-inline-selected');
  selected=meta;meta.el.classList.add('cms-form-inline-selected');
  editing={...meta,originalText:meta.el.textContent};
  meta.el.setAttribute('contenteditable','true');meta.el.classList.add('cms-form-inline-editing');
  renderContext();
  ensureForm(meta.formId).then(()=>{if(selected?.el===meta.el)renderContext()}).catch(error=>{entryFor(meta.formId).error=error.message;if(selected?.el===meta.el)renderContext()});
  placeCaret(meta.el,event);
}
function injectFrameStyle(d){
  if(d.getElementById('cms-form-inline-editor-style'))return;
  const style=d.createElement('style');style.id='cms-form-inline-editor-style';
  style.textContent='.cms-form .cms-field>span,.cms-form .cms-choice-group>legend,.cms-form .cms-choice-grid>label>span,.cms-form .cms-field>small,.cms-form .cms-choice-group>small,.cms-form .cms-consent>span,.cms-form>button.button{cursor:text}.cms-form-inline-selected{outline:1px solid currentColor!important;outline-offset:3px}.cms-form-inline-editing{outline:2px solid currentColor!important;outline-offset:3px;cursor:text!important}';
  d.head.append(style);
}
function installFrameCapture(){
  const d=frameDoc();if(!d||d.__cmsFormVisualEdit)return;
  d.__cmsFormVisualEdit=true;injectFrameStyle(d);
  d.addEventListener('pointerdown',event=>{
    const meta=resolveEditableTarget(event.target);
    if(meta){event.preventDefault();event.stopImmediatePropagation();startInline(meta,event);return}
    if(editing)finishInline(false);
    const block=formBlockFrom(event.target);
    if(block){clearSelection();event.preventDefault();event.stopImmediatePropagation();return}
    clearSelection();
  },true);
  d.addEventListener('click',event=>{
    if(formBlockFrom(event.target)){event.preventDefault();event.stopImmediatePropagation()}
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
frame.addEventListener('load',()=>setTimeout(installFrameCapture,0));
})();
