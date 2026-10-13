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
