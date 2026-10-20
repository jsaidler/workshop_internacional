<?php
declare(strict_types=1);

/**
 * A review is a single optional encounter belonging to a course. Unlike a
 * course_lesson, it never controls curricular progress or material release.
 * When cohort_id is null, all enrolled students of this course may attend.
 */
function course_review_list(PDO $db,int $courseId,?int $cohortId=null): array {
    $sql='SELECT * FROM course_review_sessions WHERE course_id=?';
    $args=[$courseId];
    if($cohortId!==null){$sql.=' AND (cohort_id IS NULL OR cohort_id=?)';$args[]=$cohortId;}
    $sql.=' ORDER BY meeting_starts_at DESC,id DESC';
    $q=$db->prepare($sql);$q->execute($args);return $q->fetchAll();
}
function course_review_save(PDO $db,int $courseId,int $reviewId,array $input): int {
    if(!course_by_id($db,$courseId))throw new RuntimeException('Curso inválido.');
    $cohortId=max(0,(int)($input['cohort_id']??0));
    if($cohortId && !admin_course_cohort($db,$courseId,$cohortId,false))throw new RuntimeException('A turma não pertence ao curso.');
    $title=trim((string)($input['title']??''));
    if($title===''||mb_strlen($title)>160)throw new RuntimeException('Informe um título com até 160 caracteres.');
    $timezone=trim((string)($input['meeting_timezone']??'America/Sao_Paulo'));
    course_agenda_timezone($timezone);
    $starts=course_agenda_to_utc((string)($input['meeting_starts_at']??''),$timezone);
    if(!$starts)throw new RuntimeException('Informe a data e hora da revisão.');
    $rawMinutes=trim((string)($input['meeting_duration_minutes']??''));
    $minutes=$rawMinutes===''?null:filter_var($rawMinutes,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1440]]);
    if($minutes===false)throw new RuntimeException('A duração deve ter entre 1 e 1440 minutos.');
    $url=trim((string)($input['meeting_url']??''));
    if(strlen($url)>2000 || ($url!=='' && course_agenda_safe_meeting_url($url)===''))throw new RuntimeException('Informe um URL HTTPS válido.');
    $notes=trim((string)($input['meeting_notes']??''));
    if(mb_strlen($notes)>2000)throw new RuntimeException('As instruções devem ter até 2000 caracteres.');
    $status=(string)($input['meeting_status']??'scheduled');
    if(!in_array($status,['scheduled','cancelled','completed'],true))throw new RuntimeException('Estado inválido.');
    $now=utc_now();
    return course_agenda_with_lock($db,static function() use($db,$courseId,$reviewId,$cohortId,$title,$starts,$timezone,$minutes,$url,$notes,$status,$now): int {
        if($reviewId){
            $q=$db->prepare('SELECT id FROM course_review_sessions WHERE id=? AND course_id=?');
            $q->execute([$reviewId,$courseId]);if(!$q->fetchColumn())throw new RuntimeException('Revisão não encontrada neste curso.');
            $db->prepare('UPDATE course_review_sessions SET cohort_id=?,title=?,meeting_starts_at=?,meeting_timezone=?,meeting_duration_minutes=?,meeting_url=?,meeting_notes=?,meeting_status=?,updated_at=? WHERE id=? AND course_id=?')
                ->execute([$cohortId?:null,$title,$starts,$timezone,$minutes,$url,$notes,$status,$now,$reviewId,$courseId]);
            return $reviewId;
        }
        $db->prepare('INSERT INTO course_review_sessions(course_id,cohort_id,title,meeting_starts_at,meeting_timezone,meeting_duration_minutes,meeting_url,meeting_notes,meeting_status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$courseId,$cohortId?:null,$title,$starts,$timezone,$minutes,$url,$notes,$status,$now,$now]);
        return (int)$db->lastInsertId();
    });
}
/**
 * Merge one authorized review per student into the same Agenda projection,
 * including global Home's next-meeting query. Never joins course_lessons.
 */
function course_review_student_meetings(PDO $db,int $studentId,array $regular): array {
    $q=$db->prepare(<<<'SQL'
SELECT r.*,c.title course_title,COALESCE(cc.title,'Todas as turmas') cohort_title
FROM course_review_sessions r
JOIN courses c ON c.id=r.course_id AND c.status!='archived'
LEFT JOIN course_cohorts cc ON cc.id=r.cohort_id AND cc.course_id=r.course_id
JOIN student_users u ON u.id=? AND u.status='active'
WHERE (r.cohort_id IS NULL AND EXISTS (
  SELECT 1 FROM course_enrollments e JOIN course_cohorts ec ON ec.id=e.cohort_id
  WHERE e.student_id=? AND e.status='active' AND ec.course_id=r.course_id AND ec.status!='archived'
)) OR (r.cohort_id IS NOT NULL AND cc.id IS NOT NULL AND cc.status!='archived' AND (
  EXISTS (SELECT 1 FROM course_enrollments e
          WHERE e.student_id=? AND e.cohort_id=r.cohort_id AND e.status='active')
  OR (EXISTS(SELECT 1 FROM course_listener_reservations lr
             WHERE lr.student_id=? AND lr.cohort_id=r.cohort_id AND lr.status='confirmed')
      AND EXISTS(SELECT 1 FROM course_enrollments e JOIN course_cohorts ec ON ec.id=e.cohort_id
                 WHERE e.student_id=? AND e.status='active' AND ec.course_id=r.course_id
                   AND ec.id!=r.cohort_id AND ec.status!='archived'))
))
SQL);
    $q->execute([$studentId,$studentId,$studentId,$studentId,$studentId]);
    foreach($q->fetchAll() as $item){
        $regular[]=[
            'cohort_id'=>$item['cohort_id'],'cohort_title'=>$item['cohort_title'],
            'course_title'=>$item['course_title'],'lesson_id'=>null,
            'lesson_title'=>$item['title'],'meeting_starts_at'=>$item['meeting_starts_at'],
            'meeting_timezone'=>$item['meeting_timezone'],
            'meeting_duration_minutes'=>$item['meeting_duration_minutes'],
            'meeting_url'=>$item['meeting_url'],'meeting_notes'=>$item['meeting_notes'],
            'meeting_status'=>$item['meeting_status'],'participation'=>'regular',
            'meeting_kind'=>'review','review_id'=>(int)$item['id']
        ];
    }
    usort($regular,static fn(array $a,array $b):int=>strcmp((string)$a['meeting_starts_at'],(string)$b['meeting_starts_at']));
    return $regular;
}
