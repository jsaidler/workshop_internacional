<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$read=static fn(string $path): string=>(string)file_get_contents($root.'/'.$path);
$assert=static function(bool $condition,string $message): void {if(!$condition){fwrite(STDERR,$message."\n");exit(1);}};

$shell=$read('app/admin_shell.php');
$assert(str_contains($shell,'class="admin-breadcrumb"'),'Course/cohort context must expose a real breadcrumb.');
$assert(str_contains($shell,'class="admin-context-back"'),'Course/cohort context must expose a visible hierarchical return action.');
$assert(str_contains($shell,'← Voltar às turmas'),'Cohort context must offer an explicit return to the cohort collection.');
$assert(str_contains($shell,"'students'=>['Alunos',admin_course_url"),'Selected course workspace must expose students without leaving course context.');

$registrations=$read('admin/registrations.php');
$assert(str_contains($registrations,'<?php if(!$selected):?>'),'Registration detail must be a focused state instead of rendering after the collection.');
$assert(str_contains($registrations,"?'Trocar turma':'Adicionar à turma'"),'Changing cohort must be an atomic assignment action.');
$assert(str_contains($registrations,'Retirar da turma'),'Removing a cohort assignment must be a separate explicit action.');
$assert(str_contains($registrations,'admin-responsive-list'),'Registration collection must have a semantic mobile presentation.');

$students=$read('admin/students.php');
$assert(str_contains($students,"admin_course_context($course,$activityId,'students'"),'Course student context must mark Students rather than Cohorts as the current area.');
$assert(str_contains($students,'admin-responsive-list'),'Student collections must have a semantic mobile presentation.');

$people=$read('admin/people.php');
$assert(str_contains($people,"'course'=>(int)$row['course_id'],'submission'=>(int)$row['source_submission_id']"),'Person to source-registration links must preserve course context.');
$assert(str_contains($people,"'course'=>(int)$row['course_id'],'submission'=>(int)$row['id']"),'Person registration history links must preserve course context.');
$assert(str_contains($people,"\$row['cohort_title']?:'Não definida'"),'Person history must show cohort identity instead of an internal numeric id.');

foreach(['admin/questions.php','admin/tests.php','admin/students.php'] as $path){
    $source=$read($path);
    $assert(!str_contains($source,'if($cohortId>0&&!$cohort){$cohortId=0;}'),$path.' must not silently broaden an invalid cohort context.');
}

$tests=$read('admin/tests.php');
$assert(str_contains($tests,'$reviewTransitions=match($currentReview)'),'Test review must offer transitions based on the current state.');

foreach(['admin/courses.php','admin/cohorts.php','admin/registrations.php','admin/students.php','admin/questions.php','admin/tests.php','admin/material.php'] as $path){
    $source=$read($path);
    $assert(str_contains($source,'admin-responsive-list'),$path.' must opt operational collections into the mobile list pattern.');
}

$adminCss=$read('assets/admin-system.css');
foreach(['autowidth','line)gap','border:0grid','nonecontent','20pxtext-align','relativeappearance'] as $broken){
    $assert(!str_contains($adminCss,$broken),'Malformed admin CSS token still present: '.$broken);
}
$assert(str_contains($adminCss,'.admin-nav-links a[aria-current=page]{border-color:var(--admin-ink);background:var(--admin-ink);color:#fff}'),'Current sidebar destination must remain legible with explicit foreground/background contrast.');

$collectionCss=$read('assets/admin-collection-ux.css');
$assert(str_contains($collectionCss,'.admin-responsive-list tbody'),'Responsive administrative collections must stack records on narrow screens.');
$assert(str_contains($collectionCss,'.admin-responsive-list td::before{content:attr(data-label)'),'Mobile records must retain field labels instead of showing contextless values.');

$teachingCss=$read('assets/admin-teaching.css');
$assert(str_contains($teachingCss,'.admin-breadcrumb')&&str_contains($teachingCss,'.admin-context-back'),'Teaching workspace CSS must own visible hierarchy and return navigation.');

echo "admin-navigation-integrity: ok\n";
