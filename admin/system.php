<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
require_once __DIR__.'/../app/update_service.php';
require_once __DIR__.'/../app/update_restore.php';
require_once __DIR__.'/../app/system_health.php';
security_headers();
require_admin();

$db=database();
$state=admin_activity_resolution($db);
$activityId=(int)($state['activity']['id']??0);
$notice=$_SESSION['system_notice']??null;
$error=$_SESSION['system_error']??null;
unset($_SESSION['system_notice'],$_SESSION['system_error']);
$status=null;

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('system-update',$_POST['_csrf']??null)){http_response_code(403);exit('Invalid request');}
    $action=(string)($_POST['action']??'install');
    try{
        if($action==='rollback'){
            $backupName=(string)($_POST['backup_name']??'');
            $result=update_restore($backupName,false);
            $_SESSION['system_notice']='Arquivos restaurados a partir de '.h($backupName).': '.count($result['restored']).' restaurado(s), '.count($result['deleted']).' removido(s). O banco de dados não foi alterado.';
        }elseif($action==='install'){
            $result=update_apply();
            $_SESSION['system_notice']='Atualização instalada: '.substr((string)$result['sourceSha'],0,12).' · '.count($result['changed']).' arquivo(s) atualizado(s).'.(!empty($result['databaseBackup'])?' Uma cópia consistente do banco foi criada antes da troca dos arquivos.':'').' A próxima requisição executará eventuais migrações.';
        }else{
            throw new RuntimeException('invalid_action');
        }
    }catch(Throwable $e){
        $_SESSION['system_error']=$e->getMessage();
    }
    header('Location: /admin/system.php'.($activityId?'?activity='.$activityId:''),true,303);
    exit;
}

try{$status=update_status();}catch(Throwable $e){$error=$error?:$e->getMessage();}
$local=$status['local']??update_local_info();
$remote=$status['remote']??null;
$available=(bool)($status['available']??false);
$backups=update_backup_history();
$health=system_health_collect();

$formatTimestamp=static function(?string $value): string {
    if(!$value)return 'Sem registro';
    $time=strtotime($value);
    return $time?date('d/m/Y · H:i',$time):$value;
};
$localSha=substr((string)($local['sourceSha']??'unknown'),0,12);
$remoteSha=$remote?substr((string)($remote['sourceSha']??'unknown'),0,12):'indisponível';
$healthLabel=$health['status']==='ok'?'Ambiente pronto':($health['status']==='warn'?'Atenção':'Intervenção necessária');

admin_shell_start('system','Sistema e atualizações',$state);
?>
<?php if($notice):?><div class="admin-notice success" role="status"><?=$notice?></div><?php endif;?>
<?php if($error):?><div class="admin-notice error" role="alert"><?=h($error)?></div><?php endif;?>

<section class="admin-stat-grid" aria-label="Estado do sistema">
  <div class="admin-stat"><span>Versão instalada</span><strong><code><?=h($localSha)?></code></strong><span><?=h($formatTimestamp((string)($local['generatedAt']??'')))?></span></div>
  <div class="admin-stat"><span>Produção disponível</span><strong><code><?=h($remoteSha)?></code></strong><span><?=$remote?h($formatTimestamp((string)($remote['generatedAt']??''))):'Canal indisponível'?></span></div>
  <div class="admin-stat"><span>Diagnóstico</span><strong><?=h($healthLabel)?></strong><span><?=count($health['items'])?> verificações · <?=(int)$health['errors']?> erro(s) · <?=(int)$health['warnings']?> aviso(s)</span></div>
</section>

