(()=>{
'use strict';
const inspector=document.querySelector('#inspector');
const sidebarTree=document.querySelector('#page-structure-tree');
const frame=document.querySelector('#page-frame');
if(!inspector||!sidebarTree)return;

const d=()=>frame?.contentDocument||null;
const root=()=>d()?.querySelector('[data-cms-page-main]')||d()?.querySelector('main')||null;
const sections=()=>root()?[...root().children].filter(node=>node.matches('[data-cms-section]')):[];

function collect(parent,depth=0,out=[]){
  for(const child of parent?.children||[]){
    if(child.hasAttribute('data-cms-component')){
      out.push({node:child,depth});
      if(child.dataset.cmsContainer==='columns'){
        [...child.querySelectorAll(':scope > [data-cms-column]')].forEach((column,index)=>{
          out.push({node:column,depth:depth+1,columnIndex:index});
          collect(column,depth+2,out);
        });
      }else if(child.dataset.cmsContainer==='stack')collect(child,depth+1,out);
    }else collect(child,depth,out);
  }
  return out;
}
function structuralTarget(button){
  const section=sections()[Number(button.dataset.pageTreeSection)];
  const index=Number(button.dataset.pageTreeNode);
  if(!section||Number.isNaN(index))return null;
  return collect(section)[index]?.node||null;
}
function activate(node){
  if(!frame||!node?.isConnected)return false;
  node.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true,view:frame.contentWindow}));
  setTimeout(()=>node.scrollIntoView({behavior:'smooth',block:'center'}),0);
  return true;
}
function removeDuplicateInspectorTrees(){
  inspector.querySelectorAll('.cms-structure-tree').forEach(tree=>tree.remove());
  inspector.dataset.cmsStructureAuthority='sidebar';
}

let scheduled=false;
function schedule(){
  if(scheduled)return;
  scheduled=true;
  queueMicrotask(()=>{scheduled=false;removeDuplicateInspectorTrees()});
}

sidebarTree.addEventListener('click',event=>{
  const button=event.target.closest?.('[data-page-tree-node]');
  if(!button||!sidebarTree.contains(button))return;
  const target=structuralTarget(button);
  if(!target)return;
  event.preventDefault();
  event.stopImmediatePropagation();
  activate(target);
},true);
new MutationObserver(schedule).observe(inspector,{childList:true,subtree:true});
removeDuplicateInspectorTrees();
window.CmsStructureAuthority={collect,structuralTarget,activate,removeDuplicateInspectorTrees};
})();
