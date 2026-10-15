<?php
declare(strict_types=1);
function must_material_cms(bool $ok,string $message):void {
 if(!$ok){fwrite(STDERR,"student-material-cms-operational: $message\n");exit(1);}
}
$_SERVER['REQUEST_METHOD']='GET';$_GET=['page'=>'current','cohort'=>'cohort-a'];
ob_start();
require __DIR__.'/browser-fixture/student-material-cms-operational.php';
$html=(string)ob_get_clean();
must_material_cms(str_contains($html,'class="cms-public cms-student-material"'),'must call the real CMS material renderer');
must_material_cms(str_contains($html,'data-cms-page-id="101"')&&str_contains($html,'data-cms-page-main'),'real CMS identifiers missing');
must_material_cms(str_contains($html,'cms-student-study-context')&&str_contains($html,'Turma Outubro 2026'),'real cohort navigation missing');
must_material_cms(str_contains($html,'Positivo direto em filme de raio-X'),'course name is not from enrolled course');
must_material_cms(str_contains($html,'Exposição pensando no positivo')&&str_contains($html,'Segunda revelação e acabamento'),'prev/next release filtering missing');
must_material_cms(!str_contains($html,'Conteúdo não liberado')&&!str_contains($html,'Esta seção não pode ser enviada ao aluno'),'blocked section leaked');
must_material_cms(str_contains($html,'data-cms-section="primeiro-desenvolvimento"')&&str_contains($html,'data-cms-section="branqueamento"'),'released sections missing');
must_material_cms(str_contains($html,'href="/aluno/cursos.php?cohort=cohort-a"')&&str_contains($html,'href="/aluno/duvidas.php?cohort=cohort-a"'),'cohort-scoped routes missing');
must_material_cms(str_contains($html,'class="student-mobile-nav"')&&str_contains($html,'aria-current="page"'),'global student nav missing');
$db=database();$activity=['id'=>1,'is_root'=>1,'slug'=>'workshop'];
$current=cms_page_by_id($db,101);$blocked=cms_page_by_id($db,103);
must_material_cms(is_array($current)&&is_array($blocked),'synthetic CMS page fixtures missing');
$student=['id'=>99];$unknown=['id'=>98];
$context=student_enrollment_material_context($db,$student,$current,'cohort-a');
must_material_cms(is_array($context)&&(int)$context['course_id']===12&&(int)$context['cohort_id']===7,'canonical enrollment context not resolved');
must_material_cms(student_enrollment_material_context($db,$unknown,$current,'cohort-a')===null,'foreign student received protected context');
must_material_cms(student_enrollment_material_context($db,$student,$current,'cohort-b')===null,'foreign cohort retained enrollment');
must_material_cms(!cms_access_page_allowed($db,$activity,$current,$unknown),'foreign student can access CMS page');
must_material_cms(course_material_page_release_summary($db,12,7,103)['state']==='blocked','unreleased material incorrectly navigable');
must_material_cms(in_array(course_material_page_release_summary($db,12,7,101)['state'],['partial','available'],true),'current material not treated as released/partially released');
$document=cms_page_doc($current,true);
$visible=cms_access_filter_html($db,$activity,$document['html'],$student,false,null,$context);
must_material_cms(str_contains($visible,'data-cms-section="branqueamento"'),'released section excluded');
must_material_cms(!str_contains($visible,'data-cms-section="aula-nao-liberada"'),'locked CMS section visible');
$entry=(string)file_get_contents(dirname(__DIR__).'/index.php');
must_material_cms(str_contains($entry,'cms_access_page_allowed($db,$activity,$page,$student)')&&str_contains($entry,'!$materialContext'),'production CMS entry must block foreign student before section rendering');
$source=(string)file_get_contents(dirname(__DIR__).'/tools/browser-fixture/student-material-cms-operational.php');
must_material_cms(str_contains($source,"require_once __DIR__.'/../../app/cms_renderer.php'")&&str_contains($source,'cms_render_public_page($activity,$page,cms_page_doc($page,true),false)'),'fixture must use public CMS production renderer and document');
must_material_cms(str_contains($source,"require_once __DIR__.'/../../app/student_enrollments.php'")&&str_contains($source,"require_once __DIR__.'/../../app/cms_access.php'"),'release and enrollment must use real services');
must_material_cms(str_contains($source,'http_response_code(403)')&&str_contains($source,'REQUEST_METHOD'),'fixture must protect synthetic personal access and deny writes');
echo "student-material-cms-operational: ok\n";
