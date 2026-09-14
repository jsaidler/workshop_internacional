(()=>{
'use strict';
const frame=document.querySelector('#page-frame'),inspector=document.querySelector('#inspector'),save=document.querySelector('#save-page');
if(!frame||!inspector||!save)return;
let saveTimer=0;
const doc=()=>frame.contentDocument;
const uid=prefix=>`${prefix}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2,8)}`;
function scheduleSave(reload=false){
  clearTimeout(saveTimer);
  saveTimer=setTimeout(()=>{
    save.click();
    if(reload)setTimeout(()=>{try{frame.contentWindow.location.reload()}catch{}},650);
  },100);
}
function selectedStructure(){return doc()?.querySelector('.cms-structure-selected')||null}
function selectedSection(){return doc()?.querySelector('.cms-section-selected[data-cms-section]')||null}
function selectedContent(){return doc()?.querySelector('.cms-editing[data-cms-editable],.cms-selection[data-cms-editable],.cms-selection[data-cms-image],.cms-selection[data-cms-image-placeholder]')||null}
function structuralRoot(node){return node?.closest?.('[data-cms-component]')||node?.closest?.('[data-cms-image-block]')||node||null}
function activePosition(){return inspector.querySelector('.cms-insert-position [data-insert-position].active')?.dataset.insertPosition||'after'}
function insertionContext(){
  const structural=selectedStructure();
  if(structural){
    if(structural.hasAttribute('data-cms-column')||structural.dataset.cmsContainer==='stack')return{kind:'append',parent:structural};
    if(structural.dataset.cmsContainer==='columns'){
      const first=structural.querySelector(':scope > [data-cms-column]');
      if(first)return{kind:'append',parent:first};
    }
    if(structural.parentElement)return{kind:'sibling',target:structural};
  }
  const content=selectedContent();
  if(content){const root=structuralRoot(content);if(root?.parentElement)return{kind:'sibling',target:root}}
  const section=selectedSection();
  if(section)return{kind:'append',parent:section};
  return null;
}
function create(type){
  const d=doc();if(!d)return null;
  let node=null;
  if(type==='list'){
    node=d.createElement('div');node.className='cms-component-list';node.dataset.cmsComponent='list';
    node.innerHTML='<ul><li data-cms-editable>Primeiro item</li><li data-cms-editable>Segundo item</li><li data-cms-editable>Terceiro item</li></ul>';
  }else if(type==='quote'){
    node=d.createElement('blockquote');node.className='cms-component-quote';node.dataset.cmsComponent='quote';
    node.innerHTML='<p data-cms-editable>Escreva a citação.</p><cite data-cms-editable>Autoria ou referência</cite>';
  }else if(type==='video'){
    node=d.createElement('div');node.className='cms-component-video';node.dataset.cmsComponent='video';
    node.innerHTML='<video data-cms-video controls playsinline preload="metadata">Vídeo</video>';
  }else if(type==='gallery'){
    node=d.createElement('div');node.className='cms-component-gallery';node.dataset.cmsComponent='gallery';node.dataset.cmsGalleryColumns='2';
    node.innerHTML='<div class="cms-gallery-grid">'+[1,2,3,4].map(()=>'<div class="cms-gallery-item"><div class="cms-media-placeholder" data-cms-image-placeholder><span>Adicionar imagem</span></div></div>').join('')+'</div>';
  }
  if(node)node.dataset.cmsNodeId=uid(type);
  return node;
}
function insert(type){
  const ctx=insertionContext(),node=create(type);if(!ctx||!node)return;
  if(ctx.kind==='append')ctx.parent.append(node);
  else activePosition()==='before'?ctx.target.before(node):ctx.target.after(node);
  sessionStorage.setItem('cms-editor-reselect',node.dataset.cmsNodeId);
  scheduleSave(true);
}
function addButtons(){
  inspector.querySelectorAll('.cms-component-library:not([data-rich-bound])').forEach(library=>{
    library.dataset.richBound='1';
    const additions=[['list','Lista'],['quote','Citação'],['video','Vídeo'],['gallery','Galeria']];
    additions.forEach(([type,label])=>{
      const button=document.createElement('button');button.type='button';button.dataset.addRichComponent=type;button.textContent=label;
      button.addEventListener('click',event=>{event.preventDefault();insert(type)});
      library.append(button);
    });
  });
}
function collectTree(parent,depth=0,out=[]){
  for(const child of parent.children){
    if(child.hasAttribute('data-cms-component')){
      out.push(child);
      if(child.dataset.cmsContainer==='columns'){
        for(const col of child.querySelectorAll(':scope > [data-cms-column]')){out.push(col);collectTree(col,depth+2,out)}
      }else if(child.dataset.cmsContainer==='stack')collectTree(child,depth+1,out);
    }else collectTree(child,depth,out);
  }
  return out;
}
function relabelTree(){
  const section=selectedSection()||selectedStructure()?.closest('[data-cms-section]');
  const tree=inspector.querySelector('.cms-structure-tree');if(!section||!tree)return;
  const nodes=collectTree(section),labels={list:'Lista',quote:'Citação',video:'Vídeo',gallery:'Galeria'};
  tree.querySelectorAll('[data-tree-row]').forEach(row=>{
    const node=nodes[Number(row.dataset.treeRow)],label=labels[node?.dataset?.cmsComponent||''];
    if(label){const strong=row.querySelector('strong');if(strong)strong.textContent=label}
  });
}
function move(node,dir){const sibling=dir<0?node.previousElementSibling:node.nextElementSibling;if(!sibling)return;dir<0?sibling.before(node):sibling.after(node);sessionStorage.setItem('cms-editor-reselect',node.dataset.cmsNodeId||'');scheduleSave(true)}
function duplicate(node){const copy=node.cloneNode(true);copy.dataset.cmsNodeId=uid(node.dataset.cmsComponent||'block');copy.querySelectorAll('[data-cms-node-id]').forEach(child=>child.dataset.cmsNodeId=uid('node'));node.after(copy);sessionStorage.setItem('cms-editor-reselect',copy.dataset.cmsNodeId);scheduleSave(true)}
function remove(node,label='componente'){if(!confirm(`Remover ${label}?`))return;node.remove();scheduleSave(true)}
function addGalleryItem(gallery){const grid=gallery.querySelector('.cms-gallery-grid');if(!grid)return;const item=doc().createElement('div');item.className='cms-gallery-item';item.innerHTML='<div class="cms-media-placeholder" data-cms-image-placeholder><span>Adicionar imagem</span></div>';grid.append(item);scheduleSave(true)}
function removeGalleryItem(gallery){const grid=gallery.querySelector('.cms-gallery-grid'),last=grid?.lastElementChild;if(!last)return;if(grid.children.length<=1){alert('A galeria precisa ter pelo menos uma imagem.');return}if(confirm('Remover a última imagem da galeria?')){last.remove();scheduleSave(true)}}
function addListItem(list){const ul=list.querySelector('ul');if(!ul)return;const li=doc().createElement('li');li.dataset.cmsEditable='';li.textContent='Novo item';ul.append(li);scheduleSave(true)}
function removeListItem(list){const ul=list.querySelector('ul'),last=ul?.lastElementChild;if(!last)return;if(ul.children.length<=1){alert('A lista precisa ter pelo menos um item.');return}last.remove();scheduleSave(true)}
function selectOptions(current,items){return items.map(([value,label])=>`<option value="${value}" ${String(current)===String(value)?'selected':''}>${label}</option>`).join('')}
function galleryPanel(node){
  const box=document.createElement('div');box.className='cms-rich-component-panel cms-pro-panel';
  box.innerHTML=`<hr><p class="eyebrow">Galeria</p><div class="button-row"><button type="button" data-gallery-add>+ Imagem</button><button type="button" data-gallery-remove>− Imagem</button></div><h3>Desktop</h3><label>Colunas<select data-gallery-setting="cmsGalleryColumns">${selectOptions(node.dataset.cmsGalleryColumns||'2',[['1','1 coluna'],['2','2 colunas'],['3','3 colunas'],['4','4 colunas']])}</select></label><h3>Tablet</h3><label>Colunas<select data-gallery-setting="cmsTabletGalleryColumns">${selectOptions(node.dataset.cmsTabletGalleryColumns||'',[['','Automático'],['1','1 coluna'],['2','2 colunas'],['3','3 colunas']])}</select></label><h3>Celular</h3><label>Colunas<select data-gallery-setting="cmsMobileGalleryColumns">${selectOptions(node.dataset.cmsMobileGalleryColumns||'',[['','Automático'],['1','1 coluna'],['2','2 colunas']])}</select></label><p class="inspector-note">“Automático” usa a regra própria do tablet ou celular; não copia o desktop.</p>`;
  box.querySelector('[data-gallery-add]').onclick=()=>addGalleryItem(node);box.querySelector('[data-gallery-remove]').onclick=()=>removeGalleryItem(node);
  box.querySelectorAll('[data-gallery-setting]').forEach(select=>select.onchange=()=>{const key=select.dataset.gallerySetting;select.value?node.dataset[key]=select.value:delete node.dataset[key];scheduleSave()});
  return box;
}
function listPanel(node){const box=document.createElement('div');box.className='cms-rich-component-panel cms-pro-panel';box.innerHTML='<hr><p class="eyebrow">Lista</p><div class="button-row"><button type="button" data-list-add>+ Item</button><button type="button" data-list-remove>− Item</button></div>';box.querySelector('[data-list-add]').onclick=()=>addListItem(node);box.querySelector('[data-list-remove]').onclick=()=>removeListItem(node);return box}
function videoStructurePanel(video){
  const wrapper=video.closest('[data-cms-component="video"]');if(!wrapper)return null;
  const box=document.createElement('div');box.className='cms-rich-component-panel cms-pro-panel';
  box.innerHTML='<hr><p class="eyebrow">Estrutura</p><div class="button-row cms-rich-video-actions"><button type="button" data-rich-up>↑</button><button type="button" data-rich-down>↓</button><button type="button" data-rich-copy>Duplicar</button><button type="button" data-rich-remove class="danger">Remover</button></div>';
  box.querySelector('[data-rich-up]').onclick=()=>move(wrapper,-1);box.querySelector('[data-rich-down]').onclick=()=>move(wrapper,1);box.querySelector('[data-rich-copy]').onclick=()=>duplicate(wrapper);box.querySelector('[data-rich-remove]').onclick=()=>remove(wrapper,'o vídeo');return box;
}
function installPanels(){
  addButtons();relabelTree();
  const structural=selectedStructure(),panel=inspector.querySelector('.cms-structure-inspector');
  panel?.querySelector('.cms-rich-component-panel')?.remove();
  if(panel&&structural?.dataset.cmsComponent==='gallery'){
    const tree=panel.querySelector('.cms-structure-tree'),box=galleryPanel(structural);tree?panel.insertBefore(box,tree):panel.append(box);
  }else if(panel&&structural?.dataset.cmsComponent==='list'){
    const tree=panel.querySelector('.cms-structure-tree'),box=listPanel(structural);tree?panel.insertBefore(box,tree):panel.append(box);
  }
  const video=doc()?.querySelector('video.cms-selection');
  const videoInspector=inspector.querySelector('.cms-video-inspector');
  if(video&&videoInspector&&!videoInspector.querySelector('.cms-rich-component-panel')){
    const box=videoStructurePanel(video);if(box)videoInspector.append(box);
  }
}
new MutationObserver(()=>queueMicrotask(installPanels)).observe(inspector,{subtree:true,childList:true});
frame.addEventListener('load',()=>setTimeout(installPanels,160));
document.addEventListener('click',()=>setTimeout(installPanels,40),true);
})();
