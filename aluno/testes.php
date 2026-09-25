<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_account_reconcile_confirmed_registrations($db);$student=student_account_current($db);
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode('/aluno/testes.php'),true,303);exit;}
$studentId=(int)$student['id'];$enrollments=student_account_enrollments($db,$studentId);
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-tests',$_POST['_csrf']??null))$error='Solicitação inválida.';
    else{
        try{
            $test=student_test_create($db,$studentId,(int)($_POST['cohort_id']??0),$_POST);
            header('Location: /aluno/teste.php?id='.(int)$test['id'],true,303);exit;
        }catch(Throwable $e){$error=$e->getMessage();}
    }
}
$tests=student_tests_for_student($db,$studentId);
student_shell_start('Meus testes',null,$student);?>
<p class="student-kicker">Área do aluno</p>
<h1 class="student-title">Meus testes</h1>
<p class="student-lead">Registre cada experimentação com os dados de exposição e revelação. Você pode anexar imagens, enviar o teste para avaliação e continuar as dúvidas no mesmo histórico.</p>

<?php if($error):?><p class="student-error" role="alert"><?=h($error)?></p><?php endif;?>
<section class="student-section">
  <div class="student-section-heading"><div><p class="student-kicker">Novo registro</p><h2 class="student-subtitle">Criar teste</h2></div><p>Comece com um título e complete os dados na ficha do teste.</p></div>
  <?php if(!$enrollments):?><div class="student-empty">Você precisa de uma matrícula ativa para registrar testes.</div><?php else:?><form class="student-form-grid" method="post">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-tests'))?>">
    <label class="student-field">Turma<select name="cohort_id" required><?php foreach($enrollments as $enrollment):?><option value="<?=(int)$enrollment['cohort_id']?>"><?=h((string)$enrollment['public_title'])?> · <?=h((string)$enrollment['cohort_title'])?></option><?php endforeach;?></select></label>
    <label class="student-field">Título do teste<input name="title" required maxlength="180" placeholder="Ex.: Janela, EI 200, Parodinal 1+50"></label>
    <label class="student-field">Data<input type="date" name="test_date"></label>
    <div class="student-actions"><button class="student-button" type="submit">Criar ficha</button></div>
  </form><?php endif;?>
</section>

<section class="student-section">
  <div class="student-section-heading"><div><p class="student-kicker">Histórico</p><h2 class="student-subtitle">Testes registrados</h2></div><p><?=count($tests)?> registro(s).</p></div>
  <?php if(!$tests):?><div class="student-empty">Você ainda não registrou nenhum teste.</div><?php else:?><div class="student-test-list"><?php foreach($tests as $test):?><a class="student-test-row" href="/aluno/teste.php?id=<?=(int)$test['id']?>"><div><span class="student-status student-status-<?=h((string)$test['status'])?>"><?=h(student_test_status_label((string)$test['status']))?></span><h3><?=h((string)$test['title'])?></h3><p><?=h((string)$test['public_title'])?> · <?=h((string)$test['cohort_title'])?></p></div><div class="student-test-meta"><span><?=h((string)($test['test_date']?:'sem data'))?></span><span>Atualizado <?=h(student_ops_datetime_exists((string)$test['updated_at']))?></span></div></a><?php endforeach;?></div><?php endif;?>
</section>
<?php student_shell_end();

function student_ops_datetime_exists(string $value): string {$ts=strtotime($value);return $ts===false?$value:date('d/m/Y',$ts);}
