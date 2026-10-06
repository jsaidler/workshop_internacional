<?php
declare(strict_types=1);
function fail_student_process_manager(string $message): never {fwrite(STDERR,"student-process-manager: $message\n");exit(1);}
function must_student_process_manager(bool $ok,string $message): void {if(!$ok)fail_student_process_manager($message);}
$root=dirname(__DIR__);require_once $root.'/app/student_process_templates.php';
$migration79=(string)file_get_contents($root.'/migrations/079_student_process_templates_and_runner.php');$migration81=(string)file_get_contents($root.'/migrations/081_process_global_versioning_and_execution_state.php');$migration92=(string)file_get_contents($root.'/migrations/092_student_process_step_timers.php');
$domain=(string)file_get_contents($root.'/app/student_process_templates.php');$standards=(string)file_get_contents($root.'/app/student_process_standards.php');$global=(string)file_get_contents($root.'/app/student_global_processes.php');$catalog=(string)file_get_contents($root.'/app/student_process_catalogs.php');
$notebook=(string)file_get_contents($root.'/app/student_process_notebook.php');$free=(string)file_get_contents($root.'/app/student_process_notebook_free.php');$replan=(string)file_get_contents($root.'/app/student_process_replanning.php');$runPage=(string)file_get_contents($root.'/aluno/processar.php');$recordPage=(string)file_get_contents($root.'/aluno/teste.php');$manager=(string)file_get_contents($root.'/aluno/processamentos.php');$bootstrap=(string)file_get_contents($root.'/app/bootstrap.php');

must_student_process_manager(student_process_time_seconds('7:00')===420,'7:00 must parse to seven minutes');
must_student_process_manager(student_process_time_seconds('5:00')===300,'5:00 must parse to five minutes');
must_student_process_manager(student_process_time_seconds('7 min')===420,'human minute duration must remain compatible');
must_student_process_manager(student_process_time_seconds('01:02:03')===3723,'hour clock parsing failed');
must_student_process_manager(student_process_time_seconds('abc')===null,'invalid duration must not become a timer');
must_student_process_manager(student_process_seconds_label(420)==='07:00','duration formatting failed');

$expectedKeys=['positive-ferric-ammonia-ei200','positive-ferric-ammonia-ei400','positive-peracetic-ei200','positive-peracetic-ei400','positive-ferric-ammonia-ei400-caffenol','positive-peracetic-ei400-caffenol'];foreach($expectedKeys as $key)must_student_process_manager(str_contains($migration81,"'$key'"),'global-process seed missing '.$key);
foreach(['student_process_templates','student_process_template_steps','student_process_plans','student_process_plan_steps'] as $table)must_student_process_manager(str_contains($migration79,'CREATE TABLE IF NOT EXISTS '.$table),'missing process table '.$table);
must_student_process_manager(str_contains($migration92,'CREATE TABLE IF NOT EXISTS student_process_step_timers'),'independent step timer table missing');
must_student_process_manager(str_contains($migration92,'plan_step_id INTEGER PRIMARY KEY'),'timer is not keyed independently by route step');
must_student_process_manager(str_contains($standards,'student_global_process_catalog(database())'),'runtime standards are not DB-backed');
must_student_process_manager(str_contains($global,"status='published'")&&str_contains($global,'active_version_id'),'published process version authority missing');
must_student_process_manager(str_contains($catalog,'student_process_stage_catalog')&&str_contains($catalog,'student_process_developer_catalog'),'managed process catalogs missing');
must_student_process_manager(str_contains($domain,'student_process_template_duplicate')&&str_contains($domain,'student_process_template_update_step'),'saved process editing/duplication missing');
must_student_process_manager(str_contains($domain,"'reuse_source_stage_key'"),'saved process does not preserve bath reuse');

foreach(['student_process_notebook_set_completed','student_process_notebook_update_step','student_process_notebook_timer_state','student_process_notebook_timer_transition','student_process_notebook_timer_retime'] as $fn)must_student_process_manager(str_contains($notebook,'function '.$fn),'notebook process authority missing '.$fn);
must_student_process_manager(!str_contains($notebook,'student_inventory_move'),'route checks/timers/editing must not consume inventory');
must_student_process_manager(str_contains($notebook,"true,'timer'"),'timer reaching zero does not check its own step');
must_student_process_manager(str_contains($free,'student_process_notebook_add_free_step')&&str_contains($free,'student_process_notebook_delete_free_step'),'free notebook steps are missing');
must_student_process_manager(!str_contains($free,'student_process_next_choices')&&!str_contains($free,'student_inventory_move'),'free notebook steps still depend on sequence or inventory side effects');
must_student_process_manager(!str_contains($replan,'student_process_replanning_prefix')&&!str_contains($replan,'pending_step_count'),'route change still rebuilds a sequential process prefix');

foreach(['Estou nesta etapa','Ir para próxima etapa','Concluir processamento','Próxima etapa','Registrar o processamento realizado','$intent'] as $forbidden)must_student_process_manager(!str_contains($runPage,$forbidden),'route tool still governs physical progression: '.$forbidden);
must_student_process_manager(str_contains($runPage,'student_process_notebook_timer_transition')&&str_contains($runPage,'student_process_notebook_set_completed'),'route tool bypasses notebook timer/check authority');
must_student_process_manager(str_contains($runPage,'Marcar como concluída')&&str_contains($runPage,'Desmarcar etapa')&&str_contains($runPage,'Editar dados da etapa'),'route steps are not freely reversible/editable');
must_student_process_manager(str_contains($runPage,'Movimentar estoque'),'route tool does not expose stock as a separate action');
foreach(['Vou revelar agora','Já revelei','Registrar manualmente','Continuar laboratório','intent=live','Próxima etapa'] as $forbidden)must_student_process_manager(!str_contains($recordPage,$forbidden),'record still contains obsolete processing workflow: '.$forbidden);
must_student_process_manager(str_contains($recordPage,'Marcar ✓')&&str_contains($recordPage,'Abrir timer'),'record does not expose independent route-step tools');

must_student_process_manager(str_contains($manager,'Meus processamentos')&&str_contains($manager,'Sua biblioteca'),'saved process library disappeared');
must_student_process_manager(str_contains($manager,'Reutilizar o banho da primeira revelação'),'saved route editor lost explicit bath reuse');
foreach(['student_process_notebook','student_process_notebook_free'] as $service)must_student_process_manager(str_contains($bootstrap,"'$service'"),'bootstrap does not load '.$service);
echo "student-process-manager: ok\n";