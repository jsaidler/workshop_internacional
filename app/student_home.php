<?php
declare(strict_types=1);

/**
 * Projection for the authenticated Home. All links remain scoped to the
 * student's own enrollment, record and tool authorizations.
 * No inferred attendance, unread flags or obligatory notebook steps.
 */
function student_home_safe_meeting_url(mixed $value): string {
    return course_agenda_safe_meeting_url($value);
}
function student_home_next_meeting(array $meetings,?int $now=null): ?array {
    $now??=time();
    foreach($meetings as $meeting){
        if((string)($meeting['meeting_status']??'')!=='scheduled')continue;
        $date=(string)($meeting['meeting_starts_at']??'');
        $ts=strtotime($date);
        if($ts===false)continue;
        // A scheduled encounter remains reachable during its published duration.
        // This is a time window, not evidence the encounter actually occurred.
        $duration=(int)($meeting['meeting_duration_minutes']??0);
        $inWindow=$ts<$now && $duration>0 && $ts+$duration*60>$now;
        if($ts<$now && !$inWindow)continue;
        return [
            'in_window'=>$inWindow,
            'date_iso'=>$date,
            'day'=>(new DateTimeImmutable($date))->setTimezone(new DateTimeZone((string)($meeting['meeting_timezone']??'America/Sao_Paulo')))->format('d'),
            'month'=>['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'][(int)(new DateTimeImmutable($date))->setTimezone(new DateTimeZone((string)($meeting['meeting_timezone']??'America/Sao_Paulo')))->format('n')-1],
            'weekday'=>['dom','seg','ter','qua','qui','sex','sáb'][(int)(new DateTimeImmutable($date))->setTimezone(new DateTimeZone((string)($meeting['meeting_timezone']??'America/Sao_Paulo')))->format('w')],
            'hour'=>(new DateTimeImmutable($date))->setTimezone(new DateTimeZone((string)($meeting['meeting_timezone']??'America/Sao_Paulo')))->format('H:i'),
            'lesson'=>(string)($meeting['lesson_title']??'Encontro'),
            'course'=>(string)($meeting['course_title']??''),
            'cohort'=>(string)($meeting['cohort_title']??''),
            'when'=>course_agenda_when($date,(string)($meeting['meeting_timezone']??'America/Sao_Paulo')),
            'url'=>student_home_safe_meeting_url($meeting['meeting_url']??''),
            'participation'=>(string)($meeting['participation']??'regular'),
        ];
    }
    return null;
}
function student_home_course_items(PDO $db,array $enrollments): array {
    $out=[];
    foreach($enrollments as $enrollment){
        $activity=activity_by_id($db,(int)$enrollment['activity_id']);
        if(!$activity)continue;
        $uuid=(string)$enrollment['cohort_uuid'];
        $cohort=(int)$enrollment['cohort_id'];
        $courseId=(int)($enrollment['course_id']??0);
        $releases=$courseId>0?course_lesson_release_rows_for_course($db,$cohort,$courseId):[];
        $first=null;$available=0;
        foreach(student_enrollment_pages_for_enrollment($db,$enrollment) as $page){
            $summary=$courseId>0?course_material_page_release_summary($db,$courseId,$cohort,(int)$page['id'],$releases):['state'=>'available'];
            if(!in_array((string)$summary['state'],['available','partial'],true))continue;
            $available++;
            if($first!==null)continue;
            $url=cms_page_url($activity,$page,(string)$page['locale']);
            $url.=(str_contains($url,'?')?'&':'?').'cohort='.rawurlencode($uuid);
            $first=['title'=>(string)$page['title'],'url'=>$url,'course'=>(string)$enrollment['course_title'],'cohort'=>(string)$enrollment['cohort_title']];
        }
        $out[]=[
            'title'=>(string)$enrollment['course_title'],
            'cohort'=>(string)$enrollment['cohort_title'],
            'url'=>'/aluno/cursos.php?cohort='.rawurlencode($uuid),
            'questions_url'=>'/aluno/duvidas.php?cohort='.rawurlencode($uuid),
            'material'=>$first,
            'available'=>$available,
        ];
    }
    return $out;
}
function student_home_record_items(array $records,int $limit=2): array {
    $out=[];
    foreach(array_slice($records,0,$limit) as $record){
        $out[]=[
            'title'=>(string)$record['title'],
            'url'=>'/aluno/teste.php?id='.(int)$record['id'],
            'date'=>student_home_short_date((string)($record['updated_at']??'')),
            'scope'=>(string)($record['context_scope']??'personal')==='personal'?'Pessoal':'Curso',
        ];
    }
    return $out;
}
function student_home_short_date(string $value): string {
    $ts=strtotime($value);
    return $ts===false?'':date('d/m/Y',$ts);
}


