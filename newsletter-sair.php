<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
security_headers();
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow, noarchive');
$db=database();$token=(string)($_POST['token']??$_GET['token']??'');
$user=communication_unsubscribe_resolve($db,$token);
$done=false;
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST' && $user){
    communication_set_opt_in($db,(int)$user['id'],false,'unsubscribe_link');$done=true;
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Preferência de e-mails · Fotografia</title></head><body>
<main>
<h1>Novidades por e-mail</h1>
<?php if($done):?><p>Sua assinatura de novidades foi cancelada. Você continuará recebendo apenas comunicações necessárias aos cursos de que participa.</p>
<?php elseif(!$user):?><p>Este link não está disponível. Você também pode alterar as preferências em sua Conta.</p>
<?php else:?><p>Deseja deixar de receber novidades sobre cursos e atividades?</p>
<form method="post"><input type="hidden" name="token" value="<?=h($token)?>">
<button type="submit">Cancelar assinatura de novidades</button></form>
<?php endif;?></main></body></html>
