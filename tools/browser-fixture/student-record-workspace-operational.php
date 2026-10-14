<?php
declare(strict_types=1);
/** Complete canonical record workspace with synthetic data; no storage, request mutations or authorization emulation. */
require_once __DIR__.'/../../app/student_shell.php';
require_once __DIR__.'/../../app/student_record_media_controls.php';
require_once __DIR__.'/../../app/student_record_workspace_navigation.php';
require_once __DIR__.'/../../app/student_record_workspace_view.php';
require_once __DIR__.'/../../app/student_workbench.php';
require_once __DIR__.'/../../app/student_experience.php';
if(!defined('STUDENT_TEST_MEDIA_MAX_FILES'))define('STUDENT_TEST_MEDIA_MAX_FILES',6);
function h(mixed $v): string {return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'controlled-workspace-csrf';}
function database(): PDO {static $db=null;return $db??=new PDO('sqlite::memory:');}
function student_account_current(PDO $db): ?array {return ['id'=>2,'name'=>'Aluno de teste'];}
function student_tools_for_student(PDO $db,int $id): array {return [];}
function cms_design_defaults(): array {return ['colors'=>['bg'=>'#101010']];}
function cms_design_font_import_css(array $design): string {return '';}
function cms_design_css(array $design): string {return ':root{--bg:#101010;--surface:#171717;--surface-2:#202020;--text:#f3f1ec;--muted:#aaa49a;--line:#383633;--line-strong:#d5d0c7;--focus:#73d2ac;--inverse-bg:#f3f1ec;--inverse:#101010;--body:Arial,sans-serif;--title:Arial,sans-serif;--mono:"Courier New",monospace;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px}';}
function student_test_status_label(string $status): string {return ['draft'=>'Rascunho','submitted'=>'Enviado para avaliação','needs_revision'=>'Revisão solicitada','reviewed'=>'Avaliação concluída'][$status]??'Rascunho';}
function student_test_message_date(string $date): string {return '14/10/2026';}
$state=(string)($_GET['state']??'exposure');
$states=['exposure','process-choice','result','result-waiting','result-revision','result-reviewed'];
if(!in_array($state,$states,true)){http_response_code(404);exit;}
$status=match($state){'result-waiting'=>'submitted','result-revision'=>'needs_revision','result-reviewed'=>'reviewed',default=>'draft'};
$contextScope=str_starts_with($state,'result-')?'course':'personal';
$recordDefaultPart=$state==='exposure'?'exposicao':($state==='process-choice'?'processamento':'resultado');
$record=[
 'id'=>7,'title'=>'Registro de exposição e revelação','status'=>$status,'context_scope'=>$contextScope,
 'public_title'=>'Positivo direto em filme de raio-X','cohort_title'=>'Turma Outubro 2026',
 'test_date'=>'2026-10-10','film'=>'Fuji Super HR-U','lot'=>'RX-24','iso_reference'=>'200',
 'aperture'=>'f/64','calculated_time'=>'04 s','reciprocity_time'=>'07 s',
 'light_condition'=>'Luz de janela lateral','tonal_range'=>'Sombras agrupadas',
 'notes'=>'O positivo apresentou contraste diferente do teste anterior.'
];
$id=7;
$notice='';$error='';$locked=$status==='reviewed';
$sceneMedia=[];$resultMedia=[];$media=[];
$plan=null;$planCompleted=0;$planSteps=[];$editPlanStep=null;$stageCatalog=student_process_stage_catalog();
$steps=[];$firstDevelopment=null;$bleachNames=[];
$feedbackState=['kind'=>$status==='needs_revision'?'revision':($status==='reviewed'?'reviewed':'waiting'),
  'label'=>$status==='submitted'?'Enviado para avaliação':student_test_status_label($status)];
$messages=$status==='needs_revision'||$status==='reviewed'
 ? [['author_role'=>'admin','body'=>$status==='needs_revision'?'Professor: revise a anotação sobre a exposição.':'Professor: avaliação concluída.','created_at'=>'2026-10-14 10:00:00']]
 : [];
$_SERVER['REQUEST_URI']='/aluno/teste.php?id=7';
$student=['id'=>2,'name'=>'Aluno de teste'];
student_shell_start('Registro de exposição e revelação · Caderno',null,$student);
student_record_render_workspace(compact('record','id','contextScope','recordDefaultPart','notice','error','locked','sceneMedia','media','plan','planCompleted','planSteps','editPlanStep','stageCatalog','steps','firstDevelopment','bleachNames','resultMedia','feedbackState','messages'));
student_shell_end();
