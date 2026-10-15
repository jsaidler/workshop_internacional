<?php
declare(strict_types=1);
/* Real PHP form renderers; synthetic data and POST simulation, no auth/DB. */
require_once __DIR__.'/../../app/student_workbench.php';
require_once __DIR__.'/../../app/student_process_saved_card.php';

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



$mode=(string)($_GET['mode']??'library');
if(!in_array($mode,['library','empty','association'],true)){http_response_code(400);exit('Invalid scenario');}
$_SERVER['REQUEST_URI']='/aluno/processamentos.php';
student_shell_start('Processamentos',null,['id'=>1,'name'=>'Aluno']);
?>
<header class="student-page-heading student-process-library-heading"><div>
<p class="student-kicker">Laboratório</p><h1 class="student-title">Processamentos</h1>
<p class="student-process-context">Salve e reutilize os roteiros que você usa no laboratório.</p></div></header>
<section class="student-process-section" aria-labelledby="student-process-owned-title">
<div class="student-process-section-heading"><div><p class="student-kicker">Sua biblioteca</p><h2 class="student-subtitle" id="student-process-owned-title">Meus processamentos</h2></div></div>
<div class="student-process-library" aria-label="Processamentos salvos">
<?php
$item=['id'=>14,'name'=>'Positivo direto — FeCl₃ + amônia','description'=>'Parodinal 10/550, EI 200. Segundo banho reutilizado.','step_count'=>9];
student_process_saved_card($item,'/aluno/processamentos.php?id=14','19:30','csrf-simulado',$mode==='association'?7:0);
if($mode==='library')student_process_saved_card(['id'=>15,'name'=>'Brewed Caffenol experimental','description'=>'Roteiro pessoal de comparação','step_count'=>7],'/aluno/processamentos.php?id=15','15:30','csrf-simulado',0);
if($mode==='empty')student_process_saved_card(['id'=>16,'name'=>'Novo roteiro sem etapas','description'=>'','step_count'=>0],'/aluno/processamentos.php?id=16','Sem tempos','csrf-simulado',0);
?>
</div></section>
<?php student_shell_end(); ?>
