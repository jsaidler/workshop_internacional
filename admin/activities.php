<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
require_admin();
$db=database();
$error='';
$notice='';
$root=root_activity($db);
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('activities',$_POST['csrf']??null)){$error='Solicitação inválida.';}
    else{
        try{
            $action=(string)($_POST['action']??'');
            if($action==='update'){
                if((int)($_POST['activity_id']??0)!==(int)$root['id'])throw new RuntimeException('Somente o site principal pode ser editado aqui.');
                activity_update_localized_identity($db,(int)$root['id'],(string)($_POST['admin_name']??''),[
                    PUBLIC_LOCALE_PT_BR=>(string)($_POST['public_title_pt']??''),
                    PUBLIC_LOCALE_EN=>(string)($_POST['public_title_en']??''),
                ]);
                $notice='Identidade do site atualizada.';
            }elseif($action==='archive_legacy'){
                activity_archive_legacy_site($db,(int)($_POST['activity_id']??0));
                $notice='Estrutura legada removida da operação normal.';
            }else throw new RuntimeException('Ação inválida.');
        }catch(Throwable $e){$error=$e->getMessage();}
    }
}
$state=admin_activity_resolution($db);
$localized=activity_locales($db,(int)$root['id']);$ptTitle=(string)($localized[PUBLIC_LOCALE_PT_BR]['public_title']??'');$enTitle=(string)($localized[PUBLIC_LOCALE_EN]['public_title']??'');
$legacy=activity_legacy_non_root($db);
admin_shell_start('activities','Identidade da instalação',$state);
?>
<?php if($notice!==''):?><p class="admin-success"><?=h($notice)?></p><?php endif;?>
<?php if($error!==''):?><p class="admin-error" role="alert"><?=h($error)?></p><?php endif;?>
<section class="admin-section-stack">
  <section class="admin-panel">
    <h2>Site principal</h2>
    <p class="muted">Workshops são criados e organizados em Páginas. Esta tela existe apenas para a identidade do site e manutenção de estruturas antigas.</p>
    <form method="post" class="admin-form-grid">
      <input type="hidden" name="csrf" value="<?=h(csrf_token('activities'))?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="activity_id" value="<?=(int)$root['id']?>">
      <label class="admin-form-span-2">Nome no admin<input name="admin_name" value="<?=h((string)$root['admin_name'])?>" required maxlength="160"></label>
      <label>Nome público · Português<input name="public_title_pt" value="<?=h($ptTitle)?>" maxlength="220"></label>
      <label>Nome público · English<input name="public_title_en" value="<?=h($enTitle)?>" maxlength="220"></label>
      <div class="admin-form-actions"><button class="admin-button" type="submit">Salvar identidade</button></div>
    </form>
  </section>
  <?php if($legacy):?>
  <section class="admin-panel">
    <p class="admin-kicker">Manutenção</p>
    <h2>Estruturas antigas</h2>
    <p class="muted">Estas estruturas foram criadas pelo modelo anterior, em que um workshop era tratado como activity. Removê-las da operação apenas arquiva o contêiner e preserva os dados para uma migração segura.</p>
    <div class="activity-list">
      <?php foreach($legacy as $item):?>
        <article class="activity-card">
          <strong><?=h((string)$item['admin_name'])?></strong>
          <p class="muted"><?=h((string)$item['slug'])?></p>
          <form method="post" onsubmit="return confirm('Remover esta estrutura antiga da operação normal? Os dados serão preservados para migração.');">
            <input type="hidden" name="csrf" value="<?=h(csrf_token('activities'))?>">
            <input type="hidden" name="action" value="archive_legacy">
            <input type="hidden" name="activity_id" value="<?=(int)$item['id']?>">
            <button class="admin-button danger" type="submit">Remover estrutura antiga</button>
          </form>
        </article>
      <?php endforeach;?>
    </div>
  </section>
  <?php endif;?>
</section>
<?php admin_shell_end();
