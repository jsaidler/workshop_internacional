(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
if(!frame)return;
let observer=null,tick=0;
const overlap=(a,b)=>a.left<b.right&&a.right>b.left&&a.top<b.bottom&&a.bottom>b.top;
function clamp(value,min,max){return Math.max(min,Math.min(value,max))}
function resolvePair(before,after,win){
  if(!before||!after)return;
  const a=before.getBoundingClientRect(),b=after.getBoundingClientRect();
  if(!overlap(a,b))return;
  const gap=6,center=(Math.min(a.left,b.left)+Math.max(a.right,b.right))/2;
  let leftA=center-gap/2-a.width,leftB=center+gap/2;
  leftA=clamp(leftA,6,Math.max(6,win.innerWidth-a.width-6));
  leftB=clamp(leftB,6,Math.max(6,win.innerWidth-b.width-6));
  if(leftA+a.width+gap>leftB){
    const total=a.width+b.width+gap;
    const start=clamp(center-total/2,6,Math.max(6,win.innerWidth-total-6));
    leftA=start;leftB=start+a.width+gap;
  }
  before.style.left=`${leftA}px`;
  after.style.left=`${leftB}px`;
}
function layout(){
  tick=0;
  const doc=frame.contentDocument,win=frame.contentWindow;
  if(!doc||!win)return;
  const layer=doc.querySelector('.cms-inline-layer');
  if(!layer)return;
  const buttons=[...layer.querySelectorAll('.cms-inline-add')];
  const before=buttons.find(button=>button.textContent.trim()==='+ Antes');
  const after=buttons.find(button=>button.textContent.trim()==='+ Depois');
  resolvePair(before,after,win);
}
function schedule(){
  if(tick)return;
  const win=frame.contentWindow||window;
  tick=win.requestAnimationFrame(layout);
}
function bind(){
  observer?.disconnect();
  const doc=frame.contentDocument;
  if(!doc?.body)return;
  observer=new MutationObserver(schedule);
  observer.observe(doc.body,{subtree:true,childList:true,attributes:true,attributeFilter:['style','class','aria-expanded']});
  doc.defaultView?.addEventListener('scroll',schedule,{passive:true});
  doc.defaultView?.addEventListener('resize',schedule,{passive:true});
  schedule();
}
frame.addEventListener('load',()=>setTimeout(bind,150));
if(frame.contentDocument?.readyState==='complete')setTimeout(bind,150);
})();
