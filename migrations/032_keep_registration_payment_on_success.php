<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasForms=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_forms'")->fetchColumn();
    if(!$hasForms)return;

    $forms=$db->query("SELECT * FROM cms_forms WHERE locale='pt-BR' AND form_key='registration' AND status!='archived'")->fetchAll(PDO::FETCH_ASSOC);
    $now=gmdate('c');
    $oldMessages=[
        '',
        'Recebi seus dados. Entrarei em contato com as próximas etapas da turma.',
        'Sua inscrição foi recebida. A vaga é confirmada somente após a confirmação do pagamento.',
    ];
    $newMessage='Recebi seus dados. Se ainda não concluiu o pagamento, use abaixo a forma escolhida. Se já pagou, basta aguardar a confirmação.';

    foreach($forms as $form){
        $updates=[];$changed=false;
        foreach(['draft_schema_json','published_schema_json'] as $column){
            $raw=(string)($form[$column]??'');if($raw==='')continue;
            $schema=json_decode($raw,true);if(!is_array($schema))continue;
            $schema['settings']=is_array($schema['settings']??null)?$schema['settings']:[];
            $blocks=is_array($schema['settings']['contentBlocks']??null)?$schema['settings']['contentBlocks']:[];
            foreach($blocks as &$block){
                if(!is_array($block))continue;
                if(in_array((string)($block['id']??''),['payment_pix','payment_card'],true)&&empty($block['showOnSuccess'])){
                    $block['showOnSuccess']=true;$changed=true;
                }
            }
            unset($block);
            $schema['settings']['contentBlocks']=$blocks;
            if(in_array((string)($schema['successMessage']??''),$oldMessages,true)){$schema['successMessage']=$newMessage;$changed=true;}
            $updates[$column]=json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        }
        if(!$updates||!$changed)continue;
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
