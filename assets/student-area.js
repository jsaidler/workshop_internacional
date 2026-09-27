(()=>{
'use strict';

const selector='input:not([type="hidden"]),select,textarea';

function fieldHost(control){
  return control.closest('.student-field,.student-choice-field,.student-check-field')||control.parentElement;
}
function errorId(control){
  if(control.id)return `${control.id}-error`;
  const safe=(control.name||'field').replace(/[^a-z0-9_-]+/gi,'-');
  const formIndex=[...document.querySelectorAll('form[data-student-validate]')].indexOf(control.form);
  return `student-${safe}-${Math.max(0,formIndex)}-error`;
}
function validationMessage(control){
  const validity=control.validity;
  if(validity.customError&&control.validationMessage)return control.validationMessage;
  if(validity.valueMissing)return control.type==='checkbox'?'Confirme esta opção para continuar.':'Preencha este campo para continuar.';
  if(validity.typeMismatch&&control.type==='email')return 'Informe um e-mail válido.';
  if(validity.tooShort)return `Use pelo menos ${control.minLength} caracteres.`;
  if(validity.tooLong)return `Use no máximo ${control.maxLength} caracteres.`;
  if(validity.patternMismatch&&control.name==='cpf')return 'Informe um CPF com 11 dígitos.';
  if(validity.patternMismatch)return 'Revise o formato deste campo.';
  if(validity.rangeUnderflow)return `Use um valor igual ou maior que ${control.min}.`;
  if(validity.rangeOverflow)return `Use um valor igual ou menor que ${control.max}.`;
  return 'Revise este campo antes de continuar.';
}
function removeError(control){
  const host=fieldHost(control);
  if(!host)return;
  control.removeAttribute('aria-invalid');
  const id=errorId(control);
  const error=host.querySelector(`#${CSS.escape(id)}`);
  if(error)error.remove();
  host.classList.remove('has-error');
  const described=(control.getAttribute('aria-describedby')||'').split(/\s+/).filter(Boolean).filter(value=>value!==id);
  if(described.length)control.setAttribute('aria-describedby',described.join(' '));else control.removeAttribute('aria-describedby');
}
function showError(control){
  const host=fieldHost(control);
  if(!host)return;
  const id=errorId(control);
  let error=host.querySelector(`#${CSS.escape(id)}`);
  if(!error){
    error=document.createElement('span');
    error.className='student-field-error';
    error.id=id;
    error.setAttribute('aria-live','polite');
    host.append(error);
  }
  error.textContent=validationMessage(control);
  control.setAttribute('aria-invalid','true');
  host.classList.add('has-error');
  const described=new Set((control.getAttribute('aria-describedby')||'').split(/\s+/).filter(Boolean));
  described.add(id);
  control.setAttribute('aria-describedby',[...described].join(' '));
}
function syncMatch(control){
  const targetSelector=control.dataset.match;
  if(!targetSelector)return;
  const target=document.querySelector(targetSelector);
  if(!target)return;
  control.setCustomValidity(control.value!==target.value?'As senhas não conferem.':'');
}
function refreshControl(control){
  syncMatch(control);
  if(control.checkValidity())removeError(control);
  else if(control.getAttribute('aria-invalid')==='true')showError(control);
}
function validateForm(form){
  const controls=[...form.querySelectorAll(selector)].filter(control=>!control.disabled);
  controls.forEach(syncMatch);
  const valid=form.checkValidity();
  controls.forEach(control=>control.checkValidity()?removeError(control):showError(control));
  if(!valid){
    const first=controls.find(control=>!control.checkValidity());
    if(first){
      first.focus({preventScroll:true});
      first.scrollIntoView({behavior:'smooth',block:'center'});
    }
  }
  return valid;
}

document.querySelectorAll('form[data-student-validate]').forEach(form=>{
  form.noValidate=true;
  const controls=[...form.querySelectorAll(selector)];
  controls.forEach(control=>{
    control.addEventListener('invalid',event=>{event.preventDefault();showError(control)});
    control.addEventListener('input',()=>refreshControl(control));
    control.addEventListener('change',()=>refreshControl(control));
  });
  form.addEventListener('submit',event=>{
    if(!validateForm(form))event.preventDefault();
  });
});
})();
