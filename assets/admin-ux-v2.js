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
function setupSiteSettings(){
  const form=document.querySelector('.admin-section-site .cms-settings-form');
  if(!form)return;
  const sections=[...form.querySelectorAll(':scope > section')];
  const preferredOrder=['Navegação principal','Identidade','Header','Footer','SEO global'];
  sections.sort((a,b)=>{
    const aa=(a.querySelector(':scope > .admin-kicker')?.textContent||'').trim();
    const bb=(b.querySelector(':scope > .admin-kicker')?.textContent||'').trim();
    const ai=preferredOrder.indexOf(aa),bi=preferredOrder.indexOf(bb);
    return (ai<0?99:ai)-(bi<0?99:bi);
  }).forEach(section=>form.insertBefore(section,form.querySelector(':scope > .dialog-actions')));
  sections.forEach(section=>{
    if(section.dataset.uxSettings==='1')return;
    section.dataset.uxSettings='1';section.classList.add('admin-settings-section');
    const kicker=section.querySelector(':scope > .admin-kicker');
    const label=(kicker?.textContent||'Configuração').trim();
    if(kicker)kicker.hidden=true;
    const body=[...section.children].filter(node=>node!==kicker);
    const toggle=document.createElement('button');toggle.type='button';toggle.className='admin-settings-toggle';toggle.innerHTML=`<span>${label==='SEO global'?'Padrões de busca':label}</span><small>${label==='Navegação principal'?'Itens exibidos no menu do site':label==='Identidade'?'Nome e assinatura do site':label==='Header'?'Cabeçalho, idioma e chamada principal':label==='Footer'?'Informações no rodapé':'Título, descrição e imagem padrão'}</small>`;
    section.insertBefore(toggle,body[0]||null);
    const open=label==='Navegação principal';toggle.setAttribute('aria-expanded',open?'true':'false');body.forEach(node=>node.hidden=!open);
    toggle.addEventListener('click',()=>{const next=toggle.getAttribute('aria-expanded')!=='true';toggle.setAttribute('aria-expanded',next?'true':'false');body.forEach(node=>node.hidden=!next);});
  });
}
function polishMediaDetail(){
  const save=document.querySelector('#md-save');if(save)save.textContent='Salvar informações';
}
const activitySelect=document.querySelector('.admin-site-current select[name="activity"],.admin-activity-box select[name="activity"]');
activitySelect?.addEventListener('change',()=>activitySelect.form?.submit());
if(document.body.classList.contains('admin-section-forms')){
  simplifyFormCards();
  const cards=[...document.querySelectorAll('.admin-editor-card')];
  cards.slice(1).forEach(makeCollapsible);
}
if(document.body.classList.contains('admin-section-site'))setupSiteSettings();
const mediaBody=document.querySelector('#media-detail-body');
if(mediaBody){new MutationObserver(polishMediaDetail).observe(mediaBody,{childList:true,subtree:true});polishMediaDetail();}
})();
