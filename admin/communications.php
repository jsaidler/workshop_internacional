<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();require_admin();
header('Cache-Control: private, no-store, max-age=0');
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
        if($action==='brevo_save'){
            communication_brevo_save($db,(string)($_POST['brevo_api_key']??''));
            $_SESSION['communications_notice']='Brevo conectado. A chave foi salva com segurança.';
            header('Location: /admin/communications.php?activity='.$activityId,true,303);exit;
        }elseif($action==='brevo_check'){
            communication_brevo_check_connection($db);
            $notice='Conexão com o Brevo confirmada.';
        }elseif($action==='brevo_remove'){
            communication_brevo_remove($db);
            $_SESSION['communications_notice']='Chave salva removida. Confira o meio de envio ativo.';
            header('Location: /admin/communications.php?activity='.$activityId,true,303);exit;
        }elseif($action==='mail_settings'){
            communication_sender_save($db,$_POST);
            $_SESSION['communications_notice']='Remetente salvo. Envie um teste para confirmar o funcionamento.';
            header('Location: /admin/communications.php?activity='.$activityId,true,303);exit;
        }elseif($action==='mail_test'){
            $admin=current_admin();$email=(string)($admin['email']??'');
            if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('A conta administrativa não tem um endereço de e-mail válido.');
            communication_send_message($db,[
              'name'=>$email,'email'=>$email,'subject'=>'[TESTE] Envio de e-mails do site',
              'body'=>"Mensagem de verificação do sistema de e-mail.\n\nSe você recebeu este teste, o envio para sua conta está funcionando."
            ]);
            $notice='O servidor aceitou a mensagem de teste. Confira sua caixa de entrada e a pasta de spam.';
        }elseif($action==='admin_notice_preferences'){
            $adminId=(int)(current_admin()['id']??0);
            admin_student_notice_set_preferences($db,$adminId,is_array($_POST['alerts']??null)?$_POST['alerts']:[]);
            $_SESSION['communications_notice']='Preferências dos avisos atualizadas.';
            header('Location: /admin/communications.php?activity='.$activityId,true,303);exit;
        }elseif($action==='admin_notice_test'){
            $email=(string)(current_admin()['email']??'');
            if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('O e-mail da sua conta administrativa é inválido.');
            admin_student_notice_send(['name'=>$email,'email'=>$email,
               'subject'=>'[TESTE] Avisos de atividades de alunos',
               'body'=>"Este é um teste do canal de avisos das ações dos alunos.\n\nO envio deste teste não corresponde a uma ação real."]);
            $notice='E-mail de teste aceito pelo mecanismo de envio. Confira sua caixa de entrada.';
        }elseif(in_array($action,['preview','save'],true)){
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
            communication_send_message($db,[
              'name'=>'Teste do administrador','email'=>$email,
              'subject'=>'[TESTE] '.(string)$selected['subject'],
              'body'=>(string)$selected['body_text']."\n\n[Mensagem de teste. Nenhum aluno foi notificado.]"
            ]);
            $notice='O servidor aceitou a mensagem de teste. Confira sua caixa de entrada e a pasta de spam.';
        }else throw new RuntimeException('Ação inválida.');
    }catch(Throwable $e){$error=$e->getMessage();}
}
if($notice===''){$notice=(string)($_SESSION['communications_notice']??'');unset($_SESSION['communications_notice']);}
$courses=course_list($db,$activityId);$allCohorts=[];
foreach($courses as $course)foreach(course_cohorts_for_course($db,(int)$course['id']) as $cohort)$allCohorts[]=[
 'id'=>(int)$cohort['id'],'course_id'=>(int)$course['id'],
 'label'=>(string)$course['title'].' — '.(string)$cohort['title']
];
$mailSettings=communication_sender_settings($db);
$brevoSettings=communication_brevo_public_status($db);
$mailStatus=communication_mail_status($db);
$worker=communication_worker_status($db);
$workerLast=$worker['last_finished_at']??null;
$workerActive=$workerLast && strtotime((string)$workerLast)!==false && strtotime((string)$workerLast)>time()-3600;
$mailQueueCount=(int)$db->query("SELECT COUNT(*) FROM communication_deliveries WHERE status IN ('pending','processing')")->fetchColumn();
$staffQueueCount=(int)$db->query("SELECT COUNT(*) FROM admin_student_notice_outbox WHERE status IN ('pending','processing')")->fetchColumn();
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
  <header><div><h2>Comunicações</h2><p>Notificações dos alunos, avisos de curso e newsletter.</p></div></header>
  <p>Configure o serviço de envio e confira os avisos que precisam de atenção.</p>
