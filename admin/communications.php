<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();require_admin();
$db=database();$state=admin_activity_resolution($db);$activity=$state['activity'];
if(!$activity){header('Location: /admin/activities.php',true,303);exit;}
$activityId=(int)$activity['id'];$error='';$preview=null;$notice='';
$selectedId=max(0,(int)($_POST['campaign_id']??$_GET['id']??0));
$selected=$selectedId?communication_campaign($db,$activityId,$selectedId):null;
if($selectedId && !$selected){http_response_code(404);exit('Comunicação não encontrada nesta área.');}
$form=$selected?:[
    'category'=>'newsletter','course_id'=>max(0,(int)($_GET['course']??0)),
    'cohort_id'=>0,'subject'=>'','body_text'=>'','status'=>'draft'
];
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('communications-admin',$_POST['_csrf']??null)){http_response_code(403);exit('Solicitação inválida.');}
    $action=(string)($_POST['action']??'');
    try{
        if(in_array($action,['preview','save'],true)){
            $form=[
              'category'=>(string)($_POST['category']??''),
              'course_id'=>max(0,(int)($_POST['course_id']??0)),
              'cohort_id'=>max(0,(int)($_POST['cohort_id']??0)),
              'subject'=>(string)($_POST['subject']??''),
              'body_text'=>(string)($_POST['body_text']??''),
              'status'=>'draft'
            ];
            $audience=communication_audience($db,$activityId,$form['category'],$form['course_id'],$form['cohort_id']);
            if($action==='save'){
                $id=communication_update_campaign($db,$activityId,$selectedId,$form);
                $_SESSION['communications_notice']='Rascunho salvo. Revise a audiência antes de colocar na fila.';
                header('Location: /admin/communications.php?'.http_build_query(['activity'=>$activityId,'id'=>$id]),true,303);exit;
            }
            $preview=count($audience);
        }elseif($action==='queue'){
            if(!$selected)throw new RuntimeException('Salve o rascunho antes de enviar.');
            $count=communication_queue_campaign($db,$activityId,$selectedId);
            $_SESSION['communications_notice']=$count.' e-mail(s) incluído(s) na fila. O processamento depende do serviço de envio configurado.';
            header('Location: /admin/communications.php?'.http_build_query(['activity'=>$activityId,'id'=>$selectedId]),true,303);exit;
        }elseif($action==='test'){
            if(!$selected || $selected['status']!=='draft')throw new RuntimeException('Salve o rascunho antes do teste.');
            $admin=current_admin();$email=(string)($admin['email']??'');
            if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Conta administrativa sem e-mail válido.');
            communication_send_brevo([
              'name'=>'Teste do administrador','email'=>$email,
              'subject'=>'[TESTE] '.(string)$selected['subject'],
              'body'=>(string)$selected['body_text']."\n\n[Mensagem de teste. Nenhum aluno foi notificado.]"
            ]);
            $notice='Teste enviado ao e-mail da sua conta administrativa.';
        }else throw new RuntimeException('Ação inválida.');
    }catch(Throwable $e){$error=$e->getMessage();}
}
if($notice===''){$notice=(string)($_SESSION['communications_notice']??'');unset($_SESSION['communications_notice']);}
$courses=course_list($db,$activityId);$allCohorts=[];
foreach($courses as $course)foreach(course_cohorts_for_course($db,(int)$course['id']) as $cohort)$allCohorts[]=[
 'id'=>(int)$cohort['id'],'course_id'=>(int)$course['id'],
 'label'=>(string)$course['title'].' — '.(string)$cohort['title']
];
$campaigns=communication_campaigns($db,$activityId);
if($selected && $preview===null){
    try{$preview=count(communication_audience($db,$activityId,(string)$selected['category'],(int)$selected['course_id'],(int)$selected['cohort_id']));}
    catch(Throwable $e){$error=$e->getMessage();}
}
admin_shell_start('communications','Comunicações',$state,['/assets/admin-operations.css']);
?>
<?php if($notice!==''):?><p class="admin-notice success" role="status"><?=h($notice)?></p><?php endif;?>
<?php if($error!==''):?><p class="admin-notice error" role="alert"><?=h($error)?></p><?php endif;?>
<section class="admin-card admin-teaching-detail">
  <header><div><h2>Comunicações</h2><p>Avisos de curso e novidades por e-mail. Campanhas promocionais somente para quem aceitou recebê-las.</p></div></header>
  <p class="muted">O envio é realizado pela fila, não durante o salvamento. Se o provedor ainda não estiver configurado, os rascunhos e a prévia continuam disponíveis.</p>
