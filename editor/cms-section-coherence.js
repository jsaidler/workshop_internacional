(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const host=document.querySelector('#sections-list');
const inspector=document.querySelector('#inspector');
const status=document.querySelector('#save-status');
if(!frame||!host)return;

let frameObserver=null;
let hostObserver=null;
let scheduled=false;
let dragging=null;

const d=()=>frame.contentDocument;
const root=()=>d()?.querySelector('[data-cms-page-main]')||d()?.querySelector('main')||null;
const sections=()=>root()?[...root().querySelectorAll('[data-cms-section]')]:[];
const label=section=>String(section?.dataset?.cmsSectionName||section?.dataset?.cmsSection||'Seção').trim()||'Seção';
const key=section=>String(section?.dataset?.cmsSection||'').trim();
const esc=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));

function selectedSection(){
  const doc=d();
  return doc?.querySelector('.cms-section-selected[data-cms-section]')
    ||doc?.querySelector('.cms-selection,.cms-editing,.cms-structure-selected')?.closest?.('[data-cms-section]')
    ||null;
}
function sameParentSections(section){
  const parent=section?.parentElement;
  return parent?[...parent.children].filter(node=>node.matches?.('[data-cms-section]')):[];
}
function markChanged(){
  if(status)status.textContent='Alterações não salvas';
  window.CmsEditorHistory?.commit?.();
  root()?.dispatchEvent(new CustomEvent('cms:structure-changed',{bubbles:true}));
}
function selectSection(section){
  if(!section?.isConnected)return;
  section.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true,view:frame.contentWindow}));
  setTimeout(()=>section.scrollIntoView({behavior:'smooth',block:'center'}),0);
  schedule();
}
function moveSibling(section,direction){
  if(!section?.isConnected)return false;
  const siblings=sameParentSections(section);
  const index=siblings.indexOf(section);
  const target=siblings[index+direction];
  if(index<0||!target)return false;
  direction<0?target.before(section):target.after(section);
  markChanged();
  selectSection(section);
  return true;
}
function needsRender(list){
  const rows=[...host.querySelectorAll('[data-cms-coherent-section]')];
  if(rows.length!==list.length)return true;
  return rows.some((row,index)=>row.dataset.cmsCoherentSection!==key(list[index])||row.querySelector('strong')?.textContent!==label(list[index])||row.classList.contains('is-selected')!==(selectedSection()===list[index]));
}
function render(){
  const list=sections();
  if(!needsRender(list))return;
  if(!list.length){
    host.innerHTML='<p class="inspector-note" data-cms-section-empty>Nenhuma seção editorial encontrada nesta página.</p>';
    return;
  }
  const selected=selectedSection();
  host.innerHTML=list.map((section,index)=>`<div class="section-row ${selected===section?'is-selected':''}" draggable="true" data-cms-coherent-section="${esc(key(section))}" data-index="${index}"><button type="button" class="section-row-main" data-select-section="${index}"><strong>${esc(label(section))}</strong><span>${esc(key(section))}</span></button><div class="section-row-actions"><button type="button" data-move-section="-1" data-index="${index}" aria-label="Subir ${esc(label(section))}">↑</button><button type="button" data-move-section="1" data-index="${index}" aria-label="Descer ${esc(label(section))}">↓</button></div></div>`).join('');
  host.querySelectorAll('[data-select-section]').forEach(button=>button.addEventListener('click',()=>selectSection(list[Number(button.dataset.selectSection)])));
  host.querySelectorAll('[data-move-section]').forEach(button=>button.addEventListener('click',event=>{
    event.preventDefault();event.stopPropagation();
    moveSibling(list[Number(button.dataset.index)],Number(button.dataset.moveSection));
  }));
  host.querySelectorAll('[data-cms-coherent-section]').forEach(row=>{
    row.addEventListener('dragstart',()=>{dragging=list[Number(row.dataset.index)]||null});
    row.addEventListener('dragover',event=>event.preventDefault());
    row.addEventListener('drop',event=>{
      event.preventDefault();
      const target=list[Number(row.dataset.index)]||null;
      if(!dragging||!target||dragging===target)return;
      if(dragging.parentElement!==target.parentElement){dragging=null;return;}
      const siblings=sameParentSections(target);
      const from=siblings.indexOf(dragging),to=siblings.indexOf(target);
      if(from<0||to<0){dragging=null;return;}
      to>from?target.after(dragging):target.before(dragging);
      const moved=dragging;dragging=null;
      markChanged();selectSection(moved);
    });
    row.addEventListener('dragend',()=>{dragging=null});
  });
}
function schedule(){
  if(scheduled)return;
  scheduled=true;
  requestAnimationFrame(()=>{scheduled=false;render()});
}
function bindFrame(){
  frameObserver?.disconnect();
  const page=root();
  if(!page){schedule();return;}
  frameObserver=new MutationObserver(schedule);
  frameObserver.observe(page,{subtree:true,childList:true,attributes:true,attributeFilter:['class','data-cms-section','data-cms-section-name']});
  d()?.addEventListener('click',()=>setTimeout(schedule,0),true);
  schedule();
}

// The legacy editor still wires section move buttons to a flat direct-child list.
// Capture those controls first so a nested section can only move among real siblings.
inspector?.addEventListener('click',event=>{
  const button=event.target?.closest?.('#s-up,#s-down');
  if(!button)return;
  const section=selectedSection();
  if(!section)return;
  event.preventDefault();event.stopImmediatePropagation();
  moveSibling(section,button.id==='s-up'?-1:1);
},true);

hostObserver=new MutationObserver(schedule);
hostObserver.observe(host,{childList:true,subtree:true,characterData:true});
frame.addEventListener('load',()=>setTimeout(bindFrame,120));
setTimeout(bindFrame,180);
})();
