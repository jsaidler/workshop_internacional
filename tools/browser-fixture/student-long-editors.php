<?php
declare(strict_types=1);
/* Real PHP form renderers; synthetic data and POST simulation, no auth/DB. */
require_once __DIR__.'/../../app/student_workbench.php';
require_once __DIR__.'/../../app/student_preparation_editor.php';
require_once __DIR__.'/../../app/student_calibration_editor.php';
require_once __DIR__.'/../../app/student_shell.php';
require_once __DIR__.'/../../app/student_lab_catalog_views.php';
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
if(!function_exists('student_experience_count')){function student_experience_count(int $number,string $singular,string $plural): string {return $number.' '.($number===1?$singular:$plural);}}
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
<?php
$openEditor=in_array($mode,['create','edit','error'],true);
$updated=$mode==='updated';
if($kind==='preparation'){
    $rows=[['id'=>7,'label'=>$updated?'Registro atualizado':'Registro existente','developer_name'=>'Parodinal','developer_amount'=>10,'water_amount'=>540,'preparation_mode'=>'concentrate','temperature'=>'26 °C','duration'=>'07:00','agitation'=>'leve']];
    $ingredients=$prep?['café'=>'','carbonato'=>'','ascórbico'=>'']:[];
    student_lab_preparations_view($rows,$prep,student_process_developer_catalog(),$ingredients,$error,$openEditor,$updated,$draft);
}else{
    $rows=[['id'=>7,'label'=>$updated?'Registro atualizado':'Registro existente','film'=>'Fuji HR-U','developer'=>'Parodinal','preparation'=>'10/550','temperature'=>'26 °C','white_time'=>'03:00']];
    student_lab_calibrations_view($rows,$cal,$error,$openEditor,$updated,$draft);
}
student_shell_end(); ?>
