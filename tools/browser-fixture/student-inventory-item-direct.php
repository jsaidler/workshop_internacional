<?php
declare(strict_types=1);
require_once __DIR__.'/../../app/cms_settings.php';
require_once __DIR__.'/../../app/student_inventory_item_editor.php';
require_once __DIR__.'/../../app/student_shell.php';
function h(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function database(): PDO {static $db=null;return $db??=new PDO('sqlite::memory:');}
function student_account_current(PDO $db): ?array {return ['id'=>1,'name'=>'Aluno'];}
function student_tools_for_student(PDO $db,int $studentId): array {return [];}
function csrf_token(string $scope): string {return 'fixture-only-token';}

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
$_SERVER['REQUEST_URI']='/aluno/inventario-item.php?id=7';
student_shell_start('Inventário',null,['id'=>1,'name'=>'Aluno']);
?>
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
<?php student_shell_end(); ?>
