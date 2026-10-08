(()=>{
'use strict';
const inspector=document.querySelector('#inspector');
const frame=document.querySelector('#page-frame');
if(!inspector||!frame)return;
let scheduled=false;

function ensureStyle(){
  if(document.querySelector('style[data-cms-inspector-coherence]'))return;
  const style=document.createElement('style');
  style.dataset.cmsInspectorCoherence='1';
  style.textContent=`
#inspector.cms-inspector-coherent{padding:0}
#inspector .cms-coherent-inspector{gap:0}
#inspector .cms-coherent-inspector>header{padding:18px 18px 16px;margin:0;border-bottom:1px solid var(--line,#d7d8d3)}
#inspector .cms-inspector-group{display:grid;gap:12px;padding:17px 18px;border-bottom:1px solid var(--line,#d7d8d3)}
#inspector .cms-inspector-group-head{display:grid;gap:3px;margin:0 0 2px}
#inspector .cms-inspector-group-head h3{margin:0;font-size:13px;line-height:1.25}
#inspector .cms-inspector-group-head p{margin:0;color:var(--muted,#6b6e6d);font-size:11px;line-height:1.4}
#inspector .cms-inspector-group label{margin:0}
#inspector .cms-inspector-group [hidden]{display:none!important}
#inspector .cms-inspector-actions{display:flex;flex-wrap:wrap;gap:7px;padding:16px 18px 20px}
#inspector .cms-inspector-actions.button-row{margin:0}
#inspector .cms-inspector-global-links{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:0}
#inspector .cms-inspector-global-links .panel-button{width:auto;margin:0;min-height:38px;display:flex;align-items:center;justify-content:center;padding:7px 9px;text-align:center}
#inspector .cms-inspector-group .cms-access-save-state{margin:0;padding:8px 9px;border-radius:4px;background:#eceee9;color:#555a55;font-size:11px;line-height:1.35}
#inspector .cms-inspector-group .cms-access-save-state[data-state="saving"]{color:#5c4a17;background:#f5f0dc}
#inspector .cms-inspector-group .cms-access-save-state[data-state="saved"]{color:#1c5b42;background:#e5f0e9}
#inspector .cms-inspector-group .cms-access-save-state[data-state="error"]{color:#8b2b24;background:#f5e6e4}
#inspector [data-cms-access-controls].is-consumed,#inspector [data-cms-page-access].is-consumed{display:none}
`;
  document.head.append(style);
}
function group(panel,key,title,description){
  let host=panel.querySelector(`:scope > [data-inspector-group="${key}"]`);
  if(host)return host;
  host=document.createElement('section');
  host.className='cms-inspector-group';
  host.dataset.inspectorGroup=key;
  const head=document.createElement('div');
  head.className='cms-inspector-group-head';
  const h=document.createElement('h3');h.textContent=title;
  const p=document.createElement('p');p.textContent=description;
  head.append(h,p);host.append(head);panel.append(host);return host;
}
function labelFor(root,selector){const field=root.querySelector(selector);return field?.closest('label')||null}
function moveUnique(target,node){if(node&&node.parentElement!==target)target.append(node)}
function cleanSeparators(panel){panel.querySelectorAll(':scope > hr').forEach(node=>node.remove())}
function sectionInspector(){
  const name=inspector.querySelector('#s-name,#section-name');
  const panel=name?.closest('.inspector-section');
  if(!panel||!name)return false;
  inspector.classList.add('cms-inspector-coherent');panel.classList.add('cms-coherent-inspector');panel.dataset.inspectorMode='section';
  const identity=group(panel,'identity','Identidade','Como esta seção é identificada dentro do editor.');
  const layout=group(panel,'layout','Layout','Aparência e comportamento desta seção na página.');
  const audience=group(panel,'audience','Audiência','Quem pode receber esta seção.');
  const availability=group(panel,'availability','Disponibilidade','Quando esta seção fica disponível para a audiência escolhida.');
  moveUnique(identity,name.closest('label'));
  for(const selector of ['#s-width','#s-bg','#s-space','#s-ratio','#s-align','#s-mobile'])moveUnique(layout,labelFor(panel,selector));
  panel.querySelectorAll(':scope > .cms-layout-consolidation,:scope > .cms-layout-legacy-details').forEach(node=>moveUnique(layout,node));
  const access=inspector.querySelector('[data-cms-access-controls]');
  if(access){
    moveUnique(audience,labelFor(access,'#cms-access-audience'));
    moveUnique(audience,access.querySelector('[data-access-cohort]'));
    moveUnique(availability,labelFor(access,'#cms-access-availability'));
    moveUnique(availability,access.querySelector('[data-access-schedule]'));
    moveUnique(availability,access.querySelector('[data-access-lesson]'));
    access.querySelectorAll(':scope > .inspector-note').forEach(node=>moveUnique(availability,node));
    access.classList.add('is-consumed');
  }
  const actions=panel.querySelector(':scope > .button-row');
  if(actions){
    actions.classList.add('cms-inspector-actions');
    if(actions!==panel.lastElementChild)panel.append(actions);
  }
  cleanSeparators(panel);
  return true;
}
function pageInspector(){
  const title=inspector.querySelector('#p-title,#page-title');
  const panel=title?.closest('.inspector-section');
  if(!panel||!title)return false;
  inspector.classList.add('cms-inspector-coherent');panel.classList.add('cms-coherent-inspector');panel.dataset.inspectorMode='page';
  const identity=group(panel,'page-identity','Identidade','Título, endereço e idioma desta página.');
  const navigation=group(panel,'page-navigation','Navegação e aparência','Como a página participa do site e qual tema utiliza.');
  const seo=group(panel,'page-seo','SEO e compartilhamento','Informações usadas por mecanismos de busca e prévias.');
  const audience=group(panel,'page-audience','Audiência','Quem pode abrir esta página. O acesso é salvo automaticamente.');
  const globals=group(panel,'page-global','Configurações globais','Ajustes que valem para o site, não apenas para esta página.');
  for(const selector of ['#p-title','#page-title','#p-nav','#page-nav','#p-slug','#page-slug'])moveUnique(identity,labelFor(panel,selector));
  const locale=[...panel.querySelectorAll('label')].find(label=>label.textContent.trim().startsWith('Idioma'))||null;moveUnique(identity,locale);
  for(const selector of ['#p-show','#page-nav-visible','#p-theme','#page-theme'])moveUnique(navigation,labelFor(panel,selector));
  for(const selector of ['#p-seo-title','#meta-title','#p-seo-description','#meta-description'])moveUnique(seo,labelFor(panel,selector));
  const access=inspector.querySelector('[data-cms-page-access]');
  if(access){
    moveUnique(audience,labelFor(access,'#cms-page-access'));
    moveUnique(audience,access.querySelector('[data-page-access-cohort]'));
    moveUnique(audience,access.querySelector('[data-page-access-state]'));
    access.querySelectorAll(':scope > .inspector-note').forEach(node=>moveUnique(audience,node));
    access.classList.add('is-consumed');
  }
  const globalLinks=panel.querySelector(':scope > .button-row');
  if(globalLinks){globalLinks.classList.add('cms-inspector-global-links');moveUnique(globals,globalLinks)}
  cleanSeparators(panel);
  return true;
}
function organize(){scheduled=false;ensureStyle();if(sectionInspector())return;pageInspector()}
function schedule(){if(scheduled)return;scheduled=true;queueMicrotask(organize)}
new MutationObserver(schedule).observe(inspector,{childList:true,subtree:true});
frame.addEventListener('load',()=>setTimeout(schedule,120));
schedule();
})();
