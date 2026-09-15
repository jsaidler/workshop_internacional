(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const saveButton=document.querySelector('#save-page');
const undoButton=document.querySelector('#undo');
const redoButton=document.querySelector('#redo');
const pageSettingsButton=document.querySelector('#page-settings');
const inspector=document.querySelector('#inspector');
const toast=document.querySelector('#toast');
if(!frame||!saveButton||!undoButton||!redoButton)return;

const params=new URLSearchParams(location.search);
const pageId=Number(params.get('page')||0);
const transientClasses=['cms-selection','cms-section-selected','cms-editing','cms-structure-selected','cms-direct-drop-before','cms-direct-drop-after','cms-direct-drop-inside','cms-direct-dragging'];
let history=[];
let historyIndex=-1;
let frameObserver=null;
let commitTimer=0;
let settingsTimer=0;
let settingsDraft=null;
let suppressUntil=0;
let pendingSelection=null;
let syncingButtons=false;

const d=()=>frame.contentDocument;
const root=()=>d()?.querySelector('[data-cms-page-main]')||d()?.querySelector('main')||null;
const clone=value=>value==null?value:JSON.parse(JSON.stringify(value));
const clean=value=>String(value||'').trim();
const same=(a,b)=>JSON.stringify(a??null)===JSON.stringify(b??null);

function sectionKey(section){return section?.dataset?.cmsSection||section?.dataset?.cmsNodeId||''}
function captureSelection(){
  const doc=d();
  if(!doc)return null;
  const structural=doc.querySelector('.cms-structure-selected');
  if(structural){
    return {nodeId:clean(structural.dataset.cmsNodeId||''),sectionKey:sectionKey(structural.closest('[data-cms-section]'))};
  }
  const selected=doc.querySelector('.cms-editing,.cms-selection');
  if(selected){
    const node=selected.closest('[data-cms-node-id]');
    return {nodeId:clean(node?.dataset?.cmsNodeId||''),sectionKey:sectionKey(selected.closest('[data-cms-section]'))};
  }
  const section=doc.querySelector('.cms-section-selected[data-cms-section]');
  return section?{nodeId:'',sectionKey:sectionKey(section)}:null;
}
function snapshotHtml(){
  const current=root();
  if(!current)return'';
  const copy=current.cloneNode(true);
  copy.querySelectorAll('[data-cms-editor-ui]').forEach(node=>node.remove());
  copy.querySelectorAll('[contenteditable]').forEach(node=>node.removeAttribute('contenteditable'));
  copy.querySelectorAll('*').forEach(node=>{
    transientClasses.forEach(name=>node.classList.remove(name));
    if(!node.className&&node.hasAttribute('class'))node.removeAttribute('class');
  });
  copy.querySelectorAll('[data-cms-form-block]').forEach(block=>{
    const placeholder=copy.ownerDocument.createElement('div');
    placeholder.dataset.cmsFormKey=block.dataset.cmsFormKey||'';
    block.replaceWith(placeholder);
  });
  return copy.innerHTML.trim();
}
function snapshot(selectionOverride=null){
  return {html:snapshotHtml(),settings:clone(settingsDraft),selection:selectionOverride||captureSelection()};
}
function syncButtons(){
  if(syncingButtons)return;
  syncingButtons=true;
  undoButton.disabled=historyIndex<=0;
  redoButton.disabled=historyIndex<0||historyIndex>=history.length-1;
  syncingButtons=false;
}
function commitNow(selectionOverride=null){
  clearTimeout(commitTimer);commitTimer=0;
  clearTimeout(settingsTimer);settingsTimer=0;
  if(Date.now()<suppressUntil||!root())return false;
  const next=snapshot(selectionOverride);
  const current=history[historyIndex];
  if(current&&current.html===next.html&&same(current.settings,next.settings)){
    if(next.selection)current.selection=next.selection;
    syncButtons();
    return false;
  }
  history=history.slice(0,historyIndex+1);
  history.push(next);
  if(history.length>80)history.shift();
  historyIndex=history.length-1;
  syncButtons();
  return true;
}
function scheduleCommit(delay=30){
  clearTimeout(commitTimer);
  commitTimer=setTimeout(()=>commitNow(),delay);
}
function settingsFromPage(page){
  if(!page)return null;
  return {
    title:String(page.title||''),
    navTitle:String(page.navTitle||''),
    slug:String(page.slug||''),
    showInNav:!!page.showInNav,
    meta:{title:String(page.document?.meta?.title||''),description:String(page.document?.meta?.description||'')},
    theme:String(page.document?.theme||'auto')
  };
}
function settingsFromPanel(){
  const title=document.querySelector('#p-title');
  if(!title)return null;
  return {
    title:title.value,
    navTitle:document.querySelector('#p-nav')?.value||'',
    slug:document.querySelector('#p-slug')?.value||'',
    showInNav:!!document.querySelector('#p-show')?.checked,
    meta:{title:document.querySelector('#p-seo-title')?.value||'',description:document.querySelector('#p-seo-description')?.value||''},
    theme:document.querySelector('#p-theme')?.value||'auto'
  };
}
async function loadSettings(){
  if(!pageId)return;
  try{
    const response=await fetch(`/admin/api/cms-page-load.php?page=${pageId}`,{credentials:'same-origin',cache:'no-store'});
    if(!response.ok)return;
    const data=await response.json();
    settingsDraft=settingsFromPage(data.page);
    if(history.length===1&&historyIndex===0)history[0].settings=clone(settingsDraft);
  }catch{}
}
function applySettings(target){
  if(!target||!pageSettingsButton)return;
  pageSettingsButton.click();
  const values={
    '#p-title':target.title,
    '#p-nav':target.navTitle,
    '#p-slug':target.slug,
    '#p-seo-title':target.meta?.title||'',
    '#p-seo-description':target.meta?.description||'',
    '#p-theme':target.theme||'auto'
  };
  Object.entries(values).forEach(([selector,value])=>{
    const control=document.querySelector(selector);
    if(!control)return;
    control.value=value??'';
    control.dispatchEvent(new Event('input',{bubbles:true}));
  });
  const show=document.querySelector('#p-show');
  if(show){show.checked=!!target.showInNav;show.dispatchEvent(new Event('input',{bubbles:true}))}
}
function mutationInsideIgnoredArea(mutation){
  const node=mutation.target?.nodeType===Node.ELEMENT_NODE?mutation.target:mutation.target?.parentElement;
  return !!node?.closest?.('[data-cms-form-block],[data-cms-editor-ui]');
}
function textualMutation(mutation){
  const node=mutation.target?.nodeType===Node.ELEMENT_NODE?mutation.target:mutation.target?.parentElement;
  return !!node?.closest?.('[contenteditable="true"]')||mutation.attributeName==='data-cms-editor-label';
}
function relevantMutation(mutation){
  if(mutationInsideIgnoredArea(mutation))return false;
  if(mutation.type==='attributes'&&['class','contenteditable','title','aria-selected','aria-current'].includes(mutation.attributeName||''))return false;
  return true;
}
function observeFrame(){
  frameObserver?.disconnect();
  const page=root();
  if(!page)return;
  frameObserver=new MutationObserver(mutations=>{
    if(Date.now()<suppressUntil)return;
    const relevant=mutations.filter(relevantMutation);
    if(!relevant.length)return;
    const slower=relevant.every(textualMutation);
    scheduleCommit(slower?360:25);
  });
  frameObserver.observe(page,{subtree:true,childList:true,characterData:true,attributes:true});
}
function restoreSelectionAfterLoad(){
  if(!pendingSelection)return;
  const wanted=pendingSelection;
  pendingSelection=null;
  const doc=d();
  if(!doc)return;
  if(wanted.nodeId){
    const node=doc.querySelector(`[data-cms-node-id="${CSS.escape(wanted.nodeId)}"]`);
    if(node){
      sessionStorage.setItem('cms-editor-reselect',wanted.nodeId);
      node.dispatchEvent(new CustomEvent('cms:history-restored',{bubbles:true,detail:wanted}));
      setTimeout(()=>{
        if(!doc.querySelector('.cms-structure-selected'))node.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true,view:frame.contentWindow}));
      },180);
      return;
    }
  }
  if(wanted.sectionKey){
    const section=[...doc.querySelectorAll('[data-cms-section]')].find(item=>sectionKey(item)===wanted.sectionKey);
    section?.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true,view:frame.contentWindow}));
  }
}
function refreshCurrentSelection(){
  const current=history[historyIndex];
  if(current)current.selection=captureSelection()||current.selection;
}
function persistRestoredState(selection){
  if(selection?.nodeId)sessionStorage.setItem('cms-editor-reselect',selection.nodeId);
  if(!toast){saveButton.click();return}
  toast.textContent='';
  toast.classList.remove('show');
  let finished=false;
  const observer=new MutationObserver(()=>{
    const message=clean(toast.textContent);
    if(!message)return;
    if(message==='Página salva.'){
      finished=true;
      observer.disconnect();
      try{frame.contentWindow.location.reload()}catch{}
      return;
    }
    if(message!=='Página salva.'){
      finished=true;
      observer.disconnect();
    }
  });
  observer.observe(toast,{subtree:true,childList:true,characterData:true,attributes:true});
  saveButton.click();
  setTimeout(()=>{if(!finished)observer.disconnect()},6000);
}
function restoreAt(nextIndex){
  if(nextIndex<0||nextIndex>=history.length||nextIndex===historyIndex)return;
  const target=history[nextIndex];
  const currentSettings=clone(settingsDraft);
  historyIndex=nextIndex;
  suppressUntil=Date.now()+500;
  frameObserver?.disconnect();
  const page=root();
  if(!page)return;
  page.innerHTML=target.html;
  pendingSelection=clone(target.selection);
  if(!same(currentSettings,target.settings))applySettings(target.settings);
  settingsDraft=clone(target.settings);
  observeFrame();
  syncButtons();
  persistRestoredState(target.selection);
}
function undo(){if(historyIndex>0)restoreAt(historyIndex-1)}
function redo(){if(historyIndex>=0&&historyIndex<history.length-1)restoreAt(historyIndex+1)}
function initFrame(){
  if(!root())return;
  observeFrame();
  if(!history.length){
    history=[snapshot()];
    historyIndex=0;
  }else{
    const current=snapshot();
    const active=history[historyIndex];
    if(active&&active.html===current.html&&same(active.settings,current.settings))active.selection=current.selection||active.selection;
  }
  syncButtons();
  setTimeout(()=>{restoreSelectionAfterLoad();refreshCurrentSelection();syncButtons()},260);
}

