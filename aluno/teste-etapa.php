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
student_shell_start('Editar etapa · '.(string)$record['title'],null,$student);?>
<header class="student-record-header student-step-editor-header"><div class="student-record-context"><a class="student-back" href="/aluno/teste.php?id=<?=$testId?>#processamento">← Processamento</a><span class="student-status">Etapa <?=str_pad((string)$position,2,'0',STR_PAD_LEFT)?></span></div><p class="student-kicker">Caderno</p><h1 class="student-title student-title-record"><?=h($stepName)?></h1><p class="student-lead">Corrija os dados desta etapa. Nenhuma outra etapa é alterada por isso.</p></header>
<?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
<section class="student-workflow-panel student-step-editor">
<form method="post" class="student-process-edit-form student-form-grid" data-ui-validate>
<input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$testId))?>"><input type="hidden" name="registro" value="<?=$testId?>"><input type="hidden" name="etapa" value="<?=$position?>"><input type="hidden" name="action" value="save">
<label class="form-field student-span-2">Nome da etapa<input name="label" value="<?=h((string)$step['label'])?>" required<?=$locked?' disabled':''?>></label>
<label class="form-field student-span-2">Químico ou solução<input name="chemical_name" value="<?=h((string)$step['chemical_name'])?>"<?=$locked?' disabled':''?>></label>
<label class="form-field">Tempo<input name="duration" value="<?=h((string)$step['duration'])?>"<?=$locked?' disabled':''?>></label><label class="form-field">Temperatura<input name="temperature" value="<?=h((string)$step['temperature'])?>"<?=$locked?' disabled':''?>></label>
<label class="form-field student-span-2">Agitação<input name="agitation" value="<?=h((string)$step['agitation'])?>"<?=$locked?' disabled':''?>></label><label class="form-field student-span-2">Anotações<textarea name="notes" rows="4" maxlength="3000"<?=$locked?' disabled':''?>><?=h((string)$step['notes'])?></textarea></label>
<?php if(!$locked):?><div class="student-sticky-action student-span-2"><button class="button button-primary" type="submit">Salvar alterações</button><a class="button button-secondary" href="/aluno/teste.php?id=<?=$testId?>#processamento">Cancelar</a></div><?php endif;?>
</form>
<?php if(!$locked):?><details class="student-step-danger"><summary>Remover esta etapa</summary><div><p>Remove somente esta anotação. As outras etapas e as movimentações de estoque permanecem como foram registradas.</p><form method="post" onsubmit="return confirm('Remover somente esta etapa?')"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$testId))?>"><input type="hidden" name="registro" value="<?=$testId?>"><input type="hidden" name="etapa" value="<?=$position?>"><input type="hidden" name="action" value="delete"><button class="button button-danger" type="submit">Remover etapa</button></form></div></details><?php endif;?>
<div class="student-actions"><a class="button button-secondary" href="/aluno/inventario.php">Movimentar estoque</a></div>
</section>
<?php student_shell_end();