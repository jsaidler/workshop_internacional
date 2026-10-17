<?php
declare(strict_types=1);

function student_question_annotation_available(PDO $db): bool {
    try{
        $table=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='student_material_annotations'")->fetchColumn();
        if(!$table)return false;
        foreach($db->query('PRAGMA table_info(student_questions)')->fetchAll(PDO::FETCH_ASSOC) as $column)if((string)($column['name']??'')==='source_annotation_id')return true;
    }catch(Throwable){}
    return false;
}

function student_question_annotation_source(PDO $db,int $studentId,int $cohortId,int $annotationId): ?array {
    if($annotationId<1||!student_question_annotation_available($db))return null;
    $q=$db->prepare("SELECT a.*,p.title page_title,p.activity_id page_activity_id,c.activity_id cohort_activity_id,c.course_id cohort_course_id
        FROM student_material_annotations a
        JOIN cms_pages p ON p.id=a.page_id
        JOIN course_cohorts c ON c.id=?
        WHERE a.id=? AND a.student_id=?
        LIMIT 1");
    $q->execute([$cohortId,$annotationId,$studentId]);$note=$q->fetch(PDO::FETCH_ASSOC);if(!$note)return null;
    $courseId=(int)($note['cohort_course_id']??0);
    if($courseId>0){
        $allowed=$db->prepare('SELECT 1 FROM course_material_pages WHERE course_id=? AND page_id=?');$allowed->execute([$courseId,(int)$note['page_id']]);
        if(!$allowed->fetchColumn())return null;
    }elseif((int)$note['page_activity_id']!==(int)$note['cohort_activity_id'])return null;
    return $note;
}

function student_question_from_annotation(PDO $db,int $studentId,int $annotationId): ?array {
    if($annotationId<1||!student_question_annotation_available($db))return null;
    $q=$db->prepare('SELECT * FROM student_questions WHERE student_id=? AND source_annotation_id=? LIMIT 1');$q->execute([$studentId,$annotationId]);
    return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

/** Resolve a linked question to a context the annotation owner can actually open.
 * The current material cohort is only a preference, never the authority.
 * Reuse the enrollment list and question-visibility policy from the domain.
 */
function student_question_annotation_url(PDO $db,int $studentId,array $question,string $preferredCohortUuid='',?array $activeEnrollments=null): string {
    $questionId=(int)($question['id']??0);
    if($questionId<1||$studentId<1||(int)($question['student_id']??0)!==$studentId)return '';
    $activeEnrollments??=student_enrollment_list($db,$studentId);
    $available=[];
    foreach($activeEnrollments as $enrollment){
        $cohortId=(int)($enrollment['cohort_id']??0);
        $uuid=trim((string)($enrollment['cohort_uuid']??''));
        if($cohortId<1||$uuid===''||!student_question_in_cohort_context($db,$question,$cohortId))continue;
        $available[]=['cohort_id'=>$cohortId,'uuid'=>$uuid];
    }
    if(!$available)return '';
    $selected=null;
    $preferredCohortUuid=trim($preferredCohortUuid);
    if($preferredCohortUuid!=='')foreach($available as $option)if($option['uuid']===$preferredCohortUuid){$selected=$option;break;}
    if($selected===null)foreach($available as $option)if($option['cohort_id']===(int)$question['cohort_id']){$selected=$option;break;}
    $selected??=$available[0];
    return '/aluno/duvidas.php?cohort='.rawurlencode($selected['uuid']).'&id='.$questionId;
}

function student_question_attach_annotation(PDO $db,int $questionId,int $studentId,int $cohortId,int $annotationId): void {
    if($annotationId<1)return;
    if(!student_question_annotation_source($db,$studentId,$cohortId,$annotationId))throw new RuntimeException('Anotação de origem inválida para esta turma.');
    $existing=student_question_from_annotation($db,$studentId,$annotationId);
    if($existing&&(int)$existing['id']!==$questionId)throw new RuntimeException('Esta anotação já foi transformada em dúvida.');
    $q=$db->prepare('UPDATE student_questions SET source_annotation_id=?,updated_at=? WHERE id=? AND student_id=? AND cohort_id=?');
    $q->execute([$annotationId,utc_now(),$questionId,$studentId,$cohortId]);
    if($q->rowCount()!==1)throw new RuntimeException('Dúvida não encontrada para vincular a anotação.');
}

function student_question_create_from_annotation(PDO $db,int $studentId,int $cohortId,int $annotationId,array $input): array {
    $existing=student_question_from_annotation($db,$studentId,$annotationId);if($existing)return $existing;
    $source=student_question_annotation_source($db,$studentId,$cohortId,$annotationId);
    if(!$source)throw new RuntimeException('Anotação de origem inválida para esta turma.');
    $title=student_workspace_text($input['question_title']??'',180);if($title==='')throw new RuntimeException('Dê um título à dúvida.');
    $visibility=(string)($input['question_visibility']??'private');if(!in_array($visibility,['private','cohort','course'],true))$visibility='private';
    $created=student_question_create($db,$studentId,$cohortId,['topic'=>'Material','title'=>$title,'body'=>(string)$source['body'],'visibility'=>$visibility,'test_id'=>'']);
    $questionId=(int)($created['id']??0);if($questionId<1)throw new RuntimeException('Não foi possível criar a dúvida.');
    student_question_attach_annotation($db,$questionId,$studentId,$cohortId,$annotationId);
    return student_question_from_annotation($db,$studentId,$annotationId)??$created;
}

function student_question_source_context(PDO $db,array $question): ?array {
    $annotationId=(int)($question['source_annotation_id']??0);if($annotationId<1||!student_question_annotation_available($db))return null;
    $q=$db->prepare('SELECT a.*,p.title page_title FROM student_material_annotations a JOIN cms_pages p ON p.id=a.page_id WHERE a.id=? LIMIT 1');$q->execute([$annotationId]);
    return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

/** Match a material question's source only against pages belonging to the selected cohort.
 * The caller must still enforce CMS release/access rights before publishing the URL.
 */
function student_question_source_page(?array $source,array $allowedPages): ?array {
    $pageId=(int)($source['page_id']??0);
    if($pageId<1)return null;
    foreach($allowedPages as $page){
        if((int)($page['id']??0)===$pageId)return $page;
    }
    return null;
}

/** Show a related record only when this student can open it. */
function student_question_related_record_href(PDO $db,array $question,int $viewerId): string {
    $testId=(int)($question['test_id']??0);
    if($testId<1)return '';
    $record=student_test_accessible_to_student($db,$testId,$viewerId);
    if(!$record)return '';
    $path=(int)$record['student_id']===$viewerId?'/aluno/teste.php':'/aluno/teste-compartilhado.php';
    return $path.'?id='.$testId;
}
