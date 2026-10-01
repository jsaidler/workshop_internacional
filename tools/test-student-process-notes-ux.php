<?php
declare(strict_types=1);
function fail_process_notes(string $message): never {fwrite(STDERR,"student-process-notes-ux: $message\n");exit(1);}
function must_process_notes(bool $ok,string $message): void {if(!$ok)fail_process_notes($message);}
$root=dirname(__DIR__);
$bootstrap=(string)file_get_contents($root.'/app/bootstrap.php');
$domain=(string)file_get_contents($root.'/app/student_process_ux.php');
$auxiliary=(string)file_get_contents($root.'/app/student_auxiliary_ux.php');
$hardening=(string)file_get_contents($root.'/app/student_workbench_hardening.php');
$experience=(string)file_get_contents($root.'/app/student_experience.php');
$editor=(string)file_get_contents($root.'/aluno/teste-etapa.php');
$inventoryEditor=(string)file_get_contents($root.'/aluno/inventario-item.php');
$calibration=(string)file_get_contents($root.'/aluno/calibracao.php');
$index=(string)file_get_contents($root.'/index.php');
$noteEndpoint=(string)file_get_contents($root.'/aluno/material-anotacao.php');
$notes=(string)file_get_contents($root.'/app/student_notes_experience.php');
$notesCss=(string)file_get_contents($root.'/assets/cms-student-notes.css');
$notesJs=(string)file_get_contents($root.'/assets/student-inline-annotations.js');
$notesMigration=(string)file_get_contents($root.'/migrations/077_student_inline_annotations.php');
$processJs=(string)file_get_contents($root.'/assets/student-process-ux.js');
$presets=(string)file_get_contents($root.'/aluno/preparos.php');

must_process_notes(str_contains($bootstrap,"'student_process_ux'")&&str_contains($bootstrap,"'student_auxiliary_ux'")&&str_contains($bootstrap,"'student_notes_experience'"),'new student UX services are not bootstrapped');
must_process_notes(str_contains($domain,'function student_process_update_step')&&str_contains($domain,'UPDATE student_process_steps SET'),'existing process stages cannot be edited in place');
$updateOffset=strpos($domain,'function student_process_update_step');must_process_notes($updateOffset!==false&&!str_contains(substr($domain,$updateOffset),"DELETE FROM student_process_steps WHERE test_id=? AND position>=?"),'normal stage editing destroys the later process tail');
must_process_notes(str_contains($hardening,'student_process_add_flexible_step'),'process creation still delegates to the rigid legacy stage adder');
must_process_notes(str_contains($experience,'student_process_next_choices($steps)')&&str_contains($experience,'student_process_stage_catalog()'),'next-step suggestion no longer exposes alternative valid stages');
must_process_notes(str_contains($editor,'Salvar alterações')&&str_contains($editor,'Remover esta etapa e as seguintes'),'stage editor does not separate ordinary editing from destructive sequence correction');
must_process_notes(str_contains($processJs,'student-process-step-edit')&&str_contains($processJs,'Registrar outra etapa')&&str_contains($processJs,'Próxima decisão')&&str_contains($processJs,'student-status-reviewed'),'process screen still behaves as a one-way or unlocked wizard');
must_process_notes(str_contains($processJs,"primary.label='Pesquisa / curso'")&&str_contains($processJs,"common.label='Outros reveladores'")&&str_contains($processJs,"custom.label='Personalizado'"),'developer catalog is not grouped by relevance');
must_process_notes(str_contains($index,'student_material_render_notebook')&&!str_contains($index,'student_material_inject_notes'),'material still injects note editors into content sections');
must_process_notes(str_contains($notes,'student-notes-panel')&&!str_contains($notes,"appendChild(\$details)"),'notes are not centralized in a page notebook');
must_process_notes(str_contains($notesMigration,'CREATE TABLE IF NOT EXISTS student_material_annotations')&&str_contains($notesMigration,'quote_exact')&&str_contains($notesMigration,'quote_prefix')&&str_contains($notesMigration,'quote_suffix')&&str_contains($notesMigration,'block_key')&&str_contains($notesMigration,'source_page_revision'),'inline annotations do not persist resilient selection anchors');
must_process_notes(str_contains($notes,'data-inline-note-compose')&&str_contains($notes,'data-student-anchor-block')&&str_contains($notes,"name=\"annotation_action\" value=\"create_page\"")&&!str_contains($notes,'<select name="section_key">'),'new annotations still depend on a manual CMS-section selector');
must_process_notes(str_contains($noteEndpoint,"\$action==='create_selection'")&&str_contains($noteEndpoint,"\$action==='create_page'")&&str_contains($noteEndpoint,"\$action==='reanchor'")&&str_contains($noteEndpoint,"\$action==='detach'"),'annotation endpoint does not support selection, page, reassociation and detach flows');
must_process_notes(str_contains($notesJs,'Trecho alterado')&&str_contains($notesJs,'Trecho original removido')&&str_contains($notesJs,'não identificado com segurança')&&str_contains($notesJs,'student-inline-note-mark'),'annotation client does not preserve changed, removed or ambiguous source states');
must_process_notes(str_contains($notesCss,'.student-notes-panel[open]')&&str_contains($notesCss,'position:fixed'),'annotation notebook is not an independent study layer');
must_process_notes(str_contains($presets,'Predefinições de revelação')&&!str_contains($presets,'salvo(s)')&&str_contains($presets,'student_saved_preparation_update_guided')&&str_contains($presets,'?editar='),'reusable development presets cannot be edited coherently');
must_process_notes(str_contains($auxiliary,'student_saved_preparation_update_guided')&&str_contains($auxiliary,'student_inventory_update_metadata'),'persisted laboratory helpers do not have update services');
must_process_notes(str_contains($inventoryEditor,'O saldo continua sendo controlado pelas movimentações.')&&str_contains($inventoryEditor,'Salvar alterações'),'inventory metadata cannot be corrected without inventing a stock movement');
must_process_notes(str_contains($calibration,'?editar=')&&str_contains($calibration,'Salvar alterações'),'calibration references remain write-once records');
echo "student-process-notes-ux: ok\n";
