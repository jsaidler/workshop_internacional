(()=>{
'use strict';
const qs=(s,r=document)=>r.querySelector(s);
const developmentKeys=new Set(['first_development','second_development']);
function setGroupState(group,enabled){
  if(!group)return;
  group.hidden=!enabled;
  group.querySelectorAll('input,select,textarea').forEach(control=>control.disabled=!enabled);
}
function init(root=document){
  root.querySelectorAll('[data-process-template-step-form]').forEach(form=>{
    if(form.dataset.processManagerBound==='1')return;form.dataset.processManagerBound='1';
    const stage=qs('[data-process-template-stage]',form);
    const development=qs('[data-process-template-development]',form);
    const custom=qs('[data-process-template-custom]',form);
    const reuse=qs('[data-process-template-reuse]',form);
    if(!stage)return;
    const update=()=>{
      const key=stage.value;
      setGroupState(development,developmentKeys.has(key));
      setGroupState(custom,key==='custom');
      setGroupState(reuse,key==='second_development');
    };
    stage.addEventListener('change',update);update();
  });
}
init();
document.addEventListener('student:local-update',event=>init(event.detail?.root||document));
})();
