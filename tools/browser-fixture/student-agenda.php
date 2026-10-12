<?php
declare(strict_types=1);
/* O conteúdo da agenda usa a mesma view operacional da rota autenticada.
 * Apenas a identidade e os dados de banco são substituídos por estados sintéticos. */
require_once __DIR__.'/../../app/course_agenda.php';
require_once __DIR__.'/../../app/student_shell.php';
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'fixture-csrf';}
$state=(string)($_GET['state']??'regular');if(!in_array($state,['empty','regular','listener','waiting','full'],true)){http_response_code(404);exit;}
$future='2030-10-12T22:00:00Z';$tz='America/Sao_Paulo';
$meeting=['cohort_id'=>2,'cohort_title'=>'Turma de outubro','course_title'=>'Positivo Direto em Filme de Raios X','lesson_id'=>101,'lesson_title'=>'Características do filme e exposição','meeting_starts_at'=>$future,'meeting_timezone'=>$tz,'meeting_duration_minutes'=>120,'meeting_url'=>'https://example.org/private-meeting','meeting_notes'=>'Acesse pelo link informado alguns minutos antes.','meeting_status'=>'scheduled','participation'=>$state==='listener'?'listener':'regular'];
$meetings=in_array($state,['regular','listener'],true)?[$meeting]:[];
$offer=['cohort_id'=>3,'cohort_title'=>'Turma de novembro','course_title'=>'Positivo Direto em Filme de Raios X','agenda_enrollment_closes_at'=>'2025-09-10T20:00:00Z','agenda_listeners_open_at'=>'2031-09-10T20:00:00Z','next_meeting'=>'2031-11-18T22:00:00Z','future_meetings'=>3,'reservation_status'=>$state==='waiting'?'waiting':($state==='full'?'':'') ,'capacity'=>['limit'=>10,'occupied'=>$state==='full'?10:3,'remaining'=>$state==='full'?0:7,'regular'=>3,'excess'=>0]];
if($state==='full')$offer['agenda_listeners_open_at']='2025-09-10T20:00:00Z';
$offers=$state==='empty'?[]:[$offer];$notice='';$error='';
?><!doctype html><html lang="pt-BR" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>:root{--bg:#101010;--surface:#171717;--surface-2:#202020;--text:#f3f1ec;--muted:#aaa49a;--line:#383633;--line-strong:#d5d0c7;--focus:#73d2ac;--inverse-bg:#f3f1ec;--inverse:#101010;--body:Arial,sans-serif;--title:Georgia,serif;--mono:monospace;--student-border:#383633;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px;--ux-space-8:64px}</style>
<link rel="stylesheet" href="/template/page.css"><link rel="stylesheet" href="/assets/ui-core.css"><link rel="stylesheet" href="/assets/student-area.css"><link rel="stylesheet" href="/assets/student-workbench.css"><link rel="stylesheet" href="/assets/student-experience.css"><link rel="stylesheet" href="/assets/student-academic.css"><link rel="stylesheet" href="/assets/student-rendered-fixes.css"><link rel="stylesheet" href="/assets/student-quality-pass.css">
<title>Agenda · teste de renderização</title></head><body class="student-page"><div class="student-shell"><header class="student-topbar"><a class="brand student-wordmark" href="/aluno/">JSaidler Fotografia</a><?php student_shell_nav('courses','student-desktop-nav');?><span class="student-topbar-spacer"></span><span class="button button-secondary button-compact">Conta</span></header><main class="student-main"><?php require __DIR__.'/../../app/student_agenda_view.php';?></main><?php student_shell_nav('courses','student-mobile-nav');?></div></body></html>
