<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();
require_admin();

$activityId=(int)($_GET['activity']??0);
$courseId=(int)($_GET['course']??0);

if($courseId>0){
    $args=['course'=>$courseId,'view'=>'students'];
    if($activityId>0)$args=['activity'=>$activityId]+$args;
    header('Location: /admin/courses.php?'.http_build_query($args),true,303);
    exit;
}

$args=[];
if($activityId>0)$args['activity']=$activityId;
header('Location: /admin/people.php'.($args?'?'.http_build_query($args):''),true,303);
exit;
