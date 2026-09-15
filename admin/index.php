<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();
require_admin();

$db=database();
$state=admin_activity_resolution($db);
$activity=$state['activity'];

if(!$activity){
    admin_shell_start('overview','Visão geral',$state);
    ?><section class="admin-empty"><h2>Crie o primeiro site</h2><p>Configure um site para começar a editar páginas, inscrições e mídia.</p><a class="admin-button" href="/admin/activities.php">Configurar site</a></section><?php
    admin_shell_end();
    exit;
}

$id=(int)$activity['id'];
cms_pages_seed($db,$id);
cms_forms_seed($db,$id);
$pages=cms_pages($db,$id);
$forms=cms_forms($db,$id);

$home=null;
foreach($pages as $page){
    if(($page['locale']??'')===PUBLIC_LOCALE_PT_BR && (int)($page['is_home']??0)===1){$home=$page;break;}
}
if(!$home){foreach($pages as $page){if((int)($page['is_home']??0)===1){$home=$page;break;}}}
if(!$home && $pages)$home=$pages[0];

$pendingPages=array_values(array_filter($pages,static fn(array $page): bool=>(int)($page['draft_revision']??0)!==(int)($page['published_revision']??0)||empty($page['published_document_json'])));
$pendingForms=array_values(array_filter($forms,static fn(array $form): bool=>(int)($form['draft_revision']??0)!==(int)($form['published_revision']??0)||empty($form['published_schema_json'])));
$pendingCount=count($pendingPages)+count($pendingForms);

$submissionStats=['total'=>0,'new_count'=>0,'converted_count'=>0,'latest'=>null];
$q=$db->prepare("SELECT COUNT(*) total,COALESCE(SUM(CASE WHEN status='new' THEN 1 ELSE 0 END),0) new_count,COALESCE(SUM(CASE WHEN status='converted' THEN 1 ELSE 0 END),0) converted_count,MAX(created_at) latest FROM cms_form_submissions WHERE activity_id=? AND status!='archived'");
$q->execute([$id]);$row=$q->fetch();if(is_array($row))$submissionStats=$row;
$analytics=analytics_dashboard($db,$id,30);
$analyticsCounts=$analytics['counts'];$analyticsRates=$analytics['rates'];

$mediaStats=['images'=>0,'videos'=>0,'latest'=>null];
$q=$db->query("SELECT COALESCE(SUM(CASE WHEN kind='image' THEN 1 ELSE 0 END),0) images,COALESCE(SUM(CASE WHEN kind='video' THEN 1 ELSE 0 END),0) videos,MAX(updated_at) latest FROM media_assets WHERE archived_at IS NULL");
$row=$q->fetch();if(is_array($row))$mediaStats=$row;

$q=$db->prepare("SELECT MAX(draft_updated_at) draft_latest,MAX(published_at) published_latest FROM cms_pages WHERE activity_id=? AND status!='archived'");$q->execute([$id]);$pageDates=$q->fetch()?:[];
$publicUrl=admin_public_activity_url($activity);
$homeUrl=$home?'/editor/?page='.(int)$home['id']:'/admin/pages.php?activity='.$id;
$formatDate=static function(?string $value): string {if(!$value)return 'Ainda não há registro';$time=strtotime($value);return $time?date('d/m/Y · H:i',$time):$value;};
$localeLabel=static fn(string $locale): string=>$locale===PUBLIC_LOCALE_PT_BR?'Português':'English';

$recent=[];
foreach([
    ['Página editada',$pageDates['draft_latest']??null],
    ['Página publicada',$pageDates['published_latest']??null],
    ['Inscrição recebida',$submissionStats['latest']??null],
    ['Mídia atualizada',$mediaStats['latest']??null],
] as [$label,$date])if(is_string($date)&&$date!=='')$recent[]=['label'=>$label,'date'=>$date];
usort($recent,static fn(array $a,array $b): int=>strtotime($b['date'])<=>strtotime($a['date']));
$recent=array_slice($recent,0,4);

admin_shell_start('overview','Visão geral',$state);
?>
<section class="overview-hero">
  <div><p class="admin-kicker">Painel</p><h2><?=h((string)$activity['public_title'])?></h2><p>O que precisa de atenção, como o site está convertendo e os atalhos para trabalhar no conteúdo.</p></div>
  <div class="hero-actions"><a class="admin-button" href="<?=h($homeUrl)?>">Editar página inicial</a><a class="admin-button secondary" href="<?=h($publicUrl)?>" target="_blank" rel="noopener">Abrir site ↗</a></div>
