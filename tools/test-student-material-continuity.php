<?php
declare(strict_types=1);

function fail_student_material_continuity(string $message): never {fwrite(STDERR,"student-material-continuity: $message\n");exit(1);}
function must_student_material_continuity(bool $condition,string $message): void {if(!$condition)fail_student_material_continuity($message);}

$root=dirname(__DIR__);
require $root.'/app/cms_renderer.php';

must_student_material_continuity(cms_student_context_url('/?page=caderno&lang=pt-br','turma-abc')==='/?page=caderno&lang=pt-br&cohort=turma-abc','context parameter was not appended to an existing query string');
must_student_material_continuity(cms_student_context_url('/curso/','turma abc')==='/curso/?cohort=turma%20abc','context parameter is not safely encoded');
must_student_material_continuity(cms_student_context_url('/curso/?lang=pt-br#parte','turma-abc')==='/curso/?lang=pt-br&cohort=turma-abc#parte','context parameter was appended after the URL fragment');
must_student_material_continuity(cms_student_context_url('/curso/','')==='/curso/','empty context must not alter canonical URLs');

$renderer=(string)file_get_contents($root.'/app/cms_renderer.php');
$css=(string)file_get_contents($root.'/assets/cms-header.css');
must_student_material_continuity(str_contains($renderer,'student_account_enrollment_for_activity'),'CMS material context is not validated against the authenticated enrollment');
must_student_material_continuity(str_contains($renderer,'data-cms-student-context'),'contextual student bar is missing from the public CMS renderer');
must_student_material_continuity(str_contains($renderer,'/aluno/?cohort=')&&str_contains($renderer,'/aluno/testes.php')&&str_contains($renderer,'/aluno/perfil.php'),'contextual student navigation is incomplete');
must_student_material_continuity(str_contains($renderer,"if((\$item['type']??'')==='page')\$item['url']=cms_student_context_url"),'CMS page navigation does not preserve the selected cohort context');
must_student_material_continuity(str_contains($renderer,'$absoluteLangUrl=$baseLangUrl!==')&&str_contains($renderer,'$langUrl=$workspace&&$baseLangUrl!=='),'language UI can preserve context without contaminating canonical hreflang URLs');
must_student_material_continuity(str_contains($css,'.cms-student-context')&&str_contains($css,'@media(max-width:560px)'),'student material context lacks responsive styling');

echo "student-material-continuity: ok\n";
