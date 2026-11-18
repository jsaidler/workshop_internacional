<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();require_admin();$db=database();$activityId=(int)($_GET['activity']??0);$testId=(int)($_GET['test']??0);$test=student_test_for_admin($db,$testId,$activityId);if(!$test)content_json(['error'=>['code'=>'test_not_found']],404);content_json(['id'=>$testId,'bleach'=>(string)($test['bleach']??'')]);
