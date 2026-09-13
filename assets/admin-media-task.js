(()=>{
'use strict';
const toggle=document.querySelector('#media-selection-toggle');
const grid=document.querySelector('#library-grid');
const clear=document.querySelector('#media-clear-selection');
if(!toggle||!grid)return;
let selecting=false;
function sync(){
  document.body.classList.toggle('media-selecting',selecting);
  toggle.setAttribute('aria-pressed',selecting?'true':'false');
  toggle.textContent=selecting?'Concluir seleção':'Selecionar';
}
toggle.addEventListener('click',()=>{
  selecting=!selecting;
  if(!selecting)clear?.click();
  sync();
});
clear?.addEventListener('click',()=>{selecting=false;sync()});
grid.addEventListener('click',event=>{
  if(!selecting)return;
  const open=event.target.closest('[data-open-asset]');
  if(!open)return;
  event.preventDefault();event.stopImmediatePropagation();
  const card=open.closest('[data-asset]');
  const checkbox=card?.querySelector('[data-select-asset]');
  checkbox?.click();
},true);
sync();
})();
