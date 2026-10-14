<?php
declare(strict_types=1);
function h(mixed $s): string {return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'test-synthetic-csrf';}
function record_view_assert(bool $ok,string $error): void {if(!$ok){fwrite(STDERR,"student-record-auxiliary-views: $error\n");exit(1);}}
require __DIR__.'/../app/student_record_auxiliary_views.php';
$step=['id'=>5,'label'=>'Primeira revelação','chemical_name'=>'Caffenol <script>','duration'=>'05:00','temperature'=>'35 °C','agitation'=>'Leve','notes'=>'Obs <teste>'];
ob_start();student_record_render_free_step_editor($step,7,1,false,'','Caffenol <script>');$editor=(string)ob_get_clean();
record_view_assert(str_contains($editor,'Caffenol &lt;script&gt;')&&!str_contains($editor,'Caffenol <script>'),'step heading and values must be escaped');
record_view_assert(str_contains($editor,'Obs &lt;teste&gt;'),'step notes not escaped');
record_view_assert(str_contains($editor,'href="/aluno/teste.php?id=7#processamento"')&&str_contains($editor,'name="registro" value="7"'),'canonical route context lost');
record_view_assert(substr_count($editor,'name="_csrf" value="test-synthetic-csrf"')===2,'step update and deletion must both have CSRF');
record_view_assert(str_contains($editor,'name="action" value="save"')&&str_contains($editor,'name="action" value="delete"'),'step mutation types changed');
record_view_assert(str_contains($editor,'Nenhuma outra etapa é alterada por isso.')&&str_contains($editor,'as movimentações de estoque permanecem'),'free step independence contract lost');
ob_start();student_record_render_free_step_editor($step,7,1,true,'','Caffenol');$locked=(string)ob_get_clean();
record_view_assert(!str_contains($locked,'name="action" value="delete"')&&!str_contains($locked,'Salvar alterações')&&str_contains($locked,' disabled'),'reviewed record must be readonly');
ob_start();student_record_render_free_step_editor($step,7,1,false,'Falha <script>','Caffenol');$error=(string)ob_get_clean();
record_view_assert(str_contains($error,'role="alert"')&&str_contains($error,'Falha &lt;script&gt;')&&!str_contains($error,'Falha <script>'),'step error must be contextual and escaped');
$record=['id'=>7,'title'=>'Teste EI 400 <script>'];
ob_start();student_record_render_delete_confirmation($record,7,'');$delete=(string)ob_get_clean();
record_view_assert(str_contains($delete,'Teste EI 400 &lt;script&gt;')&&!str_contains($delete,'Teste EI 400 <script>'),'record title not escaped in destructive confirmation');
record_view_assert(str_contains($delete,'name="_csrf" value="test-synthetic-csrf"')&&str_contains($delete,'name="id" value="7"'),'deletion CSRF or target missing');
record_view_assert(str_contains($delete,'href="/aluno/teste.php?id=7"')&&str_contains($delete,'Excluir registro definitivamente')&&str_contains($delete,'todas as fotografias anexadas'),'deletion context and consequences missing');
ob_start();student_record_render_delete_confirmation($record,7,'Erro <inválido>');$failed=(string)ob_get_clean();
record_view_assert(str_contains($failed,'role="alert"')&&str_contains($failed,'Erro &lt;inválido&gt;'),'deletion error not escaped');
foreach(['teste-etapa.php'=>'student_record_render_free_step_editor','excluir-teste.php'=>'student_record_render_delete_confirmation'] as $route=>$method){
 $content=(string)file_get_contents(__DIR__.'/../aluno/'.$route);
 record_view_assert(str_contains($content,$method.'(')&&str_contains($content,'verify_csrf(')&&str_contains($content,'student_account_current('),'production route must own authorization, CSRF and call canonical view');
}
echo "student-record-auxiliary-views: ok\n";
