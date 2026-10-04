<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();require_admin();

$activityId=max(0,(int)($_GET['activity']??0));
$courseId=max(0,(int)($_GET['course']??0));
$cohortId=max(0,(int)($_GET['cohort']??0));
$view=(string)($_GET['view']??'students');

$args=[];if($activityId>0)$args['activity']=$activityId;if($courseId>0)$args['course']=$courseId;if($cohortId>0)$args['cohort']=$cohortId;
$path='/admin/students.php';
if($courseId>0){
    $path=match($view){
        'tests'=>'/admin/tests.php',
        'lessons'=>'/admin/lessons.php',
        'cohorts'=>'/admin/cohorts.php',
        'pages','material'=>'/admin/material.php',
        default=>'/admin/students.php',
    };
}else{
    unset($args['cohort']);
}
if($view==='students'&&!empty($_GET['import']))$args['import']=1;
if(!empty($_GET['test'])&&$path==='/admin/tests.php')$args['test']=(int)$_GET['test'];
header('Location: '.$path.($args?'?'.http_build_query($args):''),true,303);exit;
