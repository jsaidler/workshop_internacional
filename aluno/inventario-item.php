<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';require_once __DIR__.'/../app/student_inventory_item_editor.php';security_headers();student_private_headers();$db=database();$student=student_account_current($db);$id=(int)($_GET['id']??$_POST['id']??0);$next='/aluno/inventario-item.php?id='.$id;if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}$studentId=(int)$student['id'];try{student_tool_require($db,$studentId,'lab_inventory');}catch(Throwable){http_response_code(403);exit('Ferramenta indisponível.');}$item=student_inventory_item($db,$studentId,$id);if(!$item){http_response_code(404);student_shell_start('Item não encontrado',null,$student);?><div class="student-empty">Item não encontrado.</div><?php student_shell_end();exit;}$error='';if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){if(!verify_csrf('student-inventory-item-'.$id,$_POST['_csrf']??null))$error='Solicitação inválida.';else try{student_inventory_update_metadata($db,$studentId,$id,$_POST);header('Location: /aluno/inventario.php?saved=1',true,303);exit;}catch(Throwable $e){$error=$e->getMessage();}}$item=student_inventory_item($db,$studentId,$id)??$item;
if($error!==''&&($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 foreach(['name','category','lot_code','acquired_or_prepared_at','expires_at','minimum_quantity','location','notes'] as $key){
  if(isset($_POST[$key])&&is_scalar($_POST[$key]))$item[$key]=substr((string)$_POST[$key],0,2000);
 }
}
student_shell_start('Editar item · Inventário',null,$student);?>
<div class="student-appbar"><a class="student-back" href="/aluno/inventario.php">← Inventário</a></div>
<?php student_inventory_item_editor($item,$id,$error,csrf_token('student-inventory-item-'.$id));student_shell_end();
