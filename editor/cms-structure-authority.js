(()=>{
'use strict';
const inspector=document.querySelector('#inspector');
const sidebarTree=document.querySelector('#page-structure-tree');
if(!inspector||!sidebarTree)return;

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

new MutationObserver(schedule).observe(inspector,{childList:true,subtree:true});
removeDuplicateInspectorTrees();
})();
