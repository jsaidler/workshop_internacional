(()=>{
'use strict';
const qsa=(selector,root=document)=>[...root.querySelectorAll(selector)];
const replaceLabelText=(control,text)=>{const label=control?.closest('.form-field');if(!label)return;const node=[...label.childNodes].find(n=>n.nodeType===Node.TEXT_NODE&&n.textContent.trim());if(node)node.textContent=text;};
const addFieldHelp=(control,text,key)=>{const label=control?.closest('.form-field');if(!label||label.querySelector(`[data-field-help="${key}"]`))return;const help=document.createElement('span');help.className='form-field-help';help.dataset.fieldHelp=key;help.textContent=text;label.appendChild(help);};
const groupDeveloperSelect=select=>{if(!select||select.dataset.developerGrouped==='1')return;const options=[...select.options];if(!options.length)return;const primary=document.createElement('optgroup'),common=document.createElement('optgroup'),custom=document.createElement('optgroup');primary.label='Pesquisa / curso';common.label='Outros reveladores';custom.label='Personalizado';options.forEach(option=>{if(['parodinal','brewed-caffenol'].includes(option.value))primary.appendChild(option);else if(option.value==='other')custom.appendChild(option);else common.appendChild(option);});select.replaceChildren();if(primary.children.length)select.appendChild(primary);if(common.children.length)select.appendChild(common);if(custom.children.length)select.appendChild(custom);select.dataset.developerGrouped='1';};
qsa('[data-developer-select]').forEach(groupDeveloperSelect);
qsa('[data-custom-developer] input[name="developer_name"]').forEach(input=>{replaceLabelText(input,'Outro revelador');addFieldHelp(input,'Preencha somente quando “Outro” estiver selecionado.','other-developer');input.placeholder='Nome do revelador';});

const washKeys=new Set(['wash_after_first','wash_after_bleach','wash_after_ammonia','wash_after_clearing','final_wash']);
const chemicalKeys=new Set(['stop_after_first','fixer','peracetic','ferric','dichromate','permanganate','ammonia','clearing']);
const stageKind=key=>key==='dry'?'dry':washKeys.has(key)?'wash':chemicalKeys.has(key)?'chemical':['first_development','second_development'].includes(key)?'development':key==='custom'?'custom':'other';
const setFieldVisible=(control,visible)=>{if(!control)return;const field=control.closest('.form-field');if(field)field.hidden=!visible;control.hidden=!visible;control.disabled=!visible;};
const bindInventoryAmount=(select,amount,allowed=true)=>{if(!amount)return;const sync=()=>{const visible=Boolean(allowed&&select&&select.value);setFieldVisible(amount,visible);};select?.addEventListener('change',sync);sync();return sync;};

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
  const primary=form.querySelector('.student-sticky-action .button-primary');if(primary&&primary.textContent.includes('Registrar etapa'))primary.textContent='Adicionar ao processo →';
  const preset=form.querySelector('select[name="saved_preparation_id"]');if(preset)replaceLabelText(preset,'Predefinição de revelação');
  const fieldset=form.querySelector('.choice-field');
  const radios=qsa('[data-stage-choice]',form);
  const simple=form.querySelector('[data-stage-fields="simple"]');
  const simpleDuration=simple?.querySelector('input[name="duration_simple"]');
  const simpleInventory=simple?.querySelector('select[name="inventory_item_id_simple"]');
  const simpleAmount=simple?.querySelector('input[name="inventory_amount_simple"]');
  const timer=form.querySelector('[data-lab-timer]');
  const syncStageFields=()=>{
    const key=radios.find(item=>item.checked)?.value||'';const kind=stageKind(key);
    if(simpleDuration){replaceLabelText(simpleDuration,kind==='wash'?'Tempo de lavagem':kind==='dry'?'Tempo de secagem (opcional)':'Tempo');}
    const inventoryAllowed=kind==='chemical';
    setFieldVisible(simpleInventory,inventoryAllowed&&Boolean(simpleInventory));
    if(simpleAmount){const amountVisible=inventoryAllowed&&Boolean(simpleInventory?.value);setFieldVisible(simpleAmount,amountVisible);}
    if(timer)timer.hidden=kind==='dry';
  };
  simpleInventory?.addEventListener('change',syncStageFields);radios.forEach(radio=>radio.addEventListener('change',()=>setTimeout(syncStageFields,0)));syncStageFields();
  const devInventory=form.querySelector('select[name="inventory_item_id"]');const devAmount=form.querySelector('input[name="inventory_amount"]');bindInventoryAmount(devInventory,devAmount,true);

  if(fieldset&&radios.length){
    const labelFor=radio=>radio.closest('label')?.querySelector('span')?.textContent.trim()||radio.value;
    const customIndex=radios.findIndex(radio=>radio.value==='custom');
    const legacySuggested=(customIndex>=0?radios.slice(0,customIndex+1):radios.slice(0,1));
    const suggested=legacySuggested.filter(radio=>radio.value!=='custom');
    const suggestedValues=new Set(suggested.map(radio=>radio.value));
    const alternatives=radios.filter(radio=>!suggestedValues.has(radio.value));
    const decision=document.createElement('div');decision.className='student-process-decision';
    const headline=now?.querySelector('h2'),nowLabel=now?.querySelector('.student-process-now-label');
    const activate=radio=>{radios.forEach(item=>{item.checked=item===radio;});radio.dispatchEvent(new Event('change',{bubbles:true}));if(primary)primary.disabled=false;};
    let choices=null,other=null,otherSummary=null,otherSelect=null;
    const resetAlternative=()=>{decision.classList.remove('has-alternative');if(choices)choices.hidden=false;if(otherSummary)otherSummary.textContent='Registrar outra etapa';if(otherSelect)otherSelect.value='';};
    if(suggested.length===1){if(nowLabel)nowLabel.textContent='Próxima etapa sugerida';if(headline)headline.textContent=labelFor(suggested[0]);decision.classList.add('is-secondary-only');}
    else if(suggested.length>1){
      radios.forEach(item=>{item.checked=false;});suggested[0].dispatchEvent(new Event('change',{bubbles:true}));if(primary)primary.disabled=true;
      if(nowLabel)nowLabel.textContent='Próxima decisão';if(headline)headline.textContent='Escolha como continuar';
      choices=document.createElement('div');choices.className='student-process-guided-choices';
      suggested.forEach(radio=>{const button=document.createElement('button');button.type='button';button.className='student-process-choice-button';button.textContent=labelFor(radio);button.setAttribute('aria-pressed','false');button.addEventListener('click',()=>{resetAlternative();activate(radio);choices.querySelectorAll('button').forEach(item=>item.setAttribute('aria-pressed',item===button?'true':'false'));if(headline)headline.textContent=labelFor(radio);if(nowLabel)nowLabel.textContent='Etapa escolhida';});choices.appendChild(button);});decision.appendChild(choices);
    }
    if(alternatives.length){other=document.createElement('details');other.className='student-process-other-stage';otherSummary=document.createElement('summary');otherSummary.textContent='Registrar outra etapa';otherSelect=document.createElement('select');otherSelect.setAttribute('aria-label','Outra etapa');const placeholder=document.createElement('option');placeholder.value='';placeholder.textContent='Escolha…';otherSelect.appendChild(placeholder);alternatives.forEach(radio=>{const option=document.createElement('option');option.value=radio.value;option.textContent=labelFor(radio);otherSelect.appendChild(option);});otherSelect.addEventListener('change',()=>{if(!otherSelect.value)return;const radio=radios.find(item=>item.value===otherSelect.value);if(!radio)return;activate(radio);if(headline)headline.textContent=labelFor(radio);if(nowLabel)nowLabel.textContent='Etapa escolhida';other.open=false;otherSummary.textContent='Alterar etapa';decision.classList.add('has-alternative');if(choices)choices.hidden=true;decision.querySelectorAll('.student-process-choice-button').forEach(item=>item.setAttribute('aria-pressed','false'));});other.append(otherSummary,otherSelect);decision.appendChild(other);}
    if(decision.childElementCount)fieldset.insertAdjacentElement('beforebegin',decision);fieldset.hidden=true;
  }
}

const editForm=document.querySelector('.student-process-edit-form');
if(editForm){
  const preset=editForm.querySelector('select[name="saved_preparation_id"]');if(preset)replaceLabelText(preset,'Predefinição de revelação');
  const developer=editForm.querySelector('[data-developer-select]'),inventory=editForm.querySelector('select[name="inventory_item_id"]'),amount=editForm.querySelector('input[name="inventory_amount"]');
  const isDevelopment=editForm.classList.contains('student-developer-edit-form');
  const fixedLabel=editForm.querySelector('.student-step-fixed strong')?.textContent.trim()||'';
  const kind=isDevelopment?'development':editForm.querySelector('input[name="custom_label"]')?'custom':fixedLabel==='Secagem'?'dry':fixedLabel.startsWith('Lavagem')?'wash':'chemical';
  const duration=editForm.querySelector('input[name="duration"]'),temperature=editForm.querySelector('input[name="temperature"]'),agitation=editForm.querySelector('input[name="agitation"]');
  if(kind==='dry'){
    if(duration)replaceLabelText(duration,'Tempo de secagem (opcional)');
    setFieldVisible(temperature,false);setFieldVisible(agitation,false);setFieldVisible(inventory,false);setFieldVisible(amount,false);
  }else if(kind==='wash'){
    if(duration)replaceLabelText(duration,'Tempo de lavagem');
    setFieldVisible(inventory,false);setFieldVisible(amount,false);
  }else{
    const syncInventory=()=>{const fresh=isDevelopment&&developer?.selectedOptions[0]?.dataset.preparationMode==='fresh';setFieldVisible(inventory,!fresh&&Boolean(inventory));setFieldVisible(amount,!fresh&&Boolean(inventory?.value));};
    developer?.addEventListener('change',syncInventory);inventory?.addEventListener('change',syncInventory);syncInventory();
  }
}
})();
