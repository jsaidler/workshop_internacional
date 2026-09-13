(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const inspector=document.querySelector('#inspector');
const save=document.querySelector('#save-page');
if(!frame||!inspector||!save||!window.MediaLibrary)return;

const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
let currentVideo=null;
let dialog=null;
let searchInput=null;
let uploadInput=null;
let grid=null;
let videoItems=[];
let frameObserver=null;
const boundVideos=new WeakSet();

function frameDoc(){return frame.contentDocument||null}
function pageRoot(){return frameDoc()?.querySelector('[data-cms-page-main]')||frameDoc()?.querySelector('main')||null}
function assetId(video){return Number(video?.dataset?.mediaAssetId||0)}
function isManaged(video){return assetId(video)>0}
function selectedVideo(){
  const d=frameDoc();
  if(currentVideo&&d?.contains(currentVideo))return currentVideo;
  return d?.querySelector('video.cms-selection')||null;
}
function clearEditorSelections(d){
  d?.querySelectorAll('.cms-selection,.cms-section-selected,.cms-editing,.cms-pro-selected,.cms-video-selected').forEach(node=>node.classList.remove('cms-selection','cms-section-selected','cms-editing','cms-pro-selected','cms-video-selected'));
}
function persist(message='Vídeo atualizado.'){
  save.click();
  if(message){
    const toast=document.querySelector('#toast');
    if(toast){toast.textContent=message;toast.classList.add('show');clearTimeout(persist.t);persist.t=setTimeout(()=>toast.classList.remove('show'),1800)}
  }
}
function toggleBoolean(video,name,checked){
  video.toggleAttribute(name,checked);
  if(name==='muted')video.muted=checked;
}
function setExternalSource(video,url){
  const clean=String(url||'').trim();
  if(!clean)return;
  delete video.dataset.mediaAssetId;
  delete video.dataset.mediaVersionId;
  delete video.dataset.mediaVersionMode;
  video.setAttribute('src',clean);
  try{video.load()}catch{}
  persist('URL do vídeo atualizada.');
  renderInspector(video);
}
function applyLibraryVideo(video,item){
  if(!video||!item)return;
  video.dataset.mediaAssetId=String(item.id);
  video.dataset.mediaVersionMode='latest';
  delete video.dataset.mediaVersionId;
  video.setAttribute('src',item.src||'');
  if(item.poster)video.setAttribute('poster',item.poster);else video.removeAttribute('poster');
  try{video.load()}catch{}
  persist('Vídeo da biblioteca aplicado.');
  renderInspector(video);
}
function renderInspector(video){
  if(!video)return;
  currentVideo=video;
  const managed=isManaged(video);
  const id=assetId(video);
  const src=video.getAttribute('src')||video.currentSrc||'';
  const poster=video.getAttribute('poster')||'';
  const preload=video.getAttribute('preload')||'metadata';
  inspector.innerHTML=`<div class="inspector-section cms-video-inspector">
    <header><p class="eyebrow">Vídeo</p><h2>Propriedades do vídeo</h2></header>
    <div class="inspector-group">
      <p class="eyebrow">Arquivo de vídeo</p>
      ${managed?`<p class="inspector-note">Vídeo vinculado à biblioteca · Asset #${id}. A origem do vídeo é administrada separadamente da imagem de capa.</p>`:`<p class="inspector-note">Este vídeo usa uma URL direta. Você pode vinculá-lo à biblioteca sem tratar o vídeo como uma imagem.</p>`}
      <div class="button-row"><button type="button" id="cv-choose" class="panel-button primary">${managed?'Trocar vídeo':'Escolher vídeo da biblioteca'}</button></div>
      <label>URL externa<input id="cv-src" type="url" value="${esc(managed?'':src)}" placeholder="https://…"></label>
      <button type="button" id="cv-apply-src" class="panel-button">Usar URL externa</button>
    </div>
    <hr>
    <div class="inspector-group">
      <p class="eyebrow">Reprodução</p>
      <label class="check-row"><input id="cv-controls" type="checkbox" ${video.hasAttribute('controls')?'checked':''}> Exibir controles</label>
      <label class="check-row"><input id="cv-autoplay" type="checkbox" ${video.hasAttribute('autoplay')?'checked':''}> Autoplay</label>
      <label class="check-row"><input id="cv-muted" type="checkbox" ${video.hasAttribute('muted')||video.muted?'checked':''}> Sem som</label>
      <label class="check-row"><input id="cv-loop" type="checkbox" ${video.hasAttribute('loop')?'checked':''}> Repetir</label>
      <label class="check-row"><input id="cv-inline" type="checkbox" ${video.hasAttribute('playsinline')?'checked':''}> Reproduzir inline</label>
      <label>Pré-carregamento<select id="cv-preload"><option value="none">Nenhum</option><option value="metadata">Metadados</option><option value="auto">Automático</option></select></label>
    </div>
    <hr>
    <div class="inspector-group">
      <p class="eyebrow">Capa do vídeo</p>
      ${poster?`<img src="${esc(poster)}" alt="Capa atual do vídeo" style="display:block;width:100%;max-height:180px;object-fit:cover;margin:0 0 12px;border:1px solid var(--editor-line,#d6d7d3)">`:''}
      <p class="inspector-note">${poster?'Há uma capa associada a este vídeo.':'Este vídeo não possui capa associada.'} A capa é uma propriedade do asset de vídeo e não usa os controles de imagem da página.</p>
      <a class="panel-button" href="/admin/media.php" target="_blank" rel="noopener">Gerenciar vídeo e capa ↗</a>
    </div>
  </div>`;
  const preloadSelect=inspector.querySelector('#cv-preload');
  if(preloadSelect)preloadSelect.value=['none','metadata','auto'].includes(preload)?preload:'metadata';
  inspector.querySelector('#cv-choose')?.addEventListener('click',()=>openVideoLibrary(video));
  inspector.querySelector('#cv-apply-src')?.addEventListener('click',()=>setExternalSource(video,inspector.querySelector('#cv-src')?.value||''));
  for(const [idAttr,name] of [['cv-controls','controls'],['cv-autoplay','autoplay'],['cv-muted','muted'],['cv-loop','loop'],['cv-inline','playsinline']]){
    inspector.querySelector('#'+idAttr)?.addEventListener('change',event=>{toggleBoolean(video,name,event.currentTarget.checked);persist('Opções de reprodução atualizadas.');renderInspector(video)});
  }
  preloadSelect?.addEventListener('change',()=>{video.setAttribute('preload',preloadSelect.value);persist('Pré-carregamento atualizado.');renderInspector(video)});
}
function selectVideo(video){
  const d=frameDoc();
  if(!d||!video||!d.contains(video))return;
  clearEditorSelections(d);
  video.classList.add('cms-selection');
  currentVideo=video;
  renderInspector(video);
}
function interceptVideoInteraction(event,video){
  if(!video||!pageRoot()?.contains(video))return;
  if(event.type==='pointerdown'&&event.button!==undefined&&event.button!==0)return;
  event.preventDefault();
  event.stopImmediatePropagation();
  selectVideo(video);
}
function bindVideo(video){
  if(boundVideos.has(video))return;
  boundVideos.add(video);
  video.style.cursor='pointer';
  video.addEventListener('pointerdown',event=>interceptVideoInteraction(event,video),true);
  video.addEventListener('click',event=>interceptVideoInteraction(event,video),true);
}
function bindVideos(){
  pageRoot()?.querySelectorAll('video').forEach(bindVideo);
}
function installFrameControls(){
  const d=frameDoc(),root=pageRoot();
  if(!d||!root)return;
  frameObserver?.disconnect();
  bindVideos();
  d.addEventListener('pointerdown',event=>{
    const video=event.target?.closest?.('video');
    if(video&&root.contains(video))interceptVideoInteraction(event,video);
  },true);
  d.addEventListener('click',event=>{
    const video=event.target?.closest?.('video');
    if(video&&root.contains(video))interceptVideoInteraction(event,video);
  },true);
  frameObserver=new MutationObserver(mutations=>{
    if(mutations.some(m=>m.type==='childList'))bindVideos();
  });
  frameObserver.observe(root,{subtree:true,childList:true});
}
function ensureDialog(){
  if(dialog)return dialog;
  dialog=document.createElement('dialog');
  dialog.className='editor-dialog wide cms-video-dialog';
  dialog.innerHTML=`<form method="dialog">
    <header><div><p>Vídeo</p><h2>Biblioteca de vídeos</h2></div><button value="cancel" aria-label="Fechar">×</button></header>
    <div class="media-dialog-tools">
      <label class="media-search-label"><span>Buscar vídeo</span><input id="cms-video-search" type="search" placeholder="Nome do vídeo"></label>
      <label class="upload-label">Enviar novo vídeo <input id="cms-video-upload" type="file" accept="video/mp4,video/webm,video/quicktime"></label>
      <a href="/admin/media.php" target="_blank" rel="noopener">Gerenciar vídeos ↗</a>
    </div>
    <div id="cms-video-grid" class="media-grid"></div>
  </form>`;
  document.body.append(dialog);
  searchInput=dialog.querySelector('#cms-video-search');
  uploadInput=dialog.querySelector('#cms-video-upload');
  grid=dialog.querySelector('#cms-video-grid');
  searchInput?.addEventListener('input',renderVideoGrid);
  uploadInput?.addEventListener('change',uploadVideo);
  return dialog;
}
function renderVideoGrid(){
  if(!grid)return;
  const q=String(searchInput?.value||'').trim().toLocaleLowerCase('pt-BR');
  const items=videoItems.filter(item=>!q||String(item.label||'').toLocaleLowerCase('pt-BR').includes(q));
  grid.innerHTML=items.map((item,i)=>`<button type="button" class="media-item" data-video="${i}">${item.poster?`<img src="${esc(item.poster)}" alt="">`:'<span class="video-picker-placeholder">Vídeo</span>'}<span>${esc(item.label||'Vídeo')}</span></button>`).join('')||'<p>Nenhum vídeo disponível.</p>';
  grid.querySelectorAll('[data-video]').forEach(button=>button.addEventListener('click',()=>{
    const item=items[Number(button.dataset.video)];
    const video=selectedVideo();
    if(video&&item){applyLibraryVideo(video,item);dialog?.close()}
  }));
}
async function openVideoLibrary(video){
  currentVideo=video;
  const dlg=ensureDialog();
  if(grid)grid.innerHTML='<p>Carregando vídeos…</p>';
  dlg.showModal();
  try{
    await MediaLibrary.load();
    videoItems=MediaLibrary.videos.filter(item=>item.status==='ready'||!item.status);
    renderVideoGrid();
  }catch(error){if(grid)grid.innerHTML=`<p>${esc(error.message||'Não foi possível carregar a biblioteca de vídeos.')}</p>`}
}
async function uploadVideo(event){
  const file=event.currentTarget.files?.[0];
  if(!file)return;
  if(grid)grid.innerHTML='<p>Enviando vídeo…</p>';
  try{
    await MediaLibrary.load();
    const uploaded=await MediaLibrary.upload(file);
    await MediaLibrary.load();
    videoItems=MediaLibrary.videos.filter(item=>item.status==='ready'||!item.status);
    const item=videoItems.find(v=>Number(v.id)===Number(uploaded.id));
    const video=selectedVideo();
    if(video&&item){applyLibraryVideo(video,item);dialog?.close()}else renderVideoGrid();
  }catch(error){if(grid)grid.innerHTML=`<p>${esc(error.message||'Não foi possível enviar o vídeo.')}</p>`}
  finally{event.currentTarget.value=''}
}

frame.addEventListener('load',()=>setTimeout(installFrameControls,0));
})();
