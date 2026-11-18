<?php
declare(strict_types=1);
function interest_form_definition(?string $locale=null): array{$locale??=PUBLIC_LOCALE_EN;$form=localize_interest_form(public_content()['forms']['interest-form'],$locale);$activity=public_activity();return workshop_price_apply_to_form($form,$locale,workshop_price(database(),(int)$activity['id'],$locale));}
function form_field_by_id(array $f):array{return array_column($f['fields'],null,'id');}
function interest_form_groups(array $form,string $locale=PUBLIC_LOCALE_EN): array {
    $labels=$locale===PUBLIC_LOCALE_PT_BR?['contact'=>'Sobre você','experience'=>'Experiência e disponibilidade','commercial'=>'Interesse no workshop']:['contact'=>'Contact','experience'=>'Experience and availability','commercial'=>'Commercial interest'];
    $legacy=['contact'=>['label'=>$labels['contact'],'fields'=>['name','email','country','city','timezone']],'experience'=>['label'=>$labels['experience'],'fields'=>['experience','preferred_days','preferred_time']],'commercial'=>['label'=>$labels['commercial'],'fields'=>['price_response','main_interest']]];
    $groups=[];
    foreach ($form['fields'] as $field) {
        $id=$field['group']??'';
        if (!is_string($id)||$id==='') foreach ($legacy as $candidate=>$definition) if (in_array($field['id'],$definition['fields'],true)) {$id=$candidate;break;}
        if (!is_string($id)||$id==='') $id='ungrouped';
        $groups[$id]['label']=$legacy[$id]['label']??($id==='ungrouped'?'':ucwords(str_replace('_',' ',$id)));
        $groups[$id]['fields'][]=$field;
    }
    return $groups;
}
function h(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
