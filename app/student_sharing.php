<?php
declare(strict_types=1);

function student_test_visibility_values(): array {return ['private','cohort','course'];}
function student_test_visibility_label(string $visibility): string {return match($visibility){'cohort'=>'Turma','course'=>'Curso',default=>'Privado'};}
function student_test_set_visibility(PDO $db,int $testId,int $studentId,string $visibility): void {if(!in_array($visibility,student_test_visibility_values(),true))throw new RuntimeException('Visibilidade inválida.');$q=$db->prepare('UPDATE student_tests SET visibility=?,updated_at=? WHERE id=? AND student_id=?');$q->execute([$visibility,gmdate('c'),$testId,$studentId]);if($q->rowCount()!==1)throw new RuntimeException('Teste não encontrado.');}
function student_test_accessible_to_student(PDO $db,int $testId,int $studentId): ?array {
    $courseScoped=course_domain_available($db);$workshopScoped=workshop_course_scope_available($db);
    $scopeSelect=$courseScoped?',c.course_id':($workshopScoped?',c.workshop_page_id':'');
    if($courseScoped)$scopePredicate='((c.course_id IS NOT NULL AND ec.course_id=c.course_id) OR (c.course_id IS NULL AND c.workshop_page_id IS NOT NULL AND ec.workshop_page_id=c.workshop_page_id) OR (c.course_id IS NULL AND c.workshop_page_id IS NULL AND ec.course_id IS NULL AND ec.workshop_page_id IS NULL AND ec.activity_id=c.activity_id))';
    elseif($workshopScoped)$scopePredicate='((c.workshop_page_id IS NOT NULL AND ec.workshop_page_id=c.workshop_page_id) OR (c.workshop_page_id IS NULL AND ec.workshop_page_id IS NULL AND ec.activity_id=c.activity_id))';
    else $scopePredicate='ec.activity_id=c.activity_id';
    $sql="SELECT t.*,u.name AS student_name,u.email AS student_email,c.title AS cohort_title,c.activity_id$scopeSelect FROM student_tests t JOIN student_users u ON u.id=t.student_id JOIN course_cohorts c ON c.id=t.cohort_id WHERE t.id=? AND (t.student_id=? OR (t.visibility='cohort' AND EXISTS(SELECT 1 FROM course_enrollments e WHERE e.student_id=? AND e.cohort_id=t.cohort_id AND e.status='active')) OR (t.visibility='course' AND EXISTS(SELECT 1 FROM course_enrollments e JOIN course_cohorts ec ON ec.id=e.cohort_id WHERE e.student_id=? AND e.status='active' AND ec.status!='archived' AND $scopePredicate)) ) LIMIT 1";
    $q=$db->prepare($sql);$q->execute([$testId,$studentId,$studentId,$studentId]);$row=$q->fetch(PDO::FETCH_ASSOC);return is_array($row)?$row:null;
}
function student_tests_shared_with_student(PDO $db,int $studentId): array {
    $courseScoped=course_domain_available($db);$workshopScoped=workshop_course_scope_available($db);
    if($courseScoped){$scopeSelect=',c.course_id,cr.title course_title,c.workshop_page_id,wp.title workshop_title';$scopeJoin=' LEFT JOIN courses cr ON cr.id=c.course_id LEFT JOIN cms_pages wp ON wp.id=c.workshop_page_id ';$scopePredicate='((c.course_id IS NOT NULL AND ec.course_id=c.course_id) OR (c.course_id IS NULL AND c.workshop_page_id IS NOT NULL AND ec.workshop_page_id=c.workshop_page_id) OR (c.course_id IS NULL AND c.workshop_page_id IS NULL AND ec.course_id IS NULL AND ec.workshop_page_id IS NULL AND ec.activity_id=c.activity_id))';}
    elseif($workshopScoped){$scopeSelect=',c.workshop_page_id,wp.title workshop_title';$scopeJoin=' LEFT JOIN cms_pages wp ON wp.id=c.workshop_page_id ';$scopePredicate='((c.workshop_page_id IS NOT NULL AND ec.workshop_page_id=c.workshop_page_id) OR (c.workshop_page_id IS NULL AND ec.workshop_page_id IS NULL AND ec.activity_id=c.activity_id))';}
    else{$scopeSelect='';$scopeJoin='';$scopePredicate='ec.activity_id=c.activity_id';}
    $sql="SELECT t.*,u.name AS student_name,c.title AS cohort_title,c.activity_id,a.public_title$scopeSelect FROM student_tests t JOIN student_users u ON u.id=t.student_id JOIN course_cohorts c ON c.id=t.cohort_id JOIN activities a ON a.id=c.activity_id $scopeJoin WHERE t.student_id<>? AND ((t.visibility='cohort' AND EXISTS(SELECT 1 FROM course_enrollments e WHERE e.student_id=? AND e.cohort_id=t.cohort_id AND e.status='active')) OR (t.visibility='course' AND EXISTS(SELECT 1 FROM course_enrollments e JOIN course_cohorts ec ON ec.id=e.cohort_id WHERE e.student_id=? AND e.status='active' AND ec.status!='archived' AND $scopePredicate))) ORDER BY t.updated_at DESC,t.id DESC LIMIT 200";
    $q=$db->prepare($sql);$q->execute([$studentId,$studentId,$studentId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}
function student_test_media_accessible_to_student(PDO $db,int $mediaId,int $studentId): ?array {$q=$db->prepare('SELECT m.* FROM student_test_media m WHERE m.id=?');$q->execute([$mediaId]);$media=$q->fetch(PDO::FETCH_ASSOC);if(!is_array($media))return null;return student_test_accessible_to_student($db,(int)$media['test_id'],$studentId)?$media:null;}
