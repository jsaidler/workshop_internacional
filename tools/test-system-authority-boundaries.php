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
foreach(['set_page_access','save_section_map','bind_page_media','unbind_page_media'] as $action)authority_must(str_contains($studentAdmin,$action),'student-area authority guard missing '.$action);
authority_must(str_contains($studentAdmin,"if(\$view==='pages')"),'legacy protected-pages route must redirect to CMS pages');
authority_must(is_file($root.'/admin/student-area-legacy.php'),'legacy student operations implementation must remain explicit during transition');

$migration=authority_source($root,'migrations/067_normalize_page_activity_access.php');
authority_must(str_contains($migration,"access_level='activity'"),'migration must normalize page access to activity');
authority_must(str_contains($migration,"access_level='enrolled'"),'migration must target legacy enrolled value');

$database=authority_source($root,'app/database.php');
authority_must(str_contains($database,'flock($handle,LOCK_EX)'),'migration runner must be serialized');

$build=authority_source($root,'tools/build-dist.php');
authority_must(str_contains($build,'function validate_migrations'),'distribution build must validate complete migration sequence');
authority_must(!str_contains($build,'range(1,19)'),'distribution build must not hardcode the old migration ceiling');

echo "system authority boundary tests passed\n";
