<?php
declare(strict_types=1);
function database(): PDO {static $db=null;return $db??=new PDO('sqlite::memory:');}
function student_tools_for_student(PDO $db,int $id): array {
    return array_map(static fn($key)=>['tool_key'=>$key],$GLOBALS['toolKeys']??[]);
}
require_once __DIR__.'/../app/student_shell.php';
function toolbox_expect(bool $ok,string $message): void {
    if(!$ok){fwrite(STDERR,"student-toolbox-parity: ".$message."\n");exit(1);}
}
$GLOBALS['toolKeys']=['reciprocity','exposure','lab_timer','solution_prep'];
ob_start();student_shell_toolbox(['id'=>42]);$all=(string)ob_get_clean();
toolbox_expect(substr_count($all,'<details class="student-toolbox-tool"')===2,'two progressive calculators missing');
toolbox_expect(str_contains($all,'data-quick-reciprocity')&&str_contains($all,'data-exposure-tool'),'working calculators absent');
toolbox_expect(substr_count($all,'href="/aluno/processamentos.php"')===1,'processes shortcut is duplicated');
toolbox_expect(!str_contains($all,'student-timer-display'),'fixture timer must not appear in production drawer');
toolbox_expect(str_contains($all,'href="/aluno/inventario.php"'),'inventory link missing');
toolbox_expect(!str_contains($all,'<details class="student-toolbox-tool" open'),'calculators must start collapsed');
$GLOBALS['toolKeys']=['reciprocity'];
ob_start();student_shell_toolbox(['id'=>42]);$restricted=(string)ob_get_clean();
toolbox_expect(!str_contains($restricted,'data-exposure-tool'),'restricted tool was shown');
toolbox_expect(!str_contains($restricted,'href="/aluno/processamentos.php"'),'restricted process shortcut shown');
toolbox_expect(!str_contains($restricted,'href="/aluno/ferramentas.php#receitas"'),'restricted recipes shortcut shown');
toolbox_expect(str_contains($restricted,'data-quick-reciprocity'),'authorized quick tool missing');
echo "student-toolbox-parity: ok\n";
