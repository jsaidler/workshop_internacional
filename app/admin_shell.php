<?php
declare(strict_types=1);

function admin_shell_url(string $path, ?array $activity): string {return $path.($activity?'?activity='.(int)$activity['id']:'');}
function admin_quantity_label(int $quantity,string $singular,string $plural): string {return $quantity.' '.($quantity===1?$singular:$plural);}
function admin_media_summary(int $images,int $videos): string {return admin_quantity_label($images,'imagem','imagens').' · '.admin_quantity_label($videos,'vídeo','vídeos');}
function admin_public_activity_url(?array $activity): string {if(!$activity)return '/';return (int)($activity['is_root']??0)===1?'/':'/'.rawurlencode((string)$activity['slug']).'/';}
function admin_shell_start(string $section,string $title,array $state): void {
    $activity=$state['activity'];
    $items=[
        'overview'=>['Visão geral','/admin/'],
        'pages'=>['Páginas',admin_shell_url('/admin/pages.php',$activity)],
        'blocks'=>['Blocos reutilizáveis',admin_shell_url('/admin/blocks.php',$activity)],
        'design'=>['Design',admin_shell_url('/admin/design.php',$activity)],
        'site'=>['Site e navegação',admin_shell_url('/admin/site.php',$activity)],
        'forms'=>['Formulários',admin_shell_url('/admin/forms.php',$activity)],
        'responses'=>['Respostas',admin_shell_url('/admin/submissions.php',$activity)],
        'media'=>['Mídia',admin_shell_url('/admin/media.php',$activity)],
        'system'=>['Sistema e atualizações','/admin/system.php'],
    ];
    ?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/admin/admin.css"><link rel="stylesheet" href="/admin/cms-admin.css"><title><?=h($title)?></title></head><body class="admin-page"><div class="admin-frame"><header class="admin-mobile-header"><a href="/admin/" class="admin-wordmark">Workshop</a><button class="admin-menu-toggle" type="button" aria-expanded="false" aria-controls="admin-navigation">Menu</button></header><aside class="admin-sidebar" id="admin-navigation"><a href="/admin/" class="admin-wordmark">Direct Positive<br><span>Workshop</span></a><nav class="admin-nav" aria-label="Navegação administrativa"><?php foreach($items as $key=>[$label,$href]):?><a href="<?=h($href)?>"<?=$key===$section?' aria-current="page"':''?>><?=h($label)?></a><?php endforeach;?></nav><div class="admin-secondary"><?php if($activity):?><a href="<?=h(admin_public_activity_url($activity))?>" target="_blank" rel="noopener">Abrir site público</a><?php endif;?><a href="/admin/activities.php">Atividades</a><form method="post" action="/admin/logout.php"><input type="hidden" name="_csrf" value="<?=h(csrf_token('logout'))?>"><button type="submit">Sair</button></form></div></aside><main class="admin-main"><header class="admin-page-header"><p class="admin-kicker">Administração<?=$activity?' · '.h($activity['admin_name']):''?></p><h1><?=h($title)?></h1></header><?php
}
function admin_shell_end(): void {?></main></div><script src="/assets/admin.js"></script></body></html><?php }
