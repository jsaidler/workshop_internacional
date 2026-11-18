<?php
declare(strict_types=1);

function interest_days(array $payload): array {
    $days=$payload['preferred_days']??null; $order=['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    if(!is_array($days)) return ['kind'=>'legacy','value'=>is_scalar($days)&&$days!==''?(string)$days:''];
    $valid=[]; foreach($days as $day) if(is_string($day)&&in_array($day,$order,true)&&!isset($valid[$day])) $valid[$day]=true;
    return count($valid)===count($days)?['kind'=>'canonical','value'=>array_values(array_filter($order,fn($day)=>isset($valid[$day])))]:['kind'=>'legacy','value'=>json_encode($days,JSON_UNESCAPED_UNICODE)];
}
function interest_day_labels(array $days, bool $compact=false): string { $labels=['monday'=>['Segunda-feira','Seg.'],'tuesday'=>['Terça-feira','Ter.'],'wednesday'=>['Quarta-feira','Qua.'],'thursday'=>['Quinta-feira','Qui.'],'friday'=>['Sexta-feira','Sex.'],'saturday'=>['Sábado','Sáb.'],'sunday'=>['Domingo','Dom.']]; return implode($compact?', ':' e ',array_map(fn($day)=>$labels[$day][$compact?1:0]??$day,$days)); }
function interest_date(string $date): string { return date('d/m/Y · H:i',strtotime($date)); }
function interest_status(array $row): string { return (($row['status']??'new')==='completed'?'Concluída':'Nova').' · '.interest_locale_label($row['workshop_locale']??'en'); }
function interest_price_label(mixed $value): string { return match($value){'yes'=>'Sim, consideraria participar.','maybe'=>'Talvez, depende das datas.','no'=>'Não, nesse preço.',default=>is_scalar($value)&&$value!==''?'Formato antigo: '.(string)$value:'Não informado'}; }
function interest_bool_label(mixed $value): string { return $value==='1'||$value===1||$value===true?'Consentimento registrado':'Não informado'; }
function interest_timezone_label(mixed $value,string $createdAt): string { if(!is_string($value)||$value==='') return 'Não informado'; if(!isset(interest_timezone_identifiers()[$value])) return 'Formato antigo: '.$value; try{$zone=new DateTimeZone($value);$date=new DateTimeImmutable($createdAt,new DateTimeZone('UTC'));$offset=$zone->getOffset($date);$hours=intdiv(abs($offset),3600);$minutes=intdiv(abs($offset)%3600,60);return $value.' · UTC'.($offset<0?'-':'+').sprintf('%02d:%02d',$hours,$minutes);}catch(Throwable){return 'Formato antigo: '.$value;} }
function interest_time_label(mixed $value): string { if(!is_string($value)||$value==='') return 'Não informado'; return preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/',$value)?$value:'Formato antigo: '.$value; }
function interest_text(mixed $value): string { return is_scalar($value)&&trim((string)$value)!==''?(string)$value:'Não informado'; }
function interest_locale_label(mixed $value): string { return normalize_public_locale($value)===PUBLIC_LOCALE_PT_BR?'PT-BR':'EN'; }
