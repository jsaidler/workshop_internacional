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

/* Comparison is a temporary mode in the notebook, not permanent checkbox clutter. */
const compareHeading=document.querySelector('.student-page-heading'),compareList=document.querySelector('.student-process-list'),compareCards=[...document.querySelectorAll('.student-process-card')];
if(compareHeading&&compareList&&compareCards.length>=2){
  const newRecord=compareHeading.querySelector('[data-record-create-open]');
  const compare=document.createElement('button');compare.type='button';compare.className='button button-secondary';compare.textContent='Comparar registros';compare.dataset.compareModeOpen='';
  newRecord?.insertAdjacentElement('beforebegin',compare);
  const bar=document.createElement('div');bar.className='student-compare-selection';bar.hidden=true;bar.innerHTML='<span data-compare-count>Selecione 2 registros</span><div><button class="button button-secondary button-compact" type="button" data-compare-cancel>Cancelar</button><button class="button button-primary button-compact" type="button" data-compare-go disabled>Comparar</button></div>';
  compareList.insertAdjacentElement('beforebegin',bar);
  const picks=[];
  compareCards.forEach(card=>{const main=card.querySelector('.student-process-card-main');if(!main)return;const match=main.getAttribute('href')?.match(/[?&]id=(\d+)/);if(!match)return;const footer=card.querySelector('.student-process-card-footer')||card;const label=document.createElement('label');label.className='student-compare-pick';label.hidden=true;label.innerHTML=`<input type="checkbox" value="${match[1]}"><span>Selecionar</span>`;footer.insertAdjacentElement('afterbegin',label);picks.push(label.querySelector('input'));main.addEventListener('click',event=>{if(!document.documentElement.classList.contains('student-compare-mode'))return;event.preventDefault();const input=label.querySelector('input');input.checked=!input.checked;input.dispatchEvent(new Event('change',{bubbles:true}));});});
  const count=bar.querySelector('[data-compare-count]'),go=bar.querySelector('[data-compare-go]');
  const sync=()=>{const selected=picks.filter(input=>input.checked);count.textContent=selected.length===0?'Selecione 2 registros':selected.length===1?'1 de 2 selecionado':selected.length===2?'2 registros selecionados':`${selected.length} selecionados · escolha apenas 2`;go.disabled=selected.length!==2;};
  const close=()=>{document.documentElement.classList.remove('student-compare-mode');bar.hidden=true;compare.hidden=false;newRecord&&(newRecord.hidden=false);picks.forEach(input=>{input.checked=false;input.closest('.student-compare-pick').hidden=true;});sync();};
  compare.addEventListener('click',()=>{document.documentElement.classList.add('student-compare-mode');bar.hidden=false;compare.hidden=true;newRecord&&(newRecord.hidden=true);picks.forEach(input=>input.closest('.student-compare-pick').hidden=false);sync();});
  bar.querySelector('[data-compare-cancel]').addEventListener('click',close);picks.forEach(input=>input.addEventListener('change',sync));
  go.addEventListener('click',()=>{const selected=picks.filter(input=>input.checked);if(selected.length!==2)return;const query=new URLSearchParams();selected.forEach(input=>query.append('id[]',input.value));location.href='/aluno/comparar-processos.php?'+query.toString();});
}

if(!document.querySelector('script[data-student-mechanics]')){const script=document.createElement('script');script.src='/assets/student-mechanics.js'+suffix;script.dataset.studentMechanics='';script.addEventListener('load',()=>{if(document.querySelector('script[data-student-mechanics-after]'))return;const after=document.createElement('script');after.src='/assets/student-mechanics-after.js'+suffix;after.dataset.studentMechanicsAfter='';document.body.appendChild(after);});document.body.appendChild(script);}
ensureScript('/assets/student-process-ux.js','data-student-process-ux');
})();