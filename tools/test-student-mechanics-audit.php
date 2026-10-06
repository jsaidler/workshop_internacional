<?php
declare(strict_types=1);
function fail_student_mechanics(string $message): never {fwrite(STDERR,"student-mechanics-audit: $message\n");exit(1);}
function must_student_mechanics(bool $ok,string $message): void {if(!$ok)fail_student_mechanics($message);}
$root=dirname(__DIR__);
$domain=(string)file_get_contents($root.'/app/student_process_ux.php');
$processJs=(string)file_get_contents($root.'/assets/student-process-ux.js');
$experienceJs=(string)file_get_contents($root.'/assets/student-experience.js');
$notes=(string)file_get_contents($root.'/app/student_notes_experience.php');
$tools=(string)file_get_contents($root.'/aluno/ferramentas.php');
$processPage=(string)file_get_contents($root.'/aluno/teste.php');
$processEdit=(string)file_get_contents($root.'/aluno/teste-etapa.php');
$preparations=(string)file_get_contents($root.'/aluno/preparos.php');
$audit=(string)file_get_contents($root.'/docs/STUDENT_AREA_MECHANICS_FULL_AUDIT_2026-10-01.md');

must_student_mechanics(str_contains($domain,"in_array(\$stageType,['development','chemical','custom'],true)"),'legacy guided chemistry compatibility no longer distinguishes inventory-capable stage kinds');
must_student_mechanics(str_contains($domain,"\$temperature=\$stageType==='dry'?'':")&&str_contains($domain,"\$agitation=\$stageType==='dry'?'':"),'legacy stage compatibility still accepts irrelevant drying data');
must_student_mechanics(str_contains($processJs,"const washKeys=new Set")&&str_contains($processJs,"const chemicalKeys=new Set"),'legacy process UI compatibility does not distinguish stage kinds');
must_student_mechanics(str_contains($processJs,"timer.hidden=kind==='dry'")&&str_contains($processJs,"Tempo de secagem (opcional)"),'legacy stage editor still exposes generic controls for drying');

must_student_mechanics(str_contains($processPage,'name="action" value="add_free_step"')&&str_contains($processPage,'name="chemical_name"')&&str_contains($processPage,'Anote uma etapa sem associar um roteiro.'),'Caderno no longer offers a neutral free-step record');
must_student_mechanics(str_contains($processEdit,'Nenhuma outra etapa é alterada por isso.')&&str_contains($processEdit,'name="chemical_name"')&&str_contains($processEdit,'Movimentar estoque'),'free-step editor no longer preserves independent editing and explicit stock movement');
must_student_mechanics(!str_contains($processPage,'student_experience_next_choices($steps)')&&!str_contains($processPage,'Registrar secagem e ir ao resultado'),'Caderno regressed to a guided physical-stage sequence');
must_student_mechanics(str_contains($preparations,'>Outro revelador<input name="developer_name"'),'saved preparation editor lost custom developer support');

must_student_mechanics(str_contains($experienceJs,'student-compare-mode')&&str_contains($experienceJs,'Comparar registros'),'comparison remains unreachable from the notebook');
must_student_mechanics(str_contains($notes,'student-notes-entry')&&str_contains($notes,'aria-controls="anotacoes"'),'material annotations remain undiscoverable');
must_student_mechanics(!str_contains($tools,'Fonte: <?=h((string)$note[\'source\'])?>'),'internal recipe provenance is still rendered to students');
must_student_mechanics(str_contains($audit,'Mapa de produto')&&str_contains($audit,'Rotas legadas'),'full student mechanics audit is missing');
echo "student-mechanics-audit: ok\n";
