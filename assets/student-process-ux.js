(()=>{
'use strict';
const replaceLabelText=(control,text)=>{const label=control?.closest('.form-field');if(!label)return;const node=[...label.childNodes].find(n=>n.nodeType===Node.TEXT_NODE&&n.textContent.trim());if(node)node.textContent=text;};
const groupDeveloperSelect=select=>{if(!select||select.dataset.developerGrouped==='1')return;const options=[...select.options];if(!options.length)return;const primary=document.createElement('optgroup'),common=document.createElement('optgroup'),custom=document.createElement('optgroup');primary.label='Pesquisa / curso';common.label='Outros reveladores';custom.label='Personalizado';options.forEach(option=>{if(['parodinal','brewed-caffenol'].includes(option.value))primary.appendChild(option);else if(option.value==='other')custom.appendChild(option);else common.appendChild(option);});select.replaceChildren();if(primary.children.length)select.appendChild(primary);if(common.children.length)select.appendChild(common);if(custom.children.length)select.appendChild(custom);select.dataset.developerGrouped='1';};
document.querySelectorAll('[data-developer-select]').forEach(groupDeveloperSelect);
const params=new URLSearchParams(window.location.search),testId=params.get('id');
const history=document.querySelector('.student-process-history');
const now=document.querySelector('.student-process-now');
const processPanel=history?.closest('.student-workflow-panel')||document.querySelector('.student-workflow-panel');
const processLocked=Boolean(document.querySelector('.student-status-reviewed'));
if(history){
  history.open=true;
  history.classList.add('student-process-log');
  const summary=history.querySelector(':scope>summary');
  if(summary)summary.textContent=summary.textContent.replace('Histórico do processamento','Processo registrado');
  if(processPanel){const anchor=now||processPanel.querySelector('.student-process-complete');if(anchor)processPanel.insertBefore(history,anchor);}
  if(!processLocked)history.querySelectorAll('.student-process-summary>li').forEach(item=>{
    if(item.querySelector('.student-process-step-edit'))return;
    const raw=item.querySelector('.student-process-step-number')?.textContent.trim()||'';
    const position=String(parseInt(raw,10)||'');if(!position||!testId)return;
    const edit=document.createElement('a');edit.className='student-process-step-edit';edit.href=`/aluno/teste-etapa.php?registro=${encodeURIComponent(testId)}&etapa=${encodeURIComponent(position)}`;edit.textContent='Editar';edit.setAttribute('aria-label',`Editar etapa ${position}`);item.appendChild(edit);
  });
}

const form=document.querySelector('.student-process-step-form');
if(form){
  const preset=form.querySelector('select[name="saved_preparation_id"]');if(preset)replaceLabelText(preset,'Predefinição de revelação');
  const fieldset=form.querySelector('.choice-field');
  const radios=[...form.querySelectorAll('[data-stage-choice]')];
  if(fieldset&&radios.length){
    const labelFor=radio=>radio.closest('label')?.querySelector('span')?.textContent.trim()||radio.value;
    const customIndex=radios.findIndex(radio=>radio.value==='custom');
    const legacySuggested=(customIndex>=0?radios.slice(0,customIndex+1):radios.slice(0,1));
    const suggested=legacySuggested.filter(radio=>radio.value!=='custom');
    const suggestedValues=new Set(suggested.map(radio=>radio.value));
    const alternatives=radios.filter(radio=>!suggestedValues.has(radio.value));
    const decision=document.createElement('div');decision.className='student-process-decision';
    const heading=document.createElement('p');heading.className='student-process-decision-label';heading.textContent=suggested.length>1?'Próxima decisão':'Etapa sugerida';decision.appendChild(heading);
    if(suggested.length===1){const fixed=document.createElement('strong');fixed.className='student-process-suggested-stage';fixed.textContent=labelFor(suggested[0]);decision.appendChild(fixed);}
    else if(suggested.length>1){const choices=document.createElement('div');choices.className='student-process-guided-choices';suggested.forEach(radio=>{const button=document.createElement('button');button.type='button';button.className='student-process-choice-button';button.textContent=labelFor(radio);button.setAttribute('aria-pressed',radio.checked?'true':'false');button.addEventListener('click',()=>{suggested.forEach(item=>{item.checked=item===radio;});radio.dispatchEvent(new Event('change',{bubbles:true}));choices.querySelectorAll('button').forEach(item=>item.setAttribute('aria-pressed',item===button?'true':'false'));const headline=now?.querySelector('h2');if(headline)headline.textContent=labelFor(radio);});choices.appendChild(button);});decision.appendChild(choices);}
    if(alternatives.length){const other=document.createElement('details');other.className='student-process-other-stage';const summary=document.createElement('summary');summary.textContent='Registrar outra etapa';const select=document.createElement('select');select.setAttribute('aria-label','Outra etapa');const placeholder=document.createElement('option');placeholder.value='';placeholder.textContent='Escolha…';select.appendChild(placeholder);alternatives.forEach(radio=>{const option=document.createElement('option');option.value=radio.value;option.textContent=labelFor(radio);select.appendChild(option);});select.addEventListener('change',()=>{if(!select.value)return;const radio=radios.find(item=>item.value===select.value);if(!radio)return;radios.forEach(item=>{item.checked=item===radio;});radio.dispatchEvent(new Event('change',{bubbles:true}));const headline=now?.querySelector('h2');if(headline)headline.textContent=labelFor(radio);other.open=false;summary.textContent='Outra etapa: '+labelFor(radio);decision.classList.add('has-alternative');});other.append(summary,select);decision.appendChild(other);}
    fieldset.insertAdjacentElement('beforebegin',decision);fieldset.hidden=true;
  }

  const nowLabel=now?.querySelector('.student-process-now-label');if(nowLabel)nowLabel.textContent='Sugestão para continuar';
  const primary=form.querySelector('.student-sticky-action .button-primary');if(primary&&primary.textContent.includes('Registrar etapa'))primary.textContent='Adicionar ao processo →';
}

const editForm=document.querySelector('.student-developer-edit-form');
if(editForm){
  const preset=editForm.querySelector('select[name="saved_preparation_id"]');if(preset)replaceLabelText(preset,'Predefinição de revelação');
  const developer=editForm.querySelector('[data-developer-select]'),inventory=editForm.querySelector('select[name="inventory_item_id"]'),amount=editForm.querySelector('input[name="inventory_amount"]');
  const syncInventory=()=>{const fresh=developer?.selectedOptions[0]?.dataset.preparationMode==='fresh';[inventory,amount].forEach(control=>{if(!control)return;control.disabled=fresh;control.closest('.form-field')?.toggleAttribute('hidden',fresh);});};
  developer?.addEventListener('change',syncInventory);syncInventory();
}
})();
