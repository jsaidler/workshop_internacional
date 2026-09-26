<?php
declare(strict_types=1);

function fail_student_material_hotfix(string $message): never {fwrite(STDERR,"student-material-access-hotfix: $message\n");exit(1);}

$root=dirname(__DIR__);
$dashboard=(string)file_get_contents($root.'/aluno/index.php');
$enrollments=(string)file_get_contents($root.'/app/student_enrollments.php');
$material=(string)file_get_contents($root.'/app/student_material.php');
$migration=(string)file_get_contents($root.'/migrations/067_normalize_page_activity_access.php');

if(!str_contains($migration,"SET access_level='activity'"))fail_student_material_hotfix('activity access normalization contract missing');
if(!str_contains($enrollments,"access_level='activity'"))fail_student_material_hotfix('canonical enrollment service does not list activity-protected pages');
if(str_contains($enrollments,"access_level IN ('activity','enrolled')"))fail_student_material_hotfix('canonical enrollment service still depends on legacy enrolled access');
if(!str_contains($dashboard,'student_enrollment_pages_for_enrollment'))fail_student_material_hotfix('student dashboard does not use canonical enrollment page lookup');
if(!str_contains($material,'Infográfico pendente'))fail_student_material_hotfix('admin pending infographic placeholder missing');
if(!str_contains($material,"if(!current_admin())return '';"))fail_student_material_hotfix('pending infographic placeholder must stay hidden from students');

echo "student-material-access-hotfix: ok\n";
