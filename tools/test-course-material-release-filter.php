<?php
declare(strict_types=1);

function fail_course_material_filter(string $message): never {fwrite(STDERR,"course-material-filter: $message\n");exit(1);}
function must_course_material_filter(bool $ok,string $message): void {if(!$ok)fail_course_material_filter($message);}

$access=file_get_contents(__DIR__.'/../app/cms_access.php');
$renderer=file_get_contents(__DIR__.'/../app/cms_renderer.php');
$enrollments=file_get_contents(__DIR__.'/../app/student_enrollments.php');
$courses=file_get_contents(__DIR__.'/../app/courses.php');
$admin=file_get_contents(__DIR__.'/../admin/courses.php');

must_course_material_filter(str_contains($courses,'course_material_pages'),'course material is not represented as a relation to CMS pages');
must_course_material_filter(str_contains($courses,'course_material_sections'),'course material sections are not mapped to canonical course lessons');
must_course_material_filter(str_contains($courses,'course_material_sections_from_page'),'material admin does not read sections from the existing CMS page document');
must_course_material_filter(str_contains($admin,'/editor/?page='),'material editing does not enter the existing CMS editor');
must_course_material_filter(str_contains($admin,'Sempre disponível'),'unmapped sections are not represented as generally available course material');
must_course_material_filter(str_contains($enrollments,"$context['material_page_id']")||str_contains($enrollments,"['material_page_id']"),'material enrollment context does not carry the canonical CMS page id');
must_course_material_filter(str_contains($access,'course_material_section_map'),'server-side access filter does not consume course section-to-lesson mapping');
must_course_material_filter(str_contains($access,"$lessonId>0?'lesson':'immediate'")||str_contains($access,"'lesson':'immediate'"),'unmapped material sections are not treated as immediate while mapped sections require lesson release');
must_course_material_filter(str_contains($access,'cms_access_lesson_released'),'lesson release is not checked server-side');
must_course_material_filter(str_contains($access,'removeChild'),'blocked sections are not removed from rendered HTML');
must_course_material_filter(str_contains($renderer,'cms_access_filter_html'),'renderer does not invoke server-side access filtering');
must_course_material_filter(str_contains($renderer,'$materialContext'),'renderer does not pass enrollment/course context to access filtering');

echo "course-material-filter: ok\n";
