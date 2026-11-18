<?php
declare(strict_types=1);

/**
 * Transfer an imported/manual enrollment within the same course.
 *
 * Unlike registrations (whose source submission is authoritative), a manual
 * enrollment has no submission to reassign. Preserve the original record
 * as disabled historical membership and activate/create the destination row.
 * Capacity triggers and listener reconciliation execute in one transaction.
 */
function admin_student_transfer_manual(PDO $db,int $courseId,int $enrollmentId,int $targetCohortId): void {
    $work=static function() use($db,$courseId,$enrollmentId,$targetCohortId):void {
        $q=$db->prepare('SELECT e.*,c.course_id origin_course_id,c.status origin_status FROM course_enrollments e JOIN course_cohorts c ON c.id=e.cohort_id WHERE e.id=?');
        $q->execute([$enrollmentId]);$old=$q->fetch(PDO::FETCH_ASSOC);
        if(!$old||(int)$old['origin_course_id']!==$courseId||(string)$old['status']!=='active')throw new RuntimeException('Matrícula de origem inválida ou já desativada.');
        if((int)($old['source_submission_id']??0)>0)throw new RuntimeException('Esta participação está vinculada à inscrição original. Altere a turma pela inscrição.');
        $studentId=(int)$old['student_id'];$oldCohortId=(int)$old['cohort_id'];
        if($targetCohortId===$oldCohortId)throw new RuntimeException('Escolha uma turma diferente da atual.');
        $target=$db->prepare('SELECT id,course_id,status FROM course_cohorts WHERE id=? AND course_id=?');
        $target->execute([$targetCohortId,$courseId]);$destination=$target->fetch(PDO::FETCH_ASSOC);
        if(!$destination||(string)$destination['status']!=='active')throw new RuntimeException('A turma de destino não está ativa neste curso.');
        $duplicate=$db->prepare('SELECT id,status,source_submission_id FROM course_enrollments WHERE student_id=? AND cohort_id=?');
        $duplicate->execute([$studentId,$targetCohortId]);$existing=$duplicate->fetch(PDO::FETCH_ASSOC)?:null;
        if($existing&&(string)$existing['status']==='active')throw new RuntimeException('O aluno já possui matrícula ativa na turma escolhida.');
        if($existing&&(int)($existing['source_submission_id']??0)>0)throw new RuntimeException('A matrícula anterior nesta turma pertence a uma inscrição. Resolva pela inscrição original.');
        $now=utc_now();
        $db->prepare("UPDATE course_enrollments SET status='disabled',updated_at=? WHERE id=? AND status='active'")->execute([$now,$enrollmentId]);
        try{
            if($existing){
                $db->prepare("UPDATE course_enrollments SET status='active',confirmed_at=?,updated_at=? WHERE id=? AND status!='active'")->execute([$now,$now,(int)$existing['id']]);
            }else{
                $db->prepare("INSERT INTO course_enrollments(enrollment_uuid,student_id,cohort_id,source_submission_id,status,confirmed_at,created_at,updated_at) VALUES(?,?,?,NULL,'active',?,?,?)")->execute([student_uuid(),$studentId,$targetCohortId,$now,$now,$now]);
            }
        }catch(PDOException $e){
            if(str_contains($e->getMessage(),'cohort_capacity_full'))throw new RuntimeException('A turma de destino está lotada; nenhuma matrícula foi alterada.',0,$e);
            throw $e;
        }
        if(function_exists('course_agenda_reconcile_student'))course_agenda_reconcile_student($db,$studentId);
        if(function_exists('course_agenda_reconcile_cohort')){
            course_agenda_reconcile_cohort($db,$oldCohortId);
            course_agenda_reconcile_cohort($db,$targetCohortId);
        }
    };
    if(function_exists('course_agenda_with_lock')){
        course_agenda_with_lock($db,$work);
        return;
    }
    $owned=!$db->inTransaction();
    if($owned)$db->beginTransaction();
    try{$work();if($owned)$db->commit();}
    catch(Throwable $e){if($owned&&$db->inTransaction())$db->rollBack();throw $e;}
}
