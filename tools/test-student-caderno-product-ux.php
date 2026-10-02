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
$quality=(string)file_get_contents($root.'/assets/student-quality-pass.css');
$doc=(string)file_get_contents($root.'/docs/STUDENT_CADERNO_PRODUCT_UX_TRANCHE_B_2026-10-01.md');
$rules=(string)file_get_contents($root.'/docs/STUDENT_PRODUCT_UX_CANONICAL_RULES_2026-10-02.md');

must_student_caderno_product_ux(str_contains($notebook,'como você expôs')&&str_contains($notebook,'como processou')&&str_contains($notebook,'o que obteve'),'Caderno library does not explain the experiment relationship');
must_student_caderno_product_ux(str_contains($notebook,'student-record-progress')&&str_contains($notebook,'is-current')&&str_contains($notebook,'is-pending'),'Caderno list does not expose record progression');
must_student_caderno_product_ux(str_contains($notebook,'Comece pelo primeiro registro.')&&str_contains($notebook,'Criar primeiro registro'),'empty notebook does not teach the first action');
must_student_caderno_product_ux(str_contains($notebook,'student_experience_process_state_with_plan'),'Caderno list does not distinguish an associated plan from execution');

foreach(['Como expus','Como revelei','O que obtive'] as $label)must_student_caderno_product_ux(str_contains($record,$label),'record workflow is missing pedagogical label '.$label);
must_student_caderno_product_ux(str_contains($record,'Exposição, processamento e resultado no mesmo registro.'),'record purpose is not concise');
must_student_caderno_product_ux(str_contains($record,'student_process_plan_for_test')&&str_contains($record,'student_process_plan_next_step'),'Caderno does not understand an applied saved-process plan');
must_student_caderno_product_ux(str_contains($record,'Vou revelar agora')&&str_contains($record,'Já revelei'),'processing entry does not ask the real top-level decision');
must_student_caderno_product_ux(str_contains($record,'/aluno/processamentos.php?test=<?=$id?>&amp;intent=live'),'live intent is not preserved into process selection');
must_student_caderno_product_ux(str_contains($record,'Registrar etapa por etapa, sem roteiro'),'manual route is not progressively de-emphasized');
must_student_caderno_product_ux(!str_contains($record,'Associar um processamento salvo'),'Caderno still exposes implementation language as the primary student decision');
must_student_caderno_product_ux(!str_contains($record,'Como este processamento aconteceu?'),'Caderno still uses the verbose three-card decision gate');
must_student_caderno_product_ux(!str_contains($record,'is-recommended'),'Caderno still marks one valid processing path as the recommended/default route');
must_student_caderno_product_ux(!str_contains($record,'data-lab-timer'),'legacy inline timer is still embedded in Caderno processing');
must_student_caderno_product_ux(str_contains($record,'Abrir laboratório')&&str_contains($record,'Registrar')&&str_contains($record,'Trocar roteiro'),'associated plan does not expose compact execution/documentation choices');
must_student_caderno_product_ux(str_contains($record,'Continuar laboratório')&&str_contains($record,'Completar registro'),'partial process does not expose live and retroactive continuation');
must_student_caderno_product_ux(str_contains($record,'Exposição e processamento, lado a lado'),'result view does not reconnect result to exposure and processing');
must_student_caderno_product_ux(str_contains($record,'Anote o que observou no positivo'),'result notes do not orient the student');
must_student_caderno_product_ux(str_contains($bridge,"processEntry='server'")&&!str_contains($bridge,'insertBefore')&&!str_contains($bridge,'form.before')&&!str_contains($bridge,'article.innerHTML'),'legacy bridge still injects product UI');

must_student_caderno_product_ux(str_contains($shell,"\$notebookFeature")&&str_contains($shell,'/assets/student-caderno.css'),'shell does not load the Caderno UX layer');
must_student_caderno_product_ux(str_contains($shell,'/assets/student-quality-pass.css'),'shell does not load the corrective visual quality layer');
foreach(['.student-record-progress','.student-process-path-choice','.student-caderno-plan-card','.student-plan-intent-grid','.student-result-context'] as $selector)must_student_caderno_product_ux(str_contains($css,$selector),'Caderno UX stylesheet is missing '.$selector);
must_student_caderno_product_ux(str_contains($quality,'.student-mobile-nav')&&str_contains($quality,'position:static!important')&&str_contains($quality,'backdrop-filter:none!important'),'mobile navigation can still cover content or remain translucent');
must_student_caderno_product_ux(str_contains($quality,'.student-record-purpose{display:none!important}')&&str_contains($quality,'.student-workflow-heading>p{display:none!important}'),'mobile density pass does not remove repeated explanatory copy');
must_student_caderno_product_ux(str_contains($doc,'exposição → processamento → resultado'),'Caderno canonical document lost the research sequence');
must_student_caderno_product_ux(str_contains($doc,'inspeção visual humana'),'Caderno canonical document does not require human visual inspection');
must_student_caderno_product_ux(str_contains($rules,'carga textual')&&str_contains($rules,'barras móveis que sobrepõem conteúdo'),'canonical quality rules do not encode the visual defects found in audit');
echo "student-caderno-product-ux: ok\n";