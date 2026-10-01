<?php
declare(strict_types=1);

/**
 * Thin safety layer around the student workbench domain.
 *
 * These helpers keep compatibility with the first workbench implementation
 * while enforcing the product contracts that depend on context: a fresh
 * Caffenol preparation is never inventory stock, drying closes processing,
 * deleting an earlier process step also removes the now-invalid tail of the
 * route, and cohort questions are only readable/repliable by students
 * enrolled in that exact cohort.
 */
function student_process_add_guided_step(PDO $db,int $testId,int $studentId,array $input): array {
    $steps=student_process_steps($db,$testId);
    if(student_experience_process_complete($steps))throw new RuntimeException('O processamento terminou na secagem. Registre o resultado.');
    $stageKey=(string)($input['stage_key']??'');
    $allowed=student_experience_next_choices($steps);
    if(!isset($allowed[$stageKey]))throw new RuntimeException('Escolha uma etapa válida.');
    if(in_array($stageKey,['first_development','second_development'],true)){
        $developer=student_process_developer((string)($input['developer_key']??''),(string)($input['developer_name']??''));
        if((string)$developer['mode']==='fresh'){
            unset($input['inventory_item_id'],$input['inventory_amount']);
        }
    }
    return student_process_add_flexible_step($db,$testId,$studentId,$input);
}

