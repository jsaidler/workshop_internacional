<?php
declare(strict_types=1);
function fail_student_caderno_product_ux(string $message): never {fwrite(STDERR,"student-caderno-product-ux: $message\n");exit(1);}
function must_student_caderno_product_ux(bool $ok,string $message): void {if(!$ok)fail_student_caderno_product_ux($message);}
$root=dirname(__DIR__);
$notebook=(string)file_get_contents($root.'/aluno/caderno.php');
$record=(string)file_get_contents($root.'/aluno/teste.php');
$shell=(string)file_get_contents($root.'/app/student_shell.php');
$bridge=(string)file_get_contents($root.'/assets/student-process-entry.js');
$css=(string)file_get_contents($root.'/assets/student-caderno.css');
$doc=(string)file_get_contents($root.'/docs/STUDENT_CADERNO_PRODUCT_UX_TRANCHE_B_2026-10-01.md');

must_student_caderno_product_ux(str_contains($notebook,'como você expôs')&&str_contains($notebook,'como processou')&&str_contains($notebook,'o que obteve'),'Caderno library does not explain the experiment relationship');
must_student_caderno_product_ux(str_contains($notebook,'student-record-progress')&&str_contains($notebook,'is-current')&&str_contains($notebook,'is-pending'),'Caderno list does not expose record progression');
must_student_caderno_product_ux(str_contains($notebook,'Comece pelo primeiro registro.')&&str_contains($notebook,'Criar primeiro registro'),'empty notebook does not teach the first action');

foreach(['Como expus','Como revelei','O que obtive'] as $label)must_student_caderno_product_ux(str_contains($record,$label),'record workflow is missing pedagogical label '.$label);
must_student_caderno_product_ux(str_contains($record,'Este registro conecta as condições da exposição, o processamento realizado e o resultado obtido.'),'record does not explain its purpose');
must_student_caderno_product_ux(str_contains($record,'student_process_plan_for_test')&&str_contains($record,'student_process_plan_next_step'),'Caderno does not understand an applied saved-process plan');
must_student_caderno_product_ux(str_contains($record,'Usar um processamento salvo')&&str_contains($record,'Recomendado'),'saved processing route is not the recommended path');
must_student_caderno_product_ux(str_contains($record,'Registrar etapas manualmente')&&str_contains($record,'Alternativa'),'manual process path is not explicitly secondary');
must_student_caderno_product_ux(!str_contains($record,'data-lab-timer'),'legacy inline timer is still embedded in Caderno processing');
must_student_caderno_product_ux(str_contains($record,'Continuar no modo laboratório')&&str_contains($record,'Trocar roteiro'),'applied plan does not expose clear continuation/change actions');
must_student_caderno_product_ux(str_contains($record,'Exposição e processamento, lado a lado'),'result view does not reconnect result to exposure and processing');
must_student_caderno_product_ux(str_contains($record,'Anote o que observou no positivo'),'result notes do not orient the student');
must_student_caderno_product_ux(str_contains($bridge,"processEntry='server'")&&!str_contains($bridge,'insertBefore')&&!str_contains($bridge,'form.before'),'legacy bridge still injects product UI');

must_student_caderno_product_ux(str_contains($shell,"\$notebookFeature")&&str_contains($shell,'/assets/student-caderno.css'),'shell does not load the Caderno UX layer');
foreach(['.student-record-progress','.student-process-path-choice','.student-caderno-plan-card','.student-result-context'] as $selector)must_student_caderno_product_ux(str_contains($css,$selector),'Caderno UX stylesheet is missing '.$selector);
must_student_caderno_product_ux(str_contains($doc,'exposição → processamento → resultado'),'Caderno canonical document lost the research sequence');
must_student_caderno_product_ux(str_contains($doc,'inspeção visual humana'),'Caderno canonical document does not require human visual inspection');
echo "student-caderno-product-ux: ok\n";
