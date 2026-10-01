(()=>{
'use strict';
const replaceLabelText=(control,text)=>{const label=control?.closest('.form-field');if(!label)return;const node=[...label.childNodes].find(n=>n.nodeType===Node.TEXT_NODE&&n.textContent.trim());if(node)node.textContent=text;};
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
    const chooser=document.createElement('label');chooser.className='form-field student-process-stage-select';chooser.appendChild(document.createTextNode('Adicionar etapa'));
    const select=document.createElement('select');select.setAttribute('aria-label','Etapa a adicionar');
    radios.forEach((radio,index)=>{
      const option=document.createElement('option');option.value=radio.value;const label=radio.closest('label')?.querySelector('span')?.textContent.trim()||radio.value;option.textContent=label+(index===0?' — sugestão':'');option.selected=radio.checked;select.appendChild(option);
    });
    const help=document.createElement('span');help.className='form-field-help';help.textContent='A sugestão acompanha o processo registrado, mas você pode escolher outra etapa.';
    chooser.append(select,help);fieldset.insertAdjacentElement('beforebegin',chooser);fieldset.hidden=true;
    select.addEventListener('change',()=>{const radio=radios.find(item=>item.value===select.value);if(!radio)return;radio.checked=true;radio.dispatchEvent(new Event('change',{bubbles:true}));const label=radio.closest('label')?.querySelector('span')?.textContent.trim();const headline=now?.querySelector('h2');if(headline&&label)headline.textContent=label;});
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
