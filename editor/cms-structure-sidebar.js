(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const sectionsList=document.querySelector('#sections-list');
const treeHost=document.querySelector('#page-structure-tree');
const tabs=[...document.querySelectorAll('[data-structure-view]')];
const heading=document.querySelector('#structure-panel-title');
const inspector=document.querySelector('#inspector');
if(!frame||!sectionsList||!treeHost||!tabs.length||!inspector)return;

let view=sessionStorage.getItem('cms-structure-sidebar-view')==='tree'?'tree':'sections';
let frameObserver=null,ticking=false,lastSectionKey='';
const collapsed=new Set(JSON.parse(sessionStorage.getItem('cms-structure-sidebar-collapsed')||'[]'));

const doc=()=>frame.contentDocument;
const root=()=>doc()?.querySelector('[data-cms-page-main]')||doc()?.querySelector('main');
const sections=()=>root()?[...root().children].filter(el=>el.matches('[data-cms-section]')):[];
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
function cleanText(value=''){return String(value).trim().replace(/\s+/g,' ')}
function sectionKey(section,index){return section.dataset.cmsSection||section.dataset.cmsNodeId||`section-${index}`}
function sectionLabel(section){return section.dataset.cmsSectionName||section.dataset.cmsSection||'Seção'}
function componentType(node){
  if(node.hasAttribute('data-cms-column'))return'Coluna';
  if(node.dataset.cmsContainer==='columns')return`${node.querySelectorAll(':scope > [data-cms-column]').length} colunas`;
  if(node.dataset.cmsContainer==='stack')return'Contêiner';
  const labels={paragraph:'Parágrafo',heading:'Título',image:'Imagem',carousel:'Carrossel',button:'Botão',divider:'Divisor',spacer:'Espaço',container:'Contêiner'};
  if(labels[node.dataset.cmsComponent])return labels[node.dataset.cmsComponent];
  if(node.matches('p'))return'Parágrafo';
  if(node.matches('h1,h2,h3,h4'))return'Título';
  return'Bloco';
}
function componentLabel(node,meta={}){
  if(node.hasAttribute('data-cms-column'))return`Coluna ${Number(meta.columnIndex||0)+1}`;
  if(node.dataset.cmsContainer==='columns')return'Grupo de colunas';
  if(node.dataset.cmsContainer==='stack')return'Contêiner';
  if(node.dataset.cmsComponent==='divider')return'Divisor';
  if(node.dataset.cmsComponent==='spacer')return'Espaço';
  if(node.dataset.cmsComponent==='image')return'Imagem';
  if(node.dataset.cmsComponent==='carousel')return'Carrossel';
  const text=cleanText(node.textContent||'');
  return text?text.slice(0,38):componentType(node);
}
function collect(parent,depth=0,out=[]){
  for(const child of parent.children){
    if(child.hasAttribute('data-cms-component')){
      out.push({node:child,depth});
      if(child.dataset.cmsContainer==='columns'){
        [...child.querySelectorAll(':scope > [data-cms-column]')].forEach((col,index)=>{
          out.push({node:col,depth:depth+1,columnIndex:index});
          collect(col,depth+2,out);
        });
      }else if(child.dataset.cmsContainer==='stack')collect(child,depth+1,out);
    }else collect(child,depth,out);
  }
  return out;
}
function selectedStructural(){return doc()?.querySelector('.cms-structure-selected')||null}
function selectedContent(){return doc()?.querySelector('.cms-editing,.cms-selection')||null}
function componentRoot(node){if(!node)return null;return node.closest('[data-cms-component]')||node.closest('[data-cms-image-block]')||null}
function activeNode(){return selectedStructural()||componentRoot(selectedContent())}
function activeSection(){return selectedStructural()?.closest('[data-cms-section]')||selectedContent()?.closest('[data-cms-section]')||doc()?.querySelector('.cms-section-selected[data-cms-section]')||null}
function saveCollapsed(){sessionStorage.setItem('cms-structure-sidebar-collapsed',JSON.stringify([...collapsed]))}
function setView(next){
  view=next==='tree'?'tree':'sections';
  sessionStorage.setItem('cms-structure-sidebar-view',view);
  sectionsList.classList.toggle('is-structure-hidden',view==='tree');
  treeHost.hidden=view!=='tree';
  tabs.forEach(button=>{
    const active=button.dataset.structureView===view;
    button.classList.toggle('is-active',active);
    button.setAttribute('aria-selected',active?'true':'false');
    button.tabIndex=active?0:-1;
  });
  if(heading)heading.textContent=view==='tree'?'Estrutura da página':'Seções';
  if(view==='tree')schedule();
}
function rowMarkup(section,sectionIndex,entry,nodeIndex,selected,context){
  const depth=entry.depth+1;
  return `<div class="cms-page-tree-row ${selected?'is-selected':''} ${context?'is-context':''}" data-tree-level="node" style="--tree-depth:${depth}"><span class="cms-page-tree-toggle is-placeholder" aria-hidden="true">›</span><button type="button" class="cms-page-tree-main" data-page-tree-node="${nodeIndex}" data-page-tree-section="${sectionIndex}" title="${esc(componentLabel(entry.node,entry))}"><strong>${esc(componentLabel(entry.node,entry))}</strong></button><span class="cms-page-tree-type">${esc(componentType(entry.node))}</span></div>`;
}
function render(){
  ticking=false;
  if(view!=='tree')return;
  const list=sections();
  if(!list.length){treeHost.innerHTML='<p class="cms-page-tree-empty">Esta página ainda não tem seções.</p>';return;}
  const currentSection=activeSection();
  const currentNode=activeNode();
  const currentKey=currentSection?sectionKey(currentSection,list.indexOf(currentSection)):'';
  if(currentKey&&currentKey!==lastSectionKey){collapsed.delete(currentKey);saveCollapsed();lastSectionKey=currentKey}
  treeHost.innerHTML=list.map((section,sectionIndex)=>{
    const key=sectionKey(section,sectionIndex);
    const entries=collect(section);
    const isCollapsed=collapsed.has(key);
    const sectionSelected=currentSection===section&&!currentNode;
    const sectionContext=currentSection===section&&!!currentNode;
    const sectionRow=`<div class="cms-page-tree-row ${sectionSelected?'is-selected':''} ${sectionContext?'is-context':''}" data-tree-level="section" style="--tree-depth:0"><button type="button" class="cms-page-tree-toggle ${entries.length?'':'is-placeholder'}" data-page-tree-toggle="${sectionIndex}" aria-label="${isCollapsed?'Expandir':'Recolher'} ${esc(sectionLabel(section))}" aria-expanded="${isCollapsed?'false':'true'}">${isCollapsed?'›':'⌄'}</button><button type="button" class="cms-page-tree-main" data-page-tree-section-root="${sectionIndex}" title="${esc(sectionLabel(section))}"><strong>${esc(sectionLabel(section))}</strong></button><span class="cms-page-tree-type">Seção</span></div>`;
    if(isCollapsed||!entries.length)return `<div class="cms-page-tree-section" data-page-tree-group="${sectionIndex}">${sectionRow}</div>`;
    const children=entries.map((entry,nodeIndex)=>rowMarkup(section,sectionIndex,entry,nodeIndex,currentNode===entry.node,currentSection===section&&currentNode!==entry.node)).join('');
    return `<div class="cms-page-tree-section" data-page-tree-group="${sectionIndex}">${sectionRow}${children}</div>`;
  }).join('');
  bindRows();
}
function schedule(){if(ticking)return;ticking=true;requestAnimationFrame(render)}
function dispatchSection(section){
  if(!section)return;
  section.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true,view:frame.contentWindow}));
  setTimeout(()=>section.scrollIntoView({behavior:'smooth',block:'center'}),0);
}
function selectNode(section,nodeIndex){
  if(!section)return;
  const entries=collect(section);
  const target=entries[nodeIndex]?.node;
  if(!target)return;
  if(activeSection()!==section)dispatchSection(section);
  let attempts=0;
  const bridge=()=>{
    const button=inspector.querySelector(`.cms-structure-tree [data-tree-select="${nodeIndex}"]`);
    if(button){
      button.click();
      target.scrollIntoView({behavior:'smooth',block:'center'});
      schedule();
      return;
    }
    if(attempts++<8)setTimeout(bridge,25);
  };
  setTimeout(bridge,0);
}
function bindRows(){
  treeHost.querySelectorAll('[data-page-tree-toggle]').forEach(button=>button.onclick=event=>{
    event.stopPropagation();
    const index=Number(button.dataset.pageTreeToggle),section=sections()[index];
    if(!section)return;
    const key=sectionKey(section,index);
    collapsed.has(key)?collapsed.delete(key):collapsed.add(key);
    saveCollapsed();
    render();
  });
  treeHost.querySelectorAll('[data-page-tree-section-root]').forEach(button=>button.onclick=()=>dispatchSection(sections()[Number(button.dataset.pageTreeSectionRoot)]));
  treeHost.querySelectorAll('[data-page-tree-node]').forEach(button=>button.onclick=()=>{
    const list=sections();
    selectNode(list[Number(button.dataset.pageTreeSection)],Number(button.dataset.pageTreeNode));
  });
}
function bindFrame(){
  frameObserver?.disconnect();
  const d=doc(),r=root();
  if(!d||!r)return;
  frameObserver=new MutationObserver(schedule);
  frameObserver.observe(r,{subtree:true,childList:true,characterData:true,attributes:true,attributeFilter:['class','data-cms-section-name','data-cms-section','data-cms-component','data-cms-container']});
  d.addEventListener('click',()=>setTimeout(schedule,0),true);
  schedule();
}

tabs.forEach(button=>button.addEventListener('click',()=>setView(button.dataset.structureView)));
new MutationObserver(schedule).observe(sectionsList,{childList:true,subtree:true,attributes:true,attributeFilter:['class']});
new MutationObserver(schedule).observe(inspector,{childList:true,subtree:true});
frame.addEventListener('load',()=>setTimeout(bindFrame,160));
setView(view);
setTimeout(bindFrame,180);
})();
