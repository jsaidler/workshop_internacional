/* Administrator-initiated delivery. Bounded HTTP batches do not require hosting cron. */
(function(){
'use strict';
const panel=document.querySelector('[data-communications-queue]');
if(!panel)return;
const form=panel.querySelector('[data-queue-form]');
const status=panel.querySelector('[data-queue-feedback]');
const counter=panel.querySelector('[data-queue-count]');
const send=panel.querySelector('[data-queue-run]');
const cancel=panel.querySelector('[data-queue-cancel]');
if(!form||!send||!cancel)return;
const token=form.querySelector('[name="_csrf"]')?.value||'';
let running=false,stopRequested=false,queue=null;
function feedback(message){if(status)status.textContent=message;}
function update(summary){
  queue=summary;
  if(counter)counter.textContent=
    summary.pending+' pendente(s) · '+summary.processing+' em processamento · '+
    summary.accepted+' aceito(s) pelo provedor · '+summary.failed+
    ' falha(s) · '+summary.cancelled+' cancelado(s).';
  send.disabled=running||summary.pending===0;
  cancel.disabled=summary.pending===0;
}
async function request(action){
  const body=new URLSearchParams({_csrf:token,action});
  const response=await fetch(panel.dataset.queueUrl,{
    method:'POST',credentials:'same-origin',cache:'no-store',
    headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},
    body:body.toString()
  });
  const result=await response.json();
  if(!response.ok||!result.ok)throw Error(result.error||'A operação não pôde ser concluída.');
  if(result.queue)update(result.queue);
  return result;
}
async function run(){
  if(running)return;
  running=true;stopRequested=false;send.disabled=true;
  let sent=0,skipped=0,failed=0;
  feedback('Enviando mensagens pendentes. Mantenha esta página aberta para continuar os lotes.');
  try{
    while(!stopRequested){
      const data=await request('run_queue');
      const result=data.result||{};
      if(result.busy){
        feedback('Há outro processamento ativo. A fila está preservada; tente novamente depois.');break;
      }
      sent+=result.sent||0;skipped+=result.skipped||0;failed+=result.failed||0;
      const done=(result.sent||0)+(result.skipped||0)+(result.failed||0);
      if(queue.pending===0){
        feedback('Envio concluído: '+sent+' aceito(s) pelo provedor, '+skipped+' ignorado(s) e '+failed+' falha(s) neste processamento.');break;
      }
      if(done===0){
        feedback('O envio não avançou. Restam '+queue.pending+' pendente(s). Verifique o limite diário e a configuração do serviço.');break;
      }
      feedback(sent+' aceito(s) pelo provedor, '+skipped+' ignorado(s), '+failed+' tentativa(s) com falha. '+queue.pending+' na fila.');
    }
    if(stopRequested)feedback('Processamento manual interrompido. Consulte a contagem da fila; e-mails já aceitos não podem ser desfeitos.');
  }catch(error){feedback(String(error.message||'Falha ao processar a fila. Acompanhe o histórico antes de tentar novamente.'));}
  finally{running=false;send.disabled=!!queue&&queue.pending===0;}
}
async function cancelPending(){
  if(!window.confirm('Cancelar os e-mails que ainda estão pendentes? Mensagens já aceitas pelo provedor ou em processamento não serão canceladas.'))return;
  stopRequested=true;
  cancel.disabled=true;
  try{
    const data=await request('cancel_queue');
    feedback(data.cancelled+' envio(s) pendente(s) cancelado(s). Mensagens já em processamento podem ser concluídas.');
  }catch(error){feedback(String(error.message||'Não foi possível cancelar os pendentes.'));}
  finally{cancel.disabled=!!queue&&queue.pending===0;}
}
form.addEventListener('submit',event=>{
  const action=event.submitter?.value;
  if(action!=='run_queue'&&action!=='cancel_queue')return;
  event.preventDefault();
  if(action==='run_queue')void run();
  else void cancelPending();
});
if(panel.dataset.autoRun==='1'){
  const url=new URL(window.location.href);
  url.searchParams.delete('run');
  window.history.replaceState(window.history.state,'',url.pathname+url.search+url.hash);
  void run();
}
})();
