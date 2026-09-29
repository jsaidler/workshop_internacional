<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$read=static fn(string $path): string=>(string)file_get_contents($root.'/'.$path);
$assert=static function(bool $condition,string $message): void {if(!$condition){fwrite(STDERR,$message."\n");exit(1);}};

$css=$read('assets/admin-operations.css');
$assert($css!=='','Shared operational stylesheet must exist.');
$assert(!str_contains($css,'!important'),'Operational stylesheet must not use !important.');
$assert(str_contains($css,'.admin-active-filters'),'Operational stylesheet must own active filter presentation.');
$assert(str_contains($css,'.admin-operation-detail'),'Operational stylesheet must own detail presentation.');

$shell=$read('app/admin_shell.php');
foreach(['function admin_badge(','function admin_filter_chip(','function admin_status_label(','function admin_status_tone('] as $needle){
    $assert(str_contains($shell,$needle),'Missing shared admin presentation helper: '.$needle);
}

$pages=['registrations','cohorts','students','people'];
foreach($pages as $page){
    $content=$read('admin/'.$page.'.php');
    $assert(str_contains($content,"'/assets/admin-operations.css'"),'Operational page must load shared stylesheet through admin_shell_start: '.$page);
    $assert(!preg_match('/<link[^>]+admin-operations\.css/i',$content),'Operational stylesheet must not be injected from page body: '.$page);
    $assert(str_contains($content,'admin-active-filters'),'Operational page must expose reversible active filters: '.$page);
    $assert(str_contains($content,'tabindex="0"'),'Scrollable operational tables must be keyboard focusable: '.$page);
}

$registrations=$read('admin/registrations.php');
$assert(str_contains($registrations,"admin_status_label('payment'"),'Registration payment states must use shared human-readable status labels.');
$assert(str_contains($registrations,'admin-operation-danger'),'Registration destructive action must live in a separated danger zone.');
$assert(str_contains($registrations,"'/assets/admin-registration.css'"),'Registration-specific stylesheet must be loaded through the shell.');
$assert(!preg_match('/<link[^>]+admin-registration\.css/i',$registrations),'Registration stylesheet must not be injected after page content.');

$cohorts=$read('admin/cohorts.php');
$assert(str_contains($cohorts,'>Nova turma</summary>'),'Cohort creation must be directly discoverable from the collection.');
$assert(str_contains($cohorts,'name="course_id" required'),'Creating a cohort must allow explicit course selection without pre-filtering the collection.');

$students=$read('admin/students.php');
$assert(str_contains($students,"'submission'=>(int)\$row['source_submission_id']"),'Student origin must link back to the source registration.');

$people=$read('admin/people.php');
$assert(str_contains($people,'id="person-detail"'),'Person detail must have a stable navigation target.');
$assert(str_contains($people,'#person-detail'),'Opening a person must navigate directly to the detail region.');
$detailPos=strpos($people,'id="person-detail"');
$listPos=strpos($people,'aria-label="Lista de pessoas"');
$assert($detailPos!==false&&$listPos!==false&&$detailPos<$listPos,'Selected person detail must precede the collection list in the reading order.');

echo "admin-operational-ux: ok\n";
