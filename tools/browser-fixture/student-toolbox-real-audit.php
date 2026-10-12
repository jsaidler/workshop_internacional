<?php
declare(strict_types=1);
// This visual audit renders the ACTUAL toolbox PHP function, with controlled tool
// permissions and no access to student records. It replaces a synthetic toolbox
// mock which falsely displayed a timer that is not in the production toolbox.
function database(): PDO {
    static $db=null;
    return $db??=new PDO('sqlite::memory:');
}
function student_tools_for_student(PDO $db,int $studentId): array {
    $keys=((string)($_GET['mode']??''))==='restricted'
       ?['reciprocity']
       :['reciprocity','exposure','lab_timer','solution_prep'];
    return array_map(static fn(string $key):array=>['tool_key'=>$key],$keys);
}
require_once __DIR__.'/../../app/student_shell.php';
?><!doctype html>
<html lang="pt-BR" data-theme="dark"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>:root{--bg:#101010;--surface:#171717;--surface-2:#222;--text:#f3f1ec;--muted:#aaa;--line:#383633;--line-strong:#666;--focus:#73d2ac;--inverse-bg:#f3f1ec;--inverse:#101010;--body:Arial,sans-serif;--title:Georgia,serif;--mono:monospace;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px;--ux-space-8:64px}</style>
<link rel="stylesheet" href="/template/page.css">
<link rel="stylesheet" href="/assets/ui-core.css">
<link rel="stylesheet" href="/assets/student-area.css">
<link rel="stylesheet" href="/assets/student-workbench.css">
<link rel="stylesheet" href="/assets/student-experience.css">
<link rel="stylesheet" href="/assets/student-rendered-fixes.css" data-student-rendered-fixes>
<link rel="stylesheet" href="/assets/student-quality-pass.css">
<title>Atalhos — auditoria fiel ao PHP operacional</title></head>
<body class="student-page" data-toolbox-fixture="real-php-controlled-permissions">
<div class="student-shell"><header class="student-topbar"><a class="student-wordmark" href="/aluno/">JSaidler Fotografia</a><span class="student-topbar-spacer"></span><button class="button button-secondary button-compact student-tools-trigger" type="button" data-toolbox-open>Atalhos</button></header>
<?php student_shell_toolbox(['id'=>42]); ?>
<main class="student-main" data-student-scroll-root><h1 class="student-title">Caderno</h1><p class="student-lead">O conteúdo mantém sua posição quando os atalhos são abertos e fechados.</p><div style="min-height:120vh"></div><p id="last-content">Último conteúdo para testar rolagem.</p></main>
<nav class="student-mobile-nav" aria-label="Área do aluno"><a href="/aluno/">Início</a><a href="/aluno/cursos.php">Cursos</a><a href="/aluno/caderno.php" aria-current="page">Caderno</a><a href="/aluno/ferramentas.php">Laboratório</a></nav></div>
<script defer src="/assets/student-reciprocity.js"></script>
<script defer src="/assets/student-workbench.js"></script>
<script defer src="/assets/student-experience.js"></script>
</body></html>
