<?php
declare(strict_types=1);
/* Interaction geometry fixture. The navigation is the same operational PHP
 * renderer used by aluno/teste.php; content is controlled example data, not
 * an authenticated database replacement. */
function h(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
require_once __DIR__.'/../../app/student_record_workspace_navigation.php';
$default=($_GET['failed']??'')==='result'?'resultado':'exposicao';
?><!doctype html>
<html lang="pt-BR" data-theme="dark">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>:root{--bg:#101416;--surface:#1b2223;--surface-2:#273031;--text:#f1f3ee;--muted:#a6b0ae;--line:#34403f;--line-strong:#34403f;--focus:#d5e6b6;--student-v2-ink:#162118;--inverse:#101416;--inverse-bg:#f1f3ee;--body:Arial,sans-serif;--title:Arial,sans-serif;--mono:monospace;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px;--ux-space-8:64px}</style>
<link rel="stylesheet" href="/template/page.css"><link rel="stylesheet" href="/assets/ui-core.css"><link rel="stylesheet" href="/assets/student-area.css"><link rel="stylesheet" href="/assets/student-workbench.css"><link rel="stylesheet" href="/assets/student-experience.css"><link rel="stylesheet" href="/assets/student-caderno.css"><link rel="stylesheet" href="/assets/student-quality-pass.css"><link rel="stylesheet" href="/assets/student-app-shell.css"><title>Registro de pesquisa · auditoria de navegação</title></head>
<body class="student-page student-app"><div class="student-shell">
<nav class="student-desktop-nav" aria-label="Área do aluno"><a class="student-sidebar-brand" href="/aluno/">JS<span>.</span></a><a href="/aluno/">Início</a><a href="/aluno/agenda.php">Agenda</a><a href="/aluno/cursos.php">Cursos</a><a href="/aluno/caderno.php" aria-current="page">Caderno</a><a href="/aluno/ferramentas.php">Laboratório</a></nav>
<header class="student-topbar"><a class="student-wordmark" href="/aluno/">JS</a><strong class="student-appbar-title">Registro fotográfico</strong><span class="student-topbar-spacer"></span></header>
<main class="student-main">
<header class="student-record-header"><div class="student-record-context"><a class="student-back" href="/aluno/caderno.php">← Caderno</a><span class="student-status">Rascunho</span></div>
<p class="student-kicker">Registro pessoal</p><h1 class="student-title student-title-record">Teste EI 200 · FeCl₃</h1>
<p class="student-record-purpose">Três partes independentes do mesmo registro. Comece por qualquer uma delas.</p></header>
<?php student_record_workspace_navigation(); ?><span data-record-workspace-default="<?=h($default)?>" hidden></span>
<section class="student-section student-record-section" id="exposicao" data-record-workspace-panel>
 <div class="student-workflow-heading"><div><p class="student-kicker">Exposição</p><h2 class="student-subtitle">Como expus</h2></div><p>Registre ou corrija o que quiser documentar sobre a exposição.</p></div>
 <form class="student-mobile-form student-record-form">
 <div class="student-form-group"><h3>Dados da exposição</h3><p class="student-form-group-intro">Preencha apenas o que fizer sentido para este registro.</p>
 <div class="student-form-grid"><label class="form-field student-span-2">Título<input name="title" value="Teste EI 200 · FeCl₃" required></label>
 <label class="form-field">Data<input type="date" name="test_date" value="2026-10-01"></label><label class="form-field">Filme<input name="film" value="Fuji HR-U"></label><label class="form-field">EI<input name="iso_reference" value="200"></label>
 <label class="form-field">Abertura<input name="aperture" value="f/64"></label><label class="form-field">Tempo sem reciprocidade<input name="calculated_time" value="04 s"></label><label class="form-field">Tempo com reciprocidade<input name="reciprocity_time" readonly value="07 s"></label>
 <details class="student-optional-fields student-span-2"><summary>Dados opcionais</summary><div class="student-form-grid"><label class="form-field">Lote<input name="lot"></label></div></details>
 </div></div>
 <details class="student-record-media-disclosure"><summary><span><strong>Referência da cena</strong><small>Imagem opcional para documentar a situação fotografada</small></span><span class="student-record-disclosure-chevron" aria-hidden="true"></span></summary><div class="student-capture-block"><div><span class="student-capture-number">A</span><div><h3>Imagem da cena</h3><p>Não é a fotografia obtida no processo.</p></div></div></div></details>
 <div class="student-actions student-record-save-actions"><button class="button button-primary" type="button">Salvar exposição</button></div></form>
</section>
<section class="student-workflow-panel student-record-section" id="processamento" data-record-workspace-panel>
<div class="student-workflow-heading"><div><p class="student-kicker">Processamento</p><h2 class="student-subtitle">Roteiro e etapas</h2></div><p>Associe um roteiro como referência ou anote etapas livremente.</p></div>
<div class="student-process-path-choice"><div class="student-actions"><a class="button button-primary" href="/aluno/registro-roteiro.php?test=7">Associar roteiro</a></div></div>
<details class="student-manual-process" id="adicionar-etapa"><summary><span><strong>Adicionar etapa</strong><small>Anote uma etapa sem associar um roteiro.</small></span><span>＋</span></summary><form class="student-process-step-form student-form-grid"><label class="form-field">Tempo<input name="duration" placeholder="07:00"></label><label class="form-field">Temperatura<input name="temperature" placeholder="26 °C"></label></form></details>
</section>
<section class="student-section student-record-section" id="resultado" data-record-workspace-panel>
<div class="student-workflow-heading"><div><p class="student-kicker">Resultado</p><h2 class="student-subtitle">O que obtive</h2></div><p>Registre o resultado quando quiser. Ele não depende de nenhuma etapa anterior.</p></div>
<details class="student-result-context"><summary><span>Resumo do registro</span><small>Exposição e processamento</small></summary><div class="student-result-context-grid"><article><span>Exposição</span><p>EI 200</p></article></div></details>
<div class="student-result-flow"><form class="student-mobile-form student-result-form"><div class="student-capture-block"><div><span class="student-capture-number">B</span><div><h3>Imagem do resultado</h3><p>Fotografia da chapa processada.</p></div></div></div>
<label class="form-field">Anotações sobre o resultado<textarea name="notes" rows="5"></textarea></label><div class="student-actions student-record-save-actions"><button class="button button-primary" type="button">Salvar resultado</button></div></form></div>
</section>
<div class="student-actions student-record-footer-actions"><a class="button button-secondary" href="/aluno/caderno.php">← Voltar ao Caderno</a></div>
</main>
<nav class="student-mobile-nav" aria-label="Área do aluno"><a href="/aluno/">Início</a><a href="/aluno/agenda.php">Agenda</a><a href="/aluno/cursos.php">Cursos</a><a href="/aluno/caderno.php" aria-current="page">Caderno</a><a href="/aluno/ferramentas.php">Laboratório</a></nav></div>
<script defer src="/assets/student-record-workspace.js"></script></body></html>