function student_process_delete_from_step(PDO $db,int $testId,int $stepId,int $studentId): void {
    $test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');
    if((string)$test['status']==='reviewed')throw new RuntimeException('Este registro já foi revisado.');
    $q=$db->prepare('SELECT * FROM student_process_steps WHERE id=? AND test_id=?');
    $q->execute([$stepId,$testId]);$target=$q->fetch(PDO::FETCH_ASSOC);
    if(!$target)throw new RuntimeException('Etapa inválida.');
    $tail=$db->prepare('SELECT * FROM student_process_steps WHERE test_id=? AND position>=? ORDER BY position DESC,id DESC');
    $tail->execute([$testId,(int)$target['position']]);$steps=$tail->fetchAll(PDO::FETCH_ASSOC);
    $db->beginTransaction();
    try{
        $sum=$db->prepare('SELECT COALESCE(SUM(quantity_delta),0) FROM student_inventory_movements WHERE step_id=?');
        foreach($steps as $step){
            $sum->execute([(int)$step['id']]);$delta=(float)$sum->fetchColumn();
            if($delta<0&&!empty($step['inventory_item_id'])){
                student_inventory_move($db,$studentId,(int)$step['inventory_item_id'],-$delta,'adjust','Estorno por alteração do processo',$testId,null,false);
            }
        }
        $db->prepare('DELETE FROM student_process_steps WHERE test_id=? AND position>=?')->execute([$testId,(int)$target['position']]);
        if((int)$target['position']===1){
            $db->prepare("UPDATE student_tests SET developer='',dilution='',temperature='',development_time='',agitation='',bleach='',updated_at=? WHERE id=? AND student_id=?")
                ->execute([utc_now(),$testId,$studentId]);
        }else{
            $remaining=student_process_steps($db,$testId);$branch=student_process_last_bleach($remaining);$bleach=['peracetic'=>'Solução peroxiacética','ferric'=>'Cloreto férrico','dichromate'=>'Dicromato','permanganate'=>'Permanganato'][$branch]??'';
            $db->prepare('UPDATE student_tests SET bleach=?,updated_at=? WHERE id=? AND student_id=?')->execute([$bleach,utc_now(),$testId,$studentId]);
        }
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}

function student_saved_preparation_create_guided(PDO $db,int $studentId,array $input): array {
    $developer=student_process_developer((string)($input['developer_key']??''),(string)($input['developer_name']??''));
    if((string)$developer['mode']==='fresh'){
        $input['developer_amount']=$input['fresh_volume']??null;
        $input['water_amount']=null;
    }
    return student_saved_preparation_create($db,$studentId,$input);
}

function student_question_for_enrolled_student(PDO $db,int $studentId,int $questionId): ?array {
    $q=$db->prepare("SELECT q.*,u.name student_name
        FROM student_questions q
        JOIN student_users u ON u.id=q.student_id
        WHERE q.id=? AND (
            q.student_id=? OR (
                q.visibility='cohort' AND EXISTS(
                    SELECT 1 FROM course_enrollments e
                    WHERE e.student_id=? AND e.cohort_id=q.cohort_id AND e.status='active'
                )
            )
        ) LIMIT 1");
    $q->execute([$questionId,$studentId,$studentId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_question_reply_enrolled(PDO $db,int $studentId,int $questionId,string $body): void {
    $question=student_question_for_enrolled_student($db,$studentId,$questionId)??throw new RuntimeException('Dúvida não encontrada.');
    if((string)$question['visibility']!=='cohort'&&(int)$question['student_id']!==$studentId)throw new RuntimeException('Esta dúvida é privada.');
    $body=student_workspace_text($body,4000);if($body==='')throw new RuntimeException('Escreva uma resposta.');$now=utc_now();
    $db->prepare("INSERT INTO student_question_messages(message_uuid,question_id,author_role,student_id,body,created_at) VALUES(?,?,'student',?,?,?)")
        ->execute([student_uuid(),$questionId,$studentId,$body,$now]);
    $db->prepare('UPDATE student_questions SET updated_at=? WHERE id=?')->execute([$now,$questionId]);
}

function student_questions_for_admin(PDO $db,int $courseId=0,int $cohortId=0,string $status=''): array {
    $where=['1=1'];$args=[];
    if($courseId>0){$where[]='c.course_id=?';$args[]=$courseId;}
    if($cohortId>0){$where[]='q.cohort_id=?';$args[]=$cohortId;}
    if(in_array($status,['open','resolved'],true)){$where[]='q.status=?';$args[]=$status;}
    $sql="SELECT q.*,u.name student_name,c.title cohort_title,cr.title course_title,
        (SELECT COUNT(*) FROM student_question_messages m WHERE m.question_id=q.id) message_count
        FROM student_questions q
        JOIN student_users u ON u.id=q.student_id
        JOIN course_cohorts c ON c.id=q.cohort_id
        LEFT JOIN courses cr ON cr.id=c.course_id
        WHERE ".implode(' AND ',$where)."
        ORDER BY CASE q.status WHEN 'open' THEN 0 ELSE 1 END,q.updated_at DESC,q.id DESC";
    $q=$db->prepare($sql);$q->execute($args);return $q->fetchAll(PDO::FETCH_ASSOC);
}

function student_question_for_admin(PDO $db,int $questionId,int $courseId=0): ?array {
    $sql="SELECT q.*,u.name student_name,u.email student_email,c.title cohort_title,cr.title course_title,c.course_id
        FROM student_questions q
        JOIN student_users u ON u.id=q.student_id
        JOIN course_cohorts c ON c.id=q.cohort_id
        LEFT JOIN courses cr ON cr.id=c.course_id
        WHERE q.id=?";$args=[$questionId];
    if($courseId>0){$sql.=' AND c.course_id=?';$args[]=$courseId;}$sql.=' LIMIT 1';
    $q=$db->prepare($sql);$q->execute($args);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_question_admin_reply(PDO $db,int $questionId,string $body): int {
    student_question_for_admin($db,$questionId)??throw new RuntimeException('Dúvida não encontrada.');
    $body=student_workspace_text($body,4000);if($body==='')throw new RuntimeException('Escreva uma resposta.');$now=utc_now();
    $db->prepare("INSERT INTO student_question_messages(message_uuid,question_id,author_role,student_id,body,created_at) VALUES(?,?,'admin',NULL,?,?)")
        ->execute([student_uuid(),$questionId,$body,$now]);$messageId=(int)$db->lastInsertId();
    $db->prepare('UPDATE student_questions SET updated_at=? WHERE id=?')->execute([$now,$questionId]);return $messageId;
}

function student_question_admin_resolve(PDO $db,int $questionId,?int $acceptedMessageId=null): void {
    student_question_for_admin($db,$questionId)??throw new RuntimeException('Dúvida não encontrada.');
    if($acceptedMessageId){$q=$db->prepare('SELECT 1 FROM student_question_messages WHERE id=? AND question_id=?');$q->execute([$acceptedMessageId,$questionId]);if(!$q->fetchColumn())$acceptedMessageId=null;}
    $db->prepare("UPDATE student_questions SET status='resolved',accepted_message_id=?,updated_at=? WHERE id=?")
        ->execute([$acceptedMessageId,utc_now(),$questionId]);
}
