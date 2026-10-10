<?php
declare(strict_types=1);
function continuity_fail(string $message): never {fwrite(STDERR,"student-interaction-continuity: $message\n");exit(1);}
function continuity_must(bool $condition,string $message): void {if(!$condition)continuity_fail($message);}
$root=dirname(__DIR__);
$read=static fn(string $path): string=>(string)file_get_contents($root.'/'.$path);
$shell=$read('app/student_shell.php');
$local=$read('assets/student-local-actions.js');
$annotations=$read('assets/student-inline-annotations.js');
$inventory=$read('aluno/inventario.php');
$inventoryItem=$read('aluno/inventario-item.php');
$questions=$read('aluno/duvidas.php');
$preparations=$read('aluno/preparos.php');
$calibration=$read('aluno/calibracao.php');

continuity_must(str_contains($shell,'student-local-actions.js'),'shared local-action runtime is not loaded by the student shell');
continuity_must(str_contains($local,"path.endsWith('/aluno/caderno.php')&&action==='visibility'"),'notebook visibility still lacks an in-place interaction contract');
continuity_must(str_contains($local,"action==='save_exposure'")&&str_contains($local,"['record-exposure','record-result']"),'exposure save does not refresh its section and the result summary coherently');
continuity_must(str_contains($local,"['toggle_plan_step','add_free_step','delete_free_step']")&&str_contains($local,"['record-processing','record-result']"),'processing notebook mutations do not refresh processing and result context together');
continuity_must(str_contains($local,"action==='delete_free_step'?'Remover somente esta etapa do registro?'"),'free-step removal confirmation still implies destructive sequence correction');
foreach(['save_result','upload','delete_media','submit','message'] as $action)continuity_must(str_contains($local,"'$action'")||str_contains($local,"action==='$action'"),"record action $action is not covered by the local interaction runtime");
continuity_must(str_contains($local,"'record-exposure':'#exposicao'")&&str_contains($local,"'record-processing':'#processamento'")&&str_contains($local,"'record-result':'#resultado'"),'record-local refresh targets are not bound to the three canonical sections');
continuity_must(!str_contains($local,'data-route-change-link')&&!str_contains($local,'processamentos-trocar.php'),'client runtime still injects a competing route-change UI over the server-owned record');
continuity_must(str_contains($local,"form.setAttribute('action',stepEdit.href)")&&str_contains($local,"dangerForm.setAttribute('action',stepEdit.href)"),'contextual process-step editor posts to the wrong page');
continuity_must(str_contains($local,"dangerForm.dataset.localConfirm='Remover somente esta etapa do registro?'"),'contextual step editor still describes removal as a tail operation');
continuity_must(str_contains($local,'form?.requestSubmit()')&&!str_contains($local,'form?.submit()'),'record media upload bypasses the local submit contract');
continuity_must(str_contains($local,"[data-process-runner][data-timer-state=\"running\"]")&&str_contains($local,'beforeunload'),'active route timer is not protected from accidental navigation');
continuity_must(str_contains($local,"q('.ui-alert-error',doc)")&&str_contains($local,"pathname.endsWith('/aluno/login.php')"),'local mutation errors or expired sessions can be mistaken for successful saves');
continuity_must(str_contains($local,'finalUrl.pathname+finalUrl.search+finalUrl.hash'),'record-local save loses the canonical section anchor');

continuity_must(substr_count($inventory,'data-student-local-form')>=3,'inventory mutations are not consistently local');
continuity_must(str_contains($inventory,'data-student-local-key="inventory-stock"')&&str_contains($inventory,'data-student-local-key="inventory-history"'),'inventory has no stable local-refresh regions');
continuity_must(str_contains($inventory,'/aluno/inventario-item.php?id=')&&str_contains($inventory,'data-student-editor-link')&&str_contains($inventory,'data-student-editor-dialog'),'inventory metadata editing is still a separate-page interaction');
continuity_must(str_contains($inventoryItem,'data-student-editor')&&str_contains($inventoryItem,'data-student-local-form')&&str_contains($inventoryItem,'data-local-refresh="inventory-stock inventory-history"'),'inventory metadata editor cannot update its parent context locally');
continuity_must(str_contains($questions,'data-student-local-key="question-thread"')&&substr_count($questions,'data-student-local-form')>=2,'question reply/resolve still depends on full-page navigation');
continuity_must(str_contains($preparations,'data-student-editor-dialog')&&str_contains($preparations,'data-student-editor-link')&&str_contains($preparations,'data-student-local-key="preparations-list"'),'preparation catalog editing is still page-centric');
continuity_must(str_contains($calibration,'data-student-editor-dialog')&&str_contains($calibration,'data-student-editor-link')&&str_contains($calibration,'data-student-local-key="calibrations-list"'),'calibration editing is still page-centric');
continuity_must(str_contains($annotations,"Accept:'application/json'")&&str_contains($annotations,'event.preventDefault()'),'material annotations still depend on form navigation');
continuity_must(!str_contains($annotations,'student.annotation.return.v1'),'material annotation continuity regressed to reload/sessionStorage restoration');

echo "student-interaction-continuity: ok\n";
