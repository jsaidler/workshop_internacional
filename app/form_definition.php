<?php
declare(strict_types=1);
function interest_form_definition(): array{return public_content()['forms']['interest-form'];}
function form_field_by_id(array $f):array{return array_column($f['fields'],null,'id');}
function interest_form_groups(array $form): array {
    $legacy=['contact'=>['label'=>'Contact','fields'=>['name','email','country','timezone']],'experience'=>['label'=>'Experience and availability','fields'=>['experience','preferred_days','preferred_time']],'commercial'=>['label'=>'Commercial interest','fields'=>['price_response','main_interest']]];
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
