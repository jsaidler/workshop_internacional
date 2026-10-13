<?php
declare(strict_types=1);

function h(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function assert_record_workspace(bool $condition,string $message):void {
    if(!$condition){fwrite(STDERR,"student-record-workspace: ".$message."\n");exit(1);}
}
require_once dirname(__DIR__).'/app/student_record_workspace_navigation.php';
ob_start();student_record_workspace_navigation();$nav=(string)ob_get_clean();
foreach(['exposicao'=>'Exposição','processamento'=>'Processamento','resultado'=>'Resultado'] as $id=>$label){
    assert_record_workspace(str_contains($nav,'href="#'.$id.'"')&&str_contains($nav,'aria-controls="'.$id.'"'),$label.' must remain native direct navigation');
}
assert_record_workspace(substr_count($nav,'data-record-workspace-link')===3,'navigation must expose exactly three peer contexts');
assert_record_workspace(str_contains($nav,'data-record-workspace-nav'),'operational navigation is not reusable');

$source=(string)file_get_contents(dirname(__DIR__).'/aluno/teste.php');
assert_record_workspace(str_contains($source,'student_record_workspace_navigation()'),'actual route must consume shared navigation');
assert_record_workspace(substr_count($source,'data-record-workspace-panel')===3,'actual route must expose three sections');
assert_record_workspace(str_contains($source,'data-record-workspace-default'),'failed POST must select its originating section');
assert_record_workspace(str_contains($source,'student-record-media-disclosure'),'scene media must remain a secondary but accessible action');
assert_record_workspace(strpos($source,'<h3>Dados da exposição</h3>')<strpos($source,'<details class="student-record-media-disclosure"'),'exposure task should precede optional scene media');
foreach(['student_test_update_exposure','student_test_add_media_batch_phase','student_test_delete_media','student_process_notebook_update_step','student_test_submit'] as $service){
    assert_record_workspace(str_contains($source,$service),'operational server boundary lost: '.$service);
}
$shell=(string)file_get_contents(dirname(__DIR__).'/app/student_shell.php');
assert_record_workspace(str_contains($shell,'student-record-workspace.js'),'JS must load from actual authenticated record route');
$js=(string)file_get_contents(dirname(__DIR__).'/assets/student-record-workspace.js');
assert_record_workspace(str_contains($js,"window.addEventListener('hashchange'")&&!str_contains($js,'history.pushState'),'native hash history must not be replaced with fake navigation');
echo "student-record-workspace: ok\n";
