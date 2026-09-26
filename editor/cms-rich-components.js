(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const inspector=document.querySelector('#inspector');
const saveButton=document.querySelector('#save-page');
const pageTree=document.querySelector('#page-structure-tree');
if(!frame||!inspector||!saveButton)return;

const TYPES={
  list:{label:'Lista'},
  quote:{label:'Citação'},
  video:{label:'Vídeo'},
  gallery:{label:'Galeria'}
};
const d=()=>frame.contentDocument;
const root=()=>d()?.querySelector('[data-cms-page-main]')||d()?.querySelector('main')||null;
const uid=prefix=>`${prefix}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2,8)}`;
let saveTimer=0,frameObserver=null,enhanceTick=false;

function richType(node){const type=node?.dataset?.cmsComponent||'';return TYPES[type]?type:''}
function richLabel(node){return TYPES[richType(node)]?.label||''}
function ensureNodeId(node){if(node&&!node.dataset.cmsNodeId)node.dataset.cmsNodeId=uid(node.dataset.cmsComponent||'component');return node?.dataset?.cmsNodeId||''}
function remember(node){const id=ensureNodeId(node);if(id)sessionStorage.setItem('cms-editor-reselect',id)}
function saveAndReload(node){
  remember(node);
  clearTimeout(saveTimer);
  saveTimer=setTimeout(()=>{
    saveButton.click();
    setTimeout(()=>{try{frame.contentWindow.location.reload()}catch{}},650);
  },70);
}
function saveSoon(){clearTimeout(saveTimer);saveTimer=setTimeout(()=>saveButton.click(),220)}
function hiddenLabel(doc,label){const span=doc.createElement('span');span.hidden=true;span.setAttribute('aria-hidden','true');span.dataset.cmsComponentLabel='';span.textContent=label;return span}
function mediaSlot(doc){const item=doc.createElement('div');item.className='cms-gallery-item';item.style.minWidth='0';const slot=doc.createElement('div');slot.className='cms-media-placeholder';slot.dataset.cmsImagePlaceholder='';slot.style.minHeight='180px';slot.innerHTML='<span>Adicionar imagem</span>';item.append(slot);return item}
function galleryColumns(size){return size==='s'?160:size==='l'?320:220}
function applyGalleryLayout(node,size='m'){
  node.dataset.cmsGallerySize=size;
  node.style.display='grid';
  node.style.gridTemplateColumns=`repeat(auto-fit,minmax(min(${galleryColumns(size)}px,100%),1fr))`;
  node.style.gap='12px';
  node.style.alignItems='start';
}
function createComponent(type){
  const doc=d();if(!doc||!TYPES[type])return null;
  let el;
  if(type==='list'){
    el=doc.createElement('div');el.className='cms-component-list';el.dataset.cmsComponent='list';el.append(hiddenLabel(doc,'Lista'));
    const list=doc.createElement('ul');
    ['Primeiro item','Segundo item','Terceiro item'].forEach(text=>{const li=doc.createElement('li');li.dataset.cmsEditable='';li.textContent=text;list.append(li)});
    el.append(list);
  }else if(type==='quote'){
    el=doc.createElement('figure');el.className='cms-component-quote';el.dataset.cmsComponent='quote';el.style.cssText='margin:0;padding:clamp(18px,3vw,36px) 0 clamp(18px,3vw,36px) clamp(18px,3vw,36px);border-left:1px solid currentColor';el.append(hiddenLabel(doc,'Citação'));
    const block=doc.createElement('blockquote');block.style.margin='0';const p=doc.createElement('p');p.dataset.cmsEditable='';p.textContent='Escreva a citação.';block.append(p);el.append(block);
    const cite=doc.createElement('figcaption');cite.dataset.cmsEditable='';cite.style.cssText='margin-top:12px;opacity:.72';cite.textContent='Autoria ou referência';el.append(cite);
  }else if(type==='video'){
    el=doc.createElement('figure');el.className='cms-component-video';el.dataset.cmsComponent='video';el.style.margin='0';el.append(hiddenLabel(doc,'Vídeo'));
    const video=doc.createElement('video');video.dataset.cmsVideo='';video.controls=true;video.playsInline=true;video.preload='metadata';video.style.cssText='display:block;width:100%;min-height:240px;background:#111';el.append(video);
    const caption=doc.createElement('figcaption');caption.dataset.cmsEditable='';caption.style.cssText='margin-top:10px;opacity:.72';caption.textContent='Legenda do vídeo';el.append(caption);
  }else if(type==='gallery'){
    el=doc.createElement('div');el.className='cms-component-gallery';el.dataset.cmsComponent='gallery';el.append(hiddenLabel(doc,'Galeria'));
    for(let i=0;i<3;i++)el.append(mediaSlot(doc));
    applyGalleryLayout(el,'m');
  }
  el.dataset.cmsNodeId=uid(type);
  return el;
}
function selectedCore(){
  const doc=d();
  return doc?.querySelector('.cms-editing[data-cms-editable],.cms-selection[data-cms-editable],.cms-selection[data-cms-image],.cms-selection[data-cms-image-placeholder],video.cms-selection')||null;
}
function selectedStructure(){return d()?.querySelector('.cms-structure-selected')||null}
function selectedSection(){return d()?.querySelector('.cms-section-selected[data-cms-section]')||selectedStructure()?.closest('[data-cms-section]')||selectedCore()?.closest('[data-cms-section]')||null}
function componentRoot(node){return node?.closest?.('[data-cms-component]')||node?.closest?.('[data-cms-image-block]')||node||null}
function preferredContainer(section){
  const selectors=['.cms-free-cell','.statement-copy','.apparatus-copy','.about-copy','.interest-intro','.format-heading','.process-copy','.hero-copy','.cms-proof-inner','.cms-support-inner','.format-inner','.cms-stack','.cms-inner'];
  for(const selector of selectors){const matches=[...section.querySelectorAll(selector)].filter(el=>!el.closest('[data-cms-form-block]'));if(matches.length===1)return matches[0]}
  return section;
}
function positionFor(source){
  if(source?.ownerDocument===document){
    const host=source.closest('.cms-structure-inspector,.cms-component-editor,.inspector-section')||inspector;
    const active=host.querySelector('[data-insert-position].active');
    if(active)return active.dataset.insertPosition==='before'?'before':'after';
  }
  const expanded=d()?.querySelector('.cms-inline-add[aria-expanded="true"]');
  const label=(expanded?.textContent||'').toLowerCase();
  if(label.includes('antes'))return'before';
  if(label.includes('depois'))return'after';
  return'after';
}
function insertionContext(source){
  const structural=selectedStructure();
  if(structural){
    if(structural.hasAttribute('data-cms-column')||structural.dataset.cmsContainer==='stack')return{mode:'append',target:structural};
    return{mode:positionFor(source),target:structural};
  }
  const selected=selectedCore();
  if(selected){const block=componentRoot(selected);if(block?.parentElement)return{mode:positionFor(source),target:block}}
  const section=selectedSection();
  return section?{mode:'append',target:preferredContainer(section)}:null;
}
function insert(type,source){
  const node=createComponent(type),ctx=insertionContext(source);if(!node||!ctx?.target)return;
  if(ctx.mode==='append')ctx.target.append(node);else if(ctx.mode==='before')ctx.target.before(node);else ctx.target.after(node);
  d()?.querySelector('.cms-inline-palette')?.remove();
  saveAndReload(node);
}
function addRichButtons(host,inline=false){
  if(!host||host.dataset.cmsRichReady==='1')return;
  host.dataset.cmsRichReady='1';
  for(const [type,{label}] of Object.entries(TYPES)){
    const button=host.ownerDocument.createElement('button');button.type='button';button.textContent=label;
    if(inline)button.dataset.richInlineType=type;else button.dataset.richComponent=type;
    button.addEventListener('click',event=>{event.preventDefault();event.stopPropagation();insert(type,button)});
    host.append(button);
  }
}
function listElement(node){if(!node)return null;if(node.matches?.('ul,ol'))return node;return node.querySelector?.(':scope > ul,:scope > ol')||node.querySelector?.('ul,ol')||null}
function addListItem(node){const list=listElement(node);if(!list)return;const li=d().createElement('li');li.dataset.cmsEditable='';li.textContent='Novo item';list.append(li);saveAndReload(node.matches('ul,ol')?node:node)}
function removeListItem(node){const list=listElement(node),item=list?.lastElementChild;if(!list||!item)return;if(list.children.length<=1){alert('A lista precisa ter pelo menos um item.');return}if(confirm('Remover o último item da lista?')){item.remove();saveAndReload(node)}}
function changeListType(node,tag){
  const list=listElement(node);if(!list||!['ul','ol'].includes(tag)||list.tagName.toLowerCase()===tag)return;
  const replacement=d().createElement(tag);
  for(const attr of [...list.attributes])replacement.setAttribute(attr.name,attr.value);
  while(list.firstChild)replacement.append(list.firstChild);
  if(list===node){list.replaceWith(replacement);saveAndReload(replacement)}else{list.replaceWith(replacement);saveAndReload(node)}
}
function galleryItems(node){return [...node.querySelectorAll(':scope > .cms-gallery-item')]}
function addGalleryItem(node){node.append(mediaSlot(d()));saveAndReload(node)}
function removeGalleryItem(node){const items=galleryItems(node),item=items.at(-1);if(!item)return;if(items.length<=1){alert('A galeria precisa ter pelo menos uma imagem.');return}const hasContent=!!item.querySelector('img,[data-cms-image-block]');if(hasContent&&!confirm('Remover a última imagem da galeria?'))return;item.remove();saveAndReload(node)}
function changeGallerySize(node,size){applyGalleryLayout(node,['s','m','l'].includes(size)?size:'m');saveSoon()}
function openVideoEditor(node){const video=node.querySelector('video');if(!video)return;video.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true,view:frame.contentWindow}))}
function specialMarkup(node){
  const type=richType(node);
  if(type==='list'){
    const list=listElement(node),ordered=list?.tagName.toLowerCase()==='ol';
    return `<div class="cms-rich-controls"><hr><p class="eyebrow">Lista</p><label>Formato<select data-rich-list-type><option value="ul" ${ordered?'':'selected'}>Com marcadores</option><option value="ol" ${ordered?'selected':''}>Numerada</option></select></label><div class="button-row"><button type="button" data-rich-list-add>+ Item</button><button type="button" data-rich-list-remove>− Item</button></div></div>`;
  }
  if(type==='quote')return '<div class="cms-rich-controls"><hr><p class="eyebrow">Citação</p><p class="inspector-note">Edite a citação e a autoria diretamente na página.</p></div>';
  if(type==='video')return '<div class="cms-rich-controls"><hr><p class="eyebrow">Vídeo</p><p class="inspector-note">Escolha o arquivo, a capa e as opções de reprodução no editor do vídeo.</p><button type="button" class="panel-button" data-rich-video-edit>Editar vídeo</button></div>';
  if(type==='gallery'){
    const size=node.dataset.cmsGallerySize||'m';
    return `<div class="cms-rich-controls"><hr><p class="eyebrow">Galeria</p><p class="inspector-note">${galleryItems(node).length} posições de imagem.</p><label>Tamanho das imagens<select data-rich-gallery-size><option value="s" ${size==='s'?'selected':''}>Compactas</option><option value="m" ${size==='m'?'selected':''}>Médias</option><option value="l" ${size==='l'?'selected':''}>Grandes</option></select></label><div class="button-row"><button type="button" data-rich-gallery-add>+ Imagem</button><button type="button" data-rich-gallery-remove>− Imagem</button></div></div>`;
  }
  return'';
}
function enhanceInspector(){
  inspector.querySelectorAll('.cms-component-library').forEach(host=>addRichButtons(host,false));
  const panel=inspector.querySelector('.cms-structure-inspector');
  const node=selectedStructure(),type=richType(node);
  if(!panel||!node||!type)return;
  const title=panel.querySelector('header h2');if(title&&title.textContent!==TYPES[type].label)title.textContent=TYPES[type].label;
  if(!panel.querySelector('.cms-rich-controls')){
    const actions=panel.querySelector('.cms-block-actions');
    const wrapper=document.createElement('div');wrapper.innerHTML=specialMarkup(node);const controls=wrapper.firstElementChild;if(controls)actions?.after(controls);
  }
  const listAdd=panel.querySelector('[data-rich-list-add]');if(listAdd)listAdd.onclick=()=>addListItem(node);
  const listRemove=panel.querySelector('[data-rich-list-remove]');if(listRemove)listRemove.onclick=()=>removeListItem(node);
  const listType=panel.querySelector('[data-rich-list-type]');if(listType)listType.onchange=event=>changeListType(node,event.currentTarget.value);
  const galleryAdd=panel.querySelector('[data-rich-gallery-add]');if(galleryAdd)galleryAdd.onclick=()=>addGalleryItem(node);
  const galleryRemove=panel.querySelector('[data-rich-gallery-remove]');if(galleryRemove)galleryRemove.onclick=()=>removeGalleryItem(node);
  const gallerySize=panel.querySelector('[data-rich-gallery-size]');if(gallerySize)gallerySize.onchange=event=>changeGallerySize(node,event.currentTarget.value);
  const videoEdit=panel.querySelector('[data-rich-video-edit]');if(videoEdit)videoEdit.onclick=()=>openVideoEditor(node);
  enhanceInspectorTree(node.closest('[data-cms-section]'));
}
function collect(parent,depth=0,out=[]){
  for(const child of parent?.children||[]){
    if(child.hasAttribute('data-cms-component')){
      out.push({node:child,depth});
      if(child.dataset.cmsContainer==='columns')for(const col of child.querySelectorAll(':scope > [data-cms-column]')){out.push({node:col,depth:depth+1});collect(col,depth+2,out)}
      else if(child.dataset.cmsContainer==='stack')collect(child,depth+1,out);
    }else collect(child,depth,out);
  }
  return out;
}
function enhanceInspectorTree(section){
  if(!section)return;const entries=collect(section);
  inspector.querySelectorAll('.cms-structure-tree [data-tree-row]').forEach(row=>{
    const node=entries[Number(row.dataset.treeRow)]?.node,label=richLabel(node);if(!label)return;
    const strong=row.querySelector('strong');if(strong)strong.textContent=label;
  });
}
function enhancePageTree(){
  if(!pageTree)return;const pageRoot=root();if(!pageRoot)return;
  const sections=[...pageRoot.children].filter(el=>el.matches('[data-cms-section]'));
  pageTree.querySelectorAll('[data-page-tree-node][data-page-tree-section]').forEach(button=>{
    const section=sections[Number(button.dataset.pageTreeSection)];if(!section)return;
    const node=collect(section)[Number(button.dataset.pageTreeNode)]?.node,label=richLabel(node);if(!label)return;
    const strong=button.querySelector('strong');if(strong)strong.textContent=label;
    const type=button.closest('.cms-page-tree-row')?.querySelector('.cms-page-tree-type');if(type)type.textContent=label;
  });
}
function enhanceInlinePalette(){d()?.querySelectorAll('.cms-inline-palette-grid').forEach(host=>addRichButtons(host,true))}
function scheduleEnhance(){if(enhanceTick)return;enhanceTick=true;requestAnimationFrame(()=>{enhanceTick=false;enhanceInspector();enhancePageTree();enhanceInlinePalette()})}
function installFrame(){
  frameObserver?.disconnect();const doc=d();if(!doc?.body)return;
  doc.addEventListener('pointerdown',event=>{if(event.target?.closest?.('video[data-cms-video]'))doc.querySelectorAll('.cms-structure-selected').forEach(node=>node.classList.remove('cms-structure-selected'))},true);
  frameObserver=new MutationObserver(scheduleEnhance);frameObserver.observe(doc.body,{subtree:true,childList:true,attributes:true,attributeFilter:['class','data-cms-component']});
  scheduleEnhance();
}

new MutationObserver(scheduleEnhance).observe(inspector,{subtree:true,childList:true});
if(pageTree)new MutationObserver(scheduleEnhance).observe(pageTree,{subtree:true,childList:true});
frame.addEventListener('load',()=>setTimeout(installFrame,150));
setTimeout(installFrame,200);
window.CmsRichComponents={createComponent};
})();
