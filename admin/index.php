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
    ?><section class="admin-empty"><h2>Crie o primeiro site</h2><p>Configure a identidade do site para começar a editar páginas, cursos, inscrições e mídia.</p><a class="admin-button" href="/admin/activities.php">Configurar site</a></section><?php
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
if(!$home&&$pages)$home=$pages[0];

$pendingPages=array_values(array_filter($pages,static fn(array $page): bool=>(int)($page['draft_revision']??0)!==(int)($page['published_revision']??0)||empty($page['published_document_json'])));
$pendingForms=array_values(array_filter($forms,static fn(array $form): bool=>(int)($form['draft_revision']??0)!==(int)($form['published_revision']??0)||empty($form['published_schema_json'])));
$pendingCount=count($pendingPages)+count($pendingForms);

$registrationStats=['total'=>0,'new_count'=>0,'latest'=>null];
$q=$db->prepare("SELECT COUNT(*) total,COALESCE(SUM(CASE WHEN s.status='new' THEN 1 ELSE 0 END),0) new_count,MAX(s.created_at) latest FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE f.activity_id=? AND f.purpose='enrollment' AND s.status!='archived'");
$q->execute([$id]);
$row=$q->fetch();
if(is_array($row))$registrationStats=$row;

$analytics=analytics_dashboard($db,$id,30);
$analyticsCounts=$analytics['counts'];
$analyticsRates=$analytics['rates'];

$mediaStats=['images'=>0,'videos'=>0,'latest'=>null];
$q=$db->query("SELECT COALESCE(SUM(CASE WHEN kind='image' THEN 1 ELSE 0 END),0) images,COALESCE(SUM(CASE WHEN kind='video' THEN 1 ELSE 0 END),0) videos,MAX(updated_at) latest FROM media_assets WHERE archived_at IS NULL");
$row=$q->fetch();
if(is_array($row))$mediaStats=$row;

$q=$db->prepare("SELECT MAX(draft_updated_at) draft_latest,MAX(published_at) published_latest FROM cms_pages WHERE activity_id=? AND status!='archived'");
$q->execute([$id]);
$pageDates=$q->fetch()?:[];
$homeUrl=$home?'/editor/?page='.(int)$home['id']:'/admin/pages.php?activity='.$id;
$formatDate=static function(?string $value): string {if(!$value)return 'Ainda não há registro';$time=strtotime($value);return $time?date('d/m/Y · H:i',$time):$value;};
$localeLabel=static fn(string $locale): string=>$locale===PUBLIC_LOCALE_PT_BR?'Português':'English';

$recent=[];
foreach([
    ['Página editada',$pageDates['draft_latest']??null],
    ['Página publicada',$pageDates['published_latest']??null],
    ['Inscrição recebida',$registrationStats['latest']??null],
    ['Mídia atualizada',$mediaStats['latest']??null],
] as [$label,$date])if(is_string($date)&&$date!=='')$recent[]=['label'=>$label,'date'=>$date];
usort($recent,static fn(array $a,array $b): int=>strtotime($b['date'])<=>strtotime($a['date']));
$recent=array_slice($recent,0,4);

admin_shell_start('overview','Visão geral',$state);
?>
<section class="admin-card">
  <header>
    <div><h2><?=h((string)$activity['public_title'])?></h2><p>Identidade pública do site atual. A navegação lateral organiza operação, ensino, conteúdo e sistema.</p></div>
    <div class="admin-inline-actions"><a class="admin-button" href="<?=h($homeUrl)?>">Editar página inicial</a><a class="admin-button secondary" href="/admin/activities.php?activity=<?=$id?>">Configurar site</a></div>
  </header>
</section>

<section class="admin-stat-grid" aria-label="Resumo operacional">
  <div class="admin-stat"><span>Publicação</span><strong><?=$pendingCount>0?h(admin_quantity_label($pendingCount,'pendência','pendências')):'Em dia'?></strong><span>Páginas e formulários</span></div>
  <div class="admin-stat"><span>Inscrições novas</span><strong><?=(int)$registrationStats['new_count']?></strong><span><?=h(admin_quantity_label((int)$registrationStats['total'],'inscrição ativa','inscrições ativas'))?></span></div>
  <div class="admin-stat"><span>Sessões · 30 dias</span><strong><?=number_format((int)$analyticsCounts['sessions'],0,',','.')?></strong><span><?=number_format((int)$analyticsCounts['views'],0,',','.')?> visualizações</span></div>
  <div class="admin-stat"><span>Conversão · 30 dias</span><strong><?=number_format((float)$analyticsRates['visitToSubmit'],1,',','.')?>%</strong><span>Acesso → resposta</span></div>
</section>

<div class="admin-section-stack">
  <section class="admin-card">
    <header><div><h2>Páginas</h2><p>Conteúdo principal do site e estado de publicação.</p></div><a class="admin-button secondary" href="/admin/pages.php?activity=<?=$id?>">Gerenciar páginas</a></header>
    <?php if(!$pages):?>
      <div class="admin-empty">Nenhuma página cadastrada.</div>
    <?php else:?>
      <div class="admin-table-scroll">
        <table class="admin-data-table">
          <thead><tr><th>Página</th><th>Idioma</th><th>Estado</th><th></th></tr></thead>
          <tbody>
          <?php foreach(array_slice($pages,0,6) as $page):$pending=(int)($page['draft_revision']??0)!==(int)($page['published_revision']??0)||empty($page['published_document_json']);?>
            <tr>
              <td><div class="admin-table-primary"><strong><?=h((string)$page['title'])?></strong><span><?=$page['is_home']?'Página inicial':'Página do site'?></span></div></td>
              <td><?=h($localeLabel((string)$page['locale']))?></td>
              <td><span class="admin-badge"><?=$pending?'Não publicado':'Publicado'?></span></td>
              <td class="actions"><a class="admin-button secondary admin-table-action" href="/editor/?page=<?=(int)$page['id']?>">Editar</a></td>
            </tr>
          <?php endforeach;?>
          </tbody>
        </table>
      </div>
    <?php endif;?>
  </section>

  <section class="admin-card">
    <header><div><h2>Atividade recente</h2><p>Últimos movimentos relevantes do site.</p></div><a class="admin-button secondary" href="/admin/analytics.php?activity=<?=$id?>">Abrir métricas</a></header>
    <?php if(!$recent):?>
      <div class="admin-empty">As alterações recentes aparecerão aqui.</div>
    <?php else:?>
      <div class="admin-table-scroll">
        <table class="admin-data-table">
          <thead><tr><th>Evento</th><th>Quando</th></tr></thead>
          <tbody><?php foreach($recent as $event):?><tr><td><strong><?=h($event['label'])?></strong></td><td><?=h($formatDate($event['date']))?></td></tr><?php endforeach;?></tbody>
        </table>
      </div>
    <?php endif;?>
  </section>
</div>
<?php admin_shell_end();
