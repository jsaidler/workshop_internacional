(()=>{
'use strict';
const dialog=document.querySelector('#media-detail'),body=document.querySelector('#media-detail-body'),status=document.querySelector('#library-status');
if(!dialog||!body)return;
let currentAssetId=0,installing=false;
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
async function jsonFetch(url,options={}){const r=await fetch(url,{credentials:'same-origin',...options});const data=await r.json().catch(()=>({}));if(!r.ok)throw new Error(data.error||`HTTP ${r.status}`);return data}
function humanError(message){return ({asset_in_use:'Este arquivo ainda está sendo usado por uma página. Remova as referências antes de excluí-lo.',asset_not_image:'Somente imagens possuem tamanhos derivados para regenerar.',original_file_missing:'O arquivo original não foi encontrado no armazenamento.',alpha_channel_lost:'A regeneração foi interrompida porque a transparência não foi preservada.'})[message]||message}
async function install(){
  if(!currentAssetId||installing||body.querySelector('[data-media-maintenance]'))return;
  installing=true;
  try{
    const data=await jsonFetch(`/admin/api/media-detail.php?asset=${currentAssetId}`),item=data.item||{},csrf=data.csrf||'';
    if(!item.id)return;
    const section=document.createElement('section');section.className='media-detail-section';section.dataset.mediaMaintenance='1';
    const uses=Array.isArray(item.uses)?item.uses:[];
    section.innerHTML=`<p class="admin-kicker">Manutenção do arquivo</p><h3>Arquivo original e derivados</h3><p class="admin-muted">O original nunca é convertido. As versões responsivas podem ser recriadas a qualquer momento a partir dele.</p><div class="button-row">${item.kind==='image'?'<button type="button" class="admin-button secondary" data-regenerate-media>Regenerar tamanhos</button>':''}<button type="button" class="admin-button danger" data-delete-media ${uses.length?'disabled':''}>Excluir definitivamente</button></div>${uses.length?`<p class="admin-muted">A exclusão está bloqueada porque este arquivo possui ${uses.length} uso${uses.length===1?'':'s'}. A lista “Onde é usado” acima mostra o que precisa ser removido antes.</p>`:'<p class="admin-muted">“Excluir definitivamente” remove o original, todas as versões, derivados e o registro da biblioteca. Esta ação não pode ser desfeita.</p>'}<p class="admin-muted" data-maintenance-result aria-live="polite"></p>`;
    body.append(section);
    const result=section.querySelector('[data-maintenance-result]');
    section.querySelector('[data-regenerate-media]')?.addEventListener('click',async e=>{
      const button=e.currentTarget;button.disabled=true;result.textContent='Regenerando todas as versões desta imagem a partir dos originais…';
      try{
        const out=await jsonFetch('/admin/api/media-regenerate.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf,assetId:item.id,scope:'all'})});
        const count=(out.versions||[]).reduce((sum,v)=>sum+Number(v.derivatives||0),0);result.textContent=`Concluído. ${count} arquivo${count===1?'':'s'} derivado${count===1?'':'s'} recriado${count===1?'':'s'} sem alterar o original.`;if(status)status.textContent='Versões responsivas regeneradas.';
      }catch(error){result.textContent=`Falha: ${humanError(error.message)}`;button.disabled=false;}
    });
    section.querySelector('[data-delete-media]')?.addEventListener('click',async e=>{
      const button=e.currentTarget;
      if(uses.length)return;
      const label=item.title||item.original_name||`arquivo #${item.id}`;
      if(!confirm(`Excluir definitivamente “${label}”?\n\nO original, as versões e todos os tamanhos derivados serão removidos do servidor. Esta ação não pode ser desfeita.`))return;
      button.disabled=true;result.textContent='Excluindo arquivo…';
      try{await jsonFetch('/admin/api/media-delete.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf,assetId:item.id})});dialog.close();location.reload();}
      catch(error){result.textContent=`Falha: ${humanError(error.message)}`;button.disabled=false;}
    });
  }catch(error){if(status)status.textContent=`Falha ao carregar as ações de manutenção: ${esc(humanError(error.message))}`;}
  finally{installing=false;}
}
document.addEventListener('click',event=>{const open=event.target.closest?.('[data-open-asset]');if(open){currentAssetId=Number(open.dataset.openAsset)||0;setTimeout(install,80);}});
new MutationObserver(()=>{if(dialog.open&&currentAssetId)install();}).observe(body,{childList:true,subtree:false});
})();