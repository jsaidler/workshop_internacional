(()=>{
'use strict';
const frame=document.querySelector('#page-frame'),inspector=document.querySelector('#inspector'),save=document.querySelector('#save-page');
if(!frame||!inspector||!save)return;
let timer=0,lastSection=null;
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
const selectedSection=()=>frame.contentDocument?.querySelector('.cms-section-selected[data-cms-section]')||null;
const scheduleSave=()=>{clearTimeout(timer);timer=setTimeout(()=>save.click(),450)};
function select(id,value,options){return `<label>${id}<select data-r="${esc(value)}">${options.map(([v,l])=>`<option value="${esc(v)}" ${v===value?'selected':''}>${esc(l)}</option>`).join('')}</select></label>`}
function field(label,key,value,options){return `<label>${label}<select data-responsive-key="${key}">${options.map(([v,l])=>`<option value="${v}" ${v===value?'selected':''}>${l}</option>`).join('')}</select></label>`}
const inherit=[['','Herdar']];
const columnsTablet=[...inherit,['1','1 coluna'],['2','2 colunas'],['3','3 colunas'],['4','4 colunas']];
const columnsMobile=[...inherit,['1','1 coluna'],['2','2 colunas']];
const gaps=[...inherit,['s','Compacto'],['m','Médio'],['l','Amplo']];
const aligns=[...inherit,['start','Início'],['center','Centro'],['end','Fim']];
const spaces=[...inherit,['none','Nenhum'],['s','Pequeno'],['m','Médio'],['l','Grande'],['xl','Muito grande']];
const order=[...inherit,['normal','Normal'],['reverse','Inverter primeiro/último']];
function setDataset(section,key,value){if(value)section.dataset[key]=value;else delete section.dataset[key]}
function install(){
  const section=selectedSection(),panel=inspector.querySelector('.inspector-section');
  if(!section||!panel)return;
  if(panel.querySelector('.responsive-editor-panel')&&lastSection===section)return;
  panel.querySelector('.responsive-editor-panel')?.remove();lastSection=section;
  const box=document.createElement('div');box.className='responsive-editor-panel cms-pro-panel';
  box.innerHTML=`<hr><p class="eyebrow">Responsividade</p><h3>Tablet</h3>
    <div class="responsive-control-grid">
      ${field('Colunas','tabletColumns',section.dataset.cmsTabletColumns||'',columnsTablet)}
      ${field('Espaço entre colunas','tabletGap',section.dataset.cmsTabletGap||'',gaps)}
      ${field('Alinhamento','tabletAlign',section.dataset.cmsTabletAlign||'',aligns)}
      ${field('Espaçamento vertical','tabletSpace',section.dataset.cmsTabletSpace||'',spaces)}
      ${field('Ordem','tabletOrder',section.dataset.cmsTabletOrder||'',order)}
    </div>
    <label class="check-row"><input type="checkbox" data-responsive-check="tabletHidden" ${section.dataset.cmsHiddenTablet==='1'?'checked':''}> Ocultar no tablet</label>
    <h3>Celular</h3>
    <div class="responsive-control-grid">
      ${field('Colunas','mobileColumns',section.dataset.cmsMobileColumns||'',columnsMobile)}
      ${field('Espaço entre colunas','mobileGap',section.dataset.cmsMobileGap||'',gaps)}
      ${field('Alinhamento','mobileAlign',section.dataset.cmsMobileAlign||'',aligns)}
      ${field('Espaçamento vertical','mobileSpace',section.dataset.cmsMobileSpace||'',spaces)}
      ${field('Ordem','mobileOrder',section.dataset.cmsMobileOrder||'',order)}
    </div>
    <label class="check-row"><input type="checkbox" data-responsive-check="mobileHidden" ${section.dataset.cmsHiddenMobile==='1'?'checked':''}> Ocultar no celular</label>
    <p class="inspector-note">Os valores vazios herdam o layout principal. Assim você só cria exceções quando realmente precisa.</p>`;
  panel.append(box);
  box.querySelectorAll('[data-responsive-key]').forEach(control=>control.onchange=()=>{
    const key=control.dataset.responsiveKey;setDataset(section,key,control.value);scheduleSave();
  });
  box.querySelectorAll('[data-responsive-check]').forEach(control=>control.onchange=()=>{
    const key=control.dataset.responsiveCheck;setDataset(section,key,control.checked?'1':'');scheduleSave();
  });
}
new MutationObserver(()=>queueMicrotask(install)).observe(inspector,{childList:true,subtree:true});
frame.addEventListener('load',()=>{lastSection=null;setTimeout(install,100)});
document.addEventListener('click',()=>setTimeout(install,50),true);
})();
