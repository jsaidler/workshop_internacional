(()=>{
'use strict';
const q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];
const path=location.pathname;
const actionOf=form=>String(new FormData(form).get('action')||form.querySelector('[name="annotation_action"]')?.value||'');
const localSpec=form=>{
  const action=actionOf(form);
  if(form.dataset.studentLocalForm!==undefined)return {refresh:(form.dataset.localRefresh||'').split(/\s+/).filter(Boolean),mode:form.dataset.localMode||'',success:form.dataset.localSuccess||'',confirm:form.dataset.localConfirm||''};
  if(path.endsWith('/aluno/caderno.php')&&action==='visibility')return {refresh:[],mode:'visibility',success:'',confirm:''};
  if(path.endsWith('/aluno/teste.php')){
    if(action==='save_exposure')return null;
    if(['add_step','delete_step','save_result','upload','delete_media','submit','message'].includes(action))return {refresh:['record-workflow'],mode:'',success:'',confirm:action==='delete_step'?'Desfazer esta etapa e as posteriores?':''};
  }
  if(path.endsWith('/aluno/duvidas.php')&&['reply','resolve'].includes(action))return {refresh:['question-thread'],mode:'',success:action==='reply'?'reset':'',confirm:''};
  return null;
};
const announce=(form,message,error=false)=>{let box=form.querySelector(':scope > [data-local-status]');if(!box){box=document.createElement('p');box.dataset.localStatus='';box.className='ui-alert';form.prepend(box);}box.classList.toggle('ui-alert-error',error);box.classList.toggle('ui-alert-notice',!error);box.textContent=message;box.hidden=false;};
const preserveOpen=element=>qa('details[open][data-local-open]',element).map(item=>item.dataset.localOpen);
const restoreOpen=(element,keys)=>keys.forEach(key=>{const item=q(`details[data-local-open="${CSS.escape(key)}"]`,element);if(item)item.open=true;});
const refreshKey=(doc,key)=>{
  const current=q(`[data-student-local-key="${CSS.escape(key)}"]`),next=q(`[data-student-local-key="${CSS.escape(key)}"]`,doc);if(!current||!next)return null;
  const open=preserveOpen(current),top=current.getBoundingClientRect().top,scrollY=window.scrollY;
  const replacement=document.importNode(next,true);current.replaceWith(replacement);restoreOpen(replacement,open);
  requestAnimationFrame(()=>{const delta=replacement.getBoundingClientRect().top-top;if(Number.isFinite(delta)&&Math.abs(delta)>0.5)window.scrollTo(0,scrollY+delta);});
  return replacement;
};
const refreshWorkflow=(doc)=>{
  const current=q('.student-workflow-panel'),next=q('.student-workflow-panel',doc);if(!current||!next)return null;const top=current.getBoundingClientRect().top,scrollY=window.scrollY;const replacement=document.importNode(next,true);current.replaceWith(replacement);requestAnimationFrame(()=>{const delta=replacement.getBoundingClientRect().top-top;if(Number.isFinite(delta)&&Math.abs(delta)>0.5)window.scrollTo(0,scrollY+delta);});return replacement;
};
const visibilitySuccess=(form,doc)=>{
  const id=form.querySelector('input[name="id"]')?.value;if(!id)return;
  const currentCard=form.closest('.student-process-card');const nextForm=q(`form input[name="action"][value="visibility"]~input[name="id"][value="${CSS.escape(id)}"]`,doc)?.closest('form');const nextCard=nextForm?.closest('.student-process-card');
  const currentStatus=currentCard?.querySelector('.student-status'),nextStatus=nextCard?.querySelector('.student-status');if(currentStatus&&nextStatus)currentStatus.textContent=nextStatus.textContent;
};
const submitLocal=async(form,spec)=>{
  const submitter=document.activeElement?.form===form?document.activeElement:null;const confirmText=submitter?.dataset.localConfirm||form.dataset.localConfirm||spec.confirm;
  if(confirmText&&!window.confirm(confirmText))return;
  const buttons=qa('button[type="submit"],input[type="submit"]',form),scrollY=window.scrollY;buttons.forEach(button=>button.disabled=true);
  try{
    const data=new FormData(form);if(submitter?.name)data.set(submitter.name,submitter.value);
    const response=await fetch(form.action||location.href,{method:(form.method||'post').toUpperCase(),body:data,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html,application/xhtml+xml'}});
    const text=await response.text();if(!response.ok)throw new Error(text.replace(/<[^>]+>/g,' ').trim()||'Não foi possível salvar.');
    const doc=new DOMParser().parseFromString(text,'text/html');let updated=[];
    if(spec.mode==='visibility')visibilitySuccess(form,doc);
    for(const key of spec.refresh){const root=key==='record-workflow'?refreshWorkflow(doc):refreshKey(doc,key);if(root)updated.push(root);}
    if(spec.success.includes('reset'))form.reset();
    if(spec.success.includes('close-dialog'))form.closest('dialog')?.close?.();
    if(!spec.refresh.length&&spec.mode!=='visibility')announce(form,'Salvo.');
    updated.forEach(root=>document.dispatchEvent(new CustomEvent('student:local-update',{detail:{root}})));
    window.scrollTo(0,scrollY);
  }catch(error){announce(form,error instanceof Error?error.message:'Não foi possível salvar.',true);}
  finally{buttons.forEach(button=>button.disabled=false);}
};

document.addEventListener('submit',event=>{const form=event.target.closest('form');if(!form)return;const spec=localSpec(form);if(!spec)return;event.preventDefault();submitLocal(form,spec);});

document.addEventListener('click',async event=>{
  const link=event.target.closest('[data-student-editor-link]');if(link){event.preventDefault();const dialog=q('[data-student-editor-dialog]');if(!dialog)return;try{const response=await fetch(link.href,{credentials:'same-origin'});if(!response.ok)throw new Error('Não foi possível abrir a edição.');const doc=new DOMParser().parseFromString(await response.text(),'text/html'),source=q('[data-student-editor]',doc),target=q('[data-student-editor]',dialog);if(!source||!target)throw new Error('Editor indisponível.');target.replaceWith(document.importNode(source,true));if(typeof dialog.showModal==='function'){if(!dialog.open)dialog.showModal();}else dialog.setAttribute('open','');document.dispatchEvent(new CustomEvent('student:local-update',{detail:{root:dialog}}));}catch(error){window.alert(error.message||'Não foi possível abrir a edição.');}return;}
  const open=event.target.closest('[data-student-editor-open]');if(open){event.preventDefault();const dialog=q('[data-student-editor-dialog]');if(!dialog)return;if(typeof dialog.showModal==='function'){if(!dialog.open)dialog.showModal();}else dialog.setAttribute('open','');return;}
  const close=event.target.closest('[data-student-editor-close]');if(close){event.preventDefault();close.closest('dialog')?.close?.();}
});

window.addEventListener('beforeunload',event=>{if(!q('[data-lab-timer][data-timer-active="1"]'))return;event.preventDefault();event.returnValue='';});
})();