<?php
declare(strict_types=1);

/**
 * Authenticate a global account. Enrollment is intentionally not part of this
 * decision; course authorization is evaluated later against course_enrollments.
 */
function student_account_global_login(PDO $db,string $email,string $password): bool {
    student_account_reconcile_confirmed_registrations($db);
    $email=student_normalize_email($email);
    if($email===''||student_login_blocked($db,$email))return false;

    $q=$db->prepare("SELECT * FROM student_users WHERE email=? AND status='active'");
    $q->execute([$email]);
    $user=$q->fetch()?:null;
    $legacyActivated=$user&&empty($user['activated_at'])&&(int)($user['must_change_password']??1)===0;

    if(!$user||(!$user['activated_at']&&!$legacyActivated)||!password_verify($password,(string)$user['password_hash'])){
        student_record_failed_login($db,$email);
        return false;
    }

    student_clear_login_attempts($db,$email);
    if($legacyActivated)$db->prepare('UPDATE student_users SET activated_at=?,privacy_notice_version=? WHERE id=?')->execute([utc_now(),STUDENT_PRIVACY_NOTICE_VERSION,(int)$user['id']]);
    student_account_start_session($user);
    $db->prepare('UPDATE student_users SET last_login_at=?,updated_at=? WHERE id=?')->execute([utc_now(),utc_now(),(int)$user['id']]);
    return true;
}
