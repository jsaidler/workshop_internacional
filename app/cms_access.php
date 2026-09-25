<?php
declare(strict_types=1);

function cms_access_page_levels(): array {return ['public','authenticated','enrolled'];}
function cms_access_section_audiences(): array {return ['public','authenticated','enrolled','cohort'];}
function cms_access_page_level(array $page): string {
    $value=(string)($page['access_level']??'public');
    return in_array($value,cms_access_page_levels(),true)?$value:'public';
}
function cms_access_page_requires_login(array $page): bool {return cms_access_page_level($page)!=='public';}

function cms_access_parse_datetime(string $value): ?DateTimeImmutable {
    $value=trim($value);if($value==='')return null;
    try{return new DateTimeImmutable($value,new DateTimeZone(date_default_timezone_get()));}catch(Throwable){return null;}
}
function cms_access_window_open(string $from,string $until,?DateTimeImmutable $now=null): bool {
    $now=$now??new DateTimeImmutable('now');$start=cms_access_parse_datetime($from);$end=cms_access_parse_datetime($until);
    if($start&&$now<$start)return false;
    if($end&&$now>$end)return false;
    return true;
}
function cms_access_datetime_local_value(?string $value): string {
    $dt=$value?cms_access_parse_datetime($value):null;return $dt?$dt->format('Y-m-d\TH:i'):'';
}
function cms_access_enrollment(PDO $db,?array $user,array $page,string $cohortUuid=''): ?array {
    if(!$user)return null;
    return student_account_enrollment_for_activity($db,(int)$user['id'],(int)$page['activity_id'],$cohortUuid);
}
function cms_access_preview_enrollment(PDO $db,array $page,int $cohortId): ?array {
    if($cohortId<1)return null;
    $q=$db->prepare("SELECT c.id cohort_id,c.cohort_uuid,c.slug cohort_slug,c.title cohort_title,c.activity_id,c.status cohort_status FROM course_cohorts c WHERE c.id=? AND c.activity_id=? AND c.status!='archived' LIMIT 1");
    $q->execute([$cohortId,(int)$page['activity_id']]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}
function cms_access_lesson_released(PDO $db,array $page,array $enrollment,string $lessonKey,?DateTimeImmutable $now=null): bool {
    $lessonKey=trim($lessonKey);if($lessonKey==='')return true;
    $q=$db->prepare('SELECT r.released_at FROM course_lessons l LEFT JOIN cohort_lesson_releases r ON r.lesson_id=l.id AND r.cohort_id=? WHERE l.activity_id=? AND l.lesson_key=? LIMIT 1');
    $q->execute([(int)($enrollment['cohort_id']??0),(int)$page['activity_id'],$lessonKey]);$released=$q->fetchColumn();
    if(!is_string($released)||trim($released)==='')return false;
    $releaseAt=cms_access_parse_datetime($released);if(!$releaseAt)return false;
    return ($now??new DateTimeImmutable('now')) >= $releaseAt;
}
function cms_access_cohort_matches(array $enrollment,string $wanted): bool {
    $wanted=trim($wanted);if($wanted==='')return false;
    return hash_equals((string)($enrollment['cohort_uuid']??''),$wanted)||hash_equals((string)($enrollment['cohort_slug']??$enrollment['slug']??''),$wanted)||(ctype_digit($wanted)&&(int)$wanted===(int)($enrollment['cohort_id']??0));
}
function cms_access_section_allowed(PDO $db,array $page,DOMElement $section,?array $user,?array $enrollment,?DateTimeImmutable $now=null): bool {
    $audience=trim($section->getAttribute('data-cms-access'));if(!in_array($audience,cms_access_section_audiences(),true))$audience='public';
    $from=$section->getAttribute('data-cms-available-from');$until=$section->getAttribute('data-cms-available-until');
    if(!cms_access_window_open($from,$until,$now))return false;
    if($audience==='authenticated'&&!$user)return false;
    if($audience==='enrolled'&&!$enrollment)return false;
    if($audience==='cohort'&&(!$enrollment||!cms_access_cohort_matches($enrollment,$section->getAttribute('data-cms-cohort'))))return false;
    $lesson=trim($section->getAttribute('data-cms-lesson'));
    if($lesson!==''&&(!$enrollment||!cms_access_lesson_released($db,$page,$enrollment,$lesson,$now)))return false;
    return true;
}
function cms_access_filter_document(PDO $db,array $page,array $document,?array $user,?array $enrollment=null,?DateTimeImmutable $now=null): array {
    $html=(string)($document['html']??'');if($html===''||!str_contains($html,'data-cms-section'))return $document;
    $previous=libxml_use_internal_errors(true);$dom=new DOMDocument('1.0','UTF-8');
    $loaded=$dom->loadHTML('<?xml encoding="utf-8" ?><div id="cms-access-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
    if(!$loaded){libxml_clear_errors();libxml_use_internal_errors($previous);return $document;}
    $xpath=new DOMXPath($dom);
    foreach(iterator_to_array($xpath->query('//*[@data-cms-section]')?:[]) as $section){
        if(!$section instanceof DOMElement)continue;
        if(!cms_access_section_allowed($db,$page,$section,$user,$enrollment,$now))$section->parentNode?->removeChild($section);
    }
    $root=$dom->getElementById('cms-access-root');$out='';if($root)foreach(iterator_to_array($root->childNodes) as $child)$out.=$dom->saveHTML($child);
    libxml_clear_errors();libxml_use_internal_errors($previous);$document['html']=$out;return $document;
}
function cms_access_page_context(PDO $db,array $page,?array $user,string $cohortUuid=''): array {
    $level=cms_access_page_level($page);$enrollment=cms_access_enrollment($db,$user,$page,$cohortUuid);
    return ['level'=>$level,'user'=>$user,'enrollment'=>$enrollment,'allowed'=>$level==='public'||($level==='authenticated'&&$user!==null)||($level==='enrolled'&&$enrollment!==null)];
}
function cms_access_section_rule(DOMElement $section): array {
    $audience=trim($section->getAttribute('data-cms-access'));if(!in_array($audience,cms_access_section_audiences(),true))$audience='public';
    return ['audience'=>$audience,'cohort'=>$section->getAttribute('data-cms-cohort'),'lesson'=>$section->getAttribute('data-cms-lesson'),'available_from'=>$section->getAttribute('data-cms-available-from'),'available_until'=>$section->getAttribute('data-cms-available-until')];
}
function cms_access_editor_context(PDO $db,array $page): array {
    $activityId=(int)$page['activity_id'];
    $lessons=[];foreach(course_lessons($db,$activityId) as $row)$lessons[]=['id'=>(int)$row['id'],'key'=>(string)$row['lesson_key'],'title'=>(string)$row['title']];
    $cohorts=[];foreach(course_cohorts($db,$activityId) as $row)if(($row['status']??'')!=='archived')$cohorts[]=['id'=>(int)$row['id'],'uuid'=>(string)$row['cohort_uuid'],'slug'=>(string)$row['slug'],'title'=>(string)$row['title']];
    return ['pageLevels'=>cms_access_page_levels(),'sectionAudiences'=>cms_access_section_audiences(),'lessons'=>$lessons,'cohorts'=>$cohorts];
}
function course_lesson_release_state(?string $releasedAt,?DateTimeImmutable $now=null): string {
    $releasedAt=trim((string)$releasedAt);if($releasedAt==='')return 'blocked';$dt=cms_access_parse_datetime($releasedAt);if(!$dt)return 'blocked';return ($now??new DateTimeImmutable('now'))>=$dt?'released':'scheduled';
}
function course_set_lesson_release_schedule(PDO $db,int $cohortId,int $lessonId,?string $releaseAt): void {
    $cohort=course_cohort_by_id($db,$cohortId);if(!$cohort)throw new RuntimeException('Turma inválida.');
    $q=$db->prepare('SELECT 1 FROM course_lessons WHERE id=? AND activity_id=?');$q->execute([$lessonId,(int)$cohort['activity_id']]);if(!$q->fetchColumn())throw new RuntimeException('Aula inválida.');
    $stored=null;if($releaseAt!==null&&trim($releaseAt)!==''){$dt=cms_access_parse_datetime($releaseAt);if(!$dt)throw new RuntimeException('Data e hora de liberação inválidas.');$stored=$dt->format('c');}
    $now=utc_now();$db->prepare('INSERT INTO cohort_lesson_releases(cohort_id,lesson_id,released_at,created_at,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(cohort_id,lesson_id) DO UPDATE SET released_at=excluded.released_at,updated_at=excluded.updated_at')->execute([$cohortId,$lessonId,$stored,$now,$now]);
}
