<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/student_home.php';
require_once __DIR__.'/../app/student_record_create_dialog.php';
security_headers();student_private_headers();
$db=database();
student_enrollment_reconcile_confirmed($db);
$student=student_account_current($db);
if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2F',true,303);exit;}
$studentId=(int)$student['id'];
$enrollments=student_enrollment_list($db,$studentId);
$records=student_process_records_for_student($db,$studentId);
$feedbackRecord=student_feedback_pending_for_student($db,$studentId,null,1)[0]??null;
$feedback=null;
if($feedbackRecord){
    $s=student_feedback_state($feedbackRecord);
    $feedback=[
        'title'=>(string)$feedbackRecord['title'],
        'status'=>(string)$s['label'],
        'action'=>(string)$s['action'],
        'url'=>'/aluno/teste.php?id='.(int)$feedbackRecord['id'].'&view=review',
    ];
}
$focus=null;
foreach($records as $candidate){
    $steps=student_process_steps($db,(int)$candidate['id']);
    $hasResult=trim((string)($candidate['notes']??''))!=='';
    if(student_experience_process_complete($steps)&&!$hasResult){
        $media=student_test_media($db,(int)$candidate['id']);
        $hasResult=(bool)student_test_media_by_phase($media,'result');
    }
    $state=student_experience_dashboard_state($candidate,$steps,$hasResult);
    if(!$state)continue;
    $focus=[
        'title'=>(string)$candidate['title'],
        'status'=>(string)$state['label'],
        'action'=>(string)$state['action'],
        'url'=>'/aluno/teste.php?id='.(int)$candidate['id'].'&view='.rawurlencode((string)$state['view']),
    ];
    break;
}
$canTimer=false;
foreach(student_tools_for_student($db,$studentId) as $tool)if((string)$tool['tool_key']==='lab_timer')$canTimer=true;
$home=[
    'meeting'=>student_home_next_meeting(course_agenda_student_meetings($db,$studentId)),
    'feedback'=>$feedback,
    'focus'=>$focus,
    'courses'=>student_home_course_items($db,$enrollments),
    'recent'=>student_home_record_items($records),
    'can_timer'=>$canTimer,
];
student_shell_start('Início',null,$student);
require __DIR__.'/../app/student_home_view.php';
student_record_create_dialog($enrollments,csrf_token('student-process-notebook'),[],'','/aluno/caderno.php');
student_shell_end();
