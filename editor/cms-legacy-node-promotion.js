(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const saveButton=document.querySelector('#save-page');
if(!frame||!saveButton)return;

let saveTimer=0;
const uid=prefix=>`${prefix}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2,8)}`;
const candidateSelector='[data-cms-image-block],figure,blockquote,ul,ol,h1,h2,h3,h4,h5,h6,p';

function componentType(node){
  if(node.matches('[data-cms-image-block],figure'))return'image';
  if(node.matches('blockquote'))return'quote';
  if(node.matches('ul,ol'))return'list';
  if(node.matches('h1,h2,h3,h4,h5,h6'))return'heading';
  if(node.matches('p'))return'paragraph';
  return'';
}
function isSafe(node){
  if(!node||node.hasAttribute('data-cms-component'))return false;
  if(node.closest('[data-cms-component]'))return false;
  if(!node.closest('[data-cms-section]'))return false;
  if(node.closest('[data-cms-form-block],form,nav,footer,[data-cms-editor-ui],.cms-inline-layer'))return false;
  return !!componentType(node);
}
function candidateFrom(target){
  if(!(target instanceof Element))return null;
  const node=target.closest(candidateSelector);
  return isSafe(node)?node:null;
}
function scheduleSave(){
  clearTimeout(saveTimer);
  saveTimer=setTimeout(()=>saveButton.click(),900);
}
function promote(node){
  if(!isSafe(node))return null;
  const type=componentType(node);
  node.dataset.cmsComponent=type;
  if(!node.dataset.cmsNodeId)node.dataset.cmsNodeId=uid(`legacy-${type}`);
  node.dispatchEvent(new CustomEvent('cms:structure-changed',{bubbles:true,detail:{nodeId:node.dataset.cmsNodeId,type}}));
  scheduleSave();
  return node;
}
function bind(){
  const doc=frame.contentDocument;
  if(!doc?.body||doc.body.dataset.cmsLegacyPromotionBound==='1')return;
  doc.body.dataset.cmsLegacyPromotionBound='1';
  doc.addEventListener('pointerdown',event=>{
    const node=candidateFrom(event.target);
    if(node)promote(node);
  },true);
  doc.addEventListener('focusin',event=>{
    const node=candidateFrom(event.target);
    if(node)promote(node);
  },true);
}

frame.addEventListener('load',()=>setTimeout(bind,80));
setTimeout(bind,120);
})();
