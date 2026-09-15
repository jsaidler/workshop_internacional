(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const panel=document.querySelector('#editor-structure-panel');
const tree=document.querySelector('#page-structure-tree');
const tabs=[...document.querySelectorAll('[data-structure-view]')];
const inspector=document.querySelector('#inspector');
if(!frame||!panel||!tree||!tabs.length||!inspector)return;

let searchWrap=null;
let searchInput=null;
let searchEmpty=null;
let frameObserver=null;
let scheduled=false;
let lastQuery='';

const d=()=>frame.contentDocument;
const root=()=>d()?.querySelector('[data-cms-page-main]')||d()?.querySelector('main')||null;
const normalize=value=>String(value||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim();
const clean=value=>String(value||'').trim().replace(/\s+/g,' ');

function isTreeView(){return tabs.some(button=>button.dataset.structureView==='tree'&&button.classList.contains('is-active'))}
function ensureSearch(){
  if(searchWrap?.isConnected)return;
  searchWrap=document.createElement('div');
  searchWrap.className='cms-structure-search';
  searchWrap.hidden=true;
  searchWrap.innerHTML='<label><span class="sr-only">Buscar na estrutura</span><input type="search" placeholder="Buscar na estrutura" autocomplete="off" spellcheck="false"></label><button type="button" aria-label="Limpar busca" title="Limpar">×</button><p class="cms-structure-search-empty" hidden>Nenhum elemento encontrado.</p>';
  const tabsHost=panel.querySelector('.cms-structure-view-tabs');
  tabsHost?.after(searchWrap);
  searchInput=searchWrap.querySelector('input');
  searchEmpty=searchWrap.querySelector('.cms-structure-search-empty');
  searchInput.addEventListener('input',()=>filterTree(searchInput.value));
  searchInput.addEventListener('keydown',event=>{
    if(event.key==='Escape'&&searchInput.value){event.preventDefault();searchInput.value='';filterTree('');return}
    if(event.key==='Enter'){
      const directNode=tree.querySelector('.cms-page-tree-row[data-tree-level="node"][data-search-match="1"]:not([hidden]) .cms-page-tree-main');
      const directSection=tree.querySelector('.cms-page-tree-row[data-tree-level="section"][data-search-match="1"]:not([hidden]) .cms-page-tree-main');
      const visibleFallback=[...tree.querySelectorAll('.cms-page-tree-row:not([hidden]) .cms-page-tree-main')].find(button=>button.offsetParent!==null);
      const target=directNode||directSection||visibleFallback;
      if(target){event.preventDefault();target.click()}
    }
  });
  searchWrap.querySelector('button').addEventListener('click',()=>{searchInput.value='';filterTree('');searchInput.focus()});
}
function rowDepth(row){return Number.parseInt(row?.style?.getPropertyValue('--tree-depth')||'0',10)||0}
function revealAncestors(rows,index){
  let wanted=rowDepth(rows[index]);
  for(let i=index-1;i>=0&&wanted>1;i--){
    const depth=rowDepth(rows[i]);
    if(depth<wanted){rows[i].hidden=false;wanted=depth}
  }
}
function setMatch(row,match){
  if(!row)return;
  match?row.dataset.searchMatch='1':delete row.dataset.searchMatch;
}
function filterTree(query=''){
  ensureSearch();
  lastQuery=query;
  const needle=normalize(query);
  const groups=[...tree.querySelectorAll('.cms-page-tree-section')];
  let visible=0;
  for(const group of groups){
    const sectionRow=group.querySelector(':scope > .cms-page-tree-row[data-tree-level="section"]');
    const childRows=[...group.querySelectorAll(':scope > .cms-page-tree-row[data-tree-level="node"]')];
    if(!needle){
      group.hidden=false;
      if(sectionRow){sectionRow.hidden=false;setMatch(sectionRow,false)}
      childRows.forEach(row=>{row.hidden=false;setMatch(row,false)});
      visible++;
      continue;
    }
    const sectionMatch=normalize(sectionRow?.textContent).includes(needle);
    setMatch(sectionRow,sectionMatch);
    childRows.forEach(row=>{row.hidden=true;setMatch(row,false)});
    let childMatches=0;
    if(sectionMatch){
      childRows.forEach(row=>row.hidden=false);
    }else{
      childRows.forEach((row,index)=>{
        const match=normalize(row.textContent).includes(needle);
        setMatch(row,match);
        if(!match)return;
        row.hidden=false;
        revealAncestors(childRows,index);
        childMatches++;
      });
    }
    const showGroup=sectionMatch||childMatches>0;
    group.hidden=!showGroup;
    if(sectionRow)sectionRow.hidden=!showGroup;
    if(showGroup)visible++;
  }
  if(searchEmpty)searchEmpty.hidden=!needle||visible>0;
  searchWrap?.classList.toggle('has-query',!!needle);
}
function syncSearchVisibility(){
  ensureSearch();
  searchWrap.hidden=!isTreeView();
  if(isTreeView())filterTree(searchInput?.value||lastQuery);
}

function sectionLabel(section){return clean(section?.dataset?.cmsSectionName||section?.dataset?.cmsSection||'Seção')}
function componentType(node){
  if(!node)return'Bloco';
  if(node.hasAttribute('data-cms-column')){
    const siblings=[...node.parentElement?.querySelectorAll(':scope > [data-cms-column]')||[]];
    const index=siblings.indexOf(node);
    return index>=0?`Coluna ${index+1}`:'Coluna';
  }
  if(node.dataset.cmsContainer==='columns')return'Grupo de colunas';
  if(node.dataset.cmsContainer==='stack')return'Contêiner';
  const labels={paragraph:'Parágrafo',heading:'Título',image:'Imagem',carousel:'Carrossel',button:'Botão',divider:'Divisor',spacer:'Espaço',list:'Lista',quote:'Citação',video:'Vídeo',gallery:'Galeria',container:'Contêiner'};
  return labels[node.dataset.cmsComponent]||'Bloco';
}
function componentLabel(node){
  const type=componentType(node);
  if(node?.hasAttribute('data-cms-column')||node?.dataset?.cmsContainer)return type;
  const hidden=node?.querySelector?.('[data-cms-component-label]')?.textContent||'';
  if(clean(hidden))return clean(hidden);
  const text=clean(node?.textContent||'');
  return text?text.slice(0,32):type;
}
function selectedNode(){
  const doc=d();
  if(!doc)return null;
  const structural=doc.querySelector('.cms-structure-selected');
  if(structural)return structural;
  const selected=doc.querySelector('.cms-editing,.cms-selection');
  return selected?.closest?.('[data-cms-component],[data-cms-column]')||null;
}
function selectedSection(){
  const doc=d();
  return selectedNode()?.closest('[data-cms-section]')||doc?.querySelector('.cms-section-selected[data-cms-section]')||doc?.querySelector('.cms-editing,.cms-selection')?.closest('[data-cms-section]')||null;
}
function pathFor(section,node){
  if(!section)return[];
  const path=[{node:section,label:sectionLabel(section),kind:'section'}];
  if(!node)return path;
  const structural=[];
  let current=node;
  while(current&&current!==section){
    if(current.matches?.('[data-cms-component],[data-cms-column]'))structural.push(current);
    current=current.parentElement;
  }
  structural.reverse().forEach(item=>path.push({node:item,label:componentLabel(item),kind:item.hasAttribute('data-cms-column')?'column':'component'}));
  return path;
}
function selectTarget(node,kind){
  if(!node?.isConnected)return;
  if(kind==='section'){
    node.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true,view:frame.contentWindow}));
  }else{
    const target=node.matches('[data-cms-component],[data-cms-column]')?node:node.closest('[data-cms-component],[data-cms-column]');
    target?.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true,view:frame.contentWindow}));
  }
  setTimeout(()=>node.scrollIntoView({behavior:'smooth',block:'center'}),0);
}
function renderBreadcrumb(){
  const section=selectedSection();
  const node=selectedNode();
  const host=inspector.querySelector('.inspector-section');
  if(!host||!section){inspector.querySelector('.cms-editor-breadcrumb')?.remove();return}
  const path=pathFor(section,node);
  if(!path.length)return;
  let crumb=host.querySelector('.cms-editor-breadcrumb');
  if(!crumb){
    crumb=document.createElement('nav');
    crumb.className='cms-editor-breadcrumb';
    crumb.setAttribute('aria-label','Localização do elemento');
    const header=host.querySelector(':scope > header');
    header?.after(crumb);
  }
  const signature=path.map(item=>item.label).join(' > ');
  if(crumb.dataset.signature===signature)return;
  crumb.dataset.signature=signature;
  crumb.innerHTML=path.map((item,index)=>`<button type="button" data-crumb="${index}" ${index===path.length-1?'aria-current="page"':''} title="${item.label.replace(/"/g,'&quot;')}">${item.label}</button>${index<path.length-1?'<span aria-hidden="true">›</span>':''}`).join('');
  crumb.querySelectorAll('[data-crumb]').forEach(button=>button.addEventListener('click',()=>{const item=path[Number(button.dataset.crumb)];if(item)selectTarget(item.node,item.kind)}));
}
function schedule(){
  if(scheduled)return;
  scheduled=true;
  requestAnimationFrame(()=>{scheduled=false;syncSearchVisibility();renderBreadcrumb()});
}
function bindFrame(){
  frameObserver?.disconnect();
  const doc=d(),page=root();
  if(!doc||!page)return;
  frameObserver=new MutationObserver(schedule);
  frameObserver.observe(page,{subtree:true,childList:true,characterData:true,attributes:true,attributeFilter:['class','data-cms-section-name','data-cms-section','data-cms-component','data-cms-container']});
  doc.addEventListener('click',()=>setTimeout(schedule,0),true);
  schedule();
}

ensureSearch();
tabs.forEach(button=>button.addEventListener('click',()=>setTimeout(schedule,0)));
new MutationObserver(()=>{filterTree(searchInput?.value||lastQuery);schedule()}).observe(tree,{childList:true,subtree:true});
new MutationObserver(schedule).observe(inspector,{childList:true,subtree:true});
frame.addEventListener('load',()=>setTimeout(bindFrame,180));
setTimeout(()=>{bindFrame();schedule()},220);
})();
