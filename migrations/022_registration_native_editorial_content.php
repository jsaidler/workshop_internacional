<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasForms=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_forms'")->fetchColumn();
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasForms||!$hasPages||!function_exists('workshop_registration_schema_with_content'))return;

    $now=gmdate('c');
    $forms=$db->query("SELECT * FROM cms_forms WHERE locale='pt-BR' AND form_key='registration' AND status!='archived'")->fetchAll(PDO::FETCH_ASSOC);
    foreach($forms as $form){
        $updates=[];
        foreach(['draft_schema_json','published_schema_json'] as $column){
            $raw=(string)($form[$column]??'');
            if($raw==='')continue;
            $schema=json_decode($raw,true);
            if(!is_array($schema))continue;
            $existing=$schema['settings']['contentBlocks']??null;
            if(!is_array($existing)||count($existing)===0)$schema=workshop_registration_schema_with_content($schema);
            else $schema=cms_validate_form_schema($schema);
            $updates[$column]=json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        }
        if(!$updates)continue;
        $revision=max((int)$form['draft_revision'],(int)($form['published_revision']??0))+1;
        $sets=[];$args=[];
        foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
        $sets[]='draft_revision=?';$args[]=$revision;
        $sets[]='published_revision=?';$args[]=$revision;
        $sets[]='draft_updated_at=?';$args[]=$now;
        $sets[]='published_at=?';$args[]=$now;
        $sets[]='updated_at=?';$args[]=$now;
        $args[]=(int)$form['id'];
        $db->prepare('UPDATE cms_forms SET '.implode(',',$sets).' WHERE id=?')->execute($args);
    }

    $pages=$db->query("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='inscricao' AND status!='archived'")->fetchAll(PDO::FETCH_ASSOC);
    foreach($pages as $page){
        $updates=[];
        foreach(['draft_document_json','published_document_json'] as $column){
            $raw=(string)($page[$column]??'');
            if($raw==='')continue;
            $doc=json_decode($raw,true);
            if(!is_array($doc)||!is_string($doc['html']??null))continue;
            $doc['html']=workshop_registration_strip_legacy_page_content($doc['html']);
            $updates[$column]=json_encode(cms_page_document($doc),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        }
        if(!$updates)continue;
        $revision=max((int)$page['draft_revision'],(int)($page['published_revision']??0))+1;
        $sets=[];$args=[];
        foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
        $sets[]='draft_revision=?';$args[]=$revision;
        $sets[]='published_revision=?';$args[]=$revision;
        $sets[]='draft_updated_at=?';$args[]=$now;
        $sets[]='published_at=?';$args[]=$now;
        $sets[]='updated_at=?';$args[]=$now;
        $args[]=(int)$page['id'];
        $db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
    }
};
