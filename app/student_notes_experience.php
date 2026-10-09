<?php
declare(strict_types=1);

/**
 * Material annotations are a private layer over the editorial document.
 * The source HTML is never modified by a student's note. Selection anchors
 * deliberately store redundant evidence (quote, context, block, offsets and
 * revision) so a note survives ordinary editorial changes.
 */
function student_notes_return_url(): string {
    $uri=(string)($_SERVER['REQUEST_URI']??'/aluno/');
    $parts=parse_url($uri);$path=(string)($parts['path']??'/');$params=[];
    if(isset($parts['query']))parse_str((string)$parts['query'],$params);
    $params['anotacoes']='1';$query=http_build_query($params);
    return $path.($query!==''?'?'.$query:'').'#anotacoes';
}

function student_notes_section_title(DOMXPath $xpath,DOMElement $section,int $index): string {
    $explicit=trim($section->getAttribute('data-cms-section-name'));
    if($explicit!=='')return $explicit;
    $heading=$xpath->query('.//*[self::h1 or self::h2 or self::h3 or self::h4][1]',$section)?->item(0);
    $text=$heading?trim((string)$heading->textContent):'';
    return $text!==''?$text:'Trecho '.($index+1);
}

function student_notes_hidden_fields(int $pageId,string $sectionKey,string $returnTo): string {
    return '<input type="hidden" name="_csrf" value="'.h(csrf_token('student-material-note')).'">'
        .'<input type="hidden" name="page_id" value="'.$pageId.'">'
        .'<input type="hidden" name="section_key" value="'.h($sectionKey).'">'
        .'<input type="hidden" name="return_to" value="'.h($returnTo).'">';
}

