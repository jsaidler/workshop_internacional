<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();
require_admin();

$db=database();
$state=admin_activity_resolution($db);
$activity=$state['activity']??null;
$activityId=(int)($activity['id']??0);

$q=trim((string)($_GET['q']??''));
$personId=(int)($_GET['person']??0);

$where=[];$params=[];
if($q!==''){
    $where[]='(u.name LIKE ? OR u.email LIKE ? OR p.cpf_last4 LIKE ?)';
    $like='%'.$q.'%';$params=[$like,$like,$like];
}
$sql="SELECT u.id,u.name,u.email,u.status,u.activated_at,u.last_login_at,p.cpf_last4,
    (SELECT COUNT(*) FROM course_enrollments e WHERE e.student_id=u.id AND e.status='active') enrollment_count,
    (SELECT COUNT(*) FROM course_enrollments e JOIN course_cohorts c ON c.id=e.cohort_id WHERE e.student_id=u.id AND e.status='active' AND c.course_id IS NULL) orphan_enrollment_count,
    (SELECT COUNT(*) FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE s.student_id=u.id AND f.purpose='enrollment') registration_count
    FROM student_users u
    LEFT JOIN student_profiles p ON p.student_id=u.id";
if($where)$sql.=' WHERE '.implode(' AND ',$where);
$sql.=' ORDER BY u.name COLLATE NOCASE,u.id LIMIT 500';
$stmt=$db->prepare($sql);$stmt->execute($params);$people=$stmt->fetchAll();

