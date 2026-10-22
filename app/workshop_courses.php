<?php
declare(strict_types=1);

function workshop_course_scope_available(PDO $db): bool {
    static $cache=[];$key=spl_object_id($db);if(array_key_exists($key,$cache))return $cache[$key];
    $columns=$db->query('PRAGMA table_info(course_cohorts)')->fetchAll(PDO::FETCH_ASSOC);$names=array_map(static fn(array $row): string=>(string)$row['name'],$columns);
    return $cache[$key]=in_array('workshop_page_id',$names,true);
}
function workshop_lesson_scope_available(PDO $db): bool {
    static $cache=[];$key=spl_object_id($db);if(array_key_exists($key,$cache))return $cache[$key];
    $columns=$db->query('PRAGMA table_info(course_lessons)')->fetchAll(PDO::FETCH_ASSOC);$names=array_map(static fn(array $row): string=>(string)$row['name'],$columns);
    return $cache[$key]=in_array('workshop_page_id',$names,true);
}
function workshop_course_page(PDO $db,int $workshopPageId): array {
    $page=cms_page_by_id($db,$workshopPageId)??throw new RuntimeException('Workshop não encontrado.');
    $root=cms_page_workshop_root($db,$page);if(!$root||(int)$root['id']!==$workshopPageId)throw new RuntimeException('A página selecionada não é a raiz do workshop.');
    return $root;
}
function workshop_course_cohorts(PDO $db,int $workshopPageId): array {
    if(!workshop_course_scope_available($db))return [];
    $q=$db->prepare("SELECT * FROM course_cohorts WHERE workshop_page_id=? AND status!='archived' ORDER BY is_registration_default DESC,id DESC");$q->execute([$workshopPageId]);return $q->fetchAll();
}
function workshop_course_default_cohort(PDO $db,int $workshopPageId,bool $create=true): ?array {
    $page=workshop_course_page($db,$workshopPageId);
    if(!workshop_course_scope_available($db))return $create?course_default_cohort($db,(int)$page['activity_id'],true):course_default_cohort($db,(int)$page['activity_id'],false);
    $q=$db->prepare("SELECT * FROM course_cohorts WHERE workshop_page_id=? AND is_registration_default=1 AND status!='archived' ORDER BY id DESC LIMIT 1");$q->execute([$workshopPageId]);$row=$q->fetch()?:null;if($row||!$create)return $row;
    $now=utc_now();$slug='turma-atual';$n=2;while(true){$check=$db->prepare('SELECT 1 FROM course_cohorts WHERE workshop_page_id=? AND slug=?');$check->execute([$workshopPageId,$slug]);if(!$check->fetchColumn())break;$slug='turma-atual-'.$n++;}
    $db->prepare("INSERT INTO course_cohorts(cohort_uuid,activity_id,workshop_page_id,title,slug,status,is_registration_default,created_at,updated_at) VALUES(?,?,?,?,?,'active',1,?,?)")
        ->execute([student_uuid(),(int)$page['activity_id'],$workshopPageId,'Turma atual',$slug,$now,$now]);
    $id=(int)$db->lastInsertId();workshop_course_sync_release_rows($db,$id,$workshopPageId);return course_cohort_by_id($db,$id);
}
function workshop_course_create_cohort(PDO $db,int $workshopPageId,string $title,string $slug='',bool $makeDefault=false): array {
    $page=workshop_course_page($db,$workshopPageId);if(!workshop_course_scope_available($db))return course_create_cohort($db,(int)$page['activity_id'],$title,$slug,$makeDefault);
    $title=trim($title);if($title==='')throw new RuntimeException('Informe o nome da turma.');$slug=course_cohort_slug($slug!==''?$slug:$title);$now=utc_now();
    $db->beginTransaction();try{
        if($makeDefault)$db->prepare('UPDATE course_cohorts SET is_registration_default=0,updated_at=? WHERE workshop_page_id=?')->execute([$now,$workshopPageId]);
        $db->prepare("INSERT INTO course_cohorts(cohort_uuid,activity_id,workshop_page_id,title,slug,status,is_registration_default,created_at,updated_at) VALUES(?,?,?,?,?,'active',?,?,?)")
            ->execute([student_uuid(),(int)$page['activity_id'],$workshopPageId,$title,$slug,$makeDefault?1:0,$now,$now]);
        $id=(int)$db->lastInsertId();
        if(!$makeDefault&&!workshop_course_default_cohort($db,$workshopPageId,false))$db->prepare('UPDATE course_cohorts SET is_registration_default=1 WHERE id=?')->execute([$id]);
        workshop_course_sync_release_rows($db,$id,$workshopPageId);$db->commit();return course_cohort_by_id($db,$id)??throw new RuntimeException('cohort_create_failed');
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function workshop_course_set_default_cohort(PDO $db,int $workshopPageId,int $cohortId): void {
    $cohort=course_cohort_by_id($db,$cohortId);if(!$cohort||(int)($cohort['workshop_page_id']??0)!==$workshopPageId||$cohort['status']==='archived')throw new RuntimeException('Turma inválida para este workshop.');
    $now=utc_now();$db->beginTransaction();try{$db->prepare('UPDATE course_cohorts SET is_registration_default=0,updated_at=? WHERE workshop_page_id=?')->execute([$now,$workshopPageId]);$db->prepare('UPDATE course_cohorts SET is_registration_default=1,updated_at=? WHERE id=?')->execute([$now,$cohortId]);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function workshop_course_lessons(PDO $db,int $workshopPageId): array {
    if(!workshop_lesson_scope_available($db)){$page=workshop_course_page($db,$workshopPageId);return course_lessons($db,(int)$page['activity_id']);}
    $q=$db->prepare('SELECT * FROM course_lessons WHERE workshop_page_id=? ORDER BY sort_order,id');$q->execute([$workshopPageId]);return $q->fetchAll();
}
function workshop_course_add_lesson(PDO $db,int $workshopPageId,string $title,string $key=''): array {
    $page=workshop_course_page($db,$workshopPageId);if(!workshop_lesson_scope_available($db))return course_add_lesson($db,(int)$page['activity_id'],$title,$key);
    $title=trim($title);if($title==='')throw new RuntimeException('Informe o nome da aula.');$key=activity_slug($key!==''?$key:$title);if($key==='')$key='aula-'.substr(bin2hex(random_bytes(4)),0,8);
    $q=$db->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM course_lessons WHERE workshop_page_id=?');$q->execute([$workshopPageId]);$sort=(int)$q->fetchColumn();$now=utc_now();
    $db->prepare('INSERT INTO course_lessons(activity_id,workshop_page_id,lesson_key,title,sort_order,created_at,updated_at) VALUES(?,?,?,?,?,?,?)')->execute([(int)$page['activity_id'],$workshopPageId,$key,$title,$sort,$now,$now]);$id=(int)$db->lastInsertId();
    foreach(workshop_course_cohorts($db,$workshopPageId) as $cohort)$db->prepare('INSERT OR IGNORE INTO cohort_lesson_releases(cohort_id,lesson_id,released_at,created_at,updated_at) VALUES(?,?,NULL,?,?)')->execute([(int)$cohort['id'],$id,$now,$now]);
    $s=$db->prepare('SELECT * FROM course_lessons WHERE id=?');$s->execute([$id]);return $s->fetch()?:throw new RuntimeException('lesson_create_failed');
}
function workshop_course_sync_release_rows(PDO $db,int $cohortId,int $workshopPageId): void {
    $now=utc_now();$q=$db->prepare('SELECT id FROM course_lessons WHERE workshop_page_id=?');$q->execute([$workshopPageId]);$insert=$db->prepare('INSERT OR IGNORE INTO cohort_lesson_releases(cohort_id,lesson_id,released_at,created_at,updated_at) VALUES(?,?,NULL,?,?)');foreach($q->fetchAll(PDO::FETCH_COLUMN) as $id)$insert->execute([$cohortId,(int)$id,$now,$now]);
}
function workshop_course_lesson_release_rows(PDO $db,int $cohortId,int $workshopPageId): array {
    workshop_course_sync_release_rows($db,$cohortId,$workshopPageId);$q=$db->prepare('SELECT l.*,r.released_at FROM course_lessons l JOIN cohort_lesson_releases r ON r.lesson_id=l.id AND r.cohort_id=? WHERE l.workshop_page_id=? ORDER BY l.sort_order,l.id');$q->execute([$cohortId,$workshopPageId]);return $q->fetchAll();
}
function workshop_course_enrollment_for_student(PDO $db,int $studentId,int $workshopPageId,string $cohortUuid=''): ?array {
    if(!workshop_course_scope_available($db)){$page=workshop_course_page($db,$workshopPageId);return student_account_enrollment_for_activity($db,$studentId,(int)$page['activity_id'],$cohortUuid);}
    $sql="SELECT e.*,c.title cohort_title,c.cohort_uuid,c.activity_id,c.workshop_page_id,c.status cohort_status FROM course_enrollments e JOIN course_cohorts c ON c.id=e.cohort_id WHERE e.student_id=? AND c.workshop_page_id=? AND e.status='active' AND c.status!='archived'";$args=[$studentId,$workshopPageId];
    if($cohortUuid!==''){$sql.=' AND c.cohort_uuid=?';$args[]=$cohortUuid;}$sql.=' ORDER BY e.confirmed_at DESC,e.id DESC LIMIT 1';$q=$db->prepare($sql);$q->execute($args);return $q->fetch()?:null;
}
function workshop_course_submission_root(PDO $db,array $submission): ?array {
    $pageId=(int)($submission['page_id']??0);if($pageId<1)return null;$page=cms_page_by_id($db,$pageId);if(!$page)return null;
    if((int)$page['activity_id']!==(int)($submission['activity_id']??0))throw new RuntimeException('submission_page_scope_invalid');
    return cms_page_workshop_root($db,$page);
}
