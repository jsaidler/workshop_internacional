(()=>{
'use strict';
const frame=document.querySelector('#page-frame');
const inspector=document.querySelector('#inspector');
const saveButton=document.querySelector('#save-page');
if(!frame||!inspector||!saveButton)return;
const uid=prefix=>`${prefix}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2,8)}`;
const fdoc=()=>frame.contentDocument;
const pageRoot=()=>fdoc()?.querySelector('[data-cms-page-main]')||fdoc()?.querySelector('main')||null;
const selectedSection=()=>fdoc()?.querySelector('.cms-section-selected[data-cms-section]')||fdoc()?.querySelector('.cms-structure-selected')?.closest('[data-cms-section]')||null;

function ensureStyles(){
  if(document.querySelector('style[data-cms-editor-consolidation]'))return;
  const style=document.createElement('style');
  style.dataset.cmsEditorConsolidation='1';
  style.textContent=`
.cms-layout-consolidation{margin-top:14px;padding-top:14px;border-top:1px solid var(--editor-line,#d8d8d2)}
.cms-layout-consolidation-card{display:grid;gap:8px;padding:12px;border:1px solid var(--editor-line,#d8d8d2);border-radius:8px;background:var(--editor-surface,#f7f7f3)}
.cms-layout-consolidation-card h3,.cms-layout-consolidation-card p{margin:0}
.cms-layout-consolidation-card .panel-button{justify-self:start}
.cms-layout-legacy-details{margin-top:10px;border:1px solid var(--editor-line,#d8d8d2);border-radius:8px;background:var(--editor-bg,#fff)}
.cms-layout-legacy-details>summary{cursor:pointer;padding:10px 12px;font-weight:600;list-style:none}
.cms-layout-legacy-details>summary::-webkit-details-marker{display:none}
.cms-layout-legacy-details>summary::after{content:'+';float:right}
.cms-layout-legacy-details[open]>summary::after{content:'−'}
.cms-layout-legacy-details>.cms-layout-children{margin:0;padding:0 12px 12px}
.cms-layout-legacy-details>.cms-layout-children>hr:first-child{display:none}
#pro-add-component[hidden],#pro-components-dialog [data-pro-component][hidden]{display:none}
.editor-more-menu>#pro-save-block{width:100%;text-align:left}
`;
  document.head.append(style);
}

function openModernSectionLibrary(event){
  const trigger=document.querySelector('#pro-add-component');
  const dialog=document.querySelector('#pro-components-dialog');
  if(!trigger||!dialog)return;
  event?.preventDefault();
  event?.stopImmediatePropagation();
  dialog.querySelector('[data-pro-tab="components"]')?.click();
  trigger.click();
}
function insertModernSection(){
  const doc=fdoc(),root=pageRoot();
  if(!doc||!root)return;
  const sectionId=uid('section'),containerId=uid('container'),headingId=uid('heading'),paragraphId=uid('paragraph');
  const section=doc.createElement('section');
  section.className='cms-section-pro';
  section.dataset.cmsSection=sectionId;
  section.dataset.cmsSectionName='Seção livre';
  section.innerHTML=`<div class="cms-inner"><div class="cms-container cms-container-stack" data-cms-component="container" data-cms-container="stack" data-cms-node-id="${containerId}"><h2 data-cms-component="heading" data-cms-editable data-cms-node-id="${headingId}">Novo título</h2><p data-cms-component="paragraph" data-cms-editable data-cms-node-id="${paragraphId}">Novo parágrafo.</p></div></div>`;
  const current=selectedSection();
  current?current.after(section):root.append(section);
  const container=section.querySelector('[data-cms-container="stack"]');
  sessionStorage.setItem('cms-editor-reselect',containerId);
  doc.dispatchEvent(new CustomEvent('cms:structure-changed',{bubbles:true}));
  saveButton.click();
  document.querySelector('#pro-components-dialog')?.close();
  setTimeout(()=>container?.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true,view:frame.contentWindow})),60);
}
function wireSectionLibrary(){
  const trigger=document.querySelector('#pro-add-component');
  const dialog=document.querySelector('#pro-components-dialog');
  if(!trigger||!dialog)return;
  trigger.hidden=true;
  trigger.tabIndex=-1;
  trigger.setAttribute('aria-hidden','true');
  const header=dialog.querySelector('header div');
  if(header){
    const eyebrow=header.querySelector('p'),title=header.querySelector('h2');
    if(eyebrow&&eyebrow.textContent!=='Página')eyebrow.textContent='Página';
    if(title&&title.textContent!=='Adicionar seção')title.textContent='Adicionar seção';
  }
  const readyTab=dialog.querySelector('[data-pro-tab="components"]');
  if(readyTab&&readyTab.textContent!=='Seções prontas')readyTab.textContent='Seções prontas';
  const blocksTab=dialog.querySelector('[data-pro-tab="blocks"]');
  if(blocksTab&&blocksTab.textContent!=='Blocos salvos')blocksTab.textContent='Blocos salvos';
  ['free2','free3','free4'].forEach(id=>{const button=dialog.querySelector(`[data-pro-component="${id}"]`);if(button)button.hidden=true});
  const host=dialog.querySelector('#pro-components');
  if(host&&!host.querySelector('[data-modern-free-section]')){
    const button=document.createElement('button');
    button.type='button';
    button.className='section-template';
    button.dataset.modernFreeSection='1';
    button.innerHTML='<strong>Seção livre</strong><span>Comece com título e parágrafo e monte a estrutura como quiser.</span>';
    button.addEventListener('click',insertModernSection);
    host.prepend(button);
  }
  for(const id of ['add-section','add-section-side']){
    const button=document.getElementById(id);
    if(button&&button.dataset.cmsUnifiedSectionLibrary!=='1'){
      button.dataset.cmsUnifiedSectionLibrary='1';
      button.addEventListener('click',openModernSectionLibrary,true);
    }
  }
  const saveBlock=document.querySelector('#pro-save-block');
  const menu=document.querySelector('#editor-more .editor-more-menu');
  if(saveBlock){
    if(saveBlock.textContent!=='Salvar seção como bloco')saveBlock.textContent='Salvar seção como bloco';
    if(menu&&saveBlock.parentElement!==menu){
      const pagesLink=menu.querySelector('#editor-pages-link');
      pagesLink?menu.insertBefore(saveBlock,pagesLink):menu.append(saveBlock);
    }
  }
}

