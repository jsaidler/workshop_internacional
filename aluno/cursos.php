<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_enrollment_reconcile_confirmed($db);
$student=student_account_current($db);
if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2Fcursos.php',true,303);exit;}
$studentId=(int)$student['id'];
$enrollments=student_enrollment_list($db,$studentId);
$requestedCohortUuid=is_string($_GET['cohort']??null)?trim((string)$_GET['cohort']):'';
$selectedEnrollment=null;
if($requestedCohortUuid!=='')$selectedEnrollment=student_enrollment_dashboard_context($enrollments,$requestedCohortUuid);
elseif(count($enrollments)===1)$selectedEnrollment=$enrollments[0];
$selectedActivity=null;
if($selectedEnrollment){
    $selectedActivity=activity_by_id($db,(int)$selectedEnrollment['activity_id']);
    if(!$selectedActivity)$selectedEnrollment=null;
}
$materialItems=[];$pendingLessons=[];$releases=[];$attention=['has_attention'=>false,'feedback'=>[],'question_count'=>0];
if($selectedEnrollment){
    $enrollment=$selectedEnrollment;
    $activity=$selectedActivity;
    $courseId=(int)($enrollment['course_id']??0);
    $workshopId=(int)($enrollment['workshop_page_id']??0);
    $pages=student_enrollment_pages_for_enrollment($db,$enrollment);
    $attention=student_feedback_course_attention($db,$studentId,(int)$enrollment['cohort_id']);
    if($courseId>0)$releases=course_lesson_release_rows_for_course($db,(int)$enrollment['cohort_id'],$courseId);
    elseif($workshopId>0)$releases=workshop_course_lesson_release_rows($db,(int)$enrollment['cohort_id'],$workshopId);
    else $releases=course_lesson_release_rows($db,(int)$enrollment['cohort_id'],(int)$enrollment['activity_id']);
    $releaseStates=[];
    foreach($releases as $lesson){
        $state=cms_access_lesson_release_state(isset($lesson['released_at'])?(string)$lesson['released_at']:null);
        $releaseStates[(int)$lesson['id']]=$state;
        if($state!=='released')$pendingLessons[]=[$lesson,$state];
    }
    foreach($pages as $page){
        $url=cms_page_url($activity,$page,(string)$page['locale']);
        $url.=(str_contains($url,'?')?'&':'?').'cohort='.rawurlencode((string)$enrollment['cohort_uuid']);
        $summary=$courseId>0?course_material_page_release_summary($db,$courseId,(int)$enrollment['cohort_id'],(int)$page['id'],$releases):['state'=>'available','label'=>'Disponível'];
        $materialItems[]=['page'=>$page,'url'=>$url,'summary'=>$summary];
    }
}
student_shell_start('Cursos',$selectedActivity,$student);
require __DIR__.'/../app/student_courses_view.php';
student_shell_end();
function student_course_release_when(string $value): string {
    $ts=strtotime($value);
    return $ts===false?'Agendada':'Agendada para '.date('d/m/Y · H:i',$ts);
}
