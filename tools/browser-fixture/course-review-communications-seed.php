<?php
declare(strict_types=1);
/** Isolated real-application fixture, never installed in production. */
if(PHP_SAPI!=='cli')exit(1);
$root=realpath((string)($argv[1]??''));$mode=(string)($argv[2]??'seed');
if(!$root||!file_exists($root.'/storage/installed.lock')||!file_exists($root.'/config/local.php'))throw new RuntimeException('Invalid isolated fixture.');
require $root.'/app/bootstrap.php';
$db=database();
if($mode==='email-media'){
    $uuid=bin2hex(random_bytes(16));$version=bin2hex(random_bytes(16));
    $relative=$uuid.'/'.$version.'/original/file.png';
    $absolute=$root.'/uploads/media/'.$relative;
    if(!is_dir(dirname($absolute)))mkdir(dirname($absolute),0755,true);
    $bytes=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+rJ3sAAAAASUVORK5CYII=',true);
    if(!is_string($bytes)||file_put_contents($absolute,$bytes)===false)throw new RuntimeException('Media seed failed');
    $now=gmdate('c');$hash=hash('sha256',$bytes);
    $db->prepare("INSERT INTO media_assets(asset_uuid,kind,title,default_alt,original_name,mime_type,byte_size,width,height,checksum,processing_status,created_at,updated_at,visibility)
      VALUES(?,'image','Fotografia do laboratório','Bancada de fotografia','seed.png','image/png',1,1,1,?,'ready',?,?,'public')")
      ->execute([$uuid,$hash,$now,$now]);
    $id=(int)$db->lastInsertId();
    $db->prepare("INSERT INTO media_versions(asset_id,version_uuid,original_path,mime_type,byte_size,width,height,checksum,processing_status,created_at)
       VALUES(?,?,?,'image/png',?,1,1,?,'ready',?)")
       ->execute([$id,$version,$relative,strlen($bytes),$hash,$now]);
    $db->prepare('UPDATE media_assets SET active_version_id=?,byte_size=? WHERE id=?')->execute([(int)$db->lastInsertId(),strlen($bytes),$id]);
    echo json_encode(['id'=>$id,'src'=>media_url($relative)],JSON_THROW_ON_ERROR).PHP_EOL;exit;
}
if($mode==='token'){
    $q=$db->prepare('SELECT id,email FROM student_users WHERE email=?');
    $q->execute(['student-qa@example.test']);$person=$q->fetch();
    if(!$person)throw new RuntimeException('Missing test identity');
    echo communication_unsubscribe_token((int)$person['id'],(string)$person['email']).PHP_EOL;exit;
}
if($mode==='student-action'){
    $studentId=(int)$db->query("SELECT id FROM student_users WHERE email='student-qa@example.test'")->fetchColumn();
    $cohortId=(int)$db->query("SELECT id FROM course_cohorts WHERE cohort_uuid='review-qa-0'")->fetchColumn();
    if(!$studentId||!$cohortId)throw new RuntimeException('Student action QA identity missing');
    $question=student_question_create($db,$studentId,$cohortId,[
       'title'=>'Dúvida criada pelo aluno QA','body'=>'Dados privados do aluno não são copiados no aviso',
       'visibility'=>'private'
    ]);
    echo json_encode(['id'=>(int)$question['id']],JSON_THROW_ON_ERROR).PHP_EOL;exit;
}
if($mode==='brevo-credential'){
    // Never uses a real API key: the validator is an isolated QA stub.
    $key='xkeysib-qa-only-'.str_repeat('q',48);
    communication_brevo_save($db,$key,static function(string $value):void {
        if(!str_starts_with($value,'xkeysib-qa-only-'))throw new RuntimeException('Test key missing');
    });
    echo json_encode(['saved'=>communication_brevo_public_status($db)['managed_in_cms']],JSON_THROW_ON_ERROR).PHP_EOL;exit;
}
if($mode==='snapshot'){
    echo json_encode([
      'reviews'=>$db->query('SELECT id,course_id,cohort_id,title,meeting_status FROM course_review_sessions ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
      'campaigns'=>$db->query('SELECT id,category,status FROM communication_campaigns ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
      'deliveries'=>$db->query('SELECT campaign_id,student_id,status FROM communication_deliveries ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
      'preferences'=>$db->query('SELECT * FROM communication_preferences ORDER BY student_id')->fetchAll(PDO::FETCH_ASSOC),
      'staff_notices'=>$db->query('SELECT admin_id,event_kind,status,event_key FROM admin_student_notice_outbox ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)
    ],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE).PHP_EOL;exit;
}
if($mode!=='seed')throw new RuntimeException('Invalid mode');
$activityId=(int)$db->query('SELECT id FROM activities WHERE is_root=1 LIMIT 1')->fetchColumn();
if($activityId<1)throw new RuntimeException('Missing root activity');
$now=utc_now();$pw='fixture-password-123!';
$db->prepare('INSERT INTO admin_users(email,password_hash,created_at,updated_at) VALUES(?,?,?,?)')
   ->execute(['admin-qa@example.test',password_hash($pw,PASSWORD_DEFAULT),$now,$now]);
$db->prepare("INSERT INTO courses(course_uuid,activity_id,title,slug,status,created_at,updated_at) VALUES(?,?,?,?,'active',?,?)")
   ->execute([student_uuid(),$activityId,'Curso QA','course-review-qa',$now,$now]);
$courseId=(int)$db->lastInsertId();$cohorts=[];
foreach(['Turma atual','Turma anterior'] as $i=>$label){
    $uuid='review-qa-'.$i;
    $db->prepare("INSERT INTO course_cohorts(cohort_uuid,activity_id,course_id,title,slug,status,is_registration_default,created_at,updated_at) VALUES(?,?,?,?,?,'active',0,?,?)")
      ->execute([$uuid,$activityId,$courseId,$label,$uuid,$now,$now]);
    $cohorts[]=(int)$db->lastInsertId();
}
foreach(['student-qa@example.test','peer-qa@example.test'] as $i=>$mail){
    $db->prepare("INSERT INTO student_users(user_uuid,name,email,password_hash,status,must_change_password,activated_at,created_at,updated_at) VALUES(?,?,?,?,'active',0,?,?,?)")
      ->execute([student_uuid(),$i===0?'Aluna QA':'Colega QA',$mail,password_hash($pw,PASSWORD_DEFAULT),$now,$now,$now]);
    $studentId=(int)$db->lastInsertId();
    $db->prepare("INSERT INTO student_enrollments(student_id,activity_id,status,created_at,updated_at) VALUES(?,?,'active',?,?)")
      ->execute([$studentId,$activityId,$now,$now]);
    $db->prepare("INSERT INTO course_enrollments(enrollment_uuid,student_id,cohort_id,status,confirmed_at,created_at,updated_at) VALUES(?,?,?,'active',?,?,?)")
      ->execute([student_uuid(),$studentId,$cohorts[$i],$now,$now,$now]);
    if($i===0)communication_set_opt_in($db,$studentId,true);
}
echo json_encode(['activityId'=>$activityId,'courseId'=>$courseId,'cohortIds'=>$cohorts],JSON_THROW_ON_ERROR).PHP_EOL;
