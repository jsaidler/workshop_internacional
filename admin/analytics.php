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
$periodLabel=$days===365?'último ano':'últimos '.$days.' dias';

admin_shell_start('analytics','Métricas',$state);
?>
<style>
.analytics-shell{display:grid;gap:18px}.analytics-header{display:flex;align-items:flex-end;justify-content:space-between;gap:24px}.analytics-header-copy{max-width:760px}.analytics-header h2{margin:4px 0 6px;font-size:clamp(28px,3.2vw,42px);letter-spacing:-.035em}.analytics-header p:last-child{margin:0;color:var(--admin-muted);max-width:62ch}.analytics-period{display:inline-flex;gap:3px;padding:3px;border:1px solid var(--admin-line);border-radius:6px;background:#fff}.analytics-period a{padding:7px 10px;border-radius:4px;text-decoration:none;color:var(--admin-muted);font-size:12px;font-weight:600}.analytics-period a[aria-current="true"]{background:var(--admin-ink);color:#fff}.analytics-note{margin:0;padding:12px 14px;border:1px solid var(--admin-line);border-radius:5px;background:#fff;font-size:13px;color:var(--admin-muted)}.analytics-note strong{color:var(--admin-ink)}.analytics-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.analytics-summary article{padding:16px;border:1px solid var(--admin-line);border-radius:5px;background:#fff;min-height:112px}.analytics-summary span{display:block;color:var(--admin-muted);font-size:11px}.analytics-summary strong{display:block;margin-top:8px;font-size:32px;line-height:1;font-weight:500;letter-spacing:-.035em}.analytics-summary small{display:block;margin-top:8px;color:var(--admin-muted);font-size:11px}.analytics-funnel-panel,.analytics-panel{border:1px solid var(--admin-line);border-radius:5px;background:#fff;overflow:hidden}.analytics-panel>header,.analytics-funnel-panel>header{display:flex;align-items:baseline;justify-content:space-between;gap:14px;padding:14px 16px;border-bottom:1px solid var(--admin-line)}.analytics-panel h3,.analytics-funnel-panel h3{margin:0;font-size:14px}.analytics-panel>header span,.analytics-funnel-panel>header span{color:var(--admin-muted);font-size:11px}.analytics-funnel{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));padding:0}.analytics-stage{position:relative;padding:20px 18px 18px;border-right:1px solid var(--admin-line)}.analytics-stage:last-child{border-right:0}.analytics-stage .stage-label{display:block;color:var(--admin-muted);font-size:11px}.analytics-stage strong{display:block;margin:6px 0 8px;font-size:30px;font-weight:500;letter-spacing:-.035em}.analytics-stage p{margin:0;color:var(--admin-muted);font-size:12px}.analytics-stage:not(:last-child)::after{content:"→";position:absolute;right:-11px;top:50%;transform:translateY(-50%);z-index:2;width:22px;height:22px;display:grid;place-items:center;border:1px solid var(--admin-line);border-radius:50%;background:var(--admin-bg);font-size:11px;color:var(--admin-muted)}.analytics-grid{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(270px,.72fr);gap:14px}.analytics-panel-body{padding:16px}.analytics-chart{display:grid;grid-template-columns:repeat(auto-fit,minmax(10px,1fr));align-items:end;gap:4px;height:190px;padding:10px 0 0;border-bottom:1px solid var(--admin-line);background:linear-gradient(to bottom,transparent 24%,#f0f0ec 25%,transparent 26%,transparent 49%,#f0f0ec 50%,transparent 51%,transparent 74%,#f0f0ec 75%,transparent 76%)}.analytics-bar{position:relative;height:100%;display:flex;align-items:flex-end;min-width:0}.analytics-bar i{display:block;width:100%;min-height:2px;background:var(--admin-ink);opacity:.78;border-radius:2px 2px 0 0}.analytics-bar:hover i{opacity:1}.analytics-bar:hover::after{content:attr(data-label);position:absolute;z-index:4;bottom:calc(var(--bar-height) + 8px);left:50%;transform:translateX(-50%);white-space:nowrap;background:var(--admin-ink);color:#fff;padding:5px 7px;border-radius:3px;font-size:10px}.analytics-table-wrap{overflow:auto}.analytics-table{width:100%;border-collapse:collapse}.analytics-table th,.analytics-table td{text-align:left;padding:10px 12px;border-top:1px solid #ecece8;vertical-align:top}.analytics-table thead th{border-top:0;background:#fafaf7;color:var(--admin-muted);font-size:10px;text-transform:uppercase;letter-spacing:.055em}.analytics-table td{font-size:12px}.analytics-table td:first-child{font-weight:600}.analytics-table td.num,.analytics-table th.num{text-align:right;white-space:nowrap}.analytics-source small{display:block;margin-top:2px;color:var(--admin-muted);font-weight:400}.analytics-breakdown{padding:6px 16px 10px}.analytics-breakdown-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;padding:10px 0;border-top:1px solid #ecece8}.analytics-breakdown-row:first-child{border-top:0}.analytics-breakdown-row span{font-size:12px}.analytics-breakdown-row strong{font-size:13px}.analytics-reading{padding:16px;color:var(--admin-muted);font-size:12px}.analytics-reading p{margin:0 0 12px}.analytics-reading p:last-child{margin-bottom:0}.analytics-reading strong{color:var(--admin-ink)}.analytics-empty{margin:0;padding:22px 16px;color:var(--admin-muted);font-size:12px}@media(max-width:1050px){.analytics-summary{grid-template-columns:repeat(2,1fr)}.analytics-grid{grid-template-columns:1fr}}@media(max-width:760px){.analytics-header{align-items:flex-start;flex-direction:column}.analytics-period{width:100%;overflow-x:auto}.analytics-period a{flex:1;text-align:center;white-space:nowrap}.analytics-funnel{grid-template-columns:1fr}.analytics-stage{border-right:0;border-bottom:1px solid var(--admin-line)}.analytics-stage:last-child{border-bottom:0}.analytics-stage:not(:last-child)::after{content:"↓";right:auto;left:50%;top:auto;bottom:-11px;transform:translateX(-50%)}.analytics-chart{height:150px}}@media(max-width:500px){.analytics-summary{grid-template-columns:1fr 1fr}.analytics-summary strong{font-size:26px}.analytics-summary article{min-height:100px;padding:13px}}
</style>
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
