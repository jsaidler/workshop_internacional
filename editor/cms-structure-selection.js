(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const inspector=document.querySelector('#inspector');
if(!frame||!inspector)return;

function dispatchClick(node){
  if(!node?.isConnected)return false;
  const view=frame.contentWindow||window;
  node.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true,view}));
  setTimeout(()=>{if(node.isConnected)node.scrollIntoView({behavior:'smooth',block:'center'})},0);
  return true;
}
function structuralTarget(node){
  if(!node?.matches)return null;
  if(node.matches('[data-cms-column],[data-cms-container],[data-cms-component]'))return node;
  return node.closest?.('[data-cms-column],[data-cms-container],[data-cms-component]')||null;
}
function structuralNodes(section){
  return [...section.querySelectorAll('[data-cms-component],[data-cms-column]')];
}
function bridgeThroughCurrentInspector(target){
  const section=target.closest('[data-cms-section]');
  if(!section)return;
  const index=structuralNodes(section).indexOf(target);
  if(index<0)return;
  let attempts=0;
  const bridge=()=>{
    if(!target.isConnected||target.classList.contains('cms-structure-selected'))return;
    if(!section.classList.contains('cms-section-selected'))dispatchClick(section);
    const button=inspector.querySelector(`.cms-structure-tree [data-tree-select="${index}"]`);
    if(button){button.click();return}
    if(attempts++<12)setTimeout(bridge,30);
  };
  setTimeout(bridge,0);
}
function select(node){
  const target=structuralTarget(node);
  if(!target||!target.closest('[data-cms-section]')||target.closest('[data-cms-form-block]'))return false;
  dispatchClick(target);
  if(!target.classList.contains('cms-structure-selected'))bridgeThroughCurrentInspector(target);
  return true;
}
function selectSection(section){
  if(!section?.matches?.('[data-cms-section]'))section=section?.closest?.('[data-cms-section]')||null;
  return section?dispatchClick(section):false;
}
window.CmsEditorStructure={select,selectSection};
})();
