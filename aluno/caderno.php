<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_account_reconcile_confirmed_registrations($db);$student=student_account_current($db);
if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2Fcaderno.php',true,303);exit;}
$studentId=(int)$student['id'];$enrollments=student_enrollment_list($db,$studentId);$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-process-notebook',$_POST['_csrf']??null))$error='Solicitação inválida.';
    else try{
        $action=(string)($_POST['action']??'create');
        if($action==='duplicate'){
            $record=student_process_duplicate($db,(int)($_POST['id']??0),$studentId);
            $_SESSION['student_process_notice']='Registro duplicado. Altere apenas o que quiser nesta variação.';
            header('Location: /aluno/teste.php?id='.(int)$record['id'],true,303);exit;
        }
        if($action==='visibility'){
            student_test_set_visibility($db,(int)($_POST['id']??0),$studentId,(string)($_POST['visibility']??'private'));
            $_SESSION['student_process_notice']='Compartilhamento atualizado.';
            header('Location: /aluno/caderno.php',true,303);exit;
        }
        $record=student_process_create_record($db,$studentId,$_POST);
        header('Location: /aluno/teste.php?id='.(int)$record['id'],true,303);exit;
    }catch(Throwable $e){$error=$e->getMessage();}
}
$notice=(string)($_SESSION['student_process_notice']??'');unset($_SESSION['student_process_notice']);
$records=student_process_records_for_student($db,$studentId);$shared=student_tests_shared_with_student($db,$studentId);
student_shell_start('Caderno de Processos',null,$student);?>
<div class="student-page-heading"><div><p class="student-kicker">Seu histórico de trabalho</p><h1 class="student-title">Caderno de Processos</h1></div><button class="button button-primary" type="button" data-process-new-toggle aria-expanded="false">Novo registro</button></div>
<p class="student-lead">Exposição, processamento e resultado reunidos no mesmo registro.</p>
<?php if($notice!==''):?><p class="ui-alert ui-alert-notice"><?=h($notice)?></p><?php endif;?><?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
<section class="student-section student-process-new" data-process-new-panel hidden>
  <div class="student-section-heading"><div><p class="student-kicker">Novo registro</p><h2 class="student-subtitle">Comece pela fotografia</h2></div></div>
  <form method="post" class="student-form-grid" data-ui-validate><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-notebook'))?>"><input type="hidden" name="action" value="create">
    <label class="form-field student-span-2">Título<input name="title" maxlength="180" placeholder="Se deixar vazio, usamos a data"></label><label class="form-field">Data<input type="date" name="test_date" value="<?=h(date('Y-m-d'))?>"></label>
    <fieldset class="choice-field student-span-2"><legend>Contexto</legend><div class="choice-row"><label class="choice-option"><input type="radio" name="context_scope" value="personal" checked data-process-context-radio><span>Pessoal</span></label><?php if($enrollments):?><label class="choice-option"><input type="radio" name="context_scope" value="course" data-process-context-radio><span>Curso / turma</span></label><?php endif;?></div></fieldset>
    <?php if($enrollments):?><label class="form-field student-span-2" data-process-course-context hidden>Curso e turma<select name="context_cohort_id"><option value="">Escolha…</option><?php foreach($enrollments as $enrollment):?><option value="<?=(int)$enrollment['cohort_id']?>"><?=h((string)$enrollment['course_title'])?> · <?=h((string)$enrollment['cohort_title'])?></option><?php endforeach;?></select></label><?php endif;?>
    <div class="student-actions student-span-2"><button class="button button-primary" type="submit">Criar registro →</button><button class="button button-secondary" type="button" data-process-new-cancel>Cancelar</button></div>
  </form>
</section>
<section class="student-section"><div class="student-section-heading"><div><p class="student-kicker">Registros</p><h2 class="student-subtitle">Seu caderno</h2></div><p><?=count($records)?> registro(s)</p></div>
<?php if(!$records):?><div class="student-empty">Seu Caderno ainda está vazio.</div><?php else:?><div class="student-test-list student-process-list"><?php foreach($records as $record):$steps=student_process_steps($db,(int)$record['id']);$personal=(string)($record['context_scope']??'course')==='personal';$context=$personal?'Pessoal':trim((string)($record['context_course_title']??$record['public_title']).' · '.(string)($record['context_cohort_title']??$record['cohort_title']));$visibility=$personal?'private':(string)($record['visibility']??'private');?><article class="student-process-row"><label class="student-process-compare"><input type="checkbox" value="<?=(int)$record['id']?>" data-process-compare aria-label="Selecionar <?=h((string)$record['title'])?> para comparação"></label><a class="student-test-row" href="/aluno/teste.php?id=<?=(int)$record['id']?>"><div><span class="student-status"><?=h($context)?></span><?php if(!$personal):?><span class="student-status"><?=h(student_test_visibility_label($visibility))?></span><?php endif;?><h3><?=h((string)$record['title'])?></h3><?php if($steps):?><p class="student-process-path"><?=h(student_process_path_label($steps))?></p><?php else:?><p>Processamento ainda não registrado.</p><?php endif;?></div><div class="student-test-meta"><span><?=h((string)($record['test_date']?:'sem data'))?></span><span>Atualizado <?=h(student_process_date((string)$record['updated_at']))?></span></div></a><div class="student-process-row-actions"><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-notebook'))?>"><input type="hidden" name="action" value="duplicate"><input type="hidden" name="id" value="<?=(int)$record['id']?>"><button class="student-link" type="submit">Repetir processo</button></form><?php if(!$personal):?><form method="post" class="student-process-visibility"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-notebook'))?>"><input type="hidden" name="action" value="visibility"><input type="hidden" name="id" value="<?=(int)$record['id']?>"><label>Compartilhar <select name="visibility" onchange="this.form.submit()"><option value="private"<?=$visibility==='private'?' selected':''?>>Privado</option><option value="cohort"<?=$visibility==='cohort'?' selected':''?>>Turma</option><option value="course"<?=$visibility==='course'?' selected':''?>>Curso</option></select></label></form><?php endif;?></div></article><?php endforeach;?></div><div class="student-process-compare-action"><button class="button button-secondary" type="button" data-process-compare-button>Comparar selecionados</button><span>Selecione dois registros.</span></div><?php endif;?>
</section>
<section class="student-section"><div class="student-section-heading"><div><p class="student-kicker">Referências</p><h2 class="student-subtitle">Compartilhados com você</h2></div><p><?=count($shared)?> registro(s)</p></div><?php if(!$shared):?><div class="student-empty">Nenhum registro foi compartilhado com você ainda.</div><?php else:?><div class="student-test-list"><?php foreach($shared as $record):?><a class="student-test-row" href="/aluno/teste-compartilhado.php?id=<?=(int)$record['id']?>"><div><span class="student-status"><?=h(student_test_visibility_label((string)$record['visibility']))?></span><h3><?=h((string)$record['title'])?></h3><p><?=h((string)$record['student_name'])?> · <?=h((string)$record['cohort_title'])?></p></div><div class="student-test-meta"><span><?=h((string)($record['test_date']?:'sem data'))?></span><span>Atualizado <?=h(student_process_date((string)$record['updated_at']))?></span></div></a><?php endforeach;?></div><?php endif;?></section>
<?php student_shell_end();
function student_process_date(string $value): string {$ts=strtotime($value);return $ts===false?$value:date('d/m/Y',$ts);}