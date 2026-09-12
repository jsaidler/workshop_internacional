<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();
require_admin();

$db=database();
$state=admin_activity_resolution($db);
$activity=$state['activity'];
$activityId=$activity?(int)$activity['id']:0;

function response_url(int $activityId,?int $response=null): string {
    return '/admin/interests.php?activity='.$activityId.($response?'&response='.$response:'');
}
function response_row(PDO $db,int $activityId,int $response): ?array {
    $query=$db->prepare('SELECT * FROM interest_submissions WHERE id=? AND activity_id=?');
    $query->execute([$response,$activityId]);
    return $query->fetch()?:null;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $response=filter_input(INPUT_POST,'submission',FILTER_VALIDATE_INT)?:0;
    $action=(string)($_POST['action']??'');
    $row=$activityId&&$response?response_row($db,$activityId,$response):null;
    if(!$row||!verify_csrf('interest-admin',$_POST['_csrf']??null)){
        $_SESSION['interest_notice']=['error','Não foi possível executar esta ação.'];
        header('Location: '.response_url($activityId));exit;
    }
    if($action==='toggle-status'){
        $next=($row['status']??'new')==='completed'?'new':'completed';
        $db->prepare('UPDATE interest_submissions SET status=? WHERE id=? AND activity_id=?')->execute([$next,$response,$activityId]);
        $_SESSION['interest_notice']=['success',$next==='completed'?'Resposta marcada como concluída.':'Resposta reaberta.'];
        header('Location: '.response_url($activityId,$response));exit;
    }
    if($action==='delete'){
        $db->beginTransaction();
        try{
            $db->prepare('DELETE FROM interest_submissions WHERE id=? AND activity_id=?')->execute([$response,$activityId]);
            $db->commit();
            $_SESSION['interest_notice']=['success','Resposta excluída permanentemente.'];
        }catch(Throwable){
            if($db->inTransaction())$db->rollBack();
            $_SESSION['interest_notice']=['error','Não foi possível excluir a resposta.'];
        }
        header('Location: '.response_url($activityId));exit;
    }
}

$notice=$_SESSION['interest_notice']??null;
unset($_SESSION['interest_notice']);
$open=filter_input(INPUT_GET,'response',FILTER_VALIDATE_INT)?:0;
if($open&&$activityId&&!response_row($db,$activityId,$open)){http_response_code(404);$open=0;}
$query=$db->prepare('SELECT * FROM interest_submissions'.($activityId?' WHERE activity_id=?':'').' ORDER BY id DESC');
$query->execute($activityId?[$activityId]:[]);
$rows=$query->fetchAll();

admin_shell_start('responses','Respostas',$state);
if($notice):?><p class="admin-notice <?=h($notice[0])?>" role="status"><?=h($notice[1])?></p><?php endif;?>
<section class="section-intro">
  <p><?=$activityId?'Respostas recebidas nesta atividade.':'Selecione uma atividade para ver as respostas.'?></p>
  <div class="admin-actions"><span><?=count($rows)?> <?=count($rows)===1?'resposta':'respostas'?></span></div>
</section>
<section class="submission-list" data-response-list>
<?php if(!$rows):?><div class="admin-empty compact"><h2>Nenhuma resposta ainda</h2></div><?php endif;?>
<?php foreach($rows as $row):
    $payload=json_decode($row['payload_json'],true)?:[];
    $days=interest_days($payload);
    $expanded=(int)$row['id']===$open;
    $panel='response-panel-'.(int)$row['id'];
    $availability=$days['kind']==='canonical'?interest_day_labels($days['value'],true).(($payload['preferred_time']??'')!==''?' · '.(string)$payload['preferred_time']:''):'';
    $completed=($row['status']??'new')==='completed';
    $locale=normalize_public_locale($row['workshop_locale']??'en');
    $location=$locale===PUBLIC_LOCALE_PT_BR?trim((string)($row['city']??'')):trim((string)$row['country']);
    $locationLabel=$locale===PUBLIC_LOCALE_PT_BR?'Cidade':'País';
