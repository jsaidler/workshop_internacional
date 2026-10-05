<?php
declare(strict_types=1);
function fail_student_caderno_product_ux(string $message): never {fwrite(STDERR,"student-caderno-product-ux: $message\n");exit(1);}
function must_student_caderno_product_ux(bool $ok,string $message): void {if(!$ok)fail_student_caderno_product_ux($message);}
$root=dirname(__DIR__);
$notebook=(string)file_get_contents($root.'/aluno/caderno.php');
$record=(string)file_get_contents($root.'/aluno/teste.php');
$routePicker=(string)file_get_contents($root.'/aluno/registro-roteiro.php');
$bootstrap=(string)file_get_contents($root.'/app/bootstrap.php');
$shell=(string)file_get_contents($root.'/app/student_shell.php');
$bridge=(string)file_get_contents($root.'/assets/student-process-entry.js');
$css=(string)file_get_contents($root.'/assets/student-caderno.css');
$quality=(string)file_get_contents($root.'/assets/student-quality-pass.css');
$doc=(string)file_get_contents($root.'/docs/STUDENT_CADERNO_PRODUCT_UX_TRANCHE_B_2026-10-01.md');
$rules=(string)file_get_contents($root.'/docs/STUDENT_PRODUCT_UX_CANONICAL_RULES_2026-10-02.md');

must_student_caderno_product_ux(str_contains($notebook,'As partes podem ser preenchidas em qualquer ordem')&&str_contains($notebook,'complete ou corrija depois'),'Caderno library does not explain non-linear recording');
must_student_caderno_product_ux(!str_contains($notebook,'student-record-progress')&&!str_contains($notebook,'is-current')&&!str_contains($notebook,'is-pending'),'Caderno list still exposes wizard progression');
must_student_caderno_product_ux(str_contains($notebook,'student-record-summary')&&str_contains($notebook,'Exposição ainda não registrada')&&str_contains($notebook,'Resultado ainda não registrado'),'Caderno list does not derive documentary summaries');
must_student_caderno_product_ux(str_contains($notebook,'Abrir registro →'),'Caderno list does not use the record itself as the primary continuation target');
must_student_caderno_product_ux(str_contains($notebook,'Comece pelo primeiro registro.')&&str_contains($notebook,'Criar primeiro registro'),'empty notebook does not teach the first action');

foreach(['id="exposicao"','id="processamento"','id="resultado"'] as $anchor)must_student_caderno_product_ux(str_contains($record,$anchor),'record page does not render all sections together: '.$anchor);
foreach(['Como expus','Como revelei','O que obtive'] as $label)must_student_caderno_product_ux(str_contains($record,$label),'record is missing documentary label '.$label);
must_student_caderno_product_ux(str_contains($record,'Exposição, processamento e resultado pertencem ao mesmo registro'),'record purpose no longer states the single-record model');
must_student_caderno_product_ux(str_contains($record,'Salvar exposição')&&!str_contains($record,'Salvar exposição e continuar'),'exposure still implies a forced next step');
must_student_caderno_product_ux(!str_contains($record,'$resultReady')&&!str_contains($record,'aria-disabled'),'result access is still gated by another section');
must_student_caderno_product_ux(!str_contains($record,'O processamento já aconteceu?')&&!str_contains($record,'Vou revelar agora')&&!str_contains($record,'Já revelei'),'processing still forces the user to classify time before acting');
must_student_caderno_product_ux(str_contains($record,'/aluno/registro-roteiro.php?test=<?=$id?>')&&str_contains($record,'Registrar manualmente'),'processing does not expose route and manual recording as tools of the same record');
must_student_caderno_product_ux(str_contains($record,'/aluno/processar.php?test=<?=$id?>&amp;intent=live')&&str_contains($record,'Abrir laboratório'),'laboratory operation is not subordinate to the current record');
must_student_caderno_product_ux(str_contains($record,'Alterar roteiro'),'an associated route is still irreversible from the record');
must_student_caderno_product_ux(str_contains($record,'Mais opções do processamento')&&str_contains($record,'Desfazer última etapa'),'destructive processing action is not progressively disclosed');
must_student_caderno_product_ux(str_contains($record,'Salvar resultado'),'result cannot be saved independently');
must_student_caderno_product_ux(str_contains($record,"header('Location: /aluno/teste.php?id='.\$id.'#'.\$anchor,true,303)")||str_contains($record,"header('Location: /aluno/teste.php?id='.\$id.('#'.\$anchor)"),'legacy view compatibility does not converge to anchors');
must_student_caderno_product_ux(str_contains($record,'← Voltar ao Caderno'),'record has no canonical exit back to notebook');
must_student_caderno_product_ux(!str_contains($record,'data-lab-timer'),'legacy inline timer is still embedded in Caderno processing');

