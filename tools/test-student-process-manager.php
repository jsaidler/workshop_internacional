<?php
declare(strict_types=1);
function fail_student_process_manager(string $message): never {fwrite(STDERR,"student-process-manager: $message\n");exit(1);}
function must_student_process_manager(bool $ok,string $message): void {if(!$ok)fail_student_process_manager($message);}
$root=dirname(__DIR__);
require_once $root.'/app/student_process_templates.php';
$migration79=(string)file_get_contents($root.'/migrations/079_student_process_templates_and_runner.php');
$migration81=(string)file_get_contents($root.'/migrations/081_process_global_versioning_and_execution_state.php');
$domain=(string)file_get_contents($root.'/app/student_process_templates.php');
$processUx=(string)file_get_contents($root.'/app/student_process_ux.php');
$standardsSource=(string)file_get_contents($root.'/app/student_process_standards.php');
$globalSource=(string)file_get_contents($root.'/app/student_global_processes.php');
$catalogSource=(string)file_get_contents($root.'/app/student_process_catalogs.php');
$executionSource=(string)file_get_contents($root.'/app/student_process_execution.php');
$runner=(string)file_get_contents($root.'/assets/student-process-runner.js');
$manager=(string)file_get_contents($root.'/aluno/processamentos.php');
$runPage=(string)file_get_contents($root.'/aluno/processar.php');
$recordPage=(string)file_get_contents($root.'/aluno/teste.php');
$notebookBridge=(string)file_get_contents($root.'/assets/student-process-entry.js');
$tools=(string)file_get_contents($root.'/aluno/ferramentas.php');
$bootstrap=(string)file_get_contents($root.'/app/bootstrap.php');
$doc=(string)file_get_contents($root.'/docs/STUDENT_PROCESS_MANAGER_AND_LAB_RUNNER_2026-10-01.md');
$runtimeDoc=(string)file_get_contents($root.'/docs/PROCESS_DOMAIN_RUNTIME_AND_ADMIN_CANONICAL_2026-10-02.md');
$productContract=(string)file_get_contents($root.'/docs/STUDENT_PRODUCT_UX_REDESIGN_CONTRACT_2026-10-01.md');

must_student_process_manager(student_process_time_seconds('7:00')===420,'7:00 must parse to seven minutes');
must_student_process_manager(student_process_time_seconds('5:00')===300,'5:00 must parse to five minutes');
must_student_process_manager(student_process_time_seconds('7 min')===420,'human minute duration must remain compatible');
must_student_process_manager(student_process_time_seconds('01:02:03')===3723,'hour clock parsing failed');
must_student_process_manager(student_process_time_seconds('abc')===null,'invalid duration must not become a timer');
must_student_process_manager(student_process_seconds_label(420)==='07:00','duration formatting failed');

$expectedKeys=[
    'positive-ferric-ammonia-ei200',
    'positive-ferric-ammonia-ei400',
    'positive-peracetic-ei200',
    'positive-peracetic-ei400',
    'positive-ferric-ammonia-ei400-caffenol',
    'positive-peracetic-ei400-caffenol',
];
foreach($expectedKeys as $key)must_student_process_manager(str_contains($migration81,"'$key'"),'global-process seed is missing '.$key);
foreach(['student_process_stage_catalog','student_process_developer_catalog','student_global_processes','student_global_process_versions','student_global_process_version_steps','student_process_execution_sessions','student_process_change_log'] as $table)must_student_process_manager(str_contains($migration81,'CREATE TABLE IF NOT EXISTS '.$table),'migration 081 is missing '.$table);
must_student_process_manager(str_contains($migration81,"ADD COLUMN source_global_version_id"),'plan snapshot does not preserve its global version origin');
foreach(["'26 °C'","'7:00'","'35,7 °C'","'5:00'","'1:30'","'1:00'","'reuse_source_stage_key'=>'first_development'"] as $fragment)must_student_process_manager(str_contains($migration81,$fragment),'migrated workshop routes lost canonical value '.$fragment);
must_student_process_manager(str_contains($migration81,"'developer_amount'=>(string)\$amount")&&str_contains($migration81,"'water_amount'=>(string)(550-\$amount)"),'Parodinal migration must preserve developer amount and water complement to 550 ml');
foreach(['37 g','54 g','20 g','1 L'] as $recipePart)must_student_process_manager(str_contains($migration81,$recipePart),'Caffenol migration note missing '.$recipePart);

