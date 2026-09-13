(()=>{
'use strict';
const params=new URLSearchParams(location.search);
const pageId=Number(params.get('page')||0);
if(!pageId)return;
const $=s=>document.querySelector(s);
const switcher=$('#editor-page-switcher');
const siteName=$('#editor-site-name');
const submissions=$('#editor-submissions');
const newCount=$('#editor-new-count');
const publicLink=$('#editor-public-link');
const mediaMaintenance=$('#media-maintenance-link');
const activityLinks={
  '#editor-pages-link':'/admin/pages.php',
  '#editor-navigation-link':'/admin/site.php',
  '#editor-design-link':'/admin/design.php',
  '#editor-seo-link':'/admin/seo.php',
  '#editor-media-link':'/admin/media.php'
};
function dirty(){return ($('#save-status')?.textContent||'').toLowerCase().includes('não salvas')}
function leaveTo(url){
  if(dirty()&&!confirm('Há alterações não salvas nesta página. Sair mesmo assim?'))return false;
  location.href=url;
  return true;
}
async function loadContext(){
  try{
    const response=await fetch('/admin/api/editor-context.php?page='+pageId,{credentials:'same-origin'});
    const data=await response.json();
    if(!response.ok)throw new Error(data?.error?.code||'context_error');
    const activity=data.activity||{};
    if(siteName)siteName.textContent=activity.title||'Site';
    document.title=`Editar site — ${activity.title||'Workshop'}`;
    if(switcher){
      switcher.innerHTML='';
      const groups=new Map();
      for(const page of data.pages||[]){
        const locale=page.locale==='pt-BR'?'Português':'English';
        if(!groups.has(locale)){const group=document.createElement('optgroup');group.label=locale;groups.set(locale,group);switcher.append(group)}
        const option=document.createElement('option');
        option.value=String(page.id);
        option.textContent=(page.isHome?'⌂ ':'')+(page.navTitle||page.title)+(page.publishedRevision===null||Number(page.draftRevision)!==Number(page.publishedRevision)?' • rascunho':'');
        option.selected=Number(page.id)===pageId;
        groups.get(locale).append(option);
      }
      switcher.disabled=false;
      switcher.addEventListener('change',()=>{
        const id=Number(switcher.value);
        if(!id||id===pageId)return;
        if(!leaveTo('/editor/?page='+id))switcher.value=String(pageId);
      });
    }
    const activityId=Number(activity.id)||0;
    if(submissions)submissions.href='/admin/submissions.php?activity='+activityId;
    if(newCount){const count=Number(data.newResponses)||0;newCount.hidden=count===0;newCount.textContent=String(count);submissions?.setAttribute('aria-label',count?`Inscrições, ${count} novas`:'Inscrições')}
    for(const [selector,path] of Object.entries(activityLinks)){const link=$(selector);if(link)link.href=path+'?activity='+activityId}
    if(publicLink)publicLink.href=activity.publicUrl||'/';
    if(mediaMaintenance)mediaMaintenance.href='/admin/media.php?activity='+activityId;
  }catch(error){
    if(siteName)siteName.textContent='Site';
    console.error('editor-context',error);
  }
}

const mediaSearch=$('#media-library-search');
function filterMedia(){
  const query=(mediaSearch?.value||'').trim().toLocaleLowerCase('pt-BR');
  document.querySelectorAll('#media-grid .media-item').forEach(item=>{
    item.hidden=!!query&&!item.textContent.toLocaleLowerCase('pt-BR').includes(query);
  });
}
mediaSearch?.addEventListener('input',filterMedia);
const mediaGrid=$('#media-grid');
if(mediaGrid){new MutationObserver(()=>{if(mediaSearch?.value)filterMedia()}).observe(mediaGrid,{childList:true});}
$('#media-dialog')?.addEventListener('close',()=>{if(mediaSearch){mediaSearch.value='';filterMedia()}});

document.addEventListener('keydown',event=>{
  if((event.ctrlKey||event.metaKey)&&event.key.toLowerCase()==='s'){
    event.preventDefault();$('#save-page')?.click();
  }
  if(event.key==='Escape')$('#editor-more')?.removeAttribute('open');
});

window.addEventListener('beforeunload',event=>{
  if(!dirty())return;
  event.preventDefault();
  event.returnValue='';
});

document.querySelectorAll('.editor-more-menu a').forEach(link=>{
  if(link.target==='_blank')return;
  link.addEventListener('click',event=>{if(!dirty())return;event.preventDefault();leaveTo(link.href)});
});

loadContext();
})();
