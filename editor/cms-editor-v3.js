(()=>{
'use strict';
const params=new URLSearchParams(location.search),pageId=Number(params.get('page')||0);
if(!pageId){location.replace('/admin/pages.php');return;}
const $=s=>document.querySelector(s),frame=$('#page-frame'),device=$('#device-frame'),sectionsHost=$('#sections-list'),inspector=$('#inspector'),statusEl=$('#save-status'),toast=$('#toast'),textToolbar=$('#text-toolbar'),mediaDialog=$('#media-dialog'),mediaGrid=$('#media-grid');
let state={csrf:'',page:null,activity:null,forms:[],selected:null,editing:null,dirty:false,history:[],historyIndex:-1,loading:true,serial:0,saving:false,saveError:''};
let inFlightSave=null;
let lastAcknowledged=null;
function signature(){if(!state.page)return '';const settings={title:state.page.title,nav_title:state.page.navTitle,slug:state.page.slug,show_in_nav:state.page.showInNav};return JSON.stringify({document:{...state.page.document,html:serialize()},settings})}
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
const clone=v=>JSON.parse(JSON.stringify(v));
function say(message){toast.textContent=message;toast.classList.add('show');clearTimeout(say.t);say.t=setTimeout(()=>toast.classList.remove('show'),2200)}
async function json(url,options={}){const r=await fetch(url,{credentials:'same-origin',...options}),b=await r.json().catch(()=>({}));if(!r.ok){const e=new Error(b?.error?.message||b?.error?.code||`HTTP ${r.status}`);e.code=b?.error?.code;throw e;}return b}
const post=(url,payload)=>json(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({...payload,csrf:state.csrf})});
const doc=()=>frame.contentDocument,root=()=>doc()?.querySelector('[data-cms-page-main]')||doc()?.querySelector('main');
function currentSections(){return root()?[...root().children].filter(el=>el.matches('[data-cms-section]')):[]}
function label(section){return section.dataset.cmsSectionName||section.dataset.cmsSection||'Seção'}
function clearSelection(){doc()?.querySelectorAll('.cms-selection,.cms-section-selected,.cms-editing').forEach(el=>el.classList.remove('cms-selection','cms-section-selected','cms-editing'))}
function finishText(commit=true){if(!state.editing)return;const el=state.editing;const edited=el.innerHTML!==state.textOriginal;state.editing=null;state.textOriginal=null;el.removeAttribute('contenteditable');el.classList.remove('cms-editing');textToolbar.hidden=true;if(commit&&edited)changed()}
function select(el,type){finishText(true);clearSelection();state.selected={el,type};if(type==='section')el.classList.add('cms-section-selected');else el.classList.add('cms-selection');renderSections();renderInspector()}
function startText(el){finishText(true);clearSelection();state.selected={el,type:'text'};state.editing=el;state.textOriginal=el.innerHTML;el.contentEditable='true';el.classList.add('cms-editing');el.focus();textToolbar.hidden=false;renderInspector()}
function cleanClone(container){container.querySelectorAll('[contenteditable]').forEach(el=>el.removeAttribute('contenteditable'));container.querySelectorAll('.cms-selection,.cms-section-selected,.cms-editing').forEach(el=>el.classList.remove('cms-selection','cms-section-selected','cms-editing'));container.querySelectorAll('[data-cms-form-block]').forEach(block=>{const p=container.ownerDocument.createElement('div');p.dataset.cmsFormKey=block.dataset.cmsFormKey||'';block.replaceWith(p)})}
function serialize(){const r=root();if(!r)return '';const copy=r.cloneNode(true);cleanClone(copy);return copy.innerHTML.trim()}
function snapshot(){return state.page?{html:root()?.innerHTML||'',settings:{title:state.page.title,navTitle:state.page.navTitle,slug:state.page.slug,showInNav:state.page.showInNav,document:clone(state.page.document)}}:null}
function pushHistory(){if(state.loading||!state.page)return;const s=snapshot(),last=state.history[state.historyIndex];if(last&&last.html===s.html&&JSON.stringify(last.settings)===JSON.stringify(s.settings))return;state.history=state.history.slice(0,state.historyIndex+1);state.history.push(s);if(state.history.length>80)state.history.shift();state.historyIndex=state.history.length-1;historyButtons()}
function changed(history=true){
  state.dirty=true;state.serial++;state.saveError='';
  renderStatus();if(history)pushHistory();
  window.dispatchEvent(new CustomEvent('cms-editor:dirty',{detail:{serial:state.serial}}));
}
function renderStatus(){
  if(!state.page)return;
  let label='',code='';
  if(state.saveError){label='Erro ao salvar · tente novamente';code='error'}
  else if(state.saving){label='Salvando…';code='saving'}
  else if(state.dirty){label='Alterações não salvas';code='dirty'}
  else if(state.page.publishedRevision===null){label='Rascunho · fora do ar';code='draft'}
  else if(state.page.draftRevision!==state.page.publishedRevision){label='Publicado · alterações em rascunho';code='draft-with-live'}
  else {label='Publicado · atualizado';code='published'}
  statusEl.textContent=label;statusEl.dataset.state=code;
  const unpublish=document.querySelector('#unpublish-page');
  if(unpublish){unpublish.hidden=state.page.publishedRevision===null||state.page.isHome===true;unpublish.disabled=state.saving}
  const publishButton=document.querySelector('#publish-page');
  if(publishButton)publishButton.disabled=state.saving;
  window.dispatchEvent(new CustomEvent('cms-editor:status',{detail:{code,dirty:state.dirty,saving:state.saving}}));
}
function historyButtons(){$('#undo').disabled=state.historyIndex<=0;$('#redo').disabled=state.historyIndex<0||state.historyIndex>=state.history.length-1}
function restore(s){if(!s)return;finishText(false);root().innerHTML=s.html;Object.assign(state.page,{title:s.settings.title,navTitle:s.settings.navTitle,slug:s.settings.slug,showInNav:s.settings.showInNav});state.page.document=clone(s.settings.document);state.dirty=true;hookFrame();renderInspector();renderStatus()}
function undo(){if(state.historyIndex<=0)return;state.historyIndex--;restore(state.history[state.historyIndex]);historyButtons()}
function redo(){if(state.historyIndex>=state.history.length-1)return;state.historyIndex++;restore(state.history[state.historyIndex]);historyButtons()}
function unique(prefix='section'){return `${prefix}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2,7)}`}
function hookFrame(){const d=doc();if(!d)return;d.querySelectorAll('[data-cms-editable]').forEach(el=>{if(el.closest('[data-cms-form-block]'))return;el.title='Clique para editar';el.onclick=e=>{e.preventDefault();e.stopPropagation();const anchor=e.target.closest?.('a');if(anchor&&el.contains(anchor)){select(anchor,'link');return}startText(el)}});d.querySelectorAll('[data-cms-image]').forEach(el=>{if(el.closest('[data-cms-form-block]'))return;el.onclick=e=>{e.preventDefault();e.stopPropagation();select(el,'image')}});d.querySelectorAll('[data-cms-image-placeholder]').forEach(el=>{if(el.closest('[data-cms-form-block]'))return;el.onclick=e=>{e.preventDefault();e.stopPropagation();select(el,'image-placeholder')}});d.querySelectorAll('[data-cms-form-key]:not([data-cms-form-block])').forEach(el=>el.onclick=e=>{e.preventDefault();e.stopPropagation();select(el,'form')});d.querySelectorAll('[data-cms-section]').forEach(el=>el.onclick=e=>{if(e.target.closest('[data-cms-form-block]'))return;if(e.target.closest('[data-cms-editable],[data-cms-image],[data-cms-image-placeholder],[data-cms-form-key]'))return;e.preventDefault();e.stopPropagation();select(el,'section')});renderSections()}
function moveSection(section,dir){const list=currentSections(),i=list.indexOf(section),j=i+dir;if(j<0||j>=list.length)return;if(dir<0)root().insertBefore(section,list[j]);else root().insertBefore(list[j],section);changed();renderSections();select(section,'section')}
function duplicateSection(section){const copy=section.cloneNode(true);copy.dataset.cmsSection=unique(section.dataset.cmsSection||'section');copy.dataset.cmsSectionName=`${label(section)} — cópia`;section.after(copy);changed();hookFrame();select(copy,'section')}
function removeSection(section){if(!confirm(`Remover “${label(section)}”?`))return;section.remove();state.selected=null;changed();hookFrame();renderInspector()}
function renderSections(){const list=currentSections();sectionsHost.innerHTML=list.map((s,i)=>`<div class="section-row ${state.selected?.el===s?'is-selected':''}" draggable="true" data-row="${i}"><button class="section-row-main" data-select="${i}"><strong>${esc(label(s))}</strong><span>${esc(s.dataset.cmsSection||'')}</span></button><div class="section-row-actions"><button data-up="${i}">↑</button><button data-down="${i}">↓</button></div></div>`).join('');sectionsHost.querySelectorAll('[data-select]').forEach(b=>b.onclick=()=>{const s=list[Number(b.dataset.select)];select(s,'section');s.scrollIntoView({behavior:'smooth',block:'center'})});sectionsHost.querySelectorAll('[data-up]').forEach(b=>b.onclick=()=>moveSection(list[Number(b.dataset.up)],-1));sectionsHost.querySelectorAll('[data-down]').forEach(b=>b.onclick=()=>moveSection(list[Number(b.dataset.down)],1));let drag=null;sectionsHost.querySelectorAll('[data-row]').forEach(row=>{row.ondragstart=()=>drag=Number(row.dataset.row);row.ondragover=e=>e.preventDefault();row.ondrop=e=>{e.preventDefault();const to=Number(row.dataset.row);if(drag===null||drag===to)return;const fresh=currentSections(),moving=fresh[drag],target=fresh[to];if(!moving||!target)return;to>drag?target.after(moving):target.before(moving);drag=null;changed();renderSections()}})}
function pageSettings(){state.selected=null;clearSelection();inspector.innerHTML=`<div class="inspector-section"><header><p class="eyebrow">Página</p><h2>Configurações</h2></header><label>Título interno<input id="p-title" value="${esc(state.page.title)}"></label><label>Título no menu<input id="p-nav" value="${esc(state.page.navTitle)}"></label><label>Slug<input id="p-slug" value="${esc(state.page.slug)}" ${state.page.isHome?'disabled':''}></label><label class="check-row"><input id="p-show" type="checkbox" ${state.page.showInNav?'checked':''}> Exibir no menu</label><hr><label>Título SEO<input id="p-seo-title" value="${esc(state.page.document.meta?.title||'')}"></label><label>Descrição SEO<textarea id="p-seo-description">${esc(state.page.document.meta?.description||'')}</textarea></label><label>Tema<select id="p-theme"><option value="auto">Automático</option><option value="light">Claro</option><option value="dark">Escuro</option></select></label><hr><div class="button-row"><a class="panel-button" href="/admin/design.php?activity=${state.activity.id}&lang=${state.page.locale==='pt-BR'?'pt-br':'en'}" target="_blank">Design global</a><a class="panel-button" href="/admin/site.php?activity=${state.activity.id}&lang=${state.page.locale==='pt-BR'?'pt-br':'en'}" target="_blank">Header e footer</a></div></div>`;$('#p-theme').value=state.page.document.theme||'auto';inspector.querySelectorAll('input,textarea,select').forEach(el=>el.oninput=()=>{state.page.title=$('#p-title').value;state.page.navTitle=$('#p-nav').value;state.page.slug=$('#p-slug').value;state.page.showInNav=$('#p-show').checked;state.page.document.meta={title:$('#p-seo-title').value,description:$('#p-seo-description').value};state.page.document.theme=$('#p-theme').value;changed(false)})}
function sectionInspector(section){const ds=section.dataset;inspector.innerHTML=`<div class="inspector-section"><header><p class="eyebrow">Seção</p><h2>${esc(label(section))}</h2></header><label>Nome no editor<input id="s-name" value="${esc(ds.cmsSectionName||'')}"></label><label>Largura<select id="s-width"><option value="">Padrão do componente</option><option value="full">Tela inteira</option><option value="content">Conteúdo</option><option value="narrow">Estreita</option></select></label><label>Fundo<select id="s-bg"><option value="">Padrão</option><option value="surface">Superfície</option><option value="surface2">Superfície 2</option><option value="inverse">Invertido</option></select></label><label>Espaçamento vertical<select id="s-space"><option value="">Padrão</option><option value="none">Nenhum</option><option value="s">Pequeno</option><option value="m">Médio</option><option value="l">Grande</option><option value="xl">Muito grande</option></select></label><label>Proporção das colunas<select id="s-ratio"><option value="">Padrão do componente</option><option>30-70</option><option>40-60</option><option>50-50</option><option>60-40</option><option>70-30</option></select></label><label>Alinhamento<select id="s-align"><option value="">Padrão</option><option value="left">Esquerda</option><option value="center">Centro</option></select></label><label class="check-row"><input id="s-mobile" type="checkbox" ${ds.layoutMobile==='reverse'?'checked':''}> Inverter ordem no celular</label><div class="button-row"><button id="s-up">↑ Subir</button><button id="s-down">↓ Descer</button><button id="s-copy">Duplicar</button><button id="s-delete" class="danger">Remover</button></div></div>`;$('#s-width').value=ds.layoutWidth||'';$('#s-bg').value=ds.layoutBackground||'';$('#s-space').value=ds.layoutSpace||'';$('#s-ratio').value=ds.layoutRatio||'';$('#s-align').value=ds.layoutAlign||'';const sync=()=>{ds.cmsSectionName=$('#s-name').value.trim()||'Seção';for(const [id,key] of [['s-width','layoutWidth'],['s-bg','layoutBackground'],['s-space','layoutSpace'],['s-ratio','layoutRatio'],['s-align','layoutAlign']]){const v=$('#'+id).value;v?ds[key]=v:delete ds[key]}$('#s-mobile').checked?ds.layoutMobile='reverse':delete ds.layoutMobile;changed();renderSections()};inspector.querySelectorAll('input,select').forEach(el=>el.onchange=sync);$('#s-up').onclick=()=>moveSection(section,-1);$('#s-down').onclick=()=>moveSection(section,1);$('#s-copy').onclick=()=>duplicateSection(section);$('#s-delete').onclick=()=>removeSection(section)}
function imageInspector(el,type){const isImage=type==='image';const assetId=isImage?Number(el.dataset.mediaAssetId||0):0;inspector.innerHTML=`<div class="inspector-section"><header><p class="eyebrow">Imagem</p><h2>${isImage?'Propriedades':'Adicionar imagem'}</h2></header>${isImage?`<label>Texto alternativo<input id="i-alt" value="${esc(el.alt||'')}"></label><label>Ajuste<select id="i-fit"><option value="cover">Preencher / cortar</option><option value="contain">Conter inteira</option></select></label><label>Ponto focal horizontal <output id="i-x-out"></output><input id="i-x" type="range" min="0" max="100" value="${esc(el.dataset.focalX||50)}"></label><label>Ponto focal vertical <output id="i-y-out"></output><input id="i-y" type="range" min="0" max="100" value="${esc(el.dataset.focalY||50)}"></label>${assetId?`<p class="inspector-note">Asset #${assetId}. A página mantém a referência à biblioteca; trocar a versão do asset não exige trocar a URL manualmente.</p>`:''}`:'<p class="inspector-note">Escolha uma imagem da biblioteca. O vínculo será salvo pelo ID do asset, não apenas pelo endereço do arquivo.</p>'}<div class="button-row"><button id="i-choose" class="primary">Biblioteca de mídia</button>${isImage?'<button id="i-remove" class="danger">Remover</button>':''}</div></div>`;if(isImage){$('#i-fit').value=el.dataset.fit||'cover';const sync=()=>{const before=[el.alt,el.dataset.fit||'cover',el.dataset.focalX||'50',el.dataset.focalY||'50'];const after=[$('#i-alt').value,$('#i-fit').value,$('#i-x').value,$('#i-y').value];el.alt=after[0];el.dataset.fit=after[1];el.dataset.focalX=after[2];el.dataset.focalY=after[3];el.style.objectFit=after[1];el.style.objectPosition=after[2]+'% '+after[3]+'%';$('#i-x-out').value=after[2]+'%';$('#i-y-out').value=after[3]+'%';if(before.some((v,i)=>v!==after[i]))changed(false)};inspector.querySelectorAll('#i-alt,#i-fit,#i-x,#i-y').forEach(n=>n.oninput=sync);$('#i-x-out').value=$('#i-x').value+'%';$('#i-y-out').value=$('#i-y').value+'%';$('#i-remove').onclick=()=>{if(!confirm('Remover esta imagem?'))return;const p=doc().createElement('div');p.className='cms-media-placeholder';p.dataset.cmsImagePlaceholder='';p.innerHTML='<span>Adicione uma imagem</span>';el.replaceWith(p);changed();hookFrame();select(p,'image-placeholder')}}$('#i-choose').onclick=()=>openMedia(el,type)}
async function openMedia(el,type){mediaDialog.showModal();mediaGrid.innerHTML='<p>Carregando…</p>';try{await MediaLibrary.load();mediaGrid.innerHTML=MediaLibrary.images.map((item,i)=>`<button type="button" class="media-item" data-media="${i}"><img src="${esc(item.src)}" alt=""><span>${esc(item.label)}</span></button>`).join('')||'<p>Nenhuma imagem.</p>';mediaGrid.querySelectorAll('[data-media]').forEach(b=>b.onclick=()=>applyMedia(el,type,MediaLibrary.images[Number(b.dataset.media)]))}catch(e){mediaGrid.innerHTML='<p>Não foi possível carregar a biblioteca.</p>'}}
function applyMedia(el,type,item){let img=el;if(type!=='image'){const wrapper=el.closest('[data-cms-image-block]');img=doc().createElement('img');img.dataset.cmsImage='';if(wrapper)el.replaceWith(img);else{const figure=doc().createElement('figure');figure.className='cms-image-generic';figure.dataset.cmsImageBlock='';figure.append(img);el.replaceWith(figure)}}img.src=item.src;img.alt=item.alt||item.label||'';img.dataset.mediaAssetId=String(item.id);img.dataset.mediaVersionId=String(item.versionId||'');img.dataset.focalX=String(item.focal_x??50);img.dataset.focalY=String(item.focal_y??50);img.dataset.fit='cover';changed();mediaDialog.close();hookFrame();select(img,'image')}
function formInspector(el){const key=el.dataset.cmsFormKey||'',id=Number(el.dataset.cmsFormBlock||0);inspector.innerHTML=`<div class="inspector-section"><header><p class="eyebrow">Formulário</p><h2>${esc(key||'Formulário')}</h2></header><label>Formulário usado<select id="f-key">${state.forms.map(f=>`<option value="${esc(f.key)}" ${f.key===key?'selected':''}>${esc(f.title)} · ${esc(f.locale)}</option>`).join('')}</select></label><p class="inspector-note">Campos, validações e mensagens são editados no gerenciador de formulários. A página controla apenas onde o formulário aparece.</p><div class="button-row"><a class="panel-button primary" href="/admin/forms.php?activity=${state.activity.id}${id?'&form='+id:''}" target="_blank">Editar campos</a></div></div>`;$('#f-key').onchange=()=>{el.dataset.cmsFormKey=$('#f-key').value;changed()}}
function validLinkDestination(raw){
  const href=String(raw||'').trim();
  if(!href||Array.from(href).some(ch=>ch.charCodeAt(0)<=32||ch.charCodeAt(0)===127))return '';
  const lower=href.toLowerCase();
  return ['https://','http://','mailto:','tel:','/','#','./','../'].some(prefix=>lower.startsWith(prefix))?href:'';
}
function linkInspector(link){
  if(!link?.isConnected)return;
  inspector.innerHTML='<div class="inspector-section" data-cms-link-inspector><header><p class="eyebrow">Link</p><h2>Editar vínculo</h2></header>'+
    '<label>Texto exibido<input id="cms-link-text"></label>'+
    '<label>Endereço<input id="cms-link-href" type="text" spellcheck="false" placeholder="https://exemplo.com, /pagina ou #secao"></label>'+
    '<label class="check-row"><input id="cms-link-newtab" type="checkbox"> Abrir em outra aba</label>'+
    '<p class="inspector-note" id="cms-link-error" role="alert" hidden>Informe uma URL válida (https, http, mailto, tel, / ou #).</p>'+
    '<div class="button-row"><button type="button" id="cms-link-apply" class="primary">Aplicar</button><button type="button" id="cms-link-test">Testar</button><button type="button" id="cms-link-text-edit">Editar no texto</button><button type="button" id="cms-link-remove" class="danger">Remover link</button></div></div>';
  $('#cms-link-text').value=link.textContent||'';
  $('#cms-link-href').value=link.getAttribute('href')||'';
  $('#cms-link-newtab').checked=link.target==='_blank';
  const error=$('#cms-link-error'),hrefInput=$('#cms-link-href');
  const apply=()=>{
    const href=validLinkDestination(hrefInput.value);
    error.hidden=!!href;
    if(!href)return false;
    const label=$('#cms-link-text').value;
    const newTab=$('#cms-link-newtab').checked;
    let changedLink=false;
    const update=(attr,value)=>{if(link.getAttribute(attr)!==value){link.setAttribute(attr,value);changedLink=true}};
    update('href',href);
    if(link.textContent!==label){link.textContent=label;changedLink=true}
    if(newTab){update('target','_blank');update('rel','noopener noreferrer')}
    else{if(link.hasAttribute('target')){link.removeAttribute('target');changedLink=true}if(link.hasAttribute('rel')){link.removeAttribute('rel');changedLink=true}}
    if(changedLink)changed();
    return true;
  };
  $('#cms-link-apply').onclick=apply;
  $('#cms-link-test').onclick=()=>{if(!apply())return;window.open(link.href,'_blank','noopener,noreferrer')};
  $('#cms-link-text-edit').onclick=()=>startText(link.closest('[data-cms-editable]')||link);
  $('#cms-link-remove').onclick=()=>{
    const parent=link.parentElement;
    link.replaceWith(doc().createTextNode(link.textContent||''));
    changed();
    if(parent?.closest('[data-cms-editable]'))startText(parent.closest('[data-cms-editable]'));
    else{state.selected=null;renderInspector()}
  };
}
let pendingLinkRange=null;
function openCreateLink(){
  const selection=frame.contentWindow?.getSelection();
  const range=pendingLinkRange||(!selection?.isCollapsed?selection?.getRangeAt(0)?.cloneRange():null);
  pendingLinkRange=null;
  const owner=range?.commonAncestorContainer?.nodeType===Node.ELEMENT_NODE?range.commonAncestorContainer:range?.commonAncestorContainer?.parentElement;
  if(!range||range.collapsed||!owner?.closest?.('[data-cms-editable]')){say('Selecione um trecho de texto para criar o link.');return}
  if(range.cloneContents().querySelector?.('a')){say('Selecione somente texto que ainda não contenha um link.');return}
  let dialog=$('#cms-link-dialog');
  if(!dialog){
    dialog=document.createElement('dialog');dialog.id='cms-link-dialog';dialog.className='editor-dialog';
    dialog.innerHTML='<form method="dialog"><header><div><p>Texto</p><h2>Inserir link</h2></div><button value="cancel" aria-label="Cancelar">×</button></header>'+
      '<div style="display:grid;gap:12px;padding:18px"><label>Endereço<input id="cms-create-link-href" type="text" placeholder="https://exemplo.com, /pagina ou #secao" required></label>'+
      '<label class="check-row"><input id="cms-create-link-newtab" type="checkbox"> Abrir em outra aba</label>'+
      '<p id="cms-create-link-error" role="alert" hidden>Informe um endereço válido.</p>'+
      '<div class="button-row"><button type="button" id="cms-create-link-apply" class="primary">Inserir link</button><button value="cancel">Cancelar</button></div></div></form>';
    document.body.append(dialog);
  }
  const apply=()=>{
    const href=validLinkDestination($('#cms-create-link-href').value);
    $('#cms-create-link-error').hidden=!!href;
    if(!href)return;
    const a=doc().createElement('a');
    a.href=href;
    if($('#cms-create-link-newtab').checked){a.target='_blank';a.rel='noopener noreferrer'}
    a.append(range.extractContents());
    range.insertNode(a);
    dialog.close();changed();select(a,'link');
  };
  $('#cms-create-link-href').value='';
  $('#cms-create-link-newtab').checked=false;
  $('#cms-create-link-error').hidden=true;
  $('#cms-create-link-apply').onclick=apply;
  dialog.showModal();
  $('#cms-create-link-href').focus();
}

function renderInspector(){if(!state.page){inspector.innerHTML='<div class="inspector-empty">Carregando…</div>';return}if(!state.selected){inspector.innerHTML='<div class="inspector-empty"><p>Selecione um elemento na página.</p><button type="button" class="panel-button" id="open-settings">Configurações da página</button></div>';$('#open-settings').onclick=pageSettings;return}const {el,type}=state.selected;if(type==='section')sectionInspector(el);else if(type==='image'||type==='image-placeholder')imageInspector(el,type);else if(type==='form')formInspector(el);else if(type==='link')linkInspector(el);else if(type==='text')inspector.innerHTML='<div class="inspector-section"><header><p class="eyebrow">Texto</p><h2>Edição direta</h2></header><p class="inspector-note">Edite na própria página. A barra flutuante oferece negrito, itálico e link.</p></div>'}
async function persistUntilCurrent(){
    state.saving=true;state.saveError='';renderStatus();
    try{
      for(;;){
        const serial=state.serial;
        const document={...state.page.document,html:serialize()};
        const settings={title:state.page.title,nav_title:state.page.navTitle,slug:state.page.slug,show_in_nav:state.page.showInNav};
        const before=JSON.stringify({document,settings});
        const r=await post('/admin/api/cms-page-save.php',{pageId:state.page.id,revision:state.page.draftRevision,document,settings});
        // Revision advances even if further local edits happened while awaiting the server.
        state.page.draftRevision=r.page.draftRevision;
        state.page.publishedRevision=r.page.publishedRevision;
        const after=JSON.stringify({document:{...state.page.document,html:serialize()},settings:{title:state.page.title,nav_title:state.page.navTitle,slug:state.page.slug,show_in_nav:state.page.showInNav}});
        if(serial===state.serial&&before===after){
          Object.assign(state.page,r.page);
          state.dirty=false;
          lastAcknowledged=signature();
          break;
        }
        // Local content is newer than the ACK. Keep it intact and persist it in order.
        state.dirty=true;
      }
      state.saveError='';renderStatus();
      $('#editor-title').textContent=state.page.title;
      say('Página salva.');
      return state.page;
    }catch(e){
      state.dirty=true;
      state.saveError=e.code==='revision_conflict'?'Conflito de revisão: mantenha esta aba aberta e recupere o conteúdo antes de recarregar.':e.message;
      renderStatus();say(state.saveError);
      throw e;
    }finally{state.saving=false;renderStatus()}
}
function save(){
  finishText(true);
  if(inFlightSave)return inFlightSave;
  if(!state.dirty&&signature()!==lastAcknowledged)changed(false);
  if(!state.dirty)return Promise.resolve(state.page);
  const request=persistUntilCurrent();
  inFlightSave=request;
  request.finally(()=>{if(inFlightSave===request)inFlightSave=null}).catch(()=>{});
  return request;
}
async function publish(){
  try{
    await save();
    if(state.dirty||state.saving)throw new Error('Finalize o salvamento antes de publicar.');
    const revision=state.page.draftRevision;
    if(!confirm('Publicar a revisão '+revision+' desta página? Ela ficará disponível à audiência configurada.'))return;
    const r=await post('/admin/api/cms-page-publish.php',{pageId:state.page.id,revision});
    state.page.publishedRevision=r.publishedRevision;
    state.page.publishedAt=r.publishedAt;renderStatus();say('Revisão publicada.');
  }catch(e){state.saveError=e.code==='revision_conflict'?'A página mudou em outra sessão. Publicação bloqueada.':e.message;renderStatus();say(state.saveError)}
}
async function unpublish(){
  if(!state.page||state.page.publishedRevision===null||state.page.isHome)return;
  if(!confirm('Retirar esta página do ar? O rascunho e os vínculos acadêmicos serão preservados.'))return;
  try{
    await save();
    const r=await post('/admin/api/cms-page-unpublish.php',{pageId:state.page.id,revision:state.page.draftRevision});
    state.page.publishedRevision=r.publishedRevision;state.page.publishedAt=null;
    state.page.showInNav=false;renderStatus();say('Página retirada da publicação. O rascunho permanece editável.');
  }catch(e){state.saveError=e.message;renderStatus();say(state.saveError)}
}
window.CmsEditorDraft={save,isDirty:()=>state.dirty||state.saving,getState:()=>({dirty:state.dirty,saving:state.saving,error:state.saveError,serial:state.serial,revision:state.page?.draftRevision})};
function reload(){state.selected=null;state.editing=null;textToolbar.hidden=true;frame.src=`/preview/?page=${state.page.id}&v=${Date.now()}`}
async function load(){try{const data=await json(`/admin/api/cms-page-load.php?page=${pageId}`);state.csrf=data.csrf;state.page=data.page;state.activity=data.activity;state.forms=data.forms||[];$('#editor-title').textContent=state.page.title;$('#editor-locale').textContent=state.page.locale;frame.src=`/preview/?page=${pageId}`;renderStatus()}catch(e){say(e.message);setTimeout(()=>location.replace('/admin/pages.php'),1400)}}
frame.addEventListener('load',()=>{if(!state.page)return;state.loading=false;hookFrame();if(lastAcknowledged===null)lastAcknowledged=signature();const d=doc();if(d&&!d.__cmsDirtyBound){d.__cmsDirtyBound=true;d.addEventListener('input',event=>{if(event.target?.closest?.('[data-cms-editable][contenteditable]'))changed(false)},true)}state.history=[snapshot()];state.historyIndex=0;historyButtons();renderInspector();renderStatus()});
$('#save-page').onclick=()=>save().catch(()=>{});$('#publish-page').onclick=publish;if($('#unpublish-page'))$('#unpublish-page').onclick=unpublish;$('#page-settings').onclick=pageSettings;$('#undo').onclick=undo;$('#redo').onclick=redo;document.querySelectorAll('[data-viewport]').forEach(b=>b.onclick=()=>{document.querySelectorAll('[data-viewport]').forEach(x=>x.classList.remove('active'));b.classList.add('active');device.className=`device-frame ${b.dataset.viewport}`});textToolbar.querySelectorAll('[data-command]').forEach(b=>{if(b.dataset.command==='createLink')b.addEventListener('pointerdown',()=>{const sel=frame.contentWindow?.getSelection();pendingLinkRange=sel&&!sel.isCollapsed?sel.getRangeAt(0).cloneRange():null});b.onclick=()=>{if(b.dataset.command==='createLink'){openCreateLink();return}doc().execCommand(b.dataset.command,false,null);changed(false)}});$('#finish-text').onclick=()=>finishText(true);$('#media-upload').onchange=async e=>{const file=e.target.files?.[0];if(!file)return;try{await MediaLibrary.load();const uploaded=await MediaLibrary.upload(file);say('Imagem enviada.');if(mediaDialog.open&&state.selected&&(state.selected.type==='image'||state.selected.type==='image-placeholder')){const image=MediaLibrary.images.find(item=>Number(item.id)===Number(uploaded.id));if(image)applyMedia(state.selected.el,state.selected.type,image);else openMedia(state.selected.el,state.selected.type)}}catch(error){say(error.message)}finally{e.target.value=''}};
load();
})();