$summaryQ=$db->query("SELECT
  (SELECT COUNT(*) FROM student_users) people,
  (SELECT COUNT(*) FROM student_users WHERE status='active') active_accounts,
  (SELECT COUNT(*) FROM course_enrollments WHERE status='active') active_enrollments");
$summary=$summaryQ->fetch()?:['people'=>0,'active_accounts'=>0,'active_enrollments'=>0];

$selected=null;$enrollments=[];$registrations=[];
if($personId>0){
    $stmt=$db->prepare("SELECT u.id,u.name,u.email,u.status,u.activated_at,u.last_login_at,p.cpf_last4,p.phone,p.instagram,p.city_state
        FROM student_users u LEFT JOIN student_profiles p ON p.student_id=u.id WHERE u.id=? LIMIT 1");
    $stmt->execute([$personId]);$selected=$stmt->fetch()?:null;
    if($selected){
        $stmt=$db->prepare("SELECT e.id,e.status,e.confirmed_at,e.source_submission_id,c.id cohort_id,c.title cohort_title,c.status cohort_status,c.course_id,c.workshop_page_id,cr.title course_title
            FROM course_enrollments e
            JOIN course_cohorts c ON c.id=e.cohort_id
            LEFT JOIN courses cr ON cr.id=c.course_id
            WHERE e.student_id=?
            ORDER BY e.confirmed_at DESC,e.id DESC");
        $stmt->execute([$personId]);$enrollments=$stmt->fetchAll();
        $stmt=$db->prepare("SELECT s.id,s.status,s.payment_status,s.created_at,s.course_id,s.cohort_id,c.title course_title,f.title form_title
            FROM cms_form_submissions s
            JOIN cms_forms f ON f.id=s.form_id
            LEFT JOIN courses c ON c.id=s.course_id
            WHERE s.student_id=? AND f.purpose='enrollment'
            ORDER BY s.created_at DESC,s.id DESC");
        $stmt->execute([$personId]);$registrations=$stmt->fetchAll();
    }
}

function people_admin_url(int $personId=0,string $q=''): string {
    $args=[];if($personId>0)$args['person']=$personId;if($q!=='')$args['q']=$q;
    return '/admin/people.php'.($args?'?'.http_build_query($args):'');
}

admin_shell_start('people','Pessoas',$state);
?>
<section class="overview-hero"><div><p class="admin-kicker">Identidade global</p><h2>Pessoas</h2><p>Cada pessoa existe uma única vez. Inscrições e matrículas aparecem como relações dessa identidade com os cursos.</p></div></section>
<div class="admin-person-summary">
  <div class="admin-stat"><span>Pessoas</span><strong><?=(int)$summary['people']?></strong></div>
  <div class="admin-stat"><span>Contas ativas</span><strong><?=(int)$summary['active_accounts']?></strong></div>
  <div class="admin-stat"><span>Matrículas ativas</span><strong><?=(int)$summary['active_enrollments']?></strong></div>
</div>
<form class="admin-data-toolbar" method="get">
    <?php if($activityId>0):?><input type="hidden" name="activity" value="<?=$activityId?>"><?php endif;?>
    <label class="grow">Buscar<input name="q" value="<?=h($q)?>" placeholder="Nome, e-mail ou final do CPF"></label>
    <button class="admin-button secondary" type="submit">Buscar</button>
</form>

<div class="submissions-layout inbox-layout">
<section class="submission-list inbox-list" aria-label="Pessoas">
<?php if(!$people):?><div class="admin-empty compact">Nenhuma pessoa encontrada.</div><?php endif;?>
<?php foreach($people as $row):?>
<a class="submission-row<?=$personId===(int)$row['id']?' selected':''?>" href="<?=h(people_admin_url((int)$row['id'],$q))?>">
    <div class="submission-row-main"><strong><?=h((string)$row['name'])?></strong><span><?=h((string)$row['email'])?></span></div>
    <div class="submission-row-meta"><small><?=(int)$row['enrollment_count']?> matrícula(s) · <?=(int)$row['registration_count']?> inscrição(ões)</small><?php if((int)$row['orphan_enrollment_count']>0):?><small>⚠ vínculo incompleto</small><?php endif;?></div>
</a>
<?php endforeach;?>
</section>

<?php if($selected):?>
<aside class="submission-detail inbox-detail">
<header class="admin-person-identity"><div><p class="admin-kicker">Pessoa</p><h2><?=h((string)$selected['name'])?></h2><p><?=h((string)$selected['email'])?></p></div></header>
<section class="registration-admin-group"><h3>Conta</h3><dl class="inbox-fields">
<div><dt>Status</dt><dd><?=h((string)$selected['status'])?></dd></div>
<div><dt>CPF</dt><dd><?=$selected['cpf_last4']?'***.'.h((string)$selected['cpf_last4']):'—'?></dd></div>
<?php if(!empty($selected['phone'])):?><div><dt>Telefone</dt><dd><?=h((string)$selected['phone'])?></dd></div><?php endif;?>
<?php if(!empty($selected['instagram'])):?><div><dt>Instagram</dt><dd><?=h((string)$selected['instagram'])?></dd></div><?php endif;?>
<?php if(!empty($selected['city_state'])):?><div><dt>Cidade/UF</dt><dd><?=h((string)$selected['city_state'])?></dd></div><?php endif;?>
<div><dt>Ativação</dt><dd><?=h((string)($selected['activated_at']?:'Pendente'))?></dd></div>
<div><dt>Último acesso</dt><dd><?=h((string)($selected['last_login_at']?:'—'))?></dd></div>
</dl></section>

<section class="registration-admin-group"><h3>Matrículas</h3>
<?php if(!$enrollments):?><p class="muted">Nenhuma matrícula.</p><?php else:?><div class="admin-table-scroll"><table class="admin-data-table"><thead><tr><th>Curso</th><th>Turma</th><th>Origem</th><th>Status</th></tr></thead><tbody>
<?php foreach($enrollments as $row):?><tr>
<td><?php if((int)($row['course_id']??0)>0):?><a href="<?=h(admin_course_context_url($activityId,(int)$row['course_id'],'students'))?>"><?=h((string)$row['course_title'])?></a><?php else:?><strong>Vínculo sem curso</strong><?php endif;?></td>
<td><?=h((string)$row['cohort_title'])?></td>
<td><?=($row['source_submission_id']??null)?'Inscrição #'.(int)$row['source_submission_id']:'Importação/manual'?></td>
<td><?=h((string)$row['status'])?></td>
</tr><?php endforeach;?>
</tbody></table></div><?php endif;?>
</section>

<section class="registration-admin-group"><h3>Inscrições</h3>
<?php if(!$registrations):?><p class="muted">Nenhuma inscrição vinculada a esta identidade.</p><?php else:?><div class="admin-table-scroll"><table class="admin-data-table"><thead><tr><th>Curso</th><th>Formulário</th><th>Pagamento</th><th>Turma</th></tr></thead><tbody>
<?php foreach($registrations as $row):?><tr>
<td><?php if((int)($row['course_id']??0)>0):?><a href="/admin/registrations.php?activity=<?=$activityId?>&course=<?=(int)$row['course_id']?>&submission=<?=(int)$row['id']?>"><?=h((string)$row['course_title'])?></a><?php else:?>Sem curso associado<?php endif;?></td>
<td><?=h((string)$row['form_title'])?></td>
<td><?=h((string)($row['payment_status']?:$row['status']))?></td>
<td><?=((int)($row['cohort_id']??0)>0)?'#'.(int)$row['cohort_id']:'Não definida'?></td>
</tr><?php endforeach;?>
</tbody></table></div><?php endif;?>
</section>
</aside>
<?php endif;?>
</div>
<link rel="stylesheet" href="<?=h(admin_asset_url('/assets/admin-inbox.css'))?>">
<link rel="stylesheet" href="<?=h(admin_asset_url('/assets/admin-registration.css'))?>">
<?php admin_shell_end();
