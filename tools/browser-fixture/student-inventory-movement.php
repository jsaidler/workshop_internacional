<?php
declare(strict_types=1);
/*
 * Operational inventory visual fixture. All visible HTML (including creation,
 * stock, metrics, item options and history) comes from the same PHP renderer as
 * aluno/inventario.php. The in-memory stock and POST simulations are fixture only.
 */
require_once __DIR__.'/../../app/cms_settings.php';
require_once __DIR__.'/../../app/student_inventory_create_panel.php';
require_once __DIR__.'/../../app/student_inventory_move_panel.php';
require_once __DIR__.'/../../app/student_shell.php';
function h(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function student_workbench_number(float $n):string{return (string)$n;}
function student_uuid():string{return 'ab123456-1111-4111-8111-123456789abc';}
function database():PDO{static $db=null;return $db??=new PDO('sqlite::memory:');}
function student_account_current(PDO $db):?array{return ['id'=>1,'name'=>'Aluno'];}
function student_tools_for_student(PDO $db,int $studentId):array{return [];}
function csrf_token(string $scope):string{return 'fixture-only-token';}

$mode=(string)($_GET['mode']??'normal');
$isPost=($_SERVER['REQUEST_METHOD']??'GET')==='POST';
$stock=180;
$item=['id'=>7,'item_kind'=>'raw','name'=>'Parodinal','quantity'=>$stock,'unit'=>'ml',
       'lot_code'=>'P-07','location'=>'Armário químico','minimum_quantity'=>null,'expires_at'=>''];
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
$draft=$isPost?$_POST:($mode==='error'?[
    'action'=>'move','item_id'=>7,'movement_uuid'=>student_uuid(),'direction'=>'adjust_down',
    'amount'=>'25','note'=>'Recontagem <b>'
]:[]);
if($mode==='error'){
    $error='Erro de servidor ao salvar.';
    if(!$isPost)$_POST=$draft;
}
if($mode==='success')$_GET['saved']='move';
$failedCreate=false;$createDraft=[];
$failedMove=$error!=='';
$items=$mode==='empty'?[]:[$item];
$lowCount=0;$solutionCount=0;
$movements=[[
    'item_name'=>'Parodinal','quantity_delta'=>-20,'unit'=>'ml','movement_type'=>'consume',
    'created_at'=>'2026-10-12T18:00:00Z','note'=>'Uso fora do Caderno','test_id'=>null
]];
$student=['id'=>1,'name'=>'Aluno'];
$_SERVER['REQUEST_URI']='/aluno/inventario.php';
student_shell_start('Inventário do laboratório',null,$student);
require __DIR__.'/../../app/student_inventory_view.php';
student_shell_end();
