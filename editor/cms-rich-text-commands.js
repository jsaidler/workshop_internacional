(()=>{
'use strict';
// Split inline markup only at selection boundaries: unselected characters,
// hyperlinks and other inline formatting retain their original semantics.
const selectors={bold:'strong,b',italic:'em,i',removeFormat:'strong,b,em,i,span,font,u'};
const splittable=new Set(['A','STRONG','B','EM','I','SPAN','FONT','U','S','DEL','MARK','SMALL','SUP','SUB','CODE']);
function editableFor(range,doc){
  if(!range||range.collapsed||range.startContainer.ownerDocument!==doc)return null;
  const ancestor=range.commonAncestorContainer;
  const node=ancestor.nodeType===1?ancestor:ancestor.parentElement;
  const edit=node?.closest?.('[data-cms-editable][contenteditable="true"]');
  return edit&&doc.contains(edit)&&edit.contains(range.startContainer)&&edit.contains(range.endContainer)?edit:null;
}
function unwrap(node){node.replaceWith(...node.childNodes)}
function styleCoverage(range,editable,selector){
  const walker=editable.ownerDocument.createTreeWalker(editable,NodeFilter.SHOW_TEXT);
  let node,found=false,allStyled=true,hasStyle=false;
  while((node=walker.nextNode())){
    if(!range.intersectsNode(node)||!node.textContent.trim())continue;
    const from=node===range.startContainer?range.startOffset:0;
    const to=node===range.endContainer?range.endOffset:node.length;
    if(!node.textContent.slice(from,to).trim())continue;
    found=true;
    const ancestor=node.parentElement?.closest(selector);
    const styled=Boolean(ancestor&&editable.contains(ancestor));
    allStyled&&=styled;
    hasStyle||=styled;
  }
  return {found,allStyled,hasStyle};
}
function validBoundaries(range,editable){
  for(const boundary of [range.startContainer,range.endContainer]){
    let node=boundary.nodeType===1?boundary:boundary.parentElement;
    while(node&&node!==editable){
      if(!splittable.has(node.tagName))return false;
      node=node.parentElement;
    }
    if(node!==editable)return false;
  }
  // Avoid wrapping block content inside inline elements or moving CMS components.
  return !range.cloneContents().querySelector('div,p,section,article,header,footer,figure,table,ul,ol,li,h1,h2,h3,h4,h5,h6,blockquote,form,iframe,video,button,[data-cms-editor-ui]');
}
// Formatting must not turn one hyperlink into multiple tab stops.
function anchorForBoundary(node,editable){
  const el=node.nodeType===1?node:node.parentElement;
  const anchor=el?.closest?.('a[href]');
  return anchor&&editable.contains(anchor)?anchor:null;
}
function linkFormattingScope(range,editable){
  const a=anchorForBoundary(range.startContainer,editable);
  const b=anchorForBoundary(range.endContainer,editable);
  if(!a&&!b)return editable;
  if(!a||a!==b)return null; // Do not split a link at only one selection boundary.
  // We may move an external formatting wrapper inside one anchor only when
  // every ancestor wraps exactly the link path; siblings must remain untouched.
  let child=a,parent=a.parentElement;
  while(parent&&parent!==editable){
    if(!['STRONG','B','EM','I','SPAN','FONT','U','S','DEL','MARK','SMALL','SUP','SUB','CODE'].includes(parent.tagName)
      ||parent.childNodes.length!==1||parent.firstChild!==child)return null;
    child=parent;parent=parent.parentElement;
  }
  return parent===editable?a:null;
}
function normalizeSingleLink(anchor,editable){
  while(anchor.parentNode!==editable){
    const wrapper=anchor.parentNode;
    wrapper.replaceWith(anchor);
    wrapper.append(...anchor.childNodes);
    anchor.append(wrapper);
  }
}
function lift(marker,editable,touched){
  while(marker.parentNode!==editable){
    const parent=marker.parentNode;
    const trailing=parent.cloneNode(false);
    touched.add(parent);touched.add(trailing);
    // Keep unique CMS node identifiers on the first surviving fragment.
    while(marker.nextSibling)trailing.appendChild(marker.nextSibling);
    const hasPrefix=[...parent.childNodes].some(child=>child!==marker&&(child.nodeType!==3||child.textContent.length>0));
    if(hasPrefix){
      trailing.removeAttribute('id');
      trailing.removeAttribute('data-cms-node-id');
    }
    parent.after(marker);
    if(trailing.hasChildNodes())marker.after(trailing);
    if(!hasPrefix)parent.remove();
  }
}
function strip(container,selector){
  [...container.querySelectorAll(selector)].reverse().forEach(node=>{
    // Editor/DOM identifiers can be attached to inline formatting nodes.
    // Keep those attributes by converting semantic formatting to a neutral span.
    const identity=[...node.attributes].some(a=>a.name==='id'||a.name==='name'||a.name.startsWith('data-'));
    if(!identity){unwrap(node);return}
    if(node.tagName==='SPAN')return; // Never discard a component's attributed span.
    const neutral=node.ownerDocument.createElement('span');
    for(const attr of [...node.attributes])neutral.setAttribute(attr.name,attr.value);
    neutral.append(...node.childNodes);
    node.replaceWith(neutral);
  });
}
function removeEmptyFormatting(editable,touched){
  const candidates=new Set();
  for(const item of touched){
    if(!editable.contains(item))continue;
    candidates.add(item);
    item.querySelectorAll('strong,b,em,i,span,font,u,a').forEach(child=>candidates.add(child));
  }
  [...candidates].reverse().forEach(node=>{
    if(!editable.contains(node)||!node.matches('strong,b,em,i,span,font,u,a'))return;
    if(node.textContent!==''||node.querySelector('img,svg,video,audio,iframe,br,input'))return;
    if(node.hasAttribute('id')||node.hasAttribute('name')||[...node.attributes].some(a=>a.name.startsWith('data-')))return;
    node.remove();
  });
}
function apply(command,doc,capturedRange){
  if(!Object.hasOwn(selectors,command)||!doc)return false;
  const selection=doc.defaultView?.getSelection();
  const range=capturedRange?.cloneRange()||(!selection?.isCollapsed?selection?.getRangeAt(0).cloneRange():null);
  const editable=editableFor(range,doc);
  if(!editable||!range.toString().trim()||!validBoundaries(range,editable))return false;
  const scope=linkFormattingScope(range,editable);
  if(!scope)return false;
  const selector=selectors[command];
  const coverage=styleCoverage(range,editable,selector);
  if(!coverage.found||command==='removeFormat'&&!coverage.hasStyle)return false;
  const remove=command==='removeFormat'||coverage.allStyled;
  // Insert the end marker first so offsets in one text node are not shifted.
  const end=doc.createElement('span'),start=doc.createElement('span');
  const atEnd=range.cloneRange();atEnd.collapse(false);atEnd.insertNode(end);
  const atStart=range.cloneRange();atStart.collapse(true);atStart.insertNode(start);
  if(scope!==editable)normalizeSingleLink(scope,editable);
  const touched=new Set();
  lift(start,scope,touched);
  lift(end,scope,touched);
  const isolated=doc.createRange();isolated.setStartAfter(start);isolated.setEndBefore(end);
  const content=isolated.extractContents();
  strip(content,selector);
  let first,last;
  if(remove){
    const nodes=[...content.childNodes];
    first=nodes[0];last=nodes.at(-1);
    end.before(content);
  }else{
    const wrapper=doc.createElement(command==='bold'?'strong':'em');
    wrapper.append(content);end.before(wrapper);
    first=last=wrapper;
  }
  start.remove();end.remove();
  removeEmptyFormatting(editable,touched);
  if(!first||!last)return false;
  const restored=doc.createRange();restored.setStartBefore(first);restored.setEndAfter(last);
  selection?.removeAllRanges();selection?.addRange(restored);
  return true;
}
window.CmsRichTextCommands={apply};
})();
