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
    if(!verify_csrf('student-test-delete-'.$id,$_POST['_csrf']??null))$error='Solicitação inválida.';
    elseif((string)($_POST['confirmation']??'')!=='EXCLUIR')$error='Digite EXCLUIR para confirmar.';
    else{
        try{
            student_test_delete($db,$id,$studentId);
            $_SESSION['student_tests_notice']='Teste excluído com todas as imagens e mensagens associadas.';
            header('Location: /aluno/testes.php',true,303);exit;
        }catch(Throwable $e){$error=$e->getMessage();}
    }
}
student_shell_start('Excluir teste',null,$student);?>
<div class="student-appbar"><a class="student-back" href="/aluno/teste.php?id=<?=$id?>">← Voltar ao teste</a></div>
<p class="student-kicker"><?=h((string)$test['public_title'])?></p>
<h1 class="student-title student-title-record">Excluir teste</h1>
<p class="student-lead">Você está prestes a excluir “<?=h((string)$test['title'])?>”. O teste, as fotografias da cena e do resultado e toda a conversa vinculada serão removidos permanentemente.</p>
<?php if($error!==''):?><p class="student-error" role="alert"><?=h($error)?></p><?php endif;?>
<section class="student-workflow-panel">
  <header class="student-workflow-heading"><div><p class="student-kicker">Confirmação</p><h2 class="student-subtitle">Excluir definitivamente</h2></div><p>Essa operação não pode ser desfeita. Digite <strong>EXCLUIR</strong> para confirmar.</p></header>
  <form method="post" class="student-mobile-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-test-delete-'.$id))?>"><input type="hidden" name="id" value="<?=$id?>"><label class="student-field">Confirmação<input name="confirmation" autocomplete="off" required></label><div class="student-actions"><button class="student-button" type="submit">Excluir teste e arquivos</button><a class="student-button student-button-secondary" href="/aluno/teste.php?id=<?=$id?>">Cancelar</a></div></form>
</section>
<?php student_shell_end();
