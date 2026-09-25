<?php
declare(strict_types=1);

function student_shell_design_context(?array $activity=null): array {
    $db=database();
    $activity=$activity?:root_activity($db);
    $locale=PUBLIC_LOCALE_PT_BR;
    $design=cms_design_settings($db,(int)$activity['id'],$locale);
    $site=cms_site_settings($db,(int)$activity['id'],$locale);
    return [$activity,$site,$design,$locale];
}

function student_shell_start(string $title,?array $activity=null,?array $student=null): void {
    [$activity,$site,$design,$locale]=student_shell_design_context($activity);
    $assetVersion=cms_public_asset_version();
    $assetVersionHtml=h($assetVersion);
    $path=(string)parse_url((string)($_SERVER['REQUEST_URI']??'/aluno/'),PHP_URL_PATH);
    $active=str_contains($path,'test')?'tests':(str_contains($path,'perfil')||str_contains($path,'senha')?'account':'courses');
    $wordmark=trim((string)($site['wordmark']??''))?:trim((string)($activity['public_title']??''));
    $themeColor=(string)($design['colors']['bg']??'#f2f2ef');
    ?><!doctype html><html lang="<?=h($locale)?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow,noarchive,nosnippet"><meta name="theme-color" content="<?=h($themeColor)?>"><style id="student-system-styles"><?=cms_design_font_import_css($design)?>@import url("/template/page.css?v=<?=$assetVersionHtml?>") layer(cms-system);</style><style id="student-design-vars">@layer cms-system{<?=cms_design_system_css($design)?>}</style><link rel="stylesheet" href="/assets/student-area.css?v=<?=$assetVersionHtml?>"><link rel="stylesheet" href="/assets/student-dashboard.css?v=<?=$assetVersionHtml?>"><style id="student-design-bridge">:root{--student-bg:var(--bg);--student-ink:var(--text);--student-muted:var(--muted);--student-line:var(--line);--student-line-soft:color-mix(in srgb,var(--line) 62%,transparent);--student-accent:var(--focus);--student-surface:var(--surface);--student-soft:var(--surface-2);--student-title:var(--title);--student-body:var(--body);--student-mono:var(--mono);--student-content:var(--max)}</style><style id="student-custom-css"><?=cms_design_custom_css($design)?></style><title><?=h($title)?></title></head><body class="student-page"><div class="student-shell"><header class="student-topbar"><a class="student-wordmark" href="/aluno/"><?=h($wordmark)?></a><?php if($student):?><nav class="student-desktop-nav" aria-label="Área do aluno"><a href="/aluno/"<?=$active==='courses'?' aria-current="page"':''?>>Cursos</a><a href="/aluno/testes.php"<?=$active==='tests'?' aria-current="page"':''?>>Testes</a><a href="/aluno/perfil.php"<?=$active==='account'?' aria-current="page"':''?>>Conta</a></nav><span class="student-topbar-spacer"></span><span class="student-user"><?=h((string)$student['name'])?></span><form method="post" action="/aluno/logout.php" class="student-logout-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-logout'))?>"><button class="student-logout" type="submit">Sair</button></form><?php endif;?></header><main class="student-main"><?php
}
function student_shell_end(): void {
    $student=function_exists('student_account_current')?student_account_current(database()):null;
    $path=(string)parse_url((string)($_SERVER['REQUEST_URI']??'/aluno/'),PHP_URL_PATH);$active=str_contains($path,'test')?'tests':(str_contains($path,'perfil')||str_contains($path,'senha')?'account':'courses');
    ?></main><?php if($student):?><nav class="student-mobile-nav" aria-label="Navegação da área do aluno"><a href="/aluno/"<?=$active==='courses'?' aria-current="page"':''?>><span>01</span>Cursos</a><a href="/aluno/testes.php"<?=$active==='tests'?' aria-current="page"':''?>><span>02</span>Testes</a><a href="/aluno/perfil.php"<?=$active==='account'?' aria-current="page"':''?>><span>03</span>Conta</a></nav><?php endif;?></div></body></html><?php }
