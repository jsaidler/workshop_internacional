<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/student_workbench.php';
require_once dirname(__DIR__).'/app/student_process_templates.php';
require_once dirname(__DIR__).'/app/student_process_lab_navigation.php';
require_once dirname(__DIR__).'/app/student_process_template_step_editor.php';
require_once dirname(__DIR__).'/app/student_process_template_editor_view.php';
function h(string $value): string {return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function student_process_template_href(int $id,int $testId=0,int $stepId=0): string {
    return '/aluno/processamentos.php?id='.$id.($testId>0?'&test='.$testId:'').($stepId>0?'&step='.$stepId:'');
}
function verify_template_editor(bool $okay,string $message): void {
    if(!$okay){fwrite(STDERR,"student-process-template-editor: ".$message."\n");exit(1);}
}
function render_template_editor(array $steps,?array $editing=null,int $testId=0,string $name='Roteiro de teste'):string {
    ob_start();
    student_process_render_template_editor(['id'=>14,'name'=>$name,'description'=>'Descrição sintética'],
        $steps,$editing,student_process_stage_catalog(),student_process_developer_catalog(),[['id'=>9,'label'=>'Parodinal 10/550']],
        'synthetic-csrf',$testId,'19:30 + etapas livres');
    return (string)ob_get_clean();
}
$keys=['first_development','wash_after_first','ferric','wash_after_bleach','ammonia','wash_after_ammonia','second_development','final_wash','dry'];
$stages=student_process_stage_catalog();
$steps=[];
foreach($keys as $i=>$key){
    $payload=$key==='second_development'?['reuse_source_stage_key'=>'first_development','developer_key'=>'parodinal','developer_name'=>'Parodinal']:[];
    $steps[]=['id'=>$i+31,'stage_key'=>$key,'label'=>(string)$stages[$key]['label'],
        'duration'=>$i===0||$i===6?'07:00':'',
        'agitation_interval'=>'','payload_json'=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];
}
$html=render_template_editor($steps,$steps[7]);
verify_template_editor(substr_count($html,'class="student-process-step-card')===9,'must render nine real step cards');
verify_template_editor(str_contains($html,'data-operational-template-editor')&&str_contains($html,'data-stage-key="ferric"'),'component identity and real stages missing');
verify_template_editor(str_contains($html,'name="action" value="move_step"')&&str_contains($html,'name="direction" value="-1"')&&str_contains($html,'name="direction" value="1"'),'up/down must remain POST actions');
foreach(['update_step','add_step','delete_step','duplicate','save','delete'] as $action)
    verify_template_editor(str_contains($html,'name="action" value="'.$action.'"'),'missing POST action '.$action);
verify_template_editor(str_contains($html,'name="step_id" value="38"')&&str_contains($html,'name="action" value="update_step"'),'edit must target selected step');
verify_template_editor(str_contains($html,'data-process-template-step-form')&&str_contains($html,'name="_csrf" value="synthetic-csrf"'),'editor forms lost CSRF');
verify_template_editor(str_contains($html,'Mesmo banho da 1ª revelação'),'planned bath reuse missing');
verify_template_editor(str_contains($html,'Abrir no laboratório')&&!str_contains($html,'Iniciar no laboratório'),'route is not automatic initiation');
verify_template_editor(str_contains($html,'href="/aluno/processamentos.php?id=14&amp;step=38#editar-etapa"')||str_contains($html,'href="/aluno/processamentos.php?id=14&amp;step=38')||str_contains($html,'href="/aluno/processamentos.php?id=14&amp;step=38'), 'edit link missing real template/step identity');
verify_template_editor(!preg_match('~</(?:div|label|details|form)(?!>)~i',$html),'unterminated closing tag');
$empty=render_template_editor([]);
verify_template_editor(str_contains($empty,'Este roteiro ainda está vazio.')&&str_contains($empty,'<details class="student-process-add" open'),'empty templates must expose add control');
verify_template_editor(!str_contains($empty,'Abrir no laboratório'),'empty route must not allow runner');
$association=render_template_editor($steps,null,7);
verify_template_editor(str_contains($association,'Associar ao registro')&&str_contains($association,'name="action" value="apply_template"'),'association must remain explicit POST');
verify_template_editor(!str_contains($association,'Duplicar processamento'),'record context must not offer personal duplication');
$unsafe=render_template_editor($steps,null,0,'<script>unsafe</script>');
verify_template_editor(!str_contains($unsafe,'<script>unsafe</script>')&&str_contains($unsafe,'&lt;script&gt;unsafe&lt;/script&gt;'),'template name must be escaped');
$root=dirname(__DIR__);$route=(string)file_get_contents($root.'/aluno/processamentos.php');
$fixture=(string)file_get_contents($root.'/tools/browser-fixture/student-process-editor.php');
verify_template_editor(str_contains($route,"require_once __DIR__.'/../app/student_process_template_editor_view.php'")&&str_contains($route,'student_process_render_template_editor('),'authenticated controller must compose shared view');
verify_template_editor(str_contains($route,"verify_csrf('student-process-templates'")&&str_contains($route,'student_process_template_for_student($db,$id,$studentId)'),'controller must retain CSRF and ownership');
verify_template_editor(str_contains($fixture,"require_once __DIR__.'/../../app/student_process_template_editor_view.php'")&&str_contains($fixture,'student_process_render_template_editor('),'fixture must call same view');
verify_template_editor(str_contains($fixture,"REQUEST_METHOD")&&str_contains($fixture,'405'),'fixture must reject mutations');
echo "student-process-template-editor: ok\n";
