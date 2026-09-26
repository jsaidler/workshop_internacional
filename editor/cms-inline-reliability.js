(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
if(!frame)return;
let boundDoc=null;
function install(){
  const doc=frame.contentDocument;
  if(!doc?.body||doc===boundDoc)return;
  boundDoc=doc;
  const style=doc.createElement('style');
  style.dataset.cmsInlineReliability='1';
  style.textContent='.cms-inline-layer{z-index:2147483600!important}.cms-inline-palette{pointer-events:auto!important}.cms-inline-palette button{pointer-events:auto!important;position:relative;z-index:1}';
  doc.head.append(style);
  doc.addEventListener('pointerdown',event=>{
    const button=event.target?.closest?.('.cms-inline-palette button[data-inline-type],.cms-inline-palette button[data-rich-inline-type]');
    if(!button||button.disabled)return;
    event.preventDefault();
    event.stopImmediatePropagation();
    button.click();
  },true);
}
function installWhenReady(){if(frame.contentDocument?.body)install();else setTimeout(installWhenReady,60)}
frame.addEventListener('load',()=>setTimeout(installWhenReady,0));
installWhenReady();
})();
