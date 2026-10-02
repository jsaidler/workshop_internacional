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
must_student_caderno_product_ux(str_contains($notebook,'student_experience_process_state_with_plan'),'Caderno list does not distinguish an associated plan from execution');

foreach(['Como expus','Como revelei','O que obtive'] as $label)must_student_caderno_product_ux(str_contains($record,$label),'record workflow is missing pedagogical label '.$label);
must_student_caderno_product_ux(str_contains($record,'Este registro conecta as condições da exposição, o processamento realizado e o resultado obtido.'),'record does not explain its purpose');
must_student_caderno_product_ux(str_contains($record,'student_process_plan_for_test')&&str_contains($record,'student_process_plan_next_step'),'Caderno does not understand an applied saved-process plan');
must_student_caderno_product_ux(str_contains($record,'Associar um processamento salvo'),'saved processing route is missing');
must_student_caderno_product_ux(str_contains($record,'Registrar um processamento já realizado'),'already-performed route is missing');
must_student_caderno_product_ux(str_contains($record,'Registrar etapas manualmente'),'manual process route is missing');
must_student_caderno_product_ux(!str_contains($record,'is-recommended'),'Caderno still marks one valid processing path as the recommended/default route');
must_student_caderno_product_ux(!str_contains($record,'data-lab-timer'),'legacy inline timer is still embedded in Caderno processing');
must_student_caderno_product_ux(str_contains($record,'Executar agora')&&str_contains($record,'Registrar o que já foi feito')&&str_contains($record,'Trocar roteiro'),'associated plan does not expose neutral execution/documentation choices');
must_student_caderno_product_ux(str_contains($record,'Continuar no modo laboratório')&&str_contains($record,'Registrar etapas já realizadas'),'partial process does not expose both live and retroactive continuation');
must_student_caderno_product_ux(str_contains($record,'Exposição e processamento, lado a lado'),'result view does not reconnect result to exposure and processing');
must_student_caderno_product_ux(str_contains($record,'Anote o que observou no positivo'),'result notes do not orient the student');
must_student_caderno_product_ux(str_contains($bridge,"processEntry='server'")&&!str_contains($bridge,'insertBefore')&&!str_contains($bridge,'form.before')&&!str_contains($bridge,'article.innerHTML'),'legacy bridge still injects product UI');

must_student_caderno_product_ux(str_contains($shell,"\$notebookFeature")&&str_contains($shell,'/assets/student-caderno.css'),'shell does not load the Caderno UX layer');
foreach(['.student-record-progress','.student-process-path-choice','.student-caderno-plan-card','.student-plan-intent-grid','.student-result-context'] as $selector)must_student_caderno_product_ux(str_contains($css,$selector),'Caderno UX stylesheet is missing '.$selector);
must_student_caderno_product_ux(str_contains($doc,'exposição → processamento → resultado'),'Caderno canonical document lost the research sequence');
must_student_caderno_product_ux(str_contains($doc,'inspeção visual humana'),'Caderno canonical document does not require human visual inspection');
echo "student-caderno-product-ux: ok\n";
