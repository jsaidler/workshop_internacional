(()=>{
'use strict';

if(!document.querySelector('link[data-cms-responsive]')){
  const responsive=document.createElement('link');
  responsive.rel='stylesheet';
  responsive.href='/assets/cms-responsive.css';
  responsive.dataset.cmsResponsive='1';
  document.head.append(responsive);
}
if(document.querySelector('.registration-page,.registration-canonical-form')&&!document.querySelector('link[data-registration-css]')){
  const registration=document.createElement('link');
  registration.rel='stylesheet';
  registration.href='/assets/registration.css';
  registration.dataset.registrationCss='1';
  document.head.append(registration);
}

const root=document.documentElement;
const themeButtons=[...document.querySelectorAll('[data-theme-value]')];
function applyTheme(theme){
  if(theme==='auto')root.removeAttribute('data-theme');
  else root.setAttribute('data-theme',theme);
  themeButtons.forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.themeValue===theme)));
  try{localStorage.setItem('workshop-theme',theme)}catch{}
}
let initialTheme=root.getAttribute('data-theme')||'auto';
try{const stored=localStorage.getItem('workshop-theme');if(stored&&['auto','light','dark'].includes(stored))initialTheme=stored}catch{}
themeButtons.forEach(button=>button.addEventListener('click',()=>applyTheme(button.dataset.themeValue)));
applyTheme(initialTheme);

const header=document.querySelector('[data-cms-public-header]');
const navToggle=header?.querySelector('.cms-nav-toggle');
function setNav(open){
  if(!header||!navToggle)return;
  header.classList.toggle('cms-nav-open',open);
  navToggle.setAttribute('aria-expanded',String(open));
}
navToggle?.addEventListener('click',()=>setNav(!header.classList.contains('cms-nav-open')));
header?.querySelectorAll('nav a').forEach(link=>link.addEventListener('click',()=>setNav(false)));
matchMedia('(min-width:981px)').addEventListener?.('change',event=>{if(event.matches)setNav(false)});

const zone=Intl.DateTimeFormat().resolvedOptions().timeZone;
document.querySelectorAll('select[data-timezone-select]').forEach(select=>{
  if(!select.value&&[...select.options].some(option=>option.value===zone))select.value=zone;
});

document.addEventListener('click',event=>{
  const link=event.target.closest('a[href^="#"]');
  if(!link)return;
  const id=link.getAttribute('href').slice(1);
  const target=document.getElementById(id);
  if(!target)return;
  event.preventDefault();
  history.replaceState({},'',`#${id}`);
  target.scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'start'});
});

function fieldControls(form,name){
  return [...form.querySelectorAll(`[name="${CSS.escape(name)}"],[name="${CSS.escape(name)}[]"]`)];
}
function conditionValues(form,name){
  const controls=fieldControls(form,name).filter(control=>!control.disabled);
  if(!controls.length)return [];
  const values=[];
  for(const control of controls){
    if((control.type==='checkbox'||control.type==='radio')&&!control.checked)continue;
    if(control.tagName==='SELECT'&&control.multiple){
      [...control.selectedOptions].forEach(option=>values.push(option.value));
      continue;
    }
    values.push(control.value??'');
  }
  return values;
}
function conditionMatch(wrapper,form){
  const source=wrapper.dataset.cmsConditionField||'';
  const operator=wrapper.dataset.cmsConditionOperator||'equals';
  const expected=wrapper.dataset.cmsConditionValue||'';
  const values=conditionValues(form,source);
  const nonEmpty=values.filter(value=>value!=='');
  if(operator==='checked')return nonEmpty.length>0&&!((nonEmpty.length===1)&&nonEmpty[0]==='0');
  if(operator==='not_checked')return nonEmpty.length===0||((nonEmpty.length===1)&&nonEmpty[0]==='0');
  if(operator==='not_equals')return !values.includes(expected);
  if(operator==='contains')return values.some(value=>String(value).includes(expected));
  return values.includes(expected);
}
function applyConditions(form){
  if(form.dataset.cmsFormPreview==='1')return;
  const wrappers=[...form.querySelectorAll('[data-cms-condition-field]')];
  for(let pass=0;pass<Math.max(1,wrappers.length);pass++){
    let changed=false;
    for(const wrapper of wrappers){
      const visible=conditionMatch(wrapper,form),wasVisible=!wrapper.hidden;
      if(visible!==wasVisible)changed=true;
      wrapper.hidden=!visible;
      wrapper.querySelectorAll('input,select,textarea').forEach(control=>{
        control.disabled=!visible;
        if(!visible)control.removeAttribute('aria-invalid');
      });
    }
    if(!changed)break;
  }
}
function annotateConditions(form,conditions){
  for(const [target,rule] of Object.entries(conditions||{})){
    const control=fieldControls(form,target)[0];
    if(!control||!rule)continue;
    const wrapper=control.closest('.cms-field,.cms-choice-group,.cms-consent');
    if(!wrapper)continue;
    wrapper.dataset.cmsConditionField=String(rule.source||'');
    wrapper.dataset.cmsConditionOperator=String(rule.operator||'equals');
    wrapper.dataset.cmsConditionValue=String(rule.value||'');
  }
}
async function loadConditions(form){
  if(form.dataset.cmsFormPreview==='1')return;
  const uuid=form.querySelector('input[name="_form_uuid"]')?.value;
  if(!uuid){applyConditions(form);return;}
  try{
    const response=await fetch(`/form-config.php?uuid=${encodeURIComponent(uuid)}`,{credentials:'same-origin',headers:{Accept:'application/json'}});
    if(response.ok){
      const data=await response.json();
      annotateConditions(form,data.conditions);
    }
  }catch{}
  applyConditions(form);
}

async function copyText(value){
  if(!value)return false;
  try{await navigator.clipboard.writeText(value);return true}catch{}
  const area=document.createElement('textarea');
  area.value=value;
  area.setAttribute('readonly','');
  area.style.position='fixed';
  area.style.opacity='0';
  document.body.append(area);
  area.select();
  let copied=false;
  try{copied=document.execCommand('copy')}catch{}
  area.remove();
  return copied;
}
document.addEventListener('click',async event=>{
  const button=event.target.closest('[data-copy-pix]');
  if(!button)return;
  const scope=button.closest('[data-cms-form-content-id],.registration-payment-panel,.cms-form')||document;
  const code=scope.querySelector('[data-pix-copy-value]')?.textContent?.trim()||'';
  if(!code)return;
  event.preventDefault();
  if(await copyText(code)){
    const original=button.textContent;
    button.textContent='Código Pix copiado';
    setTimeout(()=>button.textContent=original,1800);
  }
});

document.querySelectorAll('.cms-form').forEach(form=>{
  if(fieldControls(form,'payment_method').length&&fieldControls(form,'support_size').length)form.classList.add('registration-canonical-form');
  form.addEventListener('invalid',event=>event.target.setAttribute('aria-invalid','true'),true);
  form.addEventListener('input',event=>{
    event.target.removeAttribute?.('aria-invalid');
    applyConditions(form);
  });
  form.addEventListener('change',()=>applyConditions(form));
  loadConditions(form);
});
})();
