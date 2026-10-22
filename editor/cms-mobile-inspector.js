(()=>{
'use strict';
const inspector=document.querySelector('#inspector');
if(!inspector)return;
const mobile=window.matchMedia('(max-width: 1024px)');
let collapsed=false;
let lastSection=null;
let updating=false;
function content(){return [...inspector.children].filter(node=>!node.classList.contains('cms-mobile-inspector-header'))}
function control(){return inspector.querySelector(':scope > .cms-mobile-inspector-header')}
function update(){
  if(updating)return;
  updating=true;
  try{
    const section=inspector.querySelector(':scope > .inspector-section');
    if(!section||!mobile.matches){
      inspector.classList.remove('cms-mobile-collapsed');
      content().forEach(node=>{node.inert=false});
      control()?.remove();
      collapsed=false;
      lastSection=section||null;
      return;
    }
    if(lastSection&&lastSection!==section)collapsed=false;
    lastSection=section;
    let bar=control();
    if(!bar){
      bar=document.createElement('div');
      bar.className='cms-mobile-inspector-header';
      bar.innerHTML='<span class="cms-mobile-inspector-name" id="cms-mobile-inspector-title">Propriedades</span><button type="button" class="cms-mobile-inspector-toggle" aria-expanded="true">Recolher</button>';
      inspector.prepend(bar);
      bar.querySelector('button').addEventListener('click',()=>{
        collapsed=!collapsed;
        update();
        bar.querySelector('button')?.focus();
      });
    }
    const name=section.querySelector('header h2')?.textContent?.trim()||'Propriedades';
    bar.querySelector('.cms-mobile-inspector-name').textContent=name;
    const button=bar.querySelector('button');
    button.textContent=collapsed?'Expandir':'Recolher';
    button.setAttribute('aria-expanded',collapsed?'false':'true');
    button.setAttribute('aria-label',(collapsed?'Expandir':'Recolher')+' propriedades: '+name);
    inspector.classList.toggle('cms-mobile-collapsed',collapsed);
    content().forEach(node=>{node.inert=collapsed});
  }finally{updating=false}
}
// Observe only direct replacements of the inspector; other modules may inject
// property controls deeper in the section and must not reset the drawer.
new MutationObserver(records=>{
  if(records.some(record=>record.target===inspector&&
    [...record.addedNodes,...record.removedNodes].some(node=>node.nodeType===1&&!node.classList.contains('cms-mobile-inspector-header'))))update();
}).observe(inspector,{childList:true});
mobile.addEventListener?.('change',update);
function onEscape(event){
  if(event.key!=='Escape'||!mobile.matches||collapsed||!control())return;
  if(document.querySelector('dialog[open]')||document.body.classList.contains('structure-mobile-open'))return;
  collapsed=true;update();
  control()?.querySelector('button')?.focus();
}
document.addEventListener('keydown',onEscape);
// Keyboard events inside the page iframe never bubble to the parent document.
const frame=document.querySelector('#page-frame');
frame?.addEventListener('load',()=>frame.contentDocument?.addEventListener('keydown',onEscape));
if(frame?.contentDocument)frame.contentDocument.addEventListener('keydown',onEscape);
update();
})();
