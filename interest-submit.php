<?php
require __DIR__.'/app/bootstrap.php'; security_headers(); require __DIR__.'/template/public.php';
$async=str_contains(strtolower($_SERVER['HTTP_ACCEPT']??''),'application/json');
function interest_async(array $payload,int $status=200):never{header('Content-Type: application/json; charset=UTF-8');http_response_code($status);echo json_encode($payload,JSON_UNESCAPED_UNICODE);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: /',true,303);exit;}
$locale=public_locale();$failure=public_message('form_failed',$locale);$summary=public_message('form_summary',$locale);$definition=interest_form_definition($locale);
if((int)($_SERVER['CONTENT_LENGTH']??0)>20000||!verify_csrf('interest',$_POST['_csrf']??null)||!empty($_POST['_website']??'')||!ctype_digit((string)($_POST['_started_at']??''))||time()-(int)$_POST['_started_at']<3){if($async)interest_async(['ok'=>false,'errors'=>['_form'=>[$failure]],'message'=>$failure],422);http_response_code(422);render_public_page($_POST,['_form'=>$failure]);exit;}
[$values,$errors]=validate_interest($_POST,$definition,$locale);
if($errors){if($async)interest_async(['ok'=>false,'errors'=>array_map(fn($error)=>[$error],$errors),'message'=>$summary],422);http_response_code(422);render_public_page($values,$errors);exit;}
try{save_interest(database(),$values,$definition,$config,$locale);$_SESSION['csrf']['interest']=null;if($async)interest_async(['ok'=>true,'confirmationHtml'=>public_confirmation_markup($locale),'publicUrl'=>'/'.rawurlencode((string)public_activity()['slug']).'/?lang='.rawurlencode(public_locale_query($locale))]);$query=http_build_query(['success'=>1,'lang'=>public_locale_query($locale)]);header('Location: /'.rawurlencode((string)public_activity()['slug']).'/?'.$query.'#interest',true,303);exit;}catch(Throwable){if($async)interest_async(['ok'=>false,'errors'=>['_form'=>[$failure]],'message'=>$failure],429);http_response_code(429);render_public_page($values,['_form'=>$failure]);}
