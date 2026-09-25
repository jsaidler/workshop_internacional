(()=>{
'use strict';
const params=new URLSearchParams(location.search),pageId=Number(params.get('page')||0);
const inspector=document.querySelector('#inspector'),frame=document.querySelector('#page-frame');
if(!pageId||!inspector||!frame)return;
let csrf='',context={lessons:[],cohorts:[]},pageAccess='public',enhanceQueued=false;
const esc=value=>String(value??'').replace(/[&<>"']/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
async function json(url,options={}){const response=await fetch(url,{credentials:'same-origin',...options});const body=await response.json().catch(()=>({}));if(!response.ok)throw new Error(body?.error?.message||body?.error?.code||`HTTP ${response.status}`);return body;}
function selectedSection(){return frame.contentDocument?.querySelector('.cms-section-selected')||null;}
function touchSection(){const input=inspector.querySelector('#section-name');if(input)input.dispatchEvent(new Event('change',{bubbles:true}));}
function queueEnhance(){if(enhanceQueued)return;enhanceQueued=true;queueMicrotask(()=>{enhanceQueued=false;enhance();});}
function options(rows,value,valueKey,labelKey){return rows.map(row=>`<option value="${esc(row[valueKey])}" ${String(row[valueKey])===String(value)?'selected':''}>${esc(row[labelKey])}</option>`).join('');}
function sectionAccessPanel(section){
 const audience=section.dataset.cmsAccess||'public',cohort=section.dataset.cmsCohort||'',lesson=section.dataset.cmsLesson||'',from=section.dataset.cmsAvailableFrom||'',until=section.dataset.cmsAvailableUntil||'';
 const host=document.createElement('div');host.id='cms-section-access-panel';host.className='inspector-section';
 host.innerHTML=`<hr><header><p class="eyebrow">Acesso e disponibilidade</p><h3>Quem recebe esta seção</h3></header>
 <label>Acesso<select id="cms-section-audience"><option value="public" ${audience==='public'?'selected':''}>Público</option><option value="authenticated" ${audience==='authenticated'?'selected':''}>Usuários autenticados</option><option value="enrolled" ${audience==='enrolled'?'selected':''}>Participantes deste curso</option><option value="cohort" ${audience==='cohort'?'selected':''}>Turma específica</option></select></label>
 <label id="cms-section-cohort-row" ${audience==='cohort'?'':'hidden'}>Turma<select id="cms-section-cohort"><option value="">Escolha</option>${options(context.cohorts,cohort,'uuid','title')}</select></label>
 <label>Controlada por aula<select id="cms-section-lesson"><option value="">Não</option>${options(context.lessons,lesson,'key','title')}</select></label>
 <p class="inspector-note">A aula é uma condição adicional. A seção só aparece quando a aula estiver efetivamente liberada para a turma do usuário.</p>
 <label>Disponível a partir de<input id="cms-section-from" type="datetime-local" value="${esc(from)}"></label>
 <label>Disponível até<input id="cms-section-until" type="datetime-local" value="${esc(until)}"></label>
 <p class="inspector-note">Datas vazias significam sem limite. Audiência, janela de tempo e aula são verificadas juntas no servidor antes do HTML ser enviado.</p>
 <div id="cms-section-media-slots"></div>`;
 inspector.append(host);
 const audienceEl=host.querySelector('#cms-section-audience'),cohortRow=host.querySelector('#cms-section-cohort-row'),cohortEl=host.querySelector('#cms-section-cohort'),lessonEl=host.querySelector('#cms-section-lesson'),fromEl=host.querySelector('#cms-section-from'),untilEl=host.querySelector('#cms-section-until');
 const sync=()=>{
   const nextAudience=audienceEl.value;section.dataset.cmsAccess=nextAudience;
   cohortRow.hidden=nextAudience!=='cohort';
   if(nextAudience==='cohort'){if(!cohortEl.value&&context.cohorts[0])cohortEl.value=context.cohorts[0].uuid;section.dataset.cmsCohort=cohortEl.value;}else delete section.dataset.cmsCohort;
   if(lessonEl.value)section.dataset.cmsLesson=lessonEl.value;else delete section.dataset.cmsLesson;
   if(fromEl.value)section.dataset.cmsAvailableFrom=fromEl.value;else delete section.dataset.cmsAvailableFrom;
   if(untilEl.value)section.dataset.cmsAvailableUntil=untilEl.value;else delete section.dataset.cmsAvailableUntil;
   touchSection();
 };
 [audienceEl,cohortEl,lessonEl,fromEl,untilEl].forEach(el=>el.addEventListener('change',sync));
 renderPrivateSlots(section,host.querySelector('#cms-section-media-slots'));
}
async function renderPrivateSlots(section,host){
 const slots=[...section.querySelectorAll('[data-private-media-slot]')];if(!slots.length)return;
 host.innerHTML='<hr><p class="eyebrow">Mídia privada</p><p class="inspector-note">A Biblioteca de mídia é a única origem. Este editor apenas vincula uma imagem privada ao espaço da seção.</p><p>Carregando biblioteca…</p>';
 try{
   await window.MediaLibrary.load();const images=window.MediaLibrary.images.filter(item=>(item.visibility||'public')==='private');
   host.innerHTML=`<hr><p class="eyebrow">Mídia privada</p><p class="inspector-note">A Biblioteca de mídia é a única origem. Este editor apenas vincula uma imagem privada ao espaço da seção.</p><p><a href="/admin/media.php" target="_blank" rel="noopener">Abrir Biblioteca de mídia ↗</a></p>`;
   slots.forEach((slot,index)=>{
     const key=slot.dataset.privateMediaSlot||`slot-${index+1}`,current=slot.dataset.cmsMediaAsset||'';const label=slot.dataset.privateMediaAlt||key;
     const row=document.createElement('label');row.textContent=label;const select=document.createElement('select');select.innerHTML=`<option value="">Nenhuma mídia vinculada</option>${images.map(item=>`<option value="${esc(item.asset_uuid)}" ${item.asset_uuid===current?'selected':''}>${esc(item.title||item.original_name||item.asset_uuid)}</option>`).join('')}`;row.append(select);host.append(row);
     select.addEventListener('change',()=>{if(select.value)slot.dataset.cmsMediaAsset=select.value;else delete slot.dataset.cmsMediaAsset;touchSection();});
   });
   if(!images.length){const p=document.createElement('p');p.className='inspector-note';p.textContent='Não há imagens privadas. Envie ou marque uma imagem como Privada na Biblioteca de mídia.';host.append(p);}
 }catch(error){host.innerHTML+='<p class="inspector-note">Não foi possível carregar a Biblioteca de mídia.</p>';}
}
function pageAccessPanel(){
 if(inspector.querySelector('#cms-page-access-panel'))return;const pageTitle=inspector.querySelector('#page-title');if(!pageTitle)return;
 const host=document.createElement('div');host.id='cms-page-access-panel';host.innerHTML=`<hr><p class="eyebrow">Acesso</p><label>Página<select id="cms-page-access"><option value="public" ${pageAccess==='public'?'selected':''}>Pública</option><option value="authenticated" ${pageAccess==='authenticated'?'selected':''}>Usuários autenticados</option><option value="enrolled" ${pageAccess==='enrolled'?'selected':''}>Participantes deste curso</option></select></label><p class="inspector-note">Regras mais específicas podem ser aplicadas em cada seção. O bloqueio acontece no servidor.</p>`;
 pageTitle.closest('.inspector-section')?.append(host);
 host.querySelector('#cms-page-access').addEventListener('change',async event=>{
   const value=event.target.value;event.target.disabled=true;
   try{
     const result=await json('/admin/api/cms-page-access.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf,pageId,accessLevel:value})});pageAccess=result.accessLevel;
     if(!result.showInNav){const nav=inspector.querySelector('#page-nav-visible');if(nav){nav.checked=false;nav.dispatchEvent(new Event('input',{bubbles:true}));}}
   }catch(error){event.target.value=pageAccess;window.alert('Não foi possível alterar o acesso da página: '+error.message);}finally{event.target.disabled=false;}
 });
}
function enhance(){
 const section=selectedSection();
 if(section&&inspector.querySelector('#section-name')&&!inspector.querySelector('#cms-section-access-panel'))sectionAccessPanel(section);
 pageAccessPanel();
}
const observer=new MutationObserver(queueEnhance);observer.observe(inspector,{childList:true,subtree:true});frame.addEventListener('load',queueEnhance);
(async()=>{try{const data=await json(`/admin/api/cms-page-load.php?page=${pageId}`);csrf=data.csrf||'';context=data.accessContext||context;pageAccess=data.page?.accessLevel||'public';queueEnhance();}catch(error){console.error(error);}})();
})();
