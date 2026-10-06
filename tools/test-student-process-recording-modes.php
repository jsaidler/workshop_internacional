<?php
declare(strict_types=1);
function fail_process_recording(string $message): never {fwrite(STDERR,"student-process-recording: $message\n");exit(1);}
function must_process_recording(bool $ok,string $message): void {if(!$ok)fail_process_recording($message);}
$root=dirname(__DIR__);
$legacyMigration=(string)file_get_contents($root.'/migrations/080_student_process_recording_modes.php');$legacyDomain=(string)file_get_contents($root.'/app/student_process_recording.php');$legacyPage=(string)file_get_contents($root.'/aluno/processamento-realizado.php');
$record=(string)file_get_contents($root.'/aluno/teste.php');$runner=(string)file_get_contents($root.'/aluno/processar.php');$notebookDomain=(string)file_get_contents($root.'/app/student_process_notebook.php');$doc=(string)file_get_contents($root.'/docs/STUDENT_CADERNO_PRODUCT_UX_TRANCHE_B_2026-10-01.md');

// Migração e serviço antigos permanecem somente para compatibilidade de dados já existentes.
must_process_recording(str_contains($legacyMigration,'student_process_recording_meta'),'legacy recording metadata migration disappeared');
must_process_recording(str_contains($legacyDomain,'student_process_recording_meta'),'legacy recording data can no longer be read');
// A antiga superfície temporal não é mais uma experiência de produto.
must_process_recording(str_contains($legacyPage,"header('Location: '.\$next,true,303)")&&str_contains($legacyPage,'não possui um modo separado'),'legacy already-performed route must converge to the record');

foreach(['O processamento já aconteceu?','Vou revelar agora','Já revelei','retroativo','retrospectiv','intent=live','Registrar manualmente','Continuar laboratório'] as $obsolete)must_process_recording(!str_contains($record,$obsolete),'Caderno still exposes temporal mode: '.$obsolete);
foreach(['Estou nesta etapa','Ir para próxima etapa','Concluir processamento','Registrar o processamento realizado','$intent'] as $obsolete)must_process_recording(!str_contains($runner,$obsolete),'route tool still exposes execution mode/progression: '.$obsolete);
must_process_recording(str_contains($runner,'student_process_notebook_timer_transition')&&str_contains($runner,'student_process_notebook_set_completed'),'route timer/check does not use notebook authority');
must_process_recording(str_contains($notebookDomain,'student_process_notebook_timer_normalize')&&str_contains($notebookDomain,"true,'timer'"),'timer elapsed does not map to reversible step check');
must_process_recording(!str_contains($notebookDomain,'student_inventory_move'),'timer/check domain must not move inventory');
must_process_recording(str_contains($doc,'O sistema não diferencia “vou revelar”, “estou revelando” e “já revelei”'),'canonical document still leaves temporal modes ambiguous');
echo "student-process-recording: ok\n";