<?php
declare(strict_types=1);

function fail_student_material_hotfix(string $message): never {fwrite(STDERR,"student-material-access-hotfix: $message\n");exit(1);}

$root=dirname(__DIR__);
$dashboard=(string)file_get_contents($root.'/aluno/index.php');
$material=(string)file_get_contents($root.'/app/student_material.php');
$migration=(string)file_get_contents($root.'/migrations/067_normalize_page_activity_access.php');

if(!str_contains($migration,"SET access_level='activity'"))fail_student_material_hotfix('activity access normalization contract missing');
if(!str_contains($dashboard,"access_level IN ('activity','enrolled')"))fail_student_material_hotfix('student dashboard does not list canonical activity-protected pages');
if(!str_contains($material,'Infográfico pendente'))fail_student_material_hotfix('pending infographic placeholder missing');
if(str_contains($material,"if(!current_admin())return '';"))fail_student_material_hotfix('pending infographic placeholder is still hidden from students');

echo "student-material-access-hotfix: ok\n";
