<?php
declare(strict_types=1);

function admin_asset_version(): string {
    static $version=null;
    if(is_string($version))return $version;
    $root=dirname(__DIR__);$info=$root.'/deploy-info.json';
    if(is_file($info)){$decoded=json_decode((string)@file_get_contents($info),true);$sha=is_array($decoded)?trim((string)($decoded['sourceSha']??'')):'';if($sha!==''&&preg_match('/^[a-f0-9]{7,64}$/i',$sha))return $version=substr($sha,0,16);}
    $mtime=@filemtime($root.'/assets/admin-system.css');
    return $version=$mtime!==false?(string)$mtime:'1';
}
function admin_asset_url(string $path): string {return $path.'?v='.rawurlencode(admin_asset_version());}
function admin_shell_url(string $path, ?array $activity): string {return $path.($activity?'?activity='.(int)$activity['id']:'');}
function admin_quantity_label(int $quantity,string $singular,string $plural): string {return $quantity.' '.($quantity===1?$singular:$plural);}
function admin_media_summary(int $images,int $videos): string {return admin_quantity_label($images,'imagem','imagens').' · '.admin_quantity_label($videos,'vídeo','vídeos');}
function admin_public_activity_url(?array $activity): string {if(!$activity)return '/';return (int)($activity['is_root']??0)===1?'/':'/'.rawurlencode((string)$activity['slug']).'/';}

function admin_badge(string $label,string $tone='neutral'): string {
    $allowed=['neutral','good','attention','danger','muted'];
    if(!in_array($tone,$allowed,true))$tone='neutral';
    return '<span class="admin-badge admin-badge--'.h($tone).'">'.h($label).'</span>';
}
function admin_filter_chip(string $label,string $value,string $removeUrl): string {
    $text=trim($label).': '.trim($value);
    return '<a class="admin-filter-chip" href="'.h($removeUrl).'" aria-label="Remover filtro '.h($text).'"><span>'.h($text).'</span><b aria-hidden="true">×</b></a>';
}
function admin_status_label(string $scope,string $value): string {
    $key=strtolower(trim($value));
    $maps=[
        'payment'=>['paid'=>'Pago','pending'=>'Pendente','refunded'=>'Reembolsado','cancelled'=>'Cancelado','canceled'=>'Cancelado'],
        'enrollment'=>['active'=>'Ativa','inactive'=>'Inativa','archived'=>'Arquivada','cancelled'=>'Cancelada','canceled'=>'Cancelada'],
        'account'=>['active'=>'Ativa','pending'=>'Pendente','inactive'=>'Inativa','blocked'=>'Bloqueada','archived'=>'Arquivada'],
        'cohort'=>['active'=>'Ativa','archived'=>'Arquivada','inactive'=>'Inativa'],
    ];
    return $maps[$scope][$key]??($value!==''?$value:'—');
}
function admin_status_tone(string $scope,string $value): string {
    $key=strtolower(trim($value));
    if(in_array($key,['paid','active'],true))return 'good';
    if(in_array($key,['pending'],true))return 'attention';
    if(in_array($key,['cancelled','canceled','blocked'],true))return 'danger';
    if(in_array($key,['inactive','archived','refunded'],true))return 'muted';
    return 'neutral';
}

function admin_workspace(string $section): string {return match($section){
    'overview'=>'overview',
    'registrations','cohorts','students','people','studentops'=>'operation',
    'courses','lessons','material'=>'teaching',
    'pages','blocks','design','site','seo','forms','responses'=>'site',
    'analytics'=>'analytics',
    'media'=>'media',
    'system','activities','integrity'=>'settings',
    default=>'site',
};}

function admin_context_items(string $workspace,?array $activity): array {return match($workspace){
    'operation'=>[
        'registrations'=>['Inscrições',admin_shell_url('/admin/registrations.php',$activity)],
        'cohorts'=>['Turmas',admin_shell_url('/admin/cohorts.php',$activity)],
        'students'=>['Alunos',admin_shell_url('/admin/students.php',$activity)],
        'people'=>['Pessoas',admin_shell_url('/admin/people.php',$activity)],
    ],
    'teaching'=>[
        'courses'=>['Cursos',admin_shell_url('/admin/courses.php',$activity)],
        'lessons'=>['Aulas',admin_shell_url('/admin/lessons.php',$activity)],
        'material'=>['Material',admin_shell_url('/admin/material.php',$activity)],
    ],
    'site','content'=>[
        'pages'=>['Páginas',admin_shell_url('/admin/pages.php',$activity)],
        'forms'=>['Formulários',admin_shell_url('/admin/forms.php',$activity)],
        'responses'=>['Outras respostas',admin_shell_url('/admin/submissions.php',$activity)],
        'site'=>['Navegação',admin_shell_url('/admin/site.php',$activity)],
        'design'=>['Visual',admin_shell_url('/admin/design.php',$activity)],
        'seo'=>['SEO',admin_shell_url('/admin/seo.php',$activity)],
    ],
    'courses'=>array_merge(admin_context_items('operation',$activity),admin_context_items('teaching',$activity)),
    'settings'=>[
        'system'=>['Sistema e atualizações','/admin/system.php'],
        'activities'=>['Estrutura do site','/admin/activities.php'],
        'integrity'=>['Integridade',admin_shell_url('/admin/data-integrity.php',$activity)],
    ],
    default=>[],
};}

