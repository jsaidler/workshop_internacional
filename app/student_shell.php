<?php
declare(strict_types=1);

function student_shell_activity(?array $activity,?array $student): ?array {
    return $activity;
}

function student_shell_start(string $title,?array $activity=null,?array $student=null): void {
    $assetVersion=function_exists('admin_asset_version')?admin_asset_version():(string)(@filemtime(dirname(__DIR__).'/assets/student-area.css')?:1);
    $resolvedActivity=student_shell_activity($activity,$student);
    $design=$resolvedActivity?cms_design_settings(database(),(int)$resolvedActivity['id'],PUBLIC_LOCALE_PT_BR):cms_design_defaults();
    $path=(string)parse_url((string)($_SERVER['REQUEST_URI']??'/aluno/'),PHP_URL_PATH);
    $active=str_contains($path,'test')?'tests':(str_contains($path,'perfil')||str_contains($path,'senha')?'account':'courses');
    $brand=function_exists('installation_brand_name')?installation_brand_name():'JSaidler Fotografia';
    ?><!doctype html><html lang="pt-BR" data-theme="light"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow,noarchive,nosnippet"><meta name="theme-color" content="<?=h((string)($design['colors']['bg']??'#f2f2ef'))?>"><style data-cms-design-fonts><?=cms_design_font_import_css($design)?></style><link rel="stylesheet" href="/template/page.css?v=<?=h($assetVersion)?>"><style data-cms-design-tokens><?=cms_design_css($design)?></style><link rel="stylesheet" href="/assets/student-area.css?v=<?=h($assetVersion)?>"><title><?=h($title)?></title></head><body class="student-page"><div class="student-shell"><header class="student-topbar"><a class="brand student-wordmark" href="/aluno/"><?=h($brand)?></a><?php if($student):?><nav class="student-desktop-nav" aria-label="Área do aluno"><a href="/aluno/"<?=$active==='courses'?' aria-current="page"':''?>>Cursos</a><a href="/aluno/testes.php"<?=$active==='tests'?' aria-current="page"':''?>>Testes</a><a href="/aluno/perfil.php"<?=$active==='account'?' aria-current="page"':''?>>Conta</a></nav><span class="student-topbar-spacer"></span><span class="student-user"><?=h((string)$student['name'])?></span><form method="post" action="/aluno/logout.php" class="student-logout-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-logout'))?>"><button class="student-logout" type="submit">Sair</button></form><?php endif;?></header><main class="student-main"><?php
}
function student_shell_end(): void {
    $student=function_exists('student_account_current')?student_account_current(database()):null;
    $path=(string)parse_url((string)($_SERVER['REQUEST_URI']??'/aluno/'),PHP_URL_PATH);$active=str_contains($path,'test')?'tests':(str_contains($path,'perfil')||str_contains($path,'senha')?'account':'courses');
    ?></main><?php if($student):?><nav class="student-mobile-nav" aria-label="Navegação da área do aluno"><a href="/aluno/"<?=$active==='courses'?' aria-current="page"':''?>><span>01</span>Cursos</a><a href="/aluno/testes.php"<?=$active==='tests'?' aria-current="page"':''?>><span>02</span>Testes</a><a href="/aluno/perfil.php"<?=$active==='account'?' aria-current="page"':''?>><span>03</span>Conta</a></nav><?php endif;?></div></body></html><?php }
