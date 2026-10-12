<?php
declare(strict_types=1);
/* Reutiliza o template PHP real da ficha de aula por turma.
 * Os dados são estados controlados; não há login, matrícula nem DB hospedado. */
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'audit-csrf-token';}
function cms_access_lesson_release_state(?string $utc): string {return $utc===null?'blocked':(strtotime($utc)>time()?'scheduled':'released');}
function admin_course_lesson_material_items(PDO $db,int $courseId,int $lessonId): array {
    return $lessonId===101?[['page_title'=>'Manual de fotografia','section_label'=>'Exposição e ortocromatismo']]:[];
}
function student_tools_released_by_lesson(PDO $db,int $courseId,int $lessonId): array {
    return $lessonId===102?[['label'=>'Reciprocidade e exposições']]:[];
}
require __DIR__.'/../../app/admin_shell.php';
require __DIR__.'/../../app/course_agenda.php';
$db=new PDO('sqlite::memory:');
$activityId=1;$courseId=11;$cohortId=21;
$activity=['id'=>1,'admin_name'=>'JSaidler Fotografia','is_root'=>1,'slug'=>''];
$state=['activity'=>$activity];
$course=['id'=>11,'title'=>'Positivo Direto em Filmes de Raio-X','status'=>'active','agenda_default_capacity'=>10];
$cohort=['id'=>21,'title'=>'Turma de outubro','status'=>'active','agenda_capacity'=>10,'agenda_enrollment_closes_at'=>null,'agenda_listeners_open_at'=>null];
$materials=[['id'=>33]];
$impact=[101=>['sections'=>1,'pages'=>1],102=>['sections'=>0,'pages'=>0],103=>['sections'=>0,'pages'=>0]];
$releaseRows=[
 ['id'=>101,'sort_order'=>1,'title'=>'Características do filme e exposição','released_at'=>null,'meeting_starts_at'=>'2030-10-12T22:00:00Z','meeting_timezone'=>'America/Sao_Paulo','meeting_duration_minutes'=>120,'meeting_url'=>'https://example.org/meeting-private','meeting_notes'=>'Acesso enviado aos alunos.','meeting_status'=>'scheduled'],
 ['id'=>102,'sort_order'=>2,'title'=>'Produção do positivo direto','released_at'=>null,'meeting_starts_at'=>null,'meeting_timezone'=>'America/Sao_Paulo','meeting_duration_minutes'=>null,'meeting_url'=>'','meeting_notes'=>'','meeting_status'=>'scheduled'],
 ['id'=>103,'sort_order'=>3,'title'=>'Revisão dos resultados','released_at'=>'2025-10-12T22:00:00Z','meeting_starts_at'=>'2025-10-12T22:00:00Z','meeting_timezone'=>'America/Sao_Paulo','meeting_duration_minutes'=>90,'meeting_url'=>'https://example.org/past-meeting','meeting_notes'=>'Encontro concluído.','meeting_status'=>'completed']
];
$capacity=['limit'=>10,'occupied'=>3,'remaining'=>7,'regular'=>3,'excess'=>0];
$listeners=[['id'=>7,'student_name'=>'Aluno de referência','status'=>'waiting','requested_at'=>'2026-10-12T15:00:00Z']];
$defaultTimezone='America/Sao_Paulo';
$_GET['edit']=(string)($_GET['edit']??'101');
admin_shell_start('lessons','Aulas e acesso',$state,['/assets/admin-teaching.css','/assets/admin-operations.css']);
require __DIR__.'/../../app/admin_course_agenda_cohort.php';
admin_shell_end();
