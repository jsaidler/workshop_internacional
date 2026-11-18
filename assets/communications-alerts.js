/* Manual delivery of the signed-in administrator's own student notices. */
(function(){
'use strict';
const panel=document.querySelector('[data-communications-alerts]');
if(!panel)return;
const form=panel.querySelector('[data-alerts-form]');
const counter=panel.querySelector('[data-alerts-count]');
const status=panel.querySelector('[data-alerts-feedback]');
const send=panel.querySelector('[data-alerts-run]');
const cancel=panel.querySelector('[data-alerts-cancel]');
const retry=panel.querySelector('[data-alerts-retry]');
if(!form||!counter||!status||!send||!cancel||!retry)return;
const token=form.querySelector('[name="_csrf"]')?.value||'';
let running=false,stopRequested=false,queue=null;
function feedback(text){status.textContent=text;}
function update(next){
  queue=next;
  counter.textContent=next.pending+' pendente(s) · '+next.processing+' processando · '+
    next.accepted+' aceito(s) · '+next.failed+' falha(s) · '+next.cancelled+' cancelado(s).';
  send.disabled=running||next.pending===0;
  cancel.disabled=next.pending===0;
  retry.disabled=running||next.failed===0;
}
async function request(action){
  const response=await fetch(panel.dataset.queueUrl,{
    method:'POST',credentials:'same-origin',cache:'no-store',
    headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},
    body:new URLSearchParams({_csrf:token,action}).toString()
  });
  const answer=await response.json();
  if(!response.ok||!answer.ok)throw Error(answer.error||'Não foi possível processar os avisos.');
  if(answer.queue)update(answer.queue);
  return answer;
}
async function run(){
  if(running)return;
  running=true;stopRequested=false;send.disabled=true;retry.disabled=true;
  let sent=Number(panel.dataset.initialSent||0);
  let skipped=Number(panel.dataset.initialSkipped||0);
  let failed=Number(panel.dataset.initialFailed||0);
  feedback('Processando os avisos pendentes da sua conta.');
  try{
    while(!stopRequested){
      const data=await request('run_alerts');
      const batch=data.result||{};
      if(batch.busy){feedback('Outro processamento está em andamento. A fila foi preservada.');break;}
      sent+=batch.sent||0;skipped+=batch.skipped||0;failed+=batch.failed||0;
      const processed=(batch.sent||0)+(batch.skipped||0)+(batch.failed||0);
      if(queue.pending===0){
        feedback('Processamento concluído: '+sent+' aceito(s), '+skipped+' ignorado(s) e '+failed+' falha(s).');break;
      }
      if(processed===0){
        feedback('A fila não avançou. Verifique a configuração do serviço de e-mail.');break;
      }
      feedback(sent+' aceito(s), '+failed+' falha(s), '+queue.pending+' pendente(s).');
    }
    if(stopRequested)feedback('Processamento interrompido. Consulte a situação atualizada dos avisos.');
  }catch(e){feedback(String(e.message||'Falha ao processar avisos.'));}
  finally{running=false;send.disabled=!!queue&&queue.pending===0;retry.disabled=!!queue&&queue.failed===0;}
}
async function cancelPending(){
  if(!window.confirm('Cancelar somente os avisos ainda pendentes da sua conta? Mensagens aceitas e em processamento não podem ser desfeitas.'))return;
  stopRequested=true;cancel.disabled=true;
  try{
    const data=await request('cancel_alerts');
    feedback(data.cancelled+' aviso(s) pendente(s) cancelado(s). O histórico foi preservado.');
  }catch(e){feedback(String(e.message||'Não foi possível cancelar os avisos.'));}
  finally{cancel.disabled=!!queue&&queue.pending===0;}
}
async function retryFailed(){
  if(!window.confirm('Reprocessar os avisos que falharam? Uma resposta incerta do provedor pode indicar um e-mail já aceito, portanto há risco de duplicação.'))return;
  retry.disabled=true;
  try{
    const result=await request('retry_alerts');
    if(result.requeued>0){
      feedback(result.requeued+' aviso(s) colocado(s) novamente na fila.');
      await run();
    }else feedback('Não há falhas para reprocessar.');
  }catch(e){feedback(String(e.message||'Não foi possível reprocessar falhas.'));}
  finally{retry.disabled=!!queue&&queue.failed===0;}
}
form.addEventListener('submit',event=>{
  const action=event.submitter?.value;
  if(!['run_alerts','cancel_alerts','retry_alerts'].includes(action))return;
  event.preventDefault();
  if(action==='run_alerts')void run();
  else if(action==='cancel_alerts')void cancelPending();
  else void retryFailed();
});
if(panel.dataset.autoRun==='1')void run();
})();