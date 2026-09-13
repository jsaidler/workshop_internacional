(()=>{
'use strict';
const dialog=document.querySelector('#media-detail'),body=document.querySelector('#media-detail-body'),status=document.querySelector('#library-status');
if(!dialog||!body)return;
let currentAssetId=0,installing=false;
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot',"'":'&#039;'}[c]));
async function jsonFetch(url,options={}){const r=await fetch(url,{credentials:'same-origin',...options});const data=await r.json().catch(()=>({}));if(!r.ok)throw new Error(data.error||`HTTP ${r.status}`);return data}
function humanError(message){return ({asset_in_use:'Este arquivo ainda está sendo usado por uma página. Remova as referências antes de excluí-lo.',asset_not_image:'Somente imagens possuem tamanhos derivados para regenerar.',original_file_missing:'O arquivo original não foi encontrado no armazenamento.',alpha_channel_lost:'A regeneração foi interrompida porque a transparência não foi preservada.'})[message]||message}
function derivativeInventory(item){
  if(item.kind!=='image')return '';
  const derivatives=Array.isArray(item.derivatives)?item.derivatives:[],byWidth=new Map();
  derivatives.forEach(d=>{const width=Number(d.width)||0;if(!width)return;const formats=byWidth.get(width)||new Set();formats.add(String(d.format||'').toUpperCase());byWidth.set(width,formats)});
  const rows=[...byWidth.entries()].sort((a,b)=>a[0]-b[0]).map(([width,formats])=>`<li><strong>${width}px</strong><span>${[...formats].join(' + ')}</span></li>`).join('');
  const originalSize=item.width&&item.height?`${item.width} × ${item.height}px`:'dimensão não informada';
  const mime=String(item.mime_type||'').replace('image/','').toUpperCase()||'imagem';
  return `<div class="media-derivative-inventory"><div class="media-original-row"><strong>Original preservado</strong><span>${esc(originalSize)} · ${esc(mime)}</span></div><p class="admin-kicker">Tamanhos gerados da versão ativa</p>${rows?`<ul>${rows}</ul>`:'<p class="admin-muted">Nenhum tamanho responsivo foi encontrado para a versão ativa.</p>'}</div>`;
}
async function install(){
  if(!currentAssetId||installing||body.querySelector('[data-media-maintenance]'))return;
  installing=true;
  try{
    const data=await jsonFetch(`/admin/api/media-detail.php?asset=${currentAssetId}`),item=data.item||{},csrf=data.csrf||'';
    if(!item.id)return;
    const section=document.createElement('section');section.className='media-detail-section';section.dataset.mediaMaintenance='1';
    const uses=Array.isArray(item.uses)?item.uses:[],versions=Array.isArray(item.versions)?item.versions:[];
    section.innerHTML=`<p class="admin-kicker">Manutenção do arquivo</p><h3>Original e tamanhos do site</h3><p class="admin-muted">O original armazenado não é convertido. Os tamanhos usados pelo site podem ser recriados a qualquer momento a partir dele.</p>${derivativeInventory(item)}<div class="button-row">${item.kind==='image'?'<button type="button" class="admin-button secondary" data-regenerate-active>Regenerar versão ativa</button>':''}${item.kind==='image'&&versions.length>1?'<button type="button" class="link-button" data-regenerate-all>Regenerar todas as versões</button>':''}<button type="button" class="admin-button danger" data-delete-media ${uses.length?'disabled':''}>Excluir definitivamente</button></div>${uses.length?`<p class="admin-muted">A exclusão está bloqueada porque este arquivo possui ${uses.length} uso${uses.length===1?'':'s'}. A lista “Onde é usado” acima mostra o que precisa ser removido antes.</p>`:'<p class="admin-muted">“Excluir definitivamente” remove o original, todas as versões, tamanhos gerados e o registro da biblioteca. Esta ação não pode ser desfeita.</p>'}<p class="admin-muted" data-maintenance-result aria-live="polite"></p>`;
    body.append(section);
    const result=section.querySelector('[data-maintenance-result]');
    async function regenerate(scope,button){
      button.disabled=true;result.textContent=scope==='all'?'Regenerando todos os tamanhos de todas as versões…':'Regenerando os tamanhos da versão ativa…';
      try{
        const out=await jsonFetch('/admin/api/media-regenerate.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf,assetId:item.id,scope})});
        const count=(out.versions||[]).reduce((sum,v)=>sum+Number(v.derivatives||0),0);result.textContent=`Concluído. ${count} arquivo${count===1?'':'s'} responsivo${count===1?'':'s'} recriado${count===1?'':'s'} sem alterar o original.`;if(status)status.textContent='Tamanhos responsivos regenerados.';
      }catch(error){result.textContent=`Falha: ${humanError(error.message)}`;}
      finally{button.disabled=false;}
    }
    section.querySelector('[data-regenerate-active]')?.addEventListener('click',e=>regenerate('active',e.currentTarget));
    section.querySelector('[data-regenerate-all]')?.addEventListener('click',e=>regenerate('all',e.currentTarget));
    section.querySelector('[data-delete-media]')?.addEventListener('click',async e=>{
      const button=e.currentTarget;
      if(uses.length)return;
      const label=item.title||item.original_name||`arquivo #${item.id}`;
      if(!confirm(`Excluir definitivamente “${label}”?\n\nO original, as versões e todos os tamanhos gerados serão removidos do servidor. Esta ação não pode ser desfeita.`))return;
      button.disabled=true;result.textContent='Excluindo arquivo…';
      try{await jsonFetch('/admin/api/media-delete.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf,assetId:item.id})});dialog.close();location.reload();}
      catch(error){result.textContent=`Falha: ${humanError(error.message)}`;button.disabled=false;}
    });
  }catch(error){if(status)status.textContent=`Falha ao carregar as ações de manutenção: ${humanError(error.message)}`;}
  finally{installing=false;}
}
document.addEventListener('click',event=>{const open=event.target.closest?.('[data-open-asset]');if(open){currentAssetId=Number(open.dataset.openAsset)||0;setTimeout(install,80);}});
new MutationObserver(()=>{if(dialog.open&&currentAssetId)install();}).observe(body,{childList:true,subtree:false});
})();
