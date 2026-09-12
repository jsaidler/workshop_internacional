<?php
declare(strict_types=1);

function cms_form_workflow(array $schema): array {
    $raw=is_array($schema['settings']['workflow']??null)?$schema['settings']['workflow']:[];
    $mode=in_array($raw['successMode']??'inline',['inline','redirect'],true)?(string)$raw['successMode']:'inline';
    $redirect=trim((string)($raw['redirectPath']??''));
    if($redirect!==''&&(!str_starts_with($redirect,'/')||str_starts_with($redirect,'//'))) $redirect='';
    $email=trim((string)($raw['notificationEmail']??''));
    if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))$email='';
    $subject=preg_replace('/[\r\n]+/',' ',trim((string)($raw['notificationSubject']??'')))??'';
    if(function_exists('mb_substr'))$subject=mb_substr($subject,0,180,'UTF-8');else $subject=substr($subject,0,180);
    return [
        'successMode'=>$mode,
        'redirectPath'=>$redirect,
        'notificationEmail'=>$email,
        'notificationSubject'=>$subject,
    ];
}

function cms_form_workflow_update(array $schema,array $input): array {
    $schema['settings']=is_array($schema['settings']??null)?$schema['settings']:[];
    $candidate=['settings'=>['workflow'=>[
        'successMode'=>(string)($input['success_mode']??'inline'),
        'redirectPath'=>(string)($input['redirect_path']??''),
        'notificationEmail'=>(string)($input['notification_email']??''),
        'notificationSubject'=>(string)($input['notification_subject']??''),
    ]]];
    $workflow=cms_form_workflow($candidate);
    if(($input['success_mode']??'inline')==='redirect'&&$workflow['redirectPath']==='')throw new RuntimeException('redirect_path_invalid');
    $rawEmail=trim((string)($input['notification_email']??''));
    if($rawEmail!==''&&$workflow['notificationEmail']==='')throw new RuntimeException('notification_email_invalid');
    $schema['settings']['workflow']=$workflow;
    return $schema;
}

function cms_form_notification_body(array $form,array $schema,array $values,string $locale): string {
    $labels=[];foreach($schema['fields']??[] as $field)if(is_array($field)&&isset($field['id']))$labels[(string)$field['id']]=(string)($field['label']??$field['id']);
    $lines=[];$lines[]=$locale===PUBLIC_LOCALE_PT_BR?'Nova resposta recebida pelo site.':'New website form response.';$lines[]='';
    foreach($values as $id=>$value){$label=$labels[(string)$id]??(string)$id;$display=is_array($value)?implode(', ',array_map('strval',$value)):(string)$value;$lines[]=$label.': '.$display;}
    $lines[]='';$lines[]='Form: '.(string)($form['title']??'');$lines[]='Received: '.gmdate('c');
    return implode("\n",$lines);
}

function cms_form_notify(array $form,array $schema,array $values,string $locale): bool {
    $workflow=cms_form_workflow($schema);$recipient=$workflow['notificationEmail'];if($recipient==='')return true;
    $subject=$workflow['notificationSubject']!==''?$workflow['notificationSubject']:($locale===PUBLIC_LOCALE_PT_BR?'Nova inscrição pelo site':'New website form response');
    $body=cms_form_notification_body($form,$schema,$values,$locale);
    $ok=false;try{$ok=@mail($recipient,$subject,$body,"Content-Type: text/plain; charset=UTF-8\r\n");}catch(Throwable){$ok=false;}
    if(!$ok)error_log('CMS form notification could not be sent for form '.(string)($form['id']??''));
    return $ok;
}
