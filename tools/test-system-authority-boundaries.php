<?php
declare(strict_types=1);

$root=dirname(__DIR__);
function authority_must(bool $condition,string $message): void {if(!$condition)throw new RuntimeException($message);}
function authority_source(string $root,string $path): string {$value=file_get_contents($root.'/'.$path);if(!is_string($value))throw new RuntimeException('cannot read '.$path);return $value;}

$studentShell=authority_source($root,'app/student_shell.php');
authority_must(str_contains($studentShell,'installation_brand_name()'),'student shell must use installation brand');
authority_must(!str_contains($studentShell,'student_account_enrollments('),'global student shell must not infer design from first enrollment');
authority_must(!str_contains($studentShell,'Direct Positive Workshop'),'student shell must not hardcode workshop identity');

$publicIndex=authority_source($root,'index.php');
authority_must(str_contains($publicIndex,"if((int)(\$activity['is_root']??0)!==1)"),'non-root activity must not fall through to legacy renderer');
authority_must(str_contains($publicIndex,'legacy_public_renderer_fallback'),'legacy root fallback must remain observable');

$studentAdmin=authority_source($root,'admin/student-area.php');
$peopleAdmin=authority_source($root,'admin/people.php');
$integrityAdmin=authority_source($root,'admin/data-integrity.php');
$courseAdmin=authority_source($root,'admin/courses.php');
authority_must(str_contains($studentAdmin,'/admin/people.php'),'legacy student-area route must delegate global identity administration to People');
authority_must(str_contains($peopleAdmin,'FROM student_users u'),'people authority must consume global student identity directly');
authority_must(str_contains($peopleAdmin,'c.course_id IS NULL'),'people authority must expose historical enrollments with incomplete course scope');
authority_must(str_contains($integrityAdmin,'Somente leitura'),'integrity diagnosis must declare read-only authority');
authority_must(!str_contains($integrityAdmin,'REQUEST_METHOD'),'integrity diagnosis must not mutate installed data');
authority_must(str_contains($courseAdmin,"'material'=>'Material'"),'course admin must own material context');
authority_must(str_contains($courseAdmin,'course_material_add_page'),'course material must be an association, not a parallel page type');
authority_must(str_contains($courseAdmin,'/editor/?page='),'course material must edit through canonical CMS editor');
authority_must(!str_contains($courseAdmin,'material-editor'),'parallel material editor must not exist');
authority_must(is_file($root.'/admin/student-area-legacy.php'),'legacy student operations implementation must remain explicit during transition');

$migration=authority_source($root,'migrations/067_normalize_page_activity_access.php');
authority_must(str_contains($migration,"access_level='activity'"),'migration must normalize page access to activity');
authority_must(str_contains($migration,"access_level='enrolled'"),'migration must target legacy enrolled value');
$courseMigration=authority_source($root,'migrations/073_course_domain_material.php');
authority_must(str_contains($courseMigration,'CREATE TABLE IF NOT EXISTS courses'),'course domain migration must create canonical courses');
authority_must(str_contains($courseMigration,'CREATE TABLE IF NOT EXISTS course_material_pages'),'course domain migration must relate courses to canonical CMS pages');
authority_must(str_contains($courseMigration,'CREATE TABLE IF NOT EXISTS course_material_sections'),'course domain migration must relate canonical sections to lessons');

$database=authority_source($root,'app/database.php');
authority_must(str_contains($database,'flock($handle,LOCK_EX)'),'migration runner must be serialized');

$build=authority_source($root,'tools/build-dist.php');
authority_must(str_contains($build,'function validate_migrations'),'distribution build must validate complete migration sequence');
authority_must(!str_contains($build,'range(1,19)'),'distribution build must not hardcode the old migration ceiling');

echo "system authority boundary tests passed\n";
