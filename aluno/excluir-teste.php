<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_account_reconcile_confirmed_registrations($db);$student=student_account_current($db);
$id=(int)($_GET['id']??$_POST['id']??0);$cohortValue=$_GET['cohort']??$_POST['cohort']??null;$requestedCohortUuid=is_string($cohortValue)?trim($cohortValue):'';$nextParams=['id'=>$id];if($requestedCohortUuid!=='')$nextParams['cohort']=$requestedCohortUuid;$next='/aluno/excluir-teste.php?'.http_build_query($nextParams);
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];$test=student_test_for_student($db,$id,$studentId);
if(!$test){http_response_code(404);student_shell_start('Teste não encontrado',null,$student);?><div class="student-empty">Teste não encontrado.</div><?php student_shell_end();exit;}
$enrollments=student_enrollment_list($db,$studentId);$testContext=student_enrollment_owned_test_navigation_context($enrollments,$test,$requestedCohortUuid);$cohortUuid=$testContext?(string)$testContext['cohort_uuid']:'';$testsUrl='/aluno/testes.php'.($cohortUuid!==''?'?cohort='.rawurlencode($cohortUuid):'');$testUrl='/aluno/teste.php?'.http_build_query(array_filter(['id'=>$id,'cohort'=>$cohortUuid],static fn($value): bool=>$value!==''));$activity=activity_by_id($db,(int)$test['activity_id']);
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-delete-test-'.$id,$_POST['_csrf']??null))$error='Solicitação inválida.';
    else{
        try{
            student_test_delete_owned($db,$id,$studentId);
            $_SESSION['student_tests_notice']='Teste excluído definitivamente, incluindo fotografias e mensagens associadas.';
            header('Location: '.$testsUrl,true,303);exit;
        }catch(Throwable $e){$error=$e->getMessage();}
    }
}
student_shell_start('Excluir teste',$activity?:null,$student);?>
<?php if($testContext)student_course_context_header($testContext,'tests',count($enrollments)>1);?>
<div class="student-page-heading"><div class="student-page-heading-main"><a class="student-page-back" href="<?=h($testUrl)?>">← Voltar ao teste</a><p class="student-kicker">Ação permanente</p><h1 class="student-title student-title-record">Excluir teste</h1><p class="student-lead student-lead-compact">Você está prestes a excluir <strong><?=h((string)$test['title'])?></strong>. Esta ação apaga a ficha, todas as fotografias anexadas e toda a conversa associada ao teste.</p></div></div>
<?php if($error):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
<section class="student-danger-zone">
  <p>Essa exclusão não pode ser desfeita.</p>
  <form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-delete-test-'.$id))?>"><input type="hidden" name="id" value="<?=$id?>"><?php if($cohortUuid!==''):?><input type="hidden" name="cohort" value="<?=h($cohortUuid)?>"><?php endif;?><button class="button button-danger" type="submit">Excluir teste definitivamente</button></form>
</section>
<?php student_shell_end();