</section>
<section class="admin-dashboard-status" aria-label="Resumo do site">
  <article class="admin-status-card" data-tone="<?=$pendingCount>0?'attention':'good'?>"><span>Publicação</span><strong><?=$pendingCount>0?h(admin_quantity_label($pendingCount,'alteração pendente','alterações pendentes')):'Tudo publicado'?></strong></article>
  <article class="admin-status-card" data-tone="<?=(int)$submissionStats['new_count']>0?'attention':'good'?>"><span>Inscrições</span><strong><?=h(admin_quantity_label((int)$submissionStats['new_count'],'nova resposta','novas respostas'))?></strong></article>
  <article class="admin-status-card"><span>Conversão · 30 dias</span><strong><?=number_format((float)$analyticsRates['visitToSubmit'],1,',','.')?>% acesso → resposta</strong></article>
</section>
<section class="admin-dashboard-grid">
  <div class="admin-panel-plain">
    <header><h3>Páginas</h3><a href="/admin/pages.php?activity=<?=$id?>">Gerenciar todas</a></header>
    <div class="admin-row-list">
      <?php foreach(array_slice($pages,0,6) as $page):$pending=(int)($page['draft_revision']??0)!==(int)($page['published_revision']??0)||empty($page['published_document_json']);?>
        <a href="/editor/?page=<?=(int)$page['id']?>"><div><strong><?=h((string)$page['title'])?></strong><span><?=h($localeLabel((string)$page['locale']))?><?=$page['is_home']?' · página inicial':''?></span></div><span class="admin-badge"><?=$pending?'Não publicado':'Publicado'?></span></a>
      <?php endforeach;?>
    </div>
  </div>
  <div class="admin-panel-plain">
    <header><h3>Acesso rápido</h3></header>
    <div class="admin-quick-grid">
      <a href="<?=h($homeUrl)?>"><strong>Editar site</strong><span>Abrir diretamente a página inicial no editor.</span></a>
      <a href="/admin/submissions.php?activity=<?=$id?>"><strong>Inscrições</strong><span><?=h(admin_quantity_label((int)$submissionStats['total'],'resposta recebida','respostas recebidas'))?>.</span></a>
      <a href="/admin/analytics.php?activity=<?=$id?>"><strong>Métricas</strong><span><?=number_format((int)$analyticsCounts['sessions'],0,',','.')?> sessões nos últimos 30 dias.</span></a>
      <a href="/admin/media.php?activity=<?=$id?>"><strong>Mídia</strong><span><?=h(admin_media_summary((int)$mediaStats['images'],(int)$mediaStats['videos']))?>.</span></a>
    </div>
  </div>
  <div class="admin-panel-plain">
    <header><h3>Desempenho · 30 dias</h3><a href="/admin/analytics.php?activity=<?=$id?>">Abrir métricas</a></header>
    <div class="admin-row-list">
      <div class="admin-row-item"><div><strong><?=number_format((int)$analyticsCounts['sessions'],0,',','.')?> sessões</strong><span><?=number_format((int)$analyticsCounts['views'],0,',','.')?> visualizações de página</span></div></div>
      <div class="admin-row-item"><div><strong><?=number_format((int)$analyticsCounts['submissions'],0,',','.')?> respostas</strong><span><?=number_format((float)$analyticsRates['visitToSubmit'],1,',','.')?>% das sessões chegaram ao envio</span></div></div>
      <div class="admin-row-item"><div><strong><?=number_format((int)$analyticsCounts['converted'],0,',','.')?> convertidas</strong><span><?=number_format((float)$analyticsRates['submitToConverted'],1,',','.')?>% das respostas marcadas como convertidas</span></div></div>
    </div>
  </div>
  <div class="admin-panel-plain">
    <header><h3>Atividade recente</h3></header>
    <div class="admin-row-list">
      <?php if(!$recent):?><div class="admin-row-item"><div><strong>Nenhuma atividade registrada</strong><span>As alterações recentes aparecerão aqui.</span></div></div><?php endif;?>
      <?php foreach($recent as $event):?><div class="admin-row-item"><div><strong><?=h($event['label'])?></strong><span><?=h($formatDate($event['date']))?></span></div></div><?php endforeach;?>
    </div>
  </div>
</section>
<?php admin_shell_end();
