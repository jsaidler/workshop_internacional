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

function integrity_table_exists(PDO $db,string $table): bool {
    $q=$db->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name=?");
    $q->execute([$table]);return (bool)$q->fetchColumn();
}
function integrity_scalar(PDO $db,string $sql,array $args=[]): int {
    $q=$db->prepare($sql);$q->execute($args);return (int)$q->fetchColumn();
}

$summary=[
    'people'=>integrity_scalar($db,'SELECT COUNT(*) FROM student_users'),
    'enrollments'=>integrity_scalar($db,'SELECT COUNT(*) FROM course_enrollments'),
    'active_enrollments'=>integrity_scalar($db,"SELECT COUNT(*) FROM course_enrollments WHERE status='active'"),
    'orphan_enrollments'=>integrity_scalar($db,"SELECT COUNT(*) FROM course_enrollments e JOIN course_cohorts c ON c.id=e.cohort_id WHERE e.status='active' AND c.course_id IS NULL"),
    'courses'=>integrity_table_exists($db,'courses')?integrity_scalar($db,"SELECT COUNT(*) FROM courses WHERE status!='archived'"):0,
    'orphan_cohorts'=>integrity_table_exists($db,'courses')?integrity_scalar($db,"SELECT COUNT(*) FROM course_cohorts WHERE status!='archived' AND course_id IS NULL"):0,
    'orphan_lessons'=>integrity_table_exists($db,'courses')?integrity_scalar($db,'SELECT COUNT(*) FROM course_lessons WHERE course_id IS NULL'):0,
    'unscoped_registrations'=>integrity_table_exists($db,'courses')?integrity_scalar($db,"SELECT COUNT(*) FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE f.purpose='enrollment' AND s.course_id IS NULL"):0,
    'import_batches'=>integrity_table_exists($db,'student_import_batches')?integrity_scalar($db,'SELECT COUNT(*) FROM student_import_batches'):0,
];

