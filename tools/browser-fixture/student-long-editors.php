<?php
declare(strict_types=1);
/* Real PHP form renderers; synthetic data and POST simulation, no auth/DB. */
require_once __DIR__.'/../../app/student_workbench.php';
require_once __DIR__.'/../../app/student_preparation_editor.php';
require_once __DIR__.'/../../app/student_calibration_editor.php';
function h(string $s):string{return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$kind=(string)($_GET['kind']??'preparation');
if(!in_array($kind,['preparation','calibration'],true))exit('Invalid fixture kind');
$mode=(string)($_GET['mode']??'list');
$isPost=($_SERVER['REQUEST_METHOD']??'GET')==='POST';
$path=$kind==='preparation'?'/aluno/preparos.php':'/aluno/calibracao.php';
if($isPost&&$mode!=='error'){
    header('Location: /tools/browser-fixture/student-long-editors.php?kind='.$kind.'&mode=updated',true,303);
    exit;
}
$edit=$mode==='edit'||($mode==='error'&&(int)($_POST['id']??0)>0);
$error=$mode==='error'?'Não foi possível salvar os dados. Confira o formulário.':'';
$prep=$edit?['id'=>7,'label'=>'Parodinal 10/550 · EI 200','developer_key'=>'parodinal','developer_name'=>'Parodinal','developer_amount'=>'10','water_amount'=>'540','temperature'=>'26 °C','duration'=>'07:00','agitation'=>'leve','ingredients_json'=>'[]']:null;
$cal=$edit?['id'=>7,'label'=>'Fuji HR-U · Parodinal','film'=>'Fuji HR-U','developer'=>'Parodinal','preparation'=>'15/550','temperature'=>'26 °C','prebath'=>'Sim','white_time'=>'03:00','notes'=>'Teste']:null;
$draft=$isPost?$_POST:($mode==='error'?($kind==='preparation'?['label'=>'Teste <script>','developer_key'=>'other','developer_name'=>'Revelador experimental','developer_amount'=>'25','water_amount'=>'400','temperature'=>'30 °C','duration'=>'06:00','agitation'=>'Rotação']:['label'=>'Teste <script>','film'=>'Fuji HR-U','developer'=>'Outro','preparation'=>'25/550','temperature'=>'30 °C','prebath'=>'Não','white_time'=>'04:30','notes'=>'Rascunho']):[]);
$title=$kind==='preparation'?'Predefinições de revelação':'Referências de calibração';
$heading=$kind==='preparation'?'Editar predefinição':'Editar calibração';
?><!doctype html><html lang="pt-BR" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>:root{--bg:#101010;--surface:#171717;--surface-2:#202020;--text:#f3f1ec;--muted:#aaa49a;--line:#383633;--line-strong:#d5d0c7;--focus:#73d2ac;--inverse-bg:#f3f1ec;--inverse:#101010;--body:Arial,sans-serif;--title:Georgia,serif;--mono:monospace;--student-border:#383633;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px;--ux-space-8:64px}</style>
<link rel="stylesheet" href="/template/page.css"><link rel="stylesheet" href="/assets/ui-core.css"><link rel="stylesheet" href="/assets/student-area.css"><link rel="stylesheet" href="/assets/student-workbench.css"><link rel="stylesheet" href="/assets/student-experience.css"><link rel="stylesheet" href="/assets/student-rendered-fixes.css" data-student-rendered-fixes><link rel="stylesheet" href="/assets/student-lab-stock.css"><link rel="stylesheet" href="/assets/student-quality-pass.css">
<title>Auditoria — <?=$title?></title></head>
<body class="student-page" data-long-editor-fixture="<?=$kind?>">
<div class="student-shell"><header class="student-topbar"><a class="brand student-wordmark" href="/aluno/">JSaidler Fotografia</a><span class="student-topbar-spacer"></span><button class="button button-secondary button-compact student-tools-trigger" type="button">Atalhos</button><details class="student-user-menu"><summary>Conta</summary></details></header>
<main class="student-main" data-student-scroll-root>
<?php if(in_array($mode,['list','updated'],true)):?>
<div class="student-appbar"><a class="student-back" href="/aluno/ferramentas.php">← Laboratório</a></div>
<div class="student-page-heading"><div><p class="student-kicker">Laboratório · parâmetros reutilizáveis</p><h1 class="student-title"><?=$title?></h1></div><a class="button button-primary" href="<?=$path?>?novo=1" data-student-editor-link><?=$kind==='preparation'?'Nova predefinição':'Nova referência'?></a></div>
<?php if($mode==='updated'):?><p class="ui-alert ui-alert-notice" role="status">Dados atualizados.</p><?php endif;?>
<dialog class="student-create-dialog" data-student-editor-dialog><div data-student-editor></div></dialog>
<section class="student-section" data-student-local-key="<?=$kind==='preparation'?'preparations-list':'calibrations-list'?>"><h2><?=$kind==='preparation'?'Sua biblioteca':'Referências'?></h2>
<article class="<?=$kind==='preparation'?'student-preparation-card':'student-simple-row'?>"><div><h3><?=$mode==='updated'?'Registro atualizado':'Registro existente'?></h3></div><a class="button button-secondary button-compact" href="<?=$path?>?editar=7" data-student-editor-link>Editar</a></article>
</section>
<?php else:?>
<div class="student-appbar"><a class="student-back" href="<?=$path?>">← <?=$title?></a></div>
<?php if($kind==='preparation'):
$ingredients=[];if($prep){$ingredients=['café'=>'','carbonato'=>'','ascórbico'=>''];}
student_preparation_editor($prep,student_process_developer_catalog(),$ingredients,$error,'fixture-csrf',$draft);
else:student_calibration_editor($cal,$error,'fixture-csrf',$draft);endif;?>
<?php endif;?>
</main><nav class="student-mobile-nav" aria-label="Área do aluno"><a href="/aluno/">Início</a><a href="/aluno/cursos.php">Cursos</a><a href="/aluno/caderno.php">Caderno</a><a href="/aluno/ferramentas.php" aria-current="page">Laboratório</a></nav>
</div><script defer src="/assets/student-workbench.js"></script><script defer src="/assets/student-local-actions.js"></script></body></html>
