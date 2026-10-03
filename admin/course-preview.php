<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();require_admin();
$db=database();$state=admin_activity_resolution($db);$activity=$state['activity']??null;if(!$activity){http_response_code(404);exit('Site não encontrado.');}$activityId=(int)$activity['id'];
$courseId=max(0,(int)($_GET['course']??0));$cohortId=max(0,(int)($_GET['cohort']??0));$pageId=max(0,(int)($_GET['page']??0));
$course=$courseId>0?course_by_id($db,$courseId):null;if(!$course||(int)$course['activity_id']!==$activityId){http_response_code(404);exit('Curso não encontrado.');}
$cohort=admin_course_cohort($db,$courseId,$cohortId,false);if(!$cohort){http_response_code(404);exit('Turma não encontrada.');}
if(!course_material_page_is_associated($db,$courseId,$pageId)){http_response_code(404);exit('Material não encontrado neste curso.');}
$page=cms_page_by_id($db,$pageId);if(!$page||(int)$page['activity_id']!==$activityId||(string)$page['status']==='archived'||empty($page['published_document_json'])){http_response_code(404);exit('Este material não possui uma versão publicada para prévia.');}
$document=cms_page_doc($page,true);$previewUser=['id'=>0,'name'=>'Prévia administrativa','email'=>''];$enrollmentContext=['cohort_id'=>$cohortId,'course_id'=>$courseId,'activity_id'=>$activityId,'material_page_id'=>$pageId];
$document['html']=cms_access_filter_html($db,$activity,(string)($document['html']??''),$previewUser,false,null,$enrollmentContext);$document=student_page_resolve_private_media_slots($db,$page,$document);
$back=admin_cohort_url($activityId,$courseId,$cohortId,'lessons');$banner='<section aria-label="Prévia administrativa"><p><strong>PRÉVIA — '.h((string)$cohort['title']).'</strong></p><p>Esta página foi filtrada com as regras reais de acesso desta turma. <a href="'.h($back).'">Voltar para Aulas e acesso</a></p></section>';
$document['html']=$banner.(string)($document['html']??'');student_private_headers();cms_render_public_page($activity,$page,$document,false);