<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/student_home.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);
if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2Fnotificacoes.php',true,303);exit;}
$studentId=(int)$student['id'];
$notice='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-notifications',$_POST['_csrf']??null)){http_response_code(403);exit('Solicitação inválida.');}
    if((string)($_POST['action']??'')!=='read_all'){http_response_code(400);exit('Ação inválida.');}
    $count=communication_student_inbox_read_all($db,$studentId);
    $_SESSION['student_notifications_notice']=$count.' aviso(s) marcado(s) como lido(s).';
    header('Location: /aluno/notificacoes.php',true,303);exit;
}
$selectedMessage=max(0,(int)($_GET['message']??0));
$message=$selectedMessage?communication_student_inbox_item($db,$studentId,$selectedMessage):null;
if($selectedMessage && !$message){http_response_code(404);exit('Aviso não encontrado.');}
if($message){communication_student_inbox_read($db,$studentId,$selectedMessage);$message['read_at']=$message['read_at']?:utc_now();}
$notice=(string)($_SESSION['student_notifications_notice']??'');unset($_SESSION['student_notifications_notice']);
$notices=student_home_live_notices($db,$studentId);
student_shell_start('Notificações',null,$student);
?>
<?php if($notice!==''):?><p class="ui-alert ui-alert-notice" role="status"><?=h($notice)?></p><?php endif;?>
<?php if($message):?>
<article class="student-home-live student-home-notifications student-notification-detail">
  <header class="student-home-live-header"><div><a href="/aluno/notificacoes.php">← Todas as notificações</a><h1 class="student-title"><?=h((string)$message['subject'])?></h1></div></header>
  <p class="student-subtitle"><?=h((string)$message['course_title'])?> · <?=h((string)$message['cohort_title'])?></p>
  <div class="student-notification-body"><?=nl2br(h((string)$message['body_text']))?></div>
  <p class="student-home-live-empty">Publicado em <?=h(course_agenda_when((string)$message['created_at'],(string)(app_config()['timezone']??'America/Sao_Paulo')))?></p>
</article>
<?php else:?>
<?php if(count(array_filter($notices,static fn(array $item):bool=>($item['kind']??'')==='academic'&&!empty($item['unread'])))>0):?>
<form method="post" class="student-notifications-actions">
  <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-notifications'))?>">
  <input type="hidden" name="action" value="read_all">
  <button class="button button-secondary" type="submit">Marcar avisos como lidos</button>
</form>
<?php endif;?>
<?php require __DIR__.'/../app/student_home_notifications_view.php'; ?>
<?php endif;?>
<?php student_shell_end();
