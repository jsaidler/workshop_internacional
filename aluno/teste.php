<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/student_record_media_controls.php';
require_once __DIR__.'/../app/student_record_workspace_navigation.php';
security_headers();student_private_headers();

$db=database();student_account_reconcile_confirmed_registrations($db);$student=student_account_current($db);
$id=(int)($_GET['id']??$_POST['id']??0);$next='/aluno/teste.php?id='.$id;
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];$record=student_test_for_student($db,$id,$studentId);
if(!$record){http_response_code(404);student_shell_start('Registro não encontrado',null,$student);?><div class="student-empty">Registro não encontrado.</div><?php student_shell_end();exit;}

// Links antigos continuam apontando apenas para uma parte da mesma página.
if(($_SERVER['REQUEST_METHOD']??'GET')==='GET'&&isset($_GET['view'])){
    $anchor=match((string)$_GET['view']){'process'=>'processamento','review'=>'resultado',default=>'exposicao'};
    header('Location: /aluno/teste.php?id='.$id.'#'.$anchor,true,303);exit;
}

$error='';$contextScope=(string)($record['context_scope']??'course');$recordDefaultPart='exposicao';if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){$postedAction=(string)($_POST['action']??'');if(in_array($postedAction,['save_result','submit','message'],true)||($postedAction==='upload'&&(string)($_POST['phase']??'result')==='result'))$recordDefaultPart='resultado';elseif(in_array($postedAction,['toggle_plan_step','save_plan_step','move_plan_step','add_plan_step','add_free_step','delete_free_step'],true))$recordDefaultPart='processamento';}
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-process-'.$id,$_POST['_csrf']??null))$error='Solicitação inválida.';
    else try{
        $action=(string)($_POST['action']??'');$anchor='';
        if($action==='save_exposure'){
            student_test_update_exposure($db,$id,$studentId,$_POST);$anchor='exposicao';$_SESSION['student_process_notice']='Exposição salva.';
        }elseif($action==='toggle_plan_step'){
            $plan=student_process_plan_for_test($db,$id,$studentId)??throw new RuntimeException('Associe um roteiro a este registro.');
            student_process_notebook_set_completed($db,(int)$plan['id'],(int)($_POST['plan_step_id']??0),$studentId,(string)($_POST['completed']??'0')==='1','manual');
            $anchor='processamento';$_SESSION['student_process_notice']='Marcação da etapa atualizada.';
        }elseif($action==='save_plan_step'){
            $plan=student_process_plan_for_test($db,$id,$studentId)??throw new RuntimeException('Associe um roteiro a este registro.');
            student_process_notebook_update_step($db,(int)$plan['id'],(int)($_POST['plan_step_id']??0),$studentId,$_POST);
            $anchor='processamento';$_SESSION['student_process_notice']='Etapa do roteiro atualizada neste registro.';
        }elseif($action==='move_plan_step'){
            $plan=student_process_plan_for_test($db,$id,$studentId)??throw new RuntimeException('Associe um roteiro a este registro.');
            student_process_notebook_move_step($db,(int)$plan['id'],(int)($_POST['plan_step_id']??0),$studentId,(int)($_POST['direction']??0));
            $anchor='processamento';$_SESSION['student_process_notice']='Ordem do roteiro atualizada neste registro.';
        }elseif($action==='add_plan_step'){
            $plan=student_process_plan_for_test($db,$id,$studentId)??throw new RuntimeException('Associe um roteiro a este registro.');
            student_process_notebook_add_step($db,(int)$plan['id'],$studentId,$_POST);
            $anchor='processamento';$_SESSION['student_process_notice']='Etapa adicionada ao roteiro deste registro.';
        }elseif($action==='add_free_step'){
            student_process_notebook_add_free_step($db,$id,$studentId,$_POST);$anchor='processamento';$_SESSION['student_process_notice']='Etapa adicionada ao registro.';
        }elseif($action==='delete_free_step'){
            student_process_notebook_delete_free_step($db,$id,(int)($_POST['step_id']??0),$studentId);$anchor='processamento';$_SESSION['student_process_notice']='Etapa removida.';
        }elseif($action==='save_result'){
            $notes=student_workspace_text($_POST['notes']??'',4000);$db->prepare('UPDATE student_tests SET notes=?,updated_at=? WHERE id=? AND student_id=?')->execute([$notes,utc_now(),$id,$studentId]);$anchor='resultado';$_SESSION['student_process_notice']='Resultado salvo.';
        }elseif($action==='upload'){
            $phase=(string)($_POST['phase']??'result');
            if($phase==='scene')student_test_update_exposure($db,$id,$studentId,$_POST);
            else{$notes=student_workspace_text($_POST['notes']??'',4000);$db->prepare('UPDATE student_tests SET notes=?,updated_at=? WHERE id=? AND student_id=?')->execute([$notes,utc_now(),$id,$studentId]);}
            $batch=$_FILES['images']??null;$single=$_FILES['image']??null;if(is_array($batch)&&is_array($batch['name']??null)&&count(array_filter($batch['name'],static fn($name):bool=>(string)$name!==''))>0){$added=student_test_add_media_batch_phase($db,$id,$studentId,$batch,$phase);}elseif(is_array($single)){$added=1;student_test_add_media_phase($db,$id,$studentId,$single,$phase);}else throw new RuntimeException('Selecione uma ou mais imagens.');$anchor=$phase==='scene'?'exposicao':'resultado';$_SESSION['student_process_notice']=$added===1?'Imagem adicionada.':$added.' imagens adicionadas.';
        }elseif($action==='delete_media'){
            student_test_delete_media($db,(int)($_POST['media_id']??0),$studentId);$anchor=(string)($_POST['phase']??'result')==='scene'?'exposicao':'resultado';$_SESSION['student_process_notice']='Imagem removida.';
        }elseif($action==='submit'){
            if($contextScope!=='course')throw new RuntimeException('Registros pessoais não são enviados para avaliação.');student_test_submit($db,$id,$studentId);$anchor='resultado';$_SESSION['student_process_notice']='Registro enviado para avaliação.';
        }elseif($action==='message'){
            student_test_add_student_message($db,$id,$studentId,(string)($_POST['message']??''));$anchor='resultado';$_SESSION['student_process_notice']='Mensagem enviada.';
        }else throw new RuntimeException('Ação inválida.');
        header('Location: /aluno/teste.php?id='.$id.($anchor!==''?'#'.$anchor:''),true,303);exit;
    }catch(Throwable $e){$error=$e->getMessage();}
}