$courses=[];
if(integrity_table_exists($db,'courses')){
    $q=$db->prepare("SELECT c.id,c.title,c.slug,c.status,c.public_page_id,c.registration_form_id,
        p.title public_page_title,f.title registration_form_title,
        (SELECT COUNT(*) FROM course_cohorts cc WHERE cc.course_id=c.id AND cc.status!='archived') cohort_count,
        (SELECT COUNT(*) FROM course_enrollments e JOIN course_cohorts cc ON cc.id=e.cohort_id WHERE cc.course_id=c.id AND e.status='active') enrollment_count,
        (SELECT COUNT(*) FROM course_lessons l WHERE l.course_id=c.id) lesson_count,
        (SELECT COUNT(*) FROM course_material_pages mp WHERE mp.course_id=c.id) material_count,
        (SELECT COUNT(*) FROM cms_form_submissions s WHERE s.course_id=c.id) registration_count
        FROM courses c
        LEFT JOIN cms_pages p ON p.id=c.public_page_id
        LEFT JOIN cms_forms f ON f.id=c.registration_form_id
        WHERE c.status!='archived'".($activityId>0?' AND c.activity_id=?':'')."
        ORDER BY c.id");
    $q->execute($activityId>0?[$activityId]:[]);$courses=$q->fetchAll();
}

$orphanCohorts=[];
if(integrity_table_exists($db,'courses')){
    $sql="SELECT c.id,c.title,c.slug,c.status,c.workshop_page_id,
        p.title workshop_page_title,
        (SELECT COUNT(*) FROM course_enrollments e WHERE e.cohort_id=c.id AND e.status='active') enrollment_count,
        ".(integrity_table_exists($db,'student_import_batches')?"(SELECT COUNT(*) FROM student_import_batches b WHERE b.cohort_id=c.id)":"0")." import_batch_count
        FROM course_cohorts c
        LEFT JOIN cms_pages p ON p.id=c.workshop_page_id
        WHERE c.course_id IS NULL AND c.status!='archived'".($activityId>0?' AND c.activity_id=?':'')."
        ORDER BY c.id";
    $q=$db->prepare($sql);$q->execute($activityId>0?[$activityId]:[]);$orphanCohorts=$q->fetchAll();
}

$orphanEnrollments=[];
if(integrity_table_exists($db,'courses')){
    $sql="SELECT e.id enrollment_id,e.student_id,u.name,u.email,c.id cohort_id,c.title cohort_title,c.workshop_page_id,p.title workshop_page_title,e.source_submission_id,e.confirmed_at
        FROM course_enrollments e
        JOIN student_users u ON u.id=e.student_id
        JOIN course_cohorts c ON c.id=e.cohort_id
        LEFT JOIN cms_pages p ON p.id=c.workshop_page_id
        WHERE e.status='active' AND c.course_id IS NULL".($activityId>0?' AND c.activity_id=?':'')."
        ORDER BY c.id,u.name COLLATE NOCASE,e.id LIMIT 500";
    $q=$db->prepare($sql);$q->execute($activityId>0?[$activityId]:[]);$orphanEnrollments=$q->fetchAll();
}

$imports=[];
if(integrity_table_exists($db,'student_import_batches')){
    $sql="SELECT b.id,b.original_name,b.total_rows,b.imported_rows,b.existing_rows,b.error_rows,b.created_at,
        c.id cohort_id,c.title cohort_title,c.course_id,cr.title course_title
        FROM student_import_batches b
        JOIN course_cohorts c ON c.id=b.cohort_id
        LEFT JOIN courses cr ON cr.id=c.course_id".($activityId>0?' WHERE c.activity_id=?':'')."
        ORDER BY b.id DESC LIMIT 100";
    $q=$db->prepare($sql);$q->execute($activityId>0?[$activityId]:[]);$imports=$q->fetchAll();
}

$diagnostic=[
    'generated_at'=>gmdate('c'),
    'activity_id'=>$activityId?:null,
    'summary'=>$summary,
    'courses'=>$courses,
    'orphan_cohorts'=>$orphanCohorts,
    'orphan_enrollments'=>$orphanEnrollments,
    'import_batches'=>$imports,
];

if(($_GET['format']??'')==='json'){
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="admin-domain-integrity.json"');
    echo json_encode($diagnostic,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    exit;
}

$hasIntegrityProblems=$summary['orphan_enrollments']>0||$summary['orphan_cohorts']>0||$summary['unscoped_registrations']>0||$summary['orphan_lessons']>0;
admin_shell_start('integrity','Integridade',$state);
?>
<section class="overview-hero"><div><p class="admin-kicker">Somente leitura</p><h2>Diagnóstico de integridade</h2><p>Esta tela não corrige nem migra dados. Ela mostra o estado real antes de qualquer reconciliação destrutiva ou inferencial.</p></div><div class="hero-actions"><a class="admin-button secondary" href="?<?=http_build_query(array_filter(['activity'=>$activityId?:null,'format'=>'json']))?>">Baixar diagnóstico JSON</a></div></section>

<?php if($hasIntegrityProblems):?><div class="admin-notice">Há vínculos que precisam de reconciliação explícita. Nenhum deles será corrigido automaticamente.</div><?php else:?><div class="admin-notice">Nenhum vínculo órfão foi detectado pelas verificações atuais.</div><?php endif;?>

<div class="admin-stat-grid">
<div class="admin-stat"><span>Pessoas</span><strong><?=$summary['people']?></strong></div>
<div class="admin-stat"><span>Matrículas ativas</span><strong><?=$summary['active_enrollments']?></strong></div>
<div class="admin-stat"><span>Cursos</span><strong><?=$summary['courses']?></strong></div>
<div class="admin-stat"><span>Matrículas sem curso</span><strong><?=$summary['orphan_enrollments']?></strong></div>
<div class="admin-stat"><span>Turmas sem curso</span><strong><?=$summary['orphan_cohorts']?></strong></div>
<div class="admin-stat"><span>Inscrições sem curso</span><strong><?=$summary['unscoped_registrations']?></strong></div>
</div>

<section class="admin-card"><header><div><h2>Cursos existentes</h2><p>Esta tabela permite identificar cursos criados pela inferência anterior e comparar suas dependências antes de qualquer consolidação.</p></div></header>
<?php if(!$courses):?><div class="admin-empty">Nenhum curso encontrado.</div><?php else:?><div class="admin-table-scroll"><table class="admin-data-table"><thead><tr><th>ID</th><th>Curso</th><th>Página / formulário</th><th>Turmas</th><th>Alunos</th><th>Aulas</th><th>Material</th><th>Inscrições</th></tr></thead><tbody>
<?php foreach($courses as $row):?><tr>
<td>#<?=(int)$row['id']?></td>
<td><strong><?=h((string)$row['title'])?></strong><div class="muted"><?=h((string)$row['slug'])?></div></td>
<td><?=h((string)($row['public_page_title']?:'Sem página'))?><div class="muted"><?=h((string)($row['registration_form_title']?:'Sem formulário'))?></div></td>
<td><?=(int)$row['cohort_count']?></td><td><?=(int)$row['enrollment_count']?></td><td><?=(int)$row['lesson_count']?></td><td><?=(int)$row['material_count']?></td><td><?=(int)$row['registration_count']?></td>
</tr><?php endforeach;?>
</tbody></table></div><?php endif;?>
</section>

<section class="admin-card"><header><div><h2>Turmas sem curso</h2><p>Turmas históricas ou importadas continuam existindo aqui mesmo quando ficaram invisíveis na nova tela contextual.</p></div></header>
<?php if(!$orphanCohorts):?><div class="admin-empty">Nenhuma turma sem curso.</div><?php else:?><div class="admin-table-scroll"><table class="admin-data-table"><thead><tr><th>ID</th><th>Turma</th><th>Página legada</th><th>Matrículas</th><th>Importações</th></tr></thead><tbody>
<?php foreach($orphanCohorts as $row):?><tr><td>#<?=(int)$row['id']?></td><td><strong><?=h((string)$row['title'])?></strong><div class="muted"><?=h((string)$row['slug'])?></div></td><td><?=h((string)($row['workshop_page_title']?:($row['workshop_page_id']?'#'.$row['workshop_page_id']:'—')))?></td><td><?=(int)$row['enrollment_count']?></td><td><?=(int)$row['import_batch_count']?></td></tr><?php endforeach;?>
</tbody></table></div><?php endif;?>
</section>

<section class="admin-card"><header><div><h2>Matrículas ativas sem curso</h2><p>Estas pessoas não foram apagadas. A matrícula existe, mas a turma ainda não aponta para um curso canônico.</p></div></header>
<?php if(!$orphanEnrollments):?><div class="admin-empty">Nenhuma matrícula ativa sem curso.</div><?php else:?><div class="admin-table-scroll"><table class="admin-data-table"><thead><tr><th>Aluno</th><th>Turma</th><th>Página legada</th><th>Origem</th></tr></thead><tbody>
<?php foreach($orphanEnrollments as $row):?><tr><td><a href="/admin/people.php?person=<?=(int)$row['student_id']?>"><strong><?=h((string)$row['name'])?></strong></a><div class="muted"><?=h((string)$row['email'])?></div></td><td>#<?=(int)$row['cohort_id']?> · <?=h((string)$row['cohort_title'])?></td><td><?=h((string)($row['workshop_page_title']?:($row['workshop_page_id']?'#'.$row['workshop_page_id']:'—')))?></td><td><?=($row['source_submission_id']??null)?'Inscrição #'.(int)$row['source_submission_id']:'Importação/manual'?></td></tr><?php endforeach;?>
</tbody></table></div><?php endif;?>
</section>

<section class="admin-card"><header><div><h2>Importações históricas</h2><p>O lote continua ligado à turma original. Se essa turma estiver sem curso, o lote aparece explicitamente como tal.</p></div></header>
<?php if(!$imports):?><div class="admin-empty">Nenhuma importação histórica encontrada.</div><?php else:?><div class="admin-table-scroll"><table class="admin-data-table"><thead><tr><th>Lote</th><th>Arquivo</th><th>Turma</th><th>Curso</th><th>Resultado</th></tr></thead><tbody>
<?php foreach($imports as $row):?><tr><td>#<?=(int)$row['id']?></td><td><?=h((string)$row['original_name'])?></td><td><?=h((string)$row['cohort_title'])?></td><td><?=h((string)($row['course_title']?:'Sem curso associado'))?></td><td><?=(int)$row['imported_rows']?> novas · <?=(int)$row['existing_rows']?> existentes · <?=(int)$row['error_rows']?> erros</td></tr><?php endforeach;?>
</tbody></table></div><?php endif;?>
</section>

<?php admin_shell_end();