must_student_caderno_product_ux(str_contains($routePicker,'← Voltar ao registro')&&str_contains($routePicker,'Cancelar e voltar ao registro'),'route picker can leave the user without an explicit return');
must_student_caderno_product_ux(str_contains($routePicker,'student_process_replan_template')&&str_contains($routePicker,'student_process_replan_standard'),'route picker bypasses atomic history-preserving replanning');
must_student_caderno_product_ux(str_contains($routePicker,'O que já foi registrado no Caderno permanece no histórico'),'route picker does not explain preserved facts');
must_student_caderno_product_ux(str_contains($bootstrap,"'student_process_replanning'"),'replanning service is not loaded by bootstrap');

must_student_caderno_product_ux(str_contains($bridge,"processEntry='server'")&&!str_contains($bridge,'insertBefore')&&!str_contains($bridge,'form.before')&&!str_contains($bridge,'article.innerHTML'),'legacy bridge still injects product UI');
must_student_caderno_product_ux(str_contains($shell,"\$notebookFeature")&&str_contains($shell,'/assets/student-caderno.css'),'shell does not load the Caderno UX layer');
must_student_caderno_product_ux(str_contains($shell,'/assets/student-quality-pass.css'),'shell does not load the corrective visual quality layer');
foreach(['.student-record-summary','.student-record-section-links','.student-record-section','.student-caderno-plan-card','.student-result-context'] as $selector)must_student_caderno_product_ux(str_contains($css,$selector),'Caderno UX stylesheet is missing '.$selector);
must_student_caderno_product_ux(str_contains($css,'@media(max-width:560px)')&&str_contains($css,'.student-record-section-links{display:grid;grid-template-columns:1fr'),'mobile section links do not collapse safely');
must_student_caderno_product_ux(str_contains($quality,'.student-mobile-nav')&&str_contains($quality,'position:static;')&&str_contains($quality,'backdrop-filter:none;'),'mobile navigation can still cover content or remain translucent');

must_student_caderno_product_ux(str_contains($doc,'registro de pesquisa')&&str_contains($doc,'não um assistente de etapas'),'Caderno canonical document does not encode the non-wizard model');
must_student_caderno_product_ux(str_contains($doc,'não definem uma ordem obrigatória de uso da interface'),'Caderno canonical document still implies mandatory UI sequence');
must_student_caderno_product_ux(str_contains($doc,'Resultado pode ser registrado mesmo sem Exposição ou Processamento completos'),'Caderno canonical document still gates result');
must_student_caderno_product_ux(str_contains($doc,'alterar o roteiro depois de fatos já materializados'),'Caderno canonical document does not encode route reversibility');
must_student_caderno_product_ux(str_contains($doc,'inspeção visual humana'),'Caderno canonical document does not require human visual inspection');
must_student_caderno_product_ux(str_contains($rules,'A sequência pertence ao processo fotográfico. A navegação pertence ao usuário.')&&str_contains($rules,'Escolhas são reversíveis; fatos já materializados são preservados'),'canonical rules lost navigation/fact separation or reversibility');
echo "student-caderno-product-ux: ok\n";
