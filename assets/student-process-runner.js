(()=>{
'use strict';

document.querySelectorAll('[data-agitation-settings]').forEach(form=>{
  const mode=form.querySelector('[data-agitation-mode]'),periodic=form.querySelector('[data-agitation-periodic]');
  const refresh=()=>{if(periodic)periodic.hidden=mode?.value!=='periodic';};
  mode?.addEventListener('change',refresh);refresh();
});

document.querySelectorAll('[data-lab-insert-form]').forEach(form=>{
  const stage=form.querySelector('[data-lab-stage]'),developer=form.querySelector('[data-lab-developer]'),agitationMode=form.querySelector('[data-lab-agitation-mode]');
  const setGroup=(selector,show)=>form.querySelectorAll(selector).forEach(node=>{node.hidden=!show;node.querySelectorAll?.('input,select,textarea').forEach(control=>control.disabled=!show);});
  const refresh=()=>{
    const option=stage?.selectedOptions?.[0],type=option?.dataset.stageType||'',key=stage?.value||'';
    const developerMode=developer?.selectedOptions?.[0]?.dataset.developerMode||'';
    setGroup('[data-lab-field="custom"]',key==='custom');
    setGroup('[data-lab-field="development"]',type==='development');
    setGroup('[data-lab-field="developer-name"]',type==='development'&&developer?.value==='other');
    setGroup('[data-lab-field="fresh-volume"]',type==='development'&&developerMode==='fresh');
    setGroup('[data-lab-field="dilution-volume"]',type==='development'&&developerMode!=='fresh');
    setGroup('[data-lab-field="chemical"]',['chemical','custom'].includes(type));
    setGroup('[data-lab-field="reuse"]',key==='second_development');
    setGroup('[data-lab-field="agitation-periodic"]',agitationMode?.value==='periodic');
  };
  stage?.addEventListener('change',refresh);developer?.addEventListener('change',refresh);agitationMode?.addEventListener('change',refresh);refresh();
});

const root=document.querySelector('[data-process-runner]');if(!root)return;
const durationRaw=root.dataset.durationSeconds,agitationMode=root.dataset.agitationMode||'none',agitationDurationRaw=root.dataset.agitationDurationSeconds,agitationIntervalRaw=root.dataset.agitationIntervalSeconds;
const total=durationRaw===''?null:Number(durationRaw),agitationDuration=agitationDurationRaw===''?null:Number(agitationDurationRaw),agitationInterval=agitationIntervalRaw===''?null:Number(agitationIntervalRaw);
const clock=root.querySelector('[data-runner-clock]'),cue=root.querySelector('[data-runner-cue]'),wakeStatus=root.querySelector('[data-runner-wake-status]'),stateStatus=root.querySelector('[data-runner-state-status]'),agitationStatus=root.querySelector('[data-runner-agitation]');
const start=root.querySelector('[data-runner-start]'),pause=root.querySelector('[data-runner-pause]'),reset=root.querySelector('[data-runner-reset]');
const endpoint=root.dataset.runnerEndpoint||'',csrf=root.dataset.runnerCsrf||'',planStepId=root.dataset.planStepId||'';
const serverMode=!!(endpoint&&csrf&&planStepId),storageKey='student-process-runner:'+root.dataset.storageKey,wakeSessionKey='student-process-wake-active';
let interval=null,reconcileInterval=null,wakeLock=null,audioContext=null,busy=false,lastElapsedSync=0,lastAgitationPhase='',lastLegacyAgitationIndex=0,agitationInitialized=false;
const format=seconds=>{seconds=Math.max(0,Math.ceil(Number(seconds)||0));const h=Math.floor(seconds/3600),m=Math.floor((seconds%3600)/60),s=seconds%60;return h>0?`${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`:`${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;};
const token=()=>{try{return crypto.randomUUID();}catch{return `${Date.now()}-${Math.random().toString(16).slice(2)}`;}};
const parseDate=value=>{const ms=Date.parse(String(value||''));return Number.isFinite(ms)?ms:null;};
const setCue=(text,clear=true)=>{if(!cue)return;cue.textContent=text;if(clear&&text)setTimeout(()=>{if(cue&&cue.textContent===text)cue.textContent='';},1800);};
const beep=(frequency=880,duration=.16)=>{try{audioContext=audioContext||new (window.AudioContext||window.webkitAudioContext)();const osc=audioContext.createOscillator(),gain=audioContext.createGain();osc.frequency.value=frequency;gain.gain.setValueAtTime(.045,audioContext.currentTime);gain.gain.exponentialRampToValueAtTime(.0001,audioContext.currentTime+duration);osc.connect(gain);gain.connect(audioContext.destination);osc.start();osc.stop(audioContext.currentTime+duration);}catch{}};
const setWakeText=text=>{if(wakeStatus)wakeStatus.textContent=text;};
async function acquireWake(){
  if(document.visibilityState!=='visible')return;
  if(!('wakeLock' in navigator)){setWakeText('Tela ativa: recurso não disponível');return;}
  if(wakeLock&&!wakeLock.released){setWakeText('Tela mantida ativa');return;}
  try{wakeLock=await navigator.wakeLock.request('screen');setWakeText('Tela mantida ativa');wakeLock.addEventListener('release',()=>{wakeLock=null;if(state?.state==='running'&&document.visibilityState==='visible')setTimeout(acquireWake,150);else setWakeText('Tela ativa pausada');},{once:true});}
  catch{wakeLock=null;setWakeText('Não foi possível manter a tela ativa');}
}
async function releaseWake(){if(wakeLock&&!wakeLock.released){try{await wakeLock.release();}catch{}}wakeLock=null;setWakeText('Tela ativa pausada');}
function agitationStartCue(){setCue('Agitar',false);navigator.vibrate?.([140,70,140]);beep(920,.14);}
function agitationEndCue(){setCue('Fim da agitação');navigator.vibrate?.([90]);beep(620,.08);}

function normalizeServerState(input){
  if(!input||typeof input!=='object')return null;
  const remaining=input.remaining_seconds===null||input.remaining_seconds===undefined?total:Number(input.remaining_seconds);
  return {state:String(input.state||'idle'),remaining:Number.isFinite(remaining)?Math.max(0,remaining):total,endAt:parseDate(input.timer_ends_at),revision:Number(input.revision)||0,serverNow:parseDate(input.server_now)};
}
function initialServerState(){try{return normalizeServerState(JSON.parse(root.dataset.executionState||'{}'));}catch{return null;}}
const freshLocalState=()=>({state:'idle',remaining:total,endAt:null,revision:0});
function readLocalState(){if(total===null)return null;try{const raw=sessionStorage.getItem(storageKey);if(!raw)return freshLocalState();const parsed=JSON.parse(raw);if(Number(parsed.total)!==total)return freshLocalState();const remaining=Number(parsed.remaining);return {state:parsed.done?'elapsed':(parsed.running?'running':(parsed.paused?'paused':'idle')),remaining:Number.isFinite(remaining)?Math.max(0,remaining):total,endAt:Number(parsed.endAt)||null,revision:0};}catch{return freshLocalState();}}
let state=serverMode?(initialServerState()||freshLocalState()):readLocalState();
function persistLocal(){if(serverMode||!state||total===null)return;sessionStorage.setItem(storageKey,JSON.stringify({total,remaining:state.remaining,endAt:state.endAt,running:state.state==='running',paused:state.state==='paused',done:state.state==='elapsed'}));}
function remainingNow(){if(!state)return total;if(state.state==='running'&&state.endAt)return Math.max(0,Math.ceil((state.endAt-Date.now())/1000));return Math.max(0,Number(state.remaining)||0);}
function elapsedNow(){if(total===null)return 0;return Math.max(0,total-remainingNow());}
function stateLabel(){return state?.state==='running'?'Cronômetro em andamento':state?.state==='paused'?'Cronômetro pausado':state?.state==='elapsed'?'Tempo concluído':'Pronto para iniciar';}
async function reconcileWake(){if(state?.state==='running')await acquireWake();else await releaseWake();}

function renderAgitation({allowCue=true}={}){
  if(!agitationStatus)return;
  if(agitationMode==='continuous'){
    agitationStatus.classList.toggle('is-agitating',state?.state==='running');
    agitationStatus.textContent=state?.state==='running'?'AGITAÇÃO CONTÍNUA':state?.state==='paused'?'Agitação contínua · pausada':state?.state==='elapsed'?'Agitação contínua · tempo encerrado':'Agitação contínua durante toda a etapa';
    agitationInitialized=true;return;
  }
  if(agitationMode!=='periodic'||!agitationInterval||agitationInterval<=0){
    agitationStatus.classList.remove('is-agitating');agitationStatus.textContent='Sem aviso de agitação';agitationInitialized=true;return;
  }
  const elapsed=elapsedNow();
  if(state?.state!=='running'){
    agitationStatus.classList.remove('is-agitating');
    agitationStatus.textContent=state?.state==='paused'?'Agitação pausada':state?.state==='elapsed'?'Agitação encerrada':agitationDuration&&agitationDuration>0?`Agitar ${format(agitationDuration)} a cada ${format(agitationInterval)}`:`Agitação a cada ${format(agitationInterval)}`;
    agitationInitialized=true;return;
  }
  if(!agitationDuration||agitationDuration<=0){
    const index=Math.floor(elapsed/agitationInterval),next=Math.max(0,agitationInterval-(elapsed%agitationInterval));
    agitationStatus.classList.remove('is-agitating');agitationStatus.textContent=`Próxima agitação em ${format(next)}`;
    if(agitationInitialized&&index>lastLegacyAgitationIndex&&elapsed>0&&allowCue)agitationStartCue();
    lastLegacyAgitationIndex=index;agitationInitialized=true;return;
  }
  const cycle=Math.floor(elapsed/agitationInterval),position=elapsed-(cycle*agitationInterval),active=position<agitationDuration;
  const phase=`${cycle}:${active?'on':'off'}`;
  if(active){agitationStatus.classList.add('is-agitating');agitationStatus.textContent=`AGITAR · ${format(agitationDuration-position)}`;}
  else{agitationStatus.classList.remove('is-agitating');agitationStatus.textContent=`Próxima agitação em ${format(agitationInterval-position)}`;}
  if(agitationInitialized&&phase!==lastAgitationPhase&&allowCue){
    if(active)agitationStartCue();else if(lastAgitationPhase.endsWith(':on'))agitationEndCue();
  }
  lastAgitationPhase=phase;agitationInitialized=true;
}

function render({allowAgitationCue=true}={}){
  const timerState=state?.state||'idle';root.dataset.timerState=timerState;
  if(total===null){if(stateStatus)stateStatus.textContent='Sem cronômetro';if(start)start.hidden=true;if(pause)pause.hidden=true;if(reset)reset.hidden=true;renderAgitation({allowCue:false});return;}
  const remaining=remainingNow();if(clock)clock.textContent=format(remaining);if(stateStatus)stateStatus.textContent=stateLabel();
  if(start){start.hidden=!['idle','paused'].includes(timerState);start.disabled=busy;start.textContent=timerState==='paused'?'Retomar':'Iniciar';}
  if(pause){pause.hidden=timerState!=='running';pause.disabled=busy;}
  if(reset){reset.hidden=timerState==='idle';reset.disabled=busy;}
  renderAgitation({allowCue:allowAgitationCue});
}
function applyServerState(input,{quiet=false}={}){
  const next=normalizeServerState(input);if(!next)return;
  const previous=state?.state;state=next;
  if(previous!=='elapsed'&&state.state==='elapsed'&&!quiet){setCue('Tempo concluído',false);navigator.vibrate?.([260,100,260]);beep();}
  render();reconcileWake();
}
async function serverRequest(action,{allowConflict=true}={}){
  if(!serverMode)return null;
  const body=new URLSearchParams({_csrf:csrf,action,ajax:'1',plan_step_id:planStepId,revision:String(state?.revision??''),client_token:token()});
  const response=await fetch(endpoint,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','X-Requested-With':'XMLHttpRequest'},body,credentials:'same-origin'});
  let payload={};try{payload=await response.json();}catch{}
  if(response.status===409&&allowConflict&&payload.state){applyServerState(payload.state);setCue('Estado atualizado de outra aba ou dispositivo.');return payload.state;}
  if(!response.ok)throw new Error(payload.error||'Não foi possível atualizar o cronômetro.');
  if(payload.state)applyServerState(payload.state,{quiet:action==='timer_state'});
  return payload.state||null;
}
async function syncState({quiet=true}={}){
  if(!serverMode||busy)return;
  try{const body=new URLSearchParams({_csrf:csrf,action:'timer_state',ajax:'1',plan_step_id:planStepId,client_token:token()});const response=await fetch(endpoint,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','X-Requested-With':'XMLHttpRequest'},body,credentials:'same-origin'});const payload=await response.json();if(!response.ok)throw new Error(payload.error||'Falha ao sincronizar.');if(payload.state)applyServerState(payload.state,{quiet});}
  catch(error){if(!quiet)setCue(error instanceof Error?error.message:'Sem conexão para sincronizar.',false);}
}
async function transition(action){
  if(busy)return;busy=true;render({allowAgitationCue:false});
  try{await serverRequest(action);}
  catch(error){setCue(error instanceof Error?error.message:'Não foi possível atualizar o cronômetro.',false);}
  finally{busy=false;render();}
}
function localFinish(){if(!state||state.state==='elapsed')return;state.remaining=0;state.endAt=null;state.state='elapsed';persistLocal();setCue('Tempo concluído',false);navigator.vibrate?.([260,100,260]);beep();render();reconcileWake();}
function tick(){
  if(!state||state.state!=='running'||!state.endAt)return;
  state.remaining=remainingNow();
  if(state.remaining<=0){
    if(serverMode){state.state='elapsed';state.endAt=null;render();releaseWake();const now=Date.now();if(now-lastElapsedSync>1500){lastElapsedSync=now;syncState({quiet:false});}}
    else localFinish();
    return;
  }
  persistLocal();render();
}
async function startTimer(){
  if(!state||total===null||state.state==='running'||state.state==='elapsed')return;
  audioContext=audioContext||(()=>{try{return new (window.AudioContext||window.webkitAudioContext)();}catch{return null;}})();
  if(serverMode){await transition('timer_start');return;}
  if(state.remaining<=0)state.remaining=total;state.endAt=Date.now()+state.remaining*1000;state.state='running';sessionStorage.setItem(wakeSessionKey,'1');persistLocal();await acquireWake();render();
}
async function pauseTimer(){
  if(!state||state.state!=='running')return;
  if(serverMode){await transition('timer_pause');return;}
  state.remaining=remainingNow();state.endAt=null;state.state='paused';sessionStorage.setItem(wakeSessionKey,'0');persistLocal();await releaseWake();render();
}
async function resetTimer(){
  if(!state||total===null)return;
  lastAgitationPhase='';lastLegacyAgitationIndex=0;agitationInitialized=false;
  if(serverMode){await transition('timer_reset');setCue('Cronômetro reiniciado.');return;}
  state=freshLocalState();sessionStorage.removeItem(storageKey);sessionStorage.setItem(wakeSessionKey,'0');if(cue)cue.textContent='';await releaseWake();render({allowAgitationCue:false});
}
start?.addEventListener('click',startTimer);pause?.addEventListener('click',pauseTimer);reset?.addEventListener('click',resetTimer);
document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='visible'){if(serverMode)syncState();else if(state?.state==='running')acquireWake();}});
window.addEventListener('focus',()=>{if(serverMode)syncState();});window.addEventListener('online',()=>{if(serverMode)syncState({quiet:false});});
window.addEventListener('pagehide',()=>{clearInterval(interval);clearInterval(reconcileInterval);interval=null;reconcileInterval=null;});
if(!serverMode&&state?.state==='running'&&state.endAt&&remainingNow()<=0)localFinish();
render({allowAgitationCue:false});reconcileWake();
if(total!==null)interval=setInterval(tick,250);if(serverMode)reconcileInterval=setInterval(()=>syncState(),15000);
})();
