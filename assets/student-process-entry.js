(()=>{
'use strict';
const form=document.querySelector('[data-process-step-form]');
if(form)form.dataset.processEntry='server';

const params=new URLSearchParams(window.location.search);const testId=params.get('id');
if(!testId)return;
const recordedUrl=`/aluno/processamento-realizado.php?test=${encodeURIComponent(testId)}`;

const options=document.querySelector('.student-process-path-options');
if(options&&!options.querySelector('[data-recorded-process-path]')){
  const article=document.createElement('article');article.dataset.recordedProcessPath='1';
  article.innerHTML='<span>Também é válido</span><h4>Registrar um processamento já realizado</h4><p>Use se você já revelou a fotografia fora do sistema e quer apenas documentar o que realmente fez, sem executar os cronômetros novamente.</p><a class="button button-secondary" href="'+recordedUrl+'">Registrar o que já foi feito</a>';
  options.appendChild(article);
}

const planCard=document.querySelector('.student-caderno-plan-card');
if(planCard&&!planCard.querySelector('[data-complete-recorded-process]')){
  const actions=planCard.querySelector('.student-actions');
  if(actions){const link=document.createElement('a');link.className='student-link student-recorded-process-link';link.dataset.completeRecordedProcess='1';link.href=recordedUrl;link.textContent='Já executei etapas fora do sistema → completar o registro';actions.appendChild(link);}
}

const manualNow=document.querySelector('.student-process-now');
if(manualNow&&!document.querySelector('[data-complete-recorded-process]')){
  const link=document.createElement('a');link.className='student-link student-recorded-process-link';link.dataset.completeRecordedProcess='1';link.href=recordedUrl;link.textContent='Registrar ou completar algo que já aconteceu →';manualNow.appendChild(link);
}
})();
