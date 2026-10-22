(()=>{
'use strict';
const service=()=>window.CmsEditorDraft;
let timer=null;
function stop(){if(timer!==null){clearTimeout(timer);timer=null}}
function flush(){
  stop();
  if(!service()?.isDirty())return;
  service().save().catch(()=>{/* The editor keeps a persistent error and local content. */});
}
function schedule(){
  stop();
  if(service()?.isDirty())timer=setTimeout(flush,3200);
}
window.addEventListener('cms-editor:dirty',schedule);
document.addEventListener('visibilitychange',()=>{
  if(document.visibilityState==='hidden'&&service()?.isDirty())flush();
});
window.addEventListener('beforeunload',event=>{
  if(!service()?.isDirty())return;
  event.preventDefault();event.returnValue='';
});
})();
