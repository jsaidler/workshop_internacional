(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
if(!frame)return;
let observer=null,ticking=false,poll=0,boundWindow=null;

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
  const buttons=[...layer.querySelectorAll('.cms-inline-add')];
  const before=buttons.find(button=>button.textContent?.trim()==='+ Antes');
  const after=buttons.find(button=>button.textContent?.trim()==='+ Depois');
  if(!before||!after)return;
  resetPair(before,after);
  const a=before.getBoundingClientRect(),b=after.getBoundingClientRect();
  if(!intersects(a,b))return;

  const gap=8;
  const groupCenter=(Math.min(a.left,b.left)+Math.max(a.right,b.right))/2;
  let beforeLeft=groupCenter-gap-a.width;
  let afterLeft=groupCenter+gap;
  const total=a.width+b.width+gap*2;
  if(total>win.innerWidth-12){
    beforeLeft=6;
    afterLeft=Math.max(6,win.innerWidth-b.width-6);
  }
  beforeLeft=clamp(beforeLeft,6,Math.max(6,win.innerWidth-a.width-6));
  afterLeft=clamp(afterLeft,6,Math.max(6,win.innerWidth-b.width-6));
  before.style.transform=`translateX(${beforeLeft-a.left}px)`;
  after.style.transform=`translateX(${afterLeft-b.left}px)`;
  before.style.zIndex='2';
  after.style.zIndex='2';
}
function schedule(){
  if(ticking)return;
  ticking=true;
  const win=frame.contentWindow||window;
  win.requestAnimationFrame(adjust);
}
function unbind(){
  observer?.disconnect();observer=null;
  if(boundWindow){boundWindow.removeEventListener('scroll',schedule);boundWindow.removeEventListener('resize',schedule);boundWindow=null}
  if(poll){clearInterval(poll);poll=0}
}
function bind(){
  unbind();
  const doc=frame.contentDocument,win=frame.contentWindow;
  if(!doc?.body||!win)return;
  boundWindow=win;
  observer=new MutationObserver(schedule);
  observer.observe(doc.body,{childList:true,subtree:true,attributes:true,attributeFilter:['class']});
  win.addEventListener('scroll',schedule,{passive:true});
  win.addEventListener('resize',schedule,{passive:true});
  // Inline controls are rebuilt after selections and autosave reloads. The
  // lightweight poll guarantees collision correction even when those rebuilds
  // happen between mutation and animation-frame delivery.
  poll=setInterval(adjust,120);
  adjust();
  setTimeout(adjust,0);
  setTimeout(adjust,180);
}
frame.addEventListener('load',()=>setTimeout(bind,80));
if(frame.contentDocument?.readyState==='complete')setTimeout(bind,80);
})();
