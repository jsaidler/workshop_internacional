<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();require_admin();
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);header('Allow: POST');exit('Method Not Allowed');}
$db=database();$state=admin_activity_resolution($db);$activity=$state['activity']??null;if(!$activity){header('Location: /admin/activities.php',true,303);exit;}$activityId=(int)$activity['id'];
$courseId=max(0,(int)($_POST['course_id']??$_GET['course']??0));$cohortId=max(0,(int)($_POST['cohort_id']??$_GET['cohort']??0));$redirect='/admin/courses.php?activity='.$activityId;
if(!verify_csrf('students-import',$_POST['_csrf']??null)){http_response_code(403);exit('Invalid request');}
try{
    $course=$courseId>0?course_by_id($db,$courseId):null;if(!$course||(int)$course['activity_id']!==$activityId)throw new RuntimeException('Curso inválido.');
    $cohort=admin_course_cohort($db,$courseId,$cohortId,false);if(!$cohort)throw new RuntimeException('Turma inválida para este curso.');
    $redirect='/admin/students.php?'.http_build_query(['activity'=>$activityId,'course'=>$courseId,'cohort'=>$cohortId,'import'=>1]);
    $file=$_FILES['spreadsheet']??null;if(!is_array($file))throw new RuntimeException('Selecione um arquivo CSV.');$name=trim((string)($file['name']??''));if(strtolower(pathinfo($name,PATHINFO_EXTENSION))!=='csv')throw new RuntimeException('Envie um arquivo CSV.');
    $result=student_import_historical_students($db,$activityId,$cohortId,$file);$_SESSION['admin_students_notice']='Importação concluída: '.$result['imported'].' nova(s) conta(s), '.$result['existing'].' conta(s) já existente(s), '.$result['errors'].' linha(s) com erro.';$redirect='/admin/students.php?'.http_build_query(['activity'=>$activityId,'course'=>$courseId,'cohort'=>$cohortId,'import'=>1,'batch'=>(int)$result['batch_id']]);
}catch(Throwable $e){$_SESSION['admin_students_notice']='Não foi possível concluir: '.$e->getMessage();}
header('Location: '.$redirect,true,303);exit;