function chooseGap(value){
  const px=parseFloat(value);
  if(!Number.isFinite(px))return'';
  const choices=[['none',0],['s',12],['m',24],['l',48]];
  return choices.reduce((best,current)=>Math.abs(current[1]-px)<Math.abs(best[1]-px)?current:best,choices[0])[0];
}
function upgradeFreeGrid(section,grid){
  const cells=[...grid.children];
  if(!cells.length)return false;
  const win=frame.contentWindow;
  const oldGap=win?.getComputedStyle(grid).columnGap||'';
  const count=Math.max(1,Math.min(4,Number(section.dataset.cmsColumns)||cells.length));
  grid.classList.remove('cms-free-grid');
  grid.classList.add('cms-container','cms-container-columns');
  grid.dataset.cmsComponent='container';
  grid.dataset.cmsContainer='columns';
  grid.dataset.cmsColumns=String(count);
  grid.dataset.cmsNodeId=grid.dataset.cmsNodeId||uid('columns');
  const gap=chooseGap(oldGap);
  if(gap)grid.dataset.cmsGap=gap;
  const ratio=section.dataset.layoutRatio||section.dataset.cmsRatio||'';
  if(count===2&&['30-70','40-60','50-50','60-40','70-30'].includes(ratio))grid.dataset.cmsRatio=ratio;
  cells.forEach((cell,index)=>{
    cell.classList.remove('cms-free-cell');
    cell.classList.add('cms-column');
    cell.dataset.cmsColumn='';
    cell.dataset.cmsNodeId=cell.dataset.cmsNodeId||uid('column');
    if(['1','2','3','4'].includes(cell.dataset.cmsSpan||''))cell.dataset.cmsSpan=cell.dataset.cmsSpan;
    if(['start','center','end'].includes(cell.dataset.cmsSelf||''))cell.dataset.cmsJustify=cell.dataset.cmsSelf;
    delete cell.dataset.cmsSelf;
    if(section.dataset.layoutMobile==='reverse')cell.dataset.cmsMobileOrder=String(cells.length-index);
  });
  delete section.dataset.cmsColumns;
  delete section.dataset.layoutRatio;
  delete section.dataset.layoutMobile;
  const doc=fdoc();
  doc?.dispatchEvent(new CustomEvent('cms:structure-changed',{bubbles:true}));
  saveButton.click();
  setTimeout(()=>grid.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true,view:frame.contentWindow})),60);
  return true;
}
function wrapLegacyLayout(){
  const legacy=inspector.querySelector('.cms-layout-children:not([data-cms-consolidated])');
  if(!legacy)return;
  const section=selectedSection();
  if(!section)return;
  legacy.dataset.cmsConsolidated='1';
  const wrapper=document.createElement('div');
  wrapper.className='cms-layout-consolidation';
  const grid=section.querySelector('.cms-free-grid');
  if(grid){
    const hasSpans=[...grid.children].some(cell=>cell.dataset.cmsSpan);
    const card=document.createElement('div');
    card.className='cms-layout-consolidation-card';
    card.innerHTML=hasSpans
      ?'<p class="eyebrow">Estrutura</p><h3>Trazer esta grade para o editor atual</h3><p class="inspector-note">O conteúdo e as larguras personalizadas são mantidos. Depois da conversão, cada coluna pode ter largura própria em desktop, tablet e celular.</p><button type="button" class="panel-button" data-upgrade-free-grid>Usar estrutura atual</button>'
      :'<p class="eyebrow">Estrutura</p><h3>Trazer esta grade para o editor atual</h3><p class="inspector-note">O conteúdo é mantido. As colunas passam a aparecer na árvore Estrutura e podem ser movidas diretamente.</p><button type="button" class="panel-button" data-upgrade-free-grid>Usar estrutura atual</button>';
    wrapper.append(card);
    card.querySelector('[data-upgrade-free-grid]')?.addEventListener('click',()=>upgradeFreeGrid(section,grid));
  }
  const details=document.createElement('details');
  details.className='cms-layout-legacy-details';
  details.innerHTML='<summary>Ajustes da composição pronta</summary>';
  legacy.before(wrapper);
  wrapper.append(details);
  details.append(legacy);
}
function preferStructureView(){
  if(sessionStorage.getItem('cms-structure-sidebar-view'))return;
  const tree=document.querySelector('[data-structure-view="tree"]');
  if(tree&&!tree.classList.contains('is-active'))tree.click();
}
function install(){ensureStyles();wireSectionLibrary();preferStructureView();wrapLegacyLayout()}
new MutationObserver(()=>queueMicrotask(install)).observe(inspector,{childList:true,subtree:true});
frame.addEventListener('load',()=>setTimeout(install,180));
install();
})();