/** Live Home events: only source-backed facts for current, active enrollments. */
function student_home_notices_table(PDO $db): bool {
    return (bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='student_home_notice_reads'")->fetchColumn();
}
function student_home_question_notices(PDO $db,int $studentId,int $limit=30): array {
    if($studentId<1)return [];
    $reads=student_home_notices_table($db);
    $seen=$reads?'COALESCE(n.message_id,0)':'0';
    $join=$reads?'LEFT JOIN student_home_notice_reads n ON n.student_id=? AND n.question_id=q.id':'';
    $sql="SELECT q.id question_id,q.title,origin.title cohort_title,origin.cohort_uuid,
       courses.title course_title,MAX(m.id) message_id,MAX(m.created_at) occurred_at,
       $seen last_seen_message_id
       FROM student_questions q
       JOIN course_cohorts origin ON origin.id=q.cohort_id AND origin.status!='archived'
       JOIN course_enrollments e ON e.cohort_id=q.cohort_id AND e.student_id=q.student_id AND e.status='active'
       LEFT JOIN courses ON courses.id=origin.course_id
       JOIN student_question_messages m ON m.question_id=q.id AND m.author_role='admin'
       $join
       WHERE q.student_id=?
       GROUP BY q.id
       ORDER BY message_id DESC LIMIT ?";
    $q=$db->prepare($sql);$args=$reads?[$studentId,$studentId,max(1,min(100,$limit))]:[$studentId,max(1,min(100,$limit))];$q->execute($args);
    $out=[];
    foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row){
        $out[]=[
          'kind'=>'answer','title'=>(string)$row['title'],
          'course'=>(string)($row['course_title']?:$row['cohort_title']),
          'cohort'=>(string)$row['cohort_title'],
          'href'=>'/aluno/duvidas.php?cohort='.rawurlencode((string)$row['cohort_uuid']).'&id='.(int)$row['question_id'],
          'at'=>(string)$row['occurred_at'],
          'unread'=>(int)$row['message_id']>(int)$row['last_seen_message_id'],
          'key'=>'question:'.(int)$row['question_id'],
        ];
    }
    return $out;
}
function student_home_mark_question_read(PDO $db,int $studentId,int $questionId): void {
    if($studentId<1||$questionId<1||!student_home_notices_table($db))return;
    // Only the question author with an active enrollment may acknowledge admin replies.
    $q=$db->prepare("SELECT MAX(m.id) FROM student_questions q
        JOIN course_enrollments e ON e.cohort_id=q.cohort_id AND e.student_id=? AND e.status='active'
        JOIN course_cohorts c ON c.id=q.cohort_id AND c.status!='archived'
        JOIN student_question_messages m ON m.question_id=q.id AND m.author_role='admin'
        WHERE q.id=? AND q.student_id=?");
    $q->execute([$studentId,$questionId,$studentId]);$messageId=(int)$q->fetchColumn();
    if($messageId<1)return;
    $db->prepare("INSERT INTO student_home_notice_reads(student_id,question_id,message_id,seen_at)
        VALUES(?,?,?,?)
        ON CONFLICT(student_id,question_id) DO UPDATE SET
        message_id=MAX(student_home_notice_reads.message_id,excluded.message_id),
        seen_at=excluded.seen_at")->execute([$studentId,$questionId,$messageId,utc_now()]);
}
function student_home_feedback_notices(PDO $db,int $studentId): array {
    $out=[];
    foreach(student_feedback_pending_for_student($db,$studentId,null,12) as $row){
       $state=student_feedback_state($row);
       $out[]=['kind'=>'feedback','title'=>(string)$row['title'],'course'=>(string)($row['public_title']??''),
         'cohort'=>(string)$row['cohort_title'],
         'href'=>'/aluno/teste.php?id='.(int)$row['id'].'&view=review',
         'at'=>(string)($row['feedback_at']??$row['updated_at']??''),
         'unread'=>(bool)$state['unseen'],'action'=>(bool)$state['requires_action'],
         'key'=>'feedback:'.(int)$row['id']];
    }
    return $out;
}
function student_home_live_notices(PDO $db,int $studentId): array {
    $notices=[...student_home_question_notices($db,$studentId),...student_home_feedback_notices($db,$studentId)];
    usort($notices,static function(array $a,array $b): int {
        $attention=(int)($b['unread']||($b['action']??false))<=>(int)($a['unread']||($a['action']??false));
        return $attention!==0?$attention:(strcmp((string)$b['at'],(string)$a['at'])?:strcmp($a['key'],$b['key']));
    });
    return $notices;
}
function student_home_public_offers(PDO $db,int $studentId,int $limit=2): array {
    // The editorial source is a published public CMS course page, not a fabricated campaign.
    if(!course_domain_available($db))return [];
    $q=$db->prepare("SELECT c.id,c.title,c.activity_id,p.id page_id,p.locale,p.slug,p.is_home,p.title page_title
      FROM courses c JOIN cms_pages p ON p.id=c.public_page_id
      WHERE c.status='active' AND p.status!='archived' AND p.access_level='public'
        AND p.published_document_json IS NOT NULL AND p.locale='pt-BR'
        AND NOT EXISTS(SELECT 1 FROM course_enrollments e
          JOIN course_cohorts cohort ON cohort.id=e.cohort_id
          WHERE e.student_id=? AND e.status='active' AND cohort.course_id=c.id)
      ORDER BY c.updated_at DESC,c.id DESC LIMIT ?");
    $q->bindValue(1,$studentId,PDO::PARAM_INT);$q->bindValue(2,max(1,min(8,$limit)),PDO::PARAM_INT);$q->execute();
    $out=[];
    foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row){
        $activity=activity_by_id($db,(int)$row['activity_id']);if(!$activity)continue;
        $out[]=['title'=>(string)$row['title'],'url'=>cms_page_url($activity,$row,(string)$row['locale'])];
    }
    return $out;
}
function student_home_live_priority(?array $meeting,array $notices,?int $now=null): array {
    $now??=time();
    $start=$meeting?strtotime((string)($meeting['date_iso']??'')):false;
    $soon=$meeting&&(!empty($meeting['in_window'])||($start!==false&&$start>=$now&&$start-$now<=48*3600));
    if($soon)return ['kind'=>'meeting','item'=>$meeting];
    foreach($notices as $notice)if(!empty($notice['unread'])||!empty($notice['action']))return ['kind'=>'notice','item'=>$notice];
    return [];
}
