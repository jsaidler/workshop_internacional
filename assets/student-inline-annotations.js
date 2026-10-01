(()=>{
'use strict';
const root=document.querySelector('[data-cms-page-main]')||document.querySelector('[data-student-annotations-root]');
const panel=document.querySelector('[data-student-notes-panel]');
if(!root||!panel)return;

const compose=panel.querySelector('[data-inline-note-compose]');
const dataNode=panel.querySelector('[data-student-annotation-data]');
let annotations=[];
try{annotations=JSON.parse(dataNode?.textContent||'[]');}catch(_){annotations=[];}

const entry=document.querySelector('.student-notes-entry a[aria-controls="anotacoes"]');
entry?.addEventListener('click',event=>{event.preventDefault();panel.open=true;panel.querySelector('.student-notes-sheet')?.scrollTo({top:0,behavior:'smooth'});});

const action=document.createElement('button');
action.type='button';action.className='student-selection-note-action';action.hidden=true;action.textContent='Anotar seleção';document.body.appendChild(action);
const reanchorHint=document.createElement('div');
reanchorHint.className='student-reanchor-hint';reanchorHint.hidden=true;reanchorHint.innerHTML='<span>Selecione o novo trecho no material.</span><button type="button">Cancelar</button>';document.body.appendChild(reanchorHint);

let pendingAnchor=null;let reanchorId=0;let selectionTimer=0;let lastValidSelectionAt=0;let selectionPoll=0;
const coarsePointer=window.matchMedia?.('(pointer: coarse)').matches||window.matchMedia?.('(max-width: 760px)').matches;
const textNodes=block=>{const out=[];const walker=document.createTreeWalker(block,NodeFilter.SHOW_TEXT,{acceptNode(node){return node.nodeValue?NodeFilter.FILTER_ACCEPT:NodeFilter.FILTER_REJECT;}});while(walker.nextNode())out.push(walker.currentNode);return out;};
const closestBlock=node=>{const element=node?.nodeType===Node.ELEMENT_NODE?node:node?.parentElement;return element?.closest?.('[data-student-anchor-block]')||null;};
const offsetWithin=(block,node,offset)=>{const range=document.createRange();range.selectNodeContents(block);try{range.setEnd(node,offset);return range.toString().length;}catch(_){return -1;}};
const fingerprint=text=>{let hash=2166136261;for(let i=0;i<text.length;i++){hash^=text.charCodeAt(i);hash=Math.imul(hash,16777619);}return (hash>>>0).toString(16).padStart(8,'0');};
const nodeInsideRoot=node=>{if(!node)return false;const target=node.nodeType===Node.ELEMENT_NODE?node:node.parentNode;return !!target&&root.contains(target);};

const captureSelection=()=>{
  const selection=window.getSelection();if(!selection||selection.rangeCount<1||selection.isCollapsed)return null;
  const range=selection.getRangeAt(0);const startBlock=closestBlock(range.startContainer),endBlock=closestBlock(range.endContainer);
  if(!startBlock||startBlock!==endBlock||!root.contains(startBlock))return null;
  const start=offsetWithin(startBlock,range.startContainer,range.startOffset),end=offsetWithin(startBlock,range.endContainer,range.endOffset);
  if(start<0||end<=start)return null;
  const text=startBlock.textContent||'';const exact=text.slice(start,end);
  if(!exact.trim()||exact.length>3000)return null;
  const section=startBlock.closest('[data-student-note-context]')?.getAttribute('data-student-note-context')||'';
  if(!section)return null;
  return {sectionKey:section,blockKey:startBlock.getAttribute('data-student-anchor-block')||'',start,end,exact,prefix:text.slice(Math.max(0,start-120),start),suffix:text.slice(end,end+120),sourceBlockHash:fingerprint(text)};
};

const setSelectionActive=active=>document.documentElement.classList.toggle('student-selection-active',!!active);
const selectionRect=()=>{
  const selection=window.getSelection();if(!selection||selection.rangeCount<1)return null;
  const range=selection.getRangeAt(0);const rects=[...range.getClientRects()].filter(rect=>rect.width||rect.height);
  return rects.length?rects[rects.length-1]:range.getBoundingClientRect();
};
const positionAction=anchor=>{
  if(!anchor){if(Date.now()-lastValidSelectionAt>1800){action.hidden=true;setSelectionActive(false);}return;}
  action.textContent=reanchorId?'Reassociar seleção':'Anotar seleção';action.hidden=false;setSelectionActive(true);
  if(coarsePointer){action.classList.add('is-touch-selection');action.style.left='';action.style.top='';return;}
  action.classList.remove('is-touch-selection');
  const rect=selectionRect();if(!rect||(!rect.width&&!rect.height))return;
  const width=action.offsetWidth||104,height=action.offsetHeight||42;let left=rect.left+(rect.width/2)-(width/2);left=Math.max(8,Math.min(window.innerWidth-width-8,left));
  let top=rect.top-height-8;if(top<8)top=rect.bottom+8;top=Math.max(8,Math.min(window.innerHeight-height-8,top));
  action.style.left=`${Math.round(left)}px`;action.style.top=`${Math.round(top)}px`;
};
const refreshSelectionAction=()=>{
  if(panel.open&&!reanchorId)return;
  const anchor=captureSelection();
  if(anchor){pendingAnchor=anchor;lastValidSelectionAt=Date.now();positionAction(anchor);return;}
  positionAction(null);
};
const scheduleSelectionRefresh=(delay=0)=>{window.clearTimeout(selectionTimer);selectionTimer=window.setTimeout(()=>window.requestAnimationFrame(refreshSelectionAction),delay);};
const scheduleTouchRefreshes=()=>{scheduleSelectionRefresh(40);window.setTimeout(refreshSelectionAction,180);window.setTimeout(refreshSelectionAction,480);window.setTimeout(refreshSelectionAction,900);};

root.addEventListener('mouseup',()=>scheduleSelectionRefresh());
root.addEventListener('keyup',()=>scheduleSelectionRefresh());
root.addEventListener('pointerup',event=>{if(event.pointerType==='touch'||event.pointerType==='pen')scheduleTouchRefreshes();else scheduleSelectionRefresh();});
root.addEventListener('touchend',scheduleTouchRefreshes,{passive:true});
root.addEventListener('contextmenu',()=>{scheduleSelectionRefresh(30);window.setTimeout(refreshSelectionAction,300);});
document.addEventListener('selectionchange',()=>{
  const selection=window.getSelection();
  if(selection&&(nodeInsideRoot(selection.anchorNode)||nodeInsideRoot(selection.focusNode)))scheduleSelectionRefresh(40);
  else if(!selection||selection.isCollapsed)positionAction(null);
});
window.addEventListener('scroll',()=>{if(!action.hidden)scheduleSelectionRefresh(20);},{passive:true});
window.visualViewport?.addEventListener('resize',()=>{if(!action.hidden)scheduleSelectionRefresh(20);},{passive:true});
document.addEventListener('visibilitychange',()=>{if(!document.hidden)scheduleSelectionRefresh(80);});
selectionPoll=window.setInterval(()=>{if(!document.hidden&&!panel.open)refreshSelectionAction();},250);
window.addEventListener('pagehide',()=>window.clearInterval(selectionPoll),{once:true});

const fillCompose=(anchor,annotationId=0)=>{
  if(!compose||!anchor)return;
  const set=(selector,value)=>{const input=compose.querySelector(selector);if(input)input.value=String(value??'');};
  set('[data-anchor-section]',anchor.sectionKey);set('[data-anchor-block]',anchor.blockKey);set('[data-anchor-start]',anchor.start);set('[data-anchor-end]',anchor.end);set('[data-anchor-exact]',anchor.exact);set('[data-anchor-prefix]',anchor.prefix);set('[data-anchor-suffix]',anchor.suffix);set('[data-anchor-hash]',anchor.sourceBlockHash);set('[data-annotation-id]',annotationId||'');
  const isRelink=annotationId>0;set('[data-annotation-action]',isRelink?'reanchor':'create_selection');
  const preview=compose.querySelector('[data-anchor-preview]');if(preview)preview.textContent=anchor.exact;
  const bodyWrap=compose.querySelector('[data-inline-note-body]'),body=bodyWrap?.querySelector('textarea');if(bodyWrap)bodyWrap.hidden=isRelink;if(body)body.disabled=isRelink;
  const submit=compose.querySelector('[data-inline-note-submit]');if(submit)submit.textContent=isRelink?'Confirmar reassociação':'Adicionar anotação';
  compose.hidden=false;panel.open=true;compose.scrollIntoView({block:'nearest',behavior:'smooth'});if(!isRelink)window.setTimeout(()=>body?.focus(),80);
};
action.addEventListener('pointerdown',event=>event.stopPropagation());
action.addEventListener('click',()=>{if(!pendingAnchor)return;const anchor=pendingAnchor;fillCompose(anchor,reanchorId);action.hidden=true;setSelectionActive(false);reanchorHint.hidden=true;window.getSelection()?.removeAllRanges();});
compose?.querySelector('[data-inline-note-cancel]')?.addEventListener('click',()=>{compose.hidden=true;reanchorId=0;reanchorHint.hidden=true;const body=compose.querySelector('textarea');if(body){body.disabled=false;body.value='';}});

panel.querySelectorAll('[data-annotation-reanchor]').forEach(button=>button.addEventListener('click',()=>{
  reanchorId=Number(button.getAttribute('data-annotation-reanchor')||0);if(!reanchorId)return;
  compose.hidden=true;panel.open=false;reanchorHint.hidden=false;action.hidden=true;setSelectionActive(false);pendingAnchor=null;lastValidSelectionAt=0;window.getSelection()?.removeAllRanges();
}));
reanchorHint.querySelector('button')?.addEventListener('click',()=>{reanchorId=0;reanchorHint.hidden=true;action.hidden=true;setSelectionActive(false);pendingAnchor=null;lastValidSelectionAt=0;window.getSelection()?.removeAllRanges();});

const findOccurrences=(text,needle)=>{const out=[];if(!needle)return out;let from=0;while(from<=text.length){const at=text.indexOf(needle,from);if(at<0)break;out.push(at);from=at+Math.max(1,needle.length);}return out;};
const scoreCandidate=(text,start,note)=>{let score=0;const prefix=note.prefix||'',suffix=note.suffix||'',end=start+(note.exact||'').length;if(prefix){const before=text.slice(Math.max(0,start-prefix.length),start);if(before===prefix)score+=4;else if(before.endsWith(prefix.slice(-Math.min(40,prefix.length))))score+=2;}if(suffix){const after=text.slice(end,end+suffix.length);if(after===suffix)score+=4;else if(after.startsWith(suffix.slice(0,Math.min(40,suffix.length))))score+=2;}return score;};
const candidatesInBlock=(block,note)=>{const text=block.textContent||'',exact=note.exact||'';if(!exact)return [];return findOccurrences(text,exact).map(start=>({block,start,end:start+exact.length,score:scoreCandidate(text,start,note)}));};
const uniqueBest=candidates=>{if(!candidates.length)return null;const sorted=[...candidates].sort((a,b)=>b.score-a.score);if(sorted.length>1&&sorted[0].score===sorted[1].score)return {ambiguous:true};return sorted[0];};
const locateAnnotation=note=>{
  if(note.anchorType!=='selection'||!note.exact)return null;
  const preferred=root.querySelector(`[data-student-anchor-block="${CSS.escape(note.blockKey||'')}"]`);
  if(preferred){const text=preferred.textContent||'',start=Number(note.start),end=Number(note.end);if(Number.isInteger(start)&&Number.isInteger(end)&&start>=0&&end<=text.length&&text.slice(start,end)===note.exact)return {block:preferred,start,end,state:'linked'};const local=uniqueBest(candidatesInBlock(preferred,note));if(local?.ambiguous)return {state:'ambiguous',block:preferred};if(local)return {...local,state:'linked'};}
  const all=[];root.querySelectorAll('[data-student-anchor-block]').forEach(block=>{candidatesInBlock(block,note).forEach(candidate=>{candidate.score+=(block.closest('[data-student-note-context]')?.getAttribute('data-student-note-context')===note.sectionKey?2:0);all.push(candidate);});});
  const best=uniqueBest(all);if(best?.ambiguous)return {state:'ambiguous',block:preferred||null};if(best)return {...best,state:'moved'};
  return {state:preferred?'altered':'removed',block:preferred||null};
};
const rangeFromOffsets=(block,start,end)=>{const nodes=textNodes(block);let cursor=0,startNode=null,startOffset=0,endNode=null,endOffset=0;for(const node of nodes){const len=node.nodeValue?.length||0;if(!startNode&&start>=cursor&&start<=cursor+len){startNode=node;startOffset=start-cursor;}if(end>=cursor&&end<=cursor+len){endNode=node;endOffset=end-cursor;break;}cursor+=len;}if(!startNode||!endNode)return null;const range=document.createRange();range.setStart(startNode,startOffset);range.setEnd(endNode,endOffset);return range;};
const setStatus=(id,text,state)=>{const status=panel.querySelector(`[data-annotation-status="${id}"]`);if(status){status.textContent=text;status.dataset.anchorState=state;}};
const resolved=[];
annotations.forEach(note=>{if(note.anchorType!=='selection')return;const location=locateAnnotation(note);if(!location)return;if(location.state==='altered'){setStatus(note.id,'Trecho alterado — a anotação foi preservada.','altered');return;}if(location.state==='removed'){setStatus(note.id,'Trecho original removido — a anotação foi preservada.','removed');return;}if(location.state==='ambiguous'){setStatus(note.id,'Trecho não identificado com segurança — reassocie para restaurar o destaque.','ambiguous');return;}setStatus(note.id,location.state==='moved'?'Trecho localizado após alteração do material.':'Vinculada ao texto.',location.state);resolved.push({note,...location});});
resolved.sort((a,b)=>a.block===b.block?b.start-a.start:0).forEach(item=>{const range=rangeFromOffsets(item.block,item.start,item.end);if(!range||range.collapsed)return;const mark=document.createElement('mark');mark.className='student-inline-note-mark';mark.dataset.annotationId=String(item.note.id);mark.setAttribute('role','button');mark.tabIndex=0;mark.title='Abrir anotação';try{const fragment=range.extractContents();mark.appendChild(fragment);range.insertNode(mark);}catch(_){return;}const open=()=>{panel.open=true;action.hidden=true;setSelectionActive(false);const article=panel.querySelector(`[data-annotation-item="${item.note.id}"]`);article?.scrollIntoView({block:'center',behavior:'smooth'});article?.classList.add('is-target');setTimeout(()=>article?.classList.remove('is-target'),1200);};mark.addEventListener('click',open);mark.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();open();}});});
})();