function student_material_annotations_available(PDO $db): bool {
    try{$q=$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='student_material_annotations'");return (bool)$q->fetchColumn();}
    catch(Throwable){return false;}
}

function student_material_annotations_for_page(PDO $db,int $studentId,int $pageId): array {
    if(!student_material_annotations_available($db))return [];
    $q=$db->prepare('SELECT * FROM student_material_annotations WHERE student_id=? AND page_id=? ORDER BY created_at,id');
    $q->execute([$studentId,$pageId]);return $q->fetchAll();
}

function student_material_annotation_owned(PDO $db,int $annotationId,int $studentId,int $pageId): ?array {
    if($annotationId<1||!student_material_annotations_available($db))return null;
    $q=$db->prepare('SELECT * FROM student_material_annotations WHERE id=? AND student_id=? AND page_id=?');
    $q->execute([$annotationId,$studentId,$pageId]);return $q->fetch()?:null;
}

function student_material_annotation_anchor_input(array $input): array {
    $quote=student_workspace_text($input['quote_exact']??'',3000);
    $prefix=student_workspace_text($input['quote_prefix']??'',300);
    $suffix=student_workspace_text($input['quote_suffix']??'',300);
    $block=student_workspace_text($input['block_key']??'',180);
    $section=activity_slug((string)($input['section_key']??''));
    $start=max(0,(int)($input['start_offset']??0));$end=max(0,(int)($input['end_offset']??0));
    $blockHash=student_workspace_text($input['source_block_hash']??'',80);
    $revision=student_workspace_text($input['source_page_revision']??'',120);
    if($quote===''||$block===''||$section===''||$end<=$start)throw new RuntimeException('Selecione um trecho válido do material.');
    return ['section_key'=>$section,'quote_exact'=>$quote,'quote_prefix'=>$prefix,'quote_suffix'=>$suffix,'block_key'=>$block,'start_offset'=>$start,'end_offset'=>$end,'source_block_hash'=>$blockHash,'source_page_revision'=>$revision];
}

function student_material_annotation_create(PDO $db,int $studentId,int $pageId,?int $lessonId,string $body,string $anchorType,array $anchor=[]): int {
    $body=student_workspace_text($body,5000);if($body==='')throw new RuntimeException('Escreva a anotação.');
    $now=utc_now();$anchorType=$anchorType==='selection'?'selection':'page';
    if($anchorType==='selection')$anchor=student_material_annotation_anchor_input($anchor);
    else $anchor=['section_key'=>'pagina','quote_exact'=>'','quote_prefix'=>'','quote_suffix'=>'','block_key'=>'','start_offset'=>null,'end_offset'=>null,'source_block_hash'=>'','source_page_revision'=>student_workspace_text($anchor['source_page_revision']??'',120)];
    $q=$db->prepare('INSERT INTO student_material_annotations(annotation_uuid,student_id,page_id,lesson_id,section_key,anchor_type,body,quote_exact,quote_prefix,quote_suffix,block_key,start_offset,end_offset,source_block_hash,source_page_revision,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $q->execute([student_uuid(),$studentId,$pageId,$lessonId,$anchor['section_key'],$anchorType,$body,$anchor['quote_exact'],$anchor['quote_prefix'],$anchor['quote_suffix'],$anchor['block_key'],$anchor['start_offset'],$anchor['end_offset'],$anchor['source_block_hash'],$anchor['source_page_revision'],$now,$now]);
    return (int)$db->lastInsertId();
}

function student_material_annotation_update_body(PDO $db,int $annotationId,int $studentId,int $pageId,string $body): void {
    if(!student_material_annotation_owned($db,$annotationId,$studentId,$pageId))throw new RuntimeException('Anotação não encontrada.');
    $body=student_workspace_text($body,5000);if($body==='')throw new RuntimeException('Escreva a anotação.');
    $db->prepare('UPDATE student_material_annotations SET body=?,updated_at=? WHERE id=? AND student_id=? AND page_id=?')->execute([$body,utc_now(),$annotationId,$studentId,$pageId]);
}

function student_material_annotation_reanchor(PDO $db,int $annotationId,int $studentId,int $pageId,?int $lessonId,array $input): void {
    if(!student_material_annotation_owned($db,$annotationId,$studentId,$pageId))throw new RuntimeException('Anotação não encontrada.');
    $a=student_material_annotation_anchor_input($input);
    $db->prepare("UPDATE student_material_annotations SET lesson_id=?,section_key=?,anchor_type='selection',quote_exact=?,quote_prefix=?,quote_suffix=?,block_key=?,start_offset=?,end_offset=?,source_block_hash=?,source_page_revision=?,updated_at=? WHERE id=? AND student_id=? AND page_id=?")
        ->execute([$lessonId,$a['section_key'],$a['quote_exact'],$a['quote_prefix'],$a['quote_suffix'],$a['block_key'],$a['start_offset'],$a['end_offset'],$a['source_block_hash'],$a['source_page_revision'],utc_now(),$annotationId,$studentId,$pageId]);
}

function student_material_annotation_detach(PDO $db,int $annotationId,int $studentId,int $pageId): void {
    if(!student_material_annotation_owned($db,$annotationId,$studentId,$pageId))throw new RuntimeException('Anotação não encontrada.');
    $db->prepare("UPDATE student_material_annotations SET lesson_id=NULL,section_key='pagina',anchor_type='page',quote_exact='',quote_prefix='',quote_suffix='',block_key='',start_offset=NULL,end_offset=NULL,source_block_hash='',updated_at=? WHERE id=? AND student_id=? AND page_id=?")
        ->execute([utc_now(),$annotationId,$studentId,$pageId]);
}

function student_material_annotation_delete(PDO $db,int $annotationId,int $studentId,int $pageId): void {
    $db->prepare('DELETE FROM student_material_annotations WHERE id=? AND student_id=? AND page_id=?')->execute([$annotationId,$studentId,$pageId]);
}

function student_notes_instrument_blocks(DOMXPath $xpath,DOMElement $section,string $sectionKey): void {
    $ordinal=0;
    $blocks=$xpath->query('.//*[self::p or self::li or self::blockquote or self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6 or self::figcaption or self::td or self::th or self::pre]',$section)?:[];
    foreach(iterator_to_array($blocks) as $block){
        if(!$block instanceof DOMElement||trim((string)$block->textContent)==='')continue;
        $tag=strtolower($block->tagName);$block->setAttribute('data-student-anchor-block',$sectionKey.':'.$tag.':'.$ordinal++);
    }
}

function student_material_render_notebook(PDO $db,array $student,array $page,array $document): array {
    $html=(string)($document['html']??'');
    if($html===''||!str_contains($html,'data-cms-section'))return $document;

    $legacyNotes=student_material_notes_for_page($db,(int)$student['id'],(int)$page['id']);
    $annotations=student_material_annotations_for_page($db,(int)$student['id'],(int)$page['id']);
    $previous=libxml_use_internal_errors(true);
    $dom=new DOMDocument('1.0','UTF-8');
    $dom->loadHTML('<?xml encoding="utf-8" ?><div id="student-notes-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
    $xpath=new DOMXPath($dom);$sections=['pagina'=>['key'=>'pagina','title'=>'Página inteira','whole_page'=>true]];$index=0;
    foreach(iterator_to_array($xpath->query('//*[@data-cms-section]')?:[]) as $section){
        if(!$section instanceof DOMElement)continue;
        $key=activity_slug($section->getAttribute('data-cms-section'));if($key==='')continue;
        $sections[$key]=['key'=>$key,'title'=>student_notes_section_title($xpath,$section,$index++),'whole_page'=>false];
        $section->setAttribute('data-student-note-context',$key);
        if(!$section->hasAttribute('id'))$section->setAttribute('id','nota-trecho-'.$key);
        student_notes_instrument_blocks($xpath,$section,$key);
    }

    $root=$dom->getElementById('student-notes-root');$out='';
    if($root)foreach(iterator_to_array($root->childNodes) as $child)$out.=$dom->saveHTML($child);
    libxml_clear_errors();libxml_use_internal_errors($previous);

    $returnTo=student_notes_return_url();$count=count($legacyNotes)+count($annotations);$open=isset($_GET['anotacoes']);
    $revision=trim((string)($page['updated_at']??''));if($revision==='')$revision=substr(hash('sha256',$html),0,24);
    $cohortUuid=student_workspace_text($_GET['cohort']??'',120);
    $questionContext=function_exists('student_enrollment_material_context')?student_enrollment_material_context($db,$student,$page,$cohortUuid):null;
    if($questionContext&&trim((string)($questionContext['cohort_uuid']??''))!=='')$cohortUuid=(string)$questionContext['cohort_uuid'];
    $questionAvailable=$questionContext&&(int)($questionContext['cohort_id']??0)>0&&function_exists('student_question_create')&&function_exists('student_question_attach_annotation');
    $questionUrl=static function(int $annotationId,int $questionId=0) use($cohortUuid): string {
        $params=[];if($cohortUuid!=='')$params['cohort']=$cohortUuid;
        if($questionId>0)$params['id']=$questionId;else $params['annotation']=$annotationId;
        return '/aluno/duvidas.php?'.http_build_query($params);
    };
    $questionComposer=static function(bool $existing=false) use($questionAvailable,$cohortUuid): string {
        if(!$questionAvailable)return '';
        $submitName=$existing?'annotation_action':'create_question';$submitValue=$existing?'question':'1';
        $button=$existing?'Publicar dúvida':'Salvar e publicar dúvida';
        return '<details class="student-note-question" data-note-question>'
            .'<summary>'.($existing?'Transformar em dúvida':'Também é uma dúvida?').'</summary>'
            .'<div class="student-note-question-fields">'
            .'<p>O texto da anotação será usado como a dúvida. Você não precisa escrevê-lo de novo.</p>'
            .'<input type="hidden" name="cohort" value="'.h($cohortUuid).'">'
            .'<label>Título da dúvida<input name="question_title" maxlength="180" placeholder="Resuma a dúvida em uma frase" data-note-question-title></label>'
            .'<fieldset><legend>Quem pode participar?</legend><label><input type="radio" name="question_visibility" value="private" checked> Somente eu e o professor</label><label><input type="radio" name="question_visibility" value="cohort"> Minha turma</label><label><input type="radio" name="question_visibility" value="course"> Todas as turmas deste curso</label></fieldset>'
            .'<button class="button button-secondary" type="submit" name="'.$submitName.'" value="'.$submitValue.'" data-note-question-publish>'.$button.'</button>'
            .'</div></details>';
    };
    $entry='<div class="student-notes-entry"><a href="'.h($returnTo).'" aria-controls="anotacoes"><span>Anotações</span>'.($count>0?'<b>'.$count.'</b>':'').'</a></div>';
    $panel='<details class="student-notes-panel" id="anotacoes" data-student-notes-panel data-student-page-revision="'.h($revision).'"'.($open?' open':'').'>';
    $panel.='<summary><span>Anotações</span>'.($count>0?'<b>'.$count.'</b>':'').'</summary>';
    $panel.='<div class="student-notes-sheet"><header class="student-notes-head"><div><span>Material</span><h2>Suas anotações</h2></div><p>Selecione uma anotação para editar ou marque um trecho do material.</p></header>';
    $panel.='<div class="student-notes-back" data-student-notes-back hidden><button type="button" data-student-notes-back-button>← Todas as anotações</button></div>';

    $panel.='<form class="student-inline-note-compose" method="post" action="/aluno/material-anotacao.php" data-inline-note-compose hidden>'
        .'<input type="hidden" name="_csrf" value="'.h(csrf_token('student-material-note')).'">'
        .'<input type="hidden" name="page_id" value="'.(int)$page['id'].'">'
        .'<input type="hidden" name="return_to" value="'.h($returnTo).'">'
        .'<input type="hidden" name="annotation_action" value="create_selection" data-annotation-action>'
        .'<input type="hidden" name="annotation_id" value="" data-annotation-id>'
        .'<input type="hidden" name="section_key" value="" data-anchor-section>'
        .'<input type="hidden" name="block_key" value="" data-anchor-block>'
        .'<input type="hidden" name="start_offset" value="" data-anchor-start>'
        .'<input type="hidden" name="end_offset" value="" data-anchor-end>'
        .'<input type="hidden" name="quote_exact" value="" data-anchor-exact>'
        .'<input type="hidden" name="quote_prefix" value="" data-anchor-prefix>'
        .'<input type="hidden" name="quote_suffix" value="" data-anchor-suffix>'
        .'<input type="hidden" name="source_block_hash" value="" data-anchor-hash>'
        .'<input type="hidden" name="source_page_revision" value="'.h($revision).'">'
        .'<div class="student-inline-note-selected"><span>Trecho selecionado</span><blockquote data-anchor-preview></blockquote></div>'
        .'<label data-inline-note-body>Anotação<textarea name="body" rows="4" maxlength="5000" placeholder="Escreva o que você quer guardar."></textarea></label>'
        .'<div class="student-material-note-actions student-inline-note-primary-actions"><button class="button" type="submit" data-inline-note-submit>Salvar</button><button class="button button-secondary student-inline-note-cancel" type="button" data-inline-note-cancel>Cancelar</button></div>'
        .$questionComposer(false)
        .'</form>';

    if($annotations||$legacyNotes){
        $panel.='<div class="student-notes-list">';
        foreach($annotations as $note){
            $id=(int)$note['id'];$selection=(string)$note['anchor_type']==='selection';
            $linkedQuestion=function_exists('student_question_from_annotation')?student_question_from_annotation($db,(int)$student['id'],$id):null;
            $panel.='<article class="student-note-item'.($selection?' is-selection':' is-page').'" id="anotacao-'.$id.'" data-annotation-item="'.$id.'">'
                .'<header><span>'.($selection?'Trecho':'Página').'</span><strong>'.($selection?'Anotação vinculada ao texto':'Anotação geral').'</strong>'.($selection?'<small data-annotation-status="'.$id.'">Localizando trecho…</small>':'').'</header>'
                .'<p class="student-note-body-preview">'.nl2br(h((string)$note['body'])).'</p>'
                .'<button class="student-note-edit-trigger" type="button" data-annotation-edit="'.$id.'">Editar anotação</button>';
            if($selection)$panel.='<blockquote class="student-note-quote">'.h((string)$note['quote_exact']).'</blockquote>';
            $panel.='<form method="post" action="/aluno/material-anotacao.php">'.student_notes_hidden_fields((int)$page['id'],(string)$note['section_key'],$returnTo)
                .'<input type="hidden" name="annotation_id" value="'.$id.'"><label class="student-note-edit-field">Anotação<textarea name="body" rows="5" maxlength="5000">'.h((string)$note['body']).'</textarea></label>'
                .'<div class="student-material-note-actions student-note-actions-group"><button class="button" type="submit" name="annotation_action" value="update">Salvar alterações</button>';
            if($selection)$panel.='<button class="student-material-note-secondary" type="button" data-annotation-reanchor="'.$id.'">Reassociar</button><button class="student-material-note-secondary" type="submit" name="annotation_action" value="detach">Tornar geral</button>';
            if($linkedQuestion)$panel.='<a class="student-material-note-secondary" href="'.h($questionUrl($id,(int)$linkedQuestion['id'])).'">Ver dúvida</a>';
            $panel.='<button class="student-material-note-remove" type="submit" name="annotation_action" value="remove">Remover</button></div>'
                .(!$linkedQuestion?$questionComposer(true):'').'</form></article>';
        }
        foreach($legacyNotes as $key=>$note){
            $meta=$sections[$key]??['title'=>'Trecho original removido','whole_page'=>false];$title=(string)$meta['title'];$context=!empty($meta['whole_page'])?'Página':'Anotação anterior';
            $panel.='<article class="student-note-item is-legacy"><header><span>'.h($context).'</span><strong>'.h($title).'</strong></header>'
                .'<form method="post" action="/aluno/material-anotacao.php">'.student_notes_hidden_fields((int)$page['id'],(string)$key,$returnTo)
                .'<textarea name="body" rows="5" maxlength="5000" aria-label="Anotação sobre '.h($title).'">'.h((string)$note['body']).'</textarea>'
                .'<div class="student-material-note-actions"><button class="button" type="submit">Salvar</button><button class="student-material-note-remove" type="submit" name="remove" value="1">Remover</button></div></form></article>';
        }
        $panel.='</div>';
    }else $panel.='<p class="student-notes-empty">Você ainda não fez anotações nesta página.</p>';

    $panel.='<details class="student-note-new" data-student-note-new><summary>Nova anotação geral</summary><form method="post" action="/aluno/material-anotacao.php">'
        .'<input type="hidden" name="_csrf" value="'.h(csrf_token('student-material-note')).'">'
        .'<input type="hidden" name="page_id" value="'.(int)$page['id'].'">'
        .'<input type="hidden" name="return_to" value="'.h($returnTo).'">'
        .'<input type="hidden" name="annotation_action" value="create_page">'
        .'<input type="hidden" name="source_page_revision" value="'.h($revision).'">'
        .'<label>Anotação da página<textarea name="body" rows="4" maxlength="5000" placeholder="Para uma ideia que não pertence a um trecho específico."></textarea></label>'
        .'<button class="button" type="submit">Salvar anotação da página</button>'
        .$questionComposer(false)
        .'</form></details>';

    $client=[];foreach($annotations as $note)$client[]=['id'=>(int)$note['id'],'anchorType'=>(string)$note['anchor_type'],'blockKey'=>(string)$note['block_key'],'sectionKey'=>(string)$note['section_key'],'exact'=>(string)$note['quote_exact'],'prefix'=>(string)$note['quote_prefix'],'suffix'=>(string)$note['quote_suffix'],'start'=>$note['start_offset']===null?null:(int)$note['start_offset'],'end'=>$note['end_offset']===null?null:(int)$note['end_offset'],'sourceBlockHash'=>(string)$note['source_block_hash']];
    $json=json_encode($client,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?:'[]';
    $panel.='<script type="application/json" data-student-annotation-data>'.$json.'</script></div></details>';
    $document['html']=$entry.$out.$panel;
    return $document;
}