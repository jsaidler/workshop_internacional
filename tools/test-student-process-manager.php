<?php
declare(strict_types=1);
function fail_student_process_manager(string $message): never {fwrite(STDERR,"student-process-manager: $message\n");exit(1);}
function must_student_process_manager(bool $ok,string $message): void {if(!$ok)fail_student_process_manager($message);}
$root=dirname(__DIR__);
require_once $root.'/app/student_process_templates.php';
require_once $root.'/app/student_process_standards.php';
$migration=(string)file_get_contents($root.'/migrations/079_student_process_templates_and_runner.php');
$domain=(string)file_get_contents($root.'/app/student_process_templates.php');
$processUx=(string)file_get_contents($root.'/app/student_process_ux.php');
$standardsSource=(string)file_get_contents($root.'/app/student_process_standards.php');
$runner=(string)file_get_contents($root.'/assets/student-process-runner.js');
$manager=(string)file_get_contents($root.'/aluno/processamentos.php');
$runPage=(string)file_get_contents($root.'/aluno/processar.php');
$notebookBridge=(string)file_get_contents($root.'/assets/student-process-entry.js');
$tools=(string)file_get_contents($root.'/aluno/ferramentas.php');
$bootstrap=(string)file_get_contents($root.'/app/bootstrap.php');
$doc=(string)file_get_contents($root.'/docs/STUDENT_PROCESS_MANAGER_AND_LAB_RUNNER_2026-10-01.md');
$productContract=(string)file_get_contents($root.'/docs/STUDENT_PRODUCT_UX_REDESIGN_CONTRACT_2026-10-01.md');

must_student_process_manager(student_process_time_seconds('7:00')===420,'7:00 must parse to seven minutes');
must_student_process_manager(student_process_time_seconds('7 min')===420,'human minute duration must remain compatible');
must_student_process_manager(student_process_time_seconds('01:02:03')===3723,'hour clock parsing failed');
must_student_process_manager(student_process_time_seconds('abc')===null,'invalid duration must not become a timer');
must_student_process_manager(student_process_seconds_label(420)==='07:00','duration formatting failed');

$standards=student_process_standard_catalog();
$expectedKeys=['positive-ferric-ammonia-ei200','positive-ferric-ammonia-ei400','positive-peracetic-ei200','positive-peracetic-ei400'];
must_student_process_manager(array_keys($standards)===$expectedKeys,'workshop must expose the four current EI/bleach standards');
foreach($standards as $key=>$standard){
    $first=null;$second=null;$hasFerric=false;$hasAmmonia=false;$hasPeracetic=false;
    foreach((array)$standard['steps'] as $step){
        $stage=(string)$step['stage_key'];$duration=(string)($step['duration']??'');
        if(str_starts_with($stage,'wash_')||$stage==='final_wash')must_student_process_manager($duration==='1:00',$key.' wash must default to 1:00');
        if(in_array($stage,['ferric','peracetic'],true))must_student_process_manager($duration==='1:30',$key.' bleach must default to 1:30');
        if($stage==='ammonia')must_student_process_manager($duration==='',$key.' ammonia must remain untimed');
        if($stage==='first_development')$first=$step;
        if($stage==='second_development')$second=$step;
        if($stage==='ferric')$hasFerric=true;
        if($stage==='ammonia')$hasAmmonia=true;
        if($stage==='peracetic')$hasPeracetic=true;
    }
    must_student_process_manager(is_array($first)&&is_array($second),$key.' must contain two development stages');
    $firstProfile=$first;$secondProfile=$second;
    unset($firstProfile['stage_key'],$firstProfile['notes'],$firstProfile['reuse_source_stage_key'],$secondProfile['stage_key'],$secondProfile['notes'],$secondProfile['reuse_source_stage_key']);
    must_student_process_manager($firstProfile===$secondProfile,$key.' second development must exactly mirror first development parameters');
    must_student_process_manager(($second['reuse_source_stage_key']??'')==='first_development',$key.' second development must reference the first developer bath');
    must_student_process_manager(str_contains((string)($second['notes']??''),'Reutilizar o mesmo banho de revelador'),$key.' second development must explain bath reuse');
    must_student_process_manager(($first['developer_key']??'')==='parodinal',$key.' must use Parodinal');
    must_student_process_manager(($first['temperature']??'')==='26 °C',$key.' development temperature must be 26 °C');
    must_student_process_manager(($first['duration']??'')==='7:00',$key.' development time must be seven minutes');
    must_student_process_manager(($first['agitation']??'')==='leve',$key.' development agitation must be light');
    $ei400=str_contains($key,'ei400');
    must_student_process_manager(($first['developer_amount']??'')===($ei400?'20':'10'),$key.' Parodinal amount is wrong');
    must_student_process_manager(($first['water_amount']??'')===($ei400?'530':'540'),$key.' water amount must complete 550 ml');
    if(str_contains($key,'ferric-ammonia'))must_student_process_manager($hasFerric&&$hasAmmonia&&!$hasPeracetic,$key.' ferric route is wrong');
    else must_student_process_manager($hasPeracetic&&!$hasFerric&&!$hasAmmonia,$key.' peracetic route is wrong');
}
must_student_process_manager(student_process_standard_duration_summary($standards['positive-ferric-ammonia-ei200']['steps'])==='19:30 + etapas livres','ferric standard fixed-time summary is wrong');
must_student_process_manager(student_process_standard_duration_summary($standards['positive-peracetic-ei200']['steps'])==='18:30 + etapas livres','peracetic standard fixed-time summary is wrong');
must_student_process_manager(str_contains($standardsSource,"'water_amount'=>(string)(550-\$developerAmount)"),'standard dilution must encode water as the complement to 550 ml');
must_student_process_manager(str_contains($standardsSource,"['reuse_source_stage_key']='first_development'"),'standard second development must carry machine-readable bath reuse');
must_student_process_manager(str_contains($standardsSource,"'reuse_source_stage_key'] as"),'standard copy must persist bath reuse metadata');

