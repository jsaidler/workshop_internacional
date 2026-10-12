<?php
declare(strict_types=1);
// Uses the actual operational renderer; deliberately simulates failed writes,
// not a real inventory database or CSRF-protected hosted POST.
require_once __DIR__.'/../../app/student_inventory_create_panel.php';
function h(string $value): string {return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$isPost=($_SERVER['REQUEST_METHOD']??'GET')==='POST';
$mode=(string)($_GET['mode']??'');
$failed=$isPost||$mode==='error';
$draft=$isPost?$_POST:($failed?[
 'item_kind'=>'solution','name'=>'Parodinal <lote>','quantity'=>'125.5',
 'unit'=>'g','category'=>'Revelador','lot_code'=>'RX-10','acquired_or_prepared_at'=>'2026-10-11',
 'expires_at'=>'2026-11-12','minimum_quantity'=>'20','location'=>'Armário A','notes'=>'Anotação importante'
]:[]);
$error=$failed?'Não foi possível cadastrar o item. Revise os dados.':'';
?><!doctype html>
<html lang="pt-BR" data-theme="dark"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>:root{--bg:#101010;--surface:#171717;--surface-2:#202020;--text:#f3f1ec;--muted:#aaa49a;--line:#383633;--line-strong:#d5d0c7;--focus:#73d2ac;--inverse-bg:#f3f1ec;--inverse:#101010;--body:Arial,sans-serif;--title:Georgia,serif;--mono:"Courier New",monospace;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px;--ux-space-8:64px}</style>
<link rel="stylesheet" href="/template/page.css"><link rel="stylesheet" href="/assets/ui-core.css">
<link rel="stylesheet" href="/assets/student-area.css"><link rel="stylesheet" href="/assets/student-workbench.css">
<link rel="stylesheet" href="/assets/student-experience.css">
<link rel="stylesheet" href="/assets/student-rendered-fixes.css" data-student-rendered-fixes>
<link rel="stylesheet" href="/assets/student-lab-stock.css"><link rel="stylesheet" href="/assets/student-quality-pass.css">
<title>Inventário — recuperação da criação</title></head>
<body class="student-page" data-inventory-fixture="real-renderer-not-hosted-post"><div class="student-shell">
<header class="student-topbar"><a class="brand student-wordmark" href="/aluno/">JSaidler Fotografia</a><span class="student-topbar-spacer"></span><button class="button button-secondary button-compact student-tools-trigger" type="button">Atalhos</button><details class="student-user-menu"><summary>Conta</summary></details></header>
<main class="student-main" data-student-scroll-root>
  <div class="student-appbar"><a class="student-back" href="/aluno/ferramentas.php">← Laboratório</a></div>
  <div class="student-page-heading"><div><p class="student-kicker">Laboratório · estoque físico</p><h1 class="student-title">Inventário</h1><p class="student-lab-stock-intro">Itens e soluções disponíveis no laboratório.</p></div><button class="button button-primary" type="button" data-inventory-new-toggle aria-controls="student-inventory-new" aria-expanded="<?=$failed?'true':'false'?>">Novo item</button></div>
  <section class="student-stock-summary"><article><span>Itens ativos</span><strong>0</strong></article><article><span>Estoque baixo</span><strong>0</strong></article><article><span>Soluções preparadas</span><strong>0</strong></article></section>
  <?php student_inventory_create_panel($draft,$error,'fixture-csrf'); ?>
  <section class="student-section" data-student-local-key="inventory-stock"><div class="student-empty"><strong>Seu inventário ainda está vazio.</strong><button type="button" class="button button-primary" data-inventory-new-toggle aria-controls="student-inventory-new" aria-expanded="<?=$failed?'true':'false'?>">Adicionar primeiro item</button></div></section>
  <section class="student-section" data-student-local-key="inventory-history"><div class="student-empty">Nenhuma movimentação registrada.</div></section>
</main>
<nav class="student-mobile-nav" aria-label="Área do aluno"><a href="/aluno/">Início</a><a href="/aluno/cursos.php">Cursos</a><a href="/aluno/caderno.php">Caderno</a><a href="/aluno/ferramentas.php" aria-current="page">Laboratório</a></nav>
</div><script defer src="/assets/student-workbench.js"></script><script defer src="/assets/student-local-actions.js"></script>
</body></html>
