<?php
declare(strict_types=1);

function fail_canonical_authority(string $message): never {fwrite(STDERR,"canonical-cms-student-authority: $message\n");exit(1);}
function must_canonical_authority(bool $condition,string $message): void {if(!$condition)fail_canonical_authority($message);}

$root=dirname(__DIR__);
$enrollments=(string)file_get_contents($root.'/app/student_enrollments.php');
$front=(string)file_get_contents($root.'/index.php');
$privacy=(string)file_get_contents($root.'/app/media_privacy.php');
$material=(string)file_get_contents($root.'/app/student_material.php');
$studentMedia=(string)file_get_contents($root.'/aluno/media.php');
$studentAdmin=(string)file_get_contents($root.'/admin/student-area.php');
$contract=(string)file_get_contents($root.'/docs/CMS_STUDENT_ACCESS_CONTRACT_2026-09-26.md');

must_canonical_authority(str_contains($enrollments,"access_level='activity'"),'student dashboard page lookup does not use canonical activity access');
must_canonical_authority(!str_contains($enrollments,"access_level IN ('activity','enrolled')"),'active enrollment service still uses legacy enrolled as authority');
must_canonical_authority(!str_contains($enrollments,'course_page_sections'),'active enrollment service still reads historical section map');

must_canonical_authority(str_contains($front,'cms_access_page_allowed')&&str_contains($front,'cms_access_filter_html'),'public controller does not use canonical CMS page/section authorization');
must_canonical_authority(str_contains($front,'student_page_sign_private_media_for_access'),'public controller does not sign private editorial media after CMS authorization');
must_canonical_authority(!str_contains($front,'student_page_filter_document'),'public controller still uses historical course_page_sections filtering');

must_canonical_authority(str_contains($privacy,'cms_access_page_allowed'),'private library media does not revalidate canonical page access');
must_canonical_authority(!str_contains($privacy,'student_page_is_protected'),'private library media still depends on legacy enrolled-only helper');
must_canonical_authority(str_contains($material,'student_page_sign_private_media_for_access'),'canonical private media signer missing');
must_canonical_authority(str_contains($studentMedia,'$cohortId<0'),'authenticated private media still requires a course cohort unconditionally');

must_canonical_authority(str_contains($studentAdmin,"if(\$view==='pages')")&&str_contains($studentAdmin,"/admin/pages.php"),'legacy protected-pages destination is not redirected to CMS Pages');
must_canonical_authority(str_contains($studentAdmin,'http_response_code(410)')&&str_contains($studentAdmin,'legacyEditorialActions'),'legacy student-area editorial mutations are still active');

foreach(['student_users','course_enrollments','cms_pages.access_level','cms_pages.access_cohort_id','cohort_lesson_releases','course_page_media_slots','cms_pages.parent_page_id'] as $authority)must_canonical_authority(str_contains($contract,$authority),'contract does not name canonical authority '.$authority);
must_canonical_authority(str_contains($contract,'`course_page_sections` não é mais autoridade editorial'),'historical course_page_sections is not explicitly retired in contract');
must_canonical_authority(str_contains($contract,'PR #107'),'material lesson delivery status missing from contract');

echo "canonical-cms-student-authority: ok\n";