must_student_process_manager(str_contains($standardsSource,'student_global_process_catalog(database())'),'runtime standards are not DB-backed');
must_student_process_manager(str_contains($standardsSource,'student_global_process_for_key'),'standard lookup bypasses versioned global processes');
must_student_process_manager(!str_contains($standardsSource,'positive-ferric-ammonia-ei200'),'PHP standards source still contains canonical route recipes');
must_student_process_manager(str_contains($globalSource,"status='published'")&&str_contains($globalSource,'active_version_id'),'global process service does not resolve a published active version');
must_student_process_manager(str_contains($globalSource,"status='superseded'")&&str_contains($globalSource,"status='published'"),'publishing a new version does not preserve immutable version history');
must_student_process_manager(str_contains($globalSource,'student_process_change_log'),'global process mutations are not audited');

must_student_process_manager(str_contains($catalogSource,'student_process_stage_catalog')&&str_contains($catalogSource,'student_process_developer_catalog'),'managed process catalogs are not read from persistent domain tables');
must_student_process_manager(str_contains($processUx,'student_process_managed_stage_catalog')&&str_contains($processUx,'student_process_managed_developer'),'factual process recording still resolves stage/developer meaning from hardcoded PHP catalogs');
must_student_process_manager(str_contains($processUx,"&&\$reuseSource===''"),'reused bath must be excluded from inventory consumption');
must_student_process_manager(str_contains($processUx,"'reuse_source_stage_key'=>\$data['reuse_source_stage_key']"),'executed step must persist reuse metadata');

must_student_process_manager(str_contains($bootstrap,"'student_process_catalogs'")&&str_contains($bootstrap,"'student_global_processes'")&&str_contains($bootstrap,"'student_process_execution'"),'managed process domains are not loaded by bootstrap');
must_student_process_manager(str_contains($domain,'student_process_template_duplicate'),'saved process duplication is missing');
must_student_process_manager(str_contains($domain,'student_process_template_update_step'),'saved process step editing is missing');
must_student_process_manager(str_contains($domain,"'reuse_source_stage_key'"),'saved process payload does not preserve bath reuse');
foreach(['student_process_templates','student_process_template_steps','student_process_plans','student_process_plan_steps'] as $table)must_student_process_manager(str_contains($migration79,'CREATE TABLE IF NOT EXISTS '.$table),'missing migration table '.$table);
must_student_process_manager(str_contains($domain,'student_process_plan_apply_template')&&str_contains($domain,'payload_json'),'template snapshot authority is missing');
must_student_process_manager(str_contains($domain,'student_process_add_flexible_step'),'runner completion no longer writes through actual process-step authority');

must_student_process_manager(str_contains($manager,"action==='copy_standard'")&&str_contains($manager,'Padrões do workshop'),'process manager does not expose workshop standards');
must_student_process_manager(str_contains($manager,"action==='duplicate'")&&str_contains($manager,'Duplicar processamento'),'process manager does not expose process duplication');
must_student_process_manager(str_contains($manager,"action==='update_step'")&&str_contains($manager,'Salvar etapa'),'process manager does not expose step editing');
must_student_process_manager(str_contains($manager,'Meus processamentos')&&str_contains($manager,'Sua biblioteca'),'process manager is not library-first');
must_student_process_manager(str_contains($manager,'Reutilizar o banho da primeira revelação'),'step editor does not expose bath reuse choice');
must_student_process_manager(str_contains($manager,'Excluir processamento')&&str_contains($manager,'Excluir este processamento salvo?'),'process deletion is not discoverable');
must_student_process_manager(str_contains($manager,'student_process_template_duration_summary'),'saved templates still present partial timed sums as complete totals');
must_student_process_manager(str_contains($manager,'Associar ao registro')&&str_contains($manager,'Nenhuma execução foi iniciada.'),'process manager lost neutral association semantics');
must_student_process_manager(str_contains($manager,'Usar no laboratório')&&str_contains($manager,"\$intent==='live'"),'process manager does not preserve an explicit live intent');