undoButton.onclick=event=>{event?.preventDefault?.();undo()};
redoButton.onclick=event=>{event?.preventDefault?.();redo()};
new MutationObserver(()=>{if(!syncingButtons)queueMicrotask(syncButtons)}).observe(undoButton,{attributes:true,attributeFilter:['disabled']});
new MutationObserver(()=>{if(!syncingButtons)queueMicrotask(syncButtons)}).observe(redoButton,{attributes:true,attributeFilter:['disabled']});

document.addEventListener('input',event=>{
  if(Date.now()<suppressUntil)return;
  if(event.target?.matches?.('#p-title,#p-nav,#p-slug,#p-show,#p-seo-title,#p-seo-description,#p-theme')){
    settingsDraft=settingsFromPanel()||settingsDraft;
    clearTimeout(settingsTimer);
    settingsTimer=setTimeout(()=>commitNow(),420);
  }
},true);
document.addEventListener('change',event=>{
  if(Date.now()<suppressUntil)return;
  if(event.target?.matches?.('#p-title,#p-nav,#p-slug,#p-show,#p-seo-title,#p-seo-description,#p-theme')){
    settingsDraft=settingsFromPanel()||settingsDraft;
    commitNow();
  }
  if(event.target?.matches?.('[data-cms-editor-label-input]'))commitNow();
},true);
document.addEventListener('keydown',event=>{
  if(event.target?.matches?.('[data-cms-editor-label-input]')&&event.key==='Enter'&&Date.now()>=suppressUntil)commitNow();
  const target=event.target;
  const typing=target?.matches?.('input,textarea,select')||target?.isContentEditable;
  if(typing||event.altKey)return;
  const modifier=event.ctrlKey||event.metaKey;
  if(!modifier)return;
  if(event.key.toLowerCase()==='z'){
    event.preventDefault();
    event.shiftKey?redo():undo();
  }else if(event.key.toLowerCase()==='y'){
    event.preventDefault();
    redo();
  }
},true);

frame.addEventListener('load',()=>setTimeout(initFrame,220));
window.CmsEditorHistory={commit:options=>commitNow(options?.selection||null),undo,redo,getState:()=>({index:historyIndex,length:history.length,canUndo:historyIndex>0,canRedo:historyIndex>=0&&historyIndex<history.length-1})};
loadSettings();
setTimeout(initFrame,260);
})();
