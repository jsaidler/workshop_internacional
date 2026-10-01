(()=>{
'use strict';
const panel=document.querySelector('[data-inventory-new-panel]');if(!panel)return;
panel.querySelectorAll('.student-optional-fields').forEach(details=>{if(!details.querySelector('input,select,textarea'))details.remove();});
const heading=panel.querySelector('.student-section-heading');if(heading&&!heading.querySelector('[data-inventory-top-close]')){const close=document.createElement('button');close.type='button';close.className='button button-secondary button-compact';close.dataset.inventoryTopClose='';close.textContent='Fechar';close.addEventListener('click',()=>{panel.hidden=true;});heading.appendChild(close);}
const syncBody=()=>{const open=!panel.hidden;document.documentElement.classList.toggle('student-inventory-editor-open',open);document.documentElement.style.overflow=open?'hidden':'';};
new MutationObserver(syncBody).observe(panel,{attributes:true,attributeFilter:['hidden']});syncBody();
document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!panel.hidden){panel.hidden=true;syncBody();}});
})();
