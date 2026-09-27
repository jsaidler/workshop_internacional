<?php
declare(strict_types=1);

function cr_expect(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"course-reconciliation: $message\n");exit(1);}}
$root=dirname(__DIR__);
$migration=(string)file_get_contents($root.'/migrations/074_reconcile_positive_course_snapshot.php');
$doc=(string)file_get_contents($root.'/docs/ADMIN_DOMAIN_RECOVERY_2026-09-27.md');

cr_expect(str_contains($migration,"public_page_id']!==1"),'canonical public page must be explicit');
cr_expect(str_contains($migration,"public_page_id']!==6"),'spurious material-page course must be explicit');
cr_expect(str_contains($migration,"slug']!=='26-09-quintas'"),'imported cohort identity must be explicit');
cr_expect(str_contains($migration,'course_id=1,workshop_page_id=1,is_registration_default=0'),'imported cohort must be assigned to canonical course without becoming default');
cr_expect(str_contains($migration,'UPDATE course_lessons SET course_id=1,workshop_page_id=1'),'lessons must move without changing lesson identity');
cr_expect(str_contains($migration,'INSERT OR IGNORE INTO course_material_pages'),'material page relation must move without copying CMS content');
cr_expect(str_contains($migration,'INSERT OR IGNORE INTO course_material_sections'),'section-to-lesson mapping must move with material relation');
cr_expect(str_contains($migration,'INSERT OR IGNORE INTO cohort_lesson_releases'),'existing release rows must be preserved while missing rows are completed');
cr_expect(str_contains($migration,"status='archived',public_page_id=NULL,registration_form_id=NULL"),'spurious course must be archived only after dependencies move');
cr_expect(!str_contains($migration,'DELETE FROM courses'),'reconciliation must not hard-delete the historical course row');
cr_expect(str_contains($doc,'Diagnóstico de produção observado'),'recovery document must record the verified production snapshot');

echo "course-reconciliation: ok\n";
