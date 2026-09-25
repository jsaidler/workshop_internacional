<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();
require_admin();

if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

$db=database();
$state=admin_activity_resolution($db);
$activity=$state['activity']??null;
if(!$activity){header('Location: /admin/activities.php',true,303);exit;}
$activityId=(int)$activity['id'];
$redirect='/admin/student-area.php?'.http_build_query(['activity'=>$activityId,'view'=>'students','import'=>1]);

if(!verify_csrf('student-area',$_POST['_csrf']??null)){
    http_response_code(403);
    exit('Invalid request');
}

try{
    $cohortId=(int)($_POST['cohort_id']??0);
    $file=$_FILES['spreadsheet']??null;
    if(!is_array($file))throw new RuntimeException('Selecione um arquivo CSV.');
    $name=trim((string)($file['name']??''));
    if(strtolower(pathinfo($name,PATHINFO_EXTENSION))!=='csv')throw new RuntimeException('Envie um arquivo CSV.');
    $result=student_import_historical_students($db,$activityId,$cohortId,$file);
    $_SESSION['admin_notice']='Importação concluída: '.$result['imported'].' nova(s) conta(s), '.$result['existing'].' conta(s) já existente(s), '.$result['errors'].' linha(s) com erro.';
    $redirect='/admin/student-area.php?'.http_build_query(['activity'=>$activityId,'view'=>'students','import'=>1,'batch'=>(int)$result['batch_id']]);
}catch(Throwable $e){
    $_SESSION['admin_notice']='Não foi possível concluir: '.$e->getMessage();
}

header('Location: '.$redirect,true,303);
exit;
