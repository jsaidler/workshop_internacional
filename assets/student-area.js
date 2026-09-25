(()=>{
'use strict';
const root=document.documentElement;
const themeButtons=[...document.querySelectorAll('[data-theme-value]')];
function applyTheme(theme){
  if(theme==='auto')root.removeAttribute('data-theme');else root.setAttribute('data-theme',theme);
  themeButtons.forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.themeValue===theme)));
  try{localStorage.setItem('workshop-theme',theme)}catch{}
}
let theme='auto';
try{const stored=localStorage.getItem('workshop-theme');if(['auto','light','dark'].includes(stored))theme=stored}catch{}
themeButtons.forEach(button=>button.addEventListener('click',()=>applyTheme(button.dataset.themeValue||'auto')));
applyTheme(theme);

document.querySelectorAll('input[type="file"][data-student-auto-upload]').forEach(input=>{
  input.addEventListener('change',()=>{
    if(!input.files?.length)return;
    const form=input.closest('form');
    const label=input.closest('label');
    if(label){label.classList.add('is-uploading');const text=label.querySelector('[data-upload-label]');if(text)text.textContent='Enviando…';}
    form?.requestSubmit();
  });
});
})();
