(()=>{
'use strict';
const toolbox=document.querySelector('[data-student-toolbox]');
document.querySelectorAll('[data-toolbox-open]').forEach(button=>button.addEventListener('click',event=>{if(!toolbox)return;if(button.tagName==='A')event.preventDefault();if(typeof toolbox.showModal==='function')toolbox.showModal();else toolbox.setAttribute('open','');}));
document.querySelectorAll('[data-toolbox-close]').forEach(button=>button.addEventListener('click',()=>{if(!toolbox)return;if(typeof toolbox.close==='function')toolbox.close();else toolbox.removeAttribute('open');}));
const createDialog=document.querySelector('[data-record-create-dialog]');
const openCreate=()=>{if(!createDialog)return;if(typeof createDialog.showModal==='function'){if(!createDialog.open)createDialog.showModal();}else createDialog.setAttribute('open','');};
const closeCreate=()=>{if(!createDialog)return;if(typeof createDialog.close==='function')createDialog.close();else createDialog.removeAttribute('open');};
document.querySelectorAll('[data-record-create-open]').forEach(button=>button.addEventListener('click',openCreate));
document.querySelectorAll('[data-record-create-close]').forEach(button=>button.addEventListener('click',closeCreate));
if(createDialog&&new URLSearchParams(window.location.search).get('novo')==='1')openCreate();
document.querySelectorAll('[data-quick-reciprocity]').forEach(root=>{const source=root.querySelector('[data-quick-reciprocity-source]'),target=root.querySelector('[data-quick-reciprocity-target]');if(!source||!target)return;const update=()=>{const raw=source.value.trim();target.value=raw&&window.StudentReciprocity?(window.StudentReciprocity.calculate(raw)||''):'';};source.addEventListener('input',update);source.addEventListener('change',update);update();});
document.querySelectorAll('.student-material-note>summary').forEach(summary=>{const text=summary.textContent.trim();if(text==='Anotar')summary.textContent='Adicionar anotação';if(text==='Sua anotação')summary.textContent='Anotação';});
})();