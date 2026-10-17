(()=>{
'use strict';
const root=document.querySelector('[data-cms-page-main]')||document.querySelector('[data-student-annotations-root]');
const panel=document.querySelector('[data-student-notes-panel]');
if(!root||!panel)return;
let compose=panel.querySelector('[data-inline-note-compose]');
let annotations=[];
const readData=()=>{try{return JSON.parse(panel.querySelector('[data-student-annotation-data]')?.textContent||'[]');}catch(_){return[];}};
const normalizeNote=note=>({id:Number(note.id),anchorType:note.anchorType??note.anchor_type??'page',sectionKey:note.sectionKey??note.section_key??'pagina',body:note.body??'',exact:note.exact??note.quoteExact??note.quote_exact??'',prefix:note.prefix??note.quotePrefix??note.quote_prefix??'',suffix:note.suffix??note.quoteSuffix??note.quote_suffix??'',blockKey:note.blockKey??note.block_key??'',start:note.start??note.start_offset??null,end:note.end??note.end_offset??null,sourceBlockHash:note.sourceBlockHash??note.source_block_hash??''});
annotations=readData().map(normalizeNote);
const entry=document.querySelector('.student-notes-entry a[aria-controls="anotacoes"]');
entry?.addEventListener('click',event=>{event.preventDefault();panel.open=true;panel.querySelector('.student-notes-sheet')?.scrollTo({top:0,behavior:'smooth'});});
const action=document.createElement('button');action.type='button';action.className='student-selection-note-action';action.hidden=true;action.textContent='Anotar seleção';document.body.appendChild(action);
const reanchorHint=document.createElement('div');reanchorHint.className='student-reanchor-hint';reanchorHint.hidden=true;reanchorHint.innerHTML='<span>Selecione o novo trecho no material.</span><button type="button">Cancelar</button>';document.body.appendChild(reanchorHint);
let pendingAnchor=null,reanchorId=0,selectionTimer=0,lastValidSelectionAt=0,readingOrigin=null;
const setComposeMode=value=>{panel.classList.toggle('is-composing',!!value);if(!value)panel.classList.remove('is-questioning');};
const syncQuestionMode=details=>{
  if(!details)return;
  const active=!!(details.open&&compose&&!compose.hidden);
  panel.classList.toggle('is-questioning',active);
  const summary=details.querySelector('summary');
  if(summary)summary.textContent=active?'← Voltar à anotação':'Também é uma dúvida?';
  if(active)panel.querySelector('.student-notes-sheet')?.scrollTo({top:0,behavior:'auto'});
};
const coarsePointer=window.matchMedia?.('(pointer: coarse)').matches||window.matchMedia?.('(max-width: 760px)').matches;

const sheet=panel.querySelector('.student-notes-sheet');
const noteBack=panel.querySelector('[data-student-notes-back]');
const overlapChooser=document.createElement('section');
overlapChooser.className='student-note-overlap-chooser';
overlapChooser.hidden=true;
overlapChooser.setAttribute('aria-label','Anotações do trecho destacado');
overlapChooser.innerHTML='<h3>Qual anotação deseja abrir?</h3><p>Este trecho está associado a mais de uma anotação.</p><div data-note-overlap-options></div>';
noteBack?.after(overlapChooser);
const listSavedNotes=()=>{
  const general=panel.querySelector('[data-student-note-new]');if(general)general.open=false;
  panel.classList.remove('is-editing','is-existing-questioning','is-page-questioning','is-general-composing','is-overlap-choosing');
  overlapChooser.hidden=true;
  panel.querySelectorAll('.student-note-item').forEach(article=>{article.classList.remove('is-active');article.querySelectorAll('[data-note-question]').forEach(question=>question.open=false);});
  if(noteBack)noteBack.hidden=true;
  sheet?.scrollTo({top:0,behavior:'auto'});
};
const editSavedNote=article=>{
  if(!article?.matches('.student-note-item'))return;
  compose.hidden=true;setComposeMode(false);
  const general=panel.querySelector('[data-student-note-new]');if(general)general.open=false;
  panel.classList.remove('is-general-composing','is-overlap-choosing');
  overlapChooser.hidden=true;
  panel.querySelectorAll('.student-note-item').forEach(item=>{item.classList.toggle('is-active',item===article);if(item!==article)item.querySelectorAll('[data-note-question]').forEach(q=>q.open=false);});
  panel.classList.add('is-editing');
  panel.classList.remove('is-existing-questioning','is-page-questioning');
  if(noteBack)noteBack.hidden=false;
  panel.open=true;
  sheet?.scrollTo({top:0,behavior:'auto'});
  // The edit trigger is hidden in this state. Move focus to the actual field
  // without scrolling the independent CMS reading region behind the sheet.
  requestAnimationFrame(()=>{
    if(panel.open&&article.classList.contains('is-active'))article.querySelector('textarea[name="body"]')?.focus({preventScroll:true});
  });
};

const textNodes=block=>{const out=[],walker=document.createTreeWalker(block,NodeFilter.SHOW_TEXT,{acceptNode:n=>n.nodeValue?NodeFilter.FILTER_ACCEPT:NodeFilter.FILTER_REJECT});while(walker.nextNode())out.push(walker.currentNode);return out;};
const closestBlock=node=>(node?.nodeType===Node.ELEMENT_NODE?node:node?.parentElement)?.closest?.('[data-student-anchor-block]')||null;
const offsetWithin=(block,node,offset)=>{const range=document.createRange();range.selectNodeContents(block);try{range.setEnd(node,offset);return range.toString().length;}catch(_){return-1;}};
const fingerprint=text=>{let hash=2166136261;for(let i=0;i<text.length;i++){hash^=text.charCodeAt(i);hash=Math.imul(hash,16777619);}return(hash>>>0).toString(16).padStart(8,'0');};
const nodeInsideRoot=node=>{const target=node?.nodeType===Node.ELEMENT_NODE?node:node?.parentNode;return!!target&&root.contains(target);};
const readingBlock=blockKey=>blockKey?root.querySelector(`[data-student-anchor-block="${CSS.escape(blockKey)}"]`):null;
const readingScrollRoot=()=>document.querySelector('.cms-student-reading');

// Native Back/Forward restores the window on desktop, but not necessarily a
// scrollable reading pane on phones. Attach the pane offset to its own history
// entry; replaceState preserves the native stack and any unrelated state.
const readingHistoryKey='__cmsStudentReaderScrollV1';
const internalReader=()=>{
  const reader=readingScrollRoot();
  return reader&&reader.scrollHeight>reader.clientHeight+1&&/(auto|scroll)/.test(getComputedStyle(reader).overflowY)?reader:null;
};
let restoringReader=true,saveReadingFrame=0;
const saveReadingHistory=()=>{
  if(restoringReader)return;
  const reader=internalReader();if(!reader)return;
  const state=history.state;
  if(state!==null&&(typeof state!=='object'||Array.isArray(state)))return;
  const top=Math.round(reader.scrollTop);
  if(state?.[readingHistoryKey]===top)return;
  try{history.replaceState({...state,[readingHistoryKey]:top},'');}catch(_){/* Keep reading usable when History API is restricted. */}
};
const restoreReadingHistory=()=>{
  const reader=internalReader(),saved=history.state?.[readingHistoryKey];
  if(reader&&typeof saved==='number'&&Number.isFinite(saved)&&saved>=0&&Math.abs(reader.scrollTop-saved)>1)
    reader.scrollTop=saved;
};
const scheduleReadingRestore=()=>{
  restoringReader=true;
  requestAnimationFrame(()=>requestAnimationFrame(()=>{
    restoreReadingHistory();
    requestAnimationFrame(()=>{restoringReader=false;});
  }));
};
readingScrollRoot()?.addEventListener('scroll',()=>{
  if(restoringReader||saveReadingFrame)return;
  saveReadingFrame=requestAnimationFrame(()=>{saveReadingFrame=0;saveReadingHistory();});
},{passive:true});
window.addEventListener('pagehide',()=>{
  if(saveReadingFrame){cancelAnimationFrame(saveReadingFrame);saveReadingFrame=0;}
  saveReadingHistory();
});
window.addEventListener('pageshow',scheduleReadingRestore);
scheduleReadingRestore();

const currentReadingScroll=()=>readingScrollRoot()?.scrollTop??window.scrollY;
const adjustReadingScroll=delta=>{const root=readingScrollRoot();if(root)root.scrollTop+=delta;else window.scrollBy(0,delta);};
const restoreReadingScroll=value=>{const root=readingScrollRoot();if(root)root.scrollTop=value;else window.scrollTo(0,value);};
const captureReadingOrigin=blockKey=>{const block=readingBlock(blockKey);return{blockKey:blockKey||'',top:block?.getBoundingClientRect().top??null,y:currentReadingScroll()};};
const restoreReadingOrigin=origin=>requestAnimationFrame(()=>requestAnimationFrame(()=>{if(!origin)return;const block=readingBlock(origin.blockKey);if(block&&Number.isFinite(origin.top)){const delta=block.getBoundingClientRect().top-origin.top;if(Math.abs(delta)>.5)adjustReadingScroll(delta);}else restoreReadingScroll(origin.y||0);}));
const captureSelection=()=>{const selection=window.getSelection();if(!selection||selection.rangeCount<1||selection.isCollapsed)return null;const range=selection.getRangeAt(0),startBlock=closestBlock(range.startContainer),endBlock=closestBlock(range.endContainer);if(!startBlock||startBlock!==endBlock||!root.contains(startBlock))return null;const start=offsetWithin(startBlock,range.startContainer,range.startOffset),end=offsetWithin(startBlock,range.endContainer,range.endOffset);if(start<0||end<=start)return null;const text=startBlock.textContent||'',exact=text.slice(start,end);if(!exact.trim()||exact.length>3000)return null;const section=startBlock.closest('[data-student-note-context]')?.getAttribute('data-student-note-context')||'';if(!section)return null;return{sectionKey:section,blockKey:startBlock.getAttribute('data-student-anchor-block')||'',start,end,exact,prefix:text.slice(Math.max(0,start-120),start),suffix:text.slice(end,end+120),sourceBlockHash:fingerprint(text)};};
const setSelectionActive=value=>document.documentElement.classList.toggle('student-selection-active',!!value);
const positionAction=anchor=>{if(!anchor){if(Date.now()-lastValidSelectionAt>1800){action.hidden=true;setSelectionActive(false);}return;}action.textContent=reanchorId?'Reassociar seleção':'Anotar seleção';action.hidden=false;setSelectionActive(true);if(coarsePointer){action.classList.add('is-touch-selection');action.style.left='';action.style.top='';return;}action.classList.remove('is-touch-selection');const selection=window.getSelection();if(!selection?.rangeCount)return;const range=selection.getRangeAt(0),rects=[...range.getClientRects()].filter(r=>r.width||r.height),rect=rects.at(-1)||range.getBoundingClientRect();const width=action.offsetWidth||104,height=action.offsetHeight||42;let left=Math.max(8,Math.min(window.innerWidth-width-8,rect.left+rect.width/2-width/2)),top=rect.top-height-8;if(top<8)top=rect.bottom+8;action.style.left=`${Math.round(left)}px`;action.style.top=`${Math.round(Math.max(8,Math.min(window.innerHeight-height-8,top)))}px`;};
const refreshSelection=()=>{if(panel.open&&!reanchorId)return;const anchor=captureSelection();if(anchor){pendingAnchor=anchor;lastValidSelectionAt=Date.now();positionAction(anchor);}else positionAction(null);};
const schedule=(delay=0)=>{clearTimeout(selectionTimer);selectionTimer=setTimeout(()=>requestAnimationFrame(refreshSelection),delay);};
const touchSchedule=()=>{schedule(40);[180,480,900].forEach(ms=>setTimeout(refreshSelection,ms));};
root.addEventListener('mouseup',()=>schedule());root.addEventListener('keyup',()=>schedule());root.addEventListener('pointerup',e=>e.pointerType==='touch'||e.pointerType==='pen'?touchSchedule():schedule());root.addEventListener('touchend',touchSchedule,{passive:true});root.addEventListener('contextmenu',()=>{schedule(30);setTimeout(refreshSelection,300);});
document.addEventListener('selectionchange',()=>{const selection=window.getSelection();if(selection&&(nodeInsideRoot(selection.anchorNode)||nodeInsideRoot(selection.focusNode)))schedule(40);else if(!selection||selection.isCollapsed)positionAction(null);});
const poll=setInterval(()=>{if(!document.hidden&&!panel.open)refreshSelection();},250);window.addEventListener('pagehide',()=>clearInterval(poll),{once:true});
const fillCompose=(anchor,id=0)=>{compose=panel.querySelector('[data-inline-note-compose]');if(!compose||!anchor)return;const set=(selector,value)=>{const input=compose.querySelector(selector);if(input)input.value=String(value??'');};set('[data-anchor-section]',anchor.sectionKey);set('[data-anchor-block]',anchor.blockKey);set('[data-anchor-start]',anchor.start);set('[data-anchor-end]',anchor.end);set('[data-anchor-exact]',anchor.exact);set('[data-anchor-prefix]',anchor.prefix);set('[data-anchor-suffix]',anchor.suffix);set('[data-anchor-hash]',anchor.sourceBlockHash);set('[data-annotation-id]',id||'');set('[data-annotation-action]',id?'reanchor':'create_selection');const preview=compose.querySelector('[data-anchor-preview]');if(preview)preview.textContent=anchor.exact;const bodyWrap=compose.querySelector('[data-inline-note-body]'),body=bodyWrap?.querySelector('textarea');if(bodyWrap)bodyWrap.hidden=!!id;if(body)body.disabled=!!id;const question=compose.querySelector('[data-note-question]');if(question){question.hidden=!!id;if(!id)question.open=false;syncQuestionMode(question);}const submit=compose.querySelector('[data-inline-note-submit]');if(submit)submit.textContent=id?'Confirmar reassociação':'Salvar';compose.hidden=false;setComposeMode(true);panel.open=true;const sheet=panel.querySelector('.student-notes-sheet');if(sheet)sheet.scrollTo({top:Math.max(0,compose.offsetTop-20),behavior:'auto'});requestAnimationFrame(()=>requestAnimationFrame(()=>{if(!sheet||!submit)return;const sheetRect=sheet.getBoundingClientRect(),submitRect=submit.getBoundingClientRect();if(submitRect.bottom>sheetRect.bottom)sheet.scrollBy({top:submitRect.bottom-sheetRect.bottom+16,behavior:'auto'});else if(submitRect.top<sheetRect.top)sheet.scrollBy({top:submitRect.top-sheetRect.top-16,behavior:'auto'});}));if(!id)setTimeout(()=>body?.focus({preventScroll:true}),80);};
const activate=()=>{if(!pendingAnchor)return false;const anchor=pendingAnchor;pendingAnchor=null;readingOrigin=captureReadingOrigin(anchor.blockKey);fillCompose(anchor,reanchorId);action.hidden=true;setSelectionActive(false);reanchorHint.hidden=true;window.getSelection()?.removeAllRanges();return true;};
action.addEventListener('pointerdown',e=>{e.preventDefault();e.stopPropagation();activate();});action.addEventListener('touchstart',e=>{e.preventDefault();e.stopPropagation();activate();},{passive:false});action.addEventListener('click',e=>{e.preventDefault();activate();});
const composeQuestion=compose?.querySelector('[data-note-question]');
composeQuestion?.addEventListener('toggle',()=>syncQuestionMode(composeQuestion));
panel.addEventListener('toggle',()=>{if(!panel.open&&compose&&!compose.hidden&&composeQuestion?.open){composeQuestion.open=false;syncQuestionMode(composeQuestion);}});
const unmark=()=>root.querySelectorAll('mark.student-inline-note-mark').forEach(mark=>{
  const parent=mark.parentNode;
  mark.replaceWith(...mark.childNodes);
  parent?.normalize();
});
const findOccurrences=(text,needle)=>{const out=[];let from=0;while(needle&&from<=text.length){const at=text.indexOf(needle,from);if(at<0)break;out.push(at);from=at+Math.max(1,needle.length);}return out;};
const scoreCandidate=(text,start,note)=>{let score=0,end=start+note.exact.length;if(note.prefix){const before=text.slice(Math.max(0,start-note.prefix.length),start);if(before===note.prefix)score+=4;else if(before.endsWith(note.prefix.slice(-Math.min(40,note.prefix.length))))score+=2;}if(note.suffix){const after=text.slice(end,end+note.suffix.length);if(after===note.suffix)score+=4;else if(after.startsWith(note.suffix.slice(0,Math.min(40,note.suffix.length))))score+=2;}return score;};
// Offset and ordinal alone do not identify a passage after an editorial CMS
// revision: a different paragraph can occupy the same slot and quote the same
// short phrase. Prefer the full source-block fingerprint where available;
// otherwise demand an exact textual neighbour, not a section/ordinal heuristic.
const candidatesInBlock=(block,note)=>{
  const text=block.textContent||'';
  const unchanged=!!note.sourceBlockHash&&fingerprint(text)===note.sourceBlockHash;
  return findOccurrences(text,note.exact).map(start=>{
    const end=start+note.exact.length;
    const prefixExact=!!note.prefix&&text.slice(Math.max(0,start-note.prefix.length),start)===note.prefix;
    const suffixExact=!!note.suffix&&text.slice(end,end+note.suffix.length)===note.suffix;
    const hashMatch=unchanged&&Number(note.start)===start&&Number(note.end)===end;
    const context=scoreCandidate(text,start,note);
    // A one-word or punctuation-only neighbour is not independent evidence:
    // such contexts occur routinely across a long photographic material.
    const contextualProof=(prefixExact&&note.prefix.trim().length>=12)
      ||(suffixExact&&note.suffix.trim().length>=12);
    return{block,start,end,score:(hashMatch?16:0)+context,strong:hashMatch||contextualProof};
  });
};
const uniqueBest=list=>{
  if(!list.length)return null;
  const sorted=[...list].sort((a,b)=>b.score-a.score);
  if(!sorted[0].strong||(sorted.length>1&&sorted[0].score===sorted[1].score))
    return{ambiguous:true};
  return sorted[0];
};
const locate=note=>{
  if(note.anchorType!=='selection'||!note.exact)return null;
  const preferred=root.querySelector(`[data-student-anchor-block="${CSS.escape(note.blockKey||'')}"]`);
  // Do not accept even an unchanged-looking old slot before examining the
  // rest of the currently published page. The CMS could have copied that
  // entire paragraph into the slot while moving the actual source elsewhere.
  const all=[];
  root.querySelectorAll('[data-student-anchor-block]').forEach(block=>all.push(...candidatesInBlock(block,note)));
  const best=uniqueBest(all);
  if(best?.ambiguous)return{state:'ambiguous',block:preferred||null};
  if(best)return{...best,state:best.block===preferred?'linked':'moved'};
  return{state:preferred?'altered':'removed',block:preferred||null};
};
// Segments describe every note covering a piece of text, without nesting
// marks or extracting structural CMS elements (links, emphasis, headings).
const annotationSegments=items=>{
  const offsets=[...new Set(items.flatMap(item=>[item.start,item.end]))].sort((a,b)=>a-b);
  const segments=[];
  for(let i=0;i<offsets.length-1;i++){
    const start=offsets[i],end=offsets[i+1];
    const ids=items.filter(item=>item.start<=start&&item.end>=end).map(item=>item.note.id).sort((a,b)=>a-b);
    if(!ids.length)continue;
    const previous=segments.at(-1);
    if(previous&&previous.end===start&&previous.ids.join(',')===ids.join(','))previous.end=end;
    else segments.push({start,end,ids});
  }
  return segments;
};
const openAnnotatedFragment=ids=>{
  if(ids.length===1){
    editSavedNote(panel.querySelector(`[data-annotation-item="${ids[0]}"]`));
    return;
  }
  listSavedNotes();
  const options=overlapChooser.querySelector('[data-note-overlap-options]');
  if(!options)return;
  options.replaceChildren();
  ids.forEach((id,index)=>{
    const note=annotations.find(item=>item.id===id);
    if(!note)return;
    const button=document.createElement('button');
    button.type='button';
    button.className='student-note-overlap-option';
    button.dataset.noteOverlapChoice=String(id);
    button.textContent=`Anotação ${index+1}: ${(note.body||note.exact||'Sem descrição').slice(0,120)}`;
    options.appendChild(button);
  });
  overlapChooser.hidden=false;
  panel.classList.add('is-overlap-choosing');
  if(noteBack)noteBack.hidden=false;
  panel.open=true;
  sheet?.scrollTo({top:0,behavior:'auto'});
  requestAnimationFrame(()=>options.querySelector('button')?.focus({preventScroll:true}));
};
const setStatus=(id,text,state)=>{const status=panel.querySelector(`[data-annotation-status="${id}"]`);if(status){status.textContent=text;status.dataset.anchorState=state;}};
const renderHighlights=()=>{
  unmark();
  const grouped=new Map();
  annotations.forEach(note=>{
    const loc=locate(note);
    if(!loc)return;
    if(loc.state==='altered'){setStatus(note.id,'Trecho alterado — a anotação foi preservada.','altered');return;}
    if(loc.state==='removed'){setStatus(note.id,'Trecho original removido — a anotação foi preservada.','removed');return;}
    if(loc.state==='ambiguous'){setStatus(note.id,'Trecho não identificado com segurança — reassocie para restaurar o destaque.','ambiguous');return;}
    setStatus(note.id,loc.state==='moved'?'Trecho localizado após alteração do material.':'Vinculada ao texto.',loc.state);
    if(!grouped.has(loc.block))grouped.set(loc.block,[]);
    grouped.get(loc.block).push({note,start:loc.start,end:loc.end});
  });
  grouped.forEach((items,block)=>{
    const segments=annotationSegments(items);
    let cursor=0;
    // Freeze the original text nodes and work from right to left within each.
    // This preserves <a>, <strong> and <em> elements and all their attributes.
    textNodes(block).forEach(node=>{
      const length=node.nodeValue?.length||0,end=cursor+length;
      const matches=segments.filter(segment=>segment.end>cursor&&segment.start<end);
      for(let i=matches.length-1;i>=0;i--){
        const segment=matches[i];
        const from=Math.max(segment.start,cursor)-cursor;
        const to=Math.min(segment.end,end)-cursor;
        if(to<=from)continue;
        const range=document.createRange();
        range.setStart(node,from);
        range.setEnd(node,to);
        const mark=document.createElement('mark');
        mark.className='student-inline-note-mark';
        mark.dataset.annotationId=String(segment.ids[0]);
        mark.dataset.annotationIds=segment.ids.join(',');
        mark.setAttribute('role','button');
        mark.tabIndex=0;
        const plural=segment.ids.length>1;
        const label=plural?`Escolher entre ${segment.ids.length} anotações`:'Abrir anotação';
        mark.title=label;
        mark.setAttribute('aria-label',label);
        mark.appendChild(range.extractContents());
        range.insertNode(mark);
        const open=event=>{
          event.preventDefault();
          event.stopPropagation();
          // A real pointer drag that selects text inside an existing highlight
          // also emits click on mouseup. Let the selection action handle it.
          // Keep keyboard and programmatic activation available even if an
          // unrelated selection is still present elsewhere on the page.
          if(event.type==='click'&&event.detail>0&&!window.getSelection()?.isCollapsed)return;
          openAnnotatedFragment(segment.ids);
        };
        mark.addEventListener('click',open);
        mark.addEventListener('keydown',event=>{
          if(event.key==='Enter'||event.key===' '){open(event);}
        });
      }
      cursor=end;
    });
  });
};
const questionComposer=(annotationId,question=null)=>question?.id
  ?(question.url
    ?`<a class="student-material-note-secondary" href="${question.url.replaceAll('&','&amp;').replaceAll('"','&quot;')}">Ver dúvida</a>`
    :'<small class="student-note-linked-unavailable">Dúvida vinculada — acesso indisponível</small>')
  :`<details class="student-note-question" data-note-question><summary>Transformar em dúvida</summary><div class="student-note-question-fields"><p>O texto da anotação será usado como a dúvida. Você não precisa escrevê-lo de novo.</p><label>Título da dúvida<input name="question_title" maxlength="180" placeholder="Resuma a dúvida em uma frase" data-note-question-title></label><fieldset><legend>Quem pode participar?</legend><label><input type="radio" name="question_visibility" value="private" checked> Somente eu e o professor</label><label><input type="radio" name="question_visibility" value="cohort"> Minha turma</label><label><input type="radio" name="question_visibility" value="course"> Todas as turmas deste curso</label></fieldset><button class="button button-secondary" type="submit" name="annotation_action" value="question" data-note-question-publish>Publicar dúvida</button></div></details>`;
const hidden=(name,value)=>`<input type="hidden" name="${name}" value="${String(value??'').replaceAll('&','&amp;').replaceAll('"','&quot;')}">`;
const updateCounts=()=>{const count=panel.querySelectorAll('[data-annotation-item],.student-note-item.is-legacy').length;document.querySelectorAll('.student-notes-entry b,.student-notes-panel>summary b').forEach(el=>{el.textContent=String(count);el.hidden=count===0;});};
const renderArticle=(note,question=null)=>{let list=panel.querySelector('.student-notes-list');if(!list){list=document.createElement('div');list.className='student-notes-list';panel.querySelector('.student-note-new')?.after(list);}const formTemplate=panel.querySelector('[data-inline-note-compose]');const csrf=formTemplate?.querySelector('[name="_csrf"]')?.value||'',page=formTemplate?.querySelector('[name="page_id"]')?.value||'',returnTo=formTemplate?.querySelector('[name="return_to"]')?.value||'';const article=document.createElement('article');article.className='student-note-item '+(note.anchorType==='selection'?'is-selection':'is-page');article.id=`anotacao-${note.id}`;article.dataset.annotationItem=String(note.id);article.innerHTML=`<header><span>${note.anchorType==='selection'?'Trecho':'Página'}</span><strong class="student-note-title">${note.anchorType==='selection'?'Trecho do material':'Anotação geral'}</strong>${note.anchorType==='selection'?`<small data-annotation-status="${note.id}">Localizando trecho…</small>`:''}</header>${note.anchorType==='selection'?`<blockquote class="student-note-quote"></blockquote>`:''}<p class="student-note-body-preview"></p><button class="student-note-edit-trigger" type="button" data-annotation-edit="${note.id}">Editar anotação</button><form method="post" action="/aluno/material-anotacao.php">${hidden('_csrf',csrf)}${hidden('page_id',page)}${hidden('section_key',note.sectionKey)}${hidden('return_to',returnTo)}${hidden('annotation_id',note.id)}<label class="student-note-edit-field">Anotação<textarea name="body" rows="5" maxlength="5000"></textarea></label><div class="student-material-note-actions student-note-actions-group"><button class="button" type="submit" name="annotation_action" value="update">Salvar alterações</button>${note.anchorType==='selection'?`<button class="student-material-note-secondary" type="button" data-annotation-reanchor="${note.id}">Reassociar</button><button class="student-material-note-secondary" type="submit" name="annotation_action" value="detach">Tornar geral</button>`:''}<button class="student-material-note-remove" type="submit" name="annotation_action" value="remove">Remover</button></div>${questionComposer(note.id,question)}</form>`;const quote=article.querySelector('.student-note-quote');if(quote)quote.textContent=note.exact;const title=article.querySelector('.student-note-title');if(title&&note.anchorType==='selection')title.textContent=(note.exact||'Trecho do material').slice(0,140);article.querySelector('textarea').value=note.body;article.querySelector('.student-note-body-preview').textContent=note.body;list.appendChild(article);panel.querySelector('.student-notes-empty')?.remove();updateCounts();return article;};
const requestForm=async(form,submitter)=>{const data=new FormData(form);if(submitter?.name)data.set(submitter.name,submitter.value);const response=await fetch(form.action||'/aluno/material-anotacao.php',{method:'POST',body:data,credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});const finalUrl=new URL(response.url,location.href);if(response.status===401||finalUrl.pathname.endsWith('/aluno/login.php'))throw new Error('Sua sessão expirou. Entre novamente para continuar.');let payload=null;try{payload=await response.json();}catch(_){throw new Error('Resposta inválida ao salvar anotação.');}if(!response.ok||!payload?.ok)throw new Error(payload?.error||'Não foi possível salvar a anotação.');return payload;};
const statusMessage=(form,message,error=false)=>{
  let box=form.querySelector('[data-note-save-status]');
  if(!box){
    box=document.createElement('small');
    box.dataset.noteSaveStatus='';
    const actions=form.querySelector('.student-note-actions-group,.student-inline-note-primary-actions');
    if(actions)actions.before(box);
    else form.appendChild(box);
  }
  box.textContent=message;
  box.classList.toggle('is-error',error);
  box.setAttribute('role',error?'alert':'status');
  box.setAttribute('aria-live',error?'assertive':'polite');
  if(!error)return;
  // A failed request keeps its draft. Announce the error where the student
  // is working; scroll only the note sheet, never the reading region behind it.
  box.tabIndex=-1;
  requestAnimationFrame(()=>{
    if(!panel.open||!box.isConnected||!box.getClientRects().length)return;
    box.focus({preventScroll:true});
    const area=box.closest('.student-notes-sheet');
    if(!area)return;
    const note=box.getBoundingClientRect(),viewport=area.getBoundingClientRect(),gap=12;
    if(note.top<viewport.top+gap)area.scrollTop-=viewport.top+gap-note.top;
    else if(note.bottom>viewport.bottom-gap)area.scrollTop+=note.bottom-viewport.bottom+gap;
  });
};
panel.addEventListener('submit',async event=>{const form=event.target.closest('form[action="/aluno/material-anotacao.php"]');if(!form)return;event.preventDefault();const submitter=event.submitter;const actionName=submitter?.name==='annotation_action'?submitter.value:form.querySelector('[name="annotation_action"]')?.value||'';const id=Number(form.querySelector('[name="annotation_id"]')?.value||0);if(actionName==='remove'||submitter?.name==='remove'){const dialog=panel.querySelector('[data-note-remove-confirm]');if(!dialog||typeof dialog.showModal!=='function')throw new Error('Confirmação de exclusão indisponível.');dialog.returnValue='cancel';const approved=await new Promise(resolve=>{dialog.addEventListener('close',()=>resolve(dialog.returnValue==='confirm'),{once:true});dialog.showModal();});if(!approved)return;}const origin=readingOrigin||captureReadingOrigin(form.querySelector('[data-anchor-block]')?.value||'');form.querySelectorAll('button[type="submit"]').forEach(b=>b.disabled=true);try{const payload=await requestForm(form,submitter);if(payload.legacy){if(payload.removed){form.closest('.student-note-item')?.remove();listSavedNotes();}else{statusMessage(form,'Salvo.');const preview=form.closest('.student-note-item')?.querySelector('.student-note-body-preview');if(preview)preview.textContent=form.querySelector('textarea[name="body"]')?.value||'';}updateCounts();return;}if(actionName==='create_selection'||actionName==='create_page'){const note=normalizeNote(payload.annotation);annotations.push(note);const linkedQuestion=payload.question?{...payload.question,url:payload.question_url||''}:null;renderArticle(note,linkedQuestion);if(actionName==='create_selection'){compose.hidden=true;setComposeMode(false);compose.querySelector('textarea').value='';panel.open=false;readingOrigin=null;renderHighlights();restoreReadingOrigin(origin);}else{form.reset();form.closest('[data-student-note-new]')?.removeAttribute('open');panel.classList.remove('is-page-questioning');renderHighlights();}}else if(actionName==='update'){const note=annotations.find(n=>n.id===id);if(note)note.body=form.querySelector('textarea')?.value||'';const preview=form.closest('[data-annotation-item]')?.querySelector('.student-note-body-preview');if(preview)preview.textContent=note?.body||'';statusMessage(form,'Salvo.');}else if(actionName==='question'){const note=annotations.find(n=>n.id===id);if(note)note.body=form.querySelector('textarea')?.value||'';const details=form.querySelector('[data-note-question]');if(details&&payload.question){const link=document.createElement('a');link.className='student-material-note-secondary';if(!payload.question_url)throw new Error('A dúvida vinculada não tem um destino autorizado.');link.href=payload.question_url;link.textContent='Ver dúvida';details.replaceWith(link);}panel.classList.remove('is-existing-questioning');const preview=form.closest('[data-annotation-item]')?.querySelector('.student-note-body-preview');if(preview)preview.textContent=note?.body||'';statusMessage(form,'Anotação salva e dúvida publicada.');}else if(actionName==='remove'){annotations=annotations.filter(n=>n.id!==id);form.closest('[data-annotation-item]')?.remove();listSavedNotes();renderHighlights();updateCounts();}else if(actionName==='detach'){const note=normalizeNote(payload.annotation);annotations=annotations.map(n=>n.id===id?note:n);const old=form.closest('[data-annotation-item]');const linkedQuestion=payload.question?{...payload.question,url:payload.question_url||''}:null;const fresh=renderArticle(note,linkedQuestion);old?.remove();updateCounts();renderHighlights();editSavedNote(fresh);}else if(actionName==='reanchor'){const note=normalizeNote(payload.annotation);annotations=annotations.map(n=>n.id===id?note:n);compose.hidden=true;setComposeMode(false);reanchorId=0;reanchorHint.hidden=true;panel.open=false;readingOrigin=null;const article=panel.querySelector(`[data-annotation-item="${id}"]`);if(article){article.querySelector('.student-note-quote').textContent=note.exact;article.querySelector('.student-note-title').textContent=(note.exact||'Trecho do material').slice(0,140);article.querySelector('[name="section_key"]').value=note.sectionKey;}renderHighlights();restoreReadingOrigin(origin);}}catch(error){statusMessage(form,error instanceof Error?error.message:'Não foi possível salvar.',true);}finally{form.querySelectorAll('button[type="submit"]').forEach(b=>b.disabled=false);}});
panel.addEventListener('click',event=>{
  const overlapChoice=event.target.closest('[data-note-overlap-choice]');
  if(overlapChoice){
    editSavedNote(panel.querySelector(`[data-annotation-item="${overlapChoice.dataset.noteOverlapChoice}"]`));
    return;
  }
  const back=event.target.closest('[data-student-notes-back-button]');
  if(back){
    const active=panel.querySelector('.student-note-item.is-active');
    const trigger=active?.querySelector('[data-annotation-edit],[data-legacy-edit]');
    listSavedNotes();
    // Returning to the list is a real interaction state change: focus must not
    // remain on the now-hidden Back control or editor form.
    trigger?.focus();
    return;
  }
  const edit=event.target.closest('[data-annotation-edit],[data-legacy-edit]');
  if(edit){editSavedNote(edit.closest('.student-note-item'));return;}
  const re=event.target.closest('[data-annotation-reanchor]');if(re){listSavedNotes();reanchorId=Number(re.dataset.annotationReanchor||0);if(!reanchorId)return;compose.hidden=true;setComposeMode(false);panel.open=false;reanchorHint.hidden=false;action.hidden=true;setSelectionActive(false);pendingAnchor=null;window.getSelection()?.removeAllRanges();return;}const cancel=event.target.closest('[data-inline-note-cancel]');if(cancel){compose.hidden=true;setComposeMode(false);reanchorId=0;reanchorHint.hidden=true;const body=compose.querySelector('textarea');if(body){body.disabled=false;body.value='';}panel.open=false;const origin=readingOrigin;readingOrigin=null;restoreReadingOrigin(origin);}});
panel.addEventListener('toggle',event=>{
  const details=event.target;
  if(details===panel){if(!panel.open)listSavedNotes();return;}
  if(details.matches?.('[data-student-note-new]')){
    panel.classList.toggle('is-general-composing',details.open);
    const summary=details.querySelector('summary');
    if(summary)summary.textContent=details.open?'← Todas as anotações':'Nova anotação geral';
    if(details.open){
      panel.classList.remove('is-editing','is-existing-questioning');
      if(noteBack)noteBack.hidden=true;
      sheet?.scrollTo({top:0,behavior:'auto'});
    }else panel.classList.remove('is-page-questioning');
    return;
  }
  if(!details.matches?.('[data-note-question]'))return;
  const activeArticle=details.closest('.student-note-item.is-active');
  if(activeArticle&&panel.classList.contains('is-editing')){
    panel.classList.toggle('is-existing-questioning',details.open);
    const summary=details.querySelector('summary');
    if(summary)summary.textContent=details.open?'← Voltar à anotação':'Transformar em dúvida';
    if(details.open)sheet?.scrollTo({top:0,behavior:'auto'});
  }else if(details.closest('[data-student-note-new]')){
    panel.classList.toggle('is-page-questioning',details.open);
    const summary=details.querySelector('summary');
    if(summary)summary.textContent=details.open?'← Voltar à anotação geral':'Também é uma dúvida?';
  }
},true);
reanchorHint.querySelector('button')?.addEventListener('click',()=>{reanchorId=0;reanchorHint.hidden=true;action.hidden=true;setSelectionActive(false);pendingAnchor=null;window.getSelection()?.removeAllRanges();});
renderHighlights();
})();