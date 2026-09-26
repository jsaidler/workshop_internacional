<?php
declare(strict_types=1);

function fail_canonical_student_media(string $message): never {fwrite(STDERR,"canonical-student-media-access: $message\n");exit(1);}

$root=dirname(__DIR__);
$endpoint=(string)file_get_contents($root.'/aluno/media.php');
$material=(string)file_get_contents($root.'/app/student_material.php');
$front=(string)file_get_contents($root.'/index.php');

if(!str_contains($endpoint,'student_material_legacy_media_authorize'))fail_canonical_student_media('legacy media endpoint is not routed through the canonical compatibility authorizer');
if(str_contains($endpoint,'student_private_media_authorize('))fail_canonical_student_media('active media endpoint still calls the enrolled-only legacy authorizer');
if(!str_contains($material,'cms_access_page_allowed'))fail_canonical_student_media('legacy media compatibility does not use canonical CMS page authorization');
if(!str_contains($front,'cms_access_filter_html'))fail_canonical_student_media('public runtime is not using canonical CMS section filtering');
if(str_contains($front,'student_page_filter_document'))fail_canonical_student_media('public runtime still uses course_page_sections filtering');
if(!str_contains($material,"if(!current_admin())return '';"))fail_canonical_student_media('pending infographic slots must remain admin-only');

echo "canonical-student-media-access: ok\n";
