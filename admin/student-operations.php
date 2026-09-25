<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();
require_admin();

$legacy=(string)($_GET['view']??'cohorts');
$view=match($legacy){'tests'=>'tests','import'=>'students',default=>'cohorts'};
$params=['view'=>$view];
$activity=(int)($_GET['activity']??0);if($activity>0)$params['activity']=$activity;
if($legacy==='import')$params['import']=1;
foreach(['cohort','batch','test','q','status','p'] as $key)if(isset($_GET[$key])&&$_GET[$key]!=='')$params[$key]=$_GET[$key];
header('Location: /admin/student-area.php?'.http_build_query($params),true,303);
exit;
