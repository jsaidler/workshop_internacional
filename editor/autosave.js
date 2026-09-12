(()=>{
'use strict';
const save=document.querySelector('#save-page'),status=document.querySelector('#save-status');if(!save||!status)return;
let saving=false;
setInterval(()=>{if(saving||!status.textContent.includes('não salvas'))return;saving=true;save.click();setTimeout(()=>saving=false,2500)},30000);
window.addEventListener('beforeunload',event=>{if(status.textContent.includes('não salvas')){event.preventDefault();event.returnValue='';}});
})();
