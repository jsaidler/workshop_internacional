<?php
declare(strict_types=1);

const ANALYTICS_EVENT_TYPES=['pageview','form_start','cta_click','form_submit'];

function analytics_session_hash(): string {
    $config=app_config();
    $session=session_id();
    if($session==='')return '';
    return hash_hmac('sha256',$session,(string)$config['app_secret']);
}
function analytics_is_bot(?string $userAgent=null): bool {
    $ua=strtolower($userAgent??(string)($_SERVER['HTTP_USER_AGENT']??''));
    if($ua==='')return false;
    return (bool)preg_match('~bot|crawler|spider|slurp|bingpreview|facebookexternalhit|whatsapp|telegrambot|discordbot|headlesschrome|lighthouse~i',$ua);
}
function analytics_device_type(?string $userAgent=null): string {
    $ua=strtolower($userAgent??(string)($_SERVER['HTTP_USER_AGENT']??''));
    if(preg_match('~ipad|tablet|kindle|silk|playbook~i',$ua))return 'tablet';
    if(preg_match('~mobi|iphone|ipod|android.*mobile|windows phone~i',$ua))return 'mobile';
    return 'desktop';
}
function analytics_request_path(): string {
    $uri=(string)($_SERVER['REQUEST_URI']??'/');
    $path=parse_url($uri,PHP_URL_PATH);
    return is_string($path)&&$path!==''?substr($path,0,500):'/';
}
function analytics_referrer_host(): string {
    $ref=trim((string)($_SERVER['HTTP_REFERER']??''));
    if($ref==='')return '';
    $host=parse_url($ref,PHP_URL_HOST);
    if(!is_string($host)||$host==='')return '';
    $current=strtolower((string)($_SERVER['HTTP_HOST']??''));
    $current=preg_replace('/:\d+$/','',$current)??$current;
    return strtolower($host)===$current?'':substr(strtolower($host),0,255);
}
function analytics_utm_value(string $key): string {
    $value=is_string($_GET[$key]??null)?trim((string)$_GET[$key]):'';
    return substr($value,0,255);
}
function analytics_record_event(PDO $db,array $event,bool $dedupe=false): bool {
    $type=(string)($event['event_type']??'');
    if(!in_array($type,ANALYTICS_EVENT_TYPES,true)||analytics_is_bot())return false;
    $activityId=(int)($event['activity_id']??0);if($activityId<=0)return false;
    $session=(string)($event['session_hash']??analytics_session_hash());if($session==='')return false;
    $pageId=isset($event['page_id'])&&$event['page_id']!==null?(int)$event['page_id']:null;
    $formId=isset($event['form_id'])&&$event['form_id']!==null?(int)$event['form_id']:null;
    $submissionId=isset($event['submission_id'])&&$event['submission_id']!==null?(int)$event['submission_id']:null;
    if($dedupe){
        $sql='SELECT 1 FROM analytics_events WHERE activity_id=? AND event_type=? AND session_hash=?';$args=[$activityId,$type,$session];
        if($pageId!==null){$sql.=' AND page_id=?';$args[]=$pageId;}else $sql.=' AND page_id IS NULL';
        if($formId!==null){$sql.=' AND form_id=?';$args[]=$formId;}else $sql.=' AND form_id IS NULL';
        $sql.=' LIMIT 1';$q=$db->prepare($sql);$q->execute($args);if($q->fetchColumn())return false;
    }
    $meta=is_array($event['meta']??null)?$event['meta']:[];
    $q=$db->prepare('INSERT INTO analytics_events(activity_id,page_id,form_id,submission_id,event_type,session_hash,locale,path,referrer_host,utm_source,utm_medium,utm_campaign,device_type,meta_json,created_at)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $q->execute([
        $activityId,$pageId,$formId,$submissionId,$type,$session,
        substr((string)($event['locale']??''),0,32),
        substr((string)($event['path']??analytics_request_path()),0,500),
        substr((string)($event['referrer_host']??analytics_referrer_host()),0,255),
        substr((string)($event['utm_source']??analytics_utm_value('utm_source')),0,255),
        substr((string)($event['utm_medium']??analytics_utm_value('utm_medium')),0,255),
        substr((string)($event['utm_campaign']??analytics_utm_value('utm_campaign')),0,255),
        substr((string)($event['device_type']??analytics_device_type()),0,32),
        json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
        (string)($event['created_at']??utc_now()),
    ]);
    return true;
}
function analytics_record_pageview(PDO $db,array $activity,array $page,string $locale): bool {
    return analytics_record_event($db,[
        'activity_id'=>(int)$activity['id'],
        'page_id'=>(int)$page['id'],
        'event_type'=>'pageview',
        'locale'=>$locale,
    ]);
}
function analytics_date_floor(int $days): string {
    $days=max(1,min(3650,$days));
    return gmdate('c',time()-(($days-1)*86400));
}
function analytics_percent(int|float $numerator,int|float $denominator): float {
    return $denominator>0?round(($numerator/$denominator)*100,1):0.0;
}
function analytics_dashboard(PDO $db,int $activityId,int $days=30): array {
    $days=max(1,min(3650,$days));$from=analytics_date_floor($days);
    $collection=$db->prepare('SELECT MIN(created_at) FROM analytics_events WHERE activity_id=?');$collection->execute([$activityId]);$collectionStart=$collection->fetchColumn()?:null;
    $counts=['views'=>0,'sessions'=>0,'cta_clicks'=>0,'form_starts'=>0,'submissions'=>0,'converted'=>0];
    $q=$db->prepare("SELECT
        COALESCE(SUM(CASE WHEN event_type='pageview' THEN 1 ELSE 0 END),0) views,
        COUNT(DISTINCT CASE WHEN event_type='pageview' THEN session_hash END) sessions,
        COALESCE(SUM(CASE WHEN event_type='cta_click' THEN 1 ELSE 0 END),0) cta_clicks,
        COUNT(DISTINCT CASE WHEN event_type='form_start' THEN session_hash||':'||COALESCE(form_id,0) END) form_starts,
        COALESCE(SUM(CASE WHEN event_type='form_submit' THEN 1 ELSE 0 END),0) submissions
        FROM analytics_events WHERE activity_id=? AND created_at>=?");
    $q->execute([$activityId,$from]);$row=$q->fetch();if(is_array($row))foreach(['views','sessions','cta_clicks','form_starts','submissions'] as $key)$counts[$key]=(int)($row[$key]??0);
    $q=$db->prepare("SELECT COUNT(*) FROM analytics_events e JOIN cms_form_submissions s ON s.id=e.submission_id WHERE e.activity_id=? AND e.event_type='form_submit' AND e.created_at>=? AND s.status='converted'");$q->execute([$activityId,$from]);$counts['converted']=(int)$q->fetchColumn();

    $daily=[];$q=$db->prepare("SELECT substr(created_at,1,10) day,
        SUM(CASE WHEN event_type='pageview' THEN 1 ELSE 0 END) views,
        COUNT(DISTINCT CASE WHEN event_type='pageview' THEN session_hash END) sessions,
        SUM(CASE WHEN event_type='form_submit' THEN 1 ELSE 0 END) submissions
        FROM analytics_events WHERE activity_id=? AND created_at>=? GROUP BY substr(created_at,1,10) ORDER BY day");$q->execute([$activityId,$from]);$daily=$q->fetchAll();

    $pages=[];$q=$db->prepare("SELECT COALESCE(p.title,'Página removida') title,e.page_id,
        SUM(CASE WHEN e.event_type='pageview' THEN 1 ELSE 0 END) views,
        COUNT(DISTINCT CASE WHEN e.event_type='pageview' THEN e.session_hash END) sessions,
        SUM(CASE WHEN e.event_type='form_submit' THEN 1 ELSE 0 END) submissions
        FROM analytics_events e LEFT JOIN cms_pages p ON p.id=e.page_id
        WHERE e.activity_id=? AND e.created_at>=? GROUP BY e.page_id,p.title ORDER BY views DESC,submissions DESC LIMIT 12");$q->execute([$activityId,$from]);$pages=$q->fetchAll();

    $sources=[];$q=$db->prepare("SELECT CASE WHEN COALESCE(e.utm_source,'')<>'' THEN e.utm_source WHEN COALESCE(e.referrer_host,'')<>'' THEN e.referrer_host ELSE 'Direto' END source,
        COALESCE(e.utm_medium,'') medium,COALESCE(e.utm_campaign,'') campaign,COUNT(*) sessions
        FROM analytics_events e JOIN (
            SELECT session_hash,MIN(id) first_id FROM analytics_events WHERE activity_id=? AND created_at>=? AND event_type='pageview' GROUP BY session_hash
        ) first ON first.first_id=e.id GROUP BY source,medium,campaign ORDER BY sessions DESC LIMIT 12");$q->execute([$activityId,$from]);$sources=$q->fetchAll();

    $devices=[];$q=$db->prepare("SELECT COALESCE(NULLIF(e.device_type,''),'desktop') device,COUNT(*) sessions FROM analytics_events e JOIN (
        SELECT session_hash,MIN(id) first_id FROM analytics_events WHERE activity_id=? AND created_at>=? AND event_type='pageview' GROUP BY session_hash
    ) first ON first.first_id=e.id GROUP BY device ORDER BY sessions DESC");$q->execute([$activityId,$from]);$devices=$q->fetchAll();

    $locales=[];$q=$db->prepare("SELECT COALESCE(NULLIF(e.locale,''),'—') locale,COUNT(*) sessions FROM analytics_events e JOIN (
        SELECT session_hash,MIN(id) first_id FROM analytics_events WHERE activity_id=? AND created_at>=? AND event_type='pageview' GROUP BY session_hash
    ) first ON first.first_id=e.id GROUP BY locale ORDER BY sessions DESC");$q->execute([$activityId,$from]);$locales=$q->fetchAll();

    return [
        'days'=>$days,'from'=>$from,'collectionStart'=>$collectionStart,'counts'=>$counts,
        'rates'=>[
            'visitToStart'=>analytics_percent($counts['form_starts'],$counts['sessions']),
            'visitToSubmit'=>analytics_percent($counts['submissions'],$counts['sessions']),
            'startToSubmit'=>analytics_percent($counts['submissions'],$counts['form_starts']),
            'submitToConverted'=>analytics_percent($counts['converted'],$counts['submissions']),
        ],
        'daily'=>$daily,'pages'=>$pages,'sources'=>$sources,'devices'=>$devices,'locales'=>$locales,
    ];
}
