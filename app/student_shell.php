<?php
declare(strict_types=1);

function student_shell_activity(?array $activity,?array $student): ?array {return $activity;}

function student_course_context_header(array $enrollment,string $active='overview',string $materialUrl=''): void {
    $cohortUuid=(string)($enrollment['cohort_uuid']??'');
    $overviewUrl='/aluno/?cohort='.rawurlencode($cohortUuid);
    $testsUrl='/aluno/testes.php?cohort='.rawurlencode($cohortUuid);
    $statusLabel=function_exists('student_enrollment_cohort_status_label')?student_enrollment_cohort_status_label((string)($enrollment['cohort_status']??'')):(string)($enrollment['cohort_status']??'');
    ?>
    <header class="student-course-context" aria-label="Contexto do curso">
      <a class="student-course-context-back" href="/aluno/">← Meus cursos</a>
      <div class="student-course-context-head">
        <div><p class="student-course-context-kicker"><?=h((string)($enrollment['cohort_title']??''))?></p><h1 class="student-course-context-title"><?=h((string)($enrollment['course_title']??'Curso'))?></h1></div>
        <?php if($statusLabel!==''):?><span class="student-status student-course-context-status"><?=h($statusLabel)?></span><?php endif;?>
      </div>
      <nav class="student-course-context-nav" aria-label="Navegação do curso">
        <a href="<?=h($overviewUrl)?>"<?=$active==='overview'?' aria-current="page"':''?>>Visão geral</a>
        <?php if($materialUrl!==''):?><a href="<?=h($materialUrl)?>"<?=$active==='material'?' aria-current="page"':''?>>Material</a><?php endif;?>
        <a href="<?=h($testsUrl)?>"<?=$active==='tests'?' aria-current="page"':''?>>Testes</a>
      </nav>
    </header>
    <?php
}

function student_shell_start(string $title,?array $activity=null,?array $student=null): void {
    $assetVersion=function_exists('admin_asset_version')?admin_asset_version():(string)(@filemtime(dirname(__DIR__).'/assets/ui-core.css')?:1);
    $resolvedActivity=student_shell_activity($activity,$student);
    $design=$resolvedActivity?cms_design_settings(database(),(int)$resolvedActivity['id'],PUBLIC_LOCALE_PT_BR):cms_design_defaults();
    $path=(string)parse_url((string)($_SERVER['REQUEST_URI']??'/aluno/'),PHP_URL_PATH);
    $active=(str_contains($path,'perfil')||str_contains($path,'senha'))?'account':'courses';
    $brand=function_exists('installation_brand_name')?installation_brand_name():'JSaidler Fotografia';
    ?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow,noarchive,nosnippet"><meta name="theme-color" content="<?=h((string)($design['colors']['bg']??'#f2f2ef'))?>"><style data-cms-design-fonts><?=cms_design_font_import_css($design)?></style><link rel="stylesheet" href="/template/page.css?v=<?=h($assetVersion)?>"><style data-cms-design-tokens><?=cms_design_css($design)?></style><link rel="stylesheet" href="/assets/ui-core.css?v=<?=h($assetVersion)?>"><link rel="stylesheet" href="/assets/student-area.css?v=<?=h($assetVersion)?>"><link rel="stylesheet" href="/assets/experience-ux.css?v=<?=h($assetVersion)?>"><title><?=h($title)?></title></head><body class="student-page"><div class="student-shell"><header class="student-topbar"><a class="brand student-wordmark" href="/aluno/"><?=h($brand)?></a><?php if($student):?><nav class="student-desktop-nav" aria-label="Área do aluno"><a href="/aluno/"<?=$active==='courses'?' aria-current="page"':''?>>Meus cursos</a><a href="/aluno/perfil.php"<?=$active==='account'?' aria-current="page"':''?>>Conta</a></nav><?php endif;?><span class="student-topbar-spacer"></span><div aria-label="Tema" class="theme-switch student-theme-switch"><button aria-pressed="true" data-theme-value="auto" type="button">Auto</button><button aria-pressed="false" data-theme-value="light" type="button">Light</button><button aria-pressed="false" data-theme-value="dark" type="button">Dark</button></div><?php if($student):?><span class="student-user"><?=h((string)$student['name'])?></span><form method="post" action="/aluno/logout.php" class="student-logout-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-logout'))?>"><button class="student-logout" type="submit">Sair</button></form><?php endif;?></header><main class="student-main"><?php
}
function student_shell_end(): void {
    $student=function_exists('student_account_current')?student_account_current(database()):null;
    $path=(string)parse_url((string)($_SERVER['REQUEST_URI']??'/aluno/'),PHP_URL_PATH);$active=(str_contains($path,'perfil')||str_contains($path,'senha'))?'account':'courses';
    $assetVersion=function_exists('admin_asset_version')?admin_asset_version():(string)(@filemtime(dirname(__DIR__).'/assets/ui-core.js')?:1);
    ?></main><?php if($student):?><nav class="student-mobile-nav" aria-label="Navegação da área do aluno"><a href="/aluno/"<?=$active==='courses'?' aria-current="page"':''?>><span>01</span>Meus cursos</a><a href="/aluno/perfil.php"<?=$active==='account'?' aria-current="page"':''?>><span>02</span>Conta</a></nav><?php endif;?></div><script defer src="/assets/ui-core.js?v=<?=h($assetVersion)?>"></script></body></html><?php }
