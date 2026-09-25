<?php
declare(strict_types=1);
function fail_student_mobile(string $message): never {fwrite(STDERR,"student-mobile-experience: $message\n");exit(1);}
$root=dirname(__DIR__);
$renderer=(string)file_get_contents($root.'/app/cms_renderer.php');
$shell=(string)file_get_contents($root.'/app/student_shell.php');
$test=(string)file_get_contents($root.'/aluno/teste.php');
$css=(string)file_get_contents($root.'/assets/student-area.css');
$headerCss=(string)file_get_contents($root.'/assets/cms-header.css');
$studentAdmin=(string)file_get_contents($root.'/admin/student-area.php');
$migration=(string)file_get_contents($root.'/migrations/063_student_test_mobile_workflow.php');
$helper=(string)file_get_contents($root.'/app/student_test_mobile.php');

if(!str_contains($renderer,'class="cms-student-access"')||!str_contains($renderer,'href="/aluno/"'))fail_student_mobile('public header does not expose the student area');
if(!str_contains($shell,'student-mobile-nav')||!str_contains($shell,'student-desktop-nav'))fail_student_mobile('student shell lacks responsive navigation');
if(!str_contains($shell,'cms_design_css')||!str_contains($shell,'cms_design_font_import_css'))fail_student_mobile('student shell does not inherit activity design tokens');
if(!str_contains($test,"['exposure','development','review']"))fail_student_mobile('test workflow is not staged');
if(substr_count($test,'capture="environment"')<2)fail_student_mobile('scene and result capture controls are missing');
if(!str_contains($test,'name="phase" value="scene"')||!str_contains($test,'name="phase" value="result"'))fail_student_mobile('test media is not separated into scene and result');
if(!str_contains($helper,'student_test_update_exposure')||!str_contains($helper,'student_test_update_development')||!str_contains($helper,'student_test_add_media_phase'))fail_student_mobile('staged persistence helpers are incomplete');
if(!str_contains($migration,"ADD COLUMN phase TEXT NOT NULL DEFAULT 'result'"))fail_student_mobile('media phase migration missing');
if(!str_contains($css,'.student-step-nav')||!str_contains($css,'.student-mobile-nav')||!str_contains($css,'.student-sticky-action'))fail_student_mobile('mobile app navigation or action styling missing');
if(!str_contains($headerCss,'body[data-cms-page-slug="privacidade"]')||!str_contains($headerCss,'.cms-student-access'))fail_student_mobile('public legal/access styling missing');
if(str_contains($studentAdmin,'name="media_file"')||str_contains($studentAdmin,'upload_private_media'))fail_student_mobile('parallel private-media uploader returned to student admin');
echo "student-mobile-experience: ok\n";