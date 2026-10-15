<?php
declare(strict_types=1);
require_once __DIR__.'/../../app/cms_settings.php';
/* Visual-only projection of the same template editor as /aluno/processamentos.php.
   Values and identities are synthetic. No authenticated write or persistence occurs here. */
require_once __DIR__.'/../../app/student_workbench.php';
require_once __DIR__.'/../../app/student_process_templates.php';
require_once __DIR__.'/../../app/student_process_lab_navigation.php';
require_once __DIR__.'/../../app/student_process_template_step_editor.php';
require_once __DIR__.'/../../app/student_process_template_editor_view.php';
require_once __DIR__.'/../../app/student_shell.php';
function h(string $value): string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
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
function student_account_current(PDO $db): ?array {return ['id'=>1,'name'=>'Aluno de teste'];}
function csrf_token(string $scope): string {return 'fixture-csrf-no-write';}
function student_process_template_href(int $id,int $testId=0,int $stepId=0):string {
    return '/aluno/processamentos.php?id='.$id.($testId>0?'&test='.$testId:'').($stepId>0?'&step='.$stepId:'');
}
$mode=(string)($_GET['mode']??'edit');
if(!in_array($mode,['edit','read','empty','association','custom'],true)){http_response_code(400);exit('Invalid fixture mode');}
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit('Fixture is read-only');}
$stageKeys=['first_development','wash_after_first','ferric','wash_after_bleach','ammonia','wash_after_ammonia','second_development','final_wash','dry'];
$durations=['07:00','01:00','01:30','01:00','','01:00','07:00','01:00',''];
$stageCatalog=student_process_stage_catalog();
$steps=[];
if($mode!=='empty'){
    foreach($stageKeys as $position=>$key){
        $payload=[];
        if($position===0||$position===6){
            $payload=['developer_key'=>'parodinal','developer_name'=>'Parodinal','developer_amount'=>'10','water_amount'=>'540','temperature'=>'26 °C'];
        }
        if($position===6)$payload['reuse_source_stage_key']='first_development';
        if($position===2)$payload['notes']='Conferir a preparação antes de usar o branqueador';
        if($position===7)$payload['notes']='Lavagem final em água corrente';
        $steps[]=[
            'id'=>$position+31,
            'position'=>$position+1,
            'stage_key'=>$key,
            'label'=>(string)$stageCatalog[$key]['label'],
            'duration'=>$durations[$position],
            'agitation_interval'=>'',
            'payload_json'=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ];
    }
}
if($mode==='custom'&&$steps){
    $steps[7]['stage_key']='custom';
    $steps[7]['label']='Lavagem personalizada';
    $steps[7]['payload_json']=json_encode(['custom_label'=>'Lavagem personalizada','notes'=>'Observação de exemplo'],JSON_UNESCAPED_UNICODE);
}
$template=['id'=>14,'name'=>'Positivo direto — Parodinal EI 200 — FeCl₃ + amônia',
    'description'=>'Parodinal 10 ml + água até 550 ml. Mesmo banho reutilizado na segunda revelação.'];
$testId=$mode==='association'?7:0;
if($testId>0)$_GET['test']=$testId;
$editStep=in_array($mode,['edit','custom'],true)?$steps[7]:null;
$preparations=[['id'=>9,'label'=>'Parodinal · 10/550']];
$csrf=csrf_token('student-process-templates');
$_SERVER['REQUEST_URI']='/aluno/processamentos.php'.($testId>0?'?test='.$testId:'');
student_shell_start('Processamentos',null,['id'=>1,'name'=>'Aluno de teste']);
?>
<header class="student-page-heading student-process-library-heading">
    <div><p class="student-kicker"><?=$testId>0?'Caderno · Processamento':'Laboratório'?></p>
    <h1 class="student-title">Processamentos</h1>
    <p class="student-process-context">Edite este roteiro sem alterar cópias já registradas no Caderno.</p></div>
    <a class="button button-secondary" href="/aluno/processamentos.php<?=$testId>0?'?test='.$testId:''?>">← Meus processamentos</a>
</header>
<?php student_process_render_template_editor($template,$steps,$editStep,$stageCatalog,student_process_developer_catalog(),$preparations,$csrf,$testId,'19:30 + etapas livres'); ?>
<?php student_shell_end(); ?>
