(()=>{
'use strict';
let panel=null,localSnapshot=null;
function ensure(){
  if(panel)return panel;
  const dialog=document.createElement('dialog');
  dialog.id='cms-conflict-recovery';
  dialog.className='cms-conflict-recovery';
  dialog.setAttribute('aria-labelledby','cms-conflict-title');
  dialog.innerHTML='<div class="cms-conflict-content">'+
    '<h2 id="cms-conflict-title">Outra edição foi salva antes da sua</h2>'+
    '<p>O servidor possui uma revisão mais recente. Sua edição continua aberta neste navegador; nenhuma alteração local foi enviada sobre a versão mais nova.</p>'+
    '<p id="cms-conflict-revisions" role="status"></p>'+
    '<p>Guarde uma cópia antes de fechar a aba ou carregar novamente a página. O arquivo JSON contém seu texto, imagens referenciadas e configurações, mas não substitui a publicação.</p>'+
    '<div class="cms-conflict-actions">'+
    '<button type="button" data-conflict-download>Baixar minha edição (.json)</button>'+
    '<button type="button" data-conflict-copy>Copiar HTML da página</button>'+
    '<button type="button" data-conflict-check>Verificar revisão do servidor</button>'+
    '<button type="button" data-conflict-close>Continuar editando</button>'+
    '</div><p id="cms-conflict-feedback" role="status" aria-live="polite"></p>'+
    '</div>';
  document.body.append(dialog);
  const feedback=()=>dialog.querySelector('#cms-conflict-feedback');
  dialog.querySelector('[data-conflict-download]').addEventListener('click',()=>{
    if(!localSnapshot)return;
    const backup=JSON.stringify(localSnapshot,null,2);
    const url=URL.createObjectURL(new Blob([backup],{type:'application/json;charset=utf-8'}));
    const link=document.createElement('a');link.href=url;link.download='rascunho-pagina-'+localSnapshot.pageId+'-revisao-'+localSnapshot.basedOnRevision+'.json';
    document.body.append(link);link.click();link.remove();
    // Download has started; the local draft remains active in memory.
    setTimeout(()=>URL.revokeObjectURL(url),15000);
    feedback().textContent='Cópia solicitada. Confira o arquivo antes de sair do editor.';
  });
  dialog.querySelector('[data-conflict-copy]').addEventListener('click',async()=>{
    if(!localSnapshot)return;
    try{
      if(!navigator.clipboard?.writeText)throw new Error('clipboard_unavailable');
      await navigator.clipboard.writeText(localSnapshot.document.html||'');
      feedback().textContent='HTML copiado. Sua edição continua aberta.';
    }catch{feedback().textContent='Não foi possível copiar. Use “Baixar minha edição”.'}
  });
  dialog.querySelector('[data-conflict-check]').addEventListener('click',async()=>{
    if(!localSnapshot)return;
    const button=dialog.querySelector('[data-conflict-check]');
    button.disabled=true;
    try{
      const response=await fetch('/admin/api/cms-page-load.php?page='+encodeURIComponent(localSnapshot.pageId),{credentials:'same-origin',cache:'no-store'});
      if(!response.ok)throw new Error('server_unavailable');
      const result=await response.json();
      const revision=Number(result.page?.draftRevision);
      feedback().textContent=Number.isInteger(revision)&&revision>0?'A revisão atual no servidor é '+revision+'. Sua edição local permanece intacta.':'O servidor não informou a revisão atual.';
    }catch{feedback().textContent='Não foi possível consultar a revisão. Sua edição local permanece intacta.'}
    finally{button.disabled=false}
  });
  dialog.querySelector('[data-conflict-close]').addEventListener('click',()=>dialog.close());
  panel=dialog;
  return dialog;
}
function show({pageId,basedOnRevision,title,document,settings}){
  if(!pageId||!document||typeof document.html!=='string')return;
  // A detached JSON snapshot is captured before the user can reload or modify the draft.
  localSnapshot={format:'workshop-cms-editor-recovery-v1',capturedAt:new Date().toISOString(),
    pageId:Number(pageId),basedOnRevision:Number(basedOnRevision),title:String(title||''),
    document:JSON.parse(JSON.stringify(document)),settings:JSON.parse(JSON.stringify(settings||{}))};
  const dialog=ensure();
  dialog.querySelector('#cms-conflict-revisions').textContent='Sua edição partiu da revisão '+localSnapshot.basedOnRevision+'.';
  dialog.querySelector('#cms-conflict-feedback').textContent='';
  if(!dialog.open)dialog.showModal();
  dialog.querySelector('[data-conflict-download]').focus();
}
window.CmsConflictRecovery={show};
})();
