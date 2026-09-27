(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
if(!frame)return;

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
function select(node){
  const target=structuralTarget(node);
  if(!target||!target.closest('[data-cms-section]')||target.closest('[data-cms-form-block]'))return false;
  return dispatchClick(target);
}
function selectSection(section){
  if(!section?.matches?.('[data-cms-section]'))section=section?.closest?.('[data-cms-section]')||null;
  return section?dispatchClick(section):false;
}
window.CmsEditorStructure={select,selectSection};
})();
