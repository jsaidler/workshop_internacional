(()=>{
'use strict';
function wire(){
  const dialog=document.querySelector('#pro-components-dialog'),top=document.querySelector('#add-section'),side=document.querySelector('#add-section-side'),saveBlock=document.querySelector('#pro-save-block'),extra=document.querySelector('#pro-add-component');
  if(!dialog||!top)return false;
  const open=event=>{event.preventDefault();event.stopImmediatePropagation();dialog.showModal();dialog.querySelector('[data-pro-tab="components"]')?.click()};
  if(!top.dataset.proWired){top.dataset.proWired='1';top.textContent='+ Adicionar';top.addEventListener('click',open,true)}
  if(side&&!side.dataset.proWired){side.dataset.proWired='1';side.textContent='+ Adicionar componente';side.addEventListener('click',open,true)}
  const actions=top.closest('.editor-toolbar');if(actions&&saveBlock&&saveBlock.parentElement!==actions)actions.append(saveBlock);
  if(extra)extra.remove();
  return true;
}
if(!wire()){const observer=new MutationObserver(()=>{if(wire())observer.disconnect()});observer.observe(document.body,{childList:true,subtree:true});}
})();
