(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const inspector=document.querySelector('#inspector');
const saveButton=document.querySelector('#save-page');
if(!frame||!inspector||!saveButton)return;

let syncing=false;
const d=()=>frame.contentDocument;
const selected=()=>d()?.querySelector('.cms-structure-selected')||null;
const clean=value=>String(value||'').trim().replace(/\s+/g,' ');

function defaultLabel(node){
  if(!node)return'Elemento';
  if(node.hasAttribute('data-cms-column'))return'Coluna';
  if(node.dataset.cmsContainer==='columns')return'Grupo de colunas';
  if(node.dataset.cmsContainer==='stack')return'Contêiner';
  const labels={paragraph:'Parágrafo',heading:'Título',image:'Imagem',carousel:'Carrossel',button:'Botão',divider:'Divisor',spacer:'Espaço',list:'Lista',quote:'Citação',video:'Vídeo',gallery:'Galeria',container:'Contêiner'};
  return labels[node.dataset.cmsComponent]||'Elemento';
}
function announce(node){
  const doc=d();
  if(!doc||!node)return;
  node.dispatchEvent(new CustomEvent('cms:structure-changed',{bubbles:true,detail:{nodeId:node.dataset.cmsNodeId||'',reason:'label'}}));
}
function apply(input,node){
  if(!node?.isConnected)return;
  const value=clean(input.value);
  if(value)node.dataset.cmsEditorLabel=value;
  else delete node.dataset.cmsEditorLabel;
  announce(node);
}
function save(input,node){
  if(!node?.isConnected)return;
  apply(input,node);
  const value=clean(input.value);
  if(input.dataset.savedValue===value)return;
  input.dataset.savedValue=value;
  const nodeId=clean(node.dataset.cmsNodeId||'');
  if(nodeId)sessionStorage.setItem('cms-editor-reselect',nodeId);
  saveButton.click();
}
function sync(){
  if(syncing)return;
  syncing=true;
  requestAnimationFrame(()=>{
    syncing=false;
    const node=selected();
    const host=inspector.querySelector('.cms-structure-inspector');
    const existing=inspector.querySelector('.cms-structure-editor-name');
    if(!node||!host){existing?.remove();return}
    if(existing&&existing.dataset.nodeId===(node.dataset.cmsNodeId||''))return;
    existing?.remove();
    const wrap=document.createElement('div');
    wrap.className='cms-structure-editor-name';
    wrap.dataset.nodeId=node.dataset.cmsNodeId||'';
    const type=defaultLabel(node);
    const value=clean(node.dataset.cmsEditorLabel||'');
    wrap.innerHTML=`<label>Nome no editor<input type="text" data-cms-editor-label-input maxlength="80" value="${value.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}" placeholder="${type}"></label><p class="inspector-note">Opcional. Serve apenas para localizar e organizar este elemento no editor; não aparece no site.</p>`;
    const breadcrumb=host.querySelector('.cms-editor-breadcrumb');
    const header=host.querySelector(':scope > header');
    (breadcrumb||header)?.after(wrap);
    const input=wrap.querySelector('[data-cms-editor-label-input]');
    input.dataset.savedValue=value;
    input.addEventListener('input',()=>apply(input,node));
    input.addEventListener('change',()=>save(input,node));
    input.addEventListener('keydown',event=>{
      if(event.key==='Enter'){event.preventDefault();save(input,node);input.blur()}
      if(event.key==='Escape'){event.preventDefault();input.value=input.dataset.savedValue||'';apply(input,node);input.blur()}
    });
  });
}

new MutationObserver(sync).observe(inspector,{childList:true,subtree:true});
frame.addEventListener('load',()=>setTimeout(sync,180));
setTimeout(sync,220);
})();
