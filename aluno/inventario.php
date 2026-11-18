<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';require_once __DIR__.'/../app/student_inventory_create_panel.php';require_once __DIR__.'/../app/student_inventory_move_panel.php';security_headers();student_private_headers();$db=database();$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2Finventario.php',true,303);exit;}$studentId=(int)$student['id'];try{student_tool_require($db,$studentId,'lab_inventory');}catch(Throwable){http_response_code(403);exit('Ferramenta indisponível.');}$error='';$submittedAction=(string)($_POST['action']??'');
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 if(!verify_csrf('student-inventory',$_POST['_csrf']??null))$error='Solicitação inválida.';else try{$action=(string)($_POST['action']??'create');if($action==='create')student_inventory_create($db,$studentId,$_POST);elseif($action==='move'){
  $amount=student_workbench_float($_POST['amount']??null);
  if($amount===null||!is_finite($amount)||$amount<=0||round($amount,3)!==$amount)throw new RuntimeException('Informe uma quantidade válida (até três casas decimais).');
  $direction=(string)($_POST['direction']??'');
  $types=['in'=>[1,'in'],'out'=>[-1,'consume'],'adjust_up'=>[1,'adjust'],'adjust_down'=>[-1,'adjust']];
  if(!isset($types[$direction]))throw new RuntimeException('Escolha o tipo de movimentação.');
  $note=student_workspace_text($_POST['note']??'',500);
  if(str_starts_with($direction,'adjust_')&&trim($note)==='')throw new RuntimeException('Informe o motivo da correção de saldo.');
  $uuid=(string)($_POST['movement_uuid']??'');
  if(!preg_match('/^[a-zA-Z0-9-]{16,80}$/D',$uuid))throw new RuntimeException('Solicitação inválida. Reabra a movimentação.');
  student_inventory_move($db,$studentId,(int)($_POST['item_id']??0),$types[$direction][0]*$amount,$types[$direction][1],$note,null,null,true,$uuid);
}elseif($action==='archive')student_inventory_archive($db,$studentId,(int)($_POST['item_id']??0));else throw new RuntimeException('Ação inválida.');header('Location: /aluno/inventario.php?saved='.($action==='move'?'move':'1').($action==='move'?'#inventory-item-'.(int)($_POST['item_id']??0):''),true,303);exit;}catch(Throwable $e){$error=$e->getMessage();}
}
$failedCreate=$error!==''&&$submittedAction==='create';$createDraft=$failedCreate?$_POST:[];$failedMove=$error!==''&&$submittedAction==='move';
$items=student_inventory_items($db,$studentId);if($failedMove&&!in_array((int)($_POST['item_id']??0),array_map(static fn(array $row):int=>(int)$row['id'],$items),true))$failedMove=false;$movements=student_inventory_movements($db,$studentId,0,40);$lowCount=0;$solutionCount=0;foreach($items as $item){if((string)$item['item_kind']==='solution')$solutionCount++;if($item['minimum_quantity']!==null&&(float)$item['quantity']<=(float)$item['minimum_quantity'])$lowCount++;}student_shell_start('Inventário do laboratório',null,$student);require __DIR__.'/../app/student_inventory_view.php';student_shell_end();
