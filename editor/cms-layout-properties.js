(()=>{
'use strict';
const frame=document.querySelector('#page-frame'),inspector=document.querySelector('#inspector'),save=document.querySelector('#save-page');
if(!frame||!inspector||!save)return;
let timer=0,lastNode=null;
const fdoc=()=>frame.contentDocument;
const selectedStructure=()=>fdoc()?.querySelector('.cms-structure-selected')||null;
const scheduleSave=()=>{clearTimeout(timer);timer=setTimeout(()=>save.click(),350)};
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
function options(items,current=''){return items.map(([v,l])=>`<option value="${esc(v)}" ${String(v)===String(current)?'selected':''}>${esc(l)}</option>`).join('')}
function field(label,key,current,items){return `<label>${esc(label)}<select data-layout-prop="${esc(key)}">${options(items,current)}</select></label>`}
function checkbox(label,key,checked){return `<label class="check-row"><input type="checkbox" data-layout-check="${esc(key)}" ${checked?'checked':''}> ${esc(label)}</label>`}
const automatic=[['','Automático']];
const width=[['','Automático'],['full','Largura total'],['contained','Contido'],['narrow','Estreito']];
const tone=[['','Transparente'],['surface','Superfície'],['muted','Superfície suave'],['inverse','Invertido']];
const border=[['','Nenhuma'],['top','Superior'],['bottom','Inferior'],['both','Superior e inferior']];
const padding=[...automatic,['none','Nenhum'],['s','Pequeno'],['m','Médio'],['l','Grande'],['xl','Muito grande']];
const items=[...automatic,['start','Início'],['center','Centro'],['end','Fim'],['stretch','Esticar']];
const justify=[...automatic,['start','Início'],['center','Centro'],['end','Fim']];
const textAlign=[...automatic,['left','Esquerda'],['center','Centro'],['right','Direita']];
const order=[...automatic,['1','1'],['2','2'],['3','3'],['4','4']];
function setData(node,key,value){if(value)node.dataset[key]=value;else delete node.dataset[key];scheduleSave()}
function bind(panel,node){
  panel.querySelectorAll('[data-layout-prop]').forEach(control=>control.onchange=()=>setData(node,control.dataset.layoutProp,control.value));
  panel.querySelectorAll('[data-layout-check]').forEach(control=>control.onchange=()=>setData(node,control.dataset.layoutCheck,control.checked?'1':''));
}
function containerPanel(node){
  const box=document.createElement('div');box.className='cms-layout-properties cms-pro-panel';
  box.innerHTML=`<hr><p class="eyebrow">Contêiner</p><div class="cms-layout-property-grid">${field('Largura','cmsBoxWidth',node.dataset.cmsBoxWidth||'',width)}${field('Fundo','cmsBoxTone',node.dataset.cmsBoxTone||'',tone)}${field('Borda','cmsBoxBorder',node.dataset.cmsBoxBorder||'',border)}</div><h3>Desktop</h3><div class="cms-layout-property-grid">${field('Espaçamento interno','cmsPadding',node.dataset.cmsPadding||'',padding)}${field('Alinhamento dos itens','cmsItems',node.dataset.cmsItems||'',items)}</div><h3>Tablet</h3><div class="cms-layout-property-grid">${field('Espaçamento interno','cmsTabletPadding',node.dataset.cmsTabletPadding||'',padding)}${field('Alinhamento dos itens','cmsTabletItems',node.dataset.cmsTabletItems||'',items)}</div><h3>Celular</h3><div class="cms-layout-property-grid">${field('Espaçamento interno','cmsMobilePadding',node.dataset.cmsMobilePadding||'',padding)}${field('Alinhamento dos itens','cmsMobileItems',node.dataset.cmsMobileItems||'',items)}</div><p class="inspector-note">“Automático” usa a regra própria daquele tamanho de tela.</p>`;
  bind(box,node);return box;
}
function columnPanel(node){
  const box=document.createElement('div');box.className='cms-layout-properties cms-pro-panel';
  box.innerHTML=`<hr><p class="eyebrow">Coluna</p><h3>Desktop</h3><div class="cms-layout-property-grid">${field('Conteúdo vertical','cmsJustify',node.dataset.cmsJustify||'',justify)}${field('Conteúdo horizontal','cmsColumnAlign',node.dataset.cmsColumnAlign||'',items)}${field('Texto','cmsTextAlign',node.dataset.cmsTextAlign||'',textAlign)}${field('Ordem','cmsOrder',node.dataset.cmsOrder||'',order)}</div>${checkbox('Ocultar no desktop','cmsHideDesktop',node.dataset.cmsHideDesktop==='1')}<h3>Tablet</h3><div class="cms-layout-property-grid">${field('Conteúdo vertical','cmsTabletJustify',node.dataset.cmsTabletJustify||'',justify)}${field('Conteúdo horizontal','cmsTabletColumnAlign',node.dataset.cmsTabletColumnAlign||'',items)}${field('Texto','cmsTabletTextAlign',node.dataset.cmsTabletTextAlign||'',textAlign)}${field('Ordem','cmsTabletOrder',node.dataset.cmsTabletOrder||'',order)}</div>${checkbox('Ocultar no tablet','cmsHideTablet',node.dataset.cmsHideTablet==='1')}<h3>Celular</h3><div class="cms-layout-property-grid">${field('Conteúdo vertical','cmsMobileJustify',node.dataset.cmsMobileJustify||'',justify)}${field('Conteúdo horizontal','cmsMobileColumnAlign',node.dataset.cmsMobileColumnAlign||'',items)}${field('Texto','cmsMobileTextAlign',node.dataset.cmsMobileTextAlign||'',textAlign)}${field('Ordem','cmsMobileOrder',node.dataset.cmsMobileOrder||'',order)}</div>${checkbox('Ocultar no celular','cmsHideMobile',node.dataset.cmsHideMobile==='1')}<p class="inspector-note">Ordem e visibilidade são independentes em desktop, tablet e celular.</p>`;
  bind(box,node);return box;
}
function install(){
  const panel=inspector.querySelector('.cms-structure-inspector');
  const node=selectedStructure();
  const existing=panel?.querySelector('.cms-layout-properties')||null;
  if(panel&&node&&existing&&lastNode===node)return;
  inspector.querySelector('.cms-layout-properties')?.remove();
  if(!panel||!node){lastNode=null;return}
  if(node.dataset.cmsContainer==='stack'||node.dataset.cmsContainer==='columns')panel.append(containerPanel(node));
  else if(node.hasAttribute('data-cms-column'))panel.append(columnPanel(node));
  lastNode=node;
}
new MutationObserver(()=>queueMicrotask(install)).observe(inspector,{childList:true,subtree:true});
frame.addEventListener('load',()=>{lastNode=null;setTimeout(install,150)});
document.addEventListener('click',()=>setTimeout(install,40),true);
})();
