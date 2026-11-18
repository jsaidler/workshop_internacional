/* Caderno record contexts. Native hash links own history. The script only
 * presents the selected peer section; it does not prescribe a sequence or
 * create a second data model. If JS fails, every section remains usable. */
(()=>{
 'use strict';
 const nav=document.querySelector('[data-record-workspace-nav]');
 if(!nav)return;
 const main=nav.closest('.student-main');
 const links=[...nav.querySelectorAll('a[href^="#"][data-record-workspace-link]')];
 const sections=new Map(links.map(link=>{
   const id=link.getAttribute('href').slice(1);
   return [id,document.getElementById(id)];
 }).filter(([,section])=>section?.matches('[data-record-workspace-panel]')));
 if(sections.size!==3||!main)return;
 const initial=document.querySelector('[data-record-workspace-default]')?.dataset.recordWorkspaceDefault;
 const fallback=sections.has(initial)?initial:'exposicao';
 const identify=()=>{
   let raw=window.location.hash.slice(1);
   try{raw=decodeURIComponent(raw);}catch{return {id:fallback,anchor:null};}
   const anchor=raw?document.getElementById(raw):null;
   const panel=anchor?.closest('[data-record-workspace-panel]');
   return {id:panel&&sections.has(panel.id)?panel.id:fallback,anchor};
 };
 const revealAncestors=anchor=>{
   if(!anchor)return;
   if(anchor.matches('details'))anchor.open=true;
   let parent=anchor.parentElement;
   while(parent&&parent!==main){
     if(parent.matches('details'))parent.open=true;
     parent=parent.parentElement;
   }
 };
 const sync=(scroll=false)=>{
   const {id,anchor}=identify();
   sections.forEach((section,key)=>{
     section.hidden=key!==id;
     section.dataset.recordWorkspaceActive=key===id?'true':'false';
   });
   links.forEach(link=>{
     if(link.getAttribute('href')==='#'+id)link.setAttribute('aria-current','location');
     else link.removeAttribute('aria-current');
   });
   if(anchor&&anchor.closest('[data-record-workspace-panel]'))revealAncestors(anchor);
   document.body.classList.add('student-record-workspace-ready');
   if(scroll&&anchor){
     window.requestAnimationFrame(()=>{
       if(anchor.isConnected&&!anchor.closest('[hidden]')){
         anchor.scrollIntoView({block:'start',behavior:'instant'});
       }
     });
   }
 };
 links.forEach(link=>link.addEventListener('click',()=>{
   // Re-selecting a fragment already in the URL does not fire hashchange.
   if(window.location.hash===link.hash)sync(true);
 }));
 window.addEventListener('hashchange',()=>sync(true));
 window.addEventListener('pageshow',()=>sync(false));
 sync(Boolean(window.location.hash));
})();
