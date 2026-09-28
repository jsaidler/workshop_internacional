<?php
declare(strict_types=1);

function student_shell_activity(?array $activity,?array $student): ?array {
    return $activity;
}

function student_shell_asset_version(): string {
    return function_exists('admin_asset_version')?admin_asset_version():(string)(@filemtime(dirname(__DIR__).'/assets/student-shell.css')?:1);
}

function student_shell_global_active(?string $path=null): string {
    $path=$path??(string)parse_url((string)($_SERVER['REQUEST_URI']??'/aluno/'),PHP_URL_PATH);
    return str_contains($path,'perfil')||str_contains($path,'senha')?'account':'courses';
}

function student_shell_topbar_markup(?array $student,string $active='courses'): string {
    $brand=function_exists('installation_brand_name')?installation_brand_name():'JSaidler Fotografia';
    ob_start();?>
<header class="student-topbar" data-student-global-header>
    <div class="student-topbar-inner">
        <a class="brand student-wordmark" href="/aluno/"><?=h($brand)?></a>
        <?php if($student):?><nav class="student-desktop-nav" aria-label="Área do aluno"><a href="/aluno/"<?=$active==='courses'?' aria-current="page"':''?>>Meus cursos</a><a href="/aluno/perfil.php"<?=$active==='account'?' aria-current="page"':''?>>Conta</a></nav><?php endif;?>
        <span class="student-topbar-spacer"></span>
        <div aria-label="Tema" class="theme-switch student-theme-switch"><button aria-pressed="true" data-theme-value="auto" type="button">Auto</button><button aria-pressed="false" data-theme-value="light" type="button">Claro</button><button aria-pressed="false" data-theme-value="dark" type="button">Escuro</button></div>
        <?php if($student):?><span class="student-user"><?=h((string)$student['name'])?></span><form method="post" action="/aluno/logout.php" class="student-logout-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-logout'))?>"><button class="student-logout" type="submit">Sair</button></form><?php endif;?>
    </div>
</header>
<?php return (string)ob_get_clean();
}

function student_shell_mobile_nav_markup(?array $student,string $active='courses'): string {
    if(!$student)return '';
    ob_start();?><nav class="student-mobile-nav" aria-label="Navegação global da área do aluno"><a href="/aluno/"<?=$active==='courses'?' aria-current="page"':''?>>Meus cursos</a><a href="/aluno/perfil.php"<?=$active==='account'?' aria-current="page"':''?>>Conta</a></nav><?php return (string)ob_get_clean();
}

function student_course_context_markup(array $enrollment,string $active='overview',string $materialUrl=''): string {
    $cohortUuid=trim((string)($enrollment['cohort_uuid']??''));
    if($cohortUuid==='')return '';
    $courseTitle=trim((string)($enrollment['course_title']??$enrollment['canonical_course_title']??$enrollment['workshop_title']??$enrollment['public_title']??'Curso'))?:'Curso';
    $cohortTitle=trim((string)($enrollment['cohort_title']??'Turma'))?:'Turma';
    $status=function_exists('student_enrollment_cohort_status_label')?student_enrollment_cohort_status_label((string)($enrollment['cohort_status']??'active')):'Turma';
    $overview='/aluno/?cohort='.rawurlencode($cohortUuid);
    $material=$materialUrl!==''?$materialUrl:$overview.'#material';
    $tests='/aluno/testes.php?cohort='.rawurlencode($cohortUuid);
    ob_start();?>
<section class="student-course-context" aria-label="Contexto do curso">
    <div class="student-course-context-meta"><a class="student-course-back" href="/aluno/">← Meus cursos</a><span class="student-status"><?=h($status)?></span></div>
    <p class="student-course-context-cohort"><?=h($cohortTitle)?></p>
    <p class="student-course-context-title"><?=h($courseTitle)?></p>
    <nav class="student-course-nav" aria-label="Navegação do curso"><a href="<?=h($overview)?>"<?=$active==='overview'?' aria-current="page"':''?>>Visão geral</a><a href="<?=h($material)?>"<?=$active==='material'?' aria-current="page"':''?>>Material</a><a href="<?=h($tests)?>"<?=$active==='tests'?' aria-current="page"':''?>>Testes</a></nav>
</section>
<?php return (string)ob_get_clean();
}

function student_shell_start(string $title,?array $activity=null,?array $student=null): void {
    $assetVersion=student_shell_asset_version();
    $resolvedActivity=student_shell_activity($activity,$student);
    $design=$resolvedActivity?cms_design_settings(database(),(int)$resolvedActivity['id'],PUBLIC_LOCALE_PT_BR):cms_design_defaults();
    $active=student_shell_global_active();
    ?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow,noarchive,nosnippet"><meta name="theme-color" content="<?=h((string)($design['colors']['bg']??'#f2f2ef'))?>"><style data-cms-design-fonts><?=cms_design_font_import_css($design)?></style><link rel="stylesheet" href="/template/page.css?v=<?=h($assetVersion)?>"><style data-cms-design-tokens><?=cms_design_css($design)?></style><link rel="stylesheet" href="/assets/ui-core.css?v=<?=h($assetVersion)?>"><link rel="stylesheet" href="/assets/student-area.css?v=<?=h($assetVersion)?>"><link rel="stylesheet" href="/assets/student-shell.css?v=<?=h($assetVersion)?>"><title><?=h($title)?></title></head><body class="student-page"><?=student_shell_topbar_markup($student,$active)?><div class="student-shell"><main class="student-main"><?php
}

function student_shell_end(): void {
    $student=function_exists('student_account_current')?student_account_current(database()):null;
    $active=student_shell_global_active();
    $assetVersion=student_shell_asset_version();
    ?></main></div><?=student_shell_mobile_nav_markup($student,$active)?><script defer src="/assets/ui-core.js?v=<?=h($assetVersion)?>"></script></body></html><?php
}
