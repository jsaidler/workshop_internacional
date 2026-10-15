<?php
declare(strict_types=1);
require_once __DIR__.'/../../app/cms_settings.php';
$fixtureDesign=cms_design_defaults();
/* Same presentation component as /aluno/cursos.php; controlled projection data only. */
require_once __DIR__.'/../../app/student_shell.php';
function database():PDO {static $db=null;return $db??=new PDO('sqlite::memory:');}
function student_tools_for_student(PDO $db,int $id):array {return [['tool_key'=>'reciprocity'],['tool_key'=>'lab_timer']];}
function h(mixed $value):string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function student_enrollment_cohort_status_label(string $status):string {return $status==='closed'?'Turma encerrada':'Turma ativa';}
function student_feedback_state(array $row):array {return ['label'=>'Revisão solicitada'];}
function student_experience_count(int $n,string $singular,string $plural):string {return $n.' '.($n===1?$singular:$plural);}
function student_course_release_when(string $value):string {$ts=strtotime($value);return $ts===false?'Agendada':'Agendada para '.date('d/m/Y · H:i',$ts);}
$state=(string)($_GET['state']??'detail');
if(!in_array($state,['empty','single','multiple','detail','no-material','partial','attention'],true)){http_response_code(404);exit;}
$enrollments=[
 ['cohort_uuid'=>'cohort-a','cohort_status'=>'active','course_title'=>'Positivo direto em filme de raio-X','cohort_title'=>'Turma Outubro 2026'],
 ['cohort_uuid'=>'cohort-b','cohort_status'=>'closed','course_title'=>'O Fazer Intuitivo','cohort_title'=>'Turma Setembro 2026'],
];
if($state==='empty')$enrollments=[];
elseif($state==='single')$enrollments=[$enrollments[0]];
$selectedEnrollment=in_array($state,['multiple','empty'],true)?null:($enrollments[0]??null);
$requestedCohort=(string)($_GET['cohort']??'');
if($requestedCohort!==''){
    $selectedEnrollment=null;
    foreach($enrollments as $i)if($i['cohort_uuid']===$requestedCohort)$selectedEnrollment=$i;
}
if($selectedEnrollment && $selectedEnrollment['cohort_uuid']==='cohort-b')$state='no-material';
$materialItems=[];
$releases=[];$pendingLessons=[];
$attention=['has_attention'=>false,'feedback'=>[],'question_count'=>0];
if($selectedEnrollment){
  if($state!=='no-material'){
    $materialItems=[
      ['page'=>['title'=>'Exposição pensando no positivo'],'url'=>'/material/exposicao?cohort='.rawurlencode($selectedEnrollment['cohort_uuid']),'summary'=>['state'=>'available','label'=>'Disponível']],
      ['page'=>['title'=>'Processo químico completo'],'url'=>'/material/processo?cohort='.rawurlencode($selectedEnrollment['cohort_uuid']),'summary'=>['state'=>'partial','label'=>'Parcial']],
      ['page'=>['title'=>'Revisão de resultados'],'url'=>'/material/revisao?cohort='.rawurlencode($selectedEnrollment['cohort_uuid']),'summary'=>['state'=>'scheduled','label'=>'Agendado']],
    ];
    $releases=[['id'=>1,'title'=>'Encontro 3 — revisão dos resultados','released_at'=>'2030-10-18T22:00:00Z']];
    $pendingLessons=[[$releases[0],'scheduled'],[['id'=>2,'title'=>'Material complementar'],'blocked']];
  }
  if($state==='attention'){
    $attention=['has_attention'=>true,'feedback'=>[['id'=>17,'title'=>'Análise da exposição']], 'question_count'=>2];
  }
}
?><!doctype html><html lang="pt-BR" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style data-cms-design-fonts><?=cms_design_font_import_css($fixtureDesign)?></style><style data-cms-design-tokens><?=cms_design_css($fixtureDesign)?></style>
<link rel="stylesheet" href="/template/page.css"><link rel="stylesheet" href="/assets/ui-core.css"><link rel="stylesheet" href="/assets/student-area.css"><link rel="stylesheet" href="/assets/student-workbench.css"><link rel="stylesheet" href="/assets/student-experience.css"><link rel="stylesheet" href="/assets/student-academic.css"><link rel="stylesheet" href="/assets/student-rendered-fixes.css"><link rel="stylesheet" href="/assets/student-quality-pass.css"><link rel="stylesheet" href="/assets/student-app-shell.css"><link rel="stylesheet" href="/assets/student-courses.css">
<title>Cursos · teste de renderização</title></head>
<body class="student-page student-app" data-course-state="<?=h($state)?>"><div class="student-shell">
<?php student_shell_nav('courses','student-desktop-nav');?>
<header class="student-topbar"><a class="brand student-wordmark" href="/aluno/"><span class="student-brand-compact">JS<span>.</span></span><span class="student-brand-full">JSaidler Fotografia</span></a><span class="student-appbar-divider"></span><strong class="student-appbar-title">Cursos</strong><span class="student-topbar-spacer"></span><button class="button button-secondary button-compact student-tools-trigger" type="button" data-toolbox-open>Atalhos</button><details class="student-user-menu"><summary aria-label="Conta"><span class="student-account-initial">J</span><span class="student-account-label">Conta</span></summary><div class="student-user-menu-panel"><button type="button" class="student-tools-menu-trigger" data-toolbox-open>Ferramentas rápidas</button></div></details></header>
<?php student_shell_toolbox(['id'=>1]);?>
<main class="student-main"><?php require __DIR__.'/../../app/student_courses_view.php';?></main>
<?php student_shell_nav('courses','student-mobile-nav');?></div><script defer src="/assets/student-experience.js"></script></body></html>
