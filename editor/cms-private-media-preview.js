(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const inspector=document.querySelector('#inspector');
const toast=document.querySelector('#toast');
if(!frame)return;
const params=new URLSearchParams(location.search);const pageId=Number(params.get('page')||0);
let state={pageId,csrf:'',images:[],slots:{}};let rootObserver=null,scheduled=false,loadVersion=0,activeSlot='';
const d=()=>frame.contentDocument;
const root=()=>d()?.querySelector('[data-cms-page-main]')||d()?.querySelector('main')||null;
const clean=value=>String(value||'').trim();
const slotKey=node=>clean(node?.getAttribute('data-private-media-slot'));
const slotAlt=node=>clean(node?.getAttribute('data-private-media-alt'))||slotKey(node)||'Infográfico';
function say(message){if(!toast)return;toast.textContent=message;toast.classList.add('show');clearTimeout(say.t);say.t=setTimeout(()=>toast.classList.remove('show'),2200)}

async function loadSlots(){
  const version=++loadVersion;
  if(window.CMS_PRIVATE_MEDIA_PREVIEW_DATA&&typeof window.CMS_PRIVATE_MEDIA_PREVIEW_DATA==='object'){
    const fixture=window.CMS_PRIVATE_MEDIA_PREVIEW_DATA;state={pageId:Number(fixture.pageId||pageId),csrf:String(fixture.csrf||''),images:Array.isArray(fixture.images)?fixture.images:[],slots:fixture.slots||fixture};render();return state;
  }
  if(pageId<1)return state;
  try{
    const response=await fetch(`/admin/api/cms-private-media-slots.php?page=${pageId}`,{credentials:'same-origin',cache:'no-store'});
    if(!response.ok)throw new Error('Não foi possível carregar os espaços de mídia privada.');
    const data=await response.json();if(version!==loadVersion)return state;
    state={pageId:Number(data.pageId||pageId),csrf:String(data.csrf||''),images:Array.isArray(data.images)?data.images:[],slots:data.slots&&typeof data.slots==='object'?data.slots:{}};render();return state;
  }catch(error){say(error.message);return state}
}
async function mutate(payload){
  if(typeof window.CMS_PRIVATE_MEDIA_PREVIEW_MUTATE==='function'){
    const data=await window.CMS_PRIVATE_MEDIA_PREVIEW_MUTATE(payload);state={pageId:Number(data.pageId||state.pageId),csrf:String(data.csrf||state.csrf),images:Array.isArray(data.images)?data.images:state.images,slots:data.slots||state.slots};return state;
  }
  const response=await fetch('/admin/api/cms-private-media-slots.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({...payload,pageId:state.pageId||pageId,csrf:state.csrf})});
  const data=await response.json().catch(()=>({}));if(!response.ok)throw new Error(data?.error?.code||'Não foi possível atualizar a imagem privada.');
  state={pageId:Number(data.pageId||state.pageId),csrf:String(data.csrf||state.csrf),images:Array.isArray(data.images)?data.images:[],slots:data.slots||{}};return state;
}
function ensureStyle(doc){
  let style=doc.head?.querySelector('[data-cms-editor-private-media-style]');if(style)return style;
  style=doc.createElement('style');style.dataset.cmsEditorPrivateMediaStyle='1';style.dataset.cmsEditorUi='private-media-style';
  style.textContent=`
[data-private-media-slot]{display:block!important;position:relative!important;min-height:190px!important;box-sizing:border-box!important}
[data-cms-editor-ui="private-media-layer"]{position:fixed;inset:0;pointer-events:none;z-index:2147483300;font-family:Arial,sans-serif}
.cms-editor-private-media-card{position:fixed;display:grid;place-items:center;box-sizing:border-box;border:2px dashed rgba(30,98,75,.78);background:rgba(245,247,243,.97);color:#173d30;text-align:center;overflow:hidden;pointer-events:auto;cursor:pointer;outline:0}
.cms-editor-private-media-card:hover,.cms-editor-private-media-card:focus-visible{border-style:solid;box-shadow:0 0 0 3px rgba(30,98,75,.18)}
.cms-editor-private-media-card strong{display:block;margin-bottom:5px;font:600 13px/1.35 Arial,sans-serif}
.cms-editor-private-media-card small{display:block;max-width:min(780px,90%);opacity:.72;font:400 10px/1.45 monospace;white-space:pre-line}
.cms-editor-private-media-card .cms-editor-private-media-action{display:inline-block;margin-top:11px;padding:7px 10px;border-radius:999px;background:#173d30;color:#fff;font:600 10px/1 Arial,sans-serif}
.cms-editor-private-media-card.is-bound{border-style:solid;background:#111;color:#fff}
.cms-editor-private-media-card.is-bound img{display:block;width:100%;height:100%;object-fit:contain;background:#111;pointer-events:none}
.cms-editor-private-media-card.is-bound .cms-editor-private-media-label{position:absolute;left:8px;bottom:8px;padding:6px 8px;background:rgba(0,0,0,.78);color:#fff;text-align:left;border-radius:3px}
`;
  doc.head?.append(style);return style;
}
function ensureOuterStyle(){
  if(document.querySelector('style[data-cms-private-media-dialog-style]'))return;
  const style=document.createElement('style');style.dataset.cmsPrivateMediaDialogStyle='1';style.textContent=`
.cms-private-media-dialog{width:min(860px,calc(100vw - 40px));max-height:calc(100vh - 60px);padding:0;border:1px solid #b9bbb5;border-radius:7px;background:#fff;color:#171817;box-shadow:0 24px 70px rgba(0,0,0,.25)}
.cms-private-media-dialog::backdrop{background:rgba(0,0,0,.45)}
.cms-private-media-dialog header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:18px 20px;border-bottom:1px solid #ddd;background:#f7f7f3}.cms-private-media-dialog h2{margin:2px 0 0;font-size:20px}.cms-private-media-dialog p{margin:0;color:#696b66;font-size:12px}.cms-private-media-dialog header button{border:0;background:transparent;font-size:25px;cursor:pointer}
.cms-private-media-tools{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 20px;border-bottom:1px solid #e4e4df}.cms-private-media-tools label{display:inline-flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid #bbb;border-radius:4px;background:#fff;font:600 12px Arial,sans-serif;cursor:pointer}.cms-private-media-tools input{max-width:240px}.cms-private-media-tools a{font:600 12px Arial,sans-serif;color:inherit}
.cms-private-media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px;padding:18px 20px;overflow:auto;max-height:60vh}.cms-private-media-choice{padding:0;border:1px solid #d7d8d3;border-radius:5px;background:#fff;color:inherit;text-align:left;overflow:hidden;cursor:pointer}.cms-private-media-choice:hover,.cms-private-media-choice:focus-visible{border-color:#171817;outline:2px solid rgba(30,98,75,.18)}.cms-private-media-choice img{display:block;width:100%;aspect-ratio:4/3;object-fit:contain;background:#111}.cms-private-media-choice span{display:block;padding:9px 10px;font:600 12px/1.3 Arial,sans-serif;overflow-wrap:anywhere}.cms-private-media-empty{grid-column:1/-1;padding:30px;border:1px dashed #bbb;text-align:center;color:#696b66}
`;
  document.head.append(style);
}
function ensureLayer(doc){let layer=doc.body?.querySelector(':scope > [data-cms-editor-ui="private-media-layer"]');if(layer)return layer;layer=doc.createElement('div');layer.dataset.cmsEditorUi='private-media-layer';doc.body?.append(layer);return layer}
function render(){
  const doc=d(),page=root();if(!doc||!page)return;ensureStyle(doc);const layer=ensureLayer(doc);if(!layer)return;layer.replaceChildren();
  for(const node of page.querySelectorAll('[data-private-media-slot]')){
    const key=slotKey(node);if(!key)continue;const rect=node.getBoundingClientRect();if(rect.width<2)continue;
    const meta=state.slots[key]||{key,alt:slotAlt(node),bound:false,assetId:null,src:null};const card=doc.createElement('div');card.className='cms-editor-private-media-card'+(meta.bound&&meta.src?' is-bound':'');card.dataset.cmsEditorUi='private-media-preview';card.dataset.privateMediaSlot=key;card.tabIndex=0;card.setAttribute('role','button');card.setAttribute('aria-label',`${meta.bound?'Trocar':'Escolher'} imagem para ${meta.alt||slotAlt(node)}`);
    Object.assign(card.style,{left:`${Math.round(rect.left)}px`,top:`${Math.round(rect.top)}px`,width:`${Math.round(rect.width)}px`,height:`${Math.max(190,Math.round(rect.height))}px`});
    if(meta.bound&&meta.src){const img=doc.createElement('img');img.src=String(meta.src);img.alt=String(meta.alt||slotAlt(node));card.append(img);const label=doc.createElement('div');label.className='cms-editor-private-media-label';const title=doc.createElement('strong');title.textContent='Imagem privada vinculada';const action=doc.createElement('small');action.textContent='Clique para trocar ou remover';label.append(title,action);card.append(label)}
    else{const wrap=doc.createElement('div'),title=doc.createElement('strong'),detail=doc.createElement('small'),action=doc.createElement('span');title.textContent='Infográfico pendente';detail.textContent=`${meta.alt||slotAlt(node)}\nslot: ${key}`;action.className='cms-editor-private-media-action';action.textContent='Escolher imagem';wrap.append(title,detail,action);card.append(wrap)}
    const activate=event=>{event.preventDefault();event.stopPropagation();activeSlot=key;renderInspector(key)};card.addEventListener('click',activate);card.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();activate(event)}});layer.append(card);
  }
}
function renderInspector(key){
  if(!inspector)return;const meta=state.slots[key]||{key,alt:key,bound:false};
  inspector.innerHTML=`<div class="inspector-section"><header><p class="eyebrow">Imagem privada</p><h2>${escapeHtml(meta.alt||key)}</h2></header><p class="inspector-note">Este espaço pertence ao material protegido. A imagem é vinculada pela Biblioteca de mídia sem transformar o slot em uma imagem pública da página.</p><p class="inspector-note"><strong>slot:</strong> ${escapeHtml(key)}</p><p class="inspector-note">${meta.bound?'Imagem vinculada a este espaço.':'Nenhuma imagem vinculada a este espaço.'}</p><div class="button-row"><button type="button" class="primary" data-private-media-choose>${meta.bound?'Trocar imagem':'Escolher imagem'}</button>${meta.bound?'<button type="button" class="danger" data-private-media-unbind>Remover vínculo</button>':''}</div></div>`;
  inspector.querySelector('[data-private-media-choose]')?.addEventListener('click',()=>openChooser(key));
  inspector.querySelector('[data-private-media-unbind]')?.addEventListener('click',async()=>{try{await mutate({action:'unbind',slotKey:key});render();renderInspector(key);say('Vínculo removido.')}catch(error){say(error.message)}});
}
function escapeHtml(value){return String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}
function closeChooser(dialog){if(dialog?.open)dialog.close();dialog?.remove()}
async function bindImage(key,assetId,dialog){try{await mutate({action:'bind',slotKey:key,assetId:Number(assetId)});render();renderInspector(key);closeChooser(dialog);say('Imagem vinculada ao material.')}catch(error){say(error.message)}}
async function uploadAndBind(key,file,dialog){
  if(!file||!window.MediaLibrary)throw new Error('Envio direto indisponível. Abra a Biblioteca de mídia.');
  await window.MediaLibrary.load();const item=await window.MediaLibrary.upload(file);const response=await fetch('/admin/api/media-visibility.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:window.MediaLibrary.csrf,assetId:Number(item.id),visibility:'private'})});const body=await response.json().catch(()=>({}));if(!response.ok)throw new Error(body?.error||'Não foi possível tornar a imagem privada.');await loadSlots();await bindImage(key,item.id,dialog);
}
function openChooser(key){
  ensureOuterStyle();document.querySelectorAll('.cms-private-media-dialog').forEach(node=>node.remove());const meta=state.slots[key]||{alt:key};const dialog=document.createElement('dialog');dialog.className='cms-private-media-dialog';dialog.innerHTML=`<header><div><p>Imagem privada</p><h2>${escapeHtml(meta.alt||key)}</h2></div><button type="button" aria-label="Fechar">×</button></header><div class="cms-private-media-tools"><label>Enviar nova imagem <input type="file" accept="image/jpeg,image/png,image/webp" data-private-media-upload></label><a href="/admin/media.php" target="_blank" rel="noopener">Abrir Biblioteca de mídia ↗</a></div><div class="cms-private-media-grid"></div>`;document.body.append(dialog);const grid=dialog.querySelector('.cms-private-media-grid');const draw=()=>{grid.innerHTML='';if(!state.images.length){const empty=document.createElement('div');empty.className='cms-private-media-empty';empty.textContent='Nenhuma imagem privada disponível. Envie uma imagem aqui ou marque uma imagem como privada na Biblioteca de mídia.';grid.append(empty);return}for(const image of state.images){const button=document.createElement('button');button.type='button';button.className='cms-private-media-choice';button.innerHTML=`<img src="${escapeHtml(image.src||'')}" alt=""><span>${escapeHtml(image.title||image.originalName||'Imagem')}</span>`;button.addEventListener('click',()=>bindImage(key,image.id,dialog));grid.append(button)}};draw();dialog.querySelector('header button').onclick=()=>closeChooser(dialog);dialog.addEventListener('cancel',event=>{event.preventDefault();closeChooser(dialog)});const input=dialog.querySelector('[data-private-media-upload]');if(!window.MediaLibrary)input.closest('label').hidden=true;else input.addEventListener('change',async event=>{const file=event.target.files?.[0];if(!file)return;input.disabled=true;try{await uploadAndBind(key,file,dialog)}catch(error){say(error.message);input.disabled=false;input.value=''}});if(typeof dialog.showModal==='function')dialog.showModal();else dialog.setAttribute('open','');
}
function schedule(){if(scheduled)return;scheduled=true;(frame.contentWindow||window).requestAnimationFrame(()=>{scheduled=false;render()})}
function bind(){
  rootObserver?.disconnect();const doc=d(),page=root();if(!doc||!page)return;rootObserver=new MutationObserver(mutations=>{if(mutations.every(m=>m.target?.closest?.('[data-cms-editor-ui]')))return;schedule()});rootObserver.observe(page,{subtree:true,childList:true,attributes:true,attributeFilter:['data-private-media-slot','data-private-media-alt','class','style']});doc.defaultView?.addEventListener('scroll',schedule,{passive:true});doc.defaultView?.addEventListener('resize',schedule,{passive:true});doc.addEventListener('cms:structure-changed',schedule,true);loadSlots();schedule();
}
frame.addEventListener('load',()=>setTimeout(bind,120));setTimeout(bind,180);
window.CmsPrivateMediaPreview={refresh:()=>{loadSlots();schedule()},render,renderInspector,openChooser};
})();
