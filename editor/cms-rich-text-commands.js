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
/**
 * Toggle a partial selection off inside one formatted text run. Split the run
 * into before / selection / after, cloning its non-editable structure. Nested
 * links are preserved with their original href instead of being flattened.
 * Mixed-node selections still use the conservative path below.
 */
function splitFormattedRun(formatted,range,doc,selection){
  const text=range.startContainer;
  if(text!==range.endContainer||text?.nodeType!==3||!formatted.contains(text))return false;
  const from=range.startOffset,to=range.endOffset;
  if(from<=0&&to>=text.length)return false;
  const inner=[];
  let current=text;
  while(current.parentNode&&current.parentNode!==formatted){
    const parent=current.parentNode;
    if(parent.nodeType!==1||parent.childNodes.length!==1||parent.firstChild!==current)return false;
    // Only clone ordinary inline wrappers; editing links is safe and retains href.
    if(!['A','EM','I','STRONG','B','SPAN','U','FONT'].includes(parent.tagName))return false;
    inner.unshift(parent);
    current=parent;
  }
  if(current.parentNode!==formatted||formatted.childNodes.length!==1)return false;
  const value=text.data,selected=value.slice(from,to);
  if(!selected)return false;
  function piece(value,includeFormat){
    let leaf=doc.createTextNode(value),node=leaf;
    for(let i=inner.length-1;i>=0;i--){
      const wrapper=inner[i].cloneNode(false);
      wrapper.append(node);node=wrapper;
    }
    if(includeFormat){
      const wrapper=formatted.cloneNode(false);
      wrapper.append(node);node=wrapper;
    }
    return {root:node,text:leaf};
  }
  const left=value.slice(0,from),right=value.slice(to);
  const fragment=doc.createDocumentFragment();
  if(left)fragment.append(piece(left,true).root);
  const middle=piece(selected,false);
  fragment.append(middle.root);
  if(right)fragment.append(piece(right,true).root);
  formatted.replaceWith(fragment);
  const newRange=doc.createRange();
  newRange.selectNodeContents(middle.text);
  selection?.removeAllRanges();
  selection?.addRange(newRange);
  return true;
}
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
    if(range.toString()!==present.textContent)return splitFormattedRun(present,range,doc,selection);
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
