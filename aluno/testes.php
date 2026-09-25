<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_account_reconcile_confirmed_registrations($db);$student=student_account_current($db);
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode('/aluno/testes.php'),true,303);exit;}
$studentId=(int)$student['id'];$enrollments=student_account_enrollments($db,$studentId);$showNew=!empty($_GET['new']);$preferredCohort=(int)($_GET['cohort']??0);
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-tests',$_POST['_csrf']??null))$error='Solicitação inválida.';
    else{
        try{
            $test=student_test_create($db,$studentId,(int)($_POST['cohort_id']??0),$_POST);
            header('Location: '.student_test_stage_url((int)$test['id'],'exposure'),true,303);exit;
        }catch(Throwable $e){$error=$e->getMessage();$showNew=true;}
    }
}
$tests=student_tests_for_student($db,$studentId);
student_shell_start('Testes',null,$student);?>
<div class="student-title-row"><div><p class="student-kicker">Caderno de pesquisa</p><h1 class="student-title">Testes</h1></div><?php if(!$showNew):?><a class="student-button" href="/aluno/testes.php?new=1">Novo teste</a><?php endif;?></div>
<p class="student-lead">Use o registro no momento da experimentação: primeiro cena e exposição, depois revelação, então fotografe o resultado e envie para avaliação.</p>
<?php if($error):?><p class="student-error" role="alert"><?=h($error)?></p><?php endif;?>

<?php if($showNew):?><section class="student-section">
  <div class="student-section-heading"><div><p class="student-kicker">Novo registro</p><h2 class="student-subtitle">Começar teste</h2></div><p>Crie o registro agora. A próxima tela abre diretamente na etapa de exposição para ser usada junto à câmera.</p></div>
  <?php if(!$enrollments):?><div class="student-empty">Você precisa de uma matrícula ativa para registrar testes.</div><?php else:?><form class="student-form-grid" method="post">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-tests'))?>">
    <label class="student-field">Turma<select name="cohort_id" required><?php foreach($enrollments as $enrollment):?><option value="<?=(int)$enrollment['cohort_id']?>"<?=$preferredCohort===(int)$enrollment['cohort_id']?' selected':''?>><?=h((string)$enrollment['public_title'])?> · <?=h((string)$enrollment['cohort_title'])?></option><?php endforeach;?></select></label>
    <label class="student-field">Título do teste<input name="title" required maxlength="180" placeholder="Ex.: Retrato na janela · EI 200"></label>
    <label class="student-field">Data<input type="date" name="test_date" value="<?=h(date('Y-m-d'))?>"></label>
    <div class="student-actions"><button class="student-button" type="submit">Criar e registrar exposição</button><a class="student-button student-button-secondary" href="/aluno/testes.php">Cancelar</a></div>
  </form><?php endif;?>
</section><?php endif;?>

<section class="student-section">
  <div class="student-section-heading"><div><p class="student-kicker">Histórico</p><h2 class="student-subtitle">Registros</h2></div><p><?=count($tests)?> teste(s).</p></div>
  <?php if(!$tests):?><div class="student-empty">Você ainda não registrou nenhum teste.</div><?php else:?><div class="student-test-list"><?php foreach($tests as $test):?><a class="student-test-row" href="<?=h(student_test_stage_url((int)$test['id'],'exposure'))?>"><div><span class="student-status student-status-<?=h((string)$test['status'])?>"><?=h(student_test_status_label((string)$test['status']))?></span><h3><?=h((string)$test['title'])?></h3><p><?=h((string)$test['public_title'])?> · <?=h((string)$test['cohort_title'])?></p></div><div class="student-test-meta"><span><?=h((string)($test['test_date']?:'sem data'))?></span><span>Atualizado <?=h(student_test_list_date((string)$test['updated_at']))?></span></div></a><?php endforeach;?></div><?php endif;?>
</section>
<?php student_shell_end();

function student_test_list_date(string $value): string {$ts=strtotime($value);return $ts===false?$value:date('d/m/Y',$ts);}
