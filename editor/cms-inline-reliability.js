(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
if(!frame)return;
let boundDoc=null,observer=null,raf=0;
function positionPalette(){
  raf=0;
  const doc=frame.contentDocument;
  if(!doc||doc!==boundDoc)return;
  const palette=doc.querySelector('.cms-inline-palette');
  const anchor=doc.querySelector('.cms-inline-add[aria-expanded="true"]');
  if(!palette||!anchor)return;
  const win=doc.defaultView;
  if(!win)return;
  const a=anchor.getBoundingClientRect(),p=palette.getBoundingClientRect();
  const vw=win.innerWidth||frame.clientWidth,vh=win.innerHeight||frame.clientHeight;
  let left=a.left+a.width/2-p.width/2;
  left=Math.max(8,Math.min(left,Math.max(8,vw-p.width-8)));
  let top=a.bottom+6;
  if(top+p.height>vh-8)top=Math.max(8,a.top-p.height-6);
  top=Math.max(8,Math.min(top,Math.max(8,vh-p.height-8)));
  palette.style.left=`${Math.round(left)}px`;
  palette.style.top=`${Math.round(top)}px`;
}
function schedulePosition(){
  if(raf)return;
  const win=frame.contentWindow||window;
  raf=win.requestAnimationFrame(positionPalette);
}
function install(){
  const doc=frame.contentDocument;
  if(!doc?.body||doc===boundDoc)return;
  boundDoc=doc;
  const style=doc.createElement('style');
  style.dataset.cmsInlineReliability='1';
  style.textContent='.cms-inline-layer{z-index:2147483600!important}.cms-inline-palette{pointer-events:auto!important}.cms-inline-palette button{pointer-events:auto!important;position:relative;z-index:1}';
  doc.head.append(style);
  observer?.disconnect();
  observer=new MutationObserver(schedulePosition);
  observer.observe(doc.body,{subtree:true,childList:true});
  doc.addEventListener('pointerdown',event=>{
    const button=event.target?.closest?.('.cms-inline-palette button[data-inline-type],.cms-inline-palette button[data-rich-inline-type]');
    if(!button||button.disabled)return;
    event.preventDefault();
    event.stopImmediatePropagation();
    button.click();
  },true);
  doc.addEventListener('scroll',schedulePosition,true);
  doc.defaultView?.addEventListener('resize',schedulePosition,{passive:true});
  schedulePosition();
}
function installWhenReady(){if(frame.contentDocument?.body)install();else setTimeout(installWhenReady,60)}
frame.addEventListener('load',()=>setTimeout(installWhenReady,0));
installWhenReady();
})();
