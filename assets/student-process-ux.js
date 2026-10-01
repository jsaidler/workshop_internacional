(()=>{
'use strict';
const form=document.querySelector('.student-process-step-form');
if(form){
  const params=new URLSearchParams(window.location.search),testId=params.get('id');
  const history=document.querySelector('.student-process-history');
  const now=document.querySelector('.student-process-now');
  const section=form.closest('.student-workflow-panel');
  if(history){
    history.open=true;
    history.classList.add('student-process-log');
    const summary=history.querySelector(':scope>summary');
    if(summary)summary.textContent=summary.textContent.replace('Histórico do processamento','Processo registrado');
    if(section&&now)section.insertBefore(history,now);
    history.querySelectorAll('.student-process-summary>li').forEach(item=>{
      if(item.querySelector('.student-process-step-edit'))return;
      const raw=item.querySelector('.student-process-step-number')?.textContent.trim()||'';
      const position=String(parseInt(raw,10)||'');if(!position||!testId)return;
      const edit=document.createElement('a');edit.className='student-process-step-edit';edit.href=`/aluno/teste-etapa.php?registro=${encodeURIComponent(testId)}&etapa=${encodeURIComponent(position)}`;edit.textContent='Editar';edit.setAttribute('aria-label',`Editar etapa ${position}`);item.appendChild(edit);
    });
  }

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
})();