function admin_navigation_groups(?array $activity): array {return [
    'Principal'=>[
        'overview'=>['Visão geral','/admin/'],
    ],
    'Operação'=>admin_context_items('operation',$activity),
    'Ensino'=>admin_context_items('teaching',$activity),
    'Site'=>admin_context_items('site',$activity),
    'Biblioteca'=>[
        'media'=>['Mídia',admin_shell_url('/admin/media.php',$activity)],
    ],
    'Análise'=>[
        'analytics'=>['Métricas',admin_shell_url('/admin/analytics.php',$activity)],
    ],
    'Sistema'=>admin_context_items('settings',$activity),
];}

function admin_navigation_active_key(string $section): string {return match($section){
    'blocks'=>'pages',
    'studentops'=>'people',
    default=>$section,
};}

function admin_course_url(int $activityId,int $courseId,string $area='overview'): string {
    $args=['activity'=>$activityId,'course'=>$courseId];
    return match($area){
        'registrations'=>'/admin/registrations.php?'.http_build_query($args),
        'cohorts'=>'/admin/cohorts.php?'.http_build_query($args),
        'students'=>'/admin/students.php?'.http_build_query($args),
        'lessons'=>'/admin/lessons.php?'.http_build_query($args),
        'material'=>'/admin/material.php?'.http_build_query($args),
        'setup'=>'/admin/courses.php?'.http_build_query($args+['view'=>'setup']),
        default=>'/admin/courses.php?'.http_build_query($args),
    };
}

function admin_course_context(array $course,int $activityId,string $active='overview',?string $description=null): void {
    ?><section class="admin-scope-strip" aria-label="Curso filtrado">
      <div><span class="admin-scope-label">Curso</span><strong><?=h((string)$course['title'])?></strong><?php if($description):?><small><?=h($description)?></small><?php endif;?></div>
      <a href="<?=h(admin_course_url($activityId,(int)$course['id'],'overview'))?>">Ver curso</a>
    </section><?php
}

function admin_shell_start(string $section,string $title,array $state,array $styles=[]): void {
    $activity=$state['activity']??null;$workspace=admin_workspace($section);$activeKey=admin_navigation_active_key($section);$brand=function_exists('installation_brand_name')?installation_brand_name():'JSaidler Fotografia';
    $groups=admin_navigation_groups($activity);$publicUrl=admin_public_activity_url($activity);
    ?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="<?=h(admin_asset_url('/assets/admin-system.css'))?>"><link rel="stylesheet" href="<?=h(admin_asset_url('/assets/admin-shell-responsive.css'))?>"><?php foreach(array_values(array_unique($styles)) as $style):?><link rel="stylesheet" href="<?=h(admin_asset_url($style))?>"><?php endforeach;?><title><?=h($title)?></title></head><body class="admin-page admin-section-<?=h($section)?> admin-workspace-<?=h($workspace)?>"><div class="admin-frame"><header class="admin-mobile-header"><a href="/admin/" class="admin-wordmark"><?=h($brand)?></a><button class="admin-menu-toggle" type="button" aria-expanded="false" aria-controls="admin-navigation">Menu</button></header><aside class="admin-sidebar" id="admin-navigation"><a href="/admin/" class="admin-wordmark"><?=h($brand)?><span class="admin-wordmark-context">Administração</span></a><?php if($activity):?><div class="admin-site-current"><span>Site</span><strong><?=h((string)$activity['admin_name'])?></strong></div><?php endif;?><nav class="admin-nav" aria-label="Navegação administrativa"><?php foreach($groups as $groupLabel=>$groupItems):?><section class="admin-nav-group" aria-label="<?=h($groupLabel)?>"><p class="admin-nav-label"><?=h($groupLabel)?></p><div class="admin-nav-links"><?php foreach($groupItems as $key=>[$label,$href]):?><a href="<?=h($href)?>"<?=$key===$activeKey?' aria-current="page"':''?>><?=h($label)?></a><?php endforeach;?></div></section><?php endforeach;?></nav><div class="admin-secondary"><?php if($activity):?><a class="admin-open-site" href="<?=h($publicUrl)?>" target="_blank" rel="noopener">Abrir site ↗</a><?php endif;?><form method="post" action="/admin/logout.php"><input type="hidden" name="_csrf" value="<?=h(csrf_token('logout'))?>"><button type="submit">Sair</button></form></div></aside><main class="admin-main"><header class="admin-toolbar"><div class="admin-toolbar-title"><h1><?=h($title)?></h1><p><?=$activity?h((string)$activity['admin_name']):h($brand)?></p></div><div class="admin-toolbar-actions"><?php if($activity):?><a class="admin-open-toolbar" href="<?=h($publicUrl)?>" target="_blank" rel="noopener">Abrir site ↗</a><?php endif;?></div></header><div class="admin-content"><?php
}
function admin_shell_end(): void {?></div></main></div><script src="<?=h(admin_asset_url('/assets/admin.js'))?>"></script><script src="<?=h(admin_asset_url('/assets/admin-ux-v2.js'))?>"></script></body></html><?php }
