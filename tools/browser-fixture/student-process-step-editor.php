<?php
declare(strict_types=1);
/* Real PHP form renderers; synthetic data and POST simulation, no auth/DB. */
require_once __DIR__.'/../../app/student_workbench.php';
require_once __DIR__.'/../../app/student_process_templates.php';
require_once __DIR__.'/../../app/student_process_lab_navigation.php';
require_once __DIR__.'/../../app/student_process_template_step_editor.php';
require_once __DIR__.'/../../app/student_shell.php';
function h(string $s):string{return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function database(): PDO {
 static $db=null;
 if($db instanceof PDO)return $db;
 $db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 $db->exec('CREATE TABLE student_tools(tool_key TEXT,enabled INTEGER,access_mode TEXT,sort_order INTEGER,label TEXT);
 CREATE TABLE student_tool_courses(tool_key TEXT,course_id INTEGER);
 CREATE TABLE course_cohorts(id INTEGER,course_id INTEGER,status TEXT);
 CREATE TABLE course_enrollments(id INTEGER,cohort_id INTEGER,student_id INTEGER,status TEXT)');
 return $db;
}
function student_account_current(PDO $db): ?array {return ['id'=>1,'name'=>'Aluno'];}
function csrf_token(string $scope): string {return 'fixture-only-token';}
function cms_design_defaults(): array {return ['colors'=>['bg'=>'#101010']];}
function cms_design_font_import_css(array $design): string {return '';}
function cms_design_css(array $design): string {return ':root{--bg:#101010;--surface:#171717;--surface-2:#202020;--text:#f3f1ec;--muted:#aaa49a;--line:#383633;--line-strong:#d5d0c7;--focus:#73d2ac;--inverse-bg:#f3f1ec;--inverse:#101010;--body:Arial,sans-serif;--title:Arial,sans-serif;--mono:"Courier New",monospace;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px;--ux-space-8:64px}';}


function student_process_template_href(int $templateId,int $testId=0,int $stepId=0):string{
 return '/aluno/processamentos.php?id='.$templateId.($testId>0?'&test='.$testId:'').($stepId>0?'&step='.$stepId:'');
}
$mode=(string)($_GET['mode']??'create');
if(!in_array($mode,['create','edit','custom'],true)){http_response_code(400);exit('Invalid mode');}
$step=null;
if($mode==='edit')$step=[
 'id'=>16,'stage_key'=>'second_development',
 'payload_json'=>json_encode(json_decode('{"developer_key":"parodinal","developer_name":"Parodinal","developer_amount":"10","water_amount":"540","temperature":"26 °C","agitation_mode":"periodic","agitation_duration":"00:10","agitation_interval":"01:00","agitation":"Inversões leves","notes":"Reutilização documentada","reuse_source_stage_key":"first_development"}',true),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
 'duration'=>'07:00','agitation_interval'=>'01:00'
];
if($mode==='custom')$step=[
 'id'=>17,'stage_key'=>'custom',
 'payload_json'=>json_encode(['custom_label'=>'Secagem ao ar','notes'=>'Ambiente ventilado'],JSON_UNESCAPED_UNICODE),
 'duration'=>'','agitation_interval'=>''
];
$_SERVER['REQUEST_URI']='/aluno/processamentos.php';
student_shell_start('Editar etapas · Processamentos',null,['id'=>1,'name'=>'Aluno']);
?><div class="student-appbar"><a class="student-back" href="/aluno/processamentos.php">← Processamentos</a></div>
<header class="student-page-heading student-process-library-heading"><div>
<p class="student-kicker">Laboratório · roteiro reutilizável</p>
<h1 class="student-title">Configurar etapa</h1>
</div></header>
<section class="student-process-editor">
<div class="student-process-step-editor" data-operational-process-editor>
<?php
student_process_render_step_form(student_process_stage_catalog(),student_process_developer_catalog(),[['id'=>9,'label'=>'Parodinal · 10/550']],'fixture-csrf',14,0,$step);
?>
</div></section>
<?php student_shell_end(); ?>
