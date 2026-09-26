<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
security_headers();

if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);header('Allow: POST');exit;}
if(!verify_csrf('cms-form',$_POST['_csrf']??null)){http_response_code(400);exit('Invalid request');}
if(trim((string)($_POST['_website']??''))!==''){http_response_code(204);exit;}
$started=(int)($_POST['_started_at']??0);if($started>0&&time()-$started<1){http_response_code(429);exit('Please try again.');}

$db=database();
$form=cms_form_by_uuid($db,(string)($_POST['_form_uuid']??''));
$page=cms_page_by_id($db,(int)($_POST['_page_id']??0));
if(!$form||!$page||(int)$form['activity_id']!==(int)$page['activity_id']){http_response_code(404);exit('Form not found');}
$locale=normalize_public_locale((string)($_POST['_locale']??$page['locale']))??(string)$page['locale'];
if($locale!==$form['locale']||$locale!==$page['locale']){http_response_code(400);exit('Invalid locale');}
$schema=cms_form_schema($form,true);$workflow=cms_form_workflow($schema);
[$values,$errors]=cms_form_validate_conditional_submission($schema,$_POST,$locale);
$return=(string)($_POST['_return']??'');
if($return===''||!str_starts_with($return,'/')){$activity=activity_by_id($db,(int)$page['activity_id']);$return=$activity?cms_page_url($activity,$page,$locale):'/';}
$parts=parse_url($return);$returnPath=is_array($parts)&&isset($parts['path'])?(string)$parts['path']:'/';$returnQuery=is_array($parts)&&isset($parts['query'])?'?'.(string)$parts['query']:'';$return=$returnPath.$returnQuery;
if($errors){$_SESSION['cms_form_flash'][$form['form_uuid']]=['values'=>$values,'errors'=>$errors];header('Location: '.$return.'#form-'.(int)$form['id'],true,303);exit;}
try{
    $submissionUuid=cms_submission_save($db,$form,$schema,$values,(int)$page['id'],$locale,$return);
    try{
        $submissionIdQuery=$db->prepare('SELECT id FROM cms_form_submissions WHERE submission_uuid=?');
        $submissionIdQuery->execute([$submissionUuid]);
        $submissionId=(int)$submissionIdQuery->fetchColumn();
        analytics_record_event($db,[
            'activity_id'=>(int)$form['activity_id'],
            'page_id'=>(int)$page['id'],
            'form_id'=>(int)$form['id'],
            'submission_id'=>$submissionId>0?$submissionId:null,
            'event_type'=>'form_submit',
            'locale'=>$locale,
            'path'=>$returnPath,
            'meta'=>['form'=>(string)$form['title']],
        ]);
    }catch(Throwable $analyticsError){error_log('Analytics form submission failed: '.$analyticsError->getMessage());}
    cms_form_notify($form,$schema,$values,$locale);
    if($workflow['successMode']==='redirect'&&$workflow['redirectPath']!==''){header('Location: '.$workflow['redirectPath'],true,303);exit;}
    $_SESSION['cms_form_flash'][$form['form_uuid']]=['success'=>true,'values'=>cms_form_success_context($schema,$values)];
    header('Location: '.$return.'#form-'.(int)$form['id'],true,303);exit;
}catch(RuntimeException $error){
    if(str_contains(strtolower($error->getMessage()),'too many')){http_response_code(429);exit($locale===PUBLIC_LOCALE_PT_BR?'Muitas tentativas. Tente novamente mais tarde.':'Too many attempts. Please try again later.');}
    throw $error;
}
