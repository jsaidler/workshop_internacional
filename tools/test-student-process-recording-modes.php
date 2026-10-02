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
$quality=(string)file_get_contents($root.'/assets/student-quality-pass.css');
$rules=(string)file_get_contents($root.'/docs/STUDENT_PRODUCT_UX_CANONICAL_RULES_2026-10-02.md');

must_process_recording(str_contains($migration,'CREATE TABLE IF NOT EXISTS student_process_recording_meta'),'recording metadata migration is missing');
foreach(['live','retroactive','mixed'] as $mode)must_process_recording(str_contains($domain,"'$mode'"),'domain must recognize '.$mode.' recording mode');
must_process_recording(str_contains($domain,'student_process_plan_complete_recorded'),'plan cannot be completed as an already-performed record');
must_process_recording(str_contains($domain,'student_process_plan_apply_standard'),'workshop standards cannot be snapped directly into a record');
must_process_recording(str_contains($domain,"unset(\$input['inventory_item_id'],\$input['inventory_amount'])"),'retroactive recording can accidentally consume inventory');
must_process_recording((bool)preg_match('/\$meta\[[\'\"]recording_mode[\'\"]\]\s*=\s*\$mode/',$domain),'actual recorded steps do not preserve entry mode');

must_process_recording(str_contains($page,'Registrar processamento')&&str_contains($page,'Registre a sequência que você realmente realizou.'),'retroactive recording page has no concise student-facing purpose');
must_process_recording(str_contains($page,'Não há baixa automática no inventário.'),'retroactive page does not explain inventory consequence');
must_process_recording(str_contains($page,'vira uma cópia própria deste registro'),'retroactive page does not explain snapshot independence');
must_process_recording(str_contains($page,'Registrar etapas restantes'),'partial tracking cannot be completed without replaying timers');
must_process_recording(str_contains($page,'Registrar processo como realizado'),'fully retroactive associated plan cannot be completed directly');
must_process_recording(str_contains($page,"\$planCompleted>0?'Registro parcial':'Roteiro associado'"),'retroactive page does not distinguish an untouched associated plan from partial execution');
must_process_recording(str_contains($page,'Editar etapa'),'recorded snapshot cannot expose deviation correction');
must_process_recording(str_contains($page,'data-recorded-process-form'),'manual retroactive entry lacks progressive form contract');
must_process_recording(!str_contains($page,'student-process-recording-principles'),'retroactive page still exposes the old pair of permanent explanatory cards');

must_process_recording(str_contains($record,'Vou revelar agora')&&str_contains($record,'Já revelei'),'Caderno does not expose the real top-level processing decision');
must_process_recording(str_contains($record,'Completar registro'),'Caderno does not server-render partial completion');
must_process_recording(str_contains($record,'intent=live'),'live laboratory execution is not an explicit preserved intent');
must_process_recording(str_contains($record,'data-recorded-process-path'),'retroactive route is not a first-class server-rendered path');
must_process_recording(str_contains($notebook,'student_experience_process_state_with_plan'),'Caderno list still infers execution from plan association');
must_process_recording(!str_contains($notebook,'Roteiro em execução'),'Caderno list still labels an associated plan as execution');

must_process_recording(str_contains($manager,'Associar ao registro'),'process manager lost neutral association mode');
must_process_recording(str_contains($manager,'Nenhuma execução foi iniciada.'),'association does not state that execution remains untouched');
must_process_recording(str_contains($manager,"\$target=\$intent==='live'?'/aluno/processar.php?test='.\$testId.'&intent=live':'/aluno/teste.php?id='.\$testId.'&view=process'"),'process manager does not preserve the previously declared live intent');
must_process_recording(str_contains($manager,"\$intent!=='live')\$intent=''"),'process manager does not constrain intent values');
must_process_recording(str_contains($manager,'Usar no laboratório'),'live selection is not labeled as a laboratory action');

must_process_recording(str_contains($runner,"\$showIntentChoice=\$plan&&!\$planStarted&&\$intent!=='live'"),'runner does not gate an unstarted neutral plan behind explicit execution intent');
must_process_recording(str_contains($runner,"if(\$action==='start'){student_process_plan_start"),'plan execution can start without the explicit runner start action');
must_process_recording(str_contains($runner,'data-runner-start>Iniciar'),'laboratory runner lacks the explicit start control');

must_process_recording(str_contains($entry,"processEntry='server'")&&!str_contains($entry,'article.innerHTML'),'core processing paths are still injected by JavaScript');
must_process_recording(str_contains($progressive,'data-recorded-stage')&&str_contains($progressive,"type!=='development'"),'manual retroactive form does not hide irrelevant developer fields');
must_process_recording(str_contains($shell,'student-process-recording.css')&&str_contains($shell,'student-process-recording.js'),'recording UI assets are not loaded');
must_process_recording(str_contains($shell,'student-quality-pass.css'),'corrective quality layer is not loaded');
must_process_recording(str_contains($quality,'.student-mobile-nav')&&str_contains($quality,'position:static!important'),'mobile navigation can still cover process content');
must_process_recording(str_contains($shell,"str_contains(\$path,'processamento-realizado')")&&str_contains($shell,"return 'notebook'"),'retroactive recording is not part of the Caderno navigation domain');
must_process_recording(str_contains($rules,'Não presumir caminho único'),'canonical multipath UX rule is missing');
must_process_recording(str_contains($rules,'A interface deve perguntar uma decisão apenas quando ela muda a próxima ação'),'canonical rules do not prevent repeated decision gates');
must_process_recording(str_contains($rules,'todas as telas da área do aluno, sem exceção'),'full visual inspection rule is missing');
echo "student-process-recording: ok\n";