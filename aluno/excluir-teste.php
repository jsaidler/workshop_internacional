<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_account_reconcile_confirmed_registrations($db);$student=student_account_current($db);
$id=(int)($_GET['id']??$_POST['id']??0);
$next='/aluno/excluir-teste.php?id='.$id;
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];$test=student_test_for_student($db,$id,$studentId);
if(!$test){http_response_code(404);student_shell_start('Teste não encontrado',null,$student);?><div class="student-empty">Teste não encontrado.</div><?php student_shell_end();exit;}
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-delete-test-'.$id,$_POST['_csrf']??null))$error='Solicitação inválida.';
    else{
        try{
            student_test_delete_owned($db,$id,$studentId);
            $_SESSION['student_tests_notice']='Teste excluído definitivamente, incluindo fotografias e mensagens associadas.';
            header('Location: /aluno/testes.php',true,303);exit;
        }catch(Throwable $e){$error=$e->getMessage();}
    }
}
student_shell_start('Excluir teste',null,$student);?>
<div class="student-appbar"><a class="student-back" href="/aluno/teste.php?id=<?=$id?>">← Voltar ao teste</a></div>
<p class="student-kicker">Ação permanente</p>
<h1 class="student-title student-title-record">Excluir teste</h1>
<p class="student-lead student-lead-compact">Você está prestes a excluir <strong><?=h((string)$test['title'])?></strong>. Esta ação apaga a ficha, todas as fotografias anexadas e toda a conversa associada ao teste.</p>
<?php if($error):?><p class="student-error" role="alert"><?=h($error)?></p><?php endif;?>
<section class="student-danger-zone">
  <p>Essa exclusão não pode ser desfeita.</p>
  <form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-delete-test-'.$id))?>"><input type="hidden" name="id" value="<?=$id?>"><button class="student-button student-danger-button" type="submit">Excluir teste definitivamente</button></form>
</section>
<?php student_shell_end();
