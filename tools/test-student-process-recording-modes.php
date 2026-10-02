<?php
declare(strict_types=1);
function fail_process_recording(string $message): never {fwrite(STDERR,"student-process-recording: $message\n");exit(1);}
function must_process_recording(bool $ok,string $message): void {if(!$ok)fail_process_recording($message);}
$root=dirname(__DIR__);
$migration=(string)file_get_contents($root.'/migrations/080_student_process_recording_modes.php');
$domain=(string)file_get_contents($root.'/app/student_process_recording.php');
$page=(string)file_get_contents($root.'/aluno/processamento-realizado.php');
$record=(string)file_get_contents($root.'/aluno/teste.php');
$notebook=(string)file_get_contents($root.'/aluno/caderno.php');
$manager=(string)file_get_contents($root.'/aluno/processamentos.php');
$runner=(string)file_get_contents($root.'/aluno/processar.php');
$entry=(string)file_get_contents($root.'/assets/student-process-entry.js');
$progressive=(string)file_get_contents($root.'/assets/student-process-recording.js');
$shell=(string)file_get_contents($root.'/app/student_shell.php');
$rules=(string)file_get_contents($root.'/docs/STUDENT_PRODUCT_UX_CANONICAL_RULES_2026-10-02.md');

must_process_recording(str_contains($migration,'CREATE TABLE IF NOT EXISTS student_process_recording_meta'),'recording metadata migration is missing');
foreach(['live','retroactive','mixed'] as $mode)must_process_recording(str_contains($domain,"'$mode'"),'domain must recognize '.$mode.' recording mode');
must_process_recording(str_contains($domain,'student_process_plan_complete_recorded'),'plan cannot be completed as an already-performed record');
must_process_recording(str_contains($domain,'student_process_plan_apply_standard'),'workshop standards cannot be snapped directly into a record');
must_process_recording(str_contains($domain,"unset(\$input['inventory_item_id'],\$input['inventory_amount'])"),'retroactive recording can accidentally consume inventory');
must_process_recording((bool)preg_match('/\$meta\[[\'\"]recording_mode[\'\"]\]\s*=\s*\$mode/',$domain),'actual recorded steps do not preserve entry mode');

must_process_recording(str_contains($page,'Registrar o que já foi feito'),'retroactive recording page has no student-facing purpose');
must_process_recording(str_contains($page,'não movimenta o inventário automaticamente'),'retroactive page does not explain inventory consequence');
must_process_recording(str_contains($page,'não modifica o processamento da sua biblioteca'),'retroactive page does not explain snapshot independence');
must_process_recording(str_contains($page,'Registrar etapas restantes como realizadas'),'partial tracking cannot be completed without replaying timers');
must_process_recording(str_contains($page,'Editar etapa'),'recorded snapshot cannot expose deviation correction');
must_process_recording(str_contains($page,'data-recorded-process-form'),'manual retroactive entry lacks progressive form contract');

must_process_recording(str_contains($record,'Registrar um processamento já realizado'),'Caderno does not server-render the already-done path');
must_process_recording(str_contains($record,'Registrar etapas já realizadas'),'Caderno does not server-render partial completion');
must_process_recording(str_contains($record,'intent=live'),'live laboratory execution is not an explicit intent');
must_process_recording(str_contains($record,'data-recorded-process-path'),'retroactive route is not a first-class server-rendered path');
must_process_recording(str_contains($notebook,'student_experience_process_state_with_plan'),'Caderno list still infers execution from plan association');
must_process_recording(!str_contains($notebook,'Roteiro em execução'),'Caderno list still labels an associated plan as execution');

must_process_recording(str_contains($manager,'Associar ao registro'),'process manager does not distinguish association from execution');
must_process_recording(str_contains($manager,'Nenhuma execução foi iniciada.'),'association does not state that laboratory execution remains untouched');
must_process_recording(str_contains($manager,"header('Location: /aluno/teste.php?id='.\$testId.'&view=process'"),'process association must return to neutral Caderno processing state');
must_process_recording(!str_contains($manager,"header('Location: /aluno/processar.php?test='.\$testId"),'process association still forces laboratory mode');

must_process_recording(str_contains($runner,"\$showIntentChoice=\$plan&&!\$planStarted&&\$intent!=='live'"),'runner does not gate an unstarted plan behind explicit execution intent');
must_process_recording(str_contains($runner,'Nenhuma execução iniciada'),'runner intent gate does not explain the actual state');
must_process_recording(str_contains($runner,'Registrar o processamento realizado'),'runner intent gate does not offer documentation without execution');

must_process_recording(str_contains($entry,"processEntry='server'")&&!str_contains($entry,'article.innerHTML'),'core processing paths are still injected by JavaScript');
must_process_recording(str_contains($progressive,'data-recorded-stage')&&str_contains($progressive,"type!=='development'"),'manual retroactive form does not hide irrelevant developer fields');
must_process_recording(str_contains($shell,'student-process-recording.css')&&str_contains($shell,'student-process-recording.js'),'recording UI assets are not loaded');
must_process_recording(str_contains($shell,"str_contains(\$path,'processamento-realizado')")&&str_contains($shell,"return 'notebook'"),'retroactive recording is not part of the Caderno navigation domain');
must_process_recording(str_contains($rules,'Não presumir caminho único'),'canonical multipath UX rule is missing');
must_process_recording(str_contains($rules,'todas as telas da área do aluno, sem exceção'),'full visual inspection rule is missing');
echo "student-process-recording: ok\n";