$record=student_test_for_student($db,$id,$studentId)??$record;
if($contextScope==='course'){student_feedback_mark_seen($db,$id,$studentId);$record=student_test_for_student($db,$id,$studentId)??$record;}
$feedbackState=student_feedback_state($record);$steps=student_process_steps($db,$id);$media=student_test_media($db,$id);$sceneMedia=student_test_media_by_phase($media,'scene');$resultMedia=student_test_media_by_phase($media,'result');$messages=student_test_messages($db,$id);
$notice=(string)($_SESSION['student_process_notice']??'');unset($_SESSION['student_process_notice']);$locked=(string)$record['status']==='reviewed';
$plan=student_process_plan_for_test($db,$id,$studentId);$planSteps=$plan?student_process_plan_steps($db,(int)$plan['id']):[];$planCompleted=0;foreach($planSteps as $planStep)if((string)$planStep['status']==='completed')$planCompleted++;
$editPlanStepId=(int)($_GET['edit_plan_step']??0);$editPlanStep=null;if($plan&&$editPlanStepId>0){foreach($planSteps as $candidate)if((int)$candidate['id']===$editPlanStepId){$editPlanStep=$candidate;break;}}
$stageCatalog=student_process_stage_catalog();$firstDevelopment=null;$bleachNames=[];
foreach($steps as $step){$stageKey=(string)$step['stage_key'];if($firstDevelopment===null&&$stageKey==='first_development')$firstDevelopment=$step;if(in_array($stageKey,['ferric','peracetic','dichromate','permanganate'],true)&&trim((string)$step['chemical_name'])!=='')$bleachNames[]=(string)$step['chemical_name'];}
$bleachNames=array_values(array_unique($bleachNames));

require_once __DIR__.'/../app/student_record_workspace_view.php';
student_shell_start((string)$record['title'].' · Caderno',null,$student);
student_record_render_workspace(compact('record','id','contextScope','recordDefaultPart','notice','error','locked','sceneMedia','media','plan','planCompleted','planSteps','editPlanStep','stageCatalog','steps','firstDevelopment','bleachNames','resultMedia','feedbackState','messages'));
student_shell_end();