?>
<article class="submission-card response-item" data-response-item>
  <button class="submission-toggle" type="button" aria-expanded="<?=$expanded?'true':'false'?>" aria-controls="<?=$panel?>" data-response-toggle data-response-id="<?=(int)$row['id']?>">
    <span class="submission-summary"><span><strong><?=h($row['name'])?></strong><small><?=h($row['email'])?><?=$location!==''?' · '.h($location):''?></small><?php if($availability):?><small class="availability-summary"><?=h($availability)?></small><?php endif;?></span>
    <span><span class="status-badge <?=$completed?'completed':'new'?>"><?=h(interest_status($row))?></span><small><?=h(interest_date($row['created_at']))?> <b aria-hidden="true"><?=$expanded?'▴':'▾'?></b></small></span></span>
  </button>
  <div class="response-panel" id="<?=$panel?>" <?=$expanded?'':'hidden'?>>
    <div class="response-groups compact-groups">
      <section class="response-group"><h2>Contato</h2><dl>
        <div><dt>Nome</dt><dd><?=h($row['name'])?></dd></div>
        <div><dt>E-mail</dt><dd><a href="mailto:<?=h($row['email'])?>"><?=h($row['email'])?></a></dd></div>
        <?php if($location!==''):?><div><dt><?=$locationLabel?></dt><dd><?=h($location)?></dd></div><?php endif;?>
      </dl></section>
      <section class="response-group"><h2>Disponibilidade</h2><dl>
        <div><dt>Dias preferidos</dt><dd><?php if($days['kind']==='canonical'):foreach($days['value'] as $day):?><span class="day-chip"><?=h(interest_day_labels([$day]))?></span><?php endforeach;elseif($days['value']!==''):?><span class="legacy-value">Formato antigo: <?=h($days['value'])?></span><?php else:?>Não informado<?php endif;?></dd></div>
        <?php if(($payload['preferred_time']??'')!==''):?><div><dt>Horário preferido</dt><dd><?=h(interest_time_label($payload['preferred_time']))?></dd></div><?php endif;?>
        <?php if(($payload['timezone']??'')!==''):?><div><dt>Fuso de referência</dt><dd><?=h(interest_timezone_label($payload['timezone'],$row['created_at']))?></dd></div><?php endif;?>
      </dl></section>
      <section class="response-group"><h2>Perfil e interesse</h2><dl>
        <?php if(trim((string)($payload['experience']??''))!==''):?><div><dt>Experiência</dt><dd class="long-text"><?=nl2br(h($payload['experience']))?></dd></div><?php endif;?>
        <?php if(trim((string)($payload['main_interest']??''))!==''):?><div><dt>Principal interesse</dt><dd class="long-text"><?=nl2br(h($payload['main_interest']))?></dd></div><?php endif;?>
      </dl></section>
      <section class="response-group"><h2>Interesse comercial</h2><dl>
        <div><dt>Resposta</dt><dd><?=h(interest_price_label($payload['price_response']??''))?></dd></div>
        <div class="group-divider"><dt>Consentimento</dt><dd><?=h(interest_bool_label($payload['consent']??$row['consent']))?></dd></div>
      </dl></section>
    </div>
    <footer class="response-panel-actions">
      <form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('interest-admin'))?>"><input type="hidden" name="submission" value="<?=(int)$row['id']?>"><input type="hidden" name="action" value="toggle-status"><button class="admin-button secondary"><?=$completed?'Reabrir resposta':'Marcar como concluída'?></button></form>
      <button class="admin-button danger" type="button" data-delete-dialog-open>Excluir resposta</button>
    </footer>
    <dialog class="delete-dialog" aria-labelledby="delete-title-<?=$row['id']?>" data-delete-dialog>
      <div class="delete-dialog-header"><p class="admin-kicker">Ação permanente</p><button class="dialog-close" type="button" aria-label="Fechar" data-delete-dialog-close>×</button></div>
      <div class="delete-dialog-body"><h2 id="delete-title-<?=$row['id']?>">Excluir resposta?</h2><p><?=h($row['name'])?><br><?=h($row['email'])?><br><?=h(interest_date($row['created_at']))?></p><p>Esta ação removerá permanentemente a resposta selecionada.</p><p class="delete-warning">Esta ação não pode ser desfeita.</p></div>
      <div class="delete-dialog-actions"><button class="admin-button secondary" type="button" data-delete-dialog-close>Cancelar</button><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('interest-admin'))?>"><input type="hidden" name="submission" value="<?=(int)$row['id']?>"><input type="hidden" name="action" value="delete"><button class="admin-button danger" type="submit" data-delete-submit>Excluir resposta</button></form></div>
    </dialog>
  </div>
</article>
<?php endforeach;?>
</section>
<?php admin_shell_end();
