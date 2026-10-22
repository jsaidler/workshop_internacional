(()=>{
'use strict';
// Formatting lives in the edited iframe. The browser's deprecated execCommand
// cannot be used as a reliable source of editor history or selection state.
function editableFor(range){
  if(!range||range.collapsed)return null;
  const anchor=range.commonAncestorContainer.nodeType===1?range.commonAncestorContainer:range.commonAncestorContainer.parentElement;
  const edit=anchor?.closest?.('[data-cms-editable][contenteditable="true"]');
  return edit&&edit.contains(range.startContainer)&&edit.contains(range.endContainer)?edit:null;
}
function unwrap(node){node.replaceWith(...node.childNodes)}
function apply(command,doc,capturedRange){
  if(!['bold','italic','removeFormat'].includes(command)||!doc)return false;
  const selection=doc.defaultView?.getSelection();
  const range=capturedRange?.cloneRange()||(!selection?.isCollapsed?selection?.getRangeAt(0).cloneRange():null);
  if(!editableFor(range)||!range.toString().trim())return false;
  const format=command==='bold'?'strong':'em';
  const ancestor=range.commonAncestorContainer.nodeType===1?range.commonAncestorContainer:range.commonAncestorContainer.parentElement;
  const styles=command==='removeFormat'?'strong,b,em,i,span,font,u':command==='bold'?'strong,b':'em,i';
  const present=ancestor?.closest?.(styles);
  if(present&&present.closest('[data-cms-editable]')){
    // A whole formatted span can be toggled off without reparenting links.
    // Partial spans are not flattened because that would silently strip
    // formatting from unselected characters.
    if(range.toString()!==present.textContent)return false;
    if(command==='removeFormat')present.querySelectorAll('strong,b,em,i,span,font,u').forEach(unwrap);
    const nodes=[...present.childNodes],first=nodes[0],last=nodes.at(-1);
    if(!nodes.length)return false;
    unwrap(present);
    range.setStartBefore(first);range.setEndAfter(last);
    selection?.removeAllRanges();selection?.addRange(range);
    return true;
  }
  const selected=range.extractContents();
  let first=null,last=null;
  if(command==='removeFormat'){
    [...selected.querySelectorAll('strong,b,em,i,span,font,u')].reverse().forEach(unwrap);
    const nodes=[...selected.childNodes];
    if(!nodes.length)return false;
    first=nodes[0];last=nodes.at(-1);
    range.insertNode(selected);
  }else{
    const wrapper=doc.createElement(format);
    wrapper.append(selected);
    // Avoid redundant nested formatting from previously styled selections.
    wrapper.querySelectorAll(format).forEach(unwrap);
    range.insertNode(wrapper);
    first=wrapper;last=wrapper;
  }
  range.setStartBefore(first);range.setEndAfter(last);
  selection?.removeAllRanges();selection?.addRange(range);
  return true;
}
window.CmsRichTextCommands={apply};
})();
