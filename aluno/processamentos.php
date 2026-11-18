<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();

function student_process_template_href(int $id,int $testId=0,int $stepId=0): string {
    $url='/aluno/processamentos.php?id='.$id;
    if($testId>0)$url.='&test='.$testId;
    if($stepId>0)$url.='&step='.$stepId;
    return $url;
}

require_once __DIR__.'/../app/student_process_template_step_editor.php';
require_once __DIR__.'/../app/student_process_template_editor_view.php';
require_once __DIR__.'/../app/student_process_saved_card.php';

$db=database();$student=student_account_current($db);$next='/aluno/processamentos.php';
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];student_tool_require($db,$studentId,'lab_timer');
$testId=(int)($_GET['test']??$_POST['test_id']??0);$test=$testId>0?student_test_for_student($db,$testId,$studentId):null;
if($testId>0&&!$test){http_response_code(404);student_shell_start('Registro não encontrado',null,$student);?><div class="student-empty">Registro não encontrado.</div><?php student_shell_end();exit;}
$id=(int)($_GET['id']??$_POST['template_id']??0);$editStepId=(int)($_GET['step']??0);$error='';
$notice=(string)($_SESSION['student_process_template_notice']??'');unset($_SESSION['student_process_template_notice']);

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-process-templates',$_POST['_csrf']??null))$error='Solicitação inválida.';
    else try{
        $action=(string)($_POST['action']??'');
        if($action==='create'){
            $template=student_process_template_create($db,$studentId,$_POST);$id=(int)$template['id'];
            $_SESSION['student_process_template_notice']='Processamento criado. Agora monte a sequência de etapas.';
        }elseif($action==='copy_standard'){
            $template=student_process_standard_copy($db,$studentId,(string)($_POST['standard_key']??''));$id=(int)$template['id'];
            if($testId>0){
                student_process_plan_apply_template($db,$id,$testId,$studentId);
                $_SESSION['student_process_notice']='Roteiro associado ao registro.';
                header('Location: /aluno/teste.php?id='.$testId.'#processamento',true,303);exit;
            }
            $_SESSION['student_process_template_notice']='Padrão adicionado aos seus processamentos. Você pode editar a cópia sem alterar o padrão do workshop.';
        }elseif($action==='save'){
            $id=(int)($_POST['template_id']??0);student_process_template_update($db,$id,$studentId,$_POST);$_SESSION['student_process_template_notice']='Nome e descrição salvos.';
        }elseif($action==='duplicate'){
            $copy=student_process_template_duplicate($db,(int)($_POST['template_id']??0),$studentId);$id=(int)$copy['id'];$_SESSION['student_process_template_notice']='Cópia criada. Edite o nome ou as etapas como quiser.';
        }elseif($action==='add_step'){
            $id=(int)($_POST['template_id']??0);student_process_template_add_step($db,$id,$studentId,$_POST);$_SESSION['student_process_template_notice']='Etapa adicionada ao roteiro.';
        }elseif($action==='update_step'){
            $id=(int)($_POST['template_id']??0);student_process_template_update_step($db,$id,(int)($_POST['step_id']??0),$studentId,$_POST);$_SESSION['student_process_template_notice']='Etapa atualizada.';
        }elseif($action==='delete_step'){
            $id=(int)($_POST['template_id']??0);student_process_template_delete_step($db,$id,(int)($_POST['step_id']??0),$studentId);$_SESSION['student_process_template_notice']='Etapa removida do roteiro.';
        }elseif($action==='move_step'){
            $id=(int)($_POST['template_id']??0);student_process_template_move_step($db,$id,(int)($_POST['step_id']??0),$studentId,(int)($_POST['direction']??1));$_SESSION['student_process_template_notice']='Ordem das etapas atualizada.';
        }elseif($action==='delete'){
            $deleteId=(int)($_POST['template_id']??0);student_process_template_delete($db,$deleteId,$studentId);$id=0;$_SESSION['student_process_template_notice']='Processamento excluído.';
        }elseif($action==='apply_template'){
            $id=(int)($_POST['template_id']??0);if($testId<1)throw new RuntimeException('Escolha um registro do Caderno.');
            student_process_plan_apply_template($db,$id,$testId,$studentId);
            $_SESSION['student_process_notice']='Roteiro associado ao registro.';
            header('Location: /aluno/teste.php?id='.$testId.'#processamento',true,303);exit;
        }else throw new RuntimeException('Ação inválida.');
        $url=$id>0?student_process_template_href($id,$testId):'/aluno/processamentos.php'.($testId>0?'?test='.$testId:'');header('Location: '.$url,true,303);exit;
    }catch(Throwable $e){$error=$e->getMessage();}
}

