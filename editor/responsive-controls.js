(()=>{
'use strict';
const frame=document.querySelector('#page-frame'),inspector=document.querySelector('#inspector'),save=document.querySelector('#save-page');
if(!frame||!inspector||!save)return;
let timer=0,lastTarget=null;
const fdoc=()=>frame.contentDocument;
const selectedSection=()=>fdoc()?.querySelector('.cms-section-selected[data-cms-section]')||null;
const selectedText=()=>fdoc()?.querySelector('.cms-editing[data-cms-editable]')||null;
const selectedImage=()=>fdoc()?.querySelector('.cms-selection[data-cms-image]')||null;
const selectedElement=()=>{const image=selectedImage();if(image)return image.closest('[data-cms-image-block]')||image;return selectedText()};
const scheduleSave=()=>{clearTimeout(timer);timer=setTimeout(()=>save.click(),450)};
const GRID_SELECTOR='.cms-grid,.cms-free-grid,.cms-card-grid,.cms-stats,.cms-gallery,.statement-grid,.cms-proof-grid,.cms-support-inner,.interest-inner';
const RATIO_GRID_SELECTOR='.cms-grid,.cms-free-grid,.statement-grid,.cms-proof-grid,.cms-support-inner,.interest-inner';
function field(label,key,value,options){return `<label>${label}<select data-responsive-key="${key}">${options.map(([v,l])=>`<option value="${v}" ${v===value?'selected':''}>${l}</option>`).join('')}</select></label>`}
const automatic=[['','Automático']];
const columnsTablet=[...automatic,['1','1 coluna'],['2','2 colunas'],['3','3 colunas'],['4','4 colunas']];
const columnsMobile=[...automatic,['1','1 coluna'],['2','2 colunas']];
const ratios=[...automatic,['30-70','30 / 70'],['40-60','40 / 60'],['50-50','50 / 50'],['60-40','60 / 40'],['70-30','70 / 30']];
const gaps=[...automatic,['s','Compacto'],['m','Médio'],['l','Amplo']];
const aligns=[...automatic,['start','Início'],['center','Centro'],['end','Fim']];
const textAligns=[...automatic,['left','Esquerda'],['center','Centro'],['right','Direita']];
const selfAligns=[...automatic,['left','Esquerda'],['center','Centro'],['right','Direita']];
const spaces=[...automatic,['none','Nenhum'],['s','Pequeno'],['m','Médio'],['l','Grande'],['xl','Muito grande']];
const order=[...automatic,['normal','Normal'],['reverse','Inverter primeiro/último']];
const widthsTablet=[...automatic,['25','25%'],['33','33%'],['50','50%'],['66','66%'],['75','75%'],['100','100%']];
const widthsMobile=[...automatic,['50','50%'],['75','75%'],['100','100%']];
function gridTarget(section){if(section.matches('.apparatus'))return section;return section.querySelector(GRID_SELECTOR)}
function ratioTarget(section){if(section.matches('.apparatus'))return section;const grid=section.querySelector(RATIO_GRID_SELECTOR);if(!grid)return null;const explicit=Number(section.dataset.cmsColumns||0);if(explicit>2)return null;if(grid.matches('.cms-grid,.cms-free-grid')&&grid.children.length>2&&explicit!==2)return null;return grid}
function setDataset(target,key,value){if(value)target.dataset[key]=value;else delete target.dataset[key]}
function bind(box,target){
  box.querySelectorAll('[data-responsive-key]').forEach(control=>control.onchange=()=>{
    const key=control.dataset.responsiveKey;
    setDataset(target,key,control.value);
    if(key==='cmsMobileOrder')delete target.dataset.layoutMobile;
    scheduleSave();
  });
  box.querySelectorAll('[data-responsive-check]').forEach(control=>control.onchange=()=>{setDataset(target,control.dataset.responsiveCheck,control.checked?'1':'');scheduleSave()});
}
function renameControl(control,text){const label=control?.closest('label');if(!label)return;const node=[...label.childNodes].find(n=>n.nodeType===3&&n.nodeValue.trim()!=='');if(node)node.nodeValue=text;}
function harmonizeCoreInspector(section,panel){
  const ratio=panel.querySelector('#s-ratio');
  if(ratio){renameControl(ratio,'Proporção das colunas · Desktop');ratio.closest('label').hidden=!ratioTarget(section)}
  renameControl(panel.querySelector('#s-width'),'Largura · Desktop');
  renameControl(panel.querySelector('#s-space'),'Espaçamento vertical · Desktop');
  renameControl(panel.querySelector('#s-align'),'Alinhamento do texto · Desktop');
  const legacyMobile=panel.querySelector('#s-mobile');if(legacyMobile)legacyMobile.closest('label').hidden=true;
}
function sectionPanel(section){
  const hasGrid=!!gridTarget(section),hasRatio=!!ratioTarget(section);
  const tabletOrder=section.dataset.cmsTabletOrder||'';
  const mobileOrder=section.dataset.cmsMobileOrder||(section.dataset.layoutMobile==='reverse'?'reverse':'');
  const tabletGrid=hasGrid?`${field('Colunas','cmsTabletColumns',section.dataset.cmsTabletColumns||'',columnsTablet)}${hasRatio?field('Proporção das colunas','cmsTabletRatio',section.dataset.cmsTabletRatio||'',ratios):''}${field('Espaço entre colunas','cmsTabletGap',section.dataset.cmsTabletGap||'',gaps)}${field('Alinhamento vertical','cmsTabletAlign',section.dataset.cmsTabletAlign||'',aligns)}${field('Ordem','cmsTabletOrder',tabletOrder,order)}`:'';
  const mobileGrid=hasGrid?`${field('Colunas','cmsMobileColumns',section.dataset.cmsMobileColumns||'',columnsMobile)}${hasRatio?field('Proporção das colunas','cmsMobileRatio',section.dataset.cmsMobileRatio||'',ratios):''}${field('Espaço entre colunas','cmsMobileGap',section.dataset.cmsMobileGap||'',gaps)}${field('Alinhamento vertical','cmsMobileAlign',section.dataset.cmsMobileAlign||'',aligns)}${field('Ordem','cmsMobileOrder',mobileOrder,order)}`:'';
  const box=document.createElement('div');box.className='responsive-editor-panel cms-pro-panel';
  box.innerHTML=`<hr><p class="eyebrow">Responsividade</p><p class="inspector-note">Desktop, tablet e celular têm regras próprias. “Automático” adapta o componente ao tamanho da tela; não copia cegamente a configuração de desktop.</p><h3>Tablet</h3><div class="responsive-control-grid">${tabletGrid}${field('Alinhamento do texto','cmsTabletTextAlign',section.dataset.cmsTabletTextAlign||'',textAligns)}${field('Espaçamento vertical','cmsTabletSpace',section.dataset.cmsTabletSpace||'',spaces)}</div><label class="check-row"><input type="checkbox" data-responsive-check="cmsHiddenTablet" ${section.dataset.cmsHiddenTablet==='1'?'checked':''}> Ocultar no tablet</label><h3>Celular</h3><div class="responsive-control-grid">${mobileGrid}${field('Alinhamento do texto','cmsMobileTextAlign',section.dataset.cmsMobileTextAlign||'',textAligns)}${field('Espaçamento vertical','cmsMobileSpace',section.dataset.cmsMobileSpace||'',spaces)}</div><label class="check-row"><input type="checkbox" data-responsive-check="cmsHiddenMobile" ${section.dataset.cmsHiddenMobile==='1'?'checked':''}> Ocultar no celular</label>`;
  bind(box,section);return box;
}
function elementPanel(target){const kind=target.matches('[data-cms-image-block]')?'Imagem':'Elemento';const box=document.createElement('div');box.className='responsive-editor-panel cms-pro-panel';box.innerHTML=`<hr><p class="eyebrow">Responsividade</p><h3>${kind} · Tablet</h3><div class="responsive-control-grid">${field('Largura','cmsTabletWidth',target.dataset.cmsTabletWidth||'',widthsTablet)}${field('Alinhamento','cmsTabletSelfAlign',target.dataset.cmsTabletSelfAlign||'',selfAligns)}</div><label class="check-row"><input type="checkbox" data-responsive-check="cmsHiddenTablet" ${target.dataset.cmsHiddenTablet==='1'?'checked':''}> Ocultar no tablet</label><h3>${kind} · Celular</h3><div class="responsive-control-grid">${field('Largura','cmsMobileWidth',target.dataset.cmsMobileWidth||'',widthsMobile)}${field('Alinhamento','cmsMobileSelfAlign',target.dataset.cmsMobileSelfAlign||'',selfAligns)}</div><label class="check-row"><input type="checkbox" data-responsive-check="cmsHiddenMobile" ${target.dataset.cmsHiddenMobile==='1'?'checked':''}> Ocultar no celular</label><label class="check-row"><input type="checkbox" data-responsive-check="cmsHiddenDesktop" ${target.dataset.cmsHiddenDesktop==='1'?'checked':''}> Ocultar no desktop</label><p class="inspector-note">“Automático” usa a largura natural do elemento naquele tamanho de tela. Uma escolha explícita vale somente para o dispositivo selecionado.</p>`;bind(box,target);return box}
function install(){const panel=inspector.querySelector('.inspector-section');if(!panel)return;const section=selectedSection(),element=section?null:selectedElement(),target=section||element;if(section)harmonizeCoreInspector(section,panel);if(!target){panel.querySelector('.responsive-editor-panel')?.remove();lastTarget=null;return}if(panel.querySelector('.responsive-editor-panel')&&lastTarget===target)return;panel.querySelector('.responsive-editor-panel')?.remove();lastTarget=target;panel.append(section?sectionPanel(section):elementPanel(element))}
new MutationObserver(()=>queueMicrotask(install)).observe(inspector,{childList:true,subtree:true});
frame.addEventListener('load',()=>{lastTarget=null;setTimeout(install,100)});
document.addEventListener('click',()=>setTimeout(install,50),true);
})();
