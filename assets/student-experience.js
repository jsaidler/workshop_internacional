(()=>{
'use strict';
const toolbox=document.querySelector('[data-student-toolbox]');
document.querySelectorAll('[data-toolbox-open]').forEach(button=>button.addEventListener('click',()=>{if(!toolbox)return;if(typeof toolbox.showModal==='function')toolbox.showModal();else toolbox.setAttribute('open','');}));
document.querySelectorAll('[data-toolbox-close]').forEach(button=>button.addEventListener('click',()=>{if(!toolbox)return;if(typeof toolbox.close==='function')toolbox.close();else toolbox.removeAttribute('open');}));
})();