</section>
<section class="admin-card admin-release-card" aria-labelledby="mail-settings-heading">
  <header><div><h2 id="mail-settings-heading">Envio de e-mails</h2>
  <p>Configure o remetente e confirme que as mensagens chegam à sua caixa de entrada.</p></div></header>
  <p><strong>Meio de envio:</strong> <?=h($mailStatus['transport_label'])?></p>
  <?php if($mailStatus['transport']==='php'):?>
  <p class="muted">O site usará o serviço de e-mail da hospedagem. Não precisa de chave de API. A entrega depende das condições desse serviço.</p>
  <?php else:?>
  <p class="muted">Use um remetente autorizado na Brevo. Depois, envie um teste para confirmar o recebimento.</p>
  <?php endif;?>
  <section aria-labelledby="brevo-heading">
    <h3 id="brevo-heading">Brevo</h3>
    <p><strong>Conexão:</strong> <?=($brevoSettings['configured']?'Chave cadastrada':'Não conectado')?></p>
    <?php if($brevoSettings['configured']):?>
    <p class="muted">A chave está protegida e não será exibida novamente. Você pode substituí-la ou testar a conexão.</p>
    <?php else:?>
    <p class="muted">Cole a chave API v3 para ativar o Brevo.</p>
    <?php endif;?>
    <form method="post" class="admin-form-grid" autocomplete="off">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
      <input type="hidden" name="action" value="brevo_save">
      <label class="admin-form-span-2">Chave API do Brevo
        <input type="password" name="brevo_api_key" value="" maxlength="512" autocomplete="new-password" spellcheck="false" required placeholder="<?=($brevoSettings['configured']?'Substituir chave cadastrada':'Cole sua chave API')?>">
      </label>
      <div class="admin-form-actions">
        <button type="submit" class="admin-button"><?=($brevoSettings['configured']?'Substituir chave':'Conectar Brevo')?></button>
      </div>
    </form>
    <?php if($brevoSettings['configured']):?>
    <form method="post" class="admin-form-actions">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
      <button type="submit" name="action" value="brevo_check" class="admin-button secondary">Testar conexão</button>
      <?php if($brevoSettings['managed_in_cms']):?>
      <button type="submit" name="action" value="brevo_remove" class="admin-button secondary" data-confirm="Remover a chave salva do Brevo?">Remover chave</button>
      <?php endif;?>
    </form>
    <?php endif;?>
  </section>
  <form method="post" class="admin-form-grid">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
    <input type="hidden" name="action" value="mail_settings">
    <label>Nome do remetente<input type="text" name="sender_name" maxlength="100" value="<?=h((string)$mailSettings['sender_name'])?>" autocomplete="organization"></label>
    <label>E-mail do remetente<input type="email" name="sender_email" value="<?=h((string)$mailSettings['custom_sender_email'])?>" placeholder="<?=h((string)($mailSettings['sender_email']?:'seu-endereco@dominio.com.br'))?>" autocomplete="off"></label>
    <p class="admin-form-span-2 muted">Use preferencialmente um endereço do domínio do site, autorizado pela hospedagem. Se deixar em branco, será usada a identidade padrão do servidor.</p>
    <div class="admin-form-actions"><button type="submit" class="admin-button">Salvar remetente</button></div>
  </form>
  <form method="post" class="admin-form-actions">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
    <input type="hidden" name="action" value="mail_test">
    <button type="submit" class="admin-button secondary">Enviar teste para <?=h((string)(current_admin()['email']??'minha conta'))?></button>
  </form>
  <h3>Envio automático</h3>
  <?php if(!$workerLast):?>
    <p><strong>Sem execução registrada.</strong> Para que os avisos saiam sem abrir este painel, é necessário ativar a tarefa automática de envio na hospedagem.</p>
  <?php elseif(!$workerActive):?>
    <p><strong>Automação sem execução recente.</strong> Última execução concluída: <?=h((string)$workerLast)?>.</p>
  <?php else:?>
    <p><strong>Rotina executada recentemente:</strong> <?=h((string)$workerLast)?>. Isso não comprova o recebimento dos e-mails.</p>
  <?php endif;?>
  <p><strong>Na fila:</strong> <?=$staffQueueCount?> aviso(s) sobre alunos e <?=$mailQueueCount?> mensagem(ns) para alunos.</p>
  <?php if(!empty($worker['last_error'])):?><p class="admin-notice error" role="alert"><?=h((string)$worker['last_error'])?></p><?php endif;?>
