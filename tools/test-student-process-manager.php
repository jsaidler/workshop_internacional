<?php
declare(strict_types=1);
function fail_student_process_manager(string $message): never {fwrite(STDERR,"student-process-manager: $message\n");exit(1);}
function must_student_process_manager(bool $ok,string $message): void {if(!$ok)fail_student_process_manager($message);}
$root=dirname(__DIR__);
require_once $root.'/app/student_process_templates.php';
$migration=(string)file_get_contents($root.'/migrations/079_student_process_templates_and_runner.php');
$domain=(string)file_get_contents($root.'/app/student_process_templates.php');
$runner=(string)file_get_contents($root.'/assets/student-process-runner.js');
$manager=(string)file_get_contents($root.'/aluno/processamentos.php');
$runPage=(string)file_get_contents($root.'/aluno/processar.php');
$notebookBridge=(string)file_get_contents($root.'/assets/student-process-entry.js');
$tools=(string)file_get_contents($root.'/aluno/ferramentas.php');
$doc=(string)file_get_contents($root.'/docs/STUDENT_PROCESS_MANAGER_AND_LAB_RUNNER_2026-10-01.md');

must_student_process_manager(student_process_time_seconds('7:00')===420,'7:00 must parse to seven minutes');
must_student_process_manager(student_process_time_seconds('7 min')===420,'human minute duration must remain compatible');
must_student_process_manager(student_process_time_seconds('01:02:03')===3723,'hour clock parsing failed');
must_student_process_manager(student_process_time_seconds('abc')===null,'invalid duration must not become a timer');
must_student_process_manager(student_process_seconds_label(420)==='07:00','duration formatting failed');

foreach(['student_process_templates','student_process_template_steps','student_process_plans','student_process_plan_steps'] as $table)must_student_process_manager(str_contains($migration,'CREATE TABLE IF NOT EXISTS '.$table),'missing migration table '.$table);
must_student_process_manager(str_contains($domain,'student_process_plan_apply_template')&&str_contains($domain,'payload_json'),'template snapshot authority is missing');
must_student_process_manager(str_contains($domain,'student_process_add_flexible_step'),'runner completion no longer writes through actual process-step authority');
must_student_process_manager(str_contains($domain,"student_process_steps(\$db,\$testId)"),'template can be applied over already executed laboratory history');
must_student_process_manager(str_contains($runner,"navigator.wakeLock.request('screen')"),'Screen Wake Lock is missing');
must_student_process_manager(str_contains($runner,'state.endAt-Date.now()'),'timer is not derived from an absolute end timestamp');
must_student_process_manager(!preg_match('/remaining\s*[-]{2}|remaining\s*=\s*remaining\s*-\s*1/',$runner),'timer regressed to decrement-per-tick timing');
must_student_process_manager(str_contains($runner,"root.dataset.finalStage==='1'")&&str_contains($runPage,'data-final-stage'),'final stage does not end the wake-lock session');
must_student_process_manager(str_contains($manager,'Usar neste registro')&&str_contains($manager,'Iniciar processamento'),'process manager is not shared by notebook and standalone runner');
must_student_process_manager(str_contains($notebookBridge,'/aluno/processamentos.php?test='),'notebook does not expose reusable process selection');
must_student_process_manager(!str_contains($tools,'data-lab-timer'),'standalone timer is still the primary tools UI');
must_student_process_manager(str_contains($doc,'template → snapshot do registro → execução real'),'architecture decision is not documented');
echo "student-process-manager: ok\n";
