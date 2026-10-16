<?php
declare(strict_types=1);
require_once __DIR__.'/../../app/cms_settings.php';
$fixtureDesign=cms_design_defaults();
/** Same operational renderer as /aluno/ferramentas.php, with controlled release gates. */
require_once __DIR__.'/../../app/student_shell.php';
function database():PDO {static $db=null;return $db??=new PDO('sqlite::memory:');}
function student_tools_for_student(PDO $db,int $id):array {
  global $tools;
  return $tools;
}
function h(mixed $x):string {return htmlspecialchars((string)$x,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$state=(string)($_GET['state']??'full');
if(!in_array($state,['full','empty','limited','chemistry','operations'],true)){http_response_code(404);exit;}
$keys=match($state){
  'empty'=>[],
  'limited'=>['reciprocity'],
  'chemistry'=>['solution_prep'],
  'operations'=>['lab_timer','lab_inventory','calibration'],
  default=>['reciprocity','exposure','lab_timer','solution_prep','lab_inventory','calibration'],
};
$tools=array_map(static fn($key)=>['tool_key'=>$key],$keys);
$byKey=[];foreach($tools as $tool)$byKey[(string)$tool['tool_key']]=$tool;
$formulas=isset($byKey['solution_prep'])?[
  'parodinal'=>['label'=>'Parodinal · exemplo de interface','base_volume'=>250,'fresh'=>false,'ingredients'=>[['name'=>'Solução A','amount'=>10,'unit'=>'ml'],['name'=>'Água','amount'=>240,'unit'=>'ml']]],
  'wash'=>['label'=>'Solução de lavagem · exemplo de interface','base_volume'=>250,'fresh'=>true,'ingredients'=>[['name'=>'Componente 1','amount'=>5,'unit'=>'ml'],['name'=>'Água','amount'=>245,'unit'=>'ml']]],
]:[];
$recipeNotes=isset($byKey['solution_prep'])?['parodinal'=>['preparation'=>'Instruções ilustrativas para testar a apresentação; não são uma receita validada.','warning'=>'Use protocolos técnicos verificados e equipamentos de proteção.']]:[];
?><!doctype html><html lang="pt-BR" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style data-cms-design-fonts><?=cms_design_font_import_css($fixtureDesign)?></style>
<link rel="stylesheet" href="/template/page.css"><style data-cms-design-tokens><?=cms_design_css($fixtureDesign)?></style><link rel="stylesheet" href="/assets/ui-core.css"><link rel="stylesheet" href="/assets/student-area.css"><link rel="stylesheet" href="/assets/student-workbench.css"><link rel="stylesheet" href="/assets/student-experience.css"><link rel="stylesheet" href="/assets/student-academic.css"><link rel="stylesheet" href="/assets/student-rendered-fixes.css"><link rel="stylesheet" href="/assets/student-quality-pass.css"><link rel="stylesheet" href="/assets/student-laboratory.css">
<title>Laboratório · teste da interface real</title></head>
<body class="student-page student-app" data-lab-state="<?=h($state)?>"><div class="student-shell">
<?php student_shell_nav('laboratory','student-desktop-nav');?>
<header class="student-topbar"><a class="brand student-wordmark" href="/aluno/"><span class="student-brand-compact">JS<span>.</span></span><span class="student-brand-full">JSaidler Fotografia</span></a><span class="student-appbar-divider"></span><strong class="student-appbar-title">Laboratório</strong><span class="student-topbar-spacer"></span><button class="button button-secondary button-compact student-tools-trigger" type="button" data-toolbox-open>Atalhos</button><details class="student-user-menu"><summary aria-label="Conta"><span class="student-account-initial">J</span><span class="student-account-label">Conta</span></summary><div class="student-user-menu-panel"><button class="student-tools-menu-trigger" type="button" data-toolbox-open>Ferramentas rápidas</button></div></details></header>
<?php student_shell_toolbox(['id'=>1]);?>
<main class="student-main" data-student-scroll-root><?php require __DIR__.'/../../app/student_laboratory_view.php';?></main>
<?php student_shell_nav('laboratory','student-mobile-nav');?></div>
<script defer src="/assets/student-workbench.js"></script><script defer src="/assets/student-experience.js"></script></body></html>
