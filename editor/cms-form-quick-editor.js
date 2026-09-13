(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const inspector=document.querySelector('#inspector');
const toast=document.querySelector('#toast');
const pageId=Number(new URLSearchParams(location.search).get('page')||0);
if(!frame||!inspector||!pageId)return;

let context=null;
let quickForm=null;
let enhancing=false;
let loadToken=0;

const esc=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
async function request(url,options={}){const response=await fetch(url,{credentials:'same-origin',...options});const body=await response.json().catch(()=>({}));if(!response.ok)throw new Error(body?.error?.message||body?.error?.code||`HTTP ${response.status}`);return body}
function flash(message){if(!toast)return;toast.textContent=message;toast.classList.add('show');clearTimeout(flash.t);flash.t=setTimeout(()=>toast.classList.remove('show'),2200)}
function selectedFormBlock(){const d=frame.contentDocument;return d?.querySelector('.cms-selection[data-cms-form-block],.cms-selection[data-cms-form-key]')||null}
async function ensureContext(){if(context)return context;context=await request(`/admin/api/cms-page-load.php?page=${pageId}`);return context}
function currentFormMeta(){
  const block=selectedFormBlock();
  const select=inspector.querySelector('#f-key');
  const key=select?.value||block?.dataset?.cmsFormKey||'';
  return context?.forms?.find(form=>form.key===key)||null;
}
function optionRows(field,fieldIndex){
  if(!['select','radio','checkbox-group'].includes(field.type))return '';
  const options=Array.isArray(field.options)?field.options:[];
  return `<div class="quick-form-options"><p class="quick-form-label">Opções</p>${options.map((option,index)=>`<div class="quick-form-option"><label>Rótulo<input data-q-option-label="${fieldIndex}:${index}" value="${esc(option.label)}"></label><label>Valor<input data-q-option-value="${fieldIndex}:${index}" value="${esc(option.value)}"></label></div>`).join('')||'<p class="inspector-note">Este campo ainda não possui opções.</p>'}</div>`;
}
function fieldEditor(field,index){
  const typeLabel={text:'Texto curto',email:'E-mail',tel:'Telefone',number:'Número',textarea:'Texto longo',select:'Lista',radio:'Escolha única',checkbox:'Checkbox','checkbox-group':'Múltipla escolha',date:'Data',time:'Hora',consent:'Aceite'}[field.type]||field.type;
  return `<details class="quick-form-field" ${index===0?'open':''}><summary><span>${esc(field.label||field.id)}</span><small>${esc(typeLabel)}</small></summary><div class="quick-form-field-body"><label>Rótulo<input data-q-label="${index}" value="${esc(field.label||'')}"></label><label class="check-row"><input type="checkbox" data-q-required="${index}" ${field.required?'checked':''}> Campo obrigatório</label>${['text','email','tel','number','textarea'].includes(field.type)?`<label>Placeholder<input data-q-placeholder="${index}" value="${esc(field.placeholder||'')}"></label>`:''}${optionRows(field,index)}</div></details>`;
}
function renderQuick(){
  const host=inspector.querySelector('#cms-form-quick-editor');
  if(!host||!quickForm)return;
  const schema=quickForm.schema||{};
  host.innerHTML=`<div class="quick-form-head"><div><p class="eyebrow">Edição rápida</p><h3>Conteúdo do formulário</h3></div><span id="quick-form-status">${quickForm.publishedRevision===quickForm.draftRevision?'Publicado':'Rascunho não publicado'}</span></div><p class="inspector-note">Edite aqui o que aparece na página. Tipo de campo, ordem, condições e fluxo continuam no editor completo.</p><label>Texto do botão<input id="q-submit" value="${esc(schema.submitLabel||'')}"></label><div class="quick-form-fields">${(schema.fields||[]).map(fieldEditor).join('')}</div><div class="quick-form-actions"><button type="button" id="q-save" class="panel-button">Salvar rascunho</button><button type="button" id="q-publish" class="panel-button primary">Salvar e publicar</button></div>`;
  host.querySelector('#q-submit').oninput=event=>{quickForm.schema.submitLabel=event.target.value;markQuickDirty()};
  host.querySelectorAll('[data-q-label]').forEach(input=>input.oninput=()=>{quickForm.schema.fields[Number(input.dataset.qLabel)].label=input.value;markQuickDirty()});
  host.querySelectorAll('[data-q-required]').forEach(input=>input.onchange=()=>{quickForm.schema.fields[Number(input.dataset.qRequired)].required=input.checked;markQuickDirty()});
  host.querySelectorAll('[data-q-placeholder]').forEach(input=>input.oninput=()=>{quickForm.schema.fields[Number(input.dataset.qPlaceholder)].placeholder=input.value;markQuickDirty()});
  host.querySelectorAll('[data-q-option-label]').forEach(input=>input.oninput=()=>{const [f,o]=input.dataset.qOptionLabel.split(':').map(Number);quickForm.schema.fields[f].options[o].label=input.value;markQuickDirty()});
  host.querySelectorAll('[data-q-option-value]').forEach(input=>input.oninput=()=>{const [f,o]=input.dataset.qOptionValue.split(':').map(Number);quickForm.schema.fields[f].options[o].value=input.value;markQuickDirty()});
  host.querySelector('#q-save').onclick=()=>saveQuick(false);
  host.querySelector('#q-publish').onclick=()=>saveQuick(true);
}
function markQuickDirty(){const status=inspector.querySelector('#quick-form-status');if(status)status.textContent='Alterações não salvas'}
async function saveQuick(publish){
  if(!quickForm||!context)return;
  const saveButton=inspector.querySelector('#q-save'),publishButton=inspector.querySelector('#q-publish');
  if(saveButton)saveButton.disabled=true;if(publishButton)publishButton.disabled=true;
  try{
    const saved=await request('/admin/api/cms-form-save.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:context.csrf,formId:quickForm.id,revision:quickForm.draftRevision,title:quickForm.title,schema:quickForm.schema})});
    quickForm={...quickForm,...saved.form};
    if(publish){
      const result=await request('/admin/api/cms-form-publish.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:context.csrf,formId:quickForm.id})});
      quickForm.draftRevision=result.draftRevision;quickForm.publishedRevision=result.publishedRevision;
      flash('Formulário salvo e publicado.');
    }else flash('Rascunho do formulário salvo.');
    renderQuick();
  }catch(error){alert(error.message||'Não foi possível salvar o formulário.')}finally{if(saveButton)saveButton.disabled=false;if(publishButton)publishButton.disabled=false}
}
async function loadQuickForm(){
  const token=++loadToken;
  try{
    await ensureContext();
    const meta=currentFormMeta();
    const host=inspector.querySelector('#cms-form-quick-editor');
    if(!host)return;
    if(!meta){host.innerHTML='<p class="inspector-note">Não foi possível identificar o formulário desta página.</p>';return}
    host.innerHTML='<p class="inspector-note">Carregando campos…</p>';
    const data=await request(`/admin/api/cms-form-load.php?form=${meta.id}`);
    if(token!==loadToken)return;
    context.csrf=data.csrf||context.csrf;quickForm=data.form;renderQuick();
  }catch(error){const host=inspector.querySelector('#cms-form-quick-editor');if(host)host.innerHTML=`<p class="inspector-note">${esc(error.message||'Não foi possível carregar o formulário.')}</p>`}
}
async function enhance(){
  if(enhancing)return;
  const formSelect=inspector.querySelector('#f-key');
  const section=formSelect?.closest('.inspector-section');
  if(!formSelect||!section||section.dataset.quickFormEnhanced==='1')return;
  enhancing=true;
  section.dataset.quickFormEnhanced='1';
  const note=section.querySelector('.inspector-note');
  if(note)note.textContent='Rótulos, opções e campos obrigatórios podem ser ajustados aqui. Use o editor completo apenas para alterações estruturais, condições e fluxo.';
  const fullLink=section.querySelector('a[href*="/admin/forms.php"]');
  if(fullLink){fullLink.textContent='Abrir editor completo ↗';fullLink.classList.remove('primary')}
  const quick=document.createElement('div');quick.id='cms-form-quick-editor';quick.className='cms-form-quick-editor';quick.innerHTML='<p class="inspector-note">Carregando campos…</p>';section.append(quick);
  formSelect.addEventListener('change',()=>{quickForm=null;setTimeout(loadQuickForm,0)});
  enhancing=false;
  loadQuickForm();
}

const observer=new MutationObserver(()=>setTimeout(enhance,0));
observer.observe(inspector,{childList:true,subtree:true});
frame.addEventListener('load',()=>{context=null;quickForm=null;setTimeout(enhance,0)});
setTimeout(enhance,0);
})();