</section>
<section class="admin-card admin-release-card">
  <header><div><h2><?=$selectedId?'Comunicação #'.$selectedId:'Nova comunicação'?></h2>
  <p><?=h((string)($form['status']??'draft')==='draft'?'Rascunho':'Envio já colocado na fila')?></p></div></header>
  <?php if(($form['status']??'draft')==='draft'):?>
  <form class="admin-form-grid" method="post">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
    <input type="hidden" name="campaign_id" value="<?=$selectedId?>">
    <label>Tipo<select name="category">
      <option value="newsletter"<?=($form['category']==='newsletter')?' selected':''?>>Newsletter · novidades</option>
      <option value="operational"<?=($form['category']==='operational')?' selected':''?>>Aviso acadêmico · curso</option>
    </select></label>
    <label>Curso (opcional na newsletter)<select name="course_id">
      <option value="0">Todos os meus cursos</option>
      <?php foreach($courses as $course):?><option value="<?=(int)$course['id']?>"<?=(int)($form['course_id']??0)===(int)$course['id']?' selected':''?>><?=h((string)$course['title'])?></option><?php endforeach;?>
    </select></label>
    <label class="admin-form-span-2">Turma (opcional; selecione o curso correspondente)<select name="cohort_id">
      <option value="0">Todas as turmas elegíveis</option>
      <?php foreach($allCohorts as $cohort):?><option value="<?=$cohort['id']?>"<?=(int)($form['cohort_id']??0)===$cohort['id']?' selected':''?>><?=h($cohort['label'])?></option><?php endforeach;?>
    </select></label>
    <label class="admin-form-span-2">Assunto<input name="subject" maxlength="180" value="<?=h((string)$form['subject'])?>" required></label>
    <label class="admin-form-span-2">Mensagem<textarea name="body_text" rows="10" maxlength="20000" required><?=h((string)$form['body_text'])?></textarea></label>
    <p class="admin-form-span-2 muted">Newsletter: apenas alunos com autorização expressa. Aviso acadêmico: participantes elegíveis do curso ou turma selecionados. Um destinatário recebe uma única mensagem por campanha.</p>
    <div class="admin-form-actions">
      <button type="submit" name="action" value="preview" class="admin-button secondary">Ver destinatários e prévia</button>
      <button type="submit" name="action" value="save" class="admin-button">Salvar rascunho</button>
    </div>
  </form>
  <?php else:?><h3><?=h((string)$form['subject'])?></h3><p><?=nl2br(h((string)$form['body_text']))?></p><?php endif;?>
  <?php if($preview!==null):?>
    <section class="admin-card">
      <h3>Prévia</h3>
      <p><strong><?=$preview?></strong> <?=($preview===1?'destinatário elegível':'destinatários elegíveis')?> na consulta atual.</p>
      <p><strong><?=h((string)$form['subject'])?></strong></p>
      <p><?=nl2br(h((string)$form['body_text']))?></p>
      <?php if($selected && $selected['status']==='draft' && trim((string)$form['subject'])===(string)$selected['subject'] && trim((string)$form['body_text'])===(string)$selected['body_text'] && (string)$form['category']===(string)$selected['category'] && (int)$form['course_id']===(int)$selected['course_id'] && (int)$form['cohort_id']===(int)$selected['cohort_id']):?>
      <form method="post" class="admin-form-actions">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
        <input type="hidden" name="campaign_id" value="<?=$selectedId?>">
        <button class="admin-button secondary" type="submit" name="action" value="test">Enviar teste para meu e-mail</button>
        <button class="admin-button" type="submit" name="action" value="queue"<?=$preview===0?' disabled':''?> data-confirm="Colocar esta comunicação na fila para os destinatários elegíveis?">Confirmar e colocar na fila</button>
      </form>
      <?php endif;?>
    </section>
  <?php endif;?>
</section>
<section class="admin-card">
  <header><div><h2>Histórico</h2><p>Rascunhos e campanhas. Mensagens não são enviadas ao carregar esta tela.</p></div></header>
  <?php if(!$campaigns):?><p class="muted">Nenhuma comunicação cadastrada.</p><?php else:?>
  <div class="admin-table-scroll"><table class="admin-data-table admin-responsive-list"><thead><tr><th>Assunto</th><th>Tipo</th><th>Situação</th><th>Aceitos pelo provedor</th><th></th></tr></thead><tbody>
    <?php foreach($campaigns as $campaign):?><tr>
      <td data-primary="true"><?=h((string)$campaign['subject'])?></td>
      <td><?=h($campaign['category']==='newsletter'?'Newsletter':'Aviso de curso')?></td>
      <td><?=h(['draft'=>'Rascunho','queued'=>'Na fila','completed'=>'Processada'][$campaign['status']]??$campaign['status'])?></td>
      <td><?=(int)$campaign['sent_count']?> / <?=(int)$campaign['audience_count']?></td>
      <td data-actions="true"><a class="admin-button secondary" href="?<?=h(http_build_query(['activity'=>$activityId,'id'=>(int)$campaign['id']]))?>">Abrir</a></td>
    </tr><?php endforeach;?>
  </tbody></table></div>
  <?php endif;?>
</section>
<?php admin_shell_end();
