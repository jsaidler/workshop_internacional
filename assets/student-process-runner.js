(()=>{
'use strict';
const root=document.querySelector('[data-process-runner]');if(!root)return;
const durationRaw=root.dataset.durationSeconds,agitationRaw=root.dataset.agitationSeconds;
const total=durationRaw===''?null:Number(durationRaw),agitation=agitationRaw===''?null:Number(agitationRaw);
const clock=root.querySelector('[data-runner-clock]'),cue=root.querySelector('[data-runner-cue]'),wakeStatus=root.querySelector('[data-runner-wake-status]');
const start=root.querySelector('[data-runner-start]'),pause=root.querySelector('[data-runner-pause]'),reset=root.querySelector('[data-runner-reset]'),complete=root.querySelector('[data-runner-complete]');
const storageKey='student-process-runner:'+root.dataset.storageKey,wakeSessionKey='student-process-wake-active';
let interval=null,wakeLock=null,audioContext=null;
const format=seconds=>{seconds=Math.max(0,Math.ceil(Number(seconds)||0));const h=Math.floor(seconds/3600),m=Math.floor((seconds%3600)/60),s=seconds%60;return h>0?`${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`:`${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;};
const freshState=()=>({remaining:total,endAt:null,running:false,done:false,lastAgitation:0});
const readState=()=>{if(total===null)return null;try{const raw=sessionStorage.getItem(storageKey);if(!raw)return freshState();const parsed=JSON.parse(raw);if(Number(parsed.total)!==total)return freshState();const parsedRemaining=Number(parsed.remaining);return {remaining:Number.isFinite(parsedRemaining)?Math.max(0,parsedRemaining):total,endAt:Number(parsed.endAt)||null,running:!!parsed.running,done:!!parsed.done,lastAgitation:Number(parsed.lastAgitation)||0};}catch{return freshState();}};
let state=readState();
const persist=()=>{if(state)sessionStorage.setItem(storageKey,JSON.stringify({...state,total}));};
const enableCompletion=()=>{if(!complete)return;if(complete.tagName==='A'){complete.classList.remove('is-disabled');complete.removeAttribute('aria-disabled');complete.removeAttribute('tabindex');}else complete.disabled=false;};
const disableCompletion=()=>{if(!complete||total===null)return;if(complete.tagName==='A'){complete.classList.add('is-disabled');complete.setAttribute('aria-disabled','true');complete.setAttribute('tabindex','-1');}else complete.disabled=true;};
const render=()=>{if(total===null){enableCompletion();return;}if(clock)clock.textContent=format(state.remaining);if(start)start.disabled=state.running||state.done;if(pause)pause.disabled=!state.running;if(state.done)enableCompletion();else disableCompletion();};
const beep=()=>{try{audioContext=audioContext||new (window.AudioContext||window.webkitAudioContext)();const osc=audioContext.createOscillator(),gain=audioContext.createGain();osc.frequency.value=880;gain.gain.setValueAtTime(.045,audioContext.currentTime);gain.gain.exponentialRampToValueAtTime(.0001,audioContext.currentTime+.16);osc.connect(gain);gain.connect(audioContext.destination);osc.start();osc.stop(audioContext.currentTime+.16);}catch{}};
const setWakeText=text=>{if(wakeStatus)wakeStatus.textContent=text;};
const wakeWanted=()=>sessionStorage.getItem(wakeSessionKey)==='1';
async function acquireWake(){
  if(!wakeWanted()||document.visibilityState!=='visible')return;
  if(!('wakeLock' in navigator)){setWakeText('Tela ativa: recurso não disponível');return;}
  if(wakeLock&&!wakeLock.released){setWakeText('Tela mantida ativa');return;}
  try{wakeLock=await navigator.wakeLock.request('screen');setWakeText('Tela mantida ativa');wakeLock.addEventListener('release',()=>{wakeLock=null;if(wakeWanted()&&document.visibilityState==='visible')setTimeout(acquireWake,150);else setWakeText('Tela ativa pausada');},{once:true});}
  catch{wakeLock=null;setWakeText('Não foi possível manter a tela ativa');}
}
async function releaseWake(){if(wakeLock&&!wakeLock.released){try{await wakeLock.release();}catch{}}wakeLock=null;setWakeText('Tela ativa pausada');}
const signalStarted=()=>{const endpoint=root.dataset.startEndpoint,csrf=root.dataset.startCsrf;if(!endpoint||!csrf||root.dataset.startSent==='1')return;root.dataset.startSent='1';const body=new URLSearchParams({_csrf:csrf,action:'start',ajax:'1'});fetch(endpoint,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','X-Requested-With':'XMLHttpRequest'},body,credentials:'same-origin'}).catch(()=>{root.dataset.startSent='0';});};
const agitationCue=()=>{if(cue){cue.textContent='Agitação';setTimeout(()=>{if(cue&&state&&!state.done)cue.textContent='';},1400);}navigator.vibrate?.([120,80,120]);beep();};
const finish=()=>{if(!state||state.done)return;state.remaining=0;state.endAt=null;state.running=false;state.done=true;clearInterval(interval);interval=null;persist();if(cue)cue.textContent='Tempo concluído';navigator.vibrate?.([260,100,260]);beep();render();};
const tick=()=>{if(!state||!state.running||!state.endAt)return;state.remaining=Math.max(0,Math.ceil((state.endAt-Date.now())/1000));if(agitation&&agitation>0&&state.remaining>0){const elapsed=Math.max(0,total-state.remaining),index=Math.floor(elapsed/agitation);if(index>state.lastAgitation){state.lastAgitation=index;agitationCue();}}if(state.remaining<=0){finish();return;}persist();render();};
const startTimer=async()=>{if(!state||state.done||state.running||state.remaining<=0)return;audioContext=audioContext||(()=>{try{return new (window.AudioContext||window.webkitAudioContext)();}catch{return null;}})();state.endAt=Date.now()+(state.remaining*1000);state.running=true;sessionStorage.setItem(wakeSessionKey,'1');persist();signalStarted();await acquireWake();if(!interval)interval=setInterval(tick,250);render();};
const pauseTimer=async()=>{if(!state||!state.running)return;tick();state.running=false;state.endAt=null;sessionStorage.setItem(wakeSessionKey,'0');clearInterval(interval);interval=null;persist();await releaseWake();render();};
const resetTimer=async()=>{if(!state)return;state=freshState();sessionStorage.setItem(wakeSessionKey,'1');sessionStorage.removeItem(storageKey);clearInterval(interval);interval=null;if(cue)cue.textContent='';await acquireWake();render();};
start?.addEventListener('click',startTimer);pause?.addEventListener('click',pauseTimer);reset?.addEventListener('click',resetTimer);
complete?.addEventListener('click',event=>{if(total!==null&&!state?.done){event.preventDefault();return;}sessionStorage.removeItem(storageKey);if(root.dataset.finalStage==='1')sessionStorage.setItem(wakeSessionKey,'0');});
document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='visible'&&wakeWanted())acquireWake();});
window.addEventListener('pagehide',()=>{clearInterval(interval);interval=null;});
if(state?.running&&state.endAt){state.remaining=Math.max(0,Math.ceil((state.endAt-Date.now())/1000));if(state.remaining<=0)finish();else interval=setInterval(tick,250);}
sessionStorage.setItem(wakeSessionKey,'1');acquireWake();render();
})();
