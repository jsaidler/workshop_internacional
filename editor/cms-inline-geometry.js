(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
if(!frame)return;
let observer=null,ticking=false;

function intersects(a,b){
  return a.left<b.right&&a.right>b.left&&a.top<b.bottom&&a.bottom>b.top;
}
function clamp(value,min,max){return Math.max(min,Math.min(value,max))}
function resetPair(before,after){
  before.style.removeProperty('transform');
  after.style.removeProperty('transform');
}
function adjust(){
  ticking=false;
  const doc=frame.contentDocument,win=frame.contentWindow;
  const layer=doc?.querySelector('.cms-inline-layer');
  if(!layer||!win)return;
  const before=[...layer.querySelectorAll('.cms-inline-add')].find(button=>button.textContent?.trim()==='+ Antes');
  const after=[...layer.querySelectorAll('.cms-inline-add')].find(button=>button.textContent?.trim()==='+ Depois');
  if(!before||!after)return;
  resetPair(before,after);
  const a=before.getBoundingClientRect(),b=after.getBoundingClientRect();
  if(!intersects(a,b))return;

  const center=(Math.min(a.left,b.left)+Math.max(a.right,b.right))/2;
  const gap=6;
  const beforeLeft=clamp(center-gap-a.width,6,Math.max(6,win.innerWidth-a.width-6));
  const afterLeft=clamp(center+gap,6,Math.max(6,win.innerWidth-b.width-6));
  const beforeDelta=beforeLeft-a.left;
  const afterDelta=afterLeft-b.left;
  before.style.transform=`translateX(${beforeDelta}px)`;
  after.style.transform=`translateX(${afterDelta}px)`;
}
function schedule(){if(ticking)return;ticking=true;(frame.contentWindow||window).requestAnimationFrame(adjust)}
function bind(){
  observer?.disconnect();
  const doc=frame.contentDocument;
  if(!doc?.body)return;
  observer=new MutationObserver(schedule);
  observer.observe(doc.body,{childList:true,subtree:true,attributes:true,attributeFilter:['class']});
  doc.defaultView?.addEventListener('scroll',schedule,{passive:true});
  doc.defaultView?.addEventListener('resize',schedule,{passive:true});
  schedule();
}
frame.addEventListener('load',()=>setTimeout(bind,140));
})();
