<?php
declare(strict_types=1);

/**
 * Material annotations live in one notebook layer for the page.
 * They may still reference a section, but the editor is not injected into every
 * CMS section. This keeps study content readable and makes annotations a single
 * predictable destination.
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

function student_material_render_notebook(PDO $db,array $student,array $page,array $document): array {
    $html=(string)($document['html']??'');
    if($html===''||!str_contains($html,'data-cms-section'))return $document;

    $notes=student_material_notes_for_page($db,(int)$student['id'],(int)$page['id']);
    $previous=libxml_use_internal_errors(true);
    $dom=new DOMDocument('1.0','UTF-8');
    $dom->loadHTML('<?xml encoding="utf-8" ?><div id="student-notes-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
    $xpath=new DOMXPath($dom);$sections=[];$index=0;
    foreach(iterator_to_array($xpath->query('//*[@data-cms-section]')?:[]) as $section){
        if(!$section instanceof DOMElement)continue;
        $key=activity_slug($section->getAttribute('data-cms-section'));if($key==='')continue;
        $sections[$key]=['key'=>$key,'title'=>student_notes_section_title($xpath,$section,$index++)];
        $section->setAttribute('data-student-note-context',$key);
    }

    $root=$dom->getElementById('student-notes-root');$out='';
    if($root)foreach(iterator_to_array($root->childNodes) as $child)$out.=$dom->saveHTML($child);
    libxml_clear_errors();libxml_use_internal_errors($previous);
    if(!$sections){$document['html']=$out;return $document;}

    $returnTo=student_notes_return_url();$visibleNotes=[];
    foreach($sections as $key=>$section)if(isset($notes[$key]))$visibleNotes[$key]=$notes[$key];
    $freeSections=array_diff_key($sections,$visibleNotes);$count=count($visibleNotes);$open=isset($_GET['anotacoes']);

    $panel='<details class="student-notes-panel" id="anotacoes" data-student-notes-panel'.($open?' open':'').'>';
    $panel.='<summary><span>Anotações</span>'.($count>0?'<b>'.$count.'</b>':'').'</summary>';
    $panel.='<div class="student-notes-sheet"><header class="student-notes-head"><div><span>Material</span><h2>Suas anotações</h2></div><p>Um só lugar para consultar e editar o que você registrou nesta página.</p></header>';

    if($visibleNotes){
        $panel.='<div class="student-notes-list">';
        foreach($visibleNotes as $key=>$note){$title=(string)($sections[$key]['title']??$key);
            $panel.='<article class="student-note-item"><header><span>Trecho</span><strong>'.h($title).'</strong></header>';
            $panel.='<form method="post" action="/aluno/material-anotacao.php">'.student_notes_hidden_fields((int)$page['id'],$key,$returnTo)
                .'<textarea name="body" rows="5" maxlength="5000" aria-label="Anotação sobre '.h($title).'">'.h((string)$note['body']).'</textarea>'
                .'<div class="student-material-note-actions"><button class="button" type="submit">Salvar</button>'
                .'<button class="student-material-note-remove" type="submit" name="remove" value="1">Remover</button></div></form></article>';
        }
        $panel.='</div>';
    }else{
        $panel.='<p class="student-notes-empty">Você ainda não fez anotações nesta página.</p>';
    }

    if($freeSections){
        $panel.='<form class="student-note-new" method="post" action="/aluno/material-anotacao.php">'
            .'<input type="hidden" name="_csrf" value="'.h(csrf_token('student-material-note')).'">'
            .'<input type="hidden" name="page_id" value="'.(int)$page['id'].'">'
            .'<input type="hidden" name="return_to" value="'.h($returnTo).'">'
            .'<label>Vincular ao trecho<select name="section_key">';
        foreach($freeSections as $section)$panel.='<option value="'.h((string)$section['key']).'">'.h((string)$section['title']).'</option>';
        $panel.='</select></label><label>Anotação<textarea name="body" rows="5" maxlength="5000" placeholder="Registre aqui o que você quer guardar deste trecho."></textarea></label>'
            .'<button class="button" type="submit">Adicionar anotação</button></form>';
    }

    $panel.='</div></details>';
    $document['html']=$out.$panel;
    return $document;
}
