(()=>{
'use strict';
const dialog=document.querySelector('#media-detail'),body=document.querySelector('#media-detail-body'),status=document.querySelector('#library-status');
if(!dialog||!body)return;
let currentAsset=0,injecting=false;
document.addEventListener('click',event=>{const trigger=event.target.closest?.('[data-open-asset]');if(trigger)currentAsset=Number(trigger.dataset.openAsset)||0;},true);
async function json(url,options={}){const response=await fetch(url,{credentials:'same-origin',...options});const payload=await response.json().catch(()=>({}));if(!response.ok)throw new Error(payload.error||`HTTP ${response.status}`);return payload;}
async function enhance(){if(injecting||!currentAsset||!dialog.open||body.querySelector('[data-media-privacy]')||!body.querySelector('#md-save'))return;injecting=true;try{
  const data=await json(`/admin/api/media-detail.php?asset=${currentAsset}`),item=data.item||{},fields=body.querySelector('.media-detail-fields'),actions=fields?.querySelector('.dialog-actions');if(!fields||!actions)return;
  const label=document.createElement('label');label.dataset.mediaPrivacy='1';label.textContent='Privacidade';const select=document.createElement('select');select.innerHTML='<option value="public">Pública</option><option value="private">Privada</option>';select.value=item.visibility==='private'?'private':'public';label.append(select);
  const help=document.createElement('p');help.className='admin-muted';help.dataset.mediaPrivacyHelp='1';help.textContent=select.value==='private'?'Arquivo protegido. Pode ser vinculado a conteúdo de alunos; não possui URL pública direta.':'Arquivo público, disponível para uso normal no site.';label.append(help);actions.before(label);
  select.addEventListener('change',async()=>{const requested=select.value,previous=requested==='private'?'public':'private';select.disabled=true;try{await json('/admin/api/media-visibility.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({assetId:currentAsset,visibility:requested,csrf:window.MediaLibrary?.csrf||data.csrf||''})});if(status)status.textContent=requested==='private'?'Mídia marcada como privada.':'Mídia marcada como pública.';window.location.reload();}catch(error){select.value=previous;select.disabled=false;if(status)status.textContent=error.message==='asset_in_public_content'?'Remova esta mídia das páginas públicas antes de torná-la privada.':`Não foi possível alterar a privacidade: ${error.message}`;}});
}finally{injecting=false;}}
new MutationObserver(()=>{void enhance();}).observe(body,{childList:true,subtree:true});
dialog.addEventListener('close',()=>{currentAsset=0;});
})();
