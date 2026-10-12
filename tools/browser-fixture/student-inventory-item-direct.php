<?php
declare(strict_types=1);
require_once __DIR__.'/../../app/student_inventory_item_editor.php';
function h(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function student_workbench_number(float $number):string{return (string)$number;}
$mode=(string)($_GET['mode']??'list');
$item=['id'=>7,'item_kind'=>'solution','name'=>'Parodinal estoque','quantity'=>180,'unit'=>'ml','category'=>'Revelador','lot_code'=>'P-07','acquired_or_prepared_at'=>'2026-09-20','expires_at'=>'','minimum_quantity'=>'20','location'=>'Armário A','notes'=>''];
$error='';
if($mode==='error'){
 $error='Não foi possível salvar a alteração.';
 if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
  foreach(['name','category','lot_code','acquired_or_prepared_at','expires_at','minimum_quantity','location','notes'] as $field)
   if(isset($_POST[$field])&&is_scalar($_POST[$field]))$item[$field]=(string)$_POST[$field];
 }
}
?><!doctype html><html lang="pt-BR" data-theme="dark"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>:root{--bg:#101010;--surface:#171717;--surface-2:#222;--text:#f3f1ec;--muted:#aaa;--line:#383633;--line-strong:#666;--focus:#73d2ac;--body:Arial,sans-serif;--title:Georgia,serif;--mono:monospace;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px;--ux-space-8:64px}</style>
<link rel="stylesheet" href="/assets/ui-core.css"><link rel="stylesheet" href="/assets/student-area.css"><link rel="stylesheet" href="/assets/student-experience.css"><link rel="stylesheet" href="/assets/student-rendered-fixes.css" data-student-rendered-fixes><link rel="stylesheet" href="/assets/student-lab-stock.css"><link rel="stylesheet" href="/assets/student-quality-pass.css">
<title>Auditoria editor de item</title></head><body class="student-page" data-testid="item-route-fixture">
<div class="student-shell"><header class="student-topbar"><a class="student-wordmark" href="/aluno/">JSaidler Fotografia</a></header>
<main class="student-main" data-student-scroll-root>
<?php if(in_array($mode,['list','updated'],true)):?>
 <div class="student-appbar"><a class="student-back" href="/aluno/ferramentas.php">← Laboratório</a></div>
 <div class="student-page-heading"><h1 class="student-title">Inventário</h1></div>
 <dialog class="student-create-dialog" data-student-editor-dialog><div data-student-editor></div></dialog>
 <section class="student-section" data-student-local-key="inventory-stock"><h2>Estoque</h2><article class="student-inventory-item"><h3>Parodinal estoque</h3><p class="data-value"><?=h($mode==='updated'?'Lote atualizado':'P-07')?></p><a href="/aluno/inventario-item.php?id=7" class="button button-secondary" data-student-editor-link>Editar item</a></article></section>
 <section data-student-local-key="inventory-history"><h2>Histórico</h2><p>Sem alteração de saldo</p></section>
<?php else:?>
 <div class="student-appbar"><a class="student-back" href="/aluno/inventario.php">← Inventário</a></div>
 <?php student_inventory_item_editor($item,7,$error,'fixture-csrf'); ?>
<?php endif;?>
</main>
<nav class="student-mobile-nav" aria-label="Área do aluno"><a href="/aluno/">Início</a><a href="/aluno/cursos.php">Cursos</a><a href="/aluno/caderno.php">Caderno</a><a href="/aluno/ferramentas.php" aria-current="page">Laboratório</a></nav></div>
<script defer src="/assets/student-workbench.js"></script><script defer src="/assets/student-local-actions.js"></script>
</body></html>