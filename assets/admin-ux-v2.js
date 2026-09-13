(()=>{
'use strict';
function makeCollapsible(card){
  const header=card.querySelector(':scope > .admin-editor-header');
  if(!header||card.dataset.uxCollapsible==='1')return;
  card.dataset.uxCollapsible='1';
  const bodyNodes=[...card.children].filter(node=>node!==header);
  if(!bodyNodes.length)return;
  const button=document.createElement('button');
  button.type='button';button.className='admin-panel-toggle';button.textContent='Abrir';button.setAttribute('aria-expanded','false');
  header.append(button);
  bodyNodes.forEach(node=>node.hidden=true);
  button.addEventListener('click',()=>{
    const open=button.getAttribute('aria-expanded')!=='true';
    button.setAttribute('aria-expanded',open?'true':'false');button.textContent=open?'Fechar':'Abrir';
    bodyNodes.forEach(node=>node.hidden=!open);
  });
}
if(document.body.classList.contains('admin-section-forms')){
  const cards=[...document.querySelectorAll('.admin-editor-card')];
  cards.slice(1).forEach(makeCollapsible);
}
})();
