<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
require_once __DIR__.'/../app/update_service.php';
require_once __DIR__.'/../app/update_restore.php';
security_headers();require_admin();
$db=database();$state=admin_activity_resolution($db);$activityId=(int)($state['activity']['id']??0);$notice=$_SESSION['system_notice']??null;$error=$_SESSION['system_error']??null;unset($_SESSION['system_notice'],$_SESSION['system_error']);$status=null;
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('system-update',$_POST['_csrf']??null)){http_response_code(403);exit('Invalid request');}
    $action=(string)($_POST['action']??'install');
    try{
        if($action==='rollback'){
            $backupName=(string)($_POST['backup_name']??'');$result=update_restore($backupName,false);$_SESSION['system_notice']='Arquivos restaurados a partir de '.h($backupName).': '.count($result['restored']).' restaurado(s), '.count($result['deleted']).' removido(s). O banco de dados não foi alterado.';
        }else{
            $result=update_apply();$_SESSION['system_notice']='Atualização instalada: '.substr((string)$result['sourceSha'],0,12).' · '.count($result['changed']).' arquivo(s) atualizado(s).'.(!empty($result['databaseBackup'])?' Uma cópia consistente do banco foi criada antes da troca dos arquivos.':'').' A próxima requisição executará eventuais migrações.';
        }
    }catch(Throwable $e){$_SESSION['system_error']=$e->getMessage();}
    header('Location: /admin/system.php'.($activityId?'?activity='.$activityId:''),true,303);exit;
}
try{$status=update_status();}catch(Throwable $e){$error=$error?:$e->getMessage();}
$local=$status['local']??update_local_info();$remote=$status['remote']??null;$available=(bool)($status['available']??false);$backups=update_backup_history();
admin_shell_start('system','Sistema e atualizações',$state);?>
<?php if($notice):?><div class="admin-notice"><?=$notice?></div><?php endif;?>
<?php if($error):?><div class="admin-notice error"><?=h($error)?></div><?php endif;?>
<section class="overview-hero"><div><p class="admin-kicker">Manutenção</p><h2>Atualizações sem FTP</h2><p>Depois da instalação inicial deste recurso, novas versões publicadas no repositório podem ser aplicadas daqui. Banco, uploads, configuração local e logs não são substituídos.</p></div></section>
<section class="overview-grid">
<article class="overview-card"><p class="admin-kicker">Instalado</p><h2><?=h(substr((string)($local['sourceSha']??'unknown'),0,12))?></h2><p><?=h((string)($local['generatedAt']??'Versão anterior ao sistema de atualização'))?></p></article>
<article class="overview-card"><p class="admin-kicker">Produção disponível</p><h2><?=h($remote?substr((string)($remote['sourceSha']??'unknown'),0,12):'indisponível')?></h2><p><?=$remote?h((string)($remote['generatedAt']??'')):'Não foi possível consultar o canal de produção.'?></p></article>
</section>
<section class="admin-editor-card"><div class="admin-editor-header"><div><p class="admin-kicker">Canal de produção</p><h2><?=$available?'Há uma atualização disponível':'Sistema atualizado'?></h2></div></div>
<p>O atualizador baixa primeiro todos os arquivos modificados, confere SHA-256, cria uma cópia de segurança dos arquivos afetados e uma cópia consistente do banco SQLite, e só então substitui a aplicação. Atualizações simultâneas são bloqueadas e o histórico é limitado às cópias recentes.</p>
<?php if($available):?><form method="post" data-confirm="Aplicar a versão de produção agora? Uma cópia do banco e dos arquivos alterados será criada antes da troca."><input type="hidden" name="_csrf" value="<?=h(csrf_token('system-update'))?>"><input type="hidden" name="action" value="install"><button class="admin-button" type="submit">Instalar atualização</button></form><?php else:?><p class="admin-muted">Nenhuma atualização pendente.</p><?php endif;?>
</section>
<section class="admin-editor-card"><div class="admin-editor-header"><div><p class="admin-kicker">Histórico local</p><h2>Cópias criadas antes das atualizações</h2></div><p>As cópias ficam em <code>storage/updates/</code> e não são publicadas pelo deploy.</p></div>
<p class="admin-muted">“Restaurar arquivos” desfaz o código daquela atualização, mas preserva o banco atual. A cópia SQLite continua guardada como recuperação de emergência; ela não é aplicada automaticamente para evitar perda de inscrições ou edições feitas depois da atualização.</p>
<?php if(!$backups):?><p class="admin-muted">Ainda não há cópias registradas por este atualizador.</p><?php else:?><div class="overview-grid"><?php foreach($backups as $backup):?><article class="overview-card"><p class="admin-kicker"><?=h((string)($backup['installedAt']??$backup['name']))?></p><h2><?=h($backup['sourceSha']!==''?substr($backup['sourceSha'],0,12):'cópia local')?></h2><p><?=count($backup['changed'])?> arquivo(s) alterado(s) · <?=count($backup['removed'])?> removido(s)</p><p><?=$backup['database']?'Banco SQLite incluído.':'Sem cópia de banco registrada.'?></p><p><code><?=h($backup['name'])?></code></p><form method="post" data-confirm="Restaurar os arquivos desta cópia? O banco atual será preservado e uma nova cópia de segurança será criada antes da restauração."><input type="hidden" name="_csrf" value="<?=h(csrf_token('system-update'))?>"><input type="hidden" name="action" value="rollback"><input type="hidden" name="backup_name" value="<?=h($backup['name'])?>"><button class="admin-button secondary" type="submit">Restaurar arquivos</button></form></article><?php endforeach;?></div><?php endif;?>
</section>
<section class="admin-editor-card"><p class="admin-kicker">Persistência protegida</p><h2>O que nunca é substituído</h2><div class="overview-grid"><article class="overview-card"><h2>Banco</h2><p><code>storage/database.sqlite</code></p></article><article class="overview-card"><h2>Uploads</h2><p><code>uploads/</code></p></article><article class="overview-card"><h2>Configuração</h2><p><code>config/local.php</code></p></article><article class="overview-card"><h2>Logs</h2><p><code>storage/logs/</code></p></article></div></section>
<?php admin_shell_end();
