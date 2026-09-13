(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const inspector=document.querySelector('#inspector');
if(!frame||!inspector)return;

const esc=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
let active=null;
let pendingFieldId='';
let renderToken=0;
let inspectorObserver=null;

function frameDoc(){return frame.contentDocument||null}
function selectedBlock(){return frameDoc()?.querySelector('[data-cms-form-block].cms-selection')||null}
function normalizeFieldName(name){return String(name||'').replace(/\[\]$/,'')}
function fieldIdFromTarget(target){
  if(!target?.closest)return '';
  let control=target.closest('input[name],select[name],textarea[name]');
  if(!control){const label=target.closest('label');control=label?.querySelector('input[name],select[name],textarea[name]')||null}
  return normalizeFieldName(control?.getAttribute('name')||'');
}
function statusText(form){return form.publishedRevision==null||Number(form.publishedRevision)!==Number(form.draftRevision)?'Rascunho do formulário ainda não publicado.':'Formulário publicado e atualizado.'}
async function json(url,options={}){
  const response=await fetch(url,{credentials:'same-origin',...options});
  const body=await response.json().catch(()=>({}));
  if(!response.ok){const error=new Error(body?.error?.message||body?.error?.code||`HTTP ${response.status}`);error.code=body?.error?.code;throw error}
  return body;
}
async function post(url,payload){return json(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})}
function optionRows(field){
  if(!['select','radio','checkbox-group'].includes(field.type))return '<p class="form-quick-note">Este campo não possui opções editáveis.</p>';
  const options=Array.isArray(field.options)?field.options:[];
  if(!options.length)return '<p class="form-quick-note">Este campo ainda não possui opções. Para criar ou reordenar opções, use o editor completo.</p>';
  return `<div class="form-quick-options"><p class="eyebrow">Opções</p>${options.map((option,index)=>`<div class="form-quick-option" data-option="${index}"><label>Rótulo da opção<input data-option-label="${index}" value="${esc(option.label)}"></label><label>Valor interno<input data-option-value="${index}" value="${esc(option.value)}"></label></div>`).join('')}<p class="form-quick-warning">Alterar o valor interno pode afetar regras condicionais que dependam dessa opção.</p></div>`;
}
function renderEditor(){
  if(!active)return;
  const fields=Array.isArray(active.form.schema?.fields)?active.form.schema.fields:[];
  if(!fields.length){inspector.innerHTML='<div class="inspector-section" data-cms-form-quick><header><p class="eyebrow">Formulário</p><h2>Sem campos</h2></header><p class="inspector-note">Use o editor completo para criar a estrutura do formulário.</p></div>';return}
  let index=Math.max(0,Math.min(active.fieldIndex,fields.length-1));
  active.fieldIndex=index;
  const field=fields[index];
  inspector.innerHTML=`<div class="inspector-section form-quick-editor" data-cms-form-quick>
    <header><p class="eyebrow">Formulário · edição rápida</p><h2>${esc(active.form.title)}</h2><p class="form-quick-status">${esc(statusText(active.form))}</p></header>
    <label>Campo<select id="fq-field">${fields.map((item,i)=>`<option value="${i}" ${i===index?'selected':''}>${esc(item.label)} · ${esc(item.type)}</option>`).join('')}</select></label>
    <label>Rótulo<input id="fq-label" value="${esc(field.label)}"></label>
    ${optionRows(field)}
    <label>Texto do botão<input id="fq-submit" value="${esc(active.form.schema.submitLabel||'Enviar')}"></label>
    <div class="button-row form-quick-actions"><button type="button" id="fq-save" class="primary">Salvar formulário</button><button type="button" id="fq-publish">Salvar e publicar</button></div>
    <p class="form-quick-note">Aqui ficam apenas os ajustes editoriais rápidos. Tipo, identificador, ordem, campos novos, obrigatoriedade, lógica condicional e fluxo após envio continuam no editor completo.</p>
    <a class="panel-button" href="/admin/forms.php?activity=${Number(active.form.activityId||0)}&form=${Number(active.form.id)}" target="_blank" rel="noopener">Abrir editor completo ↗</a>
  </div>`;
  inspector.querySelector('#fq-field')?.addEventListener('change',event=>{active.fieldIndex=Number(event.currentTarget.value)||0;pendingFieldId=fields[active.fieldIndex]?.id||'';renderEditor()});
  inspector.querySelector('#fq-label')?.addEventListener('input',event=>{field.label=event.currentTarget.value;active.dirty=true;markQuickDirty()});
  inspector.querySelector('#fq-submit')?.addEventListener('input',event=>{active.form.schema.submitLabel=event.currentTarget.value;active.dirty=true;markQuickDirty()});
  inspector.querySelectorAll('[data-option-label]').forEach(input=>input.addEventListener('input',event=>{const option=field.options?.[Number(event.currentTarget.dataset.optionLabel)];if(option){option.label=event.currentTarget.value;active.dirty=true;markQuickDirty()}}));
  inspector.querySelectorAll('[data-option-value]').forEach(input=>input.addEventListener('input',event=>{const option=field.options?.[Number(event.currentTarget.dataset.optionValue)];if(option){option.value=event.currentTarget.value;active.dirty=true;markQuickDirty()}}));
  inspector.querySelector('#fq-save')?.addEventListener('click',()=>saveForm(false));
  inspector.querySelector('#fq-publish')?.addEventListener('click',()=>saveForm(true));
}
function markQuickDirty(){const status=inspector.querySelector('.form-quick-status');if(status)status.textContent='Alterações do formulário ainda não salvas.'}
async function loadForm(formId){
  const token=++renderToken;
  inspector.innerHTML='<div class="inspector-section" data-cms-form-quick><header><p class="eyebrow">Formulário</p><h2>Carregando…</h2></header></div>';
  try{
    const data=await json(`/admin/api/cms-form-load.php?form=${formId}`);
    if(token!==renderToken||Number(selectedBlock()?.dataset.cmsFormBlock||0)!==formId)return;
    const fields=Array.isArray(data.form?.schema?.fields)?data.form.schema.fields:[];
    let index=pendingFieldId?fields.findIndex(field=>field.id===pendingFieldId):-1;
    if(index<0)index=0;
    active={csrf:data.csrf,form:data.form,fieldIndex:index,dirty:false};
    renderEditor();
  }catch(error){inspector.innerHTML=`<div class="inspector-section" data-cms-form-quick><header><p class="eyebrow">Formulário</p><h2>Não foi possível carregar</h2></header><p class="inspector-note">${esc(error.message)}</p></div>`}
}
async function saveForm(publish){
  if(!active)return;
  const saveButton=inspector.querySelector('#fq-save');
  const publishButton=inspector.querySelector('#fq-publish');
  if(saveButton)saveButton.disabled=true;if(publishButton)publishButton.disabled=true;
  try{
    const saved=await post('/admin/api/cms-form-save.php',{csrf:active.csrf,formId:active.form.id,revision:active.form.draftRevision,title:active.form.title,schema:active.form.schema});
    active.form=saved.form;active.dirty=false;
    if(publish){const result=await post('/admin/api/cms-form-publish.php',{csrf:active.csrf,formId:active.form.id});active.form.publishedRevision=result.publishedRevision}
    const field=active.form.schema.fields?.[active.fieldIndex];
    pendingFieldId=field?.id||'';
    refreshPreview(active.form.id);
  }catch(error){
    if(error.code==='revision_conflict'){alert('O formulário foi alterado em outra sessão. Reabra o campo para carregar a versão mais recente.');active=null;maybeEnhance(true);return}
    alert(error.message);
    renderEditor();
  }
}
function refreshPreview(formId){
  active=null;
  const onLoad=()=>{setTimeout(()=>{
    const d=frameDoc();
    const block=d?.querySelector(`[data-cms-form-block="${formId}"]`);
    if(block)block.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true,view:frame.contentWindow}));
  },120)};
  frame.addEventListener('load',onLoad,{once:true});
  try{frame.contentWindow.location.reload()}catch{renderEditor()}
}
function maybeEnhance(force=false){
  const block=selectedBlock();
  if(!block){active=null;return}
  const formId=Number(block.dataset.cmsFormBlock||0);
  if(!formId)return;
  if(!force&&inspector.querySelector('[data-cms-form-quick]'))return;
  if(active&&Number(active.form?.id)===formId&&!force){renderEditor();return}
  loadForm(formId);
}
function installFrameCapture(){
  const d=frameDoc();
  if(!d||d.__cmsFormQuickCapture)return;
  d.__cmsFormQuickCapture=true;
  d.addEventListener('pointerdown',event=>{
    const block=event.target?.closest?.('[data-cms-form-block]');
    if(!block)return;
    pendingFieldId=fieldIdFromTarget(event.target)||pendingFieldId;
  },true);
}
frame.addEventListener('load',()=>setTimeout(installFrameCapture,0));
inspectorObserver=new MutationObserver(()=>setTimeout(maybeEnhance,0));
inspectorObserver.observe(inspector,{childList:true,subtree:true});
})();
