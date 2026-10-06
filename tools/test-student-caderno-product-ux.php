<?php
declare(strict_types=1);
function fail_student_caderno_product_ux(string $message): never {fwrite(STDERR,"student-caderno-product-ux: $message\n");exit(1);}
function must_student_caderno_product_ux(bool $ok,string $message): void {if(!$ok)fail_student_caderno_product_ux($message);}
$root=dirname(__DIR__);
$notebook=(string)file_get_contents($root.'/aluno/caderno.php');$record=(string)file_get_contents($root.'/aluno/teste.php');$runner=(string)file_get_contents($root.'/aluno/processar.php');$routePicker=(string)file_get_contents($root.'/aluno/registro-roteiro.php');
$notebookDomain=(string)file_get_contents($root.'/app/student_process_notebook.php');$freeDomain=(string)file_get_contents($root.'/app/student_process_notebook_free.php');$replan=(string)file_get_contents($root.'/app/student_process_replanning.php');$migration=(string)file_get_contents($root.'/migrations/092_student_process_step_timers.php');
$bootstrap=(string)file_get_contents($root.'/app/bootstrap.php');$shell=(string)file_get_contents($root.'/app/student_shell.php');$css=(string)file_get_contents($root.'/assets/student-caderno.css');$doc=(string)file_get_contents($root.'/docs/STUDENT_CADERNO_PRODUCT_UX_TRANCHE_B_2026-10-01.md');

must_student_caderno_product_ux(!str_contains($notebook,'student-record-progress')&&!str_contains($notebook,'is-current')&&!str_contains($notebook,'is-pending'),'Caderno list still exposes wizard progression');
must_student_caderno_product_ux(str_contains($notebook,'Abrir registro →'),'record itself must remain the primary Caderno action');
foreach(['id="exposicao"','id="processamento"','id="resultado"'] as $anchor)must_student_caderno_product_ux(str_contains($record,$anchor),'record page lost '.$anchor);
must_student_caderno_product_ux(str_contains($record,'podem ser preenchidos, corrigidos ou deixados em aberto independentemente'),'record purpose no longer states the notebook model');
must_student_caderno_product_ux(str_contains($record,'Salvar exposição')&&!str_contains($record,'Salvar exposição e continuar'),'exposure still forces progression');
must_student_caderno_product_ux(str_contains($record,'Ele não depende de nenhuma etapa anterior.'),'result is not explicitly independent');
foreach(['O processamento já aconteceu?','Vou revelar agora','Já revelei','Registrar manualmente','Continuar laboratório','Próxima etapa','intent=live'] as $obsolete)must_student_caderno_product_ux(!str_contains($record,$obsolete),'record reintroduced obsolete workflow language: '.$obsolete);
must_student_caderno_product_ux(str_contains($record,'Marcar ✓')&&str_contains($record,'Desmarcar')&&str_contains($record,'Abrir timer'),'associated route does not expose independent checks and timers');
must_student_caderno_product_ux(str_contains($record,'Movimentar estoque'),'stock movement is not available as an explicit separate action');
must_student_caderno_product_ux(str_contains($record,'Sem sequência obrigatória.'),'free steps still imply sequence');

foreach(['student_process_notebook_set_completed','student_process_notebook_update_step','student_process_notebook_timer_transition'] as $fn)must_student_caderno_product_ux(str_contains($notebookDomain,'function '.$fn),'non-linear notebook domain missing '.$fn);
must_student_caderno_product_ux(!str_contains($notebookDomain,'student_inventory_move'),'checking, editing or timing a route step must not move inventory');
must_student_caderno_product_ux(str_contains($migration,'student_process_step_timers')&&str_contains($migration,'plan_step_id INTEGER PRIMARY KEY'),'timer persistence is not independent per route step');
must_student_caderno_product_ux(str_contains($notebookDomain,"student_process_notebook_set_completed(\$db,(int)\$row['plan_id']")&&str_contains($notebookDomain,"true,'timer'"),'elapsed timer does not auto-check its own step');
must_student_caderno_product_ux(str_contains($freeDomain,'student_process_notebook_delete_free_step')&&!str_contains($freeDomain,'student_inventory_move'),'free-step removal still couples notebook data to inventory');

foreach(['Estou nesta etapa','Ir para próxima etapa','Concluir processamento','Registrar o processamento realizado','intent'] as $obsolete)must_student_caderno_product_ux(!str_contains($runner,$obsolete),'route tool still governs process progression: '.$obsolete);
must_student_caderno_product_ux(str_contains($runner,'Etapa não marcada')&&str_contains($runner,'Marcar como concluída')&&str_contains($runner,'Desmarcar etapa'),'route tool does not treat completion as reversible data');
must_student_caderno_product_ux(str_contains($runner,'Editar dados da etapa')&&str_contains($runner,'Movimentar estoque'),'route tool lost edit or explicit stock actions');

must_student_caderno_product_ux(str_contains($routePicker,'não inicia processamento, não impõe ordem e não movimenta estoque'),'route association semantics are not explicit');
must_student_caderno_product_ux(!str_contains($routePicker,'Usar daqui em diante')&&!str_contains($routePicker,'próximas etapas'),'route picker still carries temporal workflow semantics');
must_student_caderno_product_ux(!str_contains($replan,'student_process_replanning_prefix')&&!str_contains($replan,'pending_step_count'),'route change still reconstructs a sequential prefix');
must_student_caderno_product_ux(str_contains($replan,'preserved_checked_step_count'),'route change does not preserve compatible checks independently');
must_student_caderno_product_ux(str_contains($bootstrap,"'student_process_notebook'")&&str_contains($bootstrap,"'student_process_notebook_free'"),'notebook domain is not loaded');

foreach(['O Caderno observa e auxilia o processo; ele não governa o processo.','não diferencia “vou revelar”, “estou revelando” e “já revelei”','O check é **somente um dado do Caderno**','uso químico, desgaste da solução e variação física de volume são eventos diferentes','O Caderno registra. **A Análise interroga o conjunto de registros.**'] as $rule)must_student_caderno_product_ux(str_contains($doc,$rule),'canonical Caderno document missing rule: '.$rule);
foreach(['.student-record-section-links','.student-record-section','.student-caderno-plan-card','.student-result-context'] as $selector)must_student_caderno_product_ux(str_contains($css,$selector),'Caderno stylesheet missing '.$selector);
must_student_caderno_product_ux(str_contains($shell,'/assets/student-caderno.css'),'shell does not load Caderno CSS');
echo "student-caderno-product-ux: ok\n";