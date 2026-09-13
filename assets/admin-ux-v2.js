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
function simplifyFormCards(){
  document.querySelectorAll('.admin-section-forms .overview-card .admin-kicker').forEach(kicker=>{
    const text=(kicker.textContent||'').trim();
    if(text.includes(' · '))kicker.textContent=text.split(' · ')[0];
  });
}
function polishMediaDetail(){
  const save=document.querySelector('#md-save');if(save)save.textContent='Salvar informações';
}
const activitySelect=document.querySelector('.admin-activity-box select[name="activity"]');
activitySelect?.addEventListener('change',()=>activitySelect.form?.submit());
if(document.body.classList.contains('admin-section-forms')){
  simplifyFormCards();
  const cards=[...document.querySelectorAll('.admin-editor-card')];
  cards.slice(1).forEach(makeCollapsible);
}
const mediaBody=document.querySelector('#media-detail-body');
if(mediaBody){new MutationObserver(polishMediaDetail).observe(mediaBody,{childList:true,subtree:true});polishMediaDetail();}
})();
