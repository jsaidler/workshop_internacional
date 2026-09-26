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
function optimizeThumbs(){
  if(!window.MediaLibrary?.previewSrc)return;
  grid.querySelectorAll('[data-asset]').forEach(card=>{
    const id=Number(card.dataset.asset||0);
    const item=MediaLibrary.items.find(candidate=>Number(candidate.id)===id);
    const image=card.querySelector('.library-preview img');
    if(!item||!image)return;
    const src=MediaLibrary.previewSrc(item,480);
    if(src&&image.getAttribute('src')!==src)image.setAttribute('src',src);
  });
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
new MutationObserver(optimizeThumbs).observe(grid,{childList:true});
sync();
optimizeThumbs();
})();
