<?php
declare(strict_types=1);

/**
 * Administrative course/cohort workspace service.
 * Keeps admin task mutations scoped to the canonical course domain and avoids
 * the legacy activity/default-cohort semantics that still exist for migration.
 */
function admin_course_cohort(PDO $db,int $courseId,int $cohortId,bool $includeArchived=true): ?array {
    if($courseId<1||$cohortId<1)return null;
    $sql='SELECT * FROM course_cohorts WHERE id=? AND course_id=?';
    if(!$includeArchived)$sql.=" AND status!='archived'";
    $sql.=' LIMIT 1';
    $q=$db->prepare($sql);$q->execute([$cohortId,$courseId]);return $q->fetch()?:null;
}

function admin_course_update_cohort(PDO $db,int $courseId,int $cohortId,array $input): array {
    $course=course_by_id($db,$courseId)??throw new RuntimeException('Curso não encontrado.');
    $cohort=admin_course_cohort($db,$courseId,$cohortId,true)??throw new RuntimeException('Turma não encontrada neste curso.');
    $title=trim((string)($input['title']??$cohort['title']));if($title==='')throw new RuntimeException('Informe o nome da turma.');
    $startsAt=trim((string)($input['starts_at']??$cohort['starts_at']??''));$endsAt=trim((string)($input['ends_at']??$cohort['ends_at']??''));
    if($startsAt!==''&&strtotime($startsAt)===false)throw new RuntimeException('Data de início inválida.');
    if($endsAt!==''&&strtotime($endsAt)===false)throw new RuntimeException('Data de término inválida.');
    if($startsAt!==''&&$endsAt!==''&&strtotime($endsAt)<strtotime($startsAt))throw new RuntimeException('O término não pode ser anterior ao início.');
    $db->prepare('UPDATE course_cohorts SET title=?,starts_at=?,ends_at=?,is_registration_default=0,updated_at=? WHERE id=? AND course_id=?')->execute([$title,$startsAt?:null,$endsAt?:null,utc_now(),$cohortId,$courseId]);
    return admin_course_cohort($db,$courseId,$cohortId,true)??throw new RuntimeException('cohort_update_failed');
}

function admin_course_set_cohort_archived(PDO $db,int $courseId,int $cohortId,bool $archived): array {
    course_by_id($db,$courseId)??throw new RuntimeException('Curso não encontrado.');
    $cohort=admin_course_cohort($db,$courseId,$cohortId,true)??throw new RuntimeException('Turma não encontrada neste curso.');
    $status=$archived?'archived':'active';
    $db->prepare('UPDATE course_cohorts SET status=?,is_registration_default=0,updated_at=? WHERE id=? AND course_id=?')->execute([$status,utc_now(),$cohortId,$courseId]);
    return admin_course_cohort($db,$courseId,$cohortId,true)??throw new RuntimeException('cohort_status_failed');
}

function admin_course_update_lesson(PDO $db,int $courseId,int $lessonId,string $title): array {
    course_by_id($db,$courseId)??throw new RuntimeException('Curso não encontrado.');
    $title=trim($title);if($title==='')throw new RuntimeException('Informe o nome da aula.');
    $q=$db->prepare('SELECT * FROM course_lessons WHERE id=? AND course_id=? LIMIT 1');$q->execute([$lessonId,$courseId]);if(!$q->fetch())throw new RuntimeException('Aula não encontrada neste curso.');
    $db->prepare('UPDATE course_lessons SET title=?,updated_at=? WHERE id=? AND course_id=?')->execute([$title,utc_now(),$lessonId,$courseId]);
    $q->execute([$lessonId,$courseId]);return $q->fetch()?:throw new RuntimeException('lesson_update_failed');
}

