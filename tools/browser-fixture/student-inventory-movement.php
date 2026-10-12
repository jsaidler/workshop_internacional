<?php
declare(strict_types=1);
// Test-only stock values, REAL shared PHP movement renderer. POST responses here
// are simulations; SQLite operational rules are tested in the PHP runtime test.
require_once __DIR__.'/../../app/student_inventory_move_panel.php';
function h(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function student_workbench_number(float $n):string{return (string)$n;}
function student_uuid():string{return 'ab123456-1111-4111-8111-123456789abc';}
$isPost=($_SERVER['REQUEST_METHOD']??'GET')==='POST';
$mode=(string)($_GET['mode']??'normal');
$stock=180;
$item=['id'=>7,'item_kind'=>'raw','name'=>'Parodinal','quantity'=>$stock,'unit'=>'ml','lot_code'=>'P-07','location'=>'Armário químico'];
$direction=(string)($_POST['direction']??'out');
$amount=(float)($_POST['amount']??0);
$error='';
if($isPost){
    if($mode==='server-error')$error='O servidor não conseguiu registrar a movimentação.';
    elseif($amount<=0)$error='Informe uma quantidade válida.';
    elseif(in_array($direction,['out','adjust_down'],true)&&$amount>$stock)$error='O estoque não é suficiente para esta baixa.';
    elseif(str_starts_with($direction,'adjust_')&&trim((string)($_POST['note']??''))==='')$error='Informe o motivo da correção de saldo.';
    else{header('Location: /tools/browser-fixture/student-inventory-movement.php?mode=success',true,303);exit;}
}
$draft=$isPost?$_POST:($mode==='error'?['item_id'=>7,'movement_uuid'=>student_uuid(),'direction'=>'adjust_down','amount'=>'25','note'=>'Recontagem <b>']:[]);
if($mode==='error')$error='Erro de servidor ao salvar.';
?><!doctype html><html lang="pt-BR" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>:root{--bg:#101010;--surface:#171717;--surface-2:#202020;--text:#f3f1ec;--muted:#aaa49a;--line:#383633;--line-strong:#d5d0c7;--focus:#73d2ac;--inverse-bg:#f3f1ec;--inverse:#101010;--body:Arial,sans-serif;--title:Georgia,serif;--mono:monospace;--student-border:#383633;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px;--ux-space-8:64px}</style>
<link rel="stylesheet" href="/template/page.css"><link rel="stylesheet" href="/assets/ui-core.css"><link rel="stylesheet" href="/assets/student-area.css"><link rel="stylesheet" href="/assets/student-workbench.css"><link rel="stylesheet" href="/assets/student-experience.css"><link rel="stylesheet" href="/assets/student-rendered-fixes.css" data-student-rendered-fixes><link rel="stylesheet" href="/assets/student-lab-stock.css"><link rel="stylesheet" href="/assets/student-quality-pass.css">
<title>Movimentação real — Inventário</title></head>
<body class="student-page" data-inventory-fixture="real-php-renderer-simulated-post">
<div class="student-shell"><header class="student-topbar"><a class="brand student-wordmark" href="/aluno/">JSaidler Fotografia</a><span class="student-topbar-spacer"></span><button class="button button-secondary button-compact student-tools-trigger" type="button">Atalhos</button><details class="student-user-menu"><summary>Conta</summary></details></header>
<main class="student-main" data-student-scroll-root>
<div class="student-appbar"><a class="student-back" href="/aluno/ferramentas.php">← Laboratório</a></div>
<div class="student-page-heading"><div><p class="student-kicker">Laboratório · estoque físico</p><h1 class="student-title">Inventário</h1><p class="student-lab-stock-intro">Insumos e soluções que <strong>existem fisicamente no seu laboratório</strong>. O saldo muda quando você registra uma movimentação.</p></div><button class="button button-primary">Novo item</button></div>
<?php if($mode==='success'):?><p class="ui-alert ui-alert-notice" role="status">Movimentação registrada.</p><?php endif;?>
<section class="student-stock-summary" aria-label="Resumo do inventário"><article><span>Itens ativos</span><strong><?= $mode==='empty'?0:1 ?></strong><small>insumos e soluções disponíveis</small></article><article><span>Estoque baixo</span><strong>0</strong><small>itens no mínimo definido ou abaixo</small></article><article><span>Soluções preparadas</span><strong>0</strong><small>banhos ou soluções fisicamente disponíveis</small></article></section>
<section class="student-section" data-student-local-key="inventory-stock">
<div class="student-stock-section-heading"><div><p class="student-kicker">Estoque</p><h2 class="student-subtitle">O que tenho disponível</h2></div></div>
<div class="student-inventory-list">
<?php if($mode==='empty'):?><div class="student-empty student-preparation-empty"><strong>Seu inventário ainda está vazio.</strong><p>Comece cadastrando um produto que você realmente tem no laboratório.</p><button class="button button-primary">Adicionar primeiro item</button></div><?php else:?>
<article class="student-inventory-item" id="inventory-item-7"><div class="student-inventory-main"><span class="student-status">Insumo</span><h3><?=h($item['name'])?></h3><p>Lote P-07 · Armário químico</p></div>
<div class="student-inventory-balance"><strong><?=$stock?></strong><span>ml disponíveis</span></div>
<div class="student-inventory-item-actions"><a class="button button-secondary button-compact" href="/aluno/inventario-item.php?id=7">Editar item</a><?php student_inventory_move_panel($item,'fixture-csrf',$draft,$error); ?><form><button class="button button-danger button-compact" type="button">Arquivar</button></form></div></article><?php endif;?>
</div></section>
<div class="student-lab-crosslink"><div><strong>Procurando uma condição de revelação para reutilizar?</strong><p>Uma predefinição salva parâmetros como diluição, temperatura e tempo. Ela não significa que a solução esteja preparada ou disponível em estoque.</p></div><a class="button button-secondary button-compact" href="/aluno/preparos.php">Ver predefinições</a></div>
<section class="student-section" data-student-local-key="inventory-history"><div class="student-stock-section-heading"><div><p class="student-kicker">Histórico</p><h2 class="student-subtitle">O que alterou o saldo</h2></div></div><div class="student-inventory-history"><div><span>Parodinal</span><strong class="is-out">−20 ml</strong><small>Saída / consumo · 2026-10-12 18:00 UTC · Uso fora do Caderno</small></div></div></section>
</main><nav class="student-mobile-nav" aria-label="Área do aluno"><a href="/aluno/">Início</a><a href="/aluno/cursos.php">Cursos</a><a href="/aluno/caderno.php">Caderno</a><a href="/aluno/ferramentas.php" aria-current="page">Laboratório</a></nav></div>
<script defer src="/assets/student-inventory-movement.js"></script></body></html>