<div class="admin-section-stack">
  <section class="admin-card">
    <header>
      <div><h2><?=$available?'Atualização disponível':'Sistema atualizado'?></h2><p>O atualizador preserva banco, uploads, configuração local e logs. Antes de substituir arquivos, valida SHA-256 e cria cópias de segurança.</p></div>
      <?php if($available):?>
        <form method="post" data-confirm="Aplicar a versão de produção agora? Uma cópia do banco e dos arquivos alterados será criada antes da troca.">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token('system-update'))?>">
          <input type="hidden" name="action" value="install">
          <button class="admin-button" type="submit">Instalar atualização</button>
        </form>
      <?php endif;?>
    </header>
    <?php if(!$available):?><p class="admin-muted">Nenhuma atualização pendente no canal de produção.</p><?php endif;?>
  </section>

  <section class="admin-card">
    <header><div><h2>Diagnóstico da hospedagem</h2><p>Verificação feita nesta requisição. Nenhuma credencial, caminho físico do servidor ou valor sensível é exibido.</p></div></header>
    <div class="admin-table-scroll">
      <table class="admin-data-table">
        <thead><tr><th>Verificação</th><th>Detalhe</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach($health['items'] as $item):?>
          <tr>
            <td><strong><?=h((string)$item['label'])?></strong></td>
            <td><?=h((string)$item['detail'])?></td>
            <td><span class="admin-badge"><?=h($item['status']==='ok'?'OK':($item['status']==='warn'?'Atenção':'Erro'))?></span></td>
          </tr>
        <?php endforeach;?>
        </tbody>
      </table>
    </div>
    <p class="admin-muted">A disponibilidade de <code>mail()</code> confirma apenas que o PHP oferece a função; não garante a entrega de mensagens. A integridade do SQLite é verificada com <code>PRAGMA quick_check</code>.</p>
  </section>

  <section class="admin-card">
    <header><div><h2>Histórico de atualizações</h2><p>Cópias locais criadas antes de cada troca de arquivos. Restaurar preserva o banco atual.</p></div></header>
    <?php if(!$backups):?>
      <div class="admin-empty">Ainda não há cópias registradas por este atualizador.</div>
    <?php else:?>
      <div class="admin-table-scroll">
        <table class="admin-data-table">
          <thead><tr><th>Versão</th><th>Instalada em</th><th>Arquivos</th><th>Banco</th><th></th></tr></thead>
          <tbody>
          <?php foreach($backups as $backup):?>
            <tr>
              <td><div class="admin-table-primary"><strong><code><?=h($backup['sourceSha']!==''?substr($backup['sourceSha'],0,12):'cópia local')?></code></strong><span><?=h((string)$backup['name'])?></span></div></td>
              <td><?=h($formatTimestamp((string)($backup['installedAt']??'')))?></td>
              <td class="admin-table-number"><?=count($backup['changed'])?> alterado(s) · <?=count($backup['removed'])?> removido(s)</td>
              <td><?=$backup['database']?'Incluído':'Não registrado'?></td>
              <td class="actions">
                <form method="post" data-confirm="Restaurar os arquivos desta cópia? O banco atual será preservado e uma nova cópia de segurança será criada antes da restauração.">
                  <input type="hidden" name="_csrf" value="<?=h(csrf_token('system-update'))?>">
                  <input type="hidden" name="action" value="rollback">
                  <input type="hidden" name="backup_name" value="<?=h($backup['name'])?>">
                  <button class="admin-button secondary admin-table-action" type="submit">Restaurar arquivos</button>
                </form>
              </td>
            </tr>
          <?php endforeach;?>
          </tbody>
        </table>
      </div>
    <?php endif;?>
  </section>

  <section class="admin-card">
    <header><div><h2>Dados preservados</h2><p>Estes caminhos não são substituídos pelo deploy nem pelo atualizador.</p></div></header>
    <div class="admin-table-scroll">
      <table class="admin-data-table">
        <thead><tr><th>Recurso</th><th>Persistência</th></tr></thead>
        <tbody>
          <tr><td><strong>Banco</strong></td><td><code>storage/database.sqlite</code></td></tr>
          <tr><td><strong>Uploads</strong></td><td><code>uploads/</code></td></tr>
          <tr><td><strong>Configuração</strong></td><td><code>config/local.php</code></td></tr>
          <tr><td><strong>Logs</strong></td><td><code>storage/logs/</code></td></tr>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?php admin_shell_end();
