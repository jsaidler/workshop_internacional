<?php
declare(strict_types=1);
require_once __DIR__.'/../../app/cms_settings.php';
$fixtureDesign=cms_design_defaults();
/* O conteúdo da agenda usa a mesma view operacional da rota autenticada.
 * Apenas a identidade e os dados de banco são substituídos por estados sintéticos. */
require_once __DIR__.'/../../app/course_agenda.php';
require_once __DIR__.'/../../app/student_shell.php';
function database(): PDO {static $fixtureDb=null;return $fixtureDb??=new PDO('sqlite::memory:');}
function student_tools_for_student(PDO $db,int $id): array {return [['tool_key'=>'reciprocity'],['tool_key'=>'lab_timer']];}
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'fixture-csrf';}
$state=(string)($_GET['state']??'regular');if(!in_array($state,['empty','regular','listener','waiting','full','mixed','history','unsafe','two'],true)){http_response_code(404);exit;}
$future='2030-10-12T22:00:00Z';$tz='America/Sao_Paulo';
$meeting=['cohort_id'=>2,'cohort_title'=>'Turma de outubro','course_title'=>'Positivo Direto em Filme de Raios X','lesson_id'=>101,'lesson_title'=>'Características do filme e exposição','meeting_starts_at'=>$future,'meeting_timezone'=>$tz,'meeting_duration_minutes'=>120,'meeting_url'=>'https://example.org/private-meeting','meeting_notes'=>'Acesse pelo link informado alguns minutos antes.','meeting_status'=>'scheduled','participation'=>$state==='listener'?'listener':'regular'];
if($state==='unsafe')$meeting['meeting_url']='javascript:alert(1)';
$past=array_replace($meeting,['meeting_starts_at'=>'2025-01-12T22:00:00Z','meeting_status'=>'completed','lesson_title'=>'Encontro anterior · exposição','meeting_url'=>'https://example.org/previous-meeting']);
$meetings=match($state){'regular','listener','unsafe'=>[$meeting],'mixed'=>[$past,$meeting],'history'=>[$past],'two'=>[$meeting,array_replace($meeting,['lesson_title'=>'Revisão de resultados','meeting_starts_at'=>'2030-10-19T22:00:00Z'])],default=>[]};
$offer=['cohort_id'=>3,'cohort_title'=>'Turma de novembro','course_title'=>'Positivo Direto em Filme de Raios X','agenda_enrollment_closes_at'=>'2025-09-10T20:00:00Z','agenda_listeners_open_at'=>'2031-09-10T20:00:00Z','next_meeting'=>'2031-11-18T22:00:00Z','future_meetings'=>3,'reservation_status'=>$state==='waiting'?'waiting':($state==='full'?'':'') ,'capacity'=>['limit'=>10,'occupied'=>$state==='full'?10:3,'remaining'=>$state==='full'?0:7,'regular'=>3,'excess'=>0]];
$offer['schedule']=[
 ['lesson_title'=>'Filme de raio X e exposição','meeting_starts_at'=>'2031-11-18T22:00:00Z','meeting_timezone'=>$tz,'meeting_duration_minutes'=>120],
 ['lesson_title'=>'Produção dos positivos','meeting_starts_at'=>'2031-11-19T22:00:00Z','meeting_timezone'=>$tz,'meeting_duration_minutes'=>120],
 ['lesson_title'=>'Revisão dos resultados','meeting_starts_at'=>'2031-11-20T22:00:00Z','meeting_timezone'=>$tz,'meeting_duration_minutes'=>120],
];
if($state==='full')$offer['agenda_listeners_open_at']='2025-09-10T20:00:00Z';
$offers=$state==='empty'?[]:[$offer];$notice='';$error='';
?><!doctype html><html lang="pt-BR" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style data-cms-design-fonts><?=cms_design_font_import_css($fixtureDesign)?></style><style data-cms-design-tokens><?=cms_design_css($fixtureDesign)?></style>
<link rel="stylesheet" href="/template/page.css"><link rel="stylesheet" href="/assets/ui-core.css"><link rel="stylesheet" href="/assets/student-area.css"><link rel="stylesheet" href="/assets/student-workbench.css"><link rel="stylesheet" href="/assets/student-experience.css"><link rel="stylesheet" href="/assets/student-academic.css"><link rel="stylesheet" href="/assets/student-rendered-fixes.css"><link rel="stylesheet" href="/assets/student-quality-pass.css"><link rel="stylesheet" href="/assets/student-app-shell.css"><link rel="stylesheet" href="/assets/student-agenda.css">
<title>Agenda · teste de renderização</title></head><body class="student-page student-app"><div class="student-shell"><?php student_shell_nav('agenda','student-desktop-nav');?><header class="student-topbar"><a class="brand student-wordmark" href="/aluno/"><span class="student-brand-compact">JS<span>.</span></span><span class="student-brand-full">JSaidler Fotografia</span></a><span class="student-appbar-divider"></span><strong class="student-appbar-title">Agenda</strong><span class="student-topbar-spacer"></span><button class="button button-secondary button-compact student-tools-trigger" type="button" data-toolbox-open>Atalhos</button><details class="student-user-menu"><summary aria-label="Conta"><span class="student-account-initial">J</span><span class="student-account-label">Conta</span></summary><div class="student-user-menu-panel"><button type="button" class="student-tools-menu-trigger" data-toolbox-open>Ferramentas rápidas</button></div></details></header><?php student_shell_toolbox(['id'=>1]);?><main class="student-main"><?php require __DIR__.'/../../app/student_agenda_view.php';?></main><?php student_shell_nav('agenda','student-mobile-nav');?></div><script defer src="/assets/student-experience.js"></script></body></html>
