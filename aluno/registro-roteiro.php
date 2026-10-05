<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();
student_private_headers();

$db=database();
$student=student_account_current($db);
$testId=(int)($_GET['test']??$_POST['test_id']??0);
$next='/aluno/registro-roteiro.php?test='.$testId;
if(!$student){
    header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);
    exit;
}
$studentId=(int)$student['id'];
$record=student_test_for_student($db,$testId,$studentId);
if(!$record){
    http_response_code(404);
    student_shell_start('Registro não encontrado',null,$student);
    ?><div class="student-empty">Registro não encontrado.</div><?php
    student_shell_end();
    exit;
}
if((string)$record['status']==='reviewed'){
    header('Location: /aluno/teste.php?id='.$testId.'#processamento',true,303);
    exit;
}

$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-record-route-'.$testId,$_POST['_csrf']??null)){
        $error='Solicitação inválida.';
    }else{
        try{
            $kind=(string)($_POST['source_kind']??'');
            if($kind==='template'){
                $plan=student_process_replan_template($db,(int)($_POST['template_id']??0),$testId,$studentId);
            }elseif($kind==='standard'){
                $plan=student_process_replan_standard($db,(string)($_POST['standard_key']??''),$testId,$studentId);
            }else throw new RuntimeException('Escolha um roteiro.');
            $_SESSION['student_process_notice']=student_process_replanning_notice($plan);
            header('Location: /aluno/teste.php?id='.$testId.'#processamento',true,303);
            exit;
        }catch(Throwable $e){
            $error=$e->getMessage();
        }
    }
}

$current=student_process_plan_for_test($db,$testId,$studentId);
$facts=student_process_steps($db,$testId);
$templates=student_process_templates($db,$studentId);
$standards=student_process_standard_catalog();
$csrf=csrf_token('student-record-route-'.$testId);

student_shell_start('Roteiro · '.(string)$record['title'],null,$student);
?>
<header class="student-page-heading student-process-library-heading">
  <div>
    <a class="student-back" href="/aluno/teste.php?id=<?=$testId?>#processamento">← Voltar ao registro</a>
    <p class="student-kicker">Processamento · <?=h((string)$record['title'])?></p>
    <h1 class="student-title"><?=$current?'Alterar roteiro':'Associar roteiro'?></h1>
    <p class="student-process-context">Escolha a sequência que deve orientar as próximas etapas. O que já foi registrado no Caderno permanece no histórico.</p>
  </div>
</header>

<?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
<?php if($current):?>
<section class="student-section">
  <div class="student-section-heading"><div><p class="student-kicker">Roteiro atual</p><h2 class="student-subtitle"><?=h((string)$current['source_name'])?></h2></div><p><?=h(student_experience_count(count($facts),'etapa já registrada','etapas já registradas'))?>. Essas etapas não serão apagadas ao trocar o roteiro.</p></div>
</section>
<?php endif;?>

<section class="student-process-section" aria-labelledby="record-routes-own">
  <div class="student-process-section-heading">
    <div><p class="student-kicker">Sua biblioteca</p><h2 class="student-subtitle" id="record-routes-own">Meus processamentos</h2></div>
    <p>Roteiros que você salvou e pode reutilizar.</p>
  </div>
  <?php if(!$templates):?>
    <div class="student-process-empty"><strong>Nenhum roteiro pessoal salvo.</strong><p>Você pode usar um padrão do workshop abaixo ou criar e editar roteiros na biblioteca de Processamentos.</p><a class="button button-secondary button-compact" href="/aluno/processamentos.php">Abrir biblioteca</a></div>
  <?php else:?>
    <div class="student-process-library">
      <?php foreach($templates as $template):$stepCount=(int)$template['step_count'];?>
        <article class="student-process-template-card">
          <div class="student-process-template-main">
            <span class="student-process-step-number"><?=str_pad((string)$stepCount,2,'0',STR_PAD_LEFT)?></span>
            <div><h3><?=h((string)$template['name'])?></h3><p><?=h((string)($template['description']!==''?$template['description']:'Roteiro pessoal.'))?></p><div class="student-process-summary"><span><?=h(student_experience_count($stepCount,'etapa','etapas'))?></span><span><?=h(student_process_template_duration_summary($db,(int)$template['id']))?></span></div></div>
          </div>
          <div class="student-process-template-actions">
            <form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="source_kind" value="template"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><button class="button button-primary button-compact" type="submit"<?=$stepCount>0?'':' disabled'?>><?=$current?'Usar daqui em diante':'Associar ao registro'?></button></form>
          </div>
        </article>
      <?php endforeach;?>
    </div>
  <?php endif;?>
</section>

<section class="student-process-section student-process-standards" aria-labelledby="record-routes-standards">
  <div class="student-process-section-heading">
    <div><p class="student-kicker">Padrões do workshop</p><h2 class="student-subtitle" id="record-routes-standards">Roteiros conhecidos</h2></div>
    <p>Use diretamente um dos processos trabalhados no workshop.</p>
  </div>
  <div class="student-process-standard-grid">
    <?php foreach($standards as $standardKey=>$standard):$ei=str_contains((string)$standardKey,'ei400')?'EI 400':'EI 200';$route=str_contains((string)$standardKey,'ferric-ammonia')?'FeCl₃ + amônia':'Peracética';?>
      <article class="student-process-standard-card">
        <div class="student-process-standard-meta"><span><?=h($ei)?></span><span>Parodinal</span><span><?=h($route)?></span></div>
        <h3><?=h((string)$standard['name'])?></h3>
        <p><?=h((string)$standard['description'])?></p>
        <div class="student-process-standard-footer"><span><?=count((array)$standard['steps'])?> etapas · <?=h(student_process_standard_duration_summary((array)$standard['steps']))?></span><form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="source_kind" value="standard"><input type="hidden" name="standard_key" value="<?=h((string)$standardKey)?>"><button class="button button-primary button-compact" type="submit"><?=$current?'Usar daqui em diante':'Associar ao registro'?></button></form></div>
      </article>
    <?php endforeach;?>
  </div>
</section>

<div class="student-actions"><a class="button button-secondary" href="/aluno/teste.php?id=<?=$testId?>#processamento">Cancelar e voltar ao registro</a></div>
<?php student_shell_end();
