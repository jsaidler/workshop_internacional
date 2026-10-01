(()=>{
'use strict';
const form=document.querySelector('[data-process-step-form]');if(!form)return;
const params=new URLSearchParams(location.search),testId=params.get('id');if(!testId)return;
document.querySelector('.student-context-timer')?.remove();
if(document.querySelector('[data-process-template-entry]'))return;
const section=document.createElement('section');section.className='student-process-final-summary';section.dataset.processTemplateEntry='1';
const title=document.createElement('h3');title.textContent='Modo laboratório';
const text=document.createElement('p');text.textContent='Use um processamento salvo para executar várias etapas em sequência com a tela ativa.';
const actions=document.createElement('div');actions.className='student-actions';
const link=document.createElement('a');link.className='button button-primary';link.href='/aluno/processamentos.php?test='+encodeURIComponent(testId);link.textContent='Escolher processamento';
actions.append(link);section.append(title,text,actions);form.before(section);
})();
