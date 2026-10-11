<?php
declare(strict_types=1);
require_once __DIR__.'/../../app/student_record_media_controls.php';
function gallery_form(string $phase): string {
    return '<section class="student-section student-record-section" id="'.($phase==='scene'?'exposicao':'resultado').'">'
      .'<div class="student-workflow-heading"><h2>'.($phase==='scene'?'Referência da cena':'Imagem do resultado').'</h2></div>'
      .'<form method="post" enctype="multipart/form-data" class="student-mobile-form '.($phase==='scene'?'student-record-form':'student-result-form').'">'
      .'<input type="hidden" name="action" value="save_result" data-process-action><input type="hidden" name="phase" value="'.$phase.'">'
      .'<div class="student-capture-block"><div><span class="student-capture-number">'.($phase==='scene'?'A':'B').'</span><div><h3>'.($phase==='scene'?'Referência da cena':'Imagem do resultado').'</h3><p>Até seis imagens por registro, entre as duas partes.</p></div></div>'
      .student_record_media_controls($phase,6).'</div>'
      .'</form></section>';
}
?><!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>:root{--bg:#101010;--surface:#171717;--surface-2:#222;--text:#f3f1ec;--muted:#aaa;--line:#383633;--line-strong:#666;--focus:#73d2ac;--body:Arial,sans-serif;--title:Georgia,serif;--mono:monospace;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px;--ux-space-8:64px}</style>
<link rel="stylesheet" href="/assets/ui-core.css"><link rel="stylesheet" href="/assets/student-area.css"><link rel="stylesheet" href="/assets/student-rendered-fixes.css"><link rel="stylesheet" href="/assets/student-quality-pass.css"><title>Adicionar imagens — interface de escolha</title></head>
<body class="student-page" data-testid="gallery-fixture"><div class="student-shell"><header class="student-topbar"><a class="student-wordmark" href="/aluno/">JSaidler Fotografia</a></header>
<main class="student-main" data-student-scroll-root><header class="student-record-header"><p class="student-kicker">Curso · Turma de teste</p><h1 class="student-title">Registro de fotografias</h1></header>
<?=gallery_form('scene')?><?=gallery_form('result')?>
</main><nav class="student-mobile-nav" aria-label="Área do aluno"><a href="/aluno/">Início</a><a href="/aluno/cursos.php">Cursos</a><a href="/aluno/caderno.php" aria-current="page">Caderno</a><a href="/aluno/ferramentas.php">Laboratório</a></nav></div>
<script defer src="/assets/student-experience.js"></script>
<script defer src="/assets/student-local-actions.js"></script>
</body></html>