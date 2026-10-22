/**
 * Isolated e-mail composer proof of concept.
 *
 * No persistence and NO sending. Never treat client-side HTML as safe for delivery:
 * a future backend must validate the versioned MJML document and media IDs.
 */
import grapesjs from 'grapesjs';
import mjmlModule from 'grapesjs-mjml';

const mjmlPlugin=typeof mjmlModule==='function'?mjmlModule:mjmlModule?.default;
const STARTER=[
  '<mjml><mj-head><mj-attributes>',
  '<mj-all font-family="Arial,Helvetica,sans-serif" />',
  '</mj-attributes></mj-head><mj-body background-color="#f1f0ed">',
  '<mj-section background-color="#ffffff" padding="28px 24px 14px">',
  '<mj-column><mj-text font-size="22px" font-weight="bold" color="#202427">Ateliê fotográfico</mj-text></mj-column>',
  '</mj-section><mj-section background-color="#ffffff" padding="12px 24px 30px">',
  '<mj-column><mj-text font-size="16px" line-height="1.65" color="#252525">Escreva aqui a sua mensagem. Selecione o texto ou arraste novos blocos.</mj-text>',
  '<mj-button background-color="#186f4d" href="https://example.org/" border-radius="0">Saiba mais</mj-button>',
  '</mj-column></mj-section></mj-body></mjml>',
].join('');
function setStatus(element,text,failed=false){
  if(!element)return;
  element.textContent=text;
  element.dataset.state=failed?'error':'ok';
}
function escapeAttribute(value){
  return String(value).replaceAll('&','&amp;').replaceAll('"','&quot;').replaceAll('<','&lt;').replaceAll('>','&gt;');
}
function validateLinksAndMedia(mjml,allowedMedia){
  if(/<mj-(?:raw|style|font|social|navbar)\b/i.test(mjml))
    throw Error('Este bloco não é autorizado no formato de e-mail do CMS.');
  const images=[...mjml.matchAll(/<mj-image\b[^>]*\bsrc="([^"]*)"[^>]*>/gi)];
  if((mjml.match(/<mj-image\b/gi)||[]).length!==images.length)
    throw Error('Uma imagem está sem origem autorizada.');
  for(const entry of images){
    const src=entry[1].replaceAll('&amp;','&');
    if(!allowedMedia.has(src))throw Error('A imagem precisa ser escolhida na Biblioteca pública.');
    if(!/\balt="[^"]+"/i.test(entry[0]))throw Error('Informe o texto alternativo de cada imagem.');
  }
  for(const entry of mjml.matchAll(/\bhref="([^"]*)"/gi)){
    const value=entry[1].replaceAll('&amp;','&');
    if(!/^https:\/\/[^\s"]+$/i.test(value)&&!/^mailto:[^\s"@]+@[^\s"@]+\.[^\s"@]+$/i.test(value))
      throw Error('Os links precisam usar HTTPS ou um e-mail válido.');
  }
}
function initLab(){
  const root=document.querySelector('[data-communications-email-lab]');
  if(!root)return;
  const status=root.querySelector('[data-lab-status]');
  const mount=root.querySelector('[data-lab-canvas]');
  const preview=root.querySelector('[data-lab-preview]');
  const source=root.querySelector('[data-lab-source]');
  const mediaPicker=root.querySelector('[data-lab-media]');
  const mediaAlt=root.querySelector('[data-lab-media-alt]');
  const mediaStatus=root.querySelector('[data-lab-media-status]');
  if(!mount||!preview||!source)return;
  if(typeof mjmlPlugin!=='function'){
    setStatus(status,'Não foi possível carregar a biblioteca MJML.',true);return;
  }
  const allowedMedia=new Map();
  let editor;
  try{
    editor=grapesjs.init({
      container:mount,
      fromElement:false,
      height:'630px',
      storageManager:false,
      allowScripts:0,
      avoidInlineStyle:false,
      noticeOnUnload:false,
      components:STARTER,
      assetManager:{assets:[],upload:false,uploadFile:undefined},
      plugins:[ed=>mjmlPlugin(ed,{
        resetBlocks:true,resetDevices:true,resetStyleManager:true,
        blocks:['mj-1-column','mj-2-columns','mj-text','mj-image','mj-button','mj-divider','mj-spacer'],
      })],
    });
  }catch(error){
    setStatus(status,'Falha ao iniciar o editor: '+error.message,true);
    return;
  }
  const findComponent=(type)=>{
    // GrapesJS MJML components have component types; CSS selector-based find()
    // does not match those types consistently across GrapesJS versions.
    const match=component=>component&&(
      String(component.get('type')||'').toLowerCase()===type||
      String(component.get('tagName')||'').toLowerCase()===type
    );
    let selected=editor.getSelected();
    while(selected){if(match(selected))return selected;selected=selected.parent();}
    const walk=component=>{
      if(match(component))return component;
      const children=component.components();
      for(let i=0;i<children.length;i++){
        const found=walk(children.at(i));
        if(found)return found;
      }
      return null;
    };
    return walk(editor.getWrapper());
  };
  const exportEmail=()=>{
    const mjml=editor.getHtml();
    validateLinksAndMedia(mjml,allowedMedia);
    const result=editor.runCommand('mjml-code-to-html');
    if(!result||typeof result.html!=='string'||!result.html.trim())throw Error('O compilador não produziu HTML.');
    const errors=(result.errors||[]).map(x=>x.formattedMessage||x.message||String(x));
    if(errors.length)throw Error('MJML inválido: '+errors.join(' | '));
    if(!/<table\b/i.test(result.html))throw Error('O HTML gerado não contém estrutura de e-mail.');
    return {mjml,html:result.html,errors,project:editor.getProjectData()};
  };
  const roundTrip=()=>{
    const before=editor.getHtml();
    const saved=editor.getProjectData();
    editor.loadProjectData(saved);
    const after=editor.getHtml();
    if(before!==after)throw Error('A reabertura alterou a estrutura MJML.');
    return {before,after,project:saved};
  };
  const doExport=()=>{
    try{
      const result=exportEmail();
      source.value=result.mjml;
      preview.srcdoc=result.html;
      setStatus(status,'Documento compilado. Prévia gerada; nenhum e-mail foi enviado.');
      return result;
    }catch(e){setStatus(status,e.message,true);return null;}
  };
  root.querySelector('[data-lab-export]')?.addEventListener('click',doExport);
  root.querySelector('[data-lab-reopen]')?.addEventListener('click',()=>{
    try{roundTrip();setStatus(status,'Salvamento e reabertura em memória preservaram o MJML.');}
    catch(e){setStatus(status,e.message,true);}
  });
  root.querySelector('[data-lab-undo]')?.addEventListener('click',()=>editor.UndoManager.undo());
  root.querySelector('[data-lab-redo]')?.addEventListener('click',()=>editor.UndoManager.redo());
  root.querySelector('[data-lab-desktop]')?.addEventListener('click',()=>editor.setDevice('Desktop'));
  root.querySelector('[data-lab-mobile]')?.addEventListener('click',()=>editor.setDevice('Mobile'));
  root.querySelector('[data-lab-add-text]')?.addEventListener('click',()=>{
    const column=findComponent('mj-column');
    if(!column){setStatus(status,'Selecione uma coluna para inserir texto.',true);return;}
    column.append('<mj-text font-size="16px" color="#202427">Novo texto. Edite diretamente no quadro.</mj-text>');
  });
  root.querySelector('[data-lab-add-columns]')?.addEventListener('click',()=>{
    const body=findComponent('mj-body');
    if(!body){setStatus(status,'Corpo do e-mail não encontrado.',true);return;}
    body.append('<mj-section background-color="#ffffff" padding="14px 24px"><mj-column><mj-text>Coluna esquerda</mj-text></mj-column><mj-column><mj-text>Coluna direita</mj-text></mj-column></mj-section>');
  });
  root.querySelector('[data-lab-add-media]')?.addEventListener('click',()=>{
    const id=mediaPicker?.value;
    const asset=allowedMedia.get(id);
    const alt=mediaAlt?.value.trim()||'';
    const col=findComponent('mj-column');
    if(!col){setStatus(status,'Selecione uma coluna antes de inserir a imagem.',true);return;}
    if(!asset||!alt){setStatus(status,'Escolha uma imagem e informe a descrição.',true);return;}
    col.append('<mj-image src="'+escapeAttribute(asset.src)+'" alt="'+escapeAttribute(alt)+'" padding="14px 0" />');
    setStatus(status,'Imagem da Biblioteca inserida na composição.');
  });
  const mediaEndpoint=root.dataset.mediaEndpoint;
  if(mediaEndpoint&&mediaPicker){
    fetch(mediaEndpoint,{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}})
      .then(async response=>{const json=await response.json();if(!response.ok)throw Error(json.error||'Falha ao carregar a Biblioteca.');return json;})
      .then(data=>{
        for(const item of data.items||[]){
          const url=new URL(item.src,location.origin);
          if(url.origin!==location.origin||!url.pathname.startsWith('/uploads/'))continue;
          const media={id:String(item.id),src:url.href,alt:item.alt||'',title:item.title||'Imagem'};
          allowedMedia.set(media.id,media);
          allowedMedia.set(media.src,media);
          const option=document.createElement('option');option.value=media.id;option.textContent=media.title;
          mediaPicker.append(option);
        }
        if(allowedMedia.size===0)setStatus(mediaStatus,'Nenhuma imagem pública disponível.');
        else{
          setStatus(mediaStatus,(allowedMedia.size/2)+' imagem(ns) da Biblioteca.');
          mediaPicker.dispatchEvent(new Event('change'));
        }
      })
      .catch(e=>setStatus(mediaStatus,e.message,true));
    mediaPicker.addEventListener('change',()=>{
      const asset=allowedMedia.get(mediaPicker.value);
      if(asset&&mediaAlt)mediaAlt.value=asset.alt;
    });
  }
  editor.on('load',()=>{
    const blocks=editor.BlockManager.getAll();
    for(const block of [...blocks.models]){
      const name=String(block.getId()||'');
      if(/raw|social|navbar|hero|font|style|code/i.test(name))editor.BlockManager.remove(name);
    }
    setStatus(status,'Construtor carregado. Edite blocos e gere a prévia para conferir o HTML.');
  });
  window.__communicationsEmailLab={editor,exportEmail,roundTrip,doExport,findComponent,allowedMedia,ready:true};
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initLab,{once:true});
else initLab();
