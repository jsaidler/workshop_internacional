<?php
declare(strict_types=1);

const STUDENT_LOGIN_MAX_ATTEMPTS=5;
const STUDENT_LOGIN_WINDOW_SECONDS=900;
const STUDENT_PASSWORD_MIN_LENGTH=12;

function student_uuid(): string { return bin2hex(random_bytes(16)); }
function student_username_normalize(string $value): string { return strtolower(trim($value)); }
function student_material_slug(string $value): string { return activity_slug($value); }
function student_password_is_valid(string $password): bool { return mb_strlen($password,'UTF-8')>=STUDENT_PASSWORD_MIN_LENGTH; }
function student_security_headers(): void {
    security_headers();
    header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
}
function student_material_headers(): void {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'none'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; base-uri 'none'; form-action 'none'; frame-ancestors 'self'; object-src 'none'; script-src 'none'; connect-src 'none'; media-src 'none'");
    header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
}
function student_safe_return_url(?string $value): string {
    $value=trim((string)$value);
    if($value===''||!str_starts_with($value,'/aluno/'))return '/aluno/';
    if(str_contains($value,"\r")||str_contains($value,"\n")||str_starts_with($value,'//'))return '/aluno/';
    return $value;
}
function current_student(): ?array {
    $session=$_SESSION['student']??null;
    if(!is_array($session)||empty($session['id'])||empty($session['session_version']))return null;
    static $cached=null,$cachedId=null;
    $id=(int)$session['id'];
    if($cachedId===$id)return $cached;
    $q=database()->prepare("SELECT id,user_uuid,username,display_name,email,status,must_change_password,session_version FROM student_users WHERE id=? LIMIT 1");
    $q->execute([$id]);$user=$q->fetch()?:null;
    if(!$user||$user['status']!=='active'||(int)$user['session_version']!==(int)$session['session_version']){
        unset($_SESSION['student']);$cachedId=$id;return $cached=null;
    }
    $cachedId=$id;return $cached=$user;
}
function require_student(bool $allowPasswordChange=false): array {
    $student=current_student();
    if(!$student){$return=student_safe_return_url((string)($_SERVER['REQUEST_URI']??'/aluno/'));header('Location: /aluno/login.php?return='.rawurlencode($return),true,303);exit;}
    if(!$allowPasswordChange&&(int)$student['must_change_password']===1){header('Location: /aluno/senha.php',true,303);exit;}
    return $student;
}
function student_login_key(string $identifier): string {
    $config=app_config();$ip=(string)($_SERVER['REMOTE_ADDR']??'');
    return hash_hmac('sha256',$ip.'|'.student_username_normalize($identifier),(string)$config['ip_hash_secret']);
}
function student_login_limited(PDO $db,string $key): bool {
    $q=$db->prepare('SELECT window_started_at,attempt_count FROM student_login_attempts WHERE key_hash=?');$q->execute([$key]);$row=$q->fetch();if(!$row)return false;
    $started=strtotime((string)$row['window_started_at'])?:0;
    if(time()-$started>=STUDENT_LOGIN_WINDOW_SECONDS){$db->prepare('DELETE FROM student_login_attempts WHERE key_hash=?')->execute([$key]);return false;}
    return (int)$row['attempt_count']>=STUDENT_LOGIN_MAX_ATTEMPTS;
}
function student_login_failure(PDO $db,string $key): void {
    $q=$db->prepare('SELECT window_started_at,attempt_count FROM student_login_attempts WHERE key_hash=?');$q->execute([$key]);$row=$q->fetch();$now=utc_now();
    if(!$row||(time()-(strtotime((string)$row['window_started_at'])?:0))>=STUDENT_LOGIN_WINDOW_SECONDS){$db->prepare('INSERT INTO student_login_attempts(key_hash,window_started_at,attempt_count,updated_at) VALUES(?,?,1,?) ON CONFLICT(key_hash) DO UPDATE SET window_started_at=excluded.window_started_at,attempt_count=1,updated_at=excluded.updated_at')->execute([$key,$now,$now]);return;}
    $db->prepare('UPDATE student_login_attempts SET attempt_count=attempt_count+1,updated_at=? WHERE key_hash=?')->execute([$now,$key]);
}
function student_login(string $identifier,string $password): bool {
    $db=database();$identifier=student_username_normalize($identifier);$key=student_login_key($identifier);
    if(student_login_limited($db,$key))return false;
    $q=$db->prepare("SELECT * FROM student_users WHERE status='active' AND (lower(username)=? OR lower(email)=?) LIMIT 1");$q->execute([$identifier,$identifier]);$user=$q->fetch();
    if(!$user||!password_verify($password,(string)$user['password_hash'])){student_login_failure($db,$key);return false;}
    $db->prepare('DELETE FROM student_login_attempts WHERE key_hash=?')->execute([$key]);session_regenerate_id(true);
    $_SESSION['student']=['id'=>(int)$user['id'],'session_version'=>(int)$user['session_version']];
    $db->prepare('UPDATE student_users SET last_login_at=?,updated_at=? WHERE id=?')->execute([utc_now(),utc_now(),(int)$user['id']]);
    return true;
}
function student_logout(): void { unset($_SESSION['student']);session_regenerate_id(true); }
function student_set_password(PDO $db,int $userId,string $password,bool $mustChange): void {
    if(!student_password_is_valid($password))throw new RuntimeException('password_too_short');
    $hash=password_hash($password,PASSWORD_DEFAULT);if(!is_string($hash))throw new RuntimeException('password_hash_failed');
    $db->prepare('UPDATE student_users SET password_hash=?,must_change_password=?,session_version=session_version+1,updated_at=? WHERE id=?')->execute([$hash,$mustChange?1:0,utc_now(),$userId]);
}
function student_change_own_password(PDO $db,array $student,string $password): void {
    student_set_password($db,(int)$student['id'],$password,false);
    $q=$db->prepare('SELECT session_version FROM student_users WHERE id=?');$q->execute([(int)$student['id']]);$version=(int)$q->fetchColumn();
    $_SESSION['student']=['id'=>(int)$student['id'],'session_version'=>$version];session_regenerate_id(true);
}
function student_create(PDO $db,string $username,string $displayName,string $email,string $temporaryPassword,array $activityIds): array {
    $username=student_username_normalize($username);$displayName=trim($displayName);$email=strtolower(trim($email));
    if($username===''||!preg_match('/^[a-z0-9._@+\-]{3,120}$/',$username))throw new RuntimeException('invalid_username');
    if($displayName==='')throw new RuntimeException('invalid_display_name');if(!student_password_is_valid($temporaryPassword))throw new RuntimeException('password_too_short');
    if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('invalid_email');
    $hash=password_hash($temporaryPassword,PASSWORD_DEFAULT);$now=utc_now();$db->beginTransaction();
    try{$q=$db->prepare("INSERT INTO student_users(user_uuid,username,display_name,email,password_hash,status,must_change_password,session_version,created_at,updated_at) VALUES(?,?,?,?,?,'active',1,1,?,?)");$q->execute([student_uuid(),$username,$displayName,$email,$hash,$now,$now]);$id=(int)$db->lastInsertId();student_replace_enrollments($db,$id,$activityIds);$db->commit();return student_by_id($db,$id)??throw new RuntimeException('student_create_failed');}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function student_by_id(PDO $db,int $id): ?array {$q=$db->prepare('SELECT * FROM student_users WHERE id=?');$q->execute([$id]);return $q->fetch()?:null;}
function student_replace_enrollments(PDO $db,int $userId,array $activityIds): void {
    $ids=array_values(array_unique(array_filter(array_map('intval',$activityIds),fn(int $id)=>$id>0)));$db->prepare('DELETE FROM student_enrollments WHERE user_id=?')->execute([$userId]);
    if(!$ids)return;$check=$db->prepare('SELECT 1 FROM activities WHERE id=?');$insert=$db->prepare('INSERT INTO student_enrollments(user_id,activity_id,created_at) VALUES(?,?,?)');
    foreach($ids as $activityId){$check->execute([$activityId]);if($check->fetchColumn())$insert->execute([$userId,$activityId,utc_now()]);}
}
function student_enrollment_ids(PDO $db,int $userId): array {$q=$db->prepare('SELECT activity_id FROM student_enrollments WHERE user_id=? ORDER BY activity_id');$q->execute([$userId]);return array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));}
function student_material_validate_html(string $html): string {
    $html=trim($html);if($html===''||stripos($html,'<html')===false||stripos($html,'<body')===false)throw new RuntimeException('invalid_html');
    if(strlen($html)>15*1024*1024)throw new RuntimeException('html_too_large');
    if(preg_match('~<\s*(?:script|iframe|object|embed)\b~i',$html)||preg_match('~javascript\s*:~i',$html))throw new RuntimeException('unsafe_html');
    return $html;
}
function student_material_upsert(PDO $db,int $activityId,string $title,string $slug,string $html): array {
    $title=trim($title);if($title==='')throw new RuntimeException('invalid_title');$slug=student_material_slug($slug?:$title);$html=student_material_validate_html($html);$now=utc_now();
    $q=$db->prepare('SELECT id FROM student_materials WHERE activity_id=? AND slug=? LIMIT 1');$q->execute([$activityId,$slug]);$id=(int)($q->fetchColumn()?:0);
    if($id){$db->prepare("UPDATE student_materials SET title=?,html_content=?,status='active',updated_at=?,published_at=? WHERE id=?")->execute([$title,$html,$now,$now,$id]);}
    else{$db->prepare("INSERT INTO student_materials(material_uuid,activity_id,slug,title,html_content,status,created_at,updated_at,published_at) VALUES(?,?,?,?,?,'active',?,?,?)")->execute([student_uuid(),$activityId,$slug,$title,$html,$now,$now,$now]);$id=(int)$db->lastInsertId();}
    $q=$db->prepare('SELECT * FROM student_materials WHERE id=?');$q->execute([$id]);return $q->fetch()?:throw new RuntimeException('material_save_failed');
}
function student_materials_for_user(PDO $db,int $userId): array {
    $q=$db->prepare("SELECT m.material_uuid,m.slug,m.title,m.published_at,a.admin_name,a.public_title FROM student_materials m JOIN student_enrollments e ON e.activity_id=m.activity_id AND e.user_id=? JOIN activities a ON a.id=m.activity_id WHERE m.status='active' AND a.status='active' ORDER BY a.is_root DESC,a.admin_name,m.title");$q->execute([$userId]);return $q->fetchAll();
}
function student_material_for_view(PDO $db,string $uuid,?array $student,?array $admin): ?array {
    if($admin){$q=$db->prepare("SELECT m.*,a.admin_name,a.public_title FROM student_materials m JOIN activities a ON a.id=m.activity_id WHERE m.material_uuid=? AND m.status='active'");$q->execute([$uuid]);return $q->fetch()?:null;}
    if(!$student)return null;$q=$db->prepare("SELECT m.*,a.admin_name,a.public_title FROM student_materials m JOIN student_enrollments e ON e.activity_id=m.activity_id AND e.user_id=? JOIN activities a ON a.id=m.activity_id WHERE m.material_uuid=? AND m.status='active' AND a.status='active' LIMIT 1");$q->execute([(int)$student['id'],$uuid]);return $q->fetch()?:null;
}
