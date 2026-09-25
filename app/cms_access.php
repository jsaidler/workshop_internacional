<?php
declare(strict_types=1);

function cms_access_levels(): array {return ['public','authenticated','activity','cohort'];}
function cms_availability_modes(): array {return ['immediate','scheduled','lesson'];}

function cms_access_parse_local_datetime(string $value): ?int {
    $value=trim($value);if($value==='')return null;
    try{$tz=new DateTimeZone((string)(app_config()['timezone']??'UTC'));$dt=new DateTimeImmutable($value,$tz);return $dt->getTimestamp();}catch(Throwable){return null;}
}
function cms_access_local_to_utc(string $value): ?string {$ts=cms_access_parse_local_datetime($value);return $ts===null?null:gmdate('c',$ts);}
function cms_access_local_input_value(?string $utc): string {if(!$utc)return '';try{$dt=new DateTimeImmutable($utc);$tz=new DateTimeZone((string)(app_config()['timezone']??'UTC'));return $dt->setTimezone($tz)->format('Y-m-d\TH:i');}catch(Throwable){return '';}}
function cms_access_section_rule(DOMElement $section): array {
    $access=$section->getAttribute('data-cms-access');if(!in_array($access,cms_access_levels(),true))$access='public';
    $availability=$section->getAttribute('data-cms-availability');if(!in_array($availability,cms_availability_modes(),true))$availability='immediate';
    return [
        'access'=>$access,
        'cohort_id'=>(int)$section->getAttribute('data-cms-cohort-id'),
        'availability'=>$availability,
        'lesson_id'=>(int)$section->getAttribute('data-cms-lesson-id'),
        'visible_from'=>trim($section->getAttribute('data-cms-visible-from')),
        'visible_until'=>trim($section->getAttribute('data-cms-visible-until')),
    ];
}
function cms_access_active_enrollment(PDO $db,int $studentId,int $activityId,?int $cohortId=null): ?array {
    $sql="SELECT e.*,c.activity_id,c.title cohort_title,c.cohort_uuid FROM course_enrollments e JOIN course_cohorts c ON c.id=e.cohort_id WHERE e.student_id=? AND c.activity_id=? AND e.status='active' AND c.status!='archived'";$args=[$studentId,$activityId];
    if($cohortId!==null&&$cohortId>0){$sql.=' AND c.id=?';$args[]=$cohortId;}
    $sql.=' ORDER BY e.confirmed_at DESC,e.id DESC LIMIT 1';$q=$db->prepare($sql);$q->execute($args);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}
function cms_access_lesson_released(PDO $db,int $cohortId,int $lessonId,?int $now=null): bool {
    if($cohortId<1||$lessonId<1)return false;$q=$db->prepare('SELECT released_at FROM cohort_lesson_releases WHERE cohort_id=? AND lesson_id=?');$q->execute([$cohortId,$lessonId]);$value=$q->fetchColumn();if(!is_string($value)||trim($value)==='')return false;
    $ts=strtotime($value);return $ts!==false&&$ts<=($now??time());
}
function cms_access_lesson_release_state(?string $releasedAt,?int $now=null): string {if(!$releasedAt)return 'blocked';$ts=strtotime($releasedAt);if($ts===false)return 'blocked';return $ts<=($now??time())?'released':'scheduled';}
function cms_access_set_lesson_release(PDO $db,int $activityId,int $cohortId,int $lessonId,string $mode,string $scheduled=''): ?string {
    $cohort=course_cohort_by_id($db,$cohortId);if(!$cohort||(int)$cohort['activity_id']!==$activityId)throw new RuntimeException('Turma inválida.');
    $q=$db->prepare('SELECT 1 FROM course_lessons WHERE id=? AND activity_id=?');$q->execute([$lessonId,$activityId]);if(!$q->fetchColumn())throw new RuntimeException('Aula inválida.');
    $releasedAt=match($mode){'release'=>utc_now(),'block'=>null,'schedule'=>cms_access_local_to_utc($scheduled),default=>throw new RuntimeException('Ação de liberação inválida.')};
    if($mode==='schedule'&&$releasedAt===null)throw new RuntimeException('Informe uma data e hora válidas.');$now=utc_now();
    $db->prepare('INSERT INTO cohort_lesson_releases(cohort_id,lesson_id,released_at,created_at,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(cohort_id,lesson_id) DO UPDATE SET released_at=excluded.released_at,updated_at=excluded.updated_at')->execute([$cohortId,$lessonId,$releasedAt,$now,$now]);return $releasedAt;
}
function cms_access_section_allowed(PDO $db,array $activity,array $rule,?array $user=null,?int $now=null): bool {
    $now??=time();$availability=(string)($rule['availability']??'immediate');
    if($availability==='scheduled'){
        $from=cms_access_parse_local_datetime((string)($rule['visible_from']??''));$until=cms_access_parse_local_datetime((string)($rule['visible_until']??''));
        if($from!==null&&$now<$from)return false;if($until!==null&&$now>$until)return false;
    }
    $access=(string)($rule['access']??'public');
    if($access==='public')$audience=true;
    elseif(!$user)$audience=false;
    elseif($access==='authenticated')$audience=true;
    elseif($access==='activity')$audience=(bool)cms_access_active_enrollment($db,(int)$user['id'],(int)$activity['id']);
    elseif($access==='cohort')$audience=(int)($rule['cohort_id']??0)>0&&(bool)cms_access_active_enrollment($db,(int)$user['id'],(int)$activity['id'],(int)$rule['cohort_id']);
    else $audience=false;
    if(!$audience)return false;
    if($availability==='lesson'){
        if(!$user)return false;$enrollment=cms_access_active_enrollment($db,(int)$user['id'],(int)$activity['id']);if(!$enrollment)return false;
        return cms_access_lesson_released($db,(int)$enrollment['cohort_id'],(int)($rule['lesson_id']??0),$now);
    }
    return true;
}
function cms_access_filter_html(PDO $db,array $activity,string $html,?array $user=null,bool $editor=false,?int $now=null): string {
    if($editor||!str_contains($html,'data-cms-section'))return $html;
    $previous=libxml_use_internal_errors(true);$dom=new DOMDocument('1.0','UTF-8');$dom->loadHTML('<?xml encoding="utf-8" ?><div id="cms-access-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);$xpath=new DOMXPath($dom);
    foreach(iterator_to_array($xpath->query('//*[@data-cms-section]')?:[]) as $node){if(!$node instanceof DOMElement)continue;$rule=cms_access_section_rule($node);if(cms_access_section_allowed($db,$activity,$rule,$user,$now))continue;$node->parentNode?->removeChild($node);}
    $root=$dom->getElementById('cms-access-root');$out='';if($root)foreach(iterator_to_array($root->childNodes) as $child)$out.=$dom->saveHTML($child);libxml_clear_errors();libxml_use_internal_errors($previous);return $out;
}
function cms_access_page_allowed(PDO $db,array $activity,array $page,?array $user=null): bool {
    $access=(string)($page['access_level']??'public');if($access==='public')return true;if(!$user)return false;if($access==='authenticated')return true;
    if(in_array($access,['activity','enrolled'],true))return (bool)cms_access_active_enrollment($db,(int)$user['id'],(int)$activity['id']);return false;
}
function cms_access_page_label(string $access): string {return match($access){'authenticated'=>'Usuários autenticados','activity','enrolled'=>'Participantes do curso',default=>'Público'};}
