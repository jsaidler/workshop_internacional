(()=>{
'use strict';
const self=document.currentScript;const version=self?new URL(self.src,window.location.href).searchParams.get('v'):'';
const suffix=version?'?v='+encodeURIComponent(version):'';
const ensureStyle=(href,key)=>{if(document.querySelector(`link[${key}]`))return;const link=document.createElement('link');link.rel='stylesheet';link.href=href+suffix;link.setAttribute(key,'');document.head.appendChild(link);};
const ensureScript=(href,key)=>{if(document.querySelector(`script[${key}]`))return;const script=document.createElement('script');script.src=href+suffix;script.setAttribute(key,'');document.body.appendChild(script);};
ensureStyle('/assets/student-rendered-fixes.css','data-student-rendered-fixes');
ensureStyle('/assets/student-mechanics.css','data-student-mechanics-style');
ensureStyle('/assets/student-process-ux.css','data-student-process-ux-style');
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
if(!document.querySelector('script[data-student-mechanics]')){const script=document.createElement('script');script.src='/assets/student-mechanics.js'+suffix;script.dataset.studentMechanics='';script.addEventListener('load',()=>{if(document.querySelector('script[data-student-mechanics-after]'))return;const after=document.createElement('script');after.src='/assets/student-mechanics-after.js'+suffix;after.dataset.studentMechanicsAfter='';document.body.appendChild(after);});document.body.appendChild(script);}
ensureScript('/assets/student-process-ux.js','data-student-process-ux');
})();