foreach(['idle','running','paused','elapsed'] as $state)must_student_process_manager(str_contains($executionSource,"'$state'")||str_contains($executionSource,"state='$state'"),'execution service is missing state '.$state);
must_student_process_manager(str_contains($executionSource,'timer_ends_at'),'server execution does not persist an absolute timer deadline');
must_student_process_manager(str_contains($executionSource,'revision')&&str_contains($executionSource,'last_client_token'),'execution state lacks concurrency/idempotency protection');
must_student_process_manager(str_contains($executionSource,'StudentProcessExecutionConflict'),'stale tab/device conflict is not explicit');
must_student_process_manager(str_contains($executionSource,"state='elapsed'")&&str_contains($executionSource,'student_process_plan_complete_step'),'elapsed timer and factual step completion are separate transitions');
must_student_process_manager(str_contains($runner,"navigator.wakeLock.request('screen')"),'Screen Wake Lock is missing');
must_student_process_manager(str_contains($runner,'state.endAt-Date.now()'),'timer is not derived from an absolute end timestamp');
must_student_process_manager(!preg_match('/remaining\s*[-]{2}|remaining\s*=\s*remaining\s*-\s*1/',$runner),'timer regressed to decrement-per-tick timing');
must_student_process_manager(str_contains($runner,"action:'timer_state'")&&str_contains($runner,"transition('timer_start')")&&str_contains($runner,"transition('timer_pause')")&&str_contains($runner,"transition('timer_reset')"),'browser runner does not reconcile all persisted timer transitions');
must_student_process_manager(str_contains($runner,"window.addEventListener('online'")&&str_contains($runner,"document.addEventListener('visibilitychange'"),'runner does not reconcile after interruption/return');
must_student_process_manager(str_contains($runPage,'student_process_execution_transition')&&str_contains($runPage,'student_process_execution_complete_step'),'runner endpoint bypasses server execution authority');
must_student_process_manager(str_contains($runPage,'data-execution-state')&&str_contains($runPage,'data-plan-step-id'),'runner does not hydrate the exact persisted step state');
must_student_process_manager(str_contains($runPage,'Reutilize o banho da primeira revelação')&&str_contains($runPage,'não registre novo consumo'),'runner does not surface the reuse instruction');
must_student_process_manager(str_contains($runPage,"\$intent!=='live'")&&str_contains($runPage,'Registrar o processamento realizado'),'neutral associated plan can enter laboratory mode without an explicit intent gate');

must_student_process_manager(str_contains($manager,'Associar ao registro')&&str_contains($manager,'Iniciar no laboratório'),'process library no longer supports both record association and standalone execution');
must_student_process_manager(str_contains($recordPage,'/aluno/processamentos.php?test=<?=$id?>&amp;intent=live')&&str_contains($recordPage,'Vou revelar agora'),'Caderno does not preserve the live intent through reusable process selection');
must_student_process_manager(str_contains($recordPage,'Já revelei')&&str_contains($recordPage,'/aluno/processamento-realizado.php?test=<?=$id?>'),'Caderno does not expose retroactive process recording as the other primary path');
must_student_process_manager(str_contains($recordPage,'student_process_plan_for_test')&&str_contains($recordPage,'Abrir laboratório')&&str_contains($recordPage,'Trocar roteiro'),'Caderno does not expose compact choices for a neutral associated process plan');
must_student_process_manager(str_contains($recordPage,'Continuar laboratório')&&str_contains($recordPage,'Completar registro'),'Caderno does not support compact partial live/retroactive continuation');
must_student_process_manager(str_contains($notebookBridge,"processEntry='server'")&&!str_contains($notebookBridge,'article.innerHTML'),'legacy notebook bridge is still injecting process UI');
must_student_process_manager(!str_contains($recordPage,'Como este processamento aconteceu?')&&!str_contains($recordPage,'Associar um processamento salvo'),'Caderno still exposes the old verbose decision gate');
must_student_process_manager(!str_contains($tools,'data-lab-timer'),'standalone timer is still the primary tools UI');

must_student_process_manager(str_contains($doc,'mesmo banho de revelador preparado para a primeira revelação é reaproveitado na segunda'),'developer reuse is not documented canonically');
must_student_process_manager(str_contains($doc,'template → snapshot do registro → execução real'),'architecture decision is not documented');
must_student_process_manager(str_contains($runtimeDoc,'O servidor é a autoridade da sessão ao vivo')&&str_contains($runtimeDoc,'Matriz obrigatória de cenários reais'),'real-world execution/admin contract is not documented canonically');
must_student_process_manager(str_contains($productContract,'inspeção visual real em desktop e mobile antes de merge'),'product UX visual inspection contract is missing');
echo "student-process-manager: ok\n";