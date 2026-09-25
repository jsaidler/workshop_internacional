<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
require_admin();
$db=database();
$error='';$notice=$_SESSION['admin_activity_notice']??'';unset($_SESSION['admin_activity_notice']);
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('activities',$_POST['csrf']??null)){$error='Solicitação inválida.';}
    else{
        try{
            $action=(string)($_POST['action']??'create');
            if($action==='update'){
                $updated=activity_update($db,(int)($_POST['activity_id']??0),(string)($_POST['admin_name']??''),(string)($_POST['public_title']??''),(string)($_POST['slug']??''));
                $_SESSION['admin_activity_notice']='Site atualizado. O nome público do curso agora é “'.(string)$updated['public_title'].'”.';
                header('Location: /admin/activities.php?activity='.(int)$updated['id'],true,303);exit;
            }
            $created=activity_create($db,trim((string)($_POST['admin_name']??'')),trim((string)($_POST['public_title']??'')),trim((string)($_POST['slug']??'')));
            $_SESSION['admin_activity_notice']='Site criado.';
            header('Location: /admin/activities.php?activity='.(int)$created['id'],true,303);exit;
        }catch(Throwable $e){$error=$e->getMessage();}
    }
}
$state=admin_activity_resolution($db);
admin_shell_start('activities','Sites',$state);
?>
<?php if($notice!==''):?><p class="admin-success" role="status"><?=h($notice)?></p><?php endif;?>
<?php if($error!==''):?><p class="admin-error" role="alert"><?=h($error)?></p><?php endif;?>
<section class="activities-layout">
  <section class="admin-panel">
    <p class="admin-kicker">Novo site</p><h2>Criar site</h2>
    <form method="post"><input type="hidden" name="csrf" value="<?=h(csrf_token('activities'))?>"><input type="hidden" name="action" value="create">
      <label class="admin-field">Nome administrativo<input name="admin_name" required></label>
      <label class="admin-field">Nome do curso / título público<input name="public_title" required><small>É o nome exibido para o aluno e nas referências públicas deste curso.</small></label>
      <label class="admin-field">Slug<input name="slug" required></label>
      <button class="admin-button" type="submit">Criar site</button>
    </form>
  </section>
  <section>
    <p class="admin-kicker">Sites existentes</p>
    <div class="activity-list">
      <?php if(!$state['activities']):?><div class="admin-empty compact"><h2>Nenhum site criado</h2><p>Crie o primeiro site para começar.</p></div><?php endif;?>
      <?php foreach($state['activities'] as $item):?>
        <article class="activity-card">
          <p class="admin-kicker"><?=((int)$item['is_root']===1)?'Site principal':'Curso / site'?></p>
          <h2><?=h((string)$item['public_title'])?></h2>
          <p><strong><?=h((string)$item['admin_name'])?></strong><br><?=h(admin_public_activity_url($item))?></p>
          <div class="admin-inline-actions"><a href="/admin/?activity=<?=(int)$item['id']?>">Abrir administração</a></div>
          <details>
            <summary>Editar nome e identificação</summary>
            <form method="post" class="admin-form-grid"><input type="hidden" name="csrf" value="<?=h(csrf_token('activities'))?>"><input type="hidden" name="action" value="update"><input type="hidden" name="activity_id" value="<?=(int)$item['id']?>">
              <label class="admin-field">Nome administrativo<input name="admin_name" value="<?=h((string)$item['admin_name'])?>" required></label>
              <label class="admin-field">Nome do curso / título público<input name="public_title" value="<?=h((string)$item['public_title'])?>" required><small>Este é o nome que aparece na Área do aluno.</small></label>
              <label class="admin-field">Slug<input name="slug" value="<?=h((string)$item['slug'])?>" required></label>
              <div class="admin-form-actions"><button class="admin-button" type="submit">Salvar alterações</button></div>
            </form>
          </details>
        </article>
      <?php endforeach;?>
    </div>
  </section>
</section>
<?php admin_shell_end();
