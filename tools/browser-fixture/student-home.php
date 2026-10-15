<?php
declare(strict_types=1);
// PHP render parity with authenticated Home; synthetic data only, no session.
function h(mixed $value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
require_once __DIR__.'/../../app/course_agenda.php';
require_once __DIR__.'/../../app/student_home.php';
require_once __DIR__.'/../../app/student_shell.php';
function database(): PDO {static $fixtureDb=null;return $fixtureDb??=new PDO('sqlite::memory:');}
function student_tools_for_student(PDO $db,int $id): array {return [['tool_key'=>'reciprocity'],['tool_key'=>'lab_timer']];}
require_once __DIR__.'/../../app/student_record_create_dialog.php';
$state=(string)($_GET['state']??'active');
$scenario=['active','study','multiple','empty','feedback','no-link','long','restricted','record','error','ongoing'];
if(!in_array($state,$scenario,true))$state='active';
$course=['title'=>'Positivo direto em filme de raio-X','cohort'=>'Turma Outubro 2026','url'=>'/aluno/cursos.php?cohort=cohort-a','questions_url'=>'/aluno/duvidas.php?cohort=cohort-a','material'=>['title'=>'Exposição pensando no positivo','url'=>'/material-do-curso?cohort=cohort-a','course'=>'Positivo direto em filme de raio-X','cohort'=>'Turma Outubro 2026'],'available'=>2];
$course2=['title'=>'Pesquisa em processos fotográficos','cohort'=>'Turma Novembro 2026','url'=>'/aluno/cursos.php?cohort=cohort-b','questions_url'=>'/aluno/duvidas.php?cohort=cohort-b','material'=>['title'=>'Química de revelação','url'=>'/processos?cohort=cohort-b','course'=>'Pesquisa em processos fotográficos','cohort'=>'Turma Novembro 2026'],'available'=>1];
$course3=['title'=>'Experiências de grande formato e arquitetura de câmera artesanal','cohort'=>'Turma Dezembro 2026','url'=>'/aluno/cursos.php?cohort=cohort-c','questions_url'=>'/aluno/duvidas.php?cohort=cohort-c','material'=>null,'available'=>0];
$meetings=[
 ['lesson_title'=>'Primeiro encontro · exposição e filme de raio-X','course_title'=>$course['title'],'cohort_title'=>$course['cohort'],'meeting_starts_at'=>'2030-10-21T22:00:00Z','meeting_timezone'=>'America/Sao_Paulo','meeting_status'=>'completed','meeting_url'=>'https://example.org/completed'],
 ['lesson_title'=>'Revelação para positivo direto','course_title'=>$course['title'],'cohort_title'=>$course['cohort'],'meeting_starts_at'=>'2030-10-22T22:00:00Z','meeting_timezone'=>'America/Sao_Paulo','meeting_status'=>'scheduled','meeting_url'=>'https://example.org/private-meeting','participation'=>'regular'],
];
if($state==='no-link')$meetings[1]['meeting_url']='javascript:alert(1)';
if($state==='ongoing')$meetings[1]['meeting_duration_minutes']=120;
if($state==='long'){$meetings[1]['lesson_title']='Investigação de reversão química e tratamento do filme com exposições múltiplas e ajustes de compensação';$meetings[1]['course_title']=$course3['title'];}
$feedback=['title'=>'Retrato 04 — janela lateral','status'=>'Revisão solicitada','action'=>'Ler retorno e revisar','url'=>'/aluno/teste.php?id=17&view=review'];
$focus=['title'=>'EI 200 — Primeiro processamento','status'=>'Exposição registrada','action'=>'Registrar processamento','url'=>'/aluno/teste.php?id=22&view=process'];
$recent=[['title'=>'EI 200 — Primeiro processamento','scope'=>'Curso','date'=>'11/10/2026','url'=>'/aluno/teste.php?id=22'],['title'=>'Papel de contraste variável','scope'=>'Pessoal','date'=>'09/10/2026','url'=>'/aluno/teste.php?id=21']];
$home=[
 'meeting'=>in_array($state,['active','multiple','no-link','long','ongoing'],true)?student_home_next_meeting($meetings,strtotime($state==='ongoing'?'2030-10-22T22:15:00Z':'2030-10-20T00:00:00Z')):null,
 'feedback'=>in_array($state,['active','feedback','long'],true)?$feedback:null,
 'focus'=>in_array($state,['active','multiple','long','record'],true)?$focus:null,
 'courses'=>match($state){'empty'=>[],'multiple'=>[$course,$course2],'long'=>[$course,$course2,$course3],'restricted'=>[array_replace($course,['material'=>null,'available'=>0])],default=>[$course]},
 'recent'=>in_array($state,['empty','study','feedback','restricted'],true)?[]:$recent,
 'can_timer'=>$state!=='restricted',
 'can_reciprocity'=>$state!=='restricted',
];
?><!doctype html>
<html lang="pt-BR" data-theme="dark"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>:root{--bg:#101010;--surface:#171717;--surface-2:#222;--text:#f3f1ec;--muted:#aaa;--line:#383633;--line-strong:#666;--focus:#73d2ac;--inverse-bg:#f3f1ec;--inverse:#101010;--body:Arial,sans-serif;--title:Georgia,serif;--mono:monospace;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px;--ux-space-8:64px}</style>
<link rel="stylesheet" href="/template/page.css"><link rel="stylesheet" href="/assets/ui-core.css"><link rel="stylesheet" href="/assets/student-area.css"><link rel="stylesheet" href="/assets/student-workbench.css"><link rel="stylesheet" href="/assets/student-experience.css"><link rel="stylesheet" href="/assets/student-academic.css"><link rel="stylesheet" href="/assets/student-feedback.css"><link rel="stylesheet" href="/assets/student-rendered-fixes.css"><link rel="stylesheet" href="/assets/student-auth.css"><link rel="stylesheet" href="/assets/student-field-language.css"><link rel="stylesheet" href="/assets/student-quality-pass.css"><link rel="stylesheet" href="/assets/student-app-shell.css">
<title>Auditoria PHP — Início</title>
</head>
<body class="student-page student-app" data-testid="operational-home-fixture" data-state="<?=h($state)?>">
<div class="student-shell">
<?php student_shell_nav('home','student-desktop-nav');?>
<header class="student-topbar"><a class="brand student-wordmark" href="/aluno/"><span class="student-brand-compact">JS<span>.</span></span><span class="student-brand-full">JSaidler Fotografia</span></a><span class="student-appbar-divider"></span><strong class="student-appbar-title">Início</strong><span class="student-topbar-spacer"></span><button class="button button-secondary button-compact student-tools-trigger" type="button" data-toolbox-open>Atalhos</button><details class="student-user-menu"><summary aria-label="Conta"><span class="student-account-initial">J</span><span class="student-account-label">Conta</span></summary><div class="student-user-menu-panel"><button type="button" class="student-tools-menu-trigger" data-toolbox-open>Ferramentas rápidas</button></div></details></header>
<?php student_shell_toolbox(['id'=>1]); ?>
<main class="student-main">
<?php require __DIR__.'/../../app/student_home_view.php'; ?>
<?php student_record_create_dialog($state==='empty'?[]:[['cohort_id'=>12,'course_title'=>$course['title'],'cohort_title'=>$course['cohort']]],'fixture-csrf',$state==='error'?['title'=>'EI 400 — tratamento alterado','context_scope'=>'course','context_cohort_id'=>'12']:[],$state==='error'?'Verifique o título e tente novamente.':'','/aluno/'); ?>
</main>
<?php student_shell_nav('home','student-mobile-nav'); ?>
</div>
<script defer src="/assets/student-workbench.js"></script><script defer src="/assets/student-experience.js"></script>
</body></html>
