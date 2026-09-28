<?php
declare(strict_types=1);

function student_shell_activity(?array $activity,?array $student): ?array {
    return $activity;
}
function student_shell_global_active(): string {
    $path=(string)parse_url((string)($_SERVER['REQUEST_URI']??'/aluno/'),PHP_URL_PATH);
    return str_contains($path,'perfil')||str_contains($path,'senha')?'account':'courses';
}
function student_course_context_header(array $enrollment,string $active='overview',bool $showBack=false): void {
    $cohortUuid=(string)($enrollment['cohort_uuid']??'');
    $courseTitle=(string)($enrollment['course_title']??'Curso');
    $cohortTitle=(string)($enrollment['cohort_title']??'Turma');
    $status=student_enrollment_cohort_status_label((string)($enrollment['cohort_status']??'active'));
    $query='?cohort='.rawurlencode($cohortUuid);
    $items=[
        'overview'=>['Visão geral','/aluno/'.$query],
        'material'=>['Material','/aluno/material.php'.$query],
        'tests'=>['Testes','/aluno/testes.php'.$query],
    ];
    ?><section class="student-course-context" aria-label="Contexto do curso">
      <div class="student-course-context-top">
        <div class="student-course-context-copy"><?php if($showBack):?><a class="student-context-back" href="/aluno/">← Meus cursos</a><?php endif;?><p class="student-card-label"><?=h($cohortTitle)?></p><h1><?=h($courseTitle)?></h1></div>
        <span class="student-status"><?=h($status)?></span>
      </div>
      <nav class="student-course-nav" aria-label="Navegação do curso"><?php foreach($items as $key=>[$label,$href]):?><a href="<?=h($href)?>"<?=$key===$active?' aria-current="page"':''?>><?=h($label)?></a><?php endforeach;?></nav>
    </section><?php
}

function student_shell_start(string $title,?array $activity=null,?array $student=null): void {
    $assetVersion=function_exists('admin_asset_version')?admin_asset_version():(string)(@filemtime(dirname(__DIR__).'/assets/ui-core.css')?:1);
    $resolvedActivity=student_shell_activity($activity,$student);
    $design=$resolvedActivity?cms_design_settings(database(),(int)$resolvedActivity['id'],PUBLIC_LOCALE_PT_BR):cms_design_defaults();
    $active=student_shell_global_active();
    $brand=function_exists('installation_brand_name')?installation_brand_name():'JSaidler Fotografia';
    ?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow,noarchive,nosnippet"><meta name="theme-color" content="<?=h((string)($design['colors']['bg']??'#f2f2ef'))?>"><style data-cms-design-fonts><?=cms_design_font_import_css($design)?></style><link rel="stylesheet" href="/template/page.css?v=<?=h($assetVersion)?>"><style data-cms-design-tokens><?=cms_design_css($design)?></style><link rel="stylesheet" href="/assets/ui-core.css?v=<?=h($assetVersion)?>"><link rel="stylesheet" href="/assets/student-area.css?v=<?=h($assetVersion)?>"><link rel="stylesheet" href="/assets/student-area-v2.css?v=<?=h($assetVersion)?>"><title><?=h($title)?></title></head><body class="student-page"><div class="student-shell"><header class="student-topbar"><a class="brand student-wordmark" href="/aluno/"><?=h($brand)?></a><?php if($student):?><nav class="student-desktop-nav" aria-label="Área do aluno"><a href="/aluno/"<?=$active==='courses'?' aria-current="page"':''?>>Meus cursos</a><a href="/aluno/perfil.php"<?=$active==='account'?' aria-current="page"':''?>>Conta</a></nav><?php endif;?><span class="student-topbar-spacer"></span><div aria-label="Tema" class="theme-switch student-theme-switch"><button aria-pressed="true" data-theme-value="auto" type="button">Auto</button><button aria-pressed="false" data-theme-value="light" type="button">Light</button><button aria-pressed="false" data-theme-value="dark" type="button">Dark</button></div><?php if($student):?><form method="post" action="/aluno/logout.php" class="student-logout-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-logout'))?>"><button class="student-logout" type="submit">Sair</button></form><?php endif;?></header><main class="student-main"><?php
}
function student_shell_end(): void {
    $student=function_exists('student_account_current')?student_account_current(database()):null;
    $active=student_shell_global_active();
    $assetVersion=function_exists('admin_asset_version')?admin_asset_version():(string)(@filemtime(dirname(__DIR__).'/assets/ui-core.js')?:1);
    ?></main><?php if($student):?><nav class="student-mobile-nav" aria-label="Navegação da área do aluno"><a href="/aluno/"<?=$active==='courses'?' aria-current="page"':''?>>Meus cursos</a><a href="/aluno/perfil.php"<?=$active==='account'?' aria-current="page"':''?>>Conta</a></nav><?php endif;?></div><script defer src="/assets/ui-core.js?v=<?=h($assetVersion)?>"></script></body></html><?php }
