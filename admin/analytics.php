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
    admin_shell_start('analytics','Métricas',$state);
    ?><section class="admin-empty"><h2>Nenhum site selecionado</h2><p>Crie ou selecione um site para começar a medir acessos e conversões.</p></section><?php
    admin_shell_end();exit;
}
$days=(int)($_GET['days']??30);if(!in_array($days,[7,30,90,365],true))$days=30;
$data=analytics_dashboard($db,(int)$activity['id'],$days);
$counts=$data['counts'];$rates=$data['rates'];
$formatDate=static function(?string $value): string {if(!$value)return '—';$time=strtotime($value);return $time?date('d/m/Y · H:i',$time):$value;};
$maxViews=1;foreach($data['daily'] as $day)$maxViews=max($maxViews,(int)($day['views']??0));
$deviceLabels=['desktop'=>'Desktop','mobile'=>'Celular','tablet'=>'Tablet'];

admin_shell_start('analytics','Métricas',$state);
?>
<style>
.analytics-header{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:22px}.analytics-header h2{margin:4px 0 6px;font-size:clamp(24px,3vw,38px)}.analytics-period{display:flex;gap:6px;flex-wrap:wrap}.analytics-period a{padding:8px 11px;border:1px solid var(--admin-line,#d9d9d4);text-decoration:none;color:inherit;background:var(--admin-surface,#fff)}.analytics-period a[aria-current="true"]{background:#171917;color:#fff;border-color:#171917}.analytics-note{margin:0 0 20px;padding:13px 15px;border:1px solid var(--admin-line,#d9d9d4);background:var(--admin-surface-soft,#f5f5f2);font-size:14px}.analytics-kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;margin-bottom:22px}.analytics-kpi{border:1px solid var(--admin-line,#d9d9d4);padding:15px;background:var(--admin-surface,#fff);min-height:105px}.analytics-kpi span{display:block;font-size:12px;text-transform:uppercase;letter-spacing:.07em;opacity:.65}.analytics-kpi strong{display:block;margin-top:9px;font-size:28px;font-weight:500}.analytics-kpi small{display:block;margin-top:6px;opacity:.68}.analytics-grid{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(280px,.8fr);gap:14px}.analytics-panel{border:1px solid var(--admin-line,#d9d9d4);background:var(--admin-surface,#fff);padding:18px;margin-bottom:14px}.analytics-panel header{display:flex;justify-content:space-between;gap:12px;align-items:baseline;margin-bottom:14px}.analytics-panel h3{margin:0}.analytics-chart{display:grid;grid-template-columns:repeat(auto-fit,minmax(16px,1fr));align-items:end;gap:4px;height:180px;padding-top:15px;border-bottom:1px solid var(--admin-line,#d9d9d4)}.analytics-bar{position:relative;height:100%;display:flex;align-items:flex-end}.analytics-bar i{display:block;width:100%;min-height:2px;background:currentColor;opacity:.76}.analytics-bar:hover::after{content:attr(data-label);position:absolute;z-index:2;bottom:calc(var(--bar-height) + 8px);left:50%;transform:translateX(-50%);white-space:nowrap;background:#171917;color:#fff;padding:5px 7px;font-size:11px}.analytics-table{width:100%;border-collapse:collapse}.analytics-table th,.analytics-table td{text-align:left;padding:9px 7px;border-top:1px solid var(--admin-line,#e3e3df);vertical-align:top}.analytics-table th{font-size:11px;text-transform:uppercase;letter-spacing:.06em;opacity:.62}.analytics-table td.num,.analytics-table th.num{text-align:right}.analytics-breakdown{display:grid;gap:8px}.analytics-breakdown-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;padding:8px 0;border-top:1px solid var(--admin-line,#e3e3df)}.analytics-empty{opacity:.65;padding:18px 0}.analytics-source small{display:block;opacity:.6;margin-top:2px}@media(max-width:1150px){.analytics-kpis{grid-template-columns:repeat(3,1fr)}}@media(max-width:800px){.analytics-header{align-items:flex-start;flex-direction:column}.analytics-kpis{grid-template-columns:repeat(2,1fr)}.analytics-grid{grid-template-columns:1fr}.analytics-chart{height:140px}}@media(max-width:520px){.analytics-kpis{grid-template-columns:1fr 1fr}.analytics-kpi strong{font-size:23px}}
</style>
<section class="analytics-header">
  <div><p class="admin-kicker">Desempenho</p><h2><?=h((string)$activity['public_title'])?></h2><p>Acessos, origem do tráfego e passagem do site para uma resposta enviada.</p></div>
  <nav class="analytics-period" aria-label="Período">
    <?php foreach([7=>'7 dias',30=>'30 dias',90=>'90 dias',365=>'1 ano'] as $value=>$label):?><a href="?activity=<?=(int)$activity['id']?>&days=<?=$value?>"<?=$days===$value?' aria-current="true"':''?>><?=$label?></a><?php endforeach;?>
  </nav>
</section>
<?php if(!$data['collectionStart']):?>
  <p class="analytics-note"><strong>A coleta ainda não começou.</strong> Os primeiros dados aparecerão depois que esta versão for instalada e o site receber novos acessos. O sistema não inventa nem reconstrói acessos anteriores a partir das inscrições existentes.</p>
<?php else:?>
  <p class="analytics-note">Coleta própria iniciada em <strong><?=h($formatDate($data['collectionStart']))?></strong>. Uma sessão representa uma sessão de navegação, não uma pessoa identificada. Não são armazenados IP bruto nem dados pessoais neste painel.</p>
<?php endif;?>
<section class="analytics-kpis" aria-label="Indicadores principais">
  <article class="analytics-kpi"><span>Sessões</span><strong><?=number_format((int)$counts['sessions'],0,',','.')?></strong><small>Acessos distintos no período</small></article>
  <article class="analytics-kpi"><span>Visualizações</span><strong><?=number_format((int)$counts['views'],0,',','.')?></strong><small>Páginas carregadas</small></article>
  <article class="analytics-kpi"><span>Respostas</span><strong><?=number_format((int)$counts['submissions'],0,',','.')?></strong><small>Formulários enviados</small></article>
  <article class="analytics-kpi"><span>Acesso → resposta</span><strong><?=number_format((float)$rates['visitToSubmit'],1,',','.')?>%</strong><small>Respostas ÷ sessões</small></article>
  <article class="analytics-kpi"><span>Convertidos</span><strong><?=number_format((int)$counts['converted'],0,',','.')?></strong><small>Respostas marcadas como convertidas</small></article>
  <article class="analytics-kpi"><span>Resposta → convertido</span><strong><?=number_format((float)$rates['submitToConverted'],1,',','.')?>%</strong><small>Conversão comercial</small></article>
</section>
<div class="analytics-grid">
  <div>
    <section class="analytics-panel">
      <header><h3>Acessos por dia</h3><span><?=h($days===365?'Último ano':'Últimos '.$days.' dias')?></span></header>
      <?php if(!$data['daily']):?><p class="analytics-empty">Ainda não há acessos registrados neste período.</p><?php else:?><div class="analytics-chart" aria-label="Visualizações de página por dia"><?php foreach($data['daily'] as $day):$views=(int)$day['views'];$height=max(2,round(($views/$maxViews)*100,1));$date=strtotime((string)$day['day']);$label=($date?date('d/m',$date):(string)$day['day']).' · '.$views.' visualizações · '.(int)$day['sessions'].' sessões';?><span class="analytics-bar" data-label="<?=h($label)?>" style="--bar-height:<?=$height?>%"><i style="height:<?=$height?>%"></i></span><?php endforeach;?></div><?php endif;?>
    </section>
    <section class="analytics-panel">
      <header><h3>Páginas</h3><span>Onde a conversão acontece</span></header>
      <?php if(!$data['pages']):?><p class="analytics-empty">Nenhuma página medida ainda.</p><?php else:?><div style="overflow:auto"><table class="analytics-table"><thead><tr><th>Página</th><th class="num">Visualizações</th><th class="num">Sessões</th><th class="num">Respostas</th><th class="num">Conv.</th></tr></thead><tbody><?php foreach($data['pages'] as $row):$sessions=(int)$row['sessions'];$submissions=(int)$row['submissions'];?><tr><td><?=h((string)$row['title'])?></td><td class="num"><?=number_format((int)$row['views'],0,',','.')?></td><td class="num"><?=number_format($sessions,0,',','.')?></td><td class="num"><?=number_format($submissions,0,',','.')?></td><td class="num"><?=number_format(analytics_percent($submissions,$sessions),1,',','.')?>%</td></tr><?php endforeach;?></tbody></table></div><?php endif;?>
    </section>
    <section class="analytics-panel">
      <header><h3>Origem dos acessos</h3><span>Atribuição ao primeiro acesso da sessão</span></header>
      <?php if(!$data['sources']):?><p class="analytics-empty">As origens aparecerão quando houver acessos.</p><?php else:?><div style="overflow:auto"><table class="analytics-table"><thead><tr><th>Origem</th><th>Campanha</th><th class="num">Sessões</th><th class="num">Respostas</th><th class="num">Conv.</th></tr></thead><tbody><?php foreach($data['sources'] as $row):$sessions=(int)$row['sessions'];$submissions=(int)$row['submissions'];?><tr><td class="analytics-source"><strong><?=h((string)$row['source'])?></strong><?php if((string)$row['medium']!==''):?><small><?=h((string)$row['medium'])?></small><?php endif;?></td><td><?=h((string)$row['campaign']?:'—')?></td><td class="num"><?=number_format($sessions,0,',','.')?></td><td class="num"><?=number_format($submissions,0,',','.')?></td><td class="num"><?=number_format(analytics_percent($submissions,$sessions),1,',','.')?>%</td></tr><?php endforeach;?></tbody></table></div><?php endif;?>
    </section>
  </div>
  <aside>
    <section class="analytics-panel"><header><h3>Dispositivos</h3></header><div class="analytics-breakdown"><?php if(!$data['devices']):?><p class="analytics-empty">Sem dados.</p><?php endif;?><?php foreach($data['devices'] as $row):?><div class="analytics-breakdown-row"><span><?=h($deviceLabels[(string)$row['device']]??ucfirst((string)$row['device']))?></span><strong><?=number_format((int)$row['sessions'],0,',','.')?></strong></div><?php endforeach;?></div></section>
    <section class="analytics-panel"><header><h3>Idiomas</h3></header><div class="analytics-breakdown"><?php if(!$data['locales']):?><p class="analytics-empty">Sem dados.</p><?php endif;?><?php foreach($data['locales'] as $row):?><div class="analytics-breakdown-row"><span><?=h((string)$row['locale']==='pt-BR'?'Português':((string)$row['locale']==='en'?'English':(string)$row['locale']))?></span><strong><?=number_format((int)$row['sessions'],0,',','.')?></strong></div><?php endforeach;?></div></section>
    <section class="analytics-panel"><header><h3>Como ler a conversão</h3></header><p><strong>Acesso → resposta</strong> mede quantas sessões geraram um formulário enviado.</p><p><strong>Resposta → convertido</strong> usa o estado “Convertido” das respostas no admin. Assim o painel separa desempenho do site de fechamento comercial.</p><p><strong>Origem</strong> usa UTM quando existe; sem UTM, usa o domínio de referência. A conversão por origem é atribuída ao primeiro acesso da sessão.</p></section>
  </aside>
</div>
<?php admin_shell_end();
