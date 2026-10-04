<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasForms=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_forms'")->fetchColumn();
    if(!$hasForms||!function_exists('workshop_registration_content_blocks'))return;

    $defaults=workshop_registration_content_blocks();
    $defaultById=[];
    foreach($defaults as $block)$defaultById[(string)$block['id']]=$block;

    $forms=$db->query("SELECT * FROM cms_forms WHERE locale='pt-BR' AND form_key='registration' AND status!='archived'")->fetchAll(PDO::FETCH_ASSOC);
    $now=gmdate('c');
    foreach($forms as $form){
        $updates=[];
        foreach(['draft_schema_json','published_schema_json'] as $column){
            $raw=(string)($form[$column]??'');
            if($raw==='')continue;
            $schema=json_decode($raw,true);
            if(!is_array($schema))continue;
            $schema=cms_validate_form_schema($schema);
            $existing=is_array($schema['settings']['contentBlocks']??null)?$schema['settings']['contentBlocks']:[];
            $byId=[];
            foreach($existing as $block)if(is_array($block)&&isset($block['id']))$byId[(string)$block['id']]=$block;

            $merged=[];
            foreach($defaults as $default){
                $id=(string)$default['id'];
                $block=$byId[$id]??$default;
                if(in_array($id,['payment_pix','payment_card'],true)){
                    $html=(string)($block['html']??'');
                    if($html!==''&&!str_contains($html,'registration-payment-source')){
                        $block['html']='<div class="registration-payment-source">'.$html.'</div>';
                    }
                }
                $merged[]=$block;
                unset($byId[$id]);
            }
            foreach($existing as $block){
                if(!is_array($block))continue;
                $id=(string)($block['id']??'');
                if($id!==''&&isset($byId[$id])){
                    $merged[]=$block;
                    unset($byId[$id]);
                }
            }
            $schema['settings']['contentBlocks']=$merged;
            $schema=cms_validate_form_schema($schema);
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
};
