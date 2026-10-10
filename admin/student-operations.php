<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();require_admin();

$legacy=(string)($_GET['view']??'cohorts');
$activityId=max(0,(int)($_GET['activity']??0));$courseId=max(0,(int)($_GET['course']??0));$cohortId=max(0,(int)($_GET['cohort']??0));
$args=[];if($activityId>0)$args['activity']=$activityId;if($courseId>0)$args['course']=$courseId;if($cohortId>0)$args['cohort']=$cohortId;
$path=match($legacy){
    'tests'=>'/admin/tests.php',
    'lessons'=>'/admin/lessons.php',
    'import','students'=>'/admin/students.php',
    default=>$courseId>0?'/admin/cohorts.php':'/admin/courses.php',
};
if($legacy==='import')$args['import']=1;
foreach(['batch','test','q','status'] as $key)if(isset($_GET[$key])&&$_GET[$key]!=='')$args[$key]=$_GET[$key];
header('Location: '.$path.($args?'?'.http_build_query($args):''),true,303);exit;
