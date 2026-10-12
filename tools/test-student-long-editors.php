<?php
declare(strict_types=1);
function h(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function check_long_editor(bool $ok,string $message):void {if(!$ok){fwrite(STDERR,"student-long-editors: $message\n");exit(1);}}
require_once dirname(__DIR__).'/app/student_preparation_editor.php';
require_once dirname(__DIR__).'/app/student_calibration_editor.php';
$catalog=[
 'parodinal'=>['label'=>'Parodinal','mode'=>'concentrate'],
 'other'=>['label'=>'Outro','mode'=>'custom'],
 'brewed-caffenol'=>['label'=>'Caffenol','mode'=>'fresh'],
];
$prep=['id'=>7,'label'=>'Parodinal','developer_key'=>'parodinal','developer_name'=>'Parodinal','developer_amount'=>'10','water_amount'=>'540','temperature'=>'26 °C','duration'=>'07:00','agitation'=>'leve'];
$draft=['label'=>'Erro <script>','developer_key'=>'other','developer_name'=>'Revelador X','developer_amount'=>'20','water_amount'=>'400','temperature'=>'30 °C','duration'=>'05:30','agitation'=>'contínua'];
ob_start();student_preparation_editor($prep,$catalog,[],'Erro <inválido>','token',$draft);$html=(string)ob_get_clean();
check_long_editor(str_contains($html,'data-student-editor')&&str_contains($html,'class="student-long-editor"'),'preparation must use canonical renderable editor');
check_long_editor(str_contains($html,'value="Erro &lt;script&gt;"')&&!str_contains($html,'value="Erro <script>"'),'preparation failed draft escaped/retained');
check_long_editor(str_contains($html,'name="developer_key"')&&str_contains($html,'value="other"')&&str_contains($html,'selected'),'developer selection missing');
check_long_editor(str_contains($html,'data-student-editor-error-focus')&&str_contains($html,'Erro &lt;inválido&gt;'),'preparation error must be local and focusable');
check_long_editor(str_contains($html,'action="/aluno/preparos.php?editar=7"'),'preparation direct route action must be canonical');
check_long_editor(str_contains($html,'href="/aluno/preparos.php"')&&str_contains($html,'>Cancelar</a>'),'dedicated route needs native cancellation');
ob_start();student_preparation_editor(null,$catalog,[],'Erro inválido','token',['label'=>'Nome novo','developer_key'=>'parodinal']);$new=(string)ob_get_clean();
check_long_editor(str_contains($new,'action="/aluno/preparos.php?novo=1"')&&str_contains($new,'value="Nome novo"'),'new preparation loses failed draft/context');
ob_start();student_preparation_editor(null,$catalog,[],'Falha Caffenol','token',['label'=>'Caffenol','developer_key'=>'brewed-caffenol','fresh_volume'=>'650','coffee_amount'=>'45','carbonate_amount'=>'29','ascorbic_amount'=>'10']);$fresh=(string)ob_get_clean();
check_long_editor(str_contains($fresh,'name="fresh_volume" data-fresh-volume-input value="650"')&&str_contains($fresh,'value="45"'),'fresh Caffenol failed draft lost volume or ingredients');
$prepRoute=(string)file_get_contents(dirname(__DIR__).'/aluno/preparos.php');
check_long_editor(str_contains($prepRoute,"if($action==='update')student_saved_preparation_update_guided")&&str_contains($prepRoute,"$_POST['ingredients']="),'Caffenol ingredients must be normalized for update and create');
$cal=['id'=>9,'label'=>'Referência','film'=>'Fuji','developer'=>'Parodinal','preparation'=>'15/550','temperature'=>'26 °C','prebath'=>'Sim','white_time'=>'03:00','notes'=>''];
ob_start();student_calibration_editor($cal,'Falha <POST>','token',['label'=>'Nova <b>','white_time'=>'04:30','notes'=>'Campo <script>']);$edit=(string)ob_get_clean();
check_long_editor(str_contains($edit,'value="Nova &lt;b&gt;"')&&!str_contains($edit,'value="Nova <b>"'),'calibration invalid label lost');
check_long_editor(str_contains($edit,'Campo &lt;script&gt;')&&!str_contains($edit,'Campo <script>'),'calibration notes unsafe or lost');
check_long_editor(str_contains($edit,'action="/aluno/calibracao.php?editar=9"'),'calibration edit route action missing');
check_long_editor(str_contains($edit,'data-student-editor-error-focus')&&str_contains($edit,'Falha &lt;POST&gt;'),'calibration error not in editor');
ob_start();student_calibration_editor(null,'Falha','token',['label'=>'Calibração nova','white_time'=>'05:00']);$newCal=(string)ob_get_clean();
check_long_editor(str_contains($newCal,'action="/aluno/calibracao.php?novo=1"')&&str_contains($newCal,'value="05:00"'),'new calibration failed values missing');
foreach(['aluno/preparos.php','aluno/calibracao.php'] as $p){
 $route=(string)file_get_contents(dirname(__DIR__).'/'.$p);
 check_long_editor(str_contains($route,'if($openEditor)')&&str_contains($route,'student_shell_end()'),'standalone route did not separate editor and list');
 check_long_editor(str_contains($route,'data-student-editor-dialog'),'desktop contextual dialog absent');
 check_long_editor(str_contains($route,'student-back'),'contextual parent return missing');
}
$local=(string)file_get_contents(dirname(__DIR__).'/assets/student-local-actions.js');
check_long_editor(str_contains($local,"form.closest('[data-student-editor]')?.parentElement?.classList.contains('student-main')"),'direct editor still uses list-dependent AJAX');
echo "student-long-editors: ok\n";
