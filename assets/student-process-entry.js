(()=>{
'use strict';
// The Caderno now renders the saved-process choice and applied-plan state on the server.
// Keep this asset as a compatibility hook for installed clients that may still cache the shell.
const form=document.querySelector('[data-process-step-form]');
if(form)form.dataset.processEntry='server';
})();
