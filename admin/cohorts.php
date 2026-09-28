<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();require_admin();
$db=database();$state=admin_activity_resolution($db);$activity=$state['activity'];if(!$activity){header('Location: /admin/activities.php');exit;}$activityId=(int)$activity['id'];
$courses=course_list($db,$activityId);
$courseId=max(0,(int)($_GET['course']??$_POST['course_id']??0));$course=$courseId>0?course_by_id($db,$courseId):null;if($course&&(int)$course['activity_id']!==$activityId){$course=null;$courseId=0;}
$q=trim((string)($_GET['q']??''));$status=(string)($_GET['status']??'active');if(!in_array($status,['active','archived','all'],true))$status='active';$page=max(1,(int)($_GET['page']??1));$pageSize=50;
function cohorts_admin_url(int $activityId,int $courseId=0,string $q='',string $status='active',int $page=1): string {$args=['activity'=>$activityId];if($courseId>0)$args['course']=$courseId;if($q!=='')$args['q']=$q;if($status!=='active')$args['status']=$status;if($page>1)$args['page']=$page;return '/admin/cohorts.php?'.http_build_query($args);}
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('cohorts-admin',$_POST['_csrf']??null)){http_response_code(403);exit('Invalid request');}
    try{
        if(!$course)throw new RuntimeException('Escolha o curso da nova turma.');
        course_create_cohort_for_course($db,$courseId,(string)($_POST['title']??''),(string)($_POST['slug']??''),(string)($_POST['starts_at']??'')?:null,(string)($_POST['ends_at']??'')?:null);
        $_SESSION['admin_cohorts_notice']='Turma criada.';
    }catch(Throwable $e){$_SESSION['admin_cohorts_notice']='Não foi possível concluir: '.$e->getMessage();}
    header('Location: '.cohorts_admin_url($activityId,$courseId,$q,$status,$page),true,303);exit;
}
$where=['c.activity_id=?',"c.status!='archived'"];$args=[$activityId];if($courseId>0){$where[]='cc.course_id=?';$args[]=$courseId;}if($status==='active')$where[]="cc.status!='archived'";elseif($status==='archived')$where[]="cc.status='archived'";if($q!==''){$where[]='(cc.title LIKE ? OR cc.slug LIKE ? OR c.title LIKE ?)';$like='%'.$q.'%';array_push($args,$like,$like,$like);}$whereSql=implode(' AND ',$where);
$count=$db->prepare("SELECT COUNT(*) FROM course_cohorts cc JOIN courses c ON c.id=cc.course_id WHERE $whereSql");$count->execute($args);$total=(int)$count->fetchColumn();$pages=max(1,(int)ceil($total/$pageSize));if($page>$pages)$page=$pages;$offset=($page-1)*$pageSize;
$sql="SELECT cc.id,cc.course_id,cc.title,cc.slug,cc.status,cc.starts_at,cc.ends_at,c.title course_title,SUM(CASE WHEN e.status='active' THEN 1 ELSE 0 END) student_count FROM course_cohorts cc JOIN courses c ON c.id=cc.course_id LEFT JOIN course_enrollments e ON e.cohort_id=cc.id WHERE $whereSql GROUP BY cc.id ORDER BY cc.starts_at IS NULL,cc.starts_at DESC,cc.id DESC LIMIT $pageSize OFFSET $offset";$stmt=$db->prepare($sql);$stmt->execute($args);$rows=$stmt->fetchAll();
$notice=(string)($_SESSION['admin_cohorts_notice']??'');unset($_SESSION['admin_cohorts_notice']);
admin_shell_start('cohorts','Turmas',$state);?>
<?php if($notice!==''):?><div class="admin-notice"><?=h($notice)?></div><?php endif;?>
<form class="admin-filter-bar" method="get">
  <input type="hidden" name="activity" value="<?=$activityId?>">
  <label>Curso<select name="course"><option value="0">Todos os cursos</option><?php foreach($courses as $item):?><option value="<?=(int)$item['id']?>"<?=$courseId===(int)$item['id']?' selected':''?>><?=h((string)$item['title'])?></option><?php endforeach;?></select></label>
  <label>Status<select name="status"><option value="active"<?=$status==='active'?' selected':''?>>Ativas</option><option value="archived"<?=$status==='archived'?' selected':''?>>Arquivadas</option><option value="all"<?=$status==='all'?' selected':''?>>Todas</option></select></label>
  <label class="grow">Buscar<input name="q" value="<?=h($q)?>" placeholder="Turma, slug ou curso"></label>
  <div class="admin-filter-actions"><button class="admin-button secondary" type="submit">Aplicar</button><?php if($courseId>0||$q!==''||$status!=='active'):?><a class="admin-button secondary" href="<?=h(cohorts_admin_url($activityId))?>">Limpar</a><?php endif;?></div>
</form>
<div class="admin-list-summary"><span><?php if($total>0):?>Mostrando <?=($offset+1)?>–<?=min($offset+$pageSize,$total)?> de <?=$total?> turma(s)<?php else:?>Nenhuma turma neste filtro<?php endif;?></span><?php if($course):?><a href="<?=h(admin_course_url($activityId,$courseId))?>">Curso: <?=h((string)$course['title'])?> ↗</a><?php endif;?></div>
<?php if(!$rows):?><div class="admin-empty">Nenhuma turma encontrada.</div><?php else:?><div class="admin-table-scroll"><table class="admin-data-table"><thead><tr><th>Turma</th><th>Curso</th><th>Período</th><th>Alunos</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($rows as $row):?><tr><td><div class="admin-table-primary"><strong><?=h((string)$row['title'])?></strong><span><?=h((string)$row['slug'])?></span></div></td><td><a href="<?=h(admin_course_url($activityId,(int)$row['course_id']))?>"><?=h((string)$row['course_title'])?></a></td><td><?=h((string)($row['starts_at']?:'—'))?> → <?=h((string)($row['ends_at']?:'—'))?></td><td class="admin-table-number"><?=(int)$row['student_count']?></td><td><?=h((string)$row['status'])?></td><td class="actions"><a class="admin-button secondary admin-table-action" href="/admin/students.php?<?=h(http_build_query(['activity'=>$activityId,'course'=>(int)$row['course_id'],'cohort'=>(int)$row['id']]))?>">Ver alunos</a></td></tr><?php endforeach;?></tbody></table></div><?php endif;?>
<div class="admin-pagination"><span>Página <?=$page?> de <?=$pages?></span><?php if($pages>1):?><nav aria-label="Paginação de turmas"><?php if($page>1):?><a href="<?=h(cohorts_admin_url($activityId,$courseId,$q,$status,$page-1))?>">← Anterior</a><?php endif;?><?php if($page<$pages):?><a href="<?=h(cohorts_admin_url($activityId,$courseId,$q,$status,$page+1))?>">Próxima →</a><?php endif;?></nav><?php endif;?></div>
<?php if($course):?><details class="admin-secondary-setup"><summary>Criar turma para <?=h((string)$course['title'])?></summary><section class="admin-card"><form class="admin-form-grid" method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cohorts-admin'))?>"><input type="hidden" name="course_id" value="<?=$courseId?>"><label class="admin-form-span-2">Nome<input name="title" required></label><label>Slug<input name="slug"></label><label>Início<input type="date" name="starts_at"></label><label>Fim<input type="date" name="ends_at"></label><div class="admin-form-actions"><button class="admin-button" type="submit">Criar turma</button></div></form></section></details><?php endif;?>
<?php admin_shell_end();
