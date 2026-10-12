(()=>{
'use strict';
const init=()=>{
  document.querySelectorAll('[data-inventory-move-open]').forEach(button=>{
    if(button.dataset.inventoryMoveBound==='1')return;
    button.dataset.inventoryMoveBound='1';
    const dialog=button.closest('.student-inventory-item')?.querySelector('[data-inventory-move-dialog]');
    if(!dialog)return;
    const note=dialog.querySelector('[name="note"]');
    const direction=dialog.querySelector('[name="direction"]');
    const sync=()=>{if(note&&direction)note.required=direction.value.startsWith('adjust_');};
    direction?.addEventListener('change',sync);
    sync();
    const open=()=>{
      if(!dialog.open)dialog.showModal();
      dialog.querySelector('[name="amount"]')?.focus({preventScroll:true});
    };
    button.addEventListener('click',open);
    dialog.querySelectorAll('[data-inventory-move-close]').forEach(close=>{
      close.addEventListener('click',()=>dialog.close());
    });
    dialog.addEventListener('close',()=>{if(button.isConnected)button.focus({preventScroll:true});});
    if(dialog.hasAttribute('data-inventory-move-reopen')){
      open();
      dialog.querySelector('[data-inventory-move-error-focus]')?.focus({preventScroll:false});
    }
  });
};
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});
else init();
})();
