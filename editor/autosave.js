(()=>{
'use strict';
const save=document.querySelector('#save-page');
const status=document.querySelector('#save-status');
if(!save||!status)return;

const DIRTY='não salvas';
let timer=null;
let saving=false;
let lastDirtyAt=0;

function isDirty(){return (status.textContent||'').toLowerCase().includes(DIRTY)}
function schedule(){
  if(!isDirty()){if(timer){clearTimeout(timer);timer=null}return;}
  lastDirtyAt=Date.now();
  if(timer)clearTimeout(timer);
  timer=setTimeout(flush,3200);
}
function flush(){
  timer=null;
  if(saving||!isDirty())return;
  // Avoid racing a change that happened while the debounce timer was firing.
  const idleFor=Date.now()-lastDirtyAt;
  if(idleFor<2800){timer=setTimeout(flush,3200-idleFor);return;}
  saving=true;
  save.click();
  const release=()=>{saving=false;if(isDirty())schedule()};
  setTimeout(release,1800);
}

const observer=new MutationObserver(schedule);
observer.observe(status,{childList:true,characterData:true,subtree:true});

document.addEventListener('visibilitychange',()=>{
  if(document.visibilityState==='hidden'&&isDirty()&&!saving){saving=true;save.click();setTimeout(()=>saving=false,1200)}
});

window.addEventListener('beforeunload',event=>{
  if(!isDirty())return;
  event.preventDefault();
  event.returnValue='';
});
})();
