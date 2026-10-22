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
const review=root.querySelector('.comms-send-review');
const unsavedWarning=root.querySelector('[data-unsaved-warning]');
function markChanged(){
  if(root.dataset.savedDraft!=='1')return;
  if(review)review.hidden=true;
  if(unsavedWarning)unsavedWarning.hidden=false;
}
if(editor && htmlField && textField){
  if(plainFallback)plainFallback.hidden=true;
  editor.hidden=false;
  const sync=()=>{
    htmlField.value=editor.innerHTML;
    textField.value=editor.innerText.trim();
  };
  editor.addEventListener('input',()=>{sync();markChanged();});
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
      sync();markChanged();
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
list?.addEventListener('change',()=>{updateCount();markChanged();});
root.querySelector('[data-recipient-all]')?.addEventListener('click',()=>{
  list.querySelectorAll('input[name="recipient_ids[]"]').forEach(x=>x.checked=true);updateCount();markChanged();
});
root.querySelector('[data-recipient-none]')?.addEventListener('click',()=>{
  list.querySelectorAll('input[name="recipient_ids[]"]').forEach(x=>x.checked=false);updateCount();markChanged();
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
[category,course,cohort].forEach(input=>input?.addEventListener('change',()=>{markChanged();
  if(input===course){
    const selected=cohort.selectedOptions[0];
    if(selected && selected.dataset.course && selected.dataset.course!==course.value)cohort.value='0';
  }
  refreshRecipients();
}));
form.querySelector('[name="subject"]')?.addEventListener('input',markChanged);
form.querySelectorAll('[name="template_key"]').forEach(radio=>radio.addEventListener('change',markChanged));
root.querySelectorAll('[data-email-preview-mode]').forEach(button=>{
  button.addEventListener('click',()=>{
    const container=root.querySelector('.comms-email-frame');
    if(!container)return;
    container.classList.toggle('is-mobile',button.dataset.emailPreviewMode==='mobile');
    root.querySelectorAll('[data-email-preview-mode]').forEach(x=>x.setAttribute('aria-pressed',String(x===button)));
  });
});

const mediaDialog=root.querySelector('[data-email-media-dialog]');
const mediaUrl=root.dataset.emailMediaUrl;
const mediaGallery=root.querySelector('[data-email-media-gallery]');
const mediaSearch=root.querySelector('[data-email-media-search]');
const mediaStatus=root.querySelector('[data-email-media-status]');
const mediaPanel=root.querySelector('[data-email-media-insert-panel]');
const mediaAlt=root.querySelector('[data-email-media-alt]');
const mediaSelectedImage=root.querySelector('[data-email-media-selected-image]');
let mediaItems=[],mediaSelected=null,savedRange=null;
const htmlEscape=text=>String(text).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function decorateFigure(fig,item){
  if(!fig.querySelector('img')){
    const img=document.createElement('img');img.src=item.src;img.alt=fig.dataset.emailAlt||item.alt||'';
    img.loading='lazy';img.style.maxWidth='100%';img.style.height='auto';fig.append(img);
  }
  fig.contentEditable='false';
}
function renderMediaList(){
  if(!mediaGallery)return;
  const query=(mediaSearch?.value||'').trim().toLocaleLowerCase('pt-BR');
  mediaGallery.replaceChildren();
  for(const item of mediaItems.filter(i=>!query||i.title.toLocaleLowerCase('pt-BR').includes(query))){
    const button=document.createElement('button');
    button.type='button';button.className='comms-media-tile';button.dataset.assetId=String(item.id);
    const img=document.createElement('img');img.src=item.src;img.alt=item.alt||item.title;img.loading='lazy';
    const label=document.createElement('span');label.textContent=item.title;
    button.append(img,label);button.addEventListener('click',()=>selectMedia(item));mediaGallery.append(button);
  }
  if(mediaStatus)mediaStatus.textContent=mediaItems.length+' imagens públicas disponíveis';
}
function selectMedia(item){
  mediaSelected=item;if(mediaPanel)mediaPanel.hidden=false;
  if(mediaSelectedImage){mediaSelectedImage.src=item.src;mediaSelectedImage.alt=item.alt||item.title;}
  if(mediaAlt){mediaAlt.value=item.alt||'';mediaAlt.focus();}
}
async function loadMedia(){
  if(!mediaUrl)return;
  if(mediaStatus)mediaStatus.textContent='Carregando imagens…';
  try{
    const response=await fetch(mediaUrl,{credentials:'same-origin',cache:'no-store'});
    const payload=await response.json();if(!response.ok)throw Error(payload.error||'Não foi possível carregar a Biblioteca.');
    mediaItems=payload.items||[];renderMediaList();
    if(editor)for(const fig of editor.querySelectorAll('figure[data-email-media-id]')){
      const item=mediaItems.find(x=>String(x.id)===fig.dataset.emailMediaId);
      if(item)decorateFigure(fig,item);
      else fig.textContent='Imagem indisponível na seleção atual';
    }
  }catch(error){if(mediaStatus)mediaStatus.textContent=String(error.message||'Falha ao carregar imagens.');}
}
root.querySelector('[data-email-media-open]')?.addEventListener('click',()=>{
  const selection=window.getSelection();
  if(selection?.rangeCount && editor?.contains(selection.anchorNode))savedRange=selection.getRangeAt(0).cloneRange();
  mediaDialog?.showModal();loadMedia();
});
root.querySelector('[data-email-media-close]')?.addEventListener('click',()=>mediaDialog?.close());
mediaSearch?.addEventListener('input',renderMediaList);
root.querySelector('[data-email-media-upload]')?.addEventListener('change',async e=>{
  const file=e.target.files?.[0];if(!file||!mediaUrl)return;
  if(file.size>8*1024*1024){if(mediaStatus)mediaStatus.textContent='Imagem maior que 8 MB.';return;}
  const data=new FormData();data.append('_csrf',root.dataset.emailCsrf||'');data.append('image',file);
  if(mediaStatus)mediaStatus.textContent='Enviando imagem…';
  try{
    const response=await fetch(mediaUrl,{method:'POST',body:data,credentials:'same-origin'});
    const payload=await response.json();if(!response.ok)throw Error(payload.error||'Falha ao enviar imagem.');
    mediaItems=[...(payload.items||[]),...mediaItems];renderMediaList();
    if(payload.items?.[0])selectMedia(payload.items[0]);
  }catch(error){if(mediaStatus)mediaStatus.textContent=String(error.message||'Falha no envio.');}
  e.target.value='';
});
if(editor?.querySelector('figure[data-email-media-id]'))loadMedia();
root.querySelector('[data-email-media-insert]')?.addEventListener('click',()=>{
  if(!editor||!mediaSelected||!mediaAlt)return;
  const alt=mediaAlt.value.trim();if(!alt){mediaAlt.focus();return;}
  const figure=document.createElement('figure');
  figure.dataset.emailMediaId=String(mediaSelected.id);figure.dataset.emailAlt=alt;
  decorateFigure(figure,mediaSelected);
  if(savedRange && editor.contains(savedRange.commonAncestorContainer)){
    let node=savedRange.endContainer;while(node!==editor && node.parentNode!==editor)node=node.parentNode;if(node!==editor && node.parentNode===editor)editor.insertBefore(figure,node.nextSibling);else editor.append(figure);
  }else editor.append(figure);
  editor.append(document.createElement('p'));
  htmlField.value=editor.innerHTML;textField.value=editor.innerText.trim();
  markChanged();mediaDialog?.close();savedRange=null;
});
updateCount();
})();
