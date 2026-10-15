<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/student_workbench.php';
require_once dirname(__DIR__).'/app/student_process_templates.php';
require_once dirname(__DIR__).'/app/student_process_lab_navigation.php';
require_once dirname(__DIR__).'/app/student_process_template_step_editor.php';
function h(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function student_process_template_href(int $id,int $testId=0,int $stepId=0):string{return '/aluno/processamentos.php?id='.$id.($testId>0?'&test='.$testId:'').($stepId>0?'&step='.$stepId:'');}
function verify_step_editor(bool $okay,string $message):void{
 if(!$okay){fwrite(STDERR,"student-process-step-editor: ".$message."\n");exit(1);}
}
function show_step(?array $step=null):string{
 ob_start();student_process_render_step_form(student_process_stage_catalog(),student_process_developer_catalog(),[['id'=>9,'label'=>'Parodinal · 10/550']],'test-csrf',14,0,$step);
 return (string)ob_get_clean();
}
$new=show_step();
verify_step_editor(substr_count($new,'<form')===1,'form must have one POST boundary');
verify_step_editor(!preg_match('~</(?:div|label|details|form)(?!>)~i',$new),'form HTML contains an unterminated closing element');
verify_step_editor(str_contains($new,'data-process-template-stage')&&str_contains($new,'data-process-template-development'),'conditional stage model lost');
verify_step_editor(str_contains($new,'name="_csrf"')&&str_contains($new,'name="template_id"'),'CSRF/template identity lost');
verify_step_editor(str_contains($new,'name="duration"')&&str_contains($new,'name="developer_amount"')&&str_contains($new,'name="water_amount"'),'time/volume fields lost');
verify_step_editor(strpos($new,'name="duration"')<strpos($new,'Agitação e observações'),'time cannot be buried after optional agitation');
verify_step_editor(substr_count($new,'<details class="student-process-task-disclosure ')===2,'must expose exactly two optional detail groups');
verify_step_editor(str_contains($new,'data-process-template-other-developer hidden'),'other developer must begin conditional');
$payload=['developer_key'=>'parodinal','developer_amount'=>'10','water_amount'=>'540','temperature'=>'26 °C','agitation_mode'=>'periodic','agitation_duration'=>'00:10','agitation_interval'=>'01:00','agitation'=>'Inversões','notes'=>'Observado','reuse_source_stage_key'=>'first_development'];
$edit=show_step(['id'=>16,'stage_key'=>'second_development','duration'=>'07:00','agitation_interval'=>'01:00','payload_json'=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
verify_step_editor(str_contains($edit,'value="second_development" selected'),'stage editing selection lost');
verify_step_editor(str_contains($edit,'name="reuse_source_stage_key" value="first_development" checked'),'reuse must persist');
verify_step_editor(substr_count($edit,'student-process-task-disclosure student-span-2" open')===2,'existing detailed values should open corresponding sections');
verify_step_editor(str_contains($edit,'name="agitation_interval" value="01:00"')&&str_contains($edit,'name="notes"'),'agitation and notes kept');
verify_step_editor(str_contains($edit,'name="step_id" value="16"'),'editable item identity lost');
$actualRoute=(string)file_get_contents(dirname(__DIR__).'/aluno/processamentos.php');
verify_step_editor(str_contains($actualRoute,"require_once __DIR__.'/../app/student_process_template_step_editor.php'"),'authenticated page must use same renderer as fixture');
verify_step_editor(!str_contains($actualRoute,'function student_process_render_step_form'),'route contains a diverging local form');
echo "student-process-step-editor: ok\n";
