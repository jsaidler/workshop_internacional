<?php
declare(strict_types=1);
// Product-parity fixture: the same PHP dialog renderer as the authenticated
// Caderno, with synthetic enrollment data and no production database access.
require_once __DIR__.'/../../app/student_record_create_dialog.php';
$mode=(string)($_GET['mode']??'normal');
$enrollments=[['cohort_id'=>12,'course_title'=>'Positivo direto','cohort_title'=>'Turma de setembro'],['cohort_id'=>17,'course_title'=>'Pesquisa fotográfica','cohort_title'=>'Turma de outubro']];
if($mode==='personal')$enrollments=[];
$error=$mode==='error'?'Não foi possível criar o registro. Verifique as informações.':'';
$draft=$mode==='error'?['title'=>'EI 200 — teste de película','test_date'=>'2026-10-09','context_scope'=>'course','context_cohort_id'=>'17']:[];
?><!doctype html>
<html lang="pt-BR" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>:root{--bg:#101010;--surface:#171717;--surface-2:#222;--text:#f3f1ec;--muted:#aaa;--line:#383633;--line-strong:#666;--focus:#73d2ac;--inverse-bg:#f3f1ec;--inverse:#101010;--body:Arial,sans-serif;--title:Georgia,serif;--mono:monospace;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px;--ux-space-8:64px}</style>
<link rel="stylesheet" href="/template/page.css"><link rel="stylesheet" href="/assets/ui-core.css"><link rel="stylesheet" href="/assets/student-area.css"><link rel="stylesheet" href="/assets/student-workbench.css"><link rel="stylesheet" href="/assets/student-experience.css"><link rel="stylesheet" href="/assets/student-rendered-fixes.css" data-student-rendered-fixes><link rel="stylesheet" href="/assets/student-quality-pass.css">
<title>Auditoria — criar registro</title></head>
<body class="student-page" data-testid="create-record-parity"><div class="student-shell">
<header class="student-topbar"><a class="brand student-wordmark" href="/aluno/">JSaidler Fotografia</a></header>
<main class="student-main" data-student-scroll-root><div class="student-page-heading"><div><p class="student-kicker">Caderno</p><h1 class="student-title">Caderno de Processos</h1></div><button class="button button-primary" type="button" data-record-create-open>Novo registro</button></div>
<?php student_record_create_dialog($enrollments,'test-csrf',$draft,$error); ?>
<section class="student-section"><h2 class="student-subtitle">Seus registros</h2><p>Registros ilustrativos, sem conexão com o banco.</p><button class="button button-secondary" type="button" data-record-create-open>Criar primeiro registro</button></section>
</main><nav class="student-mobile-nav" aria-label="Área do aluno"><a href="/aluno/">Início</a><a href="/aluno/cursos.php">Cursos</a><a href="/aluno/caderno.php" aria-current="page">Caderno</a><a href="/aluno/ferramentas.php">Laboratório</a></nav></div>
<script defer src="/assets/student-workbench.js"></script><script defer src="/assets/student-experience.js"></script>
</body></html>
