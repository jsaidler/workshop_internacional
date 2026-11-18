(()=>{
'use strict';
const frame=document.querySelector('#page-frame');if(!frame)return;
const map={
  'data-min-height':'data-cms-min-height',
  'data-border-top':'data-cms-border-top',
  'data-border-bottom':'data-cms-border-bottom',
  'data-hidden-mobile':'data-cms-hidden-mobile',
  'data-hidden-desktop':'data-cms-hidden-desktop'
};
function normalize(root){if(!root)return;const nodes=[root,...root.querySelectorAll?.('[data-min-height],[data-border-top],[data-border-bottom],[data-hidden-mobile],[data-hidden-desktop]')||[]];for(const node of nodes){if(!(node instanceof Element))continue;for(const [oldName,newName] of Object.entries(map)){if(!node.hasAttribute(oldName))continue;node.setAttribute(newName,node.getAttribute(oldName)||'1');node.removeAttribute(oldName)}}}
function attach(){const d=frame.contentDocument;if(!d)return;normalize(d.documentElement);const observer=new MutationObserver(records=>{for(const record of records){if(record.type==='attributes')normalize(record.target);for(const node of record.addedNodes)if(node instanceof Element)normalize(node)}});observer.observe(d.documentElement,{subtree:true,childList:true,attributes:true,attributeFilter:Object.keys(map)});}
frame.addEventListener('load',attach);if(frame.contentDocument?.readyState==='complete')attach();
})();