must_student_process_manager(str_contains($bootstrap,"'student_process_standards'"),'standard process catalog is not loaded by bootstrap');
must_student_process_manager(str_contains($domain,'student_process_template_duplicate'),'saved process duplication is missing');
must_student_process_manager(str_contains($domain,'student_process_template_update_step'),'saved process step editing is missing');
must_student_process_manager(str_contains($domain,"'reuse_source_stage_key'"),'saved process payload does not preserve bath reuse');
must_student_process_manager(str_contains($processUx,"&&\$reuseSource===''"),'reused bath must be excluded from inventory consumption');
must_student_process_manager(str_contains($processUx,"'reuse_source_stage_key'=>\$data['reuse_source_stage_key']"),'executed step must persist reuse metadata');

must_student_process_manager(str_contains($manager,"action==='copy_standard'")&&str_contains($manager,'Padrões do workshop'),'process manager does not expose workshop standards');
must_student_process_manager(str_contains($manager,"action==='duplicate'")&&str_contains($manager,'Duplicar processamento'),'process manager does not expose process duplication');
must_student_process_manager(str_contains($manager,"action==='update_step'")&&str_contains($manager,'Salvar etapa'),'process manager does not expose step editing');
must_student_process_manager(str_contains($manager,'Meus processamentos')&&str_contains($manager,'Sua biblioteca'),'process manager is not library-first');
must_student_process_manager(str_contains($manager,'segunda revelação reutiliza o mesmo banho de Parodinal da primeira'),'process manager does not teach bath reuse');
must_student_process_manager(str_contains($manager,'Reutilizar o banho da primeira revelação'),'step editor does not expose bath reuse choice');
must_student_process_manager(str_contains($manager,'Excluir processamento')&&str_contains($manager,'Excluir este processamento salvo?'),'process deletion is not discoverable');
must_student_process_manager(str_contains($manager,'student_process_template_duration_summary'),'saved templates still present partial timed sums as complete totals');

foreach(['student_process_templates','student_process_template_steps','student_process_plans','student_process_plan_steps'] as $table)must_student_process_manager(str_contains($migration,'CREATE TABLE IF NOT EXISTS '.$table),'missing migration table '.$table);
must_student_process_manager(str_contains($domain,'student_process_plan_apply_template')&&str_contains($domain,'payload_json'),'template snapshot authority is missing');
must_student_process_manager(str_contains($domain,'student_process_add_flexible_step'),'runner completion no longer writes through actual process-step authority');
must_student_process_manager(str_contains($domain,"student_process_steps(\$db,\$testId)"),'template can be applied over already executed laboratory history');
must_student_process_manager(str_contains($runner,"navigator.wakeLock.request('screen')"),'Screen Wake Lock is missing');
must_student_process_manager(str_contains($runner,'state.endAt-Date.now()'),'timer is not derived from an absolute end timestamp');
must_student_process_manager(!preg_match('/remaining\s*[-]{2}|remaining\s*=\s*remaining\s*-\s*1/',$runner),'timer regressed to decrement-per-tick timing');
must_student_process_manager(str_contains($runner,"root.dataset.finalStage==='1'")&&str_contains($runPage,'data-final-stage'),'final stage does not end the wake-lock session');
must_student_process_manager(str_contains($runPage,'Reutilize o banho da primeira revelação')&&str_contains($runPage,'não registre novo consumo'),'runner does not surface the reuse instruction');
must_student_process_manager(str_contains($manager,'Usar neste registro')&&str_contains($manager,'Iniciar no laboratório'),'process manager is not shared by notebook and standalone runner');
must_student_process_manager(str_contains($notebookBridge,'/aluno/processamentos.php?test='),'notebook does not expose reusable process selection');
must_student_process_manager(!str_contains($tools,'data-lab-timer'),'standalone timer is still the primary tools UI');
must_student_process_manager(str_contains($doc,'mesmo banho de revelador preparado para a primeira revelação é reaproveitado na segunda'),'developer reuse is not documented canonically');
must_student_process_manager(str_contains($doc,'template → snapshot do registro → execução real'),'architecture decision is not documented');
must_student_process_manager(str_contains($productContract,'inspeção visual real em desktop e mobile antes de merge'),'product UX visual inspection contract is missing');
echo "student-process-manager: ok\n";
