<?php
declare(strict_types=1);
function fail_student_mobile(string $message): never {fwrite(STDERR,"student-mobile-experience: $message\n");exit(1);}
$root=dirname(__DIR__);
$renderer=(string)file_get_contents($root.'/app/cms_renderer.php');
$shell=(string)file_get_contents($root.'/app/student_shell.php');
$test=(string)file_get_contents($root.'/aluno/teste.php');
$css=(string)file_get_contents($root.'/assets/student-area.css');
$cadernoCss=(string)file_get_contents($root.'/assets/student-caderno.css');
$headerCss=(string)file_get_contents($root.'/assets/cms-header.css');
$studentAdmin=(string)file_get_contents($root.'/admin/student-area.php');
$migration=(string)file_get_contents($root.'/migrations/063_student_test_mobile_workflow.php');
$helper=(string)file_get_contents($root.'/app/student_test_mobile.php');
$hardening=(string)file_get_contents($root.'/app/student_workbench_hardening.php');

if(!str_contains($renderer,'class="cms-student-access"')||!str_contains($renderer,'$studentWorkspaceUrl'))fail_student_mobile('public header does not expose the canonical student-area destination');
if(!str_contains($shell,'student-mobile-nav')||!str_contains($shell,'student-desktop-nav'))fail_student_mobile('student shell lacks responsive navigation');
 foreach(["'home'=>['label'=>'Início','href'=>'/aluno/']","'courses'=>['label'=>'Cursos','href'=>'/aluno/cursos.php']","'notebook'=>['label'=>'Caderno','href'=>'/aluno/caderno.php']","'laboratory'=>['label'=>'Laboratório','href'=>'/aluno/ferramentas.php']"] as $needle)if(!str_contains($shell,$needle))fail_student_mobile('canonical student destination missing: '.$needle);
 require_once $root.'/app/student_shell.php';
 if(student_shell_active_area('/aluno/registro-roteiro.php')!=='notebook')fail_student_mobile('record route editor is not classified in Caderno');
 if(student_shell_active_area('/aluno/processar.php',['test'=>'42'])!=='notebook')fail_student_mobile('record timer does not preserve Caderno context');
 if(student_shell_active_area('/aluno/processar.php')!=='laboratory')fail_student_mobile('standalone process runner is not classified in Laboratório');
 if(student_shell_active_area('/aluno/inventario-item.php',['id'=>'7'])!=='laboratory')fail_student_mobile('inventory child does not preserve Laboratório context');
if(!str_contains($shell,'cms_design_css')||!str_contains($shell,'cms_design_font_import_css'))fail_student_mobile('student shell does not inherit activity design tokens');
foreach(['id="exposicao"','id="processamento"','id="resultado"'] as $anchor)if(!str_contains($test,$anchor))fail_student_mobile('mobile record does not expose all documentary sections together');
if(str_contains($test,"['exposure','process','review']")||str_contains($test,'aria-disabled'))fail_student_mobile('mobile record returned to staged/gated navigation');
if(substr_count($test,'capture="environment"')<2)fail_student_mobile('scene and result capture controls are missing');
if(!str_contains($test,'name="phase" value="scene"')||!str_contains($test,'name="phase" value="result"'))fail_student_mobile('record media is not separated into scene and result');
if(!str_contains($helper,'student_test_update_exposure')||!str_contains($helper,'student_test_add_media_phase')||!str_contains($hardening,'function student_process_add_guided_step'))fail_student_mobile('record persistence helpers are incomplete');
if(!str_contains($migration,"ADD COLUMN phase TEXT NOT NULL DEFAULT 'result'"))fail_student_mobile('media phase migration missing');
if(!str_contains($css,'.student-mobile-nav')||!str_contains($css,'.student-sticky-action'))fail_student_mobile('mobile app navigation or action styling missing');
if(!str_contains($cadernoCss,'@media(max-width:560px)')||!str_contains($cadernoCss,'.student-record-section-links{display:grid;grid-template-columns:repeat(3,minmax(0,1fr))'))fail_student_mobile('Caderno section navigation does not stay compact in one mobile row');
if(!str_contains($headerCss,'body[data-cms-page-slug="privacidade"]')||!str_contains($headerCss,'.cms-student-access'))fail_student_mobile('public legal/access styling missing');
if(str_contains($studentAdmin,'name="media_file"')||str_contains($studentAdmin,'upload_private_media'))fail_student_mobile('parallel private-media uploader returned to student admin');
echo "student-mobile-experience: ok\n";
