<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();require_admin();$db=database();$activityId=(int)($_GET['activity']??$_POST['activity']??0);if($activityId<1)content_json(['error'=>['code'=>'activity_required']],400);
if(($_SERVER['REQUEST_METHOD']??'GET')==='GET'){
    $out=[];foreach(course_cohorts($db,$activityId) as $cohort){foreach(course_lesson_release_rows($db,(int)$cohort['id'],$activityId) as $lesson){$releasedAt=$lesson['released_at']??null;$out[]=['cohortId'=>(int)$cohort['id'],'lessonId'=>(int)$lesson['id'],'releasedAt'=>$releasedAt,'localValue'=>cms_access_local_input_value(is_string($releasedAt)?$releasedAt:null),'state'=>cms_access_lesson_release_state(is_string($releasedAt)?$releasedAt:null)];}}
    content_json(['items'=>$out,'timezone'=>(string)(app_config()['timezone']??'UTC')]);
}
if(!verify_csrf('student-area',$_POST['_csrf']??null))content_json(['error'=>['code'=>'invalid_csrf']],403);
try{$releasedAt=cms_access_set_lesson_release($db,$activityId,(int)($_POST['cohort_id']??0),(int)($_POST['lesson_id']??0),(string)($_POST['mode']??''),(string)($_POST['scheduled_at']??''));content_json(['ok'=>true,'releasedAt'=>$releasedAt,'localValue'=>cms_access_local_input_value($releasedAt),'state'=>cms_access_lesson_release_state($releasedAt)]);}catch(Throwable $e){content_json(['error'=>['code'=>'save_failed','message'=>$e->getMessage()]],400);}
