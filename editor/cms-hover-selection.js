(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
if(!frame)return;
let boundDoc=null,layer=null,box=null,labelNode=null,hovered=null,raf=0;
const d=()=>frame.contentDocument;
const clean=value=>String(value||'').trim().replace(/\s+/g,' ');
const sectionName=node=>clean(node?.dataset?.cmsSectionName||node?.dataset?.cmsSection||'Seção');
function structuralLabel(node){
  if(node.hasAttribute('data-cms-column')){
    const siblings=[...node.parentElement?.querySelectorAll(':scope > [data-cms-column]')||[]];const index=siblings.indexOf(node);return index>=0?`Coluna ${index+1}`:'Coluna';
  }
  if(node.dataset.cmsContainer==='columns')return'Grupo de colunas';
  if(node.dataset.cmsContainer==='stack')return'Contêiner';
  const labels={paragraph:'Parágrafo',heading:'Título',image:'Imagem',carousel:'Carrossel',button:'Botão',divider:'Divisor',spacer:'Espaço',list:'Lista',quote:'Citação',video:'Vídeo',gallery:'Galeria',container:'Contêiner'};
  return labels[node.dataset.cmsComponent]||'Bloco';
}
function targetFrom(node){
  if(!node?.closest)return null;
  if(node.closest('[data-cms-editor-ui]'))return null;
  const special=node.closest('[data-cms-editable],[data-cms-image],[data-cms-image-placeholder],[data-cms-form-key],[data-cms-video]');
  if(special&&!special.closest('[data-cms-form-block]')){
    if(special.hasAttribute('data-cms-editable'))return{node:special,label:`Texto · ${clean(special.textContent).slice(0,46)||'sem texto'}`};
    if(special.hasAttribute('data-cms-image')||special.hasAttribute('data-cms-image-placeholder'))return{node:special,label:'Imagem'};
    if(special.hasAttribute('data-cms-video'))return{node:special,label:'Vídeo'};
    if(special.hasAttribute('data-cms-form-key'))return{node:special,label:`Formulário · ${clean(special.dataset.cmsFormKey)||'formulário'}`};
  }
  const form=node.closest('[data-cms-form-key]');
  if(form)return{node:form,label:`Formulário · ${clean(form.dataset.cmsFormKey)||'formulário'}`};
  const structural=node.closest('[data-cms-column],[data-cms-container],[data-cms-component]');
  if(structural&&!structural.closest('[data-cms-form-block]'))return{node:structural,label:structuralLabel(structural)};
  const section=node.closest('[data-cms-section]');
  if(section)return{node:section,label:`Seção · ${sectionName(section)}`};
  return null;
}
function ensureUi(doc){
  if(layer?.isConnected)return;
  const style=doc.createElement('style');style.dataset.cmsEditorUi='hover-selection-style';style.textContent=`
[data-cms-editor-ui="hover-selection-layer"]{position:fixed;inset:0;pointer-events:none;z-index:2147483200;font-family:Arial,sans-serif}
.cms-editor-hover-box{position:fixed;box-sizing:border-box;border:2px solid #1e624b;background:rgba(30,98,75,.055);box-shadow:0 0 0 1px rgba(255,255,255,.72) inset;pointer-events:none}
.cms-editor-hover-label{position:absolute;left:-2px;top:-25px;max-width:min(360px,80vw);height:23px;padding:5px 8px;box-sizing:border-box;background:#1e624b;color:#fff;border-radius:3px 3px 0 0;font:600 10px/13px Arial,sans-serif;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cms-editor-hover-box.is-label-inside .cms-editor-hover-label{top:0;border-radius:0 0 3px 0}
`;
  style.dataset.cmsEditorUi='hover-selection-style';doc.head.append(style);
  layer=doc.createElement('div');layer.dataset.cmsEditorUi='hover-selection-layer';
  box=doc.createElement('div');box.className='cms-editor-hover-box';box.hidden=true;
  labelNode=doc.createElement('div');labelNode.className='cms-editor-hover-label';box.append(labelNode);layer.append(box);doc.body.append(layer);
}
function draw(){
  raf=0;const doc=d();if(!doc)return;ensureUi(doc);
  if(!hovered?.node?.isConnected){if(box)box.hidden=true;return}
  const rect=hovered.node.getBoundingClientRect();if(rect.width<1||rect.height<1){box.hidden=true;return}
  box.hidden=false;box.classList.toggle('is-label-inside',rect.top<27);
  Object.assign(box.style,{left:`${Math.round(rect.left)}px`,top:`${Math.round(rect.top)}px`,width:`${Math.round(rect.width)}px`,height:`${Math.round(rect.height)}px`});
  labelNode.textContent=hovered.label;
}
function schedule(){if(raf)return;raf=(frame.contentWindow||window).requestAnimationFrame(draw)}
function setHovered(next){
  if(next?.node===hovered?.node&&next?.label===hovered?.label)return;
  hovered=next;schedule();
}
function bind(){
  const doc=d();if(!doc||doc===boundDoc)return;boundDoc=doc;layer=box=labelNode=null;hovered=null;ensureUi(doc);
  doc.addEventListener('pointermove',event=>setHovered(targetFrom(event.target)),true);
  doc.addEventListener('pointerleave',()=>setHovered(null),true);
  doc.addEventListener('scroll',schedule,true);doc.defaultView?.addEventListener('resize',schedule,{passive:true});
  doc.addEventListener('click',()=>setTimeout(schedule,0),true);
}
frame.addEventListener('load',()=>setTimeout(bind,130));
setTimeout(bind,200);
window.CmsHoverSelection={targetFrom,refresh:schedule};
})();
