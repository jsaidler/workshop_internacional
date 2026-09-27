(()=>{
'use strict';
const inspector=document.querySelector('#inspector');
if(!inspector)return;
let scheduled=false;
const makeGroup=(kind,eyebrow,title)=>{const section=document.createElement('section');section.className=`cms-inspector-group cms-inspector-${kind}`;section.dataset.cmsInspectorGroup=kind;section.innerHTML=`<header class="cms-inspector-group-header"><p class="eyebrow">${eyebrow}</p><h3>${title}</h3></header>`;return section};
function moveLabels(group,panel,ids){for(const id of ids){const control=panel.querySelector('#'+id);const label=control?.closest('label');if(label&&label.parentElement!==group)group.append(label)}}
function sectionPanel(){return [...inspector.querySelectorAll('.inspector-section')].find(panel=>panel.querySelector('#s-name,#section-name'))||null}
function cohereSection(){
 const panel=sectionPanel();if(!panel)return;
 panel.classList.add('cms-section-inspector-coherent');
 const header=[...panel.children].find(child=>child.tagName==='HEADER')||null;
 let identity=panel.querySelector('[data-cms-inspector-group="identity"]');if(!identity){identity=makeGroup('identity','Identidade','Como esta seção aparece no editor');header?.after(identity)}
 moveLabels(identity,panel,['s-name','section-name']);
 let layout=panel.querySelector('[data-cms-inspector-group="layout"]');if(!layout){layout=makeGroup('layout','Layout','Composição da seção');identity.after(layout)}
 moveLabels(layout,panel,['s-width','s-bg','s-space','s-ratio','s-align','s-mobile']);
 const access=inspector.querySelector('[data-cms-access-controls]');if(access&&access.parentElement!==panel)panel.append(access);if(access&&access.previousElementSibling!==layout)layout.after(access);
 const actionRow=[...panel.querySelectorAll('.button-row')].find(row=>row.querySelector('#s-up,#section-up,#s-down,#section-down,#s-copy,#section-copy,#s-delete,#section-delete'))||null;
 let actions=panel.querySelector('[data-cms-inspector-group="actions"]');
 if(actionRow&&!actions){actions=makeGroup('actions','Organização','Ações da seção');actions.append(actionRow);panel.append(actions)}else if(actionRow&&actions&&actionRow.parentElement!==actions)actions.append(actionRow);
 if(access&&actions&&access.nextElementSibling!==actions)access.after(actions);
 panel.querySelectorAll(':scope > hr').forEach(hr=>hr.remove());
}
function coherePage(){
 const panel=[...inspector.querySelectorAll('.inspector-section')].find(node=>node.querySelector('#p-title,#page-title'))||null;if(!panel)return;
 const access=inspector.querySelector('[data-cms-page-access]');if(access&&access.parentElement!==panel)panel.append(access);
}
function apply(){scheduled=false;cohereSection();coherePage()}
function schedule(){if(scheduled)return;scheduled=true;queueMicrotask(apply)}
new MutationObserver(schedule).observe(inspector,{subtree:true,childList:true});
schedule();
})();
