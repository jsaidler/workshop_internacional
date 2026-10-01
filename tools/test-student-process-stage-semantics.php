<?php
declare(strict_types=1);
function fail_stage_semantics(string $message): never {fwrite(STDERR,"student-stage-semantics: $message\n");exit(1);}
function must_stage_semantics(bool $ok,string $message): void {if(!$ok)fail_stage_semantics($message);}
$source=(string)file_get_contents(dirname(__DIR__).'/app/student_process_ux.php');
must_stage_semantics(str_contains($source,"\$inventoryAllowed=in_array(\$stageType,['development','chemical','custom'],true)"),'inventory allowance is not type-scoped');
must_stage_semantics(str_contains($source,"\$temperature=\$stageType==='dry'?'':")&&str_contains($source,"\$agitation=\$stageType==='dry'?'':"),'drying does not discard incompatible fields');
must_stage_semantics(str_contains($source,"'stage_type'=>\$stageType"),'stage type is not persisted from the catalog');
echo "student-stage-semantics: ok\n";
