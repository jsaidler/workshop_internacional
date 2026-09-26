<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasTable=static fn(string $name): bool => (bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name))->fetchColumn();
    if(!$hasTable('student_enrollments')||!$hasTable('course_enrollments')||!$hasTable('course_cohorts'))return;

    $now=gmdate('c');
    $rows=$db->query('SELECT student_id,activity_id,status,created_at,updated_at FROM student_enrollments ORDER BY activity_id,student_id')->fetchAll(PDO::FETCH_ASSOC);
    $findCohort=$db->prepare("SELECT id FROM course_cohorts WHERE activity_id=? AND status!='archived' ORDER BY is_registration_default DESC,id ASC LIMIT 1");
    $insert=$db->prepare("INSERT INTO course_enrollments(enrollment_uuid,student_id,cohort_id,source_submission_id,status,confirmed_at,created_at,updated_at) VALUES(?,?,?,NULL,?,?,?,?) ON CONFLICT(student_id,cohort_id) DO NOTHING");

    foreach($rows as $row){
        $activityId=(int)$row['activity_id'];
        $findCohort->execute([$activityId]);
        $cohortId=(int)($findCohort->fetchColumn()?:0);
        if($cohortId<1)continue;
        $created=(string)($row['created_at']??'');if($created==='')$created=$now;
        $updated=(string)($row['updated_at']??'');if($updated==='')$updated=$created;
        $status=(string)($row['status']??'')==='active'?'active':'disabled';
        $insert->execute([bin2hex(random_bytes(16)),(int)$row['student_id'],$cohortId,$status,$created,$created,$updated]);
    }
};
