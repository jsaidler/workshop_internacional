<?php
declare(strict_types=1);

function admin_asset_version(): string {
    static $version=null;
    if(is_string($version))return $version;
    $root=dirname(__DIR__);$info=$root.'/deploy-info.json';
    if(is_file($info)){$decoded=json_decode((string)@file_get_contents($info),true);$sha=is_array($decoded)?trim((string)($decoded['sourceSha']??'')):'';if($sha!==''&&preg_match('/^[a-f0-9]{7,64}$/i',$sha))return $version=substr($sha,0,16);}
    $mtime=@filemtime($root.'/assets/admin-ux-v3.css');
    return $version=$mtime!==false?(string)$mtime:'1';
}
function admin_asset_url(string $path): string {return $path.'?v='.rawurlencode(admin_asset_version());}
function admin_shell_url(string $path, ?array $activity): string {return $path.($activity?'?activity='.(int)$activity['id']:'');}
function admin_quantity_label(int $quantity,string $singular,string $plural): string {return $quantity.' '.($quantity===1?$singular:$plural);}
function admin_media_summary(int $images,int $videos): string {return admin_quantity_label($images,'imagem','imagens').' · '.admin_quantity_label($videos,'vídeo','vídeos');}
function admin_public_activity_url(?array $activity): string {if(!$activity)return '/';return (int)($activity['is_root']??0)===1?'/':'/'.rawurlencode((string)$activity['slug']).'/';}
function admin_workspace(string $section): string {return match($section){
    'pages','blocks','design','site','seo','overview','forms','responses'=>'content',
    'courses','registrations','people','students','studentops'=>'courses',
    'analytics'=>'analytics',
    'media'=>'media',
    'system','activities','integrity'=>'settings',
    default=>'content',
};}
function admin_context_items(string $workspace,?array $activity): array {return match($workspace){
    'content'=>[
        'pages'=>['Páginas',admin_shell_url('/admin/pages.php',$activity)],
        'forms'=>['Formulários',admin_shell_url('/admin/forms.php',$activity)],
        'responses'=>['Outras respostas',admin_shell_url('/admin/submissions.php',$activity)],
        'site'=>['Navegação',admin_shell_url('/admin/site.php',$activity)],
        'design'=>['Visual',admin_shell_url('/admin/design.php',$activity)],
        'seo'=>['SEO',admin_shell_url('/admin/seo.php',$activity)],
    ],
    'courses'=>[
        'courses'=>['Cursos',admin_shell_url('/admin/courses.php',$activity)],
        'registrations'=>['Inscrições',admin_shell_url('/admin/registrations.php',$activity)],
        'people'=>['Pessoas',admin_shell_url('/admin/people.php',$activity)],
    ],
    'settings'=>[
        'system'=>['Sistema e atualizações','/admin/system.php'],
        'activities'=>['Estrutura do site','/admin/activities.php'],
        'integrity'=>['Integridade',admin_shell_url('/admin/data-integrity.php',$activity)],
    ],
    default=>[],
};}
function admin_course_url(int $activityId,int $courseId,string $area='overview'): string {
    if($area==='registrations')return '/admin/registrations.php?'.http_build_query(['activity'=>$activityId,'course'=>$courseId]);
    $view=match($area){'overview'=>'overview','cohorts'=>'cohorts','students'=>'students','lessons'=>'lessons','material'=>'material','setup'=>'setup',default=>'overview'};
    return '/admin/courses.php?'.http_build_query(['activity'=>$activityId,'course'=>$courseId,'view'=>$view]);
}
function admin_course_context(array $course,int $activityId,string $active='overview',?string $description=null): void {
    $courseId=(int)$course['id'];$items=['overview'=>'Visão geral','registrations'=>'Inscrições','cohorts'=>'Turmas','students'=>'Alunos','lessons'=>'Aulas','material'=>'Material'];
    ?><section class="admin-course-context"><div><p class="admin-kicker">Curso</p><h2><?=h((string)$course['title'])?></h2><?php if($description):?><p><?=h($description)?></p><?php endif;?></div><nav class="admin-course-nav" aria-label="Administração do curso"><?php foreach($items as $key=>$label):?><a href="<?=h(admin_course_url($activityId,$courseId,$key))?>"<?=$active===$key?' aria-current="page"':''?>><?=h($label)?></a><?php endforeach;?></nav></section><?php
}
function admin_shell_start(string $section,string $title,array $state): void {
    $activity=$state['activity']??null;$workspace=admin_workspace($section);$brand=function_exists('installation_brand_name')?installation_brand_name():'JSaidler Fotografia';
    $items=[
        'content'=>['Conteúdo','/admin/'],
        'courses'=>['Cursos',admin_shell_url('/admin/courses.php',$activity)],
        'media'=>['Mídia',admin_shell_url('/admin/media.php',$activity)],
        'analytics'=>['Métricas',admin_shell_url('/admin/analytics.php',$activity)],
        'settings'=>['Configurações','/admin/system.php'],
    ];
    $context=admin_context_items($workspace,$activity);$publicUrl=admin_public_activity_url($activity);
    ?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="<?=h(admin_asset_url('/admin/admin.css'))?>"><link rel="stylesheet" href="<?=h(admin_asset_url('/admin/cms-admin.css'))?>"><link rel="stylesheet" href="<?=h(admin_asset_url('/admin/pro-admin.css'))?>"><link rel="stylesheet" href="<?=h(admin_asset_url('/assets/admin-media-maintenance.css'))?>"><link rel="stylesheet" href="<?=h(admin_asset_url('/assets/admin-ux-v2.css'))?>"><link rel="stylesheet" href="<?=h(admin_asset_url('/assets/admin-ux-v3.css'))?>"><link rel="stylesheet" href="<?=h(admin_asset_url('/assets/admin-data-ux.css'))?>"><link rel="stylesheet" href="<?=h(admin_asset_url('/assets/admin-form-ux.css'))?>"><link rel="stylesheet" href="<?=h(admin_asset_url('/assets/experience-ux.css'))?>"><style id="admin-system-choice-controls">body.admin-page input[type="checkbox"],body.admin-page input[type="radio"]{box-sizing:border-box!important;width:18px!important;height:18px!important;min-width:18px!important;min-height:18px!important;max-width:18px!important;max-height:18px!important;padding:0!important;margin:0!important;flex:0 0 18px!important;box-shadow:none!important}</style><style id="admin-shell-structure">.admin-sidebar .admin-wordmark-context{display:block;margin-top:1px}</style><title><?=h($title)?></title></head><body class="admin-page admin-section-<?=h($section)?> admin-workspace-<?=h($workspace)?>"><div class="admin-frame"><header class="admin-mobile-header"><a href="/admin/" class="admin-wordmark"><?=h($brand)?></a><button class="admin-menu-toggle" type="button" aria-expanded="false" aria-controls="admin-navigation">Menu</button></header><aside class="admin-sidebar" id="admin-navigation"><a href="/admin/" class="admin-wordmark"><?=h($brand)?><span class="admin-wordmark-context">Administração</span></a><?php if($activity):?><div class="admin-site-current"><span>Site</span><strong><?=h((string)$activity['admin_name'])?></strong></div><?php endif;?><nav class="admin-nav" aria-label="Navegação administrativa"><?php foreach($items as $key=>[$label,$href]):?><a href="<?=h($href)?>"<?=$key===$workspace?' aria-current="page"':''?>><?=h($label)?></a><?php endforeach;?></nav><div class="admin-secondary"><?php if($activity):?><a class="admin-open-site" href="<?=h($publicUrl)?>" target="_blank" rel="noopener">Abrir site ↗</a><?php endif;?><form method="post" action="/admin/logout.php"><input type="hidden" name="_csrf" value="<?=h(csrf_token('logout'))?>"><button type="submit">Sair</button></form></div></aside><main class="admin-main"><header class="admin-toolbar"><div class="admin-toolbar-title"><h1><?=h($title)?></h1><p><?=$activity?h((string)$activity['admin_name']):h($brand)?></p></div><div class="admin-toolbar-actions"><?php if($activity):?><a class="admin-open-toolbar" href="<?=h($publicUrl)?>" target="_blank" rel="noopener">Abrir site ↗</a><?php endif;?></div></header><?php if($context):?><nav class="admin-context-nav" aria-label="Ferramentas desta área"><?php foreach($context as $key=>[$label,$href]):?><a href="<?=h($href)?>"<?=$key===$section?' aria-current="page"':''?>><?=h($label)?></a><?php endforeach;?></nav><?php endif;?><div class="admin-content"><?php
}
function admin_shell_end(): void {?></div></main></div><script src="<?=h(admin_asset_url('/assets/admin.js'))?>"></script><script src="<?=h(admin_asset_url('/assets/admin-ux-v2.js'))?>"></script></body></html><?php }
