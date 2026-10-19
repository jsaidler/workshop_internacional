<?php
declare(strict_types=1);

/** Agenda de encontros; course_lessons é a única identidade pedagógica. */
function course_agenda_safe_meeting_url(mixed $value): string {
    $url=trim((string)$value);
    if($url==='' || !filter_var($url,FILTER_VALIDATE_URL))return '';
    return strtolower((string)parse_url($url,PHP_URL_SCHEME))==='https'?$url:'';
}
/** Uma ocorrência ainda está vigente apenas no período agendado explicitamente.
 * Não inferir presença, realização ou duração ausente.
 */
function course_agenda_partition_meetings(array $meetings,?int $now=null): array {
    $now??=time();$upcoming=[];$history=[];
    foreach($meetings as $meeting){
        $starts=strtotime((string)($meeting['meeting_starts_at']??''));
        $minutes=max(0,(int)($meeting['meeting_duration_minutes']??0));
        $inWindow=$starts!==false && $starts<=$now && $minutes>0 && ($starts+$minutes*60)>$now;
        if(($meeting['meeting_status']??'')==='scheduled' && $starts!==false && ($starts>=$now||$inWindow))$upcoming[]=$meeting;
        else $history[]=$meeting;
    }
    usort($upcoming,static fn(array $a,array $b):int=>strcmp((string)$a['meeting_starts_at'],(string)$b['meeting_starts_at']));
    usort($history,static fn(array $a,array $b):int=>strcmp((string)$b['meeting_starts_at'],(string)$a['meeting_starts_at']));
    return ['upcoming'=>$upcoming,'history'=>$history];
}
function course_agenda_timezone(string $name): DateTimeZone {
    try {return new DateTimeZone(trim($name));}
    catch(Throwable) {throw new RuntimeException('Escolha um fuso horário válido.');}
}
function course_agenda_timezone_label(string $timezone): string {
    if($timezone==='America/Sao_Paulo')return 'Brasília';
    $segments=explode('/',$timezone);
    return str_replace('_',' ',(string)end($segments));
}
function course_agenda_to_utc(string $local,string $timezone): ?string {
    $local=trim($local);
    if($local==='')return null;
    $zone=course_agenda_timezone($timezone);
    $dt=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$local,$zone);
    if(!$dt || $dt->format('Y-m-d\TH:i')!==$local)throw new RuntimeException('Informe data e hora válidas para o fuso escolhido.');
    return $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
}
function course_agenda_local(?string $utc,string $timezone='America/Sao_Paulo'): string {
    if(!$utc)return '';
    try{return (new DateTimeImmutable($utc))->setTimezone(course_agenda_timezone($timezone))->format('Y-m-d\TH:i');}
    catch(Throwable){return '';}
}
function course_agenda_when(?string $utc,string $timezone='America/Sao_Paulo'): string {
    if(!$utc)return 'Sem data definida';
    try{
        $local=(new DateTimeImmutable($utc))->setTimezone(course_agenda_timezone($timezone));
        $place=course_agenda_timezone_label($timezone);
        return $local->format('d/m/Y · H:i').' ('.$place.')';
    }
    catch(Throwable){return 'Data indisponível';}
}
function course_agenda_capacity(PDO $db,int $cohortId): array {
    $q=$db->prepare('SELECT cc.*,c.agenda_default_capacity FROM course_cohorts cc JOIN courses c ON c.id=cc.course_id WHERE cc.id=?');
    $q->execute([$cohortId]);$cohort=$q->fetch()?:throw new RuntimeException('Turma não encontrada.');
    $limit=(int)($cohort['agenda_capacity']??$cohort['agenda_default_capacity']??10);
    $q=$db->prepare("SELECT COUNT(*) FROM (SELECT student_id FROM course_enrollments WHERE cohort_id=? AND status='active' UNION SELECT student_id FROM course_listener_reservations WHERE cohort_id=? AND status='confirmed')");
    $q->execute([$cohortId,$cohortId]);$occupied=(int)$q->fetchColumn();
    $regular=$db->prepare("SELECT COUNT(DISTINCT student_id) FROM course_enrollments WHERE cohort_id=? AND status='active'");
    $regular->execute([$cohortId]);
    return ['limit'=>$limit,'occupied'=>$occupied,'regular'=>(int)$regular->fetchColumn(),'remaining'=>max(0,$limit-$occupied),'excess'=>max(0,$occupied-$limit)];
}
function course_agenda_can_listen(PDO $db,int $studentId,int $cohortId): bool {
    $q=$db->prepare("SELECT 1 FROM course_cohorts target JOIN courses course ON course.id=target.course_id JOIN student_users su ON su.id=? AND su.status='active' WHERE target.id=? AND target.status='active' AND course.status!='archived' AND EXISTS(SELECT 1 FROM course_enrollments e JOIN course_cohorts origin ON origin.id=e.cohort_id WHERE e.student_id=? AND e.status='active' AND origin.course_id=target.course_id AND origin.id!=target.id) AND NOT EXISTS(SELECT 1 FROM course_enrollments own WHERE own.student_id=? AND own.cohort_id=target.id AND own.status='active')");
    $q->execute([$studentId,$cohortId,$studentId,$studentId]);return (bool)$q->fetchColumn();
}
function course_agenda_window_open(array $cohort,?int $now=null): bool {
    $now??=time();$close=strtotime((string)($cohort['agenda_enrollment_closes_at']??''));$open=strtotime((string)($cohort['agenda_listeners_open_at']??''));
    return $close!==false && $open!==false && $close<=$now && $open<=$now;
}
function course_agenda_with_lock(PDO $db,callable $work): mixed {
    // PDO::inTransaction() não reconhece de modo portátil BEGIN IMMEDIATE enviado via exec.
    // Rastrear a seção crítica da Agenda permite chamadas aninhadas sem segunda transação.
    static $held=null;
    if($held===null)$held=new WeakMap();
    $owned=!$db->inTransaction() && empty($held[$db]);
    if($owned){$db->exec('BEGIN IMMEDIATE');$held[$db]=true;}
    try{
        $result=$work();
        if($owned)$db->exec('COMMIT');
        return $result;
    }catch(Throwable $e){
        if($owned)$db->exec('ROLLBACK');
        throw $e;
    }finally{
        if($owned)unset($held[$db]);
    }
}
/** Apenas vagas efetivas, após o término da janela comercial. */
function course_agenda_reconcile_cohort(PDO $db,int $cohortId): void {
    course_agenda_with_lock($db,static function() use($db,$cohortId): void {
        $q=$db->prepare('SELECT * FROM course_cohorts WHERE id=?');$q->execute([$cohortId]);$cohort=$q->fetch();
        if(!$cohort || (string)$cohort['status']!=='active')return;
        $queue=$db->prepare("SELECT * FROM course_listener_reservations WHERE cohort_id=? AND status IN ('waiting','confirmed') ORDER BY requested_at,id");
        $queue->execute([$cohortId]);$rows=$queue->fetchAll();$now=utc_now();
        foreach($rows as $row)if(!course_agenda_can_listen($db,(int)$row['student_id'],$cohortId)){
            $db->prepare("UPDATE course_listener_reservations SET status='cancelled',cancelled_at=?,updated_at=? WHERE id=?")->execute([$now,$now,(int)$row['id']]);
        }
        if(!course_agenda_window_open($cohort))return;
        foreach($rows as $row){
            if($row['status']!=='waiting' || !course_agenda_can_listen($db,(int)$row['student_id'],$cohortId))continue;
            if(course_agenda_capacity($db,$cohortId)['remaining']<1)break;
            $db->prepare("UPDATE course_listener_reservations SET status='confirmed',confirmed_at=?,cancelled_at=NULL,updated_at=? WHERE id=? AND status='waiting'")->execute([$now,$now,(int)$row['id']]);
        }
    });
}
function course_agenda_reconcile_student(PDO $db,int $studentId): void {
    if(!$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='course_listener_reservations'")->fetchColumn())return;
    $q=$db->prepare("SELECT DISTINCT cohort_id FROM course_listener_reservations WHERE student_id=? AND status IN ('waiting','confirmed')");
    $q->execute([$studentId]);
    foreach($q->fetchAll(PDO::FETCH_COLUMN) as $id)course_agenda_reconcile_cohort($db,(int)$id);
}
function course_agenda_request(PDO $db,int $studentId,int $cohortId): array {
    return course_agenda_with_lock($db,static function() use($db,$studentId,$cohortId): array {
        if(!course_agenda_can_listen($db,$studentId,$cohortId))throw new RuntimeException('Esta turma não está disponível para retorno como ouvinte.');
        $future=$db->prepare("SELECT 1 FROM cohort_lesson_releases WHERE cohort_id=? AND meeting_starts_at>? AND meeting_status='scheduled' LIMIT 1");
        $future->execute([$cohortId,gmdate('Y-m-d\\TH:i:s\\Z')]);
        if(!$future->fetchColumn())throw new RuntimeException('Esta turma ainda não possui encontros futuros disponíveis.');
        $q=$db->prepare('SELECT * FROM course_listener_reservations WHERE student_id=? AND cohort_id=?');$q->execute([$studentId,$cohortId]);$prior=$q->fetch();
        $now=utc_now();
        if(!$prior || $prior['status']==='cancelled'){
            $db->prepare("INSERT INTO course_listener_reservations(student_id,cohort_id,status,requested_at,confirmed_at,cancelled_at,updated_at) VALUES(?,?,'waiting',?,NULL,NULL,?) ON CONFLICT(student_id,cohort_id) DO UPDATE SET status='waiting',requested_at=excluded.requested_at,confirmed_at=NULL,cancelled_at=NULL,updated_at=excluded.updated_at")->execute([$studentId,$cohortId,$now,$now]);
        }
        course_agenda_reconcile_cohort($db,$cohortId);
        $q->execute([$studentId,$cohortId]);return $q->fetch()?:throw new RuntimeException('Não foi possível registrar a solicitação.');
    });
}
function course_agenda_cancel(PDO $db,int $studentId,int $cohortId): void {
    course_agenda_with_lock($db,static function() use($db,$studentId,$cohortId): void {
        $now=utc_now();
        $db->prepare("UPDATE course_listener_reservations SET status='cancelled',cancelled_at=?,updated_at=? WHERE student_id=? AND cohort_id=? AND status IN ('waiting','confirmed')")->execute([$now,$now,$studentId,$cohortId]);
        course_agenda_reconcile_cohort($db,$cohortId);
    });
}
function course_agenda_reservations(PDO $db,int $cohortId): array {
    $q=$db->prepare("SELECT r.*,u.name student_name FROM course_listener_reservations r JOIN student_users u ON u.id=r.student_id WHERE r.cohort_id=? AND r.status IN ('waiting','confirmed') ORDER BY CASE r.status WHEN 'confirmed' THEN 0 ELSE 1 END,r.requested_at,r.id");
    $q->execute([$cohortId]);return $q->fetchAll();
}
function course_agenda_save_settings(PDO $db,int $courseId,int $cohortId,array $input): void {
    $course=course_by_id($db,$courseId)??throw new RuntimeException('Curso não encontrado.');
    $default=filter_var($input['agenda_default_capacity']??10,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000]]);
    if($default===false)throw new RuntimeException('A capacidade padrão precisa ser um número positivo.');
    $override=trim((string)($input['agenda_capacity']??''));
    $capacity=$override===''?null:filter_var($override,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000]]);
    if($capacity===false)throw new RuntimeException('A capacidade da turma precisa ser um número positivo.');
    $cohort=admin_course_cohort($db,$courseId,$cohortId,false)??throw new RuntimeException('Turma inválida.');
    $timezone=(string)($input['agenda_timezone']??'America/Sao_Paulo');
    $close=course_agenda_to_utc((string)($input['agenda_enrollment_closes_at']??''),$timezone);
    $open=course_agenda_to_utc((string)($input['agenda_listeners_open_at']??''),$timezone);
    course_agenda_with_lock($db,static function() use($db,$courseId,$cohortId,$default,$capacity,$close,$open): void {
        $now=utc_now();$db->prepare('UPDATE courses SET agenda_default_capacity=?,updated_at=? WHERE id=?')->execute([$default,$now,$courseId]);
        $db->prepare('UPDATE course_cohorts SET agenda_capacity=?,agenda_enrollment_closes_at=?,agenda_listeners_open_at=?,updated_at=? WHERE id=? AND course_id=?')->execute([$capacity,$close,$open,$now,$cohortId,$courseId]);
        course_agenda_reconcile_cohort($db,$cohortId);
    });
}
function course_agenda_save_meeting(PDO $db,int $courseId,int $cohortId,int $lessonId,array $input): void {
    $cohort=admin_course_cohort($db,$courseId,$cohortId,false)??throw new RuntimeException('Turma inválida.');
    $q=$db->prepare('SELECT 1 FROM course_lessons WHERE id=? AND course_id=?');$q->execute([$lessonId,$courseId]);if(!$q->fetchColumn())throw new RuntimeException('Aula inválida para este curso.');
    $timezone=trim((string)($input['meeting_timezone']??'America/Sao_Paulo'));course_agenda_timezone($timezone);
    $starts=course_agenda_to_utc((string)($input['meeting_starts_at']??''),$timezone);
    $duration=trim((string)($input['meeting_duration_minutes']??''));$minutes=$duration===''?null:filter_var($duration,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1440]]);
    if($minutes===false)throw new RuntimeException('A duração deve estar entre 1 e 1440 minutos.');
    $url=trim((string)($input['meeting_url']??''));
    if(strlen($url)>2000 || ($url!=='' && (!filter_var($url,FILTER_VALIDATE_URL) || !str_starts_with(strtolower($url),'https://'))))throw new RuntimeException('Informe um link HTTPS válido para o encontro.');
    $notes=trim((string)($input['meeting_notes']??''));
    if(mb_strlen($notes)>2000)throw new RuntimeException('As instruções de acesso devem ter até 2000 caracteres.');
    $state=(string)($input['meeting_status']??'scheduled');
    if(!in_array($state,['scheduled','completed','cancelled'],true) || ($state==='completed' && !$starts))throw new RuntimeException('Estado do encontro inválido.');
    course_sync_release_rows_for_course($db,$cohortId,$courseId);
    $db->prepare('UPDATE cohort_lesson_releases SET meeting_starts_at=?,meeting_timezone=?,meeting_duration_minutes=?,meeting_url=?,meeting_notes=?,meeting_status=?,updated_at=? WHERE cohort_id=? AND lesson_id=?')->execute([$starts,$timezone,$minutes,$url,$notes,$state,utc_now(),$cohortId,$lessonId]);
}
function course_agenda_save_lesson_configuration(PDO $db,int $courseId,int $cohortId,int $lessonId,array $input): void {
    course_agenda_with_lock($db,static function() use($db,$courseId,$cohortId,$lessonId,$input): void {
        course_agenda_save_meeting($db,$courseId,$cohortId,$lessonId,$input);
        $mode=(string)($input['release_mode']??'keep');
        if($mode==='keep')return;
        if(!in_array($mode,['schedule','release','block'],true))throw new RuntimeException('Decisão de liberação inválida.');
        $value=null;
        if($mode==='release')$value=utc_now();
        elseif($mode==='schedule'){
            $value=course_agenda_to_utc((string)($input['release_scheduled_at']??''),(string)($input['meeting_timezone']??'America/Sao_Paulo'));
            if(!$value)throw new RuntimeException('Informe quando o material será liberado.');
        }
        $db->prepare('UPDATE cohort_lesson_releases SET released_at=?,updated_at=? WHERE cohort_id=? AND lesson_id=?')->execute([$value,utc_now(),$cohortId,$lessonId]);
    });
}
function course_agenda_student_meetings(PDO $db,int $studentId): array {
    $sql="SELECT c.id cohort_id,c.title cohort_title,cr.title course_title,l.id lesson_id,l.title lesson_title,r.meeting_starts_at,r.meeting_timezone,r.meeting_duration_minutes,r.meeting_url,r.meeting_notes,r.meeting_status,CASE WHEN EXISTS(SELECT 1 FROM course_enrollments e WHERE e.cohort_id=c.id AND e.student_id=? AND e.status='active') THEN 'regular' ELSE 'listener' END participation FROM course_cohorts c JOIN courses cr ON cr.id=c.course_id JOIN course_lessons l ON l.course_id=c.course_id JOIN cohort_lesson_releases r ON r.cohort_id=c.id AND r.lesson_id=l.id JOIN student_users u ON u.id=? AND u.status='active' WHERE c.status!='archived' AND r.meeting_starts_at IS NOT NULL AND (EXISTS(SELECT 1 FROM course_enrollments e WHERE e.cohort_id=c.id AND e.student_id=? AND e.status='active') OR (EXISTS(SELECT 1 FROM course_listener_reservations lr WHERE lr.cohort_id=c.id AND lr.student_id=? AND lr.status='confirmed') AND EXISTS(SELECT 1 FROM course_enrollments e JOIN course_cohorts oc ON oc.id=e.cohort_id WHERE e.student_id=? AND e.status='active' AND oc.course_id=c.course_id AND oc.id!=c.id))) ORDER BY r.meeting_starts_at,c.id,l.sort_order,l.id";
    $q=$db->prepare($sql);$q->execute([$studentId,$studentId,$studentId,$studentId,$studentId]);return $q->fetchAll();
}
function course_agenda_offer_schedule(PDO $db,int $cohortId): array {
    $q=$db->prepare("SELECT l.title lesson_title,r.meeting_starts_at,r.meeting_timezone,r.meeting_duration_minutes FROM course_cohorts c JOIN course_lessons l ON l.course_id=c.course_id JOIN cohort_lesson_releases r ON r.cohort_id=c.id AND r.lesson_id=l.id WHERE c.id=? AND r.meeting_starts_at>? AND r.meeting_status='scheduled' ORDER BY r.meeting_starts_at,l.sort_order,l.id");
    $q->execute([$cohortId,gmdate('Y-m-d\\TH:i:s\\Z')]);return $q->fetchAll();
}
function course_agenda_student_offers(PDO $db,int $studentId): array {
    $sql="SELECT c.id cohort_id,c.title cohort_title,c.agenda_enrollment_closes_at,c.agenda_listeners_open_at,cr.title course_title,(SELECT MIN(r.meeting_starts_at) FROM cohort_lesson_releases r WHERE r.cohort_id=c.id AND r.meeting_starts_at>? AND r.meeting_status='scheduled') next_meeting,(SELECT COUNT(*) FROM cohort_lesson_releases r WHERE r.cohort_id=c.id AND r.meeting_starts_at>? AND r.meeting_status='scheduled') future_meetings,lr.status reservation_status FROM course_cohorts c JOIN courses cr ON cr.id=c.course_id LEFT JOIN course_listener_reservations lr ON lr.cohort_id=c.id AND lr.student_id=? WHERE c.status='active' AND cr.status!='archived' AND EXISTS(SELECT 1 FROM course_enrollments e JOIN course_cohorts own ON own.id=e.cohort_id WHERE e.student_id=? AND e.status='active' AND own.course_id=c.course_id AND own.id!=c.id) AND NOT EXISTS(SELECT 1 FROM course_enrollments own WHERE own.student_id=? AND own.cohort_id=c.id AND own.status='active') AND EXISTS(SELECT 1 FROM cohort_lesson_releases r WHERE r.cohort_id=c.id AND r.meeting_starts_at>? AND r.meeting_status='scheduled') ORDER BY next_meeting,c.id";
    $now=gmdate('Y-m-d\TH:i:s\Z');$q=$db->prepare($sql);$q->execute([$now,$now,$studentId,$studentId,$studentId,$now]);$rows=$q->fetchAll();
    foreach($rows as &$row){$row['capacity']=course_agenda_capacity($db,(int)$row['cohort_id']);$row['schedule']=course_agenda_offer_schedule($db,(int)$row['cohort_id']);}unset($row);
    return $rows;
}
