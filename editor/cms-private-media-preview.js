(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const inspector=document.querySelector('#inspector');
const toast=document.querySelector('#toast');
if(!frame)return;
const params=new URLSearchParams(location.search);const pageId=Number(params.get('page')||0);
let slots={},images=[],csrf='',selectedKey='',rootObserver=null,scheduled=false,loadVersion=0,dialog=null;
const d=()=>frame.contentDocument;
const root=()=>d()?.querySelector('[data-cms-page-main]')||d()?.querySelector('main')||null;
const clean=value=>String(value||'').trim();
const slotKey=node=>clean(node?.getAttribute('data-private-media-slot'));
const slotAlt=node=>clean(node?.getAttribute('data-private-media-alt'))||slotKey(node)||'Infográfico';
const metaFor=key=>slots[key]||{key,alt:key,bound:false,assetId:null,src:null};
const sourceFor=key=>[...root()?.querySelectorAll('[data-private-media-slot]')||[]].find(node=>slotKey(node)===key)||null;

function notify(message,error=false){if(!toast)return;toast.textContent=message;toast.classList.toggle('error',error);toast.classList.add('show');setTimeout(()=>toast.classList.remove('show'),2600)}
function applyPayload(data){slots=data?.slots&&typeof data.slots==='object'?data.slots:{};images=Array.isArray(data?.images)?data.images:[];csrf=clean(data?.csrf||csrf)}
async function loadSlots(){
  const version=++loadVersion;
  if(window.CMS_PRIVATE_MEDIA_PREVIEW_DATA&&typeof window.CMS_PRIVATE_MEDIA_PREVIEW_DATA==='object'){
    applyPayload(window.CMS_PRIVATE_MEDIA_PREVIEW_DATA);render();if(selectedKey)renderInspector();return;
  }
  if(pageId<1)return;
  try{
    const response=await fetch(`/admin/api/cms-private-media-slots.php?page=${pageId}`,{credentials:'same-origin',cache:'no-store'});
    if(!response.ok)throw new Error('Não foi possível carregar as imagens privadas.');
    const data=await response.json();if(version!==loadVersion)return;applyPayload(data);render();if(selectedKey)renderInspector();
  }catch(error){notify(error?.message||'Não foi possível carregar as imagens privadas.',true)}
}
async function mutateSlot(key,action,assetId=0){
  const payload={pageId,slotKey:key,action,assetId,csrf};let data;
  if(typeof window.CMS_PRIVATE_MEDIA_PREVIEW_MUTATE==='function')data=await window.CMS_PRIVATE_MEDIA_PREVIEW_MUTATE(payload);
  else{
    const response=await fetch('/admin/api/cms-private-media-slots.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    data=await response.json().catch(()=>({}));if(!response.ok)throw new Error(data?.error||'Não foi possível atualizar a imagem privada.');
  }
  applyPayload(data);selectedKey=key;render();renderInspector();return data;
}
function ensureParentStyle(){
  if(document.querySelector('style[data-cms-private-media-editor-style]'))return;
  const style=document.createElement('style');style.dataset.cmsPrivateMediaEditorStyle='1';style.textContent=`
.cms-private-media-inspector{display:grid;gap:14px}.cms-private-media-inspector h2,.cms-private-media-inspector p{margin:0}.cms-private-media-inspector .eyebrow{color:#6c706a;font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase}.cms-private-media-inspector-preview{overflow:hidden;min-height:120px;border:1px solid #d7d9d4;border-radius:5px;background:#111;display:grid;place-items:center}.cms-private-media-inspector-preview img{display:block;width:100%;max-height:220px;object-fit:contain}.cms-private-media-inspector-actions{display:grid;gap:7px}.cms-private-media-inspector-actions button,.cms-private-media-inspector-actions a{min-height:36px;padding:8px 10px;border:1px solid #bbbdb8;border-radius:4px;background:#fff;color:#171817;font:600 12px/1.2 Arial,sans-serif;text-align:center;text-decoration:none;cursor:pointer}.cms-private-media-inspector-actions .primary{border-color:#171817;background:#171817;color:#fff}.cms-private-media-inspector-actions .danger{color:#942f25}.cms-private-media-slot-code{padding:8px;border-radius:4px;background:#f2f3ef;color:#61645f;font:10px/1.45 monospace;overflow-wrap:anywhere}.cms-private-media-dialog{width:min(840px,calc(100vw - 32px));max-height:calc(100vh - 32px);padding:0;border:1px solid #b9bbb6;border-radius:7px;background:#fff;color:#171817;box-shadow:0 24px 80px rgba(0,0,0,.24)}.cms-private-media-dialog::backdrop{background:rgba(0,0,0,.46)}.cms-private-media-dialog header{position:sticky;top:0;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 18px;border-bottom:1px solid #ddd;background:#fff}.cms-private-media-dialog header h2{margin:0;font:600 18px Arial,sans-serif}.cms-private-media-dialog header button{width:34px;height:34px;border:1px solid #ccc;border-radius:4px;background:#fff;font-size:20px;cursor:pointer}.cms-private-media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;padding:18px}.cms-private-media-choice{overflow:hidden;padding:0;border:1px solid #d7d9d4;border-radius:5px;background:#fff;color:#171817;text-align:left;cursor:pointer}.cms-private-media-choice:hover,.cms-private-media-choice:focus-visible{border-color:#171817;outline:2px solid #186f4d;outline-offset:1px}.cms-private-media-choice.is-current{box-shadow:inset 0 0 0 2px #186f4d}.cms-private-media-choice img{display:block;width:100%;aspect-ratio:4/3;object-fit:contain;background:#111}.cms-private-media-choice span{display:block;padding:9px;font:600 12px/1.35 Arial,sans-serif}.cms-private-media-empty{padding:28px}.cms-private-media-empty p{margin:0 0 12px;color:#666}.cms-private-media-empty a{font-weight:600;color:inherit}`;document.head.append(style);
}
function ensureFrameStyle(doc){
  let style=doc.head?.querySelector('[data-cms-editor-private-media-style]');if(style)return style;
  style=doc.createElement('style');style.dataset.cmsEditorPrivateMediaStyle='1';style.dataset.cmsEditorUi='private-media-style';
  style.textContent=`
[data-private-media-slot]{display:block!important;position:relative!important;min-height:190px!important;box-sizing:border-box!important}
[data-cms-editor-ui="private-media-layer"]{position:fixed;inset:0;pointer-events:none;z-index:2147483000}
.cms-editor-private-media-card{position:fixed;display:grid;place-items:center;box-sizing:border-box;padding:0;border:1px dashed rgba(30,98,75,.72);background:rgba(245,247,243,.97);color:#173d30;font:500 12px/1.45 Arial,sans-serif;text-align:center;overflow:hidden;pointer-events:auto;cursor:pointer}
.cms-editor-private-media-card:hover,.cms-editor-private-media-card:focus-visible,.cms-editor-private-media-card.is-selected{border:2px solid #186f4d;outline:none;box-shadow:0 0 0 2px rgba(255,255,255,.8) inset}
.cms-editor-private-media-card strong{display:block;margin-bottom:5px;font-size:13px}.cms-editor-private-media-card small{display:block;opacity:.72;font:400 10px/1.4 monospace}.cms-editor-private-media-action{display:inline-block;margin-top:12px;padding:6px 9px;border-radius:4px;background:#173d30;color:#fff;font:600 10px/1 Arial,sans-serif}
.cms-editor-private-media-card.is-bound{border-style:solid;background:#111;color:#fff}.cms-editor-private-media-card.is-bound img{display:block;width:100%;height:100%;object-fit:contain;background:#111}.cms-editor-private-media-card.is-bound .cms-editor-private-media-label{position:absolute;left:8px;right:8px;bottom:8px;padding:7px;background:rgba(0,0,0,.8);color:#fff;text-align:left}
`;
  doc.head?.append(style);return style;
}
function ensureLayer(doc){let layer=doc.body?.querySelector(':scope > [data-cms-editor-ui="private-media-layer"]');if(layer)return layer;layer=doc.createElement('div');layer.dataset.cmsEditorUi='private-media-layer';doc.body?.append(layer);return layer}
function selectSlot(key){selectedKey=key;render();renderInspector()}
function render(){
  const doc=d(),page=root();if(!doc||!page)return;ensureFrameStyle(doc);ensureParentStyle();const layer=ensureLayer(doc);if(!layer)return;layer.replaceChildren();
  for(const node of page.querySelectorAll('[data-private-media-slot]')){
    const key=slotKey(node);if(!key)continue;const rect=node.getBoundingClientRect();if(rect.width<2)continue;const meta=metaFor(key);
    const card=doc.createElement('button');card.type='button';card.className='cms-editor-private-media-card'+(meta.bound&&meta.src?' is-bound':'')+(selectedKey===key?' is-selected':'');card.dataset.cmsEditorUi='private-media-preview';card.dataset.privateMediaSlot=key;card.setAttribute('aria-label',`${meta.bound?'Trocar':'Escolher'} imagem privada: ${meta.alt||slotAlt(node)}`);
    Object.assign(card.style,{left:`${Math.round(rect.left)}px`,top:`${Math.round(rect.top)}px`,width:`${Math.round(rect.width)}px`,height:`${Math.max(190,Math.round(rect.height))}px`});
    if(meta.bound&&meta.src){const img=doc.createElement('img');img.src=String(meta.src);img.alt=String(meta.alt||slotAlt(node));card.append(img);const label=doc.createElement('div');label.className='cms-editor-private-media-label';const strong=doc.createElement('strong');strong.textContent='Imagem privada vinculada';const action=doc.createElement('span');action.className='cms-editor-private-media-action';action.textContent='Trocar imagem';label.append(strong,action);card.append(label)}
    else{const wrap=doc.createElement('div');const title=doc.createElement('strong');title.textContent='Infográfico pendente';const detail=doc.createElement('small');detail.textContent=String(meta.alt||slotAlt(node));const action=doc.createElement('span');action.className='cms-editor-private-media-action';action.textContent='Escolher imagem';wrap.append(title,detail,action);card.append(wrap)}
    card.addEventListener('click',event=>{event.preventDefault();event.stopPropagation();selectSlot(key)});layer.append(card);
  }
}
function renderInspector(){
  if(!inspector||!selectedKey)return;const meta=metaFor(selectedKey),node=sourceFor(selectedKey);if(!node)return;
  ensureParentStyle();const panel=document.createElement('div');panel.className='cms-private-media-inspector';
  const eyebrow=document.createElement('p');eyebrow.className='eyebrow';eyebrow.textContent='Imagem privada';const title=document.createElement('h2');title.textContent=meta.alt||slotAlt(node);const state=document.createElement('p');state.textContent=meta.bound?'Imagem vinculada a este espaço.':'Nenhuma imagem vinculada a este espaço.';panel.append(eyebrow,title,state);
  if(meta.bound&&meta.src){const preview=document.createElement('div');preview.className='cms-private-media-inspector-preview';const img=document.createElement('img');img.src=meta.src;img.alt=meta.alt||slotAlt(node);preview.append(img);panel.append(preview)}
  const code=document.createElement('div');code.className='cms-private-media-slot-code';code.textContent=`slot: ${selectedKey}`;panel.append(code);
  const actions=document.createElement('div');actions.className='cms-private-media-inspector-actions';const choose=document.createElement('button');choose.type='button';choose.className='primary';choose.textContent=meta.bound?'Trocar imagem':'Escolher imagem';choose.addEventListener('click',()=>openChooser(selectedKey));actions.append(choose);
  if(meta.bound){const unlink=document.createElement('button');unlink.type='button';unlink.className='danger';unlink.textContent='Remover vínculo';unlink.addEventListener('click',async()=>{unlink.disabled=true;try{await mutateSlot(selectedKey,'unbind');notify('Vínculo removido.')}catch(error){notify(error?.message||'Não foi possível remover o vínculo.',true)}finally{unlink.disabled=false}});actions.append(unlink)}
  const library=document.createElement('a');library.href='/admin/media.php';library.target='_blank';library.rel='noopener';library.textContent='Abrir Biblioteca de mídia ↗';actions.append(library);panel.append(actions);inspector.replaceChildren(panel);
}
function ensureDialog(){
  ensureParentStyle();if(dialog?.isConnected)return dialog;dialog=document.createElement('dialog');dialog.className='cms-private-media-dialog';dialog.innerHTML='<header><h2>Escolher imagem privada</h2><button type="button" aria-label="Fechar">×</button></header><div data-private-media-choices></div>';dialog.querySelector('header button').addEventListener('click',()=>dialog.close());document.body.append(dialog);return dialog;
}
function openChooser(key){
  const chooser=ensureDialog(),host=chooser.querySelector('[data-private-media-choices]');host.replaceChildren();const current=Number(metaFor(key).assetId||0);
  if(!images.length){const empty=document.createElement('div');empty.className='cms-private-media-empty';const p=document.createElement('p');p.textContent='Não há imagens privadas disponíveis. Marque uma imagem como privada na Biblioteca de mídia para usá-la aqui.';const link=document.createElement('a');link.href='/admin/media.php';link.target='_blank';link.rel='noopener';link.textContent='Abrir Biblioteca de mídia ↗';empty.append(p,link);host.append(empty)}
  else{const grid=document.createElement('div');grid.className='cms-private-media-grid';for(const image of images){const button=document.createElement('button');button.type='button';button.className='cms-private-media-choice'+(Number(image.id)===current?' is-current':'');const img=document.createElement('img');img.src=String(image.src||'');img.alt='';const label=document.createElement('span');label.textContent=String(image.title||image.originalName||`Imagem ${image.id}`);button.append(img,label);button.addEventListener('click',async()=>{button.disabled=true;try{await mutateSlot(key,'bind',Number(image.id));chooser.close();notify('Imagem privada vinculada.')}catch(error){notify(error?.message||'Não foi possível vincular a imagem.',true)}finally{button.disabled=false}});grid.append(button)}host.append(grid)}
  if(typeof chooser.showModal==='function')chooser.showModal();else chooser.setAttribute('open','');
}
function schedule(){if(scheduled)return;scheduled=true;(frame.contentWindow||window).requestAnimationFrame(()=>{scheduled=false;render()})}
function bind(){
  rootObserver?.disconnect();const doc=d(),page=root();if(!doc||!page)return;rootObserver=new MutationObserver(mutations=>{if(mutations.every(m=>m.target?.closest?.('[data-cms-editor-ui]')))return;schedule()});rootObserver.observe(page,{subtree:true,childList:true,attributes:true,attributeFilter:['data-private-media-slot','data-private-media-alt','class','style']});doc.defaultView?.addEventListener('scroll',schedule,{passive:true});doc.defaultView?.addEventListener('resize',schedule,{passive:true});doc.addEventListener('cms:structure-changed',schedule,true);loadSlots();schedule();
}
frame.addEventListener('load',()=>setTimeout(bind,120));setTimeout(bind,180);
window.CmsPrivateMediaPreview={refresh:()=>{loadSlots();schedule()},render,selectSlot};
})();
