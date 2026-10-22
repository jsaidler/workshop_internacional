<?php
declare(strict_types=1);

/**
 * Staff alerts for student-initiated pedagogical actions. Reuses the site's
 * authenticated admin identities and the existing CLI communications worker.
 * Stores references only: no private question body or images in the outbox.
 */
function admin_student_notice_kinds(): array {
    return [
        'question_created'=>'Novas dúvidas',
        'question_replied'=>'Respostas de alunos às dúvidas',
        'test_submitted'=>'Registros enviados para avaliação',
        'test_commented'=>'Comentários de alunos nos registros',
    ];
}
function admin_student_notice_available(PDO $db): bool {
    $q=$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='admin_student_notice_outbox'");
    return (bool)$q->fetchColumn();
}
function admin_student_notice_preferences(PDO $db,int $adminId): array {
    $result=array_fill_keys(array_keys(admin_student_notice_kinds()),true);
    $q=$db->prepare('SELECT event_kind,enabled FROM admin_student_notice_preferences WHERE admin_id=?');
    $q->execute([$adminId]);
    foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row)$result[(string)$row['event_kind']]=(int)$row['enabled']===1;
    return $result;
}
function admin_student_notice_set_preferences(PDO $db,int $adminId,array $enabled): void {
    $q=$db->prepare('SELECT 1 FROM admin_users WHERE id=?');$q->execute([$adminId]);
    if(!$q->fetchColumn())throw new RuntimeException('Administrador não encontrado.');
    $statement=$db->prepare('INSERT INTO admin_student_notice_preferences(admin_id,event_kind,enabled,updated_at) VALUES(?,?,?,?)
        ON CONFLICT(admin_id,event_kind) DO UPDATE SET enabled=excluded.enabled,updated_at=excluded.updated_at');
    foreach(admin_student_notice_kinds() as $kind=>$label)$statement->execute([$adminId,$kind,isset($enabled[$kind])?1:0,utc_now()]);
}
/**
 * Call only after the authenticated domain mutation succeeds. When a domain
 * service is used inside a transaction, the queued alert joins that transaction.
 * Outbox failures are logged, never turn an already-persisted student action
 * into an apparent failure or cause external delivery inside a HTTP request.
 */
function admin_student_notice_capture(PDO $db,int $studentId,int $cohortId,string $kind,int $targetId,string $eventKey): void {
    if(!isset(admin_student_notice_kinds()[$kind])||$studentId<1||$cohortId<1||$targetId<1||$eventKey==='')return;
    try{
        if(!admin_student_notice_available($db))return; // legacy standalone fixtures
        $now=utc_now();
        $q=$db->prepare(<<<'SQL'
INSERT OR IGNORE INTO admin_student_notice_outbox(admin_id,student_id,cohort_id,event_kind,target_id,event_key,created_at,updated_at)
SELECT a.id,?,?,?, ?,?,?,?
FROM admin_users a
LEFT JOIN admin_student_notice_preferences pref ON pref.admin_id=a.id AND pref.event_kind=?
WHERE pref.enabled IS NULL OR pref.enabled=1
SQL);
        $q->execute([$studentId,$cohortId,$kind,$targetId,substr($eventKey,0,120),$now,$now,$kind]);
    }catch(Throwable $e){error_log('Student administrative notification could not be queued: '.$e->getCode());}
}
function admin_student_notice_target(PDO $db,array $row): ?array {
    // Re-check that the referenced question/test still belongs to this student
    // and cohort. Deleted content and inactive student accounts are not emailed.
    $kind=(string)$row['event_kind'];$targetId=(int)$row['target_id'];
    $source=in_array($kind,['question_created','question_replied'],true)?'student_questions':'student_tests';
    $q=$db->prepare("SELECT id,title FROM $source WHERE id=? AND student_id=? AND cohort_id=?");
    $q->execute([$targetId,(int)$row['student_id'],(int)$row['cohort_id']]);
    return $q->fetch(PDO::FETCH_ASSOC)?:null;
}
function admin_student_notice_message(array $job,array $target): array {
    $kind=(string)$job['event_kind'];$labels=[
       'question_created'=>'Nova dúvida',
       'question_replied'=>'Resposta de aluno em dúvida',
       'test_submitted'=>'Registro enviado para avaliação',
       'test_commented'=>'Comentário de aluno em registro',
    ];
    $heading=$labels[$kind];$name=trim((string)$job['student_name']);
    $params=['activity'=>(int)$job['activity_id'],'course'=>(int)$job['course_id'],
      'cohort'=>(int)$job['cohort_id']];
    $url=in_array($kind,['question_created','question_replied'],true)?
       '/admin/questions.php?'.http_build_query($params+['id'=>(int)$target['id']]):
       '/admin/tests.php?'.http_build_query($params+['test'=>(int)$target['id']]);
    $site=rtrim((string)(app_config()['public_url']??''),'/');
    $link=preg_match('~^https://[^/]+~i',$site)?$site.$url:$url;
    $body=$heading."\n\nAluno: ".$name."\nCurso: ".$job['course_title'].
        "\nTurma: ".$job['cohort_title']."\nItem: ".$target['title'].
        "\n\nAbrir na administração: ".$link.
        "\n\nA mensagem completa e as imagens permanecem no CMS.";
    return ['name'=>(string)$job['admin_email'],'email'=>(string)$job['admin_email'],
      'subject'=>'['.(string)(app_config()['site_name']??'Cursos').'] '.$heading.' — '.$name,
      'body'=>$body];
}
/**
 * Uses verified Brevo transport if configured; otherwise the same PHP mail()
 * transport already used by the CMS for new form responses.
 */
function admin_student_notice_send(array $message): array {
    return communication_send_message(database(),$message);
}

function admin_student_notice_dispatch(PDO $db,int $limit=20,?callable $sender=null): array {
    $result=['sent'=>0,'skipped'=>0,'failed'=>0];
    if(!admin_student_notice_available($db))return $result;
    $limit=max(1,min(50,$limit));$sender??='admin_student_notice_send';
    $jobs=course_agenda_with_lock($db,static function()use($db,$limit):array {
        $stale=gmdate('Y-m-d\TH:i:s\Z',time()-1800);
        $db->prepare("UPDATE admin_student_notice_outbox SET status='pending' WHERE status='processing' AND attempts<3 AND updated_at<?")->execute([$stale]);
        $db->prepare("UPDATE admin_student_notice_outbox SET status='failed',error_code='processing_timeout' WHERE status='processing' AND attempts>=3 AND updated_at<?")->execute([$stale]);
        $sql=<<<'SQL'
SELECT n.*,a.email admin_email,u.name student_name,u.status student_status,
       c.title cohort_title,c.course_id,c.status cohort_status,
       cr.title course_title,cr.activity_id
FROM admin_student_notice_outbox n
JOIN admin_users a ON a.id=n.admin_id
JOIN student_users u ON u.id=n.student_id
JOIN course_cohorts c ON c.id=n.cohort_id
JOIN courses cr ON cr.id=c.course_id
WHERE n.status='pending' AND n.attempts<3 ORDER BY n.id LIMIT ?
SQL;
        $q=$db->prepare($sql);$q->bindValue(1,$limit,PDO::PARAM_INT);$q->execute();$rows=$q->fetchAll(PDO::FETCH_ASSOC);
        $mark=$db->prepare("UPDATE admin_student_notice_outbox SET status='processing',attempts=attempts+1,updated_at=? WHERE id=? AND status='pending'");
        foreach($rows as $row)$mark->execute([utc_now(),(int)$row['id']]);
        return $rows;
    });
    $preference=$db->prepare('SELECT enabled FROM admin_student_notice_preferences WHERE admin_id=? AND event_kind=?');
    $change=$db->prepare('UPDATE admin_student_notice_outbox SET status=?,provider_id=?,error_code=?,sent_at=?,updated_at=? WHERE id=?');
    foreach($jobs as $job){
        $target=admin_student_notice_target($db,$job);
        $preference->execute([(int)$job['admin_id'],(string)$job['event_kind']]);$enabled=$preference->fetchColumn();
        if(!$target||!filter_var($job['admin_email'],FILTER_VALIDATE_EMAIL)||
            (string)$job['student_status']!=='active'||$enabled!==false&&(int)$enabled===0){
            $change->execute(['skipped',null,'ineligible',null,utc_now(),(int)$job['id']]);$result['skipped']++;continue;
        }
        try{
            $receipt=$sender(admin_student_notice_message($job,$target));
            $change->execute(['sent',substr((string)($receipt['id']??''),0,240),null,utc_now(),utc_now(),(int)$job['id']]);
            $result['sent']++;
        }catch(Throwable $e){
            $status=(int)$job['attempts']+1>=3?'failed':'pending';
            $change->execute([$status,null,'transport_failure',null,utc_now(),(int)$job['id']]);
            $result['failed']++;
        }
    }
    return $result;
}
function admin_student_notice_recent(PDO $db,int $adminId): array {
    $q=$db->prepare('SELECT event_kind,status,created_at,sent_at FROM admin_student_notice_outbox WHERE admin_id=? ORDER BY id DESC LIMIT 10');
    $q->execute([$adminId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}
