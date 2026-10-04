(()=>{
'use strict';

const root=document.documentElement;
const embedded=new URLSearchParams(location.search).has('editor')||new URLSearchParams(location.search).has('preview');

function applyTheme(theme){
  if(theme==='auto')root.removeAttribute('data-theme');
  else root.setAttribute('data-theme',theme);
  document.querySelectorAll('[data-theme-value]').forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.themeValue===theme)));
  if(!embedded){try{localStorage.setItem('workshop-theme',theme)}catch{}}
}

function initTheme(){
  let theme=root.getAttribute('data-theme')||'auto';
  if(!embedded){try{const stored=localStorage.getItem('workshop-theme');if(stored&&['auto','light','dark'].includes(stored))theme=stored}catch{}}
  document.querySelectorAll('[data-theme-value]').forEach(button=>button.addEventListener('click',()=>applyTheme(button.dataset.themeValue||'auto')));
  applyTheme(theme);
}

const controlSelector='input:not([type="hidden"]),select,textarea';
function fieldHost(control){return control.closest('.form-field,.choice-field,.check-field')||control.parentElement}
function errorId(control){
  if(control.id)return `${control.id}-error`;
  const safe=(control.name||'field').replace(/[^a-z0-9_-]+/gi,'-');
  const forms=[...document.querySelectorAll('form[data-ui-validate]')];
  return `ui-${safe}-${Math.max(0,forms.indexOf(control.form))}-error`;
}
function validationMessage(control){
  const validity=control.validity;
  if(validity.customError&&control.validationMessage)return control.validationMessage;
  if(validity.valueMissing)return control.type==='checkbox'?'Confirme esta opção para continuar.':'Preencha este campo para continuar.';
  if(validity.typeMismatch&&control.type==='email')return 'Informe um e-mail válido.';
  if(validity.tooShort)return `Use pelo menos ${control.minLength} caracteres.`;
  if(validity.tooLong)return `Use no máximo ${control.maxLength} caracteres.`;
  if(validity.patternMismatch&&control.name==='cpf')return 'Informe um CPF válido.';
  if(validity.patternMismatch)return 'Revise o formato deste campo.';
  if(validity.rangeUnderflow)return `Use um valor igual ou maior que ${control.min}.`;
  if(validity.rangeOverflow)return `Use um valor igual ou menor que ${control.max}.`;
  return 'Revise este campo antes de continuar.';
}
function clearError(control){
  const host=fieldHost(control);if(!host)return;
  const id=errorId(control);control.removeAttribute('aria-invalid');host.classList.remove('has-error');
  host.querySelector(`#${CSS.escape(id)}`)?.remove();
  const described=(control.getAttribute('aria-describedby')||'').split(/\s+/).filter(Boolean).filter(value=>value!==id);
  if(described.length)control.setAttribute('aria-describedby',described.join(' '));else control.removeAttribute('aria-describedby');
}
function showError(control){
  const host=fieldHost(control);if(!host)return;
  const id=errorId(control);let error=host.querySelector(`#${CSS.escape(id)}`);
  if(!error){error=document.createElement('span');error.id=id;error.className='form-field-error';error.setAttribute('aria-live','polite');host.append(error)}
  error.textContent=validationMessage(control);control.setAttribute('aria-invalid','true');host.classList.add('has-error');
  const described=new Set((control.getAttribute('aria-describedby')||'').split(/\s+/).filter(Boolean));described.add(id);control.setAttribute('aria-describedby',[...described].join(' '));
}
function syncMatch(control){
  const selector=control.dataset.uiMatch;if(!selector)return;
  const target=document.querySelector(selector);if(!target)return;
  control.setCustomValidity(control.value!==target.value?'As senhas não conferem.':'');
}
function refreshControl(control){syncMatch(control);if(control.checkValidity())clearError(control);else if(control.getAttribute('aria-invalid')==='true')showError(control)}
function validateForm(form){
  const controls=[...form.querySelectorAll(controlSelector)].filter(control=>!control.disabled);controls.forEach(syncMatch);
  const valid=form.checkValidity();controls.forEach(control=>control.checkValidity()?clearError(control):showError(control));
  if(!valid){const first=controls.find(control=>!control.checkValidity());if(first){first.focus({preventScroll:true});first.scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'center'})}}
  return valid;
}
function initValidation(){
  document.querySelectorAll('form[data-ui-validate]').forEach(form=>{
    form.noValidate=true;
    form.querySelectorAll(controlSelector).forEach(control=>{
      control.addEventListener('invalid',event=>{event.preventDefault();showError(control)});
      control.addEventListener('input',()=>refreshControl(control));
      control.addEventListener('change',()=>refreshControl(control));
    });
    form.addEventListener('submit',event=>{if(!validateForm(form))event.preventDefault()});
  });
}

initTheme();
initValidation();
window.WorkshopUI={applyTheme,validateForm};
})();
