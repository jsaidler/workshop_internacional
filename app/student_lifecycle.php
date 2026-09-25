<?php
declare(strict_types=1);

/**
 * Deletes one test owned by the student.
 * Database children (messages and media rows) are removed by FK cascade; physical
 * image files are removed immediately after the transaction commits.
 */
function student_test_delete(PDO $db,int $testId,int $studentId): void {
    $test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Teste não encontrado.');
    $media=student_test_media($db,(int)$test['id']);
    $paths=[];
    foreach($media as $item)$paths[]=student_test_media_absolute_path($item);

    $db->beginTransaction();
    try{
        $q=$db->prepare('DELETE FROM student_tests WHERE id=? AND student_id=?');
        $q->execute([(int)$test['id'],$studentId]);
        if($q->rowCount()!==1)throw new RuntimeException('Não foi possível excluir o teste.');
        $db->commit();
    }catch(Throwable $e){
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }

    foreach($paths as $path)if(is_file($path))@unlink($path);
}

/**
 * Removes an enrollment without deleting the reusable student account or the
 * original form submission. This is deliberately separate from submission
 * deletion so the admin never triggers an implicit cascade across domains.
 */
function course_enrollment_delete(PDO $db,int $activityId,int $enrollmentId): void {
    $q=$db->prepare('SELECT e.id FROM course_enrollments e JOIN course_cohorts c ON c.id=e.cohort_id WHERE e.id=? AND c.activity_id=? LIMIT 1');
    $q->execute([$enrollmentId,$activityId]);
    if(!$q->fetchColumn())throw new RuntimeException('Matrícula não encontrada.');
    $db->prepare('DELETE FROM course_enrollments WHERE id=?')->execute([$enrollmentId]);
}
