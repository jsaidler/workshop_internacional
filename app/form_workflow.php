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
    return ['successMode'=>$mode,'redirectPath'=>$redirect,'notificationEmail'=>$email,'notificationSubject'=>$subject];
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
    $rawEmail=trim((string)($input['notification_email']??''));if($rawEmail!==''&&$workflow['notificationEmail']==='')throw new RuntimeException('notification_email_invalid');
    $schema['settings']['workflow']=$workflow;return $schema;
}

function cms_form_conditions(array $schema): array {
    $fields=[];foreach($schema['fields']??[] as $field)if(is_array($field)&&isset($field['id']))$fields[(string)$field['id']]=true;
    $raw=is_array($schema['settings']['conditions']??null)?$schema['settings']['conditions']:[];$out=[];
    foreach($raw as $target=>$rule){
        $target=(string)$target;if(!isset($fields[$target])||!is_array($rule))continue;$source=(string)($rule['source']??'');if($source===''||$source===$target||!isset($fields[$source]))continue;
        $operator=(string)($rule['operator']??'equals');if(!in_array($operator,['equals','not_equals','contains','checked','not_checked'],true))$operator='equals';
        $value=trim((string)($rule['value']??''));if(in_array($operator,['checked','not_checked'],true))$value='';
        $out[$target]=['source'=>$source,'operator'=>$operator,'value'=>$value];
    }
    return $out;
}
function cms_form_conditions_update(array $schema,array $input): array {
    $schema['settings']=is_array($schema['settings']??null)?$schema['settings']:[];$sources=is_array($input['condition_source']??null)?$input['condition_source']:[];$operators=is_array($input['condition_operator']??null)?$input['condition_operator']:[];$values=is_array($input['condition_value']??null)?$input['condition_value']:[];$conditions=[];
    foreach($schema['fields']??[] as $field){if(!is_array($field)||!isset($field['id']))continue;$target=(string)$field['id'];$source=trim((string)($sources[$target]??''));if($source==='')continue;$conditions[$target]=['source'=>$source,'operator'=>(string)($operators[$target]??'equals'),'value'=>(string)($values[$target]??'')];}
    $candidate=$schema;$candidate['settings']['conditions']=$conditions;$schema['settings']['conditions']=cms_form_conditions($candidate);return $schema;
}
function cms_form_validate_conditional_submission(array $schema,array $input,string $locale): array {
    [$values,$errors]=cms_form_validate_submission($schema,$input,$locale);foreach(cms_form_conditions($schema) as $target=>$rule)if(!cms_form_condition_matches($rule,$values)){unset($errors[$target]);$values[$target]=is_array($values[$target]??null)?[]:'';}return [$values,$errors];
}
function cms_form_apply_conditions_markup(string $html,array $schema,array $values=[]): string {
    $conditions=cms_form_conditions($schema);if(!$conditions||$html==='')return $html;$previous=libxml_use_internal_errors(true);$dom=new DOMDocument('1.0','UTF-8');$dom->loadHTML('<?xml encoding="utf-8" ?><div id="condition-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);$xpath=new DOMXPath($dom);
    foreach($conditions as $target=>$rule){$query='//*[@name="'.$target.'" or @name="'.$target.'[]"]';$input=$xpath->query($query)?->item(0);if(!$input instanceof DOMElement)continue;$wrapper=$input;while($wrapper->parentNode instanceof DOMElement){$classes=' '.$wrapper->getAttribute('class').' ';if(str_contains($classes,' cms-field ')||str_contains($classes,' cms-choice-group ')||str_contains($classes,' cms-consent '))break;$wrapper=$wrapper->parentNode;}
        if(!$wrapper instanceof DOMElement)continue;$wrapper->setAttribute('data-cms-condition-field',$rule['source']);$wrapper->setAttribute('data-cms-condition-operator',$rule['operator']);$wrapper->setAttribute('data-cms-condition-value',$rule['value']);if(!cms_form_condition_matches($rule,$values))$wrapper->setAttribute('hidden','hidden');
    }
    $root=$dom->getElementById('condition-root');$out='';if($root)foreach(iterator_to_array($root->childNodes) as $child)$out.=$dom->saveHTML($child);libxml_clear_errors();libxml_use_internal_errors($previous);return $out;
}

function cms_form_notification_body(array $form,array $schema,array $values,string $locale): string {
    $labels=[];foreach($schema['fields']??[] as $field)if(is_array($field)&&isset($field['id']))$labels[(string)$field['id']]=(string)($field['label']??$field['id']);
    $lines=[];$lines[]=$locale===PUBLIC_LOCALE_PT_BR?'Nova resposta recebida pelo site.':'New website form response.';$lines[]='';
    foreach($values as $id=>$value){$label=$labels[(string)$id]??(string)$id;$display=is_array($value)?implode(', ',array_map('strval',$value)):(string)$value;$lines[]=$label.': '.$display;}
    $lines[]='';$lines[]='Form: '.(string)($form['title']??'');$lines[]='Received: '.gmdate('c');return implode("\n",$lines);
}
function cms_form_notify(array $form,array $schema,array $values,string $locale): bool {
    $workflow=cms_form_workflow($schema);$recipient=$workflow['notificationEmail'];if($recipient==='')return true;$subject=$workflow['notificationSubject']!==''?$workflow['notificationSubject']:($locale===PUBLIC_LOCALE_PT_BR?'Nova inscrição pelo site':'New website form response');$body=cms_form_notification_body($form,$schema,$values,$locale);$ok=false;try{$ok=@mail($recipient,$subject,$body,"Content-Type: text/plain; charset=UTF-8\r\n");}catch(Throwable){$ok=false;}if(!$ok)error_log('CMS form notification could not be sent for form '.(string)($form['id']??''));return $ok;
}
