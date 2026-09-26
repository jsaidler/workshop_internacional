<?php
declare(strict_types=1);

/**
 * Canonical enrollment service.
 *
 * student_users is the global identity. course_enrollments is the relationship
 * between that identity and a cohort/activity. An authenticated enrollment
 * submission must therefore keep the session student_id instead of trying to
 * discover the person again from form fields.
 */
function student_enrollment_payload_identity(array $values): array {
    $email=student_normalize_email((string)($values['email']??''));
    $cpfHash=student_account_cpf_lookup_hash((string)($values['cpf']??''));
    return [$email,$cpfHash];
}

function student_enrollment_validate_authenticated_identity(PDO $db,array $values,array $student): int {
    $studentId=(int)($student['id']??0);
    if($studentId<1)throw new RuntimeException('student_identity_invalid');
    $q=$db->prepare("SELECT * FROM student_users WHERE id=? AND status='active' LIMIT 1");
    $q->execute([$studentId]);$account=$q->fetch()?:throw new RuntimeException('student_identity_invalid');

    [$email,$cpfHash]=student_enrollment_payload_identity($values);
    if($email!==''||$cpfHash!==''){
        $candidate=student_account_find_identity($db,$email,$cpfHash);
        if($candidate&&(int)$candidate['id']!==$studentId)throw new RuntimeException('student_identity_conflict');
    }
    return (int)$account['id'];
}

function student_enrollment_bind_authenticated_submission(PDO $db,int $submissionId,array $form,array $values,array $student): int {
    if(cms_form_purpose($form)!=='enrollment')return 0;
    $studentId=student_enrollment_validate_authenticated_identity($db,$values,$student);
    $q=$db->prepare("SELECT s.id,s.form_id,s.activity_id,f.purpose FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE s.id=? LIMIT 1");
    $q->execute([$submissionId]);$submission=$q->fetch()?:throw new RuntimeException('submission_not_found');
    if((int)$submission['form_id']!==(int)$form['id']||(int)$submission['activity_id']!==(int)$form['activity_id']||cms_form_purpose(['purpose'=>$submission['purpose']])!=='enrollment')throw new RuntimeException('submission_identity_scope_invalid');
    $db->prepare('UPDATE cms_form_submissions SET student_id=?,updated_at=? WHERE id=?')->execute([$studentId,utc_now(),$submissionId]);
    return $studentId;
}

function student_enrollment_student_from_submission(PDO $db,array $submission): int {
    $payload=json_decode((string)($submission['payload_json']??''),true);
    if(!is_array($payload))throw new RuntimeException('submission_payload_invalid');
    $boundStudentId=(int)($submission['student_id']??0);
    if($boundStudentId<1)return student_account_upsert_from_submission($db,$submission);

    $q=$db->prepare("SELECT * FROM student_users WHERE id=? AND status='active' LIMIT 1");
    $q->execute([$boundStudentId]);$student=$q->fetch()?:throw new RuntimeException('bound_student_not_found');
    student_enrollment_validate_authenticated_identity($db,$payload,$student);
    student_account_merge_profile_from_payload($db,$boundStudentId,$payload);
    return $boundStudentId;
}

function student_enrollment_reconcile_confirmed(PDO $db,?int $activityId=null): array {
    $sql="SELECT s.*,f.form_key,f.purpose FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE f.purpose='enrollment'";
    $args=[];
    if($activityId!==null){$sql.=' AND s.activity_id=?';$args[]=$activityId;}
    $sql.=' ORDER BY s.id';$q=$db->prepare($sql);$q->execute($args);
    $processed=0;$disabled=0;$errors=[];
    foreach($q->fetchAll() as $row){
        try{
            if(student_account_submission_confirmed($row)){
                $cohortId=(int)($row['cohort_id']??0);$cohort=$cohortId?course_cohort_by_id($db,$cohortId):null;
                if(!$cohort||(int)$cohort['activity_id']!==(int)$row['activity_id']){$cohort=course_default_cohort($db,(int)$row['activity_id'],true);$cohortId=(int)$cohort['id'];}
                $studentId=student_enrollment_student_from_submission($db,$row);$now=utc_now();
                $db->prepare("INSERT INTO course_enrollments(enrollment_uuid,student_id,cohort_id,source_submission_id,status,confirmed_at,created_at,updated_at) VALUES(?,?,?,?, 'active',?,?,?) ON CONFLICT(student_id,cohort_id) DO UPDATE SET source_submission_id=excluded.source_submission_id,status='active',confirmed_at=excluded.confirmed_at,updated_at=excluded.updated_at")
                    ->execute([student_uuid(),$studentId,$cohortId,(int)$row['id'],(string)($row['payment_confirmed_at']?:$row['updated_at']?:$now),$now,$now]);
                $db->prepare('UPDATE cms_form_submissions SET student_id=?,cohort_id=?,updated_at=? WHERE id=?')->execute([$studentId,$cohortId,$now,(int)$row['id']]);
                $processed++;
            }elseif(!empty($row['student_id'])&&!empty($row['cohort_id'])){
                $db->prepare("UPDATE course_enrollments SET status='disabled',updated_at=? WHERE student_id=? AND cohort_id=? AND source_submission_id=?")
                    ->execute([utc_now(),(int)$row['student_id'],(int)$row['cohort_id'],(int)$row['id']]);
                $disabled++;
            }
        }catch(Throwable $e){
            $errors[]=['submission_id'=>(int)$row['id'],'error'=>$e->getMessage()];
            error_log('Student enrollment reconciliation failed for submission '.(int)$row['id'].': '.$e->getMessage());
        }
    }
    return ['processed'=>$processed,'disabled'=>$disabled,'errors'=>$errors];
}

function student_enrollment_install_reconciliation_hook(): void {
    $script=str_replace('\\','/',(string)($_SERVER['SCRIPT_NAME']??$_SERVER['PHP_SELF']??''));
    if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'||!str_ends_with($script,'/admin/submissions.php'))return;
    register_shutdown_function(static function(): void {
        try{student_enrollment_reconcile_confirmed(database());}
        catch(Throwable $e){error_log('Student enrollment reconciliation hook failed: '.$e->getMessage());}
    });
}

function student_enrollment_pages_for_enrollment(PDO $db,array $enrollment): array {
    $q=$db->prepare("SELECT * FROM cms_pages WHERE activity_id=? AND access_level='activity' AND status!='archived' AND published_document_json IS NOT NULL ORDER BY locale,sort_order,id");
    $q->execute([(int)$enrollment['activity_id']]);
    return $q->fetchAll();
}
