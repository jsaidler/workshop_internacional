<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
security_headers();

if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);header('Allow: POST');exit;}
$fetchSite=strtolower((string)($_SERVER['HTTP_SEC_FETCH_SITE']??''));
if($fetchSite!==''&&!in_array($fetchSite,['same-origin','same-site','none'],true)){http_response_code(403);exit;}
$raw=file_get_contents('php://input');
if(!is_string($raw)||strlen($raw)>8192){http_response_code(400);exit;}
$input=json_decode($raw,true);
if(!is_array($input)){http_response_code(400);exit;}
$type=(string)($input['event']??'');
if(!in_array($type,['pageview','form_start','cta_click'],true)){http_response_code(400);exit;}

$db=database();
$pageId=(int)($input['pageId']??0);
$page=$pageId>0?cms_page_by_id($db,$pageId):null;
if(!$page||$page['status']==='archived'){http_response_code(404);exit;}
$activityId=(int)$page['activity_id'];
$formId=(int)($input['formId']??0);
if($formId>0){
    $form=cms_form_by_id($db,$formId);
    if(!$form||(int)$form['activity_id']!==$activityId||$form['status']==='archived'){http_response_code(400);exit;}
}else $formId=0;
$destination=trim((string)($input['destination']??''));
if(strlen($destination)>500)$destination=substr($destination,0,500);

analytics_record_event($db,[
    'activity_id'=>$activityId,
    'page_id'=>$pageId,
    'form_id'=>$formId>0?$formId:null,
    'event_type'=>$type,
    'locale'=>(string)$page['locale'],
    'path'=>substr((string)($input['path']??analytics_request_path()),0,500),
    'referrer_host'=>substr((string)($input['referrerHost']??''),0,255),
    'utm_source'=>substr((string)($input['utmSource']??''),0,255),
    'utm_medium'=>substr((string)($input['utmMedium']??''),0,255),
    'utm_campaign'=>substr((string)($input['utmCampaign']??''),0,255),
    'meta'=>$destination!==''?['destination'=>$destination]:[],
],$type==='form_start');
http_response_code(204);
