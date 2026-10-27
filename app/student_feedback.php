<?php
declare(strict_types=1);

function student_feedback_available(PDO $db): bool {
    try{
        $columns=[];
        foreach($db->query('PRAGMA table_info(student_tests)')->fetchAll(PDO::FETCH_ASSOC) as $column)$columns[(string)($column['name']??'')]=true;
        return isset($columns['feedback_at'],$columns['feedback_seen_at']);
    }catch(Throwable){return false;}
}

function student_feedback_state(array $test): array {
    $status=(string)($test['status']??'draft');
    $feedbackAt=trim((string)($test['feedback_at']??''));
    $seenAt=trim((string)($test['feedback_seen_at']??''));
    $unseen=$feedbackAt!==''&&($seenAt===''||strcmp($seenAt,$feedbackAt)<0);

    return match($status){
        'submitted' => [
            'kind'=>$unseen?'feedback':'waiting',
            'label'=>$unseen?'Novo retorno na avaliação':'Enviado para avaliação',
            'action'=>$unseen?'Ler retorno':'Aguardando avaliação',
            'unseen'=>$unseen,
            'requires_action'=>false,
        ],
        'needs_revision' => [
            'kind'=>'revision',
            'label'=>'Revisão solicitada',
            'action'=>'Ler retorno e revisar',
            'unseen'=>$unseen,
            'requires_action'=>true,
        ],
        'reviewed' => [
            'kind'=>'reviewed',
            'label'=>$unseen?'Avaliação disponível':'Avaliação concluída',
            'action'=>$unseen?'Ler avaliação':'Rever avaliação',
            'unseen'=>$unseen,
            'requires_action'=>false,
        ],
        default => [
            'kind'=>'draft',
            'label'=>'Ainda não enviado',
            'action'=>'Enviar para avaliação',
            'unseen'=>false,
            'requires_action'=>false,
        ],
    };
}

function student_feedback_pending_for_student(PDO $db,int $studentId,?int $cohortId=null,int $limit=20): array {
    if($studentId<1||!student_feedback_available($db))return [];
    $where=['t.student_id=?',"(t.status='needs_revision' OR (t.status IN ('submitted','reviewed') AND t.feedback_at IS NOT NULL AND (t.feedback_seen_at IS NULL OR t.feedback_seen_at<t.feedback_at)))"];
    $args=[$studentId];
    if($cohortId!==null&&$cohortId>0){$where[]='t.cohort_id=?';$args[]=$cohortId;}
    $sql='SELECT t.*,c.title cohort_title,c.cohort_uuid,c.activity_id,a.public_title FROM student_tests t JOIN course_cohorts c ON c.id=t.cohort_id JOIN activities a ON a.id=c.activity_id WHERE '.implode(' AND ',$where)." ORDER BY CASE t.status WHEN 'needs_revision' THEN 0 WHEN 'reviewed' THEN 1 ELSE 2 END,COALESCE(t.feedback_at,t.updated_at) DESC,t.id DESC LIMIT ?";
    $q=$db->prepare($sql);
    $position=1;foreach($args as $arg)$q->bindValue($position++,$arg,PDO::PARAM_INT);$q->bindValue($position,max(1,min(100,$limit)),PDO::PARAM_INT);$q->execute();
    return $q->fetchAll(PDO::FETCH_ASSOC);
}

function student_feedback_mark_seen(PDO $db,int $testId,int $studentId): void {
    if($testId<1||$studentId<1||!student_feedback_available($db))return;
    $q=$db->prepare('UPDATE student_tests SET feedback_seen_at=feedback_at WHERE id=? AND student_id=? AND feedback_at IS NOT NULL AND (feedback_seen_at IS NULL OR feedback_seen_at<feedback_at)');
    $q->execute([$testId,$studentId]);
}

function student_feedback_conversations_for_student(PDO $db,int $studentId,int $cohortId,int $limit=20): array {
    if($studentId<1||$cohortId<1)return [];
    $feedbackSelect=student_feedback_available($db)?',t.feedback_at,t.feedback_seen_at':',NULL feedback_at,NULL feedback_seen_at';
    $sql="SELECT t.id,t.title,t.status,t.updated_at,t.cohort_id$feedbackSelect,
        (SELECT m.author_role FROM student_test_messages m WHERE m.test_id=t.id ORDER BY m.id DESC LIMIT 1) last_author_role,
        (SELECT m.body FROM student_test_messages m WHERE m.test_id=t.id ORDER BY m.id DESC LIMIT 1) last_message,
        (SELECT m.created_at FROM student_test_messages m WHERE m.test_id=t.id ORDER BY m.id DESC LIMIT 1) last_message_at,
        (SELECT COUNT(*) FROM student_test_messages m WHERE m.test_id=t.id) message_count
        FROM student_tests t
        WHERE t.student_id=? AND t.cohort_id=? AND (t.status IN ('submitted','needs_revision','reviewed') OR EXISTS(SELECT 1 FROM student_test_messages m WHERE m.test_id=t.id))
        ORDER BY COALESCE((SELECT m.created_at FROM student_test_messages m WHERE m.test_id=t.id ORDER BY m.id DESC LIMIT 1),t.updated_at) DESC,t.id DESC LIMIT ?";
    $q=$db->prepare($sql);$q->bindValue(1,$studentId,PDO::PARAM_INT);$q->bindValue(2,$cohortId,PDO::PARAM_INT);$q->bindValue(3,max(1,min(100,$limit)),PDO::PARAM_INT);$q->execute();
    return $q->fetchAll(PDO::FETCH_ASSOC);
}

function student_feedback_open_question_count(PDO $db,int $studentId,int $cohortId): int {
    if($studentId<1||$cohortId<1)return 0;
    try{$q=$db->prepare("SELECT COUNT(*) FROM student_questions WHERE student_id=? AND cohort_id=? AND status!='resolved'");$q->execute([$studentId,$cohortId]);return (int)$q->fetchColumn();}catch(Throwable){return 0;}
}

function student_feedback_course_attention(PDO $db,int $studentId,int $cohortId): array {
    $feedback=student_feedback_pending_for_student($db,$studentId,$cohortId,20);
    $questions=student_feedback_open_question_count($db,$studentId,$cohortId);
    return ['feedback'=>$feedback,'feedback_count'=>count($feedback),'question_count'=>$questions,'has_attention'=>(bool)$feedback||$questions>0];
}

function student_feedback_date(string $value): string {
    $ts=strtotime($value);return $ts===false?$value:date('d/m/Y · H:i',$ts);
}
