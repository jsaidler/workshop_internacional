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
    admin_shell_start('analytics','Métricas',$state,['/assets/admin-analytics.css']);
    ?><section class="admin-empty"><h2>Nenhum site selecionado</h2><p>Crie ou selecione um site para começar a medir acessos e conversões.</p></section><?php
    admin_shell_end();exit;
}
$days=(int)($_GET['days']??30);if(!in_array($days,[7,30,90,365],true))$days=30;
$data=analytics_dashboard($db,(int)$activity['id'],$days);
$counts=$data['counts'];$rates=$data['rates'];
$formatDate=static function(?string $value): string {if(!$value)return '—';$time=strtotime($value);return $time?date('d/m/Y · H:i',$time):$value;};
$maxViews=1;foreach($data['daily'] as $day)$maxViews=max($maxViews,(int)($day['views']??0));
$deviceLabels=['desktop'=>'Desktop','mobile'=>'Celular','tablet'=>'Tablet'];
$periodLabel=$days===365?'último ano':'últimos '.$days.' dias';

admin_shell_start('analytics','Métricas',$state,['/assets/admin-analytics.css']);
?>
<div class="analytics-shell">
  <section class="analytics-header">
    <div class="analytics-header-copy"><p class="admin-kicker">Desempenho</p><h2><?=h((string)$activity['public_title'])?></h2><p>O painel separa desempenho do site de fechamento comercial: primeiro mede quantas sessões viram respostas; depois, quantas respostas foram marcadas como convertidas.</p></div>
    <nav class="analytics-period" aria-label="Período">
      <?php foreach([7=>'7 dias',30=>'30 dias',90=>'90 dias',365=>'1 ano'] as $value=>$label):?><a href="?activity=<?=(int)$activity['id']?>&days=<?=$value?>"<?=$days===$value?' aria-current="true"':''?>><?=$label?></a><?php endforeach;?>
    </nav>
  </section>
  <?php if(!$data['collectionStart']):?>
    <p class="analytics-note"><strong>A coleta ainda não começou.</strong> Os primeiros dados aparecerão depois que a versão com métricas estiver instalada e o site receber novos acessos. O sistema não inventa nem reconstrói acessos anteriores a partir das inscrições existentes.</p>
  <?php else:?>
    <p class="analytics-note">Coleta própria iniciada em <strong><?=h($formatDate($data['collectionStart']))?></strong>. Uma sessão representa uma sessão de navegação, não uma pessoa identificada. O painel não armazena IP bruto nem os dados pessoais enviados no formulário.</p>
  <?php endif;?>
  <section class="analytics-summary" aria-label="Indicadores principais">
    <article><span>Sessões</span><strong><?=number_format((int)$counts['sessions'],0,',','.')?></strong><small>Acessos distintos em <?=$periodLabel?></small></article>
    <article><span>Visualizações</span><strong><?=number_format((int)$counts['views'],0,',','.')?></strong><small>Páginas carregadas</small></article>
    <article><span>Respostas</span><strong><?=number_format((int)$counts['submissions'],0,',','.')?></strong><small><?=number_format((float)$rates['visitToSubmit'],1,',','.')?>% das sessões</small></article>
    <article><span>Convertidos</span><strong><?=number_format((int)$counts['converted'],0,',','.')?></strong><small><?=number_format((float)$rates['submitToConverted'],1,',','.')?>% das respostas</small></article>
  </section>
  <section class="analytics-funnel-panel">
    <header><h3>Funil principal</h3><span>sessão → resposta → convertido</span></header>
    <div class="analytics-funnel">
      <article class="analytics-stage"><span class="stage-label">1 · Entraram no site</span><strong><?=number_format((int)$counts['sessions'],0,',','.')?></strong><p>Sessões registradas no período.</p></article>
      <article class="analytics-stage"><span class="stage-label">2 · Enviaram uma resposta</span><strong><?=number_format((int)$counts['submissions'],0,',','.')?></strong><p><?=number_format((float)$rates['visitToSubmit'],1,',','.')?>% das sessões chegaram ao envio.</p></article>
      <article class="analytics-stage"><span class="stage-label">3 · Foram convertidos</span><strong><?=number_format((int)$counts['converted'],0,',','.')?></strong><p><?=number_format((float)$rates['submitToConverted'],1,',','.')?>% das respostas foram marcadas como convertidas.</p></article>
    </div>
  </section>
  <div class="analytics-grid">
    <div>
      <section class="analytics-panel">
        <header><h3>Acessos por dia</h3><span><?=h(ucfirst($periodLabel))?></span></header>
        <?php if(!$data['daily']):?><p class="analytics-empty">Ainda não há acessos registrados neste período.</p><?php else:?><div class="analytics-panel-body"><div class="analytics-chart" aria-label="Visualizações de página por dia"><?php foreach($data['daily'] as $day):$views=(int)$day['views'];$height=max(2,round(($views/$maxViews)*100,1));$date=strtotime((string)$day['day']);$label=($date?date('d/m',$date):(string)$day['day']).' · '.$views.' visualizações · '.(int)$day['sessions'].' sessões';?><span class="analytics-bar" data-label="<?=h($label)?>" style="--bar-height:<?=$height?>%"><i style="height:<?=$height?>%"></i></span><?php endforeach;?></div></div><?php endif;?>
      </section>
      <section class="analytics-panel">
        <header><h3>Páginas</h3><span>Onde as respostas acontecem</span></header>
        <?php if(!$data['pages']):?><p class="analytics-empty">Nenhuma página medida ainda.</p><?php else:?><div class="analytics-table-wrap"><table class="analytics-table"><thead><tr><th>Página</th><th class="num">Visualizações</th><th class="num">Sessões</th><th class="num">Respostas</th><th class="num">Acesso → resposta</th></tr></thead><tbody><?php foreach($data['pages'] as $row):$sessions=(int)$row['sessions'];$submissions=(int)$row['submissions'];?><tr><td><?=h((string)$row['title'])?></td><td class="num"><?=number_format((int)$row['views'],0,',','.')?></td><td class="num"><?=number_format($sessions,0,',','.')?></td><td class="num"><?=number_format($submissions,0,',','.')?></td><td class="num"><?=number_format(analytics_percent($submissions,$sessions),1,',','.')?>%</td></tr><?php endforeach;?></tbody></table></div><?php endif;?>
      </section>
      <section class="analytics-panel">
        <header><h3>Origem dos acessos</h3><span>Primeiro acesso da sessão</span></header>
        <?php if(!$data['sources']):?><p class="analytics-empty">As origens aparecerão quando houver acessos.</p><?php else:?><div class="analytics-table-wrap"><table class="analytics-table"><thead><tr><th>Origem</th><th>Campanha</th><th class="num">Sessões</th><th class="num">Respostas</th><th class="num">Acesso → resposta</th></tr></thead><tbody><?php foreach($data['sources'] as $row):$sessions=(int)$row['sessions'];$submissions=(int)$row['submissions'];?><tr><td class="analytics-source"><strong><?=h((string)$row['source'])?></strong><?php if((string)$row['medium']!==''):?><small><?=h((string)$row['medium'])?></small><?php endif;?></td><td><?=h((string)$row['campaign']?:'—')?></td><td class="num"><?=number_format($sessions,0,',','.')?></td><td class="num"><?=number_format($submissions,0,',','.')?></td><td class="num"><?=number_format(analytics_percent($submissions,$sessions),1,',','.')?>%</td></tr><?php endforeach;?></tbody></table></div><?php endif;?>
      </section>
    </div>
    <aside>
      <section class="analytics-panel"><header><h3>Dispositivos</h3><span>Sessões</span></header><div class="analytics-breakdown"><?php if(!$data['devices']):?><p class="analytics-empty">Sem dados.</p><?php endif;?><?php foreach($data['devices'] as $row):?><div class="analytics-breakdown-row"><span><?=h($deviceLabels[(string)$row['device']]??ucfirst((string)$row['device']))?></span><strong><?=number_format((int)$row['sessions'],0,',','.')?></strong></div><?php endforeach;?></div></section>
      <section class="analytics-panel"><header><h3>Idiomas</h3><span>Sessões</span></header><div class="analytics-breakdown"><?php if(!$data['locales']):?><p class="analytics-empty">Sem dados.</p><?php endif;?><?php foreach($data['locales'] as $row):?><div class="analytics-breakdown-row"><span><?=h((string)$row['locale']==='pt-BR'?'Português':((string)$row['locale']==='en'?'English':(string)$row['locale']))?></span><strong><?=number_format((int)$row['sessions'],0,',','.')?></strong></div><?php endforeach;?></div></section>
      <section class="analytics-panel"><header><h3>Como ler estes números</h3></header><div class="analytics-reading"><p><strong>Acesso → resposta</strong> mede a eficiência do site em transformar uma sessão em formulário enviado.</p><p><strong>Resposta → convertido</strong> usa o estado “Convertido” que você marca nas inscrições. Assim uma página pode converter bem e, ainda assim, trazer contatos de baixa qualidade — ou o contrário.</p><p>Percentuais com poucos acessos variam muito. Use tendência e comparação entre períodos antes de alterar uma página por causa de um único dia.</p></div></section>
    </aside>
  </div>
</div>
<?php admin_shell_end();
