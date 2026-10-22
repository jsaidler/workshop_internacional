<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();require_admin();
header('Cache-Control: private, no-store, max-age=0');
$db=database();$state=admin_activity_resolution($db);$activity=$state['activity'];
if(!$activity){header('Location: /admin/activities.php',true,303);exit;}
$activityId=(int)$activity['id'];$error='';$preview=null;$notice='';
$validTabs=['overview','alerts','campaigns','settings'];
$tab=(string)($_GET['tab']??(isset($_GET['id'])?'campaigns':'overview'));
if(!in_array($tab,$validTabs,true))$tab='overview';
$actionTab=[
 'brevo_save'=>'settings','brevo_check'=>'settings','brevo_remove'=>'settings',
 'mail_settings'=>'settings','mail_test'=>'settings','site_url_save'=>'settings',
 'admin_notice_preferences'=>'alerts','admin_notice_test'=>'alerts',
 'preview'=>'campaigns','save'=>'campaigns','queue'=>'campaigns','queue_now'=>'campaigns','test'=>'campaigns','duplicate'=>'campaigns',
  'run_queue'=>'campaigns','cancel_queue'=>'campaigns','cancel_campaign'=>'campaigns','retry_campaign'=>'campaigns'
];
$selectedId=max(0,(int)($_POST['campaign_id']??$_GET['id']??0));
$selected=$selectedId?communication_campaign($db,$activityId,$selectedId):null;
if($selectedId && !$selected){http_response_code(404);exit('Comunicação não encontrada nesta área.');}
$form=$selected?:[
    'category'=>'newsletter','course_id'=>max(0,(int)($_GET['course']??0)),
    'cohort_id'=>0,'subject'=>'','body_text'=>'','body_html'=>'','template_key'=>'minimal','status'=>'draft'
];
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('communications-admin',$_POST['_csrf']??null)){http_response_code(403);exit('Solicitação inválida.');}
    $action=(string)($_POST['action']??'');
    $tab=$actionTab[$action]??$tab;
    try{
        if($action==='site_url_save'){
            communication_site_url_save($db,(string)($_POST['public_url']??''));
            $_SESSION['communications_notice']='Endereço do site salvo.';
            header('Location: /admin/communications.php?'.http_build_query(['activity'=>$activityId,'tab'=>'settings']),true,303);exit;
        }elseif($action==='brevo_save'){
            communication_brevo_save($db,(string)($_POST['brevo_api_key']??''));
            $_SESSION['communications_notice']='Brevo conectado. A chave foi salva com segurança.';
            header('Location: /admin/communications.php?'.http_build_query(['activity'=>$activityId,'tab'=>$tab]),true,303);exit;
        }elseif($action==='brevo_check'){
            communication_brevo_check_connection($db);
            $notice='Conexão com o Brevo confirmada.';
        }elseif($action==='brevo_remove'){
            communication_brevo_remove($db);
            $_SESSION['communications_notice']='Chave salva removida. Confira o meio de envio ativo.';
            header('Location: /admin/communications.php?'.http_build_query(['activity'=>$activityId,'tab'=>$tab]),true,303);exit;
        }elseif($action==='mail_settings'){
            communication_sender_save($db,$_POST);
            $_SESSION['communications_notice']='Remetente salvo. Envie um teste para confirmar o funcionamento.';
            header('Location: /admin/communications.php?'.http_build_query(['activity'=>$activityId,'tab'=>$tab]),true,303);exit;
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
            header('Location: /admin/communications.php?'.http_build_query(['activity'=>$activityId,'tab'=>$tab]),true,303);exit;
        }elseif($action==='admin_notice_test'){
            $email=(string)(current_admin()['email']??'');
            if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('O e-mail da sua conta administrativa é inválido.');
            admin_student_notice_send(['name'=>$email,'email'=>$email,
               'subject'=>'[TESTE] Avisos de atividades de alunos',
               'body'=>"Este é um teste do canal de avisos das ações dos alunos.\n\nO envio deste teste não corresponde a uma ação real."]);
            $notice='E-mail de teste aceito pelo mecanismo de envio. Confira sua caixa de entrada.';
        }elseif($action==='run_queue'){
            $batch=communication_dispatch_manual($db,$activityId,1);
            $summary=communication_queue_summary($db,$activityId);
            $_SESSION['communications_notice']=!empty($batch['busy'])
                ?'Outro envio está em andamento. A fila permanece preservada.'
                :'Lote processado: '.(int)$batch['sent'].' aceito(s), '.(int)$batch['skipped'].' ignorado(s) e '.(int)$batch['failed'].' falha(s). Restam '.(int)$summary['pending'].' pendente(s).';
            header('Location: /admin/communications.php?'.http_build_query(['activity'=>$activityId,'tab'=>'campaigns','run'=>1]),true,303);exit;
        }elseif($action==='cancel_queue'){
            $count=communication_cancel_queue($db,$activityId);
            $_SESSION['communications_notice']=$count.' envio(s) pendente(s) cancelado(s). Mensagens já em processamento ou aceitas pelo provedor não podem ser canceladas.';
            header('Location: /admin/communications.php?'.http_build_query(['activity'=>$activityId,'tab'=>'campaigns']),true,303);exit;
        }elseif($action==='cancel_campaign'){
            if(!$selected)throw new RuntimeException('Mensagem não encontrada.');
            $count=communication_cancel_queue($db,$activityId,$selectedId);
            $_SESSION['communications_notice']=$count.' envio(s) pendente(s) cancelado(s) desta mensagem. Envios já aceitos não foram alterados.';
            header('Location: /admin/communications.php?'.http_build_query(['activity'=>$activityId,'tab'=>'campaigns','id'=>$selectedId]),true,303);exit;
        }elseif($action==='retry_campaign'){
            if(!$selected)throw new RuntimeException('Mensagem não encontrada.');
            $count=communication_retry_failed($db,$activityId,$selectedId);
            if($count===0)throw new RuntimeException('Não há falhas pendentes de reprocessamento nesta mensagem.');
            $batch=communication_dispatch_manual($db,$activityId,1);
            $_SESSION['communications_notice']=$count.' destinatário(s) com falha recolocado(s) na fila após sua confirmação. '.(int)$batch['sent'].' aceito(s) no primeiro lote.';
            header('Location: /admin/communications.php?'.http_build_query(['activity'=>$activityId,'tab'=>'campaigns','id'=>$selectedId,'run'=>1]),true,303);exit;
        }elseif($action==='duplicate'){
            if(!$selected)throw new RuntimeException('Selecione uma mensagem para duplicar.');
            $id=communication_duplicate_campaign($db,$activityId,$selectedId);
            $_SESSION['communications_notice']='Cópia criada como rascunho. Confira os destinatários antes de enviar.';
            header('Location: /admin/communications.php?'.http_build_query(['activity'=>$activityId,'tab'=>'campaigns','id'=>$id]),true,303);exit;
        }elseif(in_array($action,['preview','save'],true)){
            $form=[
              'category'=>(string)($_POST['category']??''),
              'course_id'=>max(0,(int)($_POST['course_id']??0)),
              'cohort_id'=>max(0,(int)($_POST['cohort_id']??0)),
              'subject'=>(string)($_POST['subject']??''),
              'body_text'=>(string)($_POST['body_text']??''),
              'body_html'=>(string)($_POST['body_html']??''),
              'template_key'=>(string)($_POST['template_key']??'minimal'),
              'recipient_ids'=>is_array($_POST['recipient_ids']??null)?$_POST['recipient_ids']:[],
              'selection_explicit'=>1,'status'=>'draft'
            ];
            $audience=communication_audience($db,$activityId,$form['category'],$form['course_id'],$form['cohort_id']);
            if($action==='save'){
                $id=communication_update_campaign($db,$activityId,$selectedId,$form);
                $_SESSION['communications_notice']='Rascunho salvo. Revise a audiência antes de colocar na fila.';
                header('Location: /admin/communications.php?'.http_build_query(['activity'=>$activityId,'tab'=>'campaigns','id'=>$id]),true,303);exit;
            }
            $chosen=communication_validate_targets($db,$activityId,$form);
            $preview=count($chosen);
        }elseif(in_array($action,['queue','queue_now'],true)){
            if(!$selected)throw new RuntimeException('Salve o rascunho antes de enviar.');
            if($selected['category']==='newsletter'&&communication_site_url($db)==='')
                throw new RuntimeException('Configure o endereço do site em Configuração para incluir o link de descadastro.');
            $count=communication_queue_campaign($db,$activityId,$selectedId);
            if($action==='queue_now'){
                $batch=communication_dispatch_manual($db,$activityId,1);
                $_SESSION['communications_notice']=$count.' destinatário(s) confirmado(s). '.(!empty($batch['busy'])
                    ?'Outro processamento está em andamento; tente novamente se necessário.'
                    :'Envio imediato iniciado. '.(int)$batch['sent'].' aceito(s) neste primeiro lote.');
            }else{
                $_SESSION['communications_notice']=$count.' e-mail(s) colocado(s) na fila. Use Enviar agora para processá-los sem agendamento.';
            }
            header('Location: /admin/communications.php?'.http_build_query(['activity'=>$activityId,'tab'=>'campaigns','id'=>$selectedId]+($action==='queue_now'?['run'=>1]:[])),true,303);exit;
        }elseif($action==='test'){
            if(!$selected || $selected['status']!=='draft')throw new RuntimeException('Salve o rascunho antes do teste.');
            $admin=current_admin();$email=(string)($admin['email']??'');
            if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Conta administrativa sem e-mail válido.');
            communication_send_message($db,[
              'name'=>'Teste do administrador','email'=>$email,
              'subject'=>'[TESTE] '.(string)$selected['subject'],
              'body'=>(string)$selected['body_text']."\n\n[Mensagem de teste. Nenhum aluno foi notificado.]",
              'html'=>(string)($selected['body_html']??''),
              'template'=>(string)($selected['template_key']??'minimal'),
              'activity_id'=>$activityId
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
$siteUrl=communication_site_url($db);
$worker=communication_worker_status($db);
$workerLast=$worker['last_finished_at']??null;
$workerActive=$workerLast && strtotime((string)$workerLast)!==false && strtotime((string)$workerLast)>time()-3600;
$queueSummary=communication_queue_summary($db,$activityId);
$mailQueueCount=$queueSummary['pending']+$queueSummary['processing'];
$autoQueueRun=(string)($_GET['run']??'')==='1';
$staffQueueCount=(int)$db->query("SELECT COUNT(*) FROM admin_student_notice_outbox WHERE status IN ('pending','processing')")->fetchColumn();
$campaigns=communication_campaigns($db,$activityId);
$selectedDeliveryRows=$selected && $selected['status']!=='draft'?communication_campaign_deliveries($db,$activityId,$selectedId):[];
if($selected && $preview===null){
    try{$preview=count(communication_campaign_audience($db,$activityId,$selected));}
    catch(Throwable $e){$error=$e->getMessage();}
}
$recipientCandidates=[];
$selectedRecipientIds=[];
if($tab==='campaigns'&&($form['status']??'draft')==='draft'){
    try{
        $recipientCandidates=communication_audience($db,$activityId,(string)$form['category'],(int)$form['course_id'],(int)$form['cohort_id']);
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&isset($_POST['selection_explicit'])){
            $selectedRecipientIds=communication_requested_ids(is_array($_POST['recipient_ids']??null)?$_POST['recipient_ids']:[]);
        }elseif($selected && ($selected['selection_mode']??'all')==='selected'){
            $selectedRecipientIds=communication_campaign_target_ids($db,(int)$selected['id']);
        }else $selectedRecipientIds=array_map('intval',array_column($recipientCandidates,'id'));
    }catch(Throwable $ignored){$recipientCandidates=[];}
}
$recipientLookup=array_fill_keys($selectedRecipientIds,true);
$emailBrand=communication_email_brand($db,$activityId);
$renderedEmailPreview='';
if($tab==='campaigns'&&trim((string)($form['body_text']??''))!==''){
    try {
        $renderedEmailPreview=communication_email_document((string)($form['body_html']??''),(string)$form['body_text'],
            $db,(string)($form['template_key']??'minimal'),$activityId);
    }catch(Throwable $e){$error=$e->getMessage();}
}
admin_shell_start('communications','Comunicações',$state,['/assets/admin-operations.css']);
?>
<?php if($notice!==''):?><p class="admin-notice success" role="status"><?=h($notice)?></p><?php endif;?>
<?php if($error!==''):?><p class="admin-notice error" role="alert"><?=h($error)?></p><?php endif;?>
<nav class="admin-subtabs comms-tabs" aria-label="Áreas de comunicações">
<?php foreach(['overview'=>'Visão geral','alerts'=>'Avisos dos alunos','campaigns'=>'Mensagens','settings'=>'Configuração'] as $key=>$label):?>
  <a href="?<?=h(http_build_query(['activity'=>$activityId,'tab'=>$key]))?>"<?=$tab===$key?' aria-current="page"':''?>><?=h($label)?></a>
<?php endforeach;?>
</nav>
<div class="comms-workspace">
<?php if(in_array($tab,['overview','campaigns'],true)):?>
<section class="admin-card comms-section comms-wide" data-communications-queue data-queue-url="/admin/communications-queue.php" data-auto-run="<?=$autoQueueRun?'1':'0'?>">
  <header><div><h2>Fila de mensagens</h2><p>Envie quando quiser, mesmo sem agendamento no servidor.</p></div></header>
  <p data-queue-count aria-live="polite"><strong><?=$queueSummary['pending']?></strong> pendente(s) · <?=$queueSummary['processing']?> em processamento · <?=$queueSummary['accepted']?> aceito(s) pelo provedor · <?=$queueSummary['failed']?> falha(s) · <?=$queueSummary['cancelled']?> cancelado(s).</p>
  <form method="post" class="comms-inline-actions" data-queue-form>
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
    <button class="admin-button" name="action" value="run_queue" type="submit"<?=$queueSummary['pending']<1?' disabled':''?> data-queue-run>Enviar agora</button>
    <button class="admin-button secondary" name="action" value="cancel_queue" type="submit"<?=$queueSummary['pending']<1?' disabled':''?> data-queue-cancel>Cancelar fila</button>
  </form>
  <p class="muted" data-queue-feedback role="status" aria-live="polite">Cancelar afeta somente os envios ainda pendentes. Mensagens já aceitas ou em processamento não podem ser retiradas.</p>
</section>
<?php endif;?>
<?php if($tab==='overview'):?>
  <div class="comms-lead">
    <div><p class="comms-eyebrow">Central de comunicações</p><h2>Visão geral</h2>
    <p>Consulte os envios pendentes e acesse as tarefas que precisam de atenção.</p></div>
    <a class="admin-button" href="?<?=h(http_build_query(['activity'=>$activityId,'tab'=>'campaigns']))?>">Escrever mensagem</a>
  </div>
  <div class="admin-stat-grid comms-stats">
    <div class="admin-stat"><span>Serviço de envio</span><strong><?=h($mailStatus['transport_label'])?></strong>
      <small><?=($brevoSettings['configured']?'Chave cadastrada':'Sem chave Brevo')?><?=(!$mailStatus['sender_configured']?' · remetente pendente':'')?></small></div>
    <div class="admin-stat"><span>Notificações na fila</span><strong><?=$staffQueueCount?></strong><small>Ações dos alunos</small></div>
    <div class="admin-stat"><span>Mensagens na fila</span><strong><?=$mailQueueCount?></strong><small>Para participantes</small></div>
    <div class="admin-stat"><span>Envio automático</span><strong><?=$workerActive?'Ativo recentemente':($workerLast?'Verificar':'Não identificado')?></strong>
      <small><?=$workerLast?'Última execução: '.h((string)$workerLast):'Nenhuma execução registrada'?></small></div>
  </div>
  <div class="comms-overview-grid">
    <section class="admin-card comms-section">
      <header><div><h2>Avisos dos alunos</h2><p>Dúvidas, respostas e registros enviados para avaliação.</p></div></header>
      <p><strong><?=$staffQueueCount?></strong> aviso(s) aguardando processamento.</p>
      <a class="admin-button secondary" href="?<?=h(http_build_query(['activity'=>$activityId,'tab'=>'alerts']))?>">Gerenciar avisos</a>
    </section>
    <section class="admin-card comms-section">
      <header><div><h2>Mensagens</h2><p>Avisos de curso e newsletters com destinatários verificados.</p></div></header>
      <p><strong><?=count($campaigns)?></strong> comunicação(ões) no histórico.</p>
      <a class="admin-button secondary" href="?<?=h(http_build_query(['activity'=>$activityId,'tab'=>'campaigns']))?>">Abrir mensagens</a>
    </section>
    <section class="admin-card comms-section">
      <header><div><h2>Configuração</h2><p>Brevo, remetente, endereço do site e funcionamento da fila.</p></div></header>
      <?php if($siteUrl===''):?>
        <p class="comms-attention"><strong>Endereço do site não definido.</strong> Ele é necessário para o link de descadastro da newsletter, mas não impede o teste de conexão com o Brevo.</p>
      <?php elseif(!$mailStatus['sender_configured']):?>
        <p class="comms-attention"><strong>Remetente pendente.</strong> Defina um e-mail autorizado para enviar mensagens.</p>
      <?php else:?>
        <p>Remetente: <strong><?=h((string)$mailSettings['sender_email'])?></strong></p>
      <?php endif;?>
      <a class="admin-button secondary" href="?<?=h(http_build_query(['activity'=>$activityId,'tab'=>'settings']))?>">Configurar envio</a>
    </section>
  </div>
<?php elseif($tab==='settings'):?>
  <div class="comms-lead"><div><p class="comms-eyebrow">Envio de e-mails</p><h2>Configuração</h2>
    <p>Conecte o Brevo, escolha o remetente e valide o envio para sua conta.</p></div></div>
  <div class="comms-settings-grid">
    <section class="admin-card comms-section" aria-labelledby="brevo-heading">
      <header><div><h2 id="brevo-heading">Brevo</h2><p>Serviço utilizado para os e-mails do site.</p></div>
        <?=admin_badge($brevoSettings['configured']?'Chave cadastrada':'Não conectado',$brevoSettings['configured']?'good':'attention')?></header>
      <p class="comms-detail">Serviço atual: <strong><?=h($mailStatus['transport_label'])?></strong></p>
      <form method="post" class="comms-form" autocomplete="off">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
        <input type="hidden" name="action" value="brevo_save">
        <label>Chave API do Brevo
          <input type="password" name="brevo_api_key" value="" maxlength="512" autocomplete="new-password" spellcheck="false" required placeholder="<?=$brevoSettings['configured']?'Nova chave para substituição':'Cole a chave API v3'?>">
        </label>
        <button type="submit" class="admin-button"><?=$brevoSettings['configured']?'Substituir chave':'Conectar Brevo'?></button>
      </form>
      <?php if($brevoSettings['configured']):?>
      <form method="post" class="admin-form-actions comms-inline-actions">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
        <button type="submit" name="action" value="brevo_check" class="admin-button secondary">Testar conexão</button>
        <?php if($brevoSettings['managed_in_cms']):?>
        <button type="submit" name="action" value="brevo_remove" class="admin-button secondary" data-confirm="Remover a chave salva do Brevo?">Remover chave</button>
        <?php endif;?>
      </form>
      <?php endif;?>
    </section>
    <section class="admin-card comms-section">
      <header><div><h2>Remetente</h2><p>Identidade exibida nas mensagens enviadas.</p></div></header>
      <form method="post" class="comms-form">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
        <input type="hidden" name="action" value="mail_settings">
        <label>Nome do remetente<input name="sender_name" maxlength="100" value="<?=h((string)$mailSettings['sender_name'])?>"></label>
        <label>E-mail do remetente<input type="email" name="sender_email" value="<?=h((string)$mailSettings['custom_sender_email'])?>" placeholder="<?=h((string)($mailSettings['sender_email']?:'contato@seudominio.com.br'))?>"></label>
        <p class="muted">Escolha um endereço autorizado na Brevo.</p>
        <button type="submit" class="admin-button">Salvar remetente</button>
      </form>
    </section>
    <section class="admin-card comms-section">
      <header><div><h2>Endereço do site</h2><p>Usado nos links de descadastro e acesso às atividades.</p></div>
        <?=admin_badge($siteUrl!==''?'Definido':'Pendente',$siteUrl!==''?'good':'attention')?></header>
      <form method="post" class="comms-form">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
        <input type="hidden" name="action" value="site_url_save">
        <label>Endereço público (HTTPS)<input type="url" name="public_url" value="<?=h($siteUrl)?>" placeholder="https://seudominio.com.br" required maxlength="240" inputmode="url"></label>
        <button type="submit" class="admin-button">Salvar endereço</button>
      </form>
    </section>
    <section class="admin-card comms-section">
      <header><div><h2>Teste de envio</h2><p>Destino: <?=h((string)(current_admin()['email']??''))?></p></div></header>
      <p>Depois de conectar o Brevo e salvar o remetente, envie uma mensagem para confirmar o recebimento.</p>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
        <input type="hidden" name="action" value="mail_test">
        <button type="submit" class="admin-button secondary"<?=$mailStatus['transport']==='brevo'&&!$mailStatus['sender_configured']?' disabled':''?>>Enviar teste para <?=h((string)(current_admin()['email']??'minha conta'))?></button>
      </form>
    </section>
    <section class="admin-card comms-section comms-wide">
      <header><div><h2>Envio automático</h2><p>Mensagens na fila aguardam o processamento periódico da hospedagem.</p></div></header>
      <div class="comms-automation">
        <p><strong>Última execução:</strong> <?=$workerLast?h((string)$workerLast):'Nenhuma registrada'?></p>
        <p><strong>Na fila:</strong> <?=$staffQueueCount?> avisos e <?=$mailQueueCount?> mensagens.</p>
      </div>
      <?php if(!$workerActive):?><p class="comms-attention"><?=$workerLast?'A rotina não executou recentemente.':'A rotina automática ainda não foi detectada.'?> Verifique o agendamento de tarefas da hospedagem.</p><?php endif;?>
    </section>
  </div>
<?php elseif($tab==='alerts'):?>
  <div class="comms-lead"><div><p class="comms-eyebrow">Acompanhamento</p><h2>Avisos dos alunos</h2>
    <p>Escolha quais atividades devem gerar avisos no seu e-mail.</p></div></div>
  <?php
  $adminId=(int)(current_admin()['id']??0);
  $adminNoticeSettings=admin_student_notice_preferences($db,$adminId);
  $adminNoticeHistory=admin_student_notice_recent($db,$adminId);
  ?>
  <section class="admin-card comms-section comms-max">
    <header><div><h2>Notificações</h2><p>Destino: <?=h((string)(current_admin()['email']??''))?></p></div></header>
    <form method="post" class="comms-form">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
      <input type="hidden" name="action" value="admin_notice_preferences">
      <div class="comms-checklist">
      <?php foreach(admin_student_notice_kinds() as $kind=>$label):?>
        <label><input type="checkbox" name="alerts[<?=h($kind)?>]" value="1"<?=!empty($adminNoticeSettings[$kind])?' checked':''?>><span><?=h($label)?></span></label>
      <?php endforeach;?>
      </div>
      <button type="submit" class="admin-button">Salvar avisos</button>
    </form>
    <form method="post" class="comms-inline-actions">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
      <button type="submit" name="action" value="admin_notice_test" class="admin-button secondary">Testar aviso por e-mail</button>
    </form>
  </section>
  <section class="admin-card comms-section">
    <header><div><h2>Atividade recente</h2><p>Últimos avisos gerados para sua conta.</p></div></header>
    <?php if(!$adminNoticeHistory):?><p class="muted">Nenhum aviso registrado.</p><?php else:?>
    <div class="admin-table-scroll"><table class="admin-data-table"><thead><tr><th>Ação</th><th>Situação</th><th>Data</th></tr></thead><tbody>
      <?php foreach($adminNoticeHistory as $row):?>
      <tr><td><?=h(admin_student_notice_kinds()[(string)$row['event_kind']]??(string)$row['event_kind'])?></td>
        <td><?=h(['pending'=>'Na fila','processing'=>'Processando','sent'=>'Aceito para envio','skipped'=>'Ignorado','failed'=>'Falha'][$row['status']]??(string)$row['status'])?></td>
        <td><?=h((string)$row['created_at'])?></td></tr>
      <?php endforeach;?>
    </tbody></table></div>
    <?php endif;?>
  </section>
<?php elseif($tab==='campaigns'):?>
  <div class="comms-lead"><div><p class="comms-eyebrow">Comunicação com participantes</p><h2>Mensagens</h2>
    <p>Redija avisos acadêmicos ou newsletters e revise os destinatários antes de enviar.</p></div>
    <?php if($selectedId):?><a class="admin-button secondary" href="?<?=h(http_build_query(['activity'=>$activityId,'tab'=>'campaigns']))?>">Nova mensagem</a><?php endif;?>
  </div>
  <section class="admin-card comms-section" data-communication-composer data-email-media-url="/admin/communications-media.php" data-email-csrf="<?=h(csrf_token('communications-admin'))?>" data-saved-draft="<?=$selectedId>0?'1':'0'?>" data-audience-url="/admin/communications-audience.php">
    <header><div><h2><?=($selectedId?((($form['status']??'draft')==='draft')?'Editar mensagem':'Mensagem #'.$selectedId):'Nova mensagem')?></h2>
    <p><?=h((string)(($form['status']??'draft')==='draft'?'Rascunho':'Envio já colocado na fila'))?></p></div></header>
    <?php if(($form['status']??'draft')==='draft'):?>
    <form method="post" class="comms-compose-form" data-composer-form>
      <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
      <input type="hidden" name="campaign_id" value="<?=$selectedId?>">
      <input type="hidden" name="selection_explicit" value="1">
      <div class="comms-filter-grid">
        <label>Tipo de mensagem<select name="category">
          <option value="newsletter"<?=$form['category']==='newsletter'?' selected':''?>>Newsletter</option>
          <option value="operational"<?=$form['category']==='operational'?' selected':''?>>Aviso acadêmico</option>
        </select></label>
        <label>Curso<select name="course_id">
          <option value="0">Todos os cursos</option>
          <?php foreach($courses as $course):?><option value="<?=(int)$course['id']?>"<?=(int)($form['course_id']??0)===(int)$course['id']?' selected':''?>><?=h((string)$course['title'])?></option><?php endforeach;?>
        </select></label>
        <label>Turma<select name="cohort_id">
          <option value="0">Todas as turmas elegíveis</option>
          <?php foreach($allCohorts as $cohort):?><option data-course="<?=$cohort['course_id']?>" value="<?=$cohort['id']?>"<?=(int)($form['cohort_id']??0)===$cohort['id']?' selected':''?>><?=h($cohort['label'])?></option><?php endforeach;?>
        </select></label>
      </div>
      <section class="comms-recipient-panel" aria-labelledby="recipients-title">
        <div class="comms-recipient-heading">
          <div><h3 id="recipients-title">Destinatários</h3><p data-recipient-count aria-live="polite"></p></div>
          <div class="comms-recipient-actions">
            <button type="button" class="admin-button secondary" data-recipient-all>Selecionar todos</button>
            <button type="button" class="admin-button secondary" data-recipient-none>Limpar seleção</button>
          </div>
        </div>
        <label class="comms-search-label">Buscar por nome ou e-mail
          <input type="search" data-recipient-search placeholder="Pesquisar destinatários" autocomplete="off">
        </label>
        <div class="comms-recipient-list" data-recipient-list aria-label="Pessoas elegíveis">
          <?php foreach($recipientCandidates as $person):?>
          <label class="comms-recipient-row">
            <input type="checkbox" name="recipient_ids[]" value="<?=(int)$person['id']?>"<?=isset($recipientLookup[(int)$person['id']])?' checked':''?>>
            <span class="comms-recipient-identity"><strong><?=h((string)$person['name'])?></strong><small><?=h((string)$person['email'])?></small></span>
          </label>
          <?php endforeach;?>
        </div>
        <p class="muted" data-recipient-status aria-live="polite">Somente pessoas elegíveis para o tipo, curso e turma escolhidos.</p>
      </section>
      <fieldset class="comms-template-picker">
        <legend>Modelo visual do e-mail</legend>
        <div class="comms-template-grid" style="--comms-preview-accent:<?=h($emailBrand['accent'])?>;--comms-preview-ink:<?=h($emailBrand['ink'])?>;--comms-preview-ground:<?=h($emailBrand['bg'])?>">
          <?php foreach(communication_template_keys() as $key=>$label):?>
            <label class="comms-template-option">
              <input type="radio" name="template_key" value="<?=h($key)?>"<?=($form['template_key']??'minimal')===$key?' checked':''?>>
              <span class="comms-template-thumbnail comms-template-<?=h($key)?>" aria-hidden="true"><span></span><i></i><b></b><em></em></span>
              <strong><?=h($label)?></strong>
            </label>
          <?php endforeach;?>
        </div>
      </fieldset>
      <label class="comms-compose-label">Assunto<input name="subject" maxlength="180" value="<?=h((string)$form['subject'])?>" required></label>
      <section class="comms-rich-section" aria-labelledby="rich-heading">
        <div class="comms-recipient-heading"><h3 id="rich-heading">Mensagem</h3><span class="muted">Editor visual</span></div>
        <div class="comms-editor-toolbar" role="toolbar" aria-label="Formatação da mensagem">
          <button type="button" data-editor-command="bold" aria-label="Negrito"><strong>N</strong></button>
          <button type="button" data-editor-command="italic" aria-label="Itálico"><em>I</em></button>
          <button type="button" data-editor-command="underline" aria-label="Sublinhado"><u>S</u></button>
          <button type="button" data-editor-command="insertUnorderedList">Lista</button>
          <button type="button" data-editor-command="insertOrderedList">1. Lista</button>
          <button type="button" data-editor-command="formatBlock" data-editor-value="h2">Título</button>
          <button type="button" data-editor-command="insertHorizontalRule">Separador</button>
          <button type="button" data-editor-command="createButton">Botão</button>
          <button type="button" data-editor-command="createLink">Link</button>
          <button type="button" data-email-media-open class="comms-toolbar-image">Inserir imagem</button>
          <button type="button" data-editor-command="removeFormat">Limpar formatação</button>
        </div>
        <div class="comms-rich-editor" data-mail-editor role="textbox" aria-label="Corpo da mensagem" aria-multiline="true" contenteditable="true" hidden><?=($form['body_html']??'')!==''?communication_safe_html((string)$form['body_html']):nl2br(h((string)$form['body_text']))?></div>
        <input type="hidden" name="body_html" value="">
        <label data-plain-fallback class="comms-compose-label">Texto da mensagem
          <textarea name="body_text" rows="11"><?=h((string)$form['body_text'])?></textarea>
        </label>
      </section>
      <p class="comms-attention" data-unsaved-warning hidden>Você alterou a mensagem. Salve o rascunho antes de confirmar o envio.</p>
      <div class="comms-compose-actions">
        <button type="submit" name="action" value="save" class="admin-button">Salvar rascunho</button>
        <button type="submit" name="action" value="preview" class="admin-button secondary">Revisar mensagem</button>
      </div>
    </form>
    <dialog class="comms-media-dialog" data-email-media-dialog aria-labelledby="email-media-dialog-heading">
      <div class="comms-media-dialog-inner">
        <header><h3 id="email-media-dialog-heading">Adicionar imagem</h3><button type="button" class="admin-button secondary" data-email-media-close>Fechar</button></header>
        <div class="comms-media-picker-actions">
          <label>Pesquisar na Biblioteca<input type="search" data-email-media-search placeholder="Nome da imagem"></label>
          <label class="admin-button secondary">Enviar nova imagem<input type="file" data-email-media-upload accept="image/jpeg,image/png,image/webp" hidden></label>
        </div>
        <p class="muted" data-email-media-status aria-live="polite"></p>
        <div class="comms-media-gallery" data-email-media-gallery></div>
        <div class="comms-media-insert" hidden data-email-media-insert-panel>
          <img alt="" data-email-media-selected-image>
          <label>Descrição da imagem (texto alternativo)<input type="text" data-email-media-alt maxlength="250" required placeholder="Descreva o conteúdo para quem não puder visualizar"></label>
          <button type="button" class="admin-button" data-email-media-insert>Inserir na mensagem</button>
        </div>
      </div>
    </dialog>
    <?php else:?>
    <h3><?=h((string)$form['subject'])?></h3>
    <div class="comms-preview-toolbar"><strong>Prévia do e-mail</strong><div class="comms-preview-toggle" aria-label="Largura da prévia"><button type="button" data-email-preview-mode="desktop" aria-pressed="true">Computador</button><button type="button" data-email-preview-mode="mobile" aria-pressed="false">Celular</button></div></div>
      <?php if($renderedEmailPreview!==''):?>
      <div class="comms-email-frame"><iframe title="Prévia visual do e-mail" sandbox="" loading="lazy" srcdoc="<?=h($renderedEmailPreview)?>"></iframe></div>
      <?php else:?><p class="comms-attention">A prévia não está disponível. Verifique as imagens e o endereço do site.</p><?php endif;?>
    <p class="muted"><?=($form['status']==='queued'?'Esta mensagem está na fila. Use Enviar agora para processá-la sem depender do servidor agendado.':'O processamento desta mensagem foi concluído.')?></p>
    <?php endif;?>
    <?php if($preview!==null && ($form['status']??'draft')==='draft'):?>
    <section class="comms-send-review" aria-labelledby="send-review-title">
      <header><h3 id="send-review-title">Revisão do envio</h3><p><strong><?=$preview?></strong> destinatário(s) selecionado(s) e elegível(is).</p></header>
      <p><strong><?=h((string)$form['subject'])?></strong></p>
      <div class="comms-preview-toolbar"><strong>Prévia do e-mail</strong><div class="comms-preview-toggle" aria-label="Largura da prévia"><button type="button" data-email-preview-mode="desktop" aria-pressed="true">Computador</button><button type="button" data-email-preview-mode="mobile" aria-pressed="false">Celular</button></div></div>
      <?php if($renderedEmailPreview!==''):?>
      <div class="comms-email-frame"><iframe title="Prévia visual do e-mail" sandbox="" loading="lazy" srcdoc="<?=h($renderedEmailPreview)?>"></iframe></div>
      <?php else:?><p class="comms-attention">A prévia não está disponível. Verifique as imagens e o endereço do site.</p><?php endif;?>
      <?php if($selected && $selected['status']==='draft' && trim((string)$form['subject'])===(string)$selected['subject'] && trim((string)$form['body_text'])===(string)$selected['body_text'] && (string)($form['body_html']??'')===(string)($selected['body_html']??'') && (string)($form['template_key']??'minimal')===(string)($selected['template_key']??'minimal') && (string)$form['category']===(string)$selected['category'] && (int)$form['course_id']===(int)$selected['course_id'] && (int)$form['cohort_id']===(int)$selected['cohort_id']):?>
      <form method="post" class="comms-inline-actions">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
        <input type="hidden" name="campaign_id" value="<?=$selectedId?>">
        <button type="submit" class="admin-button secondary" name="action" value="test">Enviar teste para meu e-mail</button>
        <button type="submit" class="admin-button" name="action" value="queue_now"<?=$preview===0?' disabled':''?> data-confirm="Confirmar e iniciar agora o envio aos destinatários selecionados e elegíveis? Os demais envios pendentes também serão processados.">Confirmar e enviar agora</button>
        <button type="submit" class="admin-button secondary" name="action" value="queue"<?=$preview===0?' disabled':''?> data-confirm="Confirmar e colocar na fila sem iniciar o processamento agora?">Somente colocar na fila</button>
      </form>
      <?php else:?><p class="muted">Salve o rascunho para confirmar o envio.</p><?php endif;?>
    </section>
    <?php endif;?>
  </section>
<?php if($selected && $selected['status']!=='draft'):?>
<section class="admin-card comms-section">
  <header><div><h2>Destinatários e situação do envio</h2><p>Os resultados indicam o aceite pelo serviço, não a entrega na caixa de entrada.</p></div></header>
  <?php if(!$selectedDeliveryRows):?><p class="muted">Nenhum destinatário registrado.</p><?php else:?>
  <?php
    $deliveryLabels=['pending'=>'Na fila','processing'=>'Processando','sent'=>'Aceito pelo serviço',
      'skipped'=>'Ignorado','failed'=>'Falha'];
    $errorLabels=['cancelled_by_admin'=>'Cancelado pelo administrador',
      'no_longer_eligible'=>'Destinatário deixou de ser elegível',
      'provider_failure'=>'Serviço de envio não confirmou a mensagem',
      'media_unavailable'=>'Imagem indisponível',
      'audience_validation_error'=>'Não foi possível verificar a audiência',
      'processing_outcome_unknown'=>'Resultado incerto após interrupção',
      'processing_timeout'=>'Processamento interrompido'];
  ?>
  <div class="admin-table-scroll"><table class="admin-data-table admin-responsive-list">
    <thead><tr><th>Destinatário</th><th>Situação</th><th>Tentativas</th><th>Atualização</th></tr></thead><tbody>
    <?php foreach($selectedDeliveryRows as $delivery):?>
    <tr>
      <td data-primary="true"><strong><?=h((string)$delivery['name'])?></strong><small><?=h((string)$delivery['email'])?></small></td>
      <td><?=h($delivery['error_code']==='cancelled_by_admin'?'Cancelado':($deliveryLabels[$delivery['status']]??$delivery['status']))?>
      <?php if($delivery['error_code']!==null):?><small class="muted"><?=h($errorLabels[$delivery['error_code']]??'Falha ou restrição registrada')?></small><?php endif;?></td>
      <td><?=(int)$delivery['attempts']?></td>
      <td><?=h((string)$delivery['updated_at'])?></td>
    </tr>
    <?php endforeach;?>
    </tbody></table></div>
  <?php endif;?>
  <?php if(count($selectedDeliveryRows)>=300):?><p class="muted">Exibindo os primeiros 300 registros desta mensagem.</p><?php endif;?>
</section>
<?php endif;?>
<section class="admin-card">
  <header><div><h2>Histórico</h2><p>Consulte seus rascunhos e envios.</p></div></header>
  <?php if(!$campaigns):?><p class="muted">Nenhuma comunicação cadastrada.</p><?php else:?>
  <div class="admin-table-scroll"><table class="admin-data-table admin-responsive-list"><thead><tr><th>Assunto</th><th>Tipo</th><th>Situação</th><th>Aceitos pelo provedor</th><th></th></tr></thead><tbody>
    <?php foreach($campaigns as $campaign):?><tr>
      <td data-primary="true"><?=h((string)$campaign['subject'])?></td>
      <td><?=h($campaign['category']==='newsletter'?'Newsletter':'Aviso de curso')?></td>
      <td><?=h($campaign['status']==='completed' && (int)$campaign['cancelled_count']>0
          ? ((int)$campaign['cancelled_count']===(int)$campaign['audience_count']?'Cancelada':'Parcialmente cancelada')
          : (['draft'=>'Rascunho','queued'=>'Na fila','completed'=>'Processada'][$campaign['status']]??$campaign['status']))?></td>
      <td><?=($campaign['status']==='draft'?'Ainda não enviado':(int)$campaign['sent_count'].' / '.(int)$campaign['audience_count'])?>
        <?php if((int)$campaign['cancelled_count']>0 || (int)$campaign['failed_count']>0):?>
          <small class="muted">· <?=(int)$campaign['cancelled_count']?> cancelado(s) · <?=(int)$campaign['failed_count']?> falha(s)</small>
        <?php endif;?>
      </td>
      <td data-actions="true"><div class="comms-history-actions">
        <a class="admin-button secondary" href="?<?=h(http_build_query(['activity'=>$activityId,'tab'=>'campaigns','id'=>(int)$campaign['id']]))?>">Abrir</a>
        <form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
          <input type="hidden" name="campaign_id" value="<?=(int)$campaign['id']?>">
          <button type="submit" name="action" value="duplicate" class="admin-button secondary">Duplicar</button>
        </form>
        <?php if((int)$campaign['failed_count']>0):?>
        <form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
          <input type="hidden" name="campaign_id" value="<?=(int)$campaign['id']?>">
          <button type="submit" name="action" value="retry_campaign" class="admin-button secondary" data-confirm="Reprocessar somente os destinatários com falha? Confira antes no provedor: uma resposta inconclusiva pode significar que o e-mail foi aceito, e repetir o envio pode duplicá-lo.">Reprocessar falhas</button>
        </form>
        <?php endif;?>
        <?php if($campaign['status']==='queued' && (int)$campaign['pending_count']>0):?>
        <form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('communications-admin'))?>">
          <input type="hidden" name="campaign_id" value="<?=(int)$campaign['id']?>">
          <button type="submit" name="action" value="cancel_campaign" class="admin-button secondary" data-confirm="Cancelar apenas os envios ainda pendentes desta mensagem?">Cancelar pendentes</button>
        </form>
        <?php endif;?>
      </div></td>
    </tr><?php endforeach;?>
  </tbody></table></div>
  <?php endif;?>
</section>

<?php endif;?>
</div>
<script src="/assets/communications-editor.js" defer></script>
<script src="/assets/communications-queue.js" defer></script>
<?php admin_shell_end();
