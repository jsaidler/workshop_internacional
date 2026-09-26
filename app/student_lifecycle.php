<?php
declare(strict_types=1);

function student_test_delete_owned(PDO $db,int $testId,int $studentId): void {
    $q=$db->prepare('SELECT id FROM student_tests WHERE id=? AND student_id=?');
    $q->execute([$testId,$studentId]);
    if(!(int)$q->fetchColumn())throw new RuntimeException('Teste não encontrado.');

    $mediaQ=$db->prepare('SELECT id,storage_path FROM student_test_media WHERE test_id=? ORDER BY id');
    $mediaQ->execute([$testId]);
    $media=$mediaQ->fetchAll(PDO::FETCH_ASSOC);

    $db->beginTransaction();
    try{
        // Delete explicitly instead of depending on SQLite FK settings at runtime.
        $db->prepare('DELETE FROM student_test_messages WHERE test_id=?')->execute([$testId]);
        $db->prepare('DELETE FROM student_test_media WHERE test_id=?')->execute([$testId]);
        $db->prepare('DELETE FROM student_tests WHERE id=? AND student_id=?')->execute([$testId,$studentId]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}

    foreach($media as $item){
        $relative=basename((string)($item['storage_path']??''));
        if($relative==='')continue;
        $path=student_test_media_storage_root().'/'.$relative;
        if(is_file($path)&&!@unlink($path))error_log('student_test_media_delete_failed: '.$path);
    }
}

function admin_registration_delete(PDO $db,int $submissionId,int $activityId): void {
    $q=$db->prepare('SELECT s.id,f.purpose FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE s.id=? AND s.activity_id=?');
    $q->execute([$submissionId,$activityId]);
    $submission=$q->fetch(PDO::FETCH_ASSOC);
    if(!$submission)throw new RuntimeException('Inscrição não encontrada.');
    if(cms_form_purpose($submission)!=='enrollment')throw new RuntimeException('Este registro não gera matrícula de curso.');

    $db->beginTransaction();
    try{
        // A inscrição é a origem da matrícula. Excluí-la remove somente a matrícula
        // criada a partir dela. Conta, perfil e histórico de testes são entidades próprias.
        $db->prepare('DELETE FROM course_enrollments WHERE source_submission_id=?')->execute([$submissionId]);
        $db->prepare('DELETE FROM cms_form_submissions WHERE id=? AND activity_id=?')->execute([$submissionId,$activityId]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