$templates=student_process_templates($db,$studentId);$template=$id>0?student_process_template_for_student($db,$id,$studentId):null;
if($id>0&&!$template){$id=0;$error=$error?:'Processamento não encontrado.';}
$steps=$template?student_process_template_steps($db,(int)$template['id']):[];
$editStep=$template&&$editStepId>0?student_process_template_step_for_student($db,(int)$template['id'],$editStepId,$studentId):null;
if($editStepId>0&&!$editStep)$error=$error?:'Etapa não encontrada.';
$catalog=student_process_managed_stage_catalog($db,false);$developers=student_process_managed_developer_catalog($db,false);$preparations=student_saved_preparations($db,$studentId);$standards=student_process_standard_catalog();$csrf=csrf_token('student-process-templates');
student_shell_start('Processamentos',null,$student);
?>
<header class="student-page-heading student-process-library-heading">
  <div>
    <p class="student-kicker"><?=$test?'Caderno · Processamento':'Laboratório'?></p>
    <h1 class="student-title"><?=$test?'Escolha um roteiro':'Processamentos'?></h1>
    <?php if($test):?>
      <p class="student-process-context">Escolha uma referência para <strong><?=h((string)$test['title'])?></strong>. Associar um roteiro não inicia nem controla o processamento.</p>
    <?php elseif(!$template):?>
      <p class="student-process-context">Salve e reutilize as sequências de processamento que você usa no laboratório.</p>
    <?php else:?>
      <p class="student-process-context">Edite este roteiro sem alterar cópias já registradas no Caderno.</p>
    <?php endif;?>
  </div>
  <?php if($template):?><a class="button button-secondary" href="/aluno/processamentos.php<?=$testId>0?'?test='.$testId:''?>">← Meus processamentos</a><?php endif;?>
</header>
<?php if($notice!==''):?><p class="ui-alert ui-alert-notice"><?=h($notice)?></p><?php endif;?>
<?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>

<?php if(!$template):?>
<section class="student-process-section" aria-labelledby="student-process-owned-title">
  <div class="student-process-section-heading">
    <div><p class="student-kicker">Sua biblioteca</p><h2 class="student-subtitle" id="student-process-owned-title">Meus processamentos</h2></div>
    <?php if(!$test):?><p>Use um roteiro salvo como está ou abra para ajustar etapas, tempos e observações.</p><?php endif;?>
  </div>
  <?php if(!$templates):?>
    <div class="student-process-empty">
      <strong>Você ainda não tem um processamento salvo.</strong>
      <p>Escolha abaixo um dos padrões do workshop para começar.</p>
      <a class="button button-primary button-compact" href="#padroes-workshop">Ver padrões do workshop</a>
    </div>
  <?php else:?><div class="student-process-library" aria-label="Processamentos salvos">
    <?php foreach($templates as $item):$href=student_process_template_href((int)$item['id'],$testId);$hasSteps=(int)$item['step_count']>0;?>
      <?php student_process_saved_card($item,$href,student_process_template_duration_summary($db,(int)$item['id']),$csrf,$testId);?>
    <?php endforeach;?>
  </div><?php endif;?>
</section>

<section class="student-process-section student-process-standards" id="padroes-workshop" aria-labelledby="student-process-standards-title">
  <div class="student-process-section-heading">
    <div><p class="student-kicker">Padrões do workshop</p><h2 class="student-subtitle" id="student-process-standards-title">Roteiros conhecidos</h2></div>
    <?php if(!$test):?><p>Escolha o EI e a rota de branqueamento. Nos quatro padrões, a segunda revelação reutiliza o mesmo banho de Parodinal da primeira.</p><?php endif;?>
  </div>
  <div class="student-process-standard-grid">
    <?php foreach($standards as $standardKey=>$standard):$ei=str_contains((string)$standardKey,'ei400')?'EI 400':'EI 200';$route=str_contains((string)$standardKey,'ferric-ammonia')?'FeCl₃ + amônia':'Peracética';?>
      <article class="student-process-standard-card">
        <div class="student-process-standard-meta"><span><?=h($ei)?></span><span>Parodinal</span><span><?=h($route)?></span></div>
        <h3><?=h((string)$standard['name'])?></h3>
        <?php if(!$test):?><p><?=h((string)$standard['description'])?></p><?php endif;?>
        <div class="student-process-standard-footer"><span><?=count((array)$standard['steps'])?> etapas · <?=h(student_process_standard_duration_summary((array)$standard['steps']))?></span>
          <form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="copy_standard"><input type="hidden" name="standard_key" value="<?=h((string)$standardKey)?>"><input type="hidden" name="test_id" value="<?=$testId?>"><button class="button button-primary button-compact" type="submit"><?=$testId>0?'Associar ao registro':'Adicionar aos meus'?></button></form>
        </div>
      </article>
    <?php endforeach;?>
  </div>
</section>

<details class="student-process-create">
  <summary><span><strong>Criar um processamento do zero</strong><small>Use esta opção quando o roteiro não parte de um padrão do workshop.</small></span><span aria-hidden="true">＋</span></summary>
  <form method="post" class="student-form-grid"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="create"><input type="hidden" name="test_id" value="<?=$testId?>"><label class="form-field student-span-2">Nome<input name="name" required maxlength="160" placeholder="Ex.: Positivo direto — teste de branqueamento"></label><label class="form-field student-span-2">Descrição<textarea name="description" rows="2" maxlength="1000" placeholder="O que diferencia este roteiro?"></textarea></label><div class="student-actions student-span-2"><button class="button button-primary" type="submit">Criar e montar roteiro</button></div></form>
</details>

<?php else:?>
<?php student_process_render_template_editor($template,$steps,$editStep,$catalog,$developers,$preparations,$csrf,$testId,student_process_template_duration_summary($db,(int)$template['id']));?>
<?php endif;?>
<?php student_shell_end();