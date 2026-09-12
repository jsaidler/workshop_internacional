<?php
declare(strict_types=1);

function interest_trimmed(mixed $value, int $limit, array &$errors, string $id, bool $multiline=false,string $locale=PUBLIC_LOCALE_EN): string {
    if (is_array($value)) { $errors[$id]=public_validation_message('invalid',$locale); return ''; }
    $value=(string)$value;
    $value=$multiline?trim($value):trim(preg_replace('/\s+/u',' ',$value)??$value);
    $length=function_exists('mb_strlen')?mb_strlen($value,'UTF-8'):strlen($value);
    if ($length>$limit) { $errors[$id]=public_validation_message('long',$locale); }
    return $value;
}
function interest_timezone_identifiers(): array { static $identifiers; return $identifiers??=array_fill_keys(DateTimeZone::listIdentifiers(),true); }
function validate_interest(array $input,array $definition,string $locale=PUBLIC_LOCALE_EN):array {
    $fields=form_field_by_id($definition); $errors=[]; $clean=[];
    foreach($input as $key=>$_) if(!in_array($key,array_merge(array_keys($fields),['_csrf','_website','_started_at','_locale']),true)) $errors['_form']=public_message('form_failed',$locale);
    foreach($fields as $id=>$field) {
        $required=(bool)($field['required']??false); $raw=$input[$id]??'';
        if($id==='preferred_days') {
            if($raw===''||$raw===null) $days=[]; elseif(!is_array($raw)) { $errors[$id]=public_validation_message('valid_days',$locale); $days=[]; } else $days=$raw;
            $allowed=['monday','tuesday','wednesday','thursday','friday','saturday','sunday']; $seen=[];
            foreach($days as $day) { if(!is_string($day)||!in_array($day,$allowed,true)||isset($seen[$day])) { $errors[$id]=public_validation_message('valid_days',$locale); continue; } $seen[$day]=true; }
            $clean[$id]=array_values(array_filter($allowed,fn($day)=>isset($seen[$day])));
            if($required&&!$clean[$id]) $errors[$id]=public_validation_message('one_day',$locale);
            continue;
        }
        $value=interest_trimmed($raw,in_array($id,['experience','main_interest'],true)?4000:(in_array($id,['country','city'],true)?120:($id==='name'?160:255)),$errors,$id,in_array($id,['experience','main_interest'],true),$locale);
        if($required&&$value==='') { $errors[$id]=public_validation_message('required',$locale); $clean[$id]=$value; continue; }
        if($value==='') { $clean[$id]=''; continue; }
        if($id==='email') { if(!filter_var($value,FILTER_VALIDATE_EMAIL)) $errors[$id]=public_validation_message('email',$locale); $value=function_exists('mb_strtolower')?mb_strtolower($value,'UTF-8'):strtolower($value); }
        if($id==='timezone'&&!isset(interest_timezone_identifiers()[$value])) $errors[$id]=public_validation_message('timezone',$locale);
        if($id==='preferred_time'&&!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$value)) $errors[$id]=public_validation_message('time',$locale);
        if($id==='price_response'&&!in_array($value,array_column($field['options']??[],'value'),true)) $errors[$id]=public_validation_message('option',$locale);
        if($id==='consent'&&$value!=='1') $errors[$id]=public_validation_message('consent',$locale);
        $clean[$id]=$value;
    }
    return [$clean,$errors];
}
