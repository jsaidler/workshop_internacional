<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
require_once __DIR__.'/../app/update_service.php';
security_headers();require_admin();
$db=database();$state=admin_activity_resolution($db);$notice=null;$error=null;$status=null;
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('system-update',$_POST['_csrf']??null)){http_response_code(403);exit('Invalid request');}
    try{$result=update_apply();$notice='Atualização instalada: '.substr((string)$result['sourceSha'],0,12).' · '.count($result['changed']).' arquivo(s) atualizado(s).'.(!empty($result['databaseBackup'])?' Uma cópia consistente do banco foi criada antes da troca dos arquivos.':'').' Recarregue a área administrativa para executar eventuais migrações.';}
    catch(Throwable $e){$error=$e->getMessage();}
}
try{$status=update_status();}catch(Throwable $e){$error=$error?:$e->getMessage();}
$local=$status['local']??update_local_info();$remote=$status['remote']??null;$available=(bool)($status['available']??false);$backups=update_backup_history();
admin_shell_start('system','Sistema e atualizações',$state);?>
<?php if($notice):?><div class="admin-notice"><?=h($notice)?></div><?php endif;?>
<?php if($error):?><div class="admin-notice error"><?=h($error)?></div><?php endif;?>
<section class="overview-hero"><div><p class="admin-kicker">Manutenção</p><h2>Atualizações sem FTP</h2><p>Depois da instalação inicial deste recurso, novas versões publicadas no repositório podem ser aplicadas daqui. Banco, uploads, configuração local e logs não são substituídos.</p></div></section>
<section class="overview-grid">
<article class="overview-card"><p class="admin-kicker">Instalado</p><h2><?=h(substr((string)($local['sourceSha']??'unknown'),0,12))?></h2><p><?=h((string)($local['generatedAt']??'Versão anterior ao sistema de atualização'))?></p></article>
<article class="overview-card"><p class="admin-kicker">Produção disponível</p><h2><?=h($remote?substr((string)($remote['sourceSha']??'unknown'),0,12):'indisponível')?></h2><p><?=$remote?h((string)($remote['generatedAt']??'')):'Não foi possível consultar o canal de produção.'?></p></article>
</section>
<section class="admin-editor-card"><div class="admin-editor-header"><div><p class="admin-kicker">Canal de produção</p><h2><?=$available?'Há uma atualização disponível':'Sistema atualizado'?></h2></div></div>
<p>O atualizador baixa primeiro todos os arquivos modificados, confere SHA-256, cria uma cópia de segurança dos arquivos afetados e uma cópia consistente do banco SQLite, e só então substitui a aplicação. Se a troca falhar, arquivos novos também são removidos e os arquivos anteriores são restaurados.</p>
<?php if($available):?><form method="post" data-confirm="Aplicar a versão de produção agora? Uma cópia do banco e dos arquivos alterados será criada antes da troca."><input type="hidden" name="_csrf" value="<?=h(csrf_token('system-update'))?>"><button class="admin-button" type="submit">Instalar atualização</button></form><?php else:?><p class="admin-muted">Nenhuma atualização pendente.</p><?php endif;?>
</section>
<section class="admin-editor-card"><div class="admin-editor-header"><div><p class="admin-kicker">Histórico local</p><h2>Cópias criadas antes das atualizações</h2></div><p>As cópias ficam em <code>storage/updates/</code> e não são publicadas pelo deploy.</p></div>
<?php if(!$backups):?><p class="admin-muted">Ainda não há cópias registradas por este atualizador.</p><?php else:?><div class="overview-grid"><?php foreach($backups as $backup):?><article class="overview-card"><p class="admin-kicker"><?=h((string)($backup['installedAt']??$backup['name']))?></p><h2><?=h($backup['sourceSha']!==''?substr($backup['sourceSha'],0,12):'cópia local')?></h2><p><?=count($backup['changed'])?> arquivo(s) alterado(s) · <?=count($backup['removed'])?> removido(s)</p><p><?=$backup['database']?'Banco SQLite incluído.':'Sem cópia de banco registrada.'?></p><p><code><?=h($backup['name'])?></code></p></article><?php endforeach;?></div><?php endif;?>
</section>
<section class="admin-editor-card"><p class="admin-kicker">Persistência protegida</p><h2>O que nunca é substituído</h2><div class="overview-grid"><article class="overview-card"><h2>Banco</h2><p><code>storage/database.sqlite</code></p></article><article class="overview-card"><h2>Uploads</h2><p><code>uploads/</code></p></article><article class="overview-card"><h2>Configuração</h2><p><code>config/local.php</code></p></article><article class="overview-card"><h2>Logs</h2><p><code>storage/logs/</code></p></article></div></section>
<?php admin_shell_end();
