<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);
$testId=(int)($_GET['registro']??$_POST['registro']??0);$position=(int)($_GET['etapa']??$_POST['etapa']??0);$next='/aluno/teste-etapa.php?registro='.$testId.'&etapa='.$position;
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];$record=student_test_for_student($db,$testId,$studentId);$step=$record?student_process_step_for_student($db,$testId,$position,$studentId):null;
if(!$record||!$step){http_response_code(404);student_shell_start('Etapa não encontrada',null,$student);?><div class="student-empty">Etapa não encontrada.</div><?php student_shell_end();exit;}
$locked=(string)$record['status']==='reviewed';$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-process-'.$testId,$_POST['_csrf']??null))$error='Solicitação inválida.';
    else try{
        if($locked)throw new RuntimeException('Este registro já foi revisado.');
        $action=(string)($_POST['action']??'save');
        if($action==='delete'){
            student_process_notebook_delete_free_step($db,$testId,(int)$step['id'],$studentId);$_SESSION['student_process_notice']='Etapa removida.';header('Location: /aluno/teste.php?id='.$testId.'#processamento',true,303);exit;
        }
        student_process_notebook_update_free_step($db,$testId,(int)$step['id'],$studentId,$_POST);$_SESSION['student_process_notice']='Etapa atualizada.';header('Location: /aluno/teste.php?id='.$testId.'#processamento',true,303);exit;
    }catch(Throwable $e){$error=$e->getMessage();}
}
$step=student_process_step_for_student($db,$testId,$position,$studentId)??$step;$stepName=trim((string)$step['chemical_name'])!==''?(string)$step['chemical_name']:(string)$step['label'];
require_once __DIR__.'/../app/student_record_auxiliary_views.php';
student_shell_start('Editar etapa · '.(string)$record['title'],null,$student);
student_record_render_free_step_editor($step,$testId,$position,$locked,$error,$stepName,$error!==''&&!$locked?$_POST:[]);
student_shell_end();
