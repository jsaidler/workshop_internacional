(()=>{
'use strict';
if(!document.body.classList.contains('admin-section-forms'))return;
const cards=[...document.querySelectorAll('.admin-editor-card')];
if(cards.length<2)return;
cards.slice(1).forEach(card=>{
  const header=card.querySelector(':scope > .admin-editor-header');
  if(!header||card.dataset.collapsibleReady==='1')return;
  card.dataset.collapsibleReady='1';
  card.classList.add('admin-collapsible-card');
  const content=document.createElement('div');
  content.className='admin-collapsible-content';
  while(header.nextSibling)content.append(header.nextSibling);
  content.hidden=true;
  card.append(content);
  const toggle=document.createElement('button');
  toggle.type='button';
  toggle.className='admin-panel-toggle';
  toggle.textContent='Abrir';
  toggle.setAttribute('aria-expanded','false');
  header.append(toggle);
  toggle.addEventListener('click',()=>{
    const open=content.hidden;
    content.hidden=!open;
    toggle.textContent=open?'Fechar':'Abrir';
    toggle.setAttribute('aria-expanded',open?'true':'false');
  });
});
})();
