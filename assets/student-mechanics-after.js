(()=>{
'use strict';
const panel=document.querySelector('[data-inventory-new-panel]');
if(panel){
  panel.querySelectorAll('.student-optional-fields').forEach(details=>{if(!details.querySelector('input,select,textarea'))details.remove();});
  const heading=panel.querySelector('.student-section-heading');if(heading&&!heading.querySelector('[data-inventory-top-close]')){const close=document.createElement('button');close.type='button';close.className='button button-secondary button-compact';close.dataset.inventoryTopClose='';close.textContent='Fechar';close.addEventListener('click',()=>{panel.hidden=true;});heading.appendChild(close);}
  const syncBody=()=>{const open=!panel.hidden;document.documentElement.classList.toggle('student-inventory-editor-open',open);document.documentElement.style.overflow=open?'hidden':'';};
  new MutationObserver(syncBody).observe(panel,{attributes:true,attributeFilter:['hidden']});syncBody();
  document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!panel.hidden){panel.hidden=true;syncBody();}});
}
document.querySelectorAll('.student-inventory-item').forEach(item=>{
  const actions=item.querySelector('.student-inventory-actions');if(!actions||actions.querySelector('[data-inventory-edit]'))return;
  const id=actions.querySelector('input[name="item_id"]')?.value;if(!id)return;
  const link=document.createElement('a');link.className='button button-secondary button-compact';link.dataset.inventoryEdit='';link.href=`/aluno/inventario-item.php?id=${encodeURIComponent(id)}`;link.textContent='Editar dados';
  const archive=actions.querySelector('.student-inline-form');if(archive)archive.insertAdjacentElement('beforebegin',link);else actions.appendChild(link);
});
})();