</section>
<?php
$adminId=(int)(current_admin()['id']??0);
$adminNoticeSettings=admin_student_notice_preferences($db,$adminId);
$adminNoticeHistory=admin_student_notice_recent($db,$adminId);
?>
<section class="admin-card admin-release-card">
  <header><div><h2>Avisos sobre atividades dos alunos</h2>
    <p>Receba no e-mail da sua conta administrativa avisos das ações que exigem acompanhamento.</p></div></header>
  <p><strong>Destino:</strong> <?=h((string)(current_admin()['email']??''))?></p>
  <form method="post" class="admin-form-grid">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
    <input type="hidden" name="action" value="admin_notice_preferences">
    <?php foreach(admin_student_notice_kinds() as $kind=>$label):?>
    <label class="admin-form-span-2"><input type="checkbox" name="alerts[<?=h($kind)?>]" value="1"<?=!empty($adminNoticeSettings[$kind])?' checked':''?>> <?=h($label)?></label>
    <?php endforeach;?>
    <div class="admin-form-actions"><button type="submit" class="admin-button">Salvar avisos</button></div>
  </form>
  <form method="post" class="admin-form-actions">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
    <button type="submit" name="action" value="admin_notice_test" class="admin-button secondary">Enviar e-mail de teste</button>
  </form>
  <p class="muted">Os e-mails incluem um link para abrir a atividade no painel.</p>
  <?php if($adminNoticeHistory):?><details class="admin-secondary-setup"><summary class="admin-button secondary">Últimos avisos (<?=count($adminNoticeHistory)?>)</summary>
    <div class="admin-table-scroll"><table class="admin-data-table admin-responsive-list"><thead><tr><th>Ação</th><th>Situação</th><th>Registrado em</th></tr></thead><tbody>
    <?php foreach($adminNoticeHistory as $row):?>
    <tr><td><?=h(admin_student_notice_kinds()[(string)$row['event_kind']]??(string)$row['event_kind'])?></td>
        <td><?=h(['pending'=>'Na fila','processing'=>'Processando','sent'=>'Aceito para envio','skipped'=>'Ignorado','failed'=>'Falha'][$row['status']]??(string)$row['status'])?></td>
        <td><?=h((string)$row['created_at'])?></td></tr>
    <?php endforeach;?></tbody></table></div>
  </details><?php endif;?>
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
    <p class="admin-form-span-2 muted">Newsletter: somente para alunos que autorizaram o recebimento de novidades. Avisos acadêmicos: para participantes do curso ou turma selecionados.</p>
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
  <header><div><h2>Histórico</h2><p>Consulte seus rascunhos e envios.</p></div></header>
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