function admin_course_move_lesson(PDO $db,int $courseId,int $lessonId,int $direction): void {
    if(!in_array($direction,[-1,1],true))throw new RuntimeException('Direção inválida.');
    $lessons=course_lessons_for_course($db,$courseId);$index=null;
    foreach($lessons as $i=>$lesson)if((int)$lesson['id']===$lessonId){$index=$i;break;}
    if($index===null)throw new RuntimeException('Aula não encontrada neste curso.');
    $otherIndex=$index+$direction;if(!isset($lessons[$otherIndex]))return;
    $current=$lessons[$index];$other=$lessons[$otherIndex];$now=utc_now();
    $db->beginTransaction();try{
        $db->prepare('UPDATE course_lessons SET sort_order=?,updated_at=? WHERE id=? AND course_id=?')->execute([(int)$other['sort_order'],$now,$lessonId,$courseId]);
        $db->prepare('UPDATE course_lessons SET sort_order=?,updated_at=? WHERE id=? AND course_id=?')->execute([(int)$current['sort_order'],$now,(int)$other['id'],$courseId]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}

function admin_course_lesson_material_impact(PDO $db,int $courseId): array {
    $q=$db->prepare("SELECT s.lesson_id,COUNT(*) section_count,COUNT(DISTINCT s.page_id) page_count FROM course_material_sections s JOIN course_material_pages m ON m.course_id=s.course_id AND m.page_id=s.page_id JOIN cms_pages p ON p.id=s.page_id WHERE s.course_id=? AND p.status!='archived' GROUP BY s.lesson_id");
    $q->execute([$courseId]);$out=[];foreach($q->fetchAll() as $row)$out[(int)$row['lesson_id']]=['sections'=>(int)$row['section_count'],'pages'=>(int)$row['page_count']];return $out;
}

function admin_course_lesson_material_items(PDO $db,int $courseId,int $lessonId): array {
    $q=$db->prepare("SELECT p.id page_id,p.title page_title,s.section_key FROM course_material_sections s JOIN course_material_pages m ON m.course_id=s.course_id AND m.page_id=s.page_id JOIN cms_pages p ON p.id=s.page_id WHERE s.course_id=? AND s.lesson_id=? AND p.status!='archived' ORDER BY m.sort_order,p.sort_order,p.id,s.section_key");
    $q->execute([$courseId,$lessonId]);$rows=$q->fetchAll();$labels=[];foreach($rows as &$row){$sections=course_material_sections_from_page($db,(int)$row['page_id']);$row['section_label']=$sections[(string)$row['section_key']]??(string)$row['section_key'];}unset($row);return $rows;
}

function admin_course_cohort_stats(PDO $db,int $courseId,int $cohortId): array {
    $cohort=admin_course_cohort($db,$courseId,$cohortId,true)??throw new RuntimeException('Turma não encontrada neste curso.');
    $q=$db->prepare("SELECT COUNT(*) students,COALESCE(SUM(CASE WHEN u.activated_at IS NULL THEN 1 ELSE 0 END),0) activation_pending FROM course_enrollments e JOIN student_users u ON u.id=e.student_id WHERE e.cohort_id=? AND e.status='active'");$q->execute([$cohortId]);$students=$q->fetch()?:[];
    $q=$db->prepare("SELECT COUNT(*) FROM student_questions WHERE cohort_id=? AND status='open'");$q->execute([$cohortId]);$questions=(int)$q->fetchColumn();
    $q=$db->prepare("SELECT COUNT(*) FROM student_tests WHERE cohort_id=? AND status IN ('submitted','needs_revision')");$q->execute([$cohortId]);$tests=(int)$q->fetchColumn();
    $releases=course_lesson_release_rows_for_course($db,$cohortId,$courseId);$releaseCounts=['released'=>0,'scheduled'=>0,'blocked'=>0];foreach($releases as $row){$state=cms_access_lesson_release_state(is_string($row['released_at']??null)?$row['released_at']:null);$releaseCounts[$state]++;}
    return ['students'=>(int)($students['students']??0),'activation_pending'=>(int)($students['activation_pending']??0),'questions'=>$questions,'tests'=>$tests,'lessons'=>count($releases),'release'=>$releaseCounts,'cohort'=>$cohort];
}

function admin_course_attention(PDO $db,int $courseId): array {
    $course=course_by_id($db,$courseId)??throw new RuntimeException('Curso não encontrado.');
    $q=$db->prepare("SELECT COALESCE(SUM(CASE WHEN status!='archived' AND payment_status!='paid' AND status!='converted' THEN 1 ELSE 0 END),0) pending_payment,COALESCE(SUM(CASE WHEN status!='archived' AND (payment_status='paid' OR status='converted') AND cohort_id IS NULL THEN 1 ELSE 0 END),0) unassigned FROM cms_form_submissions WHERE course_id=?");$q->execute([$courseId]);$registrations=$q->fetch()?:[];
    $q=$db->prepare("SELECT COUNT(*) FROM student_questions sq JOIN course_cohorts cc ON cc.id=sq.cohort_id WHERE cc.course_id=? AND sq.status='open'");$q->execute([$courseId]);$questions=(int)$q->fetchColumn();
    $q=$db->prepare("SELECT COUNT(*) FROM student_tests t JOIN course_cohorts cc ON cc.id=t.cohort_id WHERE cc.course_id=? AND t.status IN ('submitted','needs_revision')");$q->execute([$courseId]);$tests=(int)$q->fetchColumn();
    return ['pending_payment'=>(int)($registrations['pending_payment']??0),'unassigned'=>(int)($registrations['unassigned']??0),'questions'=>$questions,'tests'=>$tests];
}

function admin_course_date_label(?string $value): string {if(!$value)return '—';$ts=strtotime($value);return $ts===false?$value:date('d/m/Y',$ts);}
function admin_course_cohort_period(array $cohort): string {return admin_course_date_label($cohort['starts_at']??null).' → '.admin_course_date_label($cohort['ends_at']??null);}
function admin_course_cohort_phase(array $cohort,?int $now=null): string {
    if((string)($cohort['status']??'')==='archived')return 'Arquivada';$now??=time();$start=!empty($cohort['starts_at'])?strtotime((string)$cohort['starts_at']):false;$end=!empty($cohort['ends_at'])?strtotime((string)$cohort['ends_at']):false;
    if($start!==false&&$start>$now)return 'Próxima';if($end!==false&&$end<$now)return 'Concluída';return 'Em andamento';
}
