<?php
declare(strict_types=1);
/* Real PHP form renderers; synthetic data and POST simulation, no auth/DB. */
require_once __DIR__.'/../../app/student_workbench.php';
require_once __DIR__.'/../../app/student_preparation_editor.php';
require_once __DIR__.'/../../app/student_calibration_editor.php';
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

$kind=(string)($_GET['kind']??'preparation');
if(!in_array($kind,['preparation','calibration'],true))exit('Invalid fixture kind');
$mode=(string)($_GET['mode']??'list');
$isPost=($_SERVER['REQUEST_METHOD']??'GET')==='POST';
$path=$kind==='preparation'?'/aluno/preparos.php':'/aluno/calibracao.php';
if($isPost&&$mode!=='error'){
    header('Location: /tools/browser-fixture/student-long-editors.php?kind='.$kind.'&mode=updated',true,303);
    exit;
}
$edit=$mode==='edit'||($mode==='error'&&(int)($_POST['id']??0)>0);
$error=$mode==='error'?'Não foi possível salvar os dados. Confira o formulário.':'';
$prep=$edit?['id'=>7,'label'=>'Parodinal 10/550 · EI 200','developer_key'=>'parodinal','developer_name'=>'Parodinal','developer_amount'=>'10','water_amount'=>'540','temperature'=>'26 °C','duration'=>'07:00','agitation'=>'leve','ingredients_json'=>'[]']:null;
$cal=$edit?['id'=>7,'label'=>'Fuji HR-U · Parodinal','film'=>'Fuji HR-U','developer'=>'Parodinal','preparation'=>'15/550','temperature'=>'26 °C','prebath'=>'Sim','white_time'=>'03:00','notes'=>'Teste']:null;
$draft=$isPost?$_POST:($mode==='error'?($kind==='preparation'?['label'=>'Teste <script>','developer_key'=>'other','developer_name'=>'Revelador experimental','developer_amount'=>'25','water_amount'=>'400','temperature'=>'30 °C','duration'=>'06:00','agitation'=>'Rotação']:['label'=>'Teste <script>','film'=>'Fuji HR-U','developer'=>'Outro','preparation'=>'25/550','temperature'=>'30 °C','prebath'=>'Não','white_time'=>'04:30','notes'=>'Rascunho']):[]);
$title=$kind==='preparation'?'Predefinições de revelação':'Referências de calibração';
$heading=$kind==='preparation'?'Editar predefinição':'Editar calibração';
$_SERVER['REQUEST_URI']=$path;
student_shell_start($title,null,['id'=>1,'name'=>'Aluno']);
?>
<?php if(in_array($mode,['list','updated'],true)):?>
<div class="student-appbar"><a class="student-back" href="/aluno/ferramentas.php">← Laboratório</a></div>
<div class="student-page-heading"><div><p class="student-kicker">Laboratório · parâmetros reutilizáveis</p><h1 class="student-title"><?=$title?></h1></div><a class="button button-primary" href="<?=$path?>?novo=1" data-student-editor-link><?=$kind==='preparation'?'Nova predefinição':'Nova referência'?></a></div>
<?php if($mode==='updated'):?><p class="ui-alert ui-alert-notice" role="status">Dados atualizados.</p><?php endif;?>
<dialog class="student-create-dialog" data-student-editor-dialog><div data-student-editor></div></dialog>
<section class="student-section" data-student-local-key="<?=$kind==='preparation'?'preparations-list':'calibrations-list'?>"><h2><?=$kind==='preparation'?'Sua biblioteca':'Referências'?></h2>
<article class="<?=$kind==='preparation'?'student-preparation-card':'student-simple-row'?>"><div><h3><?=$mode==='updated'?'Registro atualizado':'Registro existente'?></h3></div><a class="button button-secondary button-compact" href="<?=$path?>?editar=7" data-student-editor-link>Editar</a></article>
</section>
<?php else:?>
<div class="student-appbar"><a class="student-back" href="<?=$path?>">← <?=$title?></a></div>
<?php if($kind==='preparation'):
$ingredients=[];if($prep){$ingredients=['café'=>'','carbonato'=>'','ascórbico'=>''];}
student_preparation_editor($prep,student_process_developer_catalog(),$ingredients,$error,'fixture-csrf',$draft);
else:student_calibration_editor($cal,$error,'fixture-csrf',$draft);endif;?>
<?php endif;?>
<?php student_shell_end(); ?>
