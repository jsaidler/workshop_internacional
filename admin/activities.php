<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
require_admin();
$db=database();
$error='';
$notice='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('activities',$_POST['csrf']??null)){$error='Solicitação inválida.';}
    else{
        try{
            $action=(string)($_POST['action']??'create');
            if($action==='update'){
                $updated=activity_update_identity($db,(int)($_POST['activity_id']??0),(string)($_POST['admin_name']??''),(string)($_POST['public_title']??''));
                $notice='Nome do curso atualizado.';
                $_SESSION['activities_notice']=$notice;
                header('Location: /admin/activities.php?activity='.(int)$updated['id'],true,303);exit;
            }
            activity_create($db,trim((string)($_POST['admin_name']??'')),trim((string)($_POST['public_title']??'')),trim((string)($_POST['slug']??'')));
            header('Location: /admin/activities.php',true,303);exit;
        }catch(Throwable $e){$error=$e->getMessage();}
    }
}
$notice=(string)($_SESSION['activities_notice']??'');unset($_SESSION['activities_notice']);
$state=admin_activity_resolution($db);
admin_shell_start('activities','Cursos e workshops',$state);
?>
<?php if($notice!==''):?><p class="admin-success"><?=h($notice)?></p><?php endif;?>
<?php if($error!==''):?><p class="admin-error" role="alert"><?=h($error)?></p><?php endif;?>
<section class="activities-layout">
  <section class="admin-panel">
    <p class="admin-kicker">Nova atividade</p>
    <h2>Criar curso ou workshop</h2>
    <form method="post">
      <input type="hidden" name="csrf" value="<?=h(csrf_token('activities'))?>">
      <input type="hidden" name="action" value="create">
      <label class="admin-field">Nome no admin<input name="admin_name" required maxlength="160"></label>
      <label class="admin-field">Nome público do curso<input name="public_title" required maxlength="220"></label>
      <label class="admin-field">Slug<input name="slug" required></label>
      <button class="admin-button">Criar atividade</button>
    </form>
    <p class="muted">A criação de atividades ainda usa o template histórico do workshop atual. O modo realmente vazio será liberado somente depois da retirada dos seeds automáticos de páginas e formulários.</p>
  </section>
  <section>
    <p class="admin-kicker">Cursos e workshops existentes</p>
    <div class="activity-list">
      <?php if(!$state['activities']):?><div class="admin-empty compact"><h2>Nenhuma atividade criada</h2><p>Crie o primeiro curso ou workshop para começar.</p></div><?php endif;?>
      <?php foreach($state['activities'] as $item):?>
        <article class="activity-card">
          <form method="post">
            <input type="hidden" name="csrf" value="<?=h(csrf_token('activities'))?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="activity_id" value="<?=(int)$item['id']?>">
            <label class="admin-field">Nome no admin<input name="admin_name" value="<?=h((string)$item['admin_name'])?>" required maxlength="160"></label>
            <label class="admin-field">Nome público do curso<input name="public_title" value="<?=h((string)$item['public_title'])?>" required maxlength="220"></label>
            <p class="muted">Endereço: <?=h(admin_public_activity_url($item))?></p>
            <div class="admin-inline-actions"><button class="admin-button" type="submit">Salvar nomes</button><a class="admin-button secondary" href="/admin/?activity=<?=(int)$item['id']?>">Abrir administração</a></div>
          </form>
        </article>
      <?php endforeach;?>
    </div>
  </section>
</section>
<?php admin_shell_end();
