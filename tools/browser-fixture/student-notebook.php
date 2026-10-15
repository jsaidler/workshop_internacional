<?php
declare(strict_types=1);
require_once __DIR__.'/../../app/cms_settings.php';
$fixtureDesign=cms_design_defaults();
/** Renders the actual authenticated notebook index with controlled fixture data. */
require_once __DIR__.'/../../app/student_shell.php';
require_once __DIR__.'/../../app/student_record_create_dialog.php';
function database():PDO {static $db=null;return $db??=new PDO('sqlite::memory:');}
function student_tools_for_student(PDO $db,int $id):array {return [['tool_key'=>'reciprocity'],['tool_key'=>'lab_timer']];}
function h(mixed $x):string{return htmlspecialchars((string)$x,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $context):string{return 'fixture-csrf';}
function student_experience_count(int $number,string $singular,string $plural):string{return $number.' '.($number===1?$singular:$plural);}
function student_process_steps(PDO $db,int $id):array{return $id===12?[['id'=>3],['id'=>4],['id'=>5]]:[];}
function student_process_plan_for_test(PDO $db,int $id,int $studentId):?array{if(str_starts_with((string)($_GET['state']??''),'derived'))return null;return $id===14?['id'=>3,'source_name'=>'Revelação reversível']:null;}
function student_process_plan_steps(PDO $db,int $id):array{return [['status'=>'completed'],['status'=>'pending']];}
function student_test_media(PDO $db,int $id):array{return [];}
function student_test_media_by_phase(array $rows,string $phase):array{return [];}
function student_research_source(PDO $db,array $record,int $studentId):?array{
    $state=(string)($_GET['state']??'mixed');
    if($state==='derived-no-source')return null;
    return (int)$record['id']===14?['id'=>12,'title'=>str_starts_with($state,'derived')?'EI 400 — FeCl₃':'Primeiro teste com RX']:null;
}
function student_process_date(string $date):string{return '10/10/2026';}
function student_notebook_has_exposure(array $r,array $media):bool{return !empty($r['exposure_recorded']);}
function student_notebook_has_result(array $r,array $media):bool{return !empty($r['result_recorded']);}
function student_notebook_process_summary(array $steps,?array $plan,array $planSteps):string {
    return $steps?'Processamento: '.count($steps).' etapas registradas':($plan?'Roteiro associado: '.$plan['source_name']:'Processamento ainda não registrado');
}
function student_test_visibility_label(string $v):string{return match($v){'cohort'=>'Turma','course'=>'Curso',default=>'Privado'};}
$state=(string)($_GET['state']??'mixed');
if(!in_array($state,['empty','personal','mixed','shared','many','error','derived','derived-no-source','derived-no-intent'],true)){http_response_code(404);exit;}
$studentId=1;$db=database();$notice=$state==='error'?'':'';
$error=$state==='error'?'Informe um título para continuar.':'';$failedCreate=$state==='error';
$enrollments=[['cohort_id'=>101,'course_title'=>'Positivo direto em filme de raio-X','cohort_title'=>'Turma Outubro 2026']];
if($state==='personal'||$state==='empty')$enrollments=[];
$sample=[
    ['id'=>12,'title'=>'Primeiro teste com RX','context_scope'=>'course','context_course_title'=>'Positivo direto em filme de raio-X','context_cohort_title'=>'Turma Outubro 2026','visibility'=>'cohort','updated_at'=>'2026-10-10T10:00:00Z','test_date'=>'2026-10-10','exposure_recorded'=>true,'result_recorded'=>false,'research_intent'=>''],
    ['id'=>13,'title'=>'Exposição em luz natural','context_scope'=>'personal','visibility'=>'private','updated_at'=>'2026-10-09T11:00:00Z','test_date'=>'2026-10-09','exposure_recorded'=>true,'result_recorded'=>true,'research_intent'=>''],
    ['id'=>14,'title'=>'Comparação do branqueamento','context_scope'=>'course','context_course_title'=>'Positivo direto em filme de raio-X','context_cohort_title'=>'Turma Outubro 2026','visibility'=>'private','updated_at'=>'2026-10-08T12:00:00Z','test_date'=>'2026-10-08','exposure_recorded'=>false,'result_recorded'=>true,'research_intent'=>'Verificar a densidade']
];
if(str_starts_with($state,'derived')){
    // The notebook index renderer is the same PHP used in production.
    // Synthetic lineage follows the canonical research-source service contract.
    $sample[0]['title']='EI 400 — FeCl₃';
    $sample[2]['title']='Variação de EI 400 — FeCl₃';
    $sample[2]['research_intent']=$state==='derived-no-intent'?'':'Manter EI 400 e voltar a primeira revelação para 7 minutos.';
    $sample[2]['exposure_recorded']=true; // Fork inherits an exposure baseline, not physical processing facts.
    $sample[2]['result_recorded']=false;
}
$records=match($state){
  'empty'=>[],
  'personal'=>[$sample[1]],
  'derived','derived-no-source','derived-no-intent'=>[$sample[2],$sample[0]],
  'many'=>array_merge($sample,$sample,$sample,$sample),
  default=>$sample
};
$shared=$state==='shared'?[['id'=>44,'title'=>'Positivo de placa 13×18','visibility'=>'cohort','student_name'=>'Colega','cohort_title'=>'Turma Outubro 2026']]:[];
?><!doctype html><html lang="pt-BR" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style data-cms-design-fonts><?=cms_design_font_import_css($fixtureDesign)?></style>
<link rel="stylesheet" href="/template/page.css"><style data-cms-design-tokens><?=cms_design_css($fixtureDesign)?></style><link rel="stylesheet" href="/assets/ui-core.css"><link rel="stylesheet" href="/assets/student-area.css"><link rel="stylesheet" href="/assets/student-workbench.css"><link rel="stylesheet" href="/assets/student-experience.css"><link rel="stylesheet" href="/assets/student-academic.css"><link rel="stylesheet" href="/assets/student-caderno.css"><link rel="stylesheet" href="/assets/student-rendered-fixes.css"><link rel="stylesheet" href="/assets/student-quality-pass.css">
<title>Caderno · teste de renderização</title></head>
<body class="student-page student-app" data-notebook-state="<?=h($state)?>"><div class="student-shell">
<?php student_shell_nav('notebook','student-desktop-nav');?>
<header class="student-topbar"><a class="brand student-wordmark" href="/aluno/"><span class="student-brand-compact">JS<span>.</span></span><span class="student-brand-full">JSaidler Fotografia</span></a><span class="student-appbar-divider"></span><strong class="student-appbar-title">Caderno</strong><span class="student-topbar-spacer"></span><button class="button button-secondary button-compact student-tools-trigger" type="button" data-toolbox-open>Atalhos</button><details class="student-user-menu"><summary aria-label="Conta"><span class="student-account-initial">J</span><span class="student-account-label">Conta</span></summary><div class="student-user-menu-panel"><button type="button" class="student-tools-menu-trigger" data-toolbox-open>Ferramentas rápidas</button></div></details></header>
<?php student_shell_toolbox(['id'=>1]);?>
<main class="student-main" data-student-scroll-root><?php require __DIR__.'/../../app/student_notebook_view.php';?></main>
<?php student_shell_nav('notebook','student-mobile-nav');?></div>
<script defer src="/assets/student-workbench.js"></script><script defer src="/assets/student-experience.js"></script>
</body></html>