<?php
declare(strict_types=1);

const CMS_FORM_PURPOSES=['common','interest','registration','enrollment'];

function cms_form_purpose(array $form): string {
    $purpose=trim((string)($form['purpose']??''));
    if(in_array($purpose,CMS_FORM_PURPOSES,true))return $purpose;
    // Compatibility only for pre-migration rows/snapshots. New writes persist purpose explicitly.
    $legacy=(string)($form['form_key']??'');
    if($legacy==='registration')return 'enrollment';
    if($legacy==='interest')return 'interest';
    return 'common';
}
function cms_form_purpose_label(string $purpose): string {
    return match($purpose){
        'interest'=>'Interesse',
        'registration'=>'Inscrição',
        'enrollment'=>'Inscrição + matrícula',
        default=>'Comum',
    };
}
function cms_form_set_purpose(PDO $db,int $formId,string $purpose): array {
    if(!in_array($purpose,CMS_FORM_PURPOSES,true))throw new RuntimeException('Finalidade de formulário inválida.');
    $form=cms_form_by_id($db,$formId)??throw new RuntimeException('form_not_found');
    // Migration 013 seeds the registration page before migration 069 adds
    // cms_forms.purpose. During that one-time bootstrap step the schema cannot
    // persist this field; migration 069 backfills registration/interest by key.
    if(!array_key_exists('purpose',$form)){
        $form['purpose']=$purpose;
        return $form;
    }
    $db->prepare('UPDATE cms_forms SET purpose=?,updated_at=? WHERE id=?')->execute([$purpose,utc_now(),$formId]);
    $form['purpose']=$purpose;
    return $form;
}
