<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers(); require_admin();
$db=database(); $state=admin_activity_resolution($db); $activity=$state['activity'];
if(!$activity){
    admin_shell_start('overview','Visão geral',$state); ?>
    <section class="admin-empty"><p class="admin-kicker">Nenhuma atividade configurada</p><h2>Comece pela primeira atividade</h2><p>Ainda não há uma página para administrar. Use a criação de atividade para iniciar uma configuração segura.</p><a class="admin-button" href="/admin/activities.php">Configurar atividade</a></section>
    <?php admin_shell_end(); exit;
}
$id=(int)$activity['id'];
$doc=content_current($db,$id);
$q=$db->prepare('SELECT COUNT(*) FROM interest_submissions WHERE activity_id=?');$q->execute([$id]);$responseCount=(int)$q->fetchColumn();
$q=$db->prepare('SELECT created_at FROM interest_submissions WHERE activity_id=? ORDER BY created_at DESC LIMIT 1');$q->execute([$id]);$latestResponse=$q->fetchColumn()?:null;
$assets=$db->query("SELECT kind,COUNT(*) total,MAX(updated_at) latest FROM media_assets WHERE archived_at IS NULL GROUP BY kind")->fetchAll();$media=['image'=>0,'video'=>0];$latestMedia=null;foreach($assets as $asset){$media[$asset['kind']]=(int)$asset['total'];if($asset['latest']&&(!$latestMedia||strtotime($asset['latest'])>strtotime($latestMedia)))$latestMedia=$asset['latest'];}$mediaStorageReady=media_storage_available();$uploadLimit=media_effective_upload_limit();$systemOperational=$mediaStorageReady&&$uploadLimit>0;
$draft=(int)$doc['draft_revision'];$published=$doc['published_revision']===null?null:(int)$doc['published_revision'];$unpublished=$published===null||$draft!==$published;
$editorial=$published===null?'Ainda não publicada':($unpublished?'Alterações não publicadas':'Publicada e atualizada');
function admin_date(?string $date): string { return $date?date('d/m/Y · H:i',strtotime($date)):'Ainda não disponível'; }
admin_shell_start('overview','Visão geral',$state);
?>
<section class="overview-hero" aria-labelledby="activity-title"><div><p class="admin-kicker">Página administrada</p><h2 id="activity-title"><?= h($activity['admin_name']) ?></h2><p class="public-url"><span>URL pública</span><a href="/<?= h($activity['slug']) ?>/" target="_blank" rel="noopener">/<?= h($activity['slug']) ?>/</a></p></div><div class="editorial-state"><strong><?= h($editorial) ?></strong><span><?= $unpublished?'O rascunho ainda não corresponde à versão pública.':'A versão pública corresponde ao último rascunho salvo.' ?></span></div><div class="hero-actions"><a class="admin-button" href="/editor/?activity=<?= $id ?>"><?= $unpublished?'Continuar edição':'Editar página' ?></a><a class="admin-button secondary" href="/<?= h($activity['slug']) ?>/" target="_blank" rel="noopener">Abrir página pública</a></div></section>
<section class="overview-grid" aria-label="Resumo operacional">
 <article class="overview-card"><p class="admin-kicker">Página</p><h2>Revisões e publicação</h2><dl><dt>Revisão publicada</dt><dd><?= $published===null?'Nenhuma':$published ?></dd><dt>Última publicação</dt><dd><?= h(admin_date($doc['published_at'])) ?></dd><dt>Último salvamento</dt><dd><?= h(admin_date($doc['draft_updated_at'])) ?></dd></dl><a href="/editor/?activity=<?= $id ?>"><?= $unpublished?'Continuar edição':'Abrir editor' ?></a></article>
 <article class="overview-card"><p class="admin-kicker">Respostas</p><h2><?= $responseCount ?> <?= $responseCount===1?'resposta':'respostas' ?></h2><p><?= $latestResponse?'Mais recente: '.h(admin_date($latestResponse)): 'Nenhuma resposta ainda. Quando alguém enviar o formulário, ela aparecerá aqui.' ?></p><a href="/admin/interests.php?activity=<?= $id ?>">Ver respostas</a></article>
 <article class="overview-card"><p class="admin-kicker">Mídia</p><h2><?= h(admin_media_summary($media['image'],$media['video'])) ?></h2><p><?= $latestMedia?'Arquivo mais recente: '.h(admin_date($latestMedia)):'A biblioteca ainda não possui arquivos.' ?></p><a href="/admin/media.php?activity=<?= $id ?>">Abrir biblioteca</a></article>
 <article class="overview-card"><p class="admin-kicker">Sistema</p><h2><?= $systemOperational?'Sistema operacional':'Atenção necessária' ?></h2><?php if($systemOperational): ?><p>Uploads disponíveis até <?= h((string)round($uploadLimit/1024/1024)) ?> MB.</p><p>Armazenamento e mídia estão disponíveis.</p><?php else: ?><p><?= !$mediaStorageReady?'O armazenamento de mídia não está disponível para novos uploads.':'O limite efetivo de upload não é válido.' ?></p><?php endif; ?></article>
</section>
<section class="recent-activity" aria-labelledby="recent-title"><div><p class="admin-kicker">Atividade recente</p><h2 id="recent-title">O que mudou por último</h2></div><ol><?php $events=[];if($doc['draft_updated_at'])$events[]=['Rascunho salvo',admin_date($doc['draft_updated_at'])];if($doc['published_at'])$events[]=['Página publicada',admin_date($doc['published_at'])];if($latestResponse)$events[]=['Resposta recebida',admin_date($latestResponse)];if($latestMedia)$events[]=['Mídia atualizada',admin_date($latestMedia)];foreach(array_slice($events,0,4) as [$label,$date]): ?><li><strong><?= h($label) ?></strong><span><?= h($date) ?></span></li><?php endforeach; if(!$events): ?><li><strong>Ainda não há atividade registrada</strong><span>Os próximos salvamentos, publicações e respostas aparecerão aqui.</span></li><?php endif; ?></ol></section>
<?php admin_shell_end();
