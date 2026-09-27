(()=>{
'use strict';
const params=new URLSearchParams(location.search),pageId=Number(params.get('page')||0),inspector=document.querySelector('#inspector'),frame=document.querySelector('#page-frame');
if(!pageId||!inspector||!frame)return;
let options=null,loading=null,pageSaveTimer=0;
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
async function loadOptions(){if(options)return options;if(loading)return loading;loading=fetch(`/admin/api/cms-access-options.php?page=${pageId}`,{credentials:'same-origin'}).then(async r=>{const data=await r.json();if(!r.ok)throw new Error(data?.error?.code||'access_options_failed');options=data;return data;});return loading;}
function markDirty(){const name=inspector.querySelector('#s-name,#section-name');if(name)name.dispatchEvent(new Event('change',{bubbles:true}));}
function selectedSection(){return frame.contentDocument?.querySelector('.cms-section-selected')||null;}
function activeInspectorPanel(){return inspector.querySelector('.inspector-section:has(#s-name),.inspector-section:has(#section-name)')||inspector.querySelector('.inspector-section')||inspector;}
function setAttr(section,name,value){value=String(value??'').trim();if(value)section.setAttribute(name,value);else section.removeAttribute(name);}
function renderConditional(host,section){
 const access=host.querySelector('#cms-access-audience')?.value||'public',availability=host.querySelector('#cms-access-availability')?.value||'immediate';
 host.querySelector('[data-access-cohort]').hidden=access!=='cohort';host.querySelector('[data-access-schedule]').hidden=availability!=='scheduled';host.querySelector('[data-access-lesson]').hidden=availability!=='lesson';
 setAttr(section,'data-cms-access',access==='public'?'':access);setAttr(section,'data-cms-cohort-id',access==='cohort'?host.querySelector('#cms-access-cohort')?.value:'');setAttr(section,'data-cms-availability',availability==='immediate'?'':availability);setAttr(section,'data-cms-visible-from',availability==='scheduled'?host.querySelector('#cms-visible-from')?.value:'');setAttr(section,'data-cms-visible-until',availability==='scheduled'?host.querySelector('#cms-visible-until')?.value:'');setAttr(section,'data-cms-lesson-id',availability==='lesson'?host.querySelector('#cms-access-lesson')?.value:'');
}
async function injectSectionControls(){
 const section=selectedSection();if(!section||inspector.querySelector('[data-cms-access-controls]'))return;const data=await loadOptions();if(!selectedSection()||selectedSection()!==section)return;
 const ds=section.dataset,access=ds.cmsAccess||'public',availability=ds.cmsAvailability||'immediate';
 const block=document.createElement('section');block.className='cms-inspector-group cms-access-section';block.dataset.cmsAccessControls='1';block.innerHTML=`<header class="cms-inspector-group-header"><p class="eyebrow">Acesso</p><h3>Audiência e disponibilidade</h3></header><div class="cms-inspector-subgroup"><h4>Audiência</h4><label>Quem pode ver<select id="cms-access-audience"><option value="public">Todos os visitantes</option><option value="authenticated">Usuários autenticados</option><option value="activity">Participantes deste curso</option><option value="cohort">Turma específica</option></select></label><label data-access-cohort>Turma<select id="cms-access-cohort"><option value="">Escolha a turma</option>${data.cohorts.map(c=>`<option value="${c.id}">${esc(c.title)}</option>`).join('')}</select></label></div><div class="cms-inspector-subgroup"><h4>Disponibilidade</h4><label>Quando aparece<select id="cms-access-availability"><option value="immediate">Imediatamente</option><option value="scheduled">Em data programada</option><option value="lesson">Conforme liberação da aula</option></select></label><div data-access-schedule><label>Liberar em<input id="cms-visible-from" type="datetime-local" value="${esc(ds.cmsVisibleFrom||'')}"></label><label>Encerrar em <span class="inspector-note">(opcional)</span><input id="cms-visible-until" type="datetime-local" value="${esc(ds.cmsVisibleUntil||'')}"></label><p class="inspector-note">Horário: ${esc(data.timezone)}.</p></div><label data-access-lesson>Aula<select id="cms-access-lesson"><option value="">Escolha a aula</option>${data.lessons.map(l=>`<option value="${l.id}">${esc(l.title)}</option>`).join('')}</select></label></div><p class="inspector-note cms-access-authority-note">Audiência e disponibilidade são independentes. O servidor remove conteúdo sem acesso antes de gerar o HTML público.</p>`;
 activeInspectorPanel().append(block);block.querySelector('#cms-access-audience').value=access;block.querySelector('#cms-access-availability').value=availability;block.querySelector('#cms-access-cohort').value=ds.cmsCohortId||'';block.querySelector('#cms-access-lesson').value=ds.cmsLessonId||'';renderConditional(block,section);
 block.querySelectorAll('select,input').forEach(el=>el.addEventListener('change',()=>{renderConditional(block,section);markDirty();}));
}
async function persistPageAccess(block,data){
 const accessSelect=block.querySelector('#cms-page-access'),cohortSelect=block.querySelector('#cms-page-access-cohort'),status=block.querySelector('[data-page-access-status]');
 const value=accessSelect.value,cohortId=value==='cohort'?Number(cohortSelect.value||0):0;
 if(value==='cohort'&&!cohortId){status.textContent='Escolha a turma.';status.dataset.state='warning';return;}
 status.textContent='Salvando…';status.dataset.state='saving';accessSelect.disabled=true;cohortSelect.disabled=true;
 try{
  const r=await fetch('/admin/api/cms-page-access-save.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({pageId,access:value,cohortId,csrf:data.csrf})});const body=await r.json().catch(()=>({}));if(!r.ok)throw new Error(body?.error?.message||'Não foi possível salvar o acesso.');
  options.pageAccess=body.access;options.pageCohortId=body.cohortId||0;status.textContent='Salvo';status.dataset.state='saved';setTimeout(()=>{if(status.dataset.state==='saved')status.textContent=''},1400);
 }catch(error){status.textContent=error.message||'Erro ao salvar';status.dataset.state='error';}
 finally{accessSelect.disabled=false;cohortSelect.disabled=false;}
}
async function injectPageAccess(){
 if(!inspector.querySelector('#p-title,#page-title')||inspector.querySelector('[data-cms-page-access]'))return;const data=await loadOptions();if(!inspector.querySelector('#p-title,#page-title'))return;
 const block=document.createElement('section');block.className='cms-inspector-group cms-page-access-section';block.dataset.cmsPageAccess='1';block.innerHTML=`<header class="cms-inspector-group-header"><p class="eyebrow">Acesso</p><h3>Quem pode abrir esta página</h3></header><label>Audiência<select id="cms-page-access"><option value="public">Pública</option><option value="authenticated">Usuários autenticados</option><option value="activity">Participantes deste curso</option><option value="cohort">Turma específica</option></select></label><label data-page-access-cohort>Turma<select id="cms-page-access-cohort"><option value="">Escolha a turma</option>${data.cohorts.map(c=>`<option value="${c.id}">${esc(c.title)}</option>`).join('')}</select></label><div class="cms-inspector-inline-status"><span data-page-access-status aria-live="polite"></span></div><p class="inspector-note">O acesso da página é independente das regras das seções. Para restrições parciais, selecione a seção correspondente.</p>`;
 activeInspectorPanel().append(block);
 const accessSelect=block.querySelector('#cms-page-access'),cohortField=block.querySelector('[data-page-access-cohort]'),cohortSelect=block.querySelector('#cms-page-access-cohort');accessSelect.value=data.pageAccess==='enrolled'?'activity':data.pageAccess;cohortSelect.value=String(data.pageCohortId||'');
 const sync=()=>{cohortField.hidden=accessSelect.value!=='cohort';};sync();
 const scheduleSave=()=>{sync();clearTimeout(pageSaveTimer);pageSaveTimer=setTimeout(()=>persistPageAccess(block,data),180);};accessSelect.addEventListener('change',scheduleSave);cohortSelect.addEventListener('change',scheduleSave);
}
async function refresh(){try{await injectSectionControls();await injectPageAccess();}catch(e){console.error(e)}}
const observer=new MutationObserver(()=>queueMicrotask(refresh));observer.observe(inspector,{childList:true,subtree:true});frame.addEventListener('load',refresh);refresh();
})();
