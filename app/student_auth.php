<?php
declare(strict_types=1);

function student_uuid(): string { return bin2hex(random_bytes(16)); }
function student_normalize_email(string $email): string { return strtolower(trim($email)); }
function student_password_valid(string $password): bool { return mb_strlen($password)>=12; }
function student_activity_query(array $activity): string { return (int)($activity['is_root']??0)===1?'':'activity='.rawurlencode((string)$activity['slug']); }
function student_url(string $path,array $activity,array $extra=[]): string {
    $query=[];$aq=student_activity_query($activity);if($aq!=='')parse_str($aq,$query);foreach($extra as $k=>$v)$query[$k]=$v;
    return $path.($query?'?'.http_build_query($query,'','&',PHP_QUERY_RFC3986):'');
}
function current_student(PDO $db): ?array {
    $id=(int)($_SESSION['student']['id']??0);if($id<=0)return null;
    $q=$db->prepare("SELECT id,user_uuid,name,email,status,must_change_password,last_login_at FROM student_users WHERE id=? AND status='active'");$q->execute([$id]);$user=$q->fetch();
    if(!$user){unset($_SESSION['student']);return null;}return $user;
}
function student_has_activity(PDO $db,int $studentId,int $activityId): bool {
    $q=$db->prepare("SELECT 1 FROM student_enrollments WHERE student_id=? AND activity_id=? AND status='active'");$q->execute([$studentId,$activityId]);return (bool)$q->fetchColumn();
}
function student_rate_key(string $email): string {
    $config=app_config();$ip=(string)($_SERVER['REMOTE_ADDR']??'');
    return hash_hmac('sha256',student_normalize_email($email).'|'.$ip,(string)$config['ip_hash_secret']);
}
function student_login_blocked(PDO $db,string $email): bool {
    $key=student_rate_key($email);$q=$db->prepare('SELECT blocked_until FROM student_login_attempts WHERE key_hash=?');$q->execute([$key]);$until=$q->fetchColumn();
    return is_string($until)&&$until!==''&&strtotime($until)>time();
}
function student_record_failed_login(PDO $db,string $email): void {
    $key=student_rate_key($email);$now=utc_now();$q=$db->prepare('SELECT * FROM student_login_attempts WHERE key_hash=?');$q->execute([$key]);$row=$q->fetch()?:null;
    $window=15*60;$first=$row?strtotime((string)$row['first_attempt_at']):0;$attempts=$row?(int)$row['attempts']:0;
    if(!$row||$first===false||time()-$first>$window){$attempts=1;$firstIso=$now;}else{$attempts++;$firstIso=(string)$row['first_attempt_at'];}
    $blocked=$attempts>=5?gmdate('c',time()+$window):null;
    $db->prepare('INSERT INTO student_login_attempts(key_hash,attempts,first_attempt_at,last_attempt_at,blocked_until) VALUES(?,?,?,?,?) ON CONFLICT(key_hash) DO UPDATE SET attempts=excluded.attempts,first_attempt_at=excluded.first_attempt_at,last_attempt_at=excluded.last_attempt_at,blocked_until=excluded.blocked_until')->execute([$key,$attempts,$firstIso,$now,$blocked]);
}
function student_clear_login_attempts(PDO $db,string $email): void { $db->prepare('DELETE FROM student_login_attempts WHERE key_hash=?')->execute([student_rate_key($email)]); }
function student_login(PDO $db,array $activity,string $email,string $password): bool {
    $email=student_normalize_email($email);if($email===''||student_login_blocked($db,$email))return false;
    $q=$db->prepare("SELECT * FROM student_users WHERE email=? AND status='active'");$q->execute([$email]);$user=$q->fetch()?:null;
    if(!$user||!password_verify($password,(string)$user['password_hash'])||!student_has_activity($db,(int)$user['id'],(int)$activity['id'])){student_record_failed_login($db,$email);return false;}
    student_clear_login_attempts($db,$email);session_regenerate_id(true);$_SESSION['student']=['id'=>(int)$user['id'],'email'=>(string)$user['email'],'name'=>(string)$user['name']];
    $db->prepare('UPDATE student_users SET last_login_at=?,updated_at=? WHERE id=?')->execute([utc_now(),utc_now(),(int)$user['id']]);return true;
}
function student_logout(): void { unset($_SESSION['student']);session_regenerate_id(true); }
function student_private_headers(): void {
    header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');header('Pragma: no-cache');header('Expires: 0');header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
}
function student_safe_next(?string $value,string $fallback='/aluno/'): string {
    $value=trim((string)$value);if($value===''||!str_starts_with($value,'/')||str_starts_with($value,'//')||str_contains($value,"\r")||str_contains($value,"\n"))return $fallback;return $value;
}
function require_student_for_activity(PDO $db,array $activity,bool $allowPasswordChange=false): array {
    $student=current_student($db);if(!$student){$next=student_safe_next((string)($_SERVER['REQUEST_URI']??'/aluno/'));header('Location: '.student_url('/aluno/login.php',$activity,['next'=>$next]),true,303);exit;}
    if(!student_has_activity($db,(int)$student['id'],(int)$activity['id'])){http_response_code(403);student_private_headers();exit('Acesso não autorizado.');}
    if(!$allowPasswordChange&&(int)$student['must_change_password']===1){$next=student_safe_next((string)($_SERVER['REQUEST_URI']??'/aluno/'));header('Location: '.student_url('/aluno/senha.php',$activity,['next'=>$next]),true,303);exit;}
    student_private_headers();return $student;
}
function student_change_password(PDO $db,int $studentId,string $password): void {
    if(!student_password_valid($password))throw new RuntimeException('password_too_short');
    $hash=password_hash($password,PASSWORD_DEFAULT);if(!is_string($hash)||$hash==='')throw new RuntimeException('password_hash_failed');
    $db->prepare('UPDATE student_users SET password_hash=?,must_change_password=0,updated_at=? WHERE id=?')->execute([$hash,utc_now(),$studentId]);
}
function student_random_password(): string {
    $alphabet='ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';$out='';$max=strlen($alphabet)-1;for($i=0;$i<18;$i++)$out.=$alphabet[random_int(0,$max)];return $out;
}
function student_admin_create_or_enroll(PDO $db,int $activityId,string $name,string $email,?string $password=null): array {
    $name=trim($name);$email=student_normalize_email($email);if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('student_invalid');
    $q=$db->prepare('SELECT * FROM student_users WHERE email=?');$q->execute([$email]);$user=$q->fetch()?:null;$generated=null;$now=utc_now();
    if(!$user){$password=$password!==null&&$password!==''?$password:student_random_password();if(!student_password_valid($password))throw new RuntimeException('password_too_short');$hash=password_hash($password,PASSWORD_DEFAULT);$db->prepare('INSERT INTO student_users(user_uuid,name,email,password_hash,status,must_change_password,created_at,updated_at) VALUES(?,?,?,?,\'active\',1,?,?)')->execute([student_uuid(),$name,$email,$hash,$now,$now]);$id=(int)$db->lastInsertId();$generated=$password;$q=$db->prepare('SELECT * FROM student_users WHERE id=?');$q->execute([$id]);$user=$q->fetch();}
    else{$id=(int)$user['id'];$db->prepare("UPDATE student_users SET name=?,status='active',updated_at=? WHERE id=?")->execute([$name,$now,$id]);}
    $db->prepare("INSERT INTO student_enrollments(student_id,activity_id,status,created_at,updated_at) VALUES(?,?, 'active',?,?) ON CONFLICT(student_id,activity_id) DO UPDATE SET status='active',updated_at=excluded.updated_at")->execute([(int)$user['id'],$activityId,$now,$now]);
    return ['student_id'=>(int)$user['id'],'generated_password'=>$generated];
}
function student_admin_reset_password(PDO $db,int $studentId): string {
    $password=student_random_password();$hash=password_hash($password,PASSWORD_DEFAULT);$db->prepare('UPDATE student_users SET password_hash=?,must_change_password=1,status=\'active\',updated_at=? WHERE id=?')->execute([$hash,utc_now(),$studentId]);return $password;
}
function student_admin_set_enrollment(PDO $db,int $studentId,int $activityId,string $status): void {
    if(!in_array($status,['active','disabled'],true))throw new RuntimeException('invalid_status');$db->prepare('UPDATE student_enrollments SET status=?,updated_at=? WHERE student_id=? AND activity_id=?')->execute([$status,utc_now(),$studentId,$activityId]);
}
function student_admin_list(PDO $db,int $activityId): array {
    $q=$db->prepare('SELECT u.*,e.status AS enrollment_status,e.created_at AS enrolled_at FROM student_users u JOIN student_enrollments e ON e.student_id=u.id WHERE e.activity_id=? ORDER BY u.name COLLATE NOCASE,u.email COLLATE NOCASE');$q->execute([$activityId]);return $q->fetchAll();
}
function student_material_slug(string $value): string { return activity_slug($value); }
function student_materials(PDO $db,int $activityId,?string $locale=null,bool $activeOnly=true): array {
    $sql='SELECT * FROM student_materials WHERE activity_id=?';$args=[$activityId];if($locale!==null){$sql.=' AND locale=?';$args[]=$locale;}if($activeOnly)$sql.=" AND status='active'";$sql.=' ORDER BY title COLLATE NOCASE,id';$q=$db->prepare($sql);$q->execute($args);return $q->fetchAll();
}
function student_material_by_slug(PDO $db,int $activityId,string $locale,string $slug,bool $activeOnly=true): ?array {
    $sql='SELECT * FROM student_materials WHERE activity_id=? AND locale=? AND slug=?'.($activeOnly?" AND status='active'":'').' LIMIT 1';$q=$db->prepare($sql);$q->execute([$activityId,$locale,student_material_slug($slug)]);return $q->fetch()?:null;
}
function student_material_upsert(PDO $db,int $activityId,string $locale,string $title,string $slug,string $html,string $status='active'): array {
    $title=trim($title);$slug=student_material_slug($slug!==''?$slug:$title);if($title===''||trim($html)==='')throw new RuntimeException('material_invalid');if(!in_array($status,['active','draft'],true))$status='draft';$now=utc_now();
    $existing=student_material_by_slug($db,$activityId,$locale,$slug,false);if($existing){$db->prepare('UPDATE student_materials SET title=?,html_content=?,status=?,updated_at=? WHERE id=?')->execute([$title,$html,$status,$now,(int)$existing['id']]);$id=(int)$existing['id'];}else{$db->prepare('INSERT INTO student_materials(material_uuid,activity_id,locale,slug,title,status,html_content,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)')->execute([student_uuid(),$activityId,$locale,$slug,$title,$status,$html,$now,$now]);$id=(int)$db->lastInsertId();}
    $q=$db->prepare('SELECT * FROM student_materials WHERE id=?');$q->execute([$id]);return $q->fetch();
}
function student_material_set_status(PDO $db,int $id,int $activityId,string $status): void { if(!in_array($status,['active','draft'],true))throw new RuntimeException('invalid_status');$db->prepare('UPDATE student_materials SET status=?,updated_at=? WHERE id=? AND activity_id=?')->execute([$status,utc_now(),$id,$activityId]); }
function student_material_render(string $html,array $student,array $activity): string {
    $label=trim((string)$student['name']).' · '.(string)$student['email'];$bar='<div class="student-access-bar"><a href="'.h(student_url('/aluno/',$activity)).'">Área do aluno</a><span>'.h($label).'</span><form method="post" action="'.h(student_url('/aluno/logout.php',$activity)).'"><input type="hidden" name="_csrf" value="'.h(csrf_token('student-logout')).'"><button type="submit">Sair</button></form></div>';
    $style='<style id="student-protection-ui">.student-access-bar{position:sticky;top:0;z-index:2147483647;display:flex;align-items:center;gap:16px;padding:10px 16px;background:#0b0c0d;color:#f2f2ef;font:12px/1.3 Arial,sans-serif}.student-access-bar a,.student-access-bar button{color:inherit}.student-access-bar span{margin-left:auto;opacity:.72}.student-access-bar form{margin:0}.student-access-bar button{border:0;background:transparent;text-decoration:underline;cursor:pointer;padding:0}.student-protection-mark{position:fixed;right:10px;bottom:8px;z-index:2147483646;font:10px/1.2 Arial,sans-serif;color:rgba(20,20,20,.35);pointer-events:none}@media print{.student-access-bar{position:fixed}.student-protection-mark{color:rgba(0,0,0,.28)}}</style>';
    $mark='<div class="student-protection-mark">Acesso individual: '.h($label).'</div>';
    if(preg_match('~</head>~i',$html))$html=preg_replace('~</head>~i',$style.'</head>',$html,1)??$html;else$html=$style.$html;
    if(preg_match('~<body[^>]*>~i',$html))$html=preg_replace('~(<body[^>]*>)~i','$1'.$bar.$mark,$html,1)??$html;else$html=$bar.$mark.$html;
    return $html;
}
