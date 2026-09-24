<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();
$db=database();
try{$activity=activity_for_request($db);}catch(Throwable $e){http_response_code(404);exit('Atividade não encontrada.');}
$student=require_student_for_activity($db,$activity);
$locale=(string)($_GET['lang']??public_locale());if(!in_array($locale,[PUBLIC_LOCALE_PT_BR,PUBLIC_LOCALE_EN],true))$locale=PUBLIC_LOCALE_PT_BR;
$slug=student_material_slug((string)($_GET['slug']??''));
$material=$slug!==''?student_material_by_slug($db,(int)$activity['id'],$locale,$slug,true):null;
if(!$material){http_response_code(404);student_shell_start('Material não encontrado',$activity,$student);?><p class="student-kicker">Material</p><h1 class="student-title" style="font-size:56px">Não encontrado</h1><p class="student-lead">Este material não está disponível para este acesso.</p><?php student_shell_end();exit;}
header('Content-Disposition: inline');
echo student_material_render((string)$material['html_content'],$student,$activity);
