(()=>{
'use strict';
let activeAssetId=0,csrf='';
document.addEventListener('click',event=>{const trigger=event.target.closest?.('[data-open-asset]');if(trigger)activeAssetId=Number(trigger.dataset.openAsset)||0;},true);
const body=document.querySelector('#media-detail-body');if(!body)return;
const observer=new MutationObserver(async()=>{
  const fields=body.querySelector('.media-detail-fields');
  if(!fields||!activeAssetId||fields.querySelector('[data-media-privacy-field]'))return;
  const label=document.createElement('label');label.dataset.mediaPrivacyField='1';label.textContent='Acesso';
  const select=document.createElement('select');select.innerHTML='<option value="public">Pública</option><option value="private">Privada</option>';label.appendChild(select);
  const tags=fields.querySelector('#md-tags')?.closest('label');(tags||fields.firstElementChild)?.after(label);
  try{
    const response=await fetch(`/admin/api/media-detail.php?asset=${activeAssetId}`,{credentials:'same-origin'});const data=await response.json();if(!response.ok)throw new Error(data.error||'Falha');csrf=data.csrf||csrf;select.value=data.item?.visibility==='private'?'private':'public';
  }catch{return;}
  select.addEventListener('change',async()=>{
    select.disabled=true;
    try{
      const response=await fetch('/admin/api/media-update.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({assetId:activeAssetId,visibility:select.value,csrf})});const data=await response.json();if(!response.ok)throw new Error(data.error||'Falha ao alterar acesso');
      const status=document.querySelector('#library-status');if(status)status.textContent=select.value==='private'?'Mídia marcada como privada.':'Mídia marcada como pública.';
    }catch(error){select.value=select.value==='private'?'public':'private';window.alert(error.message);}finally{select.disabled=false;}
  });
});
observer.observe(body,{childList:true,subtree:true});
})();
