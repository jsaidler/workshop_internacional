/* Communications: progressive enhancement only. The server revalidates everything. */
(function(){
'use strict';
const root=document.querySelector('[data-communication-composer]');
if(!root)return;
const form=root.querySelector('form[data-composer-form]');
if(!form)return;
const editor=root.querySelector('[data-mail-editor]');
const htmlField=form.querySelector('[name="body_html"]');
const textField=form.querySelector('[name="body_text"]');
const plainFallback=root.querySelector('[data-plain-fallback]');
const list=root.querySelector('[data-recipient-list]');
const count=root.querySelector('[data-recipient-count]');
const search=root.querySelector('[data-recipient-search]');
const category=form.querySelector('[name="category"]');
const course=form.querySelector('[name="course_id"]');
const cohort=form.querySelector('[name="cohort_id"]');
const audienceUrl=root.getAttribute('data-audience-url');
if(editor && htmlField && textField){
  if(plainFallback)plainFallback.hidden=true;
  editor.hidden=false;
  const sync=()=>{
    htmlField.value=editor.innerHTML;
    textField.value=editor.innerText.trim();
  };
  editor.addEventListener('input',sync);
  root.querySelectorAll('[data-editor-command]').forEach(button=>{
    button.addEventListener('mousedown',event=>event.preventDefault());
    button.addEventListener('click',()=>{
      editor.focus();
      const command=button.dataset.editorCommand;
      if(command==='createLink'){
        const selection=window.getSelection();
        const url=window.prompt('Endereço HTTPS do link');
        if(url===null)return;
        if(!/^https:\/\/[^\s]+$/i.test(url.trim())){window.alert('Informe um link HTTPS válido.');return;}
        if(selection && !selection.isCollapsed)document.execCommand(command,false,url.trim());
      }else document.execCommand(command,false,null);
      sync();
    });
  });
  form.addEventListener('submit',sync);
  sync();
}
let requestId=0;
function updateCount(){
  if(!count||!list)return;
  const checkboxes=[...list.querySelectorAll('input[type=checkbox][name="recipient_ids[]"]')];
  const selected=checkboxes.filter(x=>x.checked).length;
  count.textContent=selected+' de '+checkboxes.length+' destinatários selecionados';
}
function render(people,keepIds){
  if(!list)return;
  const fragment=document.createDocumentFragment();
  people.forEach(person=>{
    const label=document.createElement('label');
    label.className='comms-recipient-row';
    const checkbox=document.createElement('input');
    checkbox.type='checkbox';checkbox.name='recipient_ids[]';checkbox.value=String(person.id);
    checkbox.checked=keepIds===null||keepIds.has(person.id);
    const content=document.createElement('span');content.className='comms-recipient-identity';
    const name=document.createElement('strong');name.textContent=person.name||person.email;
    const email=document.createElement('small');email.textContent=person.email;
    content.append(name,email);label.append(checkbox,content);fragment.append(label);
  });
  list.replaceChildren(fragment);
  updateCount();filterResults();
}
function filterResults(){
  if(!list)return;
  const q=(search?.value||'').trim().toLocaleLowerCase('pt-BR');
  list.querySelectorAll('.comms-recipient-row').forEach(row=>{
    row.hidden=!!q&&!row.textContent.toLocaleLowerCase('pt-BR').includes(q);
  });
}
if(search)search.addEventListener('input',filterResults);
list?.addEventListener('change',updateCount);
root.querySelector('[data-recipient-all]')?.addEventListener('click',()=>{
  list.querySelectorAll('input[name="recipient_ids[]"]').forEach(x=>x.checked=true);updateCount();
});
root.querySelector('[data-recipient-none]')?.addEventListener('click',()=>{
  list.querySelectorAll('input[name="recipient_ids[]"]').forEach(x=>x.checked=false);updateCount();
});
async function refreshRecipients(){
  if(!audienceUrl)return;
  const id=++requestId;
  const params=new URLSearchParams({category:category.value,course_id:course.value,cohort_id:cohort.value});
  list.setAttribute('aria-busy','true');
  const status=root.querySelector('[data-recipient-status]');
  if(status)status.textContent='Atualizando destinatários…';
  try{
    const response=await fetch(audienceUrl+'?'+params,{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});
    const result=await response.json();
    if(id!==requestId)return;
    if(!response.ok)throw Error(result.error||'Não foi possível consultar os destinatários.');
    render(result.recipients,null);
    if(status)status.textContent=result.recipients.length===0?'Nenhum destinatário elegível para este filtro.':'';
  }catch(err){
    if(id!==requestId)return;
    render([],null);
    if(status)status.textContent=String(err.message||'Falha ao carregar destinatários.');
  }finally{if(id===requestId)list.removeAttribute('aria-busy');}
}
[category,course,cohort].forEach(input=>input?.addEventListener('change',()=>{
  if(input===course){
    const selected=cohort.selectedOptions[0];
    if(selected && selected.dataset.course && selected.dataset.course!==course.value)cohort.value='0';
  }
  refreshRecipients();
}));
updateCount();
})();
