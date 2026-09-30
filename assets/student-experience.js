(()=>{
'use strict';
const toolbox=document.querySelector('[data-student-toolbox]');
document.querySelectorAll('[data-toolbox-open]').forEach(button=>button.addEventListener('click',()=>{if(!toolbox)return;if(typeof toolbox.showModal==='function')toolbox.showModal();else toolbox.setAttribute('open','');}));
document.querySelectorAll('[data-toolbox-close]').forEach(button=>button.addEventListener('click',()=>{if(!toolbox)return;if(typeof toolbox.close==='function')toolbox.close();else toolbox.removeAttribute('open');}));
document.querySelectorAll('[data-quick-reciprocity]').forEach(root=>{const source=root.querySelector('[data-quick-reciprocity-source]'),target=root.querySelector('[data-quick-reciprocity-target]');if(!source||!target)return;const update=()=>{const raw=source.value.trim();target.value=raw&&window.StudentReciprocity?(window.StudentReciprocity.calculate(raw)||''):'';};source.addEventListener('input',update);source.addEventListener('change',update);update();});
})();