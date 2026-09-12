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
function field(label,key,value,options){return `<label>${label}<select data-responsive-key="${key}">${options.map(([v,l])=>`<option value="${v}" ${v===value?'selected':''}>${l}</option>`).join('')}</select></label>`}
const inherit=[['','Herdar']];
const columnsTablet=[...inherit,['1','1 coluna'],['2','2 colunas'],['3','3 colunas'],['4','4 colunas']];
const columnsMobile=[...inherit,['1','1 coluna'],['2','2 colunas']];
const gaps=[...inherit,['s','Compacto'],['m','Médio'],['l','Amplo']];
const aligns=[...inherit,['start','Início'],['center','Centro'],['end','Fim']];
const selfAligns=[...inherit,['left','Esquerda'],['center','Centro'],['right','Direita']];
const spaces=[...inherit,['none','Nenhum'],['s','Pequeno'],['m','Médio'],['l','Grande'],['xl','Muito grande']];
const order=[...inherit,['normal','Normal'],['reverse','Inverter primeiro/último']];
const widthsTablet=[...inherit,['25','25%'],['33','33%'],['50','50%'],['66','66%'],['75','75%'],['100','100%']];
const widthsMobile=[...inherit,['50','50%'],['75','75%'],['100','100%']];
function setDataset(target,key,value){if(value)target.dataset[key]=value;else delete target.dataset[key]}
function bind(box,target){box.querySelectorAll('[data-responsive-key]').forEach(control=>control.onchange=()=>{setDataset(target,control.dataset.responsiveKey,control.value);scheduleSave()});box.querySelectorAll('[data-responsive-check]').forEach(control=>control.onchange=()=>{setDataset(target,control.dataset.responsiveCheck,control.checked?'1':'');scheduleSave()})}
function sectionPanel(section){const box=document.createElement('div');box.className='responsive-editor-panel cms-pro-panel';box.innerHTML=`<hr><p class="eyebrow">Responsividade</p><h3>Tablet</h3><div class="responsive-control-grid">${field('Colunas','cmsTabletColumns',section.dataset.cmsTabletColumns||'',columnsTablet)}${field('Espaço entre colunas','cmsTabletGap',section.dataset.cmsTabletGap||'',gaps)}${field('Alinhamento','cmsTabletAlign',section.dataset.cmsTabletAlign||'',aligns)}${field('Espaçamento vertical','cmsTabletSpace',section.dataset.cmsTabletSpace||'',spaces)}${field('Ordem','cmsTabletOrder',section.dataset.cmsTabletOrder||'',order)}</div><label class="check-row"><input type="checkbox" data-responsive-check="cmsHiddenTablet" ${section.dataset.cmsHiddenTablet==='1'?'checked':''}> Ocultar no tablet</label><h3>Celular</h3><div class="responsive-control-grid">${field('Colunas','cmsMobileColumns',section.dataset.cmsMobileColumns||'',columnsMobile)}${field('Espaço entre colunas','cmsMobileGap',section.dataset.cmsMobileGap||'',gaps)}${field('Alinhamento','cmsMobileAlign',section.dataset.cmsMobileAlign||'',aligns)}${field('Espaçamento vertical','cmsMobileSpace',section.dataset.cmsMobileSpace||'',spaces)}${field('Ordem','cmsMobileOrder',section.dataset.cmsMobileOrder||'',order)}</div><label class="check-row"><input type="checkbox" data-responsive-check="cmsHiddenMobile" ${section.dataset.cmsHiddenMobile==='1'?'checked':''}> Ocultar no celular</label><p class="inspector-note">Os valores vazios herdam o layout principal. Assim você só cria exceções quando realmente precisa.</p>`;bind(box,section);return box}
function elementPanel(target){const kind=target.matches('[data-cms-image-block]')?'Imagem':'Elemento';const box=document.createElement('div');box.className='responsive-editor-panel cms-pro-panel';box.innerHTML=`<hr><p class="eyebrow">Responsividade</p><h3>${kind} · Tablet</h3><div class="responsive-control-grid">${field('Largura','cmsTabletWidth',target.dataset.cmsTabletWidth||'',widthsTablet)}${field('Alinhamento','cmsTabletSelfAlign',target.dataset.cmsTabletSelfAlign||'',selfAligns)}</div><label class="check-row"><input type="checkbox" data-responsive-check="cmsHiddenTablet" ${target.dataset.cmsHiddenTablet==='1'?'checked':''}> Ocultar no tablet</label><h3>${kind} · Celular</h3><div class="responsive-control-grid">${field('Largura','cmsMobileWidth',target.dataset.cmsMobileWidth||'',widthsMobile)}${field('Alinhamento','cmsMobileSelfAlign',target.dataset.cmsMobileSelfAlign||'',selfAligns)}</div><label class="check-row"><input type="checkbox" data-responsive-check="cmsHiddenMobile" ${target.dataset.cmsHiddenMobile==='1'?'checked':''}> Ocultar no celular</label><label class="check-row"><input type="checkbox" data-responsive-check="cmsHiddenDesktop" ${target.dataset.cmsHiddenDesktop==='1'?'checked':''}> Ocultar no desktop</label><p class="inspector-note">Esses controles afetam somente este ${kind.toLowerCase()}; o restante da seção continua herdando o layout normal.</p>`;bind(box,target);return box}
function install(){const panel=inspector.querySelector('.inspector-section');if(!panel)return;const section=selectedSection(),element=section?null:selectedElement(),target=section||element;if(!target){panel.querySelector('.responsive-editor-panel')?.remove();lastTarget=null;return}if(panel.querySelector('.responsive-editor-panel')&&lastTarget===target)return;panel.querySelector('.responsive-editor-panel')?.remove();lastTarget=target;panel.append(section?sectionPanel(section):elementPanel(element))}
new MutationObserver(()=>queueMicrotask(install)).observe(inspector,{childList:true,subtree:true});
frame.addEventListener('load',()=>{lastTarget=null;setTimeout(install,100)});
document.addEventListener('click',()=>setTimeout(install,50),true);
})();
