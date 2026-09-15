(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const saveButton=document.querySelector('#save-page');
const treeHost=document.querySelector('#page-structure-tree');
if(!frame||!saveButton||!treeHost)return;

const uid=prefix=>`${prefix}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2,8)}`;
const d=()=>frame.contentDocument;
const pageRoot=()=>d()?.querySelector('[data-cms-page-main]')||d()?.querySelector('main')||null;
const sections=()=>pageRoot()?[...pageRoot().children].filter(node=>node.matches('[data-cms-section]')):[];
const boundDocuments=new WeakSet();
let dragNode=null;
let frameObserver=null;
let treeObserver=null;
let canvasLayer=null;
let canvasStyle=null;
let rafPending=false;

function ensureNodeId(node){
  if(!node)return'';
  if(!node.dataset.cmsNodeId)node.dataset.cmsNodeId=uid(node.hasAttribute('data-cms-column')?'column':'node');
  return node.dataset.cmsNodeId;
}
function remember(node){const id=ensureNodeId(node);if(id)sessionStorage.setItem('cms-editor-reselect',id)}
function clearCanvasMarks(){
  d()?.querySelectorAll('.cms-direct-drop-before,.cms-direct-drop-after,.cms-direct-drop-inside,.cms-direct-dragging').forEach(node=>node.classList.remove('cms-direct-drop-before','cms-direct-drop-after','cms-direct-drop-inside','cms-direct-dragging'));
}
function clearTreeDropMarks(){treeHost.querySelectorAll('.is-direct-drop-before,.is-direct-drop-after,.is-direct-drop-inside').forEach(row=>row.classList.remove('is-direct-drop-before','is-direct-drop-after','is-direct-drop-inside'))}
function clearTreeMarks(){treeHost.querySelectorAll('.is-direct-drop-before,.is-direct-drop-after,.is-direct-drop-inside,.is-direct-dragging').forEach(row=>row.classList.remove('is-direct-drop-before','is-direct-drop-after','is-direct-drop-inside','is-direct-dragging'))}
function clearDrag(){clearCanvasMarks();clearTreeMarks();dragNode=null}
function saveAndReload(node){
  remember(node);
  clearCanvasMarks();
  clearTreeMarks();
  setTimeout(()=>{
    saveButton.click();
    setTimeout(()=>{try{frame.contentWindow.location.reload()}catch{}},650);
  },70);
}
function canDrop(drag,target){
  if(!drag||!target||drag===target||drag.contains(target))return false;
  if(drag.hasAttribute('data-cms-column'))return target.hasAttribute('data-cms-column')&&target.parentElement===drag.parentElement;
  if(target.hasAttribute('data-cms-column'))return true;
  if(target.dataset.cmsContainer==='stack')return true;
  return target.hasAttribute('data-cms-component')&&!target.hasAttribute('data-cms-column');
}
function modeFor(drag,target,event,axis='vertical'){
  if(!drag||!target)return'';
  if(target.hasAttribute('data-cms-column')&&!drag.hasAttribute('data-cms-column'))return'inside';
  if(target.dataset.cmsContainer==='stack')return'inside';
  const rect=target.getBoundingClientRect();
  if(axis==='horizontal')return event.clientX>=rect.left+rect.width/2?'after':'before';
  return event.clientY>=rect.top+rect.height/2?'after':'before';
}
function performDrop(drag,target,mode){
  if(!canDrop(drag,target))return false;
  if(target.hasAttribute('data-cms-column')){
    if(drag.hasAttribute('data-cms-column'))mode==='after'?target.after(drag):target.before(drag);
    else target.append(drag);
  }else if(target.dataset.cmsContainer==='stack'){
    target.append(drag);
  }else if(mode==='after'){
    target.after(drag);
  }else{
    target.before(drag);
  }
  saveAndReload(drag);
  return true;
}
function structuralTargetFromEvent(event){
  const target=event.target?.closest?.('[data-cms-column],[data-cms-container="stack"],[data-cms-component]')||null;
  if(!target||target.closest('[data-cms-form-block]'))return null;
  return target;
}
function selectedMovable(){
  const doc=d();
  if(!doc)return null;
  const structural=doc.querySelector('.cms-structure-selected');
  if(structural)return structural;
  const selected=doc.querySelector('.cms-editing[data-cms-editable],.cms-selection[data-cms-editable],.cms-selection[data-cms-image],.cms-selection[data-cms-image-placeholder],video.cms-selection');
  return selected?.closest?.('[data-cms-component],[data-cms-image-block]')||null;
}
function ensureCanvasUi(){
  const doc=d();
  if(!doc?.body)return null;
  const existingStyle=doc.querySelector('style[data-cms-direct-structure-style="1"]');
  if(existingStyle)canvasStyle=existingStyle;
  if(!canvasStyle||!doc.contains(canvasStyle)){
    canvasStyle=doc.createElement('style');
    canvasStyle.dataset.cmsDirectStructureStyle='1';
    canvasStyle.textContent=`
.cms-direct-move-layer{position:fixed;inset:0;z-index:2147483100;pointer-events:none;font-family:Arial,sans-serif}
.cms-direct-move-handle{position:fixed;pointer-events:auto;min-height:26px;padding:5px 8px;border:1px solid #171817;border-radius:999px;background:#171817;color:#fff;box-shadow:0 2px 10px rgba(0,0,0,.18);font:600 10px/1 Arial,sans-serif;cursor:grab;white-space:nowrap}
.cms-direct-move-handle:active{cursor:grabbing}
.cms-direct-drop-before{box-shadow:inset 0 3px 0 #1e4d3c!important}
.cms-direct-drop-after{box-shadow:inset 0 -3px 0 #1e4d3c!important}
.cms-direct-drop-inside{outline:2px solid #1e4d3c!important;outline-offset:-2px!important;background-image:linear-gradient(rgba(30,77,60,.07),rgba(30,77,60,.07))!important}
.cms-direct-dragging{opacity:.45!important}
`;
    doc.head.append(canvasStyle);
  }
  const existingLayer=doc.querySelector('body > .cms-direct-move-layer[data-cms-editor-ui="1"]');
  if(existingLayer)canvasLayer=existingLayer;
  if(!canvasLayer||!doc.contains(canvasLayer)){
    canvasLayer=doc.createElement('div');
    canvasLayer.className='cms-direct-move-layer';
    canvasLayer.dataset.cmsEditorUi='1';
    doc.body.append(canvasLayer);
  }
  doc.querySelectorAll('body > .cms-direct-move-layer[data-cms-editor-ui="1"]').forEach((layer,index)=>{if(index>0)layer.remove()});
  return canvasLayer;
}
function renderCanvasHandle(){
  rafPending=false;
  const doc=d(),layer=ensureCanvasUi();
  if(!doc||!layer)return;
  layer.innerHTML='';
  const node=selectedMovable();
  if(!node||!node.isConnected||node.closest('[data-cms-form-block]'))return;
  const rect=node.getBoundingClientRect();
  if(rect.width<=0||rect.height<=0)return;
  const button=doc.createElement('button');
  button.type='button';
  button.className='cms-direct-move-handle';
  button.textContent='Mover ⋮⋮';
  button.draggable=true;
  button.setAttribute('aria-label','Arrastar para mover este bloco');
  button.title='Arraste para mudar a ordem ou mover para outra coluna';
  layer.append(button);
  const box=button.getBoundingClientRect();
  const left=Math.max(6,Math.min(rect.right-box.width-6,doc.defaultView.innerWidth-box.width-6));
  const top=Math.max(6,Math.min(rect.top+6,doc.defaultView.innerHeight-box.height-6));
  button.style.left=`${left}px`;
  button.style.top=`${top}px`;
  button.addEventListener('dragstart',event=>{
    dragNode=node;
    ensureNodeId(node);
    node.classList.add('cms-direct-dragging');
    event.dataTransfer.effectAllowed='move';
    try{event.dataTransfer.setData('text/plain',node.dataset.cmsNodeId||'cms-node')}catch{}
  });
  button.addEventListener('dragend',()=>{clearDrag();scheduleCanvasHandle()});
}
function scheduleCanvasHandle(){
  if(rafPending)return;
  rafPending=true;
  (frame.contentWindow||window).requestAnimationFrame(renderCanvasHandle);
}
function bindCanvasDnD(doc){
  ensureCanvasUi();
  doc.addEventListener('dragover',event=>{
    if(!dragNode)return;
    const target=structuralTargetFromEvent(event);
    if(!canDrop(dragNode,target))return;
    event.preventDefault();
    event.dataTransfer.dropEffect='move';
    clearCanvasMarks();
    dragNode.classList.add('cms-direct-dragging');
    const axis=dragNode.hasAttribute('data-cms-column')&&target.hasAttribute('data-cms-column')?'horizontal':'vertical';
    const mode=modeFor(dragNode,target,event,axis);
    target.classList.add(mode==='inside'?'cms-direct-drop-inside':mode==='after'?'cms-direct-drop-after':'cms-direct-drop-before');
  },true);
  doc.addEventListener('drop',event=>{
    if(!dragNode)return;
    const target=structuralTargetFromEvent(event);
    if(!canDrop(dragNode,target))return;
    event.preventDefault();
    event.stopPropagation();
    const axis=dragNode.hasAttribute('data-cms-column')&&target.hasAttribute('data-cms-column')?'horizontal':'vertical';
    const mode=modeFor(dragNode,target,event,axis);
    const moved=dragNode;
    clearCanvasMarks();
    performDrop(moved,target,mode);
    dragNode=null;
  },true);
  doc.addEventListener('dragend',()=>{clearDrag();scheduleCanvasHandle()},true);
}
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
function treeNodeForRow(row){
  const button=row.querySelector('[data-page-tree-node]');
  const section=sections()[Number(button?.dataset.pageTreeSection)];
  const index=Number(button?.dataset.pageTreeNode);
  if(!section||Number.isNaN(index))return null;
  return collect(section)[index]?.node||null;
}
function treeMode(drag,target,row,event){
  if(target.hasAttribute('data-cms-column')&&!drag.hasAttribute('data-cms-column'))return'inside';
  if(target.dataset.cmsContainer==='stack')return'inside';
  const rect=row.getBoundingClientRect();
  return event.clientY>=rect.top+rect.height/2?'after':'before';
}
function enhanceTree(){
  treeHost.querySelectorAll('.cms-page-tree-row[data-tree-level="node"]').forEach(row=>{
    if(row.dataset.cmsDirectDndReady==='1')return;
    row.dataset.cmsDirectDndReady='1';
    const grip=document.createElement('span');
    grip.className='cms-page-tree-grip';
    grip.textContent='⋮⋮';
    grip.draggable=true;
    grip.tabIndex=0;
    grip.setAttribute('role','button');
    grip.setAttribute('aria-label','Arrastar para mover');
    grip.title='Arraste para mudar a ordem ou mover entre colunas';
    row.append(grip);
    grip.addEventListener('dragstart',event=>{
      const node=treeNodeForRow(row);
      if(!node){event.preventDefault();return}
      dragNode=node;
      ensureNodeId(node);
      row.classList.add('is-direct-dragging');
      event.dataTransfer.effectAllowed='move';
      try{event.dataTransfer.setData('text/plain',node.dataset.cmsNodeId||'cms-node')}catch{}
    });
    grip.addEventListener('dragend',()=>clearDrag());
    row.addEventListener('dragover',event=>{
      if(!dragNode)return;
      const target=treeNodeForRow(row);
      if(!canDrop(dragNode,target))return;
      event.preventDefault();
      event.dataTransfer.dropEffect='move';
      clearTreeDropMarks();
      const mode=treeMode(dragNode,target,row,event);
      row.classList.add(mode==='inside'?'is-direct-drop-inside':mode==='after'?'is-direct-drop-after':'is-direct-drop-before');
    });
    row.addEventListener('dragleave',event=>{if(!row.contains(event.relatedTarget))row.classList.remove('is-direct-drop-before','is-direct-drop-after','is-direct-drop-inside')});
    row.addEventListener('drop',event=>{
      if(!dragNode)return;
      const target=treeNodeForRow(row);
      if(!canDrop(dragNode,target))return;
      event.preventDefault();
      event.stopPropagation();
      const mode=treeMode(dragNode,target,row,event);
      const moved=dragNode;
      clearTreeMarks();
      performDrop(moved,target,mode);
      dragNode=null;
    });
  });
}
function bindFrame(){
  const doc=d(),root=pageRoot();
  if(!doc||!root)return;
  canvasStyle=doc.querySelector('style[data-cms-direct-structure-style="1"]');
  canvasLayer=doc.querySelector('body > .cms-direct-move-layer[data-cms-editor-ui="1"]');
  if(boundDocuments.has(doc)){
    ensureCanvasUi();
    scheduleCanvasHandle();
    setTimeout(enhanceTree,0);
    return;
  }
  boundDocuments.add(doc);
  frameObserver?.disconnect();
  clearDrag();
  bindCanvasDnD(doc);
  frameObserver=new MutationObserver(()=>scheduleCanvasHandle());
  frameObserver.observe(root,{subtree:true,childList:true,attributes:true,attributeFilter:['class','data-cms-node-id']});
  doc.addEventListener('click',()=>setTimeout(scheduleCanvasHandle,0),true);
  doc.defaultView?.addEventListener('scroll',scheduleCanvasHandle,{passive:true});
  doc.defaultView?.addEventListener('resize',scheduleCanvasHandle,{passive:true});
  scheduleCanvasHandle();
  setTimeout(enhanceTree,0);
}

treeObserver=new MutationObserver(()=>enhanceTree());
treeObserver.observe(treeHost,{subtree:true,childList:true});
frame.addEventListener('load',()=>setTimeout(bindFrame,180));
setTimeout(()=>{bindFrame();enhanceTree()},220);
})();
