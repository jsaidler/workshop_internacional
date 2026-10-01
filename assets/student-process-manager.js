(()=>{
'use strict';
const qs=(s,r=document)=>r.querySelector(s);
const developmentKeys=new Set(['first_development','second_development']);
function init(root=document){
  root.querySelectorAll('[data-process-template-step-form]').forEach(form=>{
    if(form.dataset.processManagerBound==='1')return;form.dataset.processManagerBound='1';
    const stage=qs('[data-process-template-stage]',form),development=qs('[data-process-template-development]',form),custom=qs('[data-process-template-custom]',form);
    if(!stage)return;
    const update=()=>{
      const key=stage.value;
      if(development){development.hidden=!developmentKeys.has(key);development.querySelectorAll('input,select,textarea').forEach(control=>control.disabled=!developmentKeys.has(key));}
      if(custom){custom.hidden=key!=='custom';custom.querySelectorAll('input,select,textarea').forEach(control=>control.disabled=key!=='custom');}
    };
    stage.addEventListener('change',update);update();
  });
}
init();
document.addEventListener('student:local-update',event=>init(event.detail?.root||document));
})();
