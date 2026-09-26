<?php
declare(strict_types=1);

const CMS_FORM_PURPOSES=['common','interest','registration','enrollment'];

function cms_form_purpose_normalize(string $purpose): string {
    $purpose=strtolower(trim($purpose));
    return in_array($purpose,CMS_FORM_PURPOSES,true)?$purpose:'common';
}

function cms_form_purpose_label(string $purpose): string {
    return match(cms_form_purpose_normalize($purpose)){
        'interest'=>'Interesse',
        'registration'=>'Inscrição',
        'enrollment'=>'Inscrição que gera matrícula',
        default=>'Comum',
    };
}

function cms_form_purpose(array $form): string {
    return cms_form_purpose_normalize((string)($form['purpose']??'common'));
}

function cms_form_purpose_column_exists(PDO $db): bool {
    foreach($db->query('PRAGMA table_info(cms_forms)')->fetchAll(PDO::FETCH_ASSOC) as $column){
        if((string)($column['name']??'')==='purpose')return true;
    }
    return false;
}

function cms_form_set_purpose(PDO $db,int $id,string $purpose): array {
    $form=cms_form_by_id($db,$id)??throw new RuntimeException('form_not_found');
    $purpose=cms_form_purpose_normalize($purpose);
    if(!cms_form_purpose_column_exists($db)){
        $form['purpose']=$purpose;
        return $form;
    }
    $db->prepare('UPDATE cms_forms SET purpose=?,updated_at=? WHERE id=?')->execute([$purpose,utc_now(),$id]);
    return cms_form_by_id($db,$id)??throw new RuntimeException('form_not_found');
}
