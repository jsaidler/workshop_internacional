(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
if(!frame)return;
const params=new URLSearchParams(location.search);const pageId=Number(params.get('page')||0);
let slots={};let rootObserver=null;let scheduled=false;let loadVersion=0;
const d=()=>frame.contentDocument;
const root=()=>d()?.querySelector('[data-cms-page-main]')||d()?.querySelector('main')||null;
const clean=value=>String(value||'').trim();
const slotKey=node=>clean(node?.getAttribute('data-private-media-slot'));
const slotAlt=node=>clean(node?.getAttribute('data-private-media-alt'))||slotKey(node)||'Infográfico';

async function loadSlots(){
  const version=++loadVersion;
  if(window.CMS_PRIVATE_MEDIA_PREVIEW_DATA&&typeof window.CMS_PRIVATE_MEDIA_PREVIEW_DATA==='object'){
    slots=window.CMS_PRIVATE_MEDIA_PREVIEW_DATA.slots||window.CMS_PRIVATE_MEDIA_PREVIEW_DATA;
    render();return;
  }
  if(pageId<1)return;
  try{
    const response=await fetch(`/admin/api/cms-private-media-slots.php?page=${pageId}`,{credentials:'same-origin',cache:'no-store'});
    if(!response.ok)return;
    const data=await response.json();
    if(version!==loadVersion)return;
    slots=data?.slots&&typeof data.slots==='object'?data.slots:{};
    render();
  }catch{}
}
function ensureStyle(doc){
  let style=doc.head?.querySelector('[data-cms-editor-private-media-style]');
  if(style)return style;
  style=doc.createElement('style');style.dataset.cmsEditorPrivateMediaStyle='1';style.dataset.cmsEditorUi='private-media-style';
  style.textContent=`
[data-private-media-slot]{display:block!important;position:relative!important;min-height:190px!important;box-sizing:border-box!important}
[data-cms-editor-ui="private-media-layer"]{position:fixed;inset:0;pointer-events:none;z-index:2147483000}
.cms-editor-private-media-card{position:fixed;display:grid;place-items:center;box-sizing:border-box;border:1px dashed rgba(30,98,75,.72);background:rgba(245,247,243,.96);color:#173d30;font:500 12px/1.45 Arial,sans-serif;text-align:center;overflow:hidden}
.cms-editor-private-media-card strong{display:block;margin-bottom:5px;font-size:13px}
.cms-editor-private-media-card small{display:block;opacity:.72;font:400 10px/1.4 monospace}
.cms-editor-private-media-card.is-bound{border-style:solid;background:#111}
.cms-editor-private-media-card.is-bound img{display:block;width:100%;height:100%;object-fit:contain;background:#111}
.cms-editor-private-media-card.is-bound .cms-editor-private-media-label{position:absolute;left:8px;bottom:8px;padding:5px 7px;background:rgba(0,0,0,.78);color:#fff;text-align:left}
`;
  doc.head?.append(style);return style;
}
function ensureLayer(doc){
  let layer=doc.body?.querySelector(':scope > [data-cms-editor-ui="private-media-layer"]');
  if(layer)return layer;
  layer=doc.createElement('div');layer.dataset.cmsEditorUi='private-media-layer';doc.body?.append(layer);return layer;
}
function render(){
  const doc=d(),page=root();if(!doc||!page)return;
  ensureStyle(doc);const layer=ensureLayer(doc);if(!layer)return;layer.replaceChildren();
  for(const node of page.querySelectorAll('[data-private-media-slot]')){
    const key=slotKey(node);if(!key)continue;
    const rect=node.getBoundingClientRect();if(rect.width<2)continue;
    const meta=slots[key]||{key,alt:slotAlt(node),bound:false,src:null};
    const card=doc.createElement('div');card.className='cms-editor-private-media-card'+(meta.bound&&meta.src?' is-bound':'');card.dataset.cmsEditorUi='private-media-preview';card.dataset.privateMediaSlot=key;
    Object.assign(card.style,{left:`${Math.round(rect.left)}px`,top:`${Math.round(rect.top)}px`,width:`${Math.round(rect.width)}px`,height:`${Math.max(190,Math.round(rect.height))}px`});
    if(meta.bound&&meta.src){
      const img=doc.createElement('img');img.src=String(meta.src);img.alt=String(meta.alt||slotAlt(node));card.append(img);
      const label=doc.createElement('div');label.className='cms-editor-private-media-label';label.innerHTML=`<strong>Mídia privada vinculada</strong><small>${String(meta.alt||slotAlt(node)).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}<br>slot: ${key.replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}</small>`;card.append(label);
    }else{
      const wrap=doc.createElement('div');const title=doc.createElement('strong');title.textContent='Infográfico pendente';const detail=doc.createElement('small');detail.textContent=`${meta.alt||slotAlt(node)}\nslot: ${key}`;detail.style.whiteSpace='pre-line';wrap.append(title,detail);card.append(wrap);
    }
    layer.append(card);
  }
}
function schedule(){if(scheduled)return;scheduled=true;requestAnimationFrame(()=>{scheduled=false;render()})}
function bind(){
  rootObserver?.disconnect();const doc=d(),page=root();if(!doc||!page)return;
  rootObserver=new MutationObserver(mutations=>{
    if(mutations.every(m=>m.target?.closest?.('[data-cms-editor-ui]')))return;
    schedule();
  });
  rootObserver.observe(page,{subtree:true,childList:true,attributes:true,attributeFilter:['data-private-media-slot','data-private-media-alt','class','style']});
  doc.defaultView?.addEventListener('scroll',schedule,{passive:true});doc.defaultView?.addEventListener('resize',schedule,{passive:true});
  doc.addEventListener('cms:structure-changed',schedule,true);
  loadSlots();schedule();
}
frame.addEventListener('load',()=>setTimeout(bind,120));
setTimeout(bind,180);
window.CmsPrivateMediaPreview={refresh:()=>{loadSlots();schedule()},render};
})